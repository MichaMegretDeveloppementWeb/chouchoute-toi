#!/bin/bash
# =============================================================================
# deploy.sh — Deploiement Chouchoute-toi (Hostinger Shared Hosting)
#
# Usage :
#   ./deploy.sh           Deploiement standard
#   ./deploy.sh --fresh   Premiere installation (composer install + storage link + migrate)
#   ./deploy.sh --rollback  Revenir au commit precedent
# =============================================================================

set -euo pipefail

# -- Configuration ------------------------------------------------------------

BRANCH="main"
APP_DIR="$(cd "$(dirname "$0")" && pwd)"

# PHP 8.5 minimum (contrainte de composer.json). Sur Hostinger le `php` du PATH
# est une version plus ancienne : on cible le binaire 8.5 explicitement.
# Surchargeable : PHP_BIN=/chemin/vers/php ./deploy.sh
if [ -z "${PHP_BIN:-}" ]; then
    for candidate in /opt/alt/php85/usr/bin/php /usr/local/bin/php85 /usr/bin/php85; do
        if [ -x "$candidate" ]; then
            PHP_BIN="$candidate"
            break
        fi
    done
fi
PHP_BIN="${PHP_BIN:-$(command -v php 2>/dev/null || echo "php")}"

# -- Couleurs -----------------------------------------------------------------

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# -- Fonctions ----------------------------------------------------------------

info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
error()   { echo -e "${RED}[ERREUR]${NC} $1"; }

step() {
    echo ""
    echo -e "${BLUE}━━━ $1 ━━━${NC}"
}

# Remettre le site en ligne en cas d'erreur
cleanup() {
    if [ -f "${APP_DIR}/storage/framework/down" ]; then
        warn "Erreur detectee — remise en ligne du site..."
        $PHP_BIN artisan up 2>/dev/null || true
    fi
}
trap cleanup ERR

# -- Validation ---------------------------------------------------------------

cd "$APP_DIR"

if [ ! -f "artisan" ]; then
    error "Ce script doit etre execute a la racine du projet Laravel."
    exit 1
fi

if [ ! -d ".git" ]; then
    error "Le depot Git n'est pas initialise. Lance d'abord : git clone <repo> ."
    exit 1
fi

# -- Args ---------------------------------------------------------------------

FRESH=false
ROLLBACK=false

for arg in "$@"; do
    case $arg in
        --fresh)    FRESH=true ;;
        --rollback) ROLLBACK=true ;;
        *)          warn "Option inconnue : $arg" ;;
    esac
done

# -- Rollback -----------------------------------------------------------------

if [ "$ROLLBACK" = true ]; then
    step "Rollback au commit precedent"
    CURRENT=$(git rev-parse --short HEAD)
    git reset --hard HEAD~1
    NEW=$(git rev-parse --short HEAD)
    info "Revenu de $CURRENT a $NEW"

    $PHP_BIN artisan optimize:clear
    $PHP_BIN artisan optimize
    success "Rollback termine."
    exit 0
fi

# -- Debut du deploiement ----------------------------------------------------

DEPLOY_START=$(date +%s)
COMMIT_BEFORE=$(git rev-parse --short HEAD 2>/dev/null || echo "inconnu")

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     Deploiement Chouchoute-toi           ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
info "Branche : $BRANCH"
info "Commit actuel : $COMMIT_BEFORE"
info "PHP : $($PHP_BIN -v | head -1)"

# Arret immediat si le binaire PHP ne satisfait pas la contrainte du projet :
# composer install echouerait de toute facon, mais apres la mise en maintenance.
if ! $PHP_BIN -r 'exit(PHP_VERSION_ID >= 80500 ? 0 : 1);'; then
    error "PHP 8.5 minimum requis, or $PHP_BIN est en $($PHP_BIN -r 'echo PHP_VERSION;')."
    error "Relancez avec : PHP_BIN=/opt/alt/php85/usr/bin/php ./deploy.sh"
    exit 1
fi

# -- 1. Mode maintenance ------------------------------------------------------

