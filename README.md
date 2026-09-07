# Chouchoute-toi

Le site d'Amandine David-Cruz, technicienne en extensions de cils à domicile
sur le bassin lémanique. Vitrine publique, prise de rendez-vous en ligne et
back-office.

Laravel 13, PHP 8.5, Livewire 4, Tailwind 4, Vite.

## Démarrer

Le site est servi par Herd sur https://chouchoute-toi.test. Il n'y a rien à
lancer pour l'afficher.

```bash
composer setup     # dépendances, .env, clé, migrations, build
npm run dev        # rechargement à chaud pendant qu'on travaille
```

## Les trois paquets

L'essentiel des écrans ne vit pas ici. Le projet assemble trois paquets
maison, développés en parallèle dans `packages/` et liés par symlink :

| Paquet | Ce qu'il apporte |
|---|---|
| `falcon/ui-kit` | le design system : composants Blade, thème clair et sombre |
| `falcon/booking` | la réservation, l'agenda, le catalogue des prestations |
| `falcon/analytics` | la mesure d'audience et le module marketing |

Chacun sait dire si son installation tient debout :

```bash
php artisan booking:check
php artisan analytics:check
php artisan ui-kit:check
```

Ces trois commandes valent d'être lancées après une mise à jour de paquet.
Presque tout ce qu'elles vérifient échoue en silence : un import oublié laisse
des écrans sans style, un collecteur non posé ne mesure rien, et rien nulle
part ne s'en plaint.

## Ce qui appartient à l'hôte

Les six pages publiques (accueil, prestations, à propos, avis, contact,
mentions légales), le graphe de données structurées du site, la coquille du
back-office dans laquelle les écrans des paquets viennent se poser, et la
grille tarifaire dans `config/tarifs.php`.

L'identité de l'entreprise vit dans `config/entreprise.php`, une seule fois.
Elle était recopiée dans chaque vue, et une correction en oubliait toujours
une.

## Travailler dessus

```bash
composer test      # la suite
composer analyse   # PHPStan
npm run build      # après toute mise à jour de paquet
```

Le `npm run build` n'est pas facultatif après un `composer update` d'un
paquet : votre feuille a été fabriquée en lisant ses vues telles qu'elles
étaient au dernier build.

Les paquets ont leur propre chaîne, à lancer depuis leur dossier et jamais
depuis ici, leurs dépendances n'étant pas les nôtres :

```bash
cd packages/falcon-booking && composer qa
```

## Documentation

- [`docs/structure-fichiers.md`](docs/structure-fichiers.md) : où va quoi,
  contrôleurs, vues, partials
- [`docs/assets-vite.md`](docs/assets-vite.md) : une page, une feuille, un
  point d'entrée
- [`docs/back-office.md`](docs/back-office.md) : la coquille partagée et
  comment y accrocher un paquet