step "1/6 — Mode maintenance"
$PHP_BIN artisan down --secret="deploy-$(date +%Y%m%d)" --retry=60 2>/dev/null || true
success "Site en maintenance"

# -- 2. Git pull ---------------------------------------------------------------

step "2/6 — Mise a jour du code"

git fetch origin "$BRANCH"

LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse "origin/$BRANCH")

if [ "$LOCAL" = "$REMOTE" ] && [ "$FRESH" = false ]; then
    warn "Aucune mise a jour disponible (deja a jour)."
    $PHP_BIN artisan up
    success "Site remis en ligne."
    exit 0
fi

git reset --hard "origin/$BRANCH"
COMMIT_AFTER=$(git rev-parse --short HEAD)
success "Code mis a jour : $COMMIT_BEFORE → $COMMIT_AFTER"

# Les assets Vite sont commites (pas de build sur le serveur). Un `git commit -am`
# apres un `npm run build` embarque le manifeste sans les nouveaux fichiers
# hashes : on verifie donc que tout ce que le manifeste reference existe, avant
# de toucher aux dependances.
info "Verification des assets compiles..."

if [ ! -f "public/build/manifest.json" ]; then
    error "public/build/manifest.json absent. Sur la machine de dev : npm run build, puis git add -A public/build."
    exit 1
fi

MISSING_ASSETS=$(grep -o '"assets/[^"]*"' public/build/manifest.json \
    | tr -d '"' \
    | sort -u \
    | while read -r asset; do
        [ -f "public/build/$asset" ] || echo "  $asset"
    done)

if [ -n "$MISSING_ASSETS" ]; then
    error "Assets references par le manifeste mais absents du depot :"
    echo "$MISSING_ASSETS"
    error "Sur la machine de dev : npm run build, puis git add -A public/build."
    exit 1
fi

success "Assets compiles coherents"

# -- 3. Composer ---------------------------------------------------------------

step "3/6 — Dependances PHP"

COMPOSER_BIN=$(command -v composer 2>/dev/null || echo "")

if [ -z "$COMPOSER_BIN" ]; then
    # Tenter les chemins courants Hostinger
    for path in ~/bin/composer ~/composer.phar /usr/local/bin/composer; do
        if [ -f "$path" ]; then
            COMPOSER_BIN="$path"
            break
        fi
    done
fi

if [ -z "$COMPOSER_BIN" ]; then
    warn "Composer introuvable. Installation temporaire..."
    curl -sS https://getcomposer.org/installer | $PHP_BIN -- --install-dir="$APP_DIR" --filename=composer 2>/dev/null
    COMPOSER_BIN="$PHP_BIN $APP_DIR/composer"
else
    COMPOSER_BIN="$PHP_BIN $COMPOSER_BIN"
fi

$COMPOSER_BIN install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-progress

success "Dependances installees"

# -- 4. Migrations + Storage ---------------------------------------------------

step "4/6 — Base de donnees"

if [ "$FRESH" = true ]; then
    info "Mode --fresh : migration complete + storage link"
    $PHP_BIN artisan migrate --force
    $PHP_BIN artisan storage:link 2>/dev/null || true
else
    $PHP_BIN artisan migrate --force
fi

success "Migrations executees"

# -- 5. Cache et optimisation --------------------------------------------------

step "5/6 — Optimisation"

$PHP_BIN artisan optimize:clear
$PHP_BIN artisan optimize
$PHP_BIN artisan view:cache

success "Caches regeneres"

# -- 6. Permissions ------------------------------------------------------------

step "6/6 — Permissions"

chmod -R 755 storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage/logs storage/framework 2>/dev/null || true

success "Permissions ajustees"

# -- Fin -----------------------------------------------------------------------

$PHP_BIN artisan up
DEPLOY_END=$(date +%s)
DURATION=$((DEPLOY_END - DEPLOY_START))

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     Deploiement termine avec succes       ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
info "Commit  : $COMMIT_AFTER"
info "Duree   : ${DURATION}s"
info "Date    : $(date '+%d/%m/%Y %H:%M:%S')"
echo ""
success "Le site est en ligne."
