# L'espace Auth · se connecter, et les comptes

L'espace Auth est à part, au même rang que le site public (`Web`) et le
back-office (`Admin`) · il porte tout ce qui fait entrer quelqu'un dans le
back-office · la connexion et la déconnexion, le mot de passe oublié, la page
qu'ouvre une invitation, et la vie d'un compte. Il a son gabarit, ses routes, ses
écrans et ses services.

Pour le back-office lui-même, voir [Le back-office](back-office.md). Pour les
assets, voir [Gestion des assets avec Vite](assets-vite.md).

## Ses trois domaines

| Domaine | Ce qu'il porte |
|---|---|
| **SignIn** | se connecter, se déconnecter, la limite des tentatives |
| **Password** | le mot de passe oublié, et choisir son mot de passe depuis un lien · l'invitation comme l'oubli |
| **Account** | les comptes · leur naissance, leur adresse, leur suspension, et ce qu'ils reçoivent par e-mail |

## Où vivent ses fichiers

| Ce qu'on cherche | Où |
|---|---|
| les contrôleurs, un par page | `app/Http/Controllers/Auth/{SignIn,Password}/` |
| les formulaires | `app/Livewire/Auth/{SignIn,Password}/` · leurs vues sous `resources/views/livewire/auth/{sign-in,password}/` |
| les pages | `resources/views/auth/{login,forgot-password,choose-password}.blade.php` |
| le gabarit | `resources/views/components/layout/auth.blade.php` · `<x-layout.auth title="…">` |
| la feuille | `resources/css/auth.css` · l'espace n'a pas encore de script à lui |
| les routes | `routes/auth.php`, montées par `bootstrap/app.php` sous le préfixe `admin` et les noms `auth.*` |
| les services | `app/Services/Auth/{SignIn,Password,Account}/` |
| les courriels | `app/Mail/Auth/`, une seule vue, `resources/views/mail/auth/message.blade.php` |
| le démarrage | `app/Providers/AuthServiceProvider.php` |
| les rôles | `app/Enums/Account/Role.php` · ce qu'ils ouvrent, `app/Booking/Roles.php` et `app/Analytics/Roles.php` |
| ce que le site donne à booking | `app/Booking/Accounts.php` |

**Un composant ne fait que valider et appeler un service.** La connexion vit dans
`SignInService`, le choix d'un mot de passe dans `PasswordChoiceService`, le mot de
passe oublié dans `PasswordResetService`, et les liens eux-mêmes — leur durée, leur
adresse, les oublier — dans `PasswordLinksService`.

## Les adresses

| Adresse | Nom | Ce qu'elle ouvre |
|---|---|---|
| `/admin/connexion` | `auth.login` | la connexion |
| `/admin/mot-de-passe-oublie` | `auth.password.forgot` | la demande d'un lien |
| `/admin/mot-de-passe/{compte}/{jeton}` | `auth.password.reset` | choisir un nouveau mot de passe |
| `/admin/bienvenue/{compte}/{jeton}` | `auth.invitation` | choisir son premier mot de passe |
| `/admin/deconnexion` | `auth.logout` | la déconnexion, en `POST` |

**Les adresses restent sous `/admin`**, où un visiteur les cherche · seul le code a
quitté l'administration. La connexion et la demande d'un lien ne s'ouvrent qu'à un
visiteur non connecté (`guest:admin`).

**Les deux liens s'ouvrent aussi à un compte connecté**, qui lit sous quel nom il
l'est, et « Se déconnecter et ouvrir le lien » · le cas ordinaire est l'ordinateur
de l'institut, où une session est restée ouverte quand un nouveau membre ouvre son
invitation. Le bouton passe par `POST /admin/lien/{usage}/{compte}/{jeton}`
(`auth.link.sign-out`), qui déconnecte puis ouvre le lien.

**Ouvrir un lien vérifie son secret**, ce qui coûte un hachage · trente visites par
minute et par visiteur (`AuthServiceProvider::LINK_VISITS_PER_MINUTE`), puis le site
répond 429.

## Le gabarit

`<x-layout.auth>` dessine la page entière · le logo, « Espace d'administration »,
et une carte au centre. Il pose `@falconStyles` avant sa propre feuille, pour que
la nôtre arrive après celles du kit et des paquets et gagne, puis
`@falconScripts` et les marques de Livewire. Il porte la classe du thème
(`falcon_theme_class()`), et dit au navigateur de ne pas transmettre l'adresse de
la page · un lien qui porte son secret dans le chemin ne le donne pas à la page
suivante.

Sa feuille, `resources/css/auth.css`, partage avec celle du back-office la police,
la couleur de la marque et les champs · `resources/css/shared/office-theme.css` et
`resources/css/shared/fields.css`.

## Se connecter

L'adresse et le mot de passe, et « Rester connecté ». **Cinq essais ratés** par
adresse et par visiteur, puis le formulaire attend · le message dit combien de
secondes. La session est renouvelée à chaque connexion, et le compte arrive sur le
tableau de bord, qui mène un compte qu'il n'ouvre pas au premier écran de sa barre.

## Un compte suspendu n'est plus personne

Laravel retrouve le compte connecté à chaque clic par son **fournisseur de
comptes** · `admins`, dans `config/auth.php`. `AuthServiceProvider` lui ajoute une
condition · **un compte suspendu (`suspended_at`) n'existe plus**. La connexion, une
session déjà ouverte, un appareil gardé (« Rester connecté ») et le mot de passe
oublié passent tous par lui, donc une suspension les ferme tous au clic suivant,
sur le site comme dans les écrans des paquets.

## Les liens

| Lien | Courtier (`config/auth.php`) | Table | Durée |
|---|---|---|---|
| l'invitation | `admin_invitations` | `admin_invitation_tokens` | 7 jours |
| le mot de passe oublié | `admins` | `admin_password_reset_tokens` | 60 minutes |

Chacun ne sert qu'une fois, et un nouveau remplace le précédent. **La durée se lit
dans `config/auth.php`**, et l'écran comme le courriel la disent depuis là. Le lien
nomme le compte par son numéro, jamais par son adresse, et se bâtit sur `APP_URL`,
jamais sur l'adresse de la requête · **`APP_URL` doit donc être celle du site en
ligne**, ou les liens mèneront ailleurs.

**La réponse au mot de passe oublié est la même qu'un compte existe ou non**, et elle
prend le même temps · une demi-seconde, `auth.timebox_duration` dans
`config/auth.php`, au-dessus de ce que coûte le hachage d'un lien neuf · le courriel
part **après** la réponse (`defer()`), si bien qu'un envoi lent ne trahit pas une
adresse. Trois demandes par minute et par visiteur.

Une fois le mot de passe choisi, le compte est connecté et arrive sur le tableau de
bord, comme à la connexion.

## Un mot de passe changé ferme les autres sessions

**Choisi par un lien ou changé depuis le Profil**, le mot de passe neuf ferme toute
autre session du compte à son clic suivant, appareils gardés connectés compris ·
c'est le geste de qui craint qu'on ait pris son mot de passe. La session qui l'a
changé reste ouverte.

**Ce qui le tient** · `auth.session` après `auth:admin`, sur les écrans du site
(`bootstrap/app.php`) et sur ceux des paquets (`booking.admin.middleware`,
`analytics.admin.middleware`, `analytics.admin.marketing.middleware`) · Laravel garde
dans la session l'empreinte du mot de passe, et une empreinte qui ne correspond plus
déconnecte. `AuthServiceProvider` le déclare aussi à Livewire, pour qu'un clic le
rejoue. **Un écran neuf du back-office** porte la même pile.

## Les comptes que booking demande

Booking crée des praticiens, et leur donne un accès en demandant au site de leur
créer un compte · `app/Booking/Accounts.php` répond à ce que booking lui demande,
**à l'emplacement que booking attend** (`booking.accounts`), sans rien à lier. Booking
ne lui passe jamais un praticien · des numéros de compte, un nom, une adresse et un
rôle · et garde lui-même quel compte est celui de quelle fiche. Le site écrit les
phrases que la fiche et la liste montrent de chaque compte.

**La fiche ne demande aucun mot de passe** · le site invite par e-mail, et le mot de
passe qu'il range est tiré au hasard, jamais donné. Elle porte la rangée « Accès » ·
« Donner un accès », puis le rôle, Propriétaire ou Collaborateur.

| Geste dans l'équipe | Ce qui arrive au compte |
|---|---|
| créer une fiche avec un accès | un compte à l'adresse de la fiche, sans mot de passe connu, avec le rôle choisi, et l'invitation part une fois la fiche enregistrée |
| donner un accès à l'adresse d'un compte qui existe déjà | ce compte est repris, avec son mot de passe, et rien ne part · c'est le cas du compte d'origine |
| changer le rôle | le compte prend le rôle choisi · le sien, et celui du dernier propriétaire, ne changent pas, et la fiche dit pourquoi |
| corriger l'adresse d'une invitation en attente | le lien part à la nouvelle adresse, l'ancien ne sert plus |
| corriger l'adresse d'un compte en service | l'identifiant change, le mot de passe reste, et les deux adresses sont prévenues · un message qui ne part pas se dit, avec l'adresse de connexion |
| « Renvoyer l'invitation » | un nouveau lien remplace le précédent · une minute entre deux envois, sauf si le précédent n'est pas parti |
| archiver, réactiver | le compte est suspendu, puis rendu tel qu'il était · un appareil resté connecté (« Rester connecté ») doit se reconnecter · une session ordinaire de moins de deux heures, elle, reprend |
| supprimer | le compte et ses liens partent avec la fiche |

**Le dernier propriétaire et le compte qui agit** ne sont jamais rétrogradés,
suspendus ni supprimés depuis l'équipe · et deux propriétaires qui se retirent l'un
l'autre au même instant ne laissent pas le back-office sans propriétaire ·
`OwnerLockService` tient les propriétaires en service, un par un par numéro croissant,
le temps de l'écriture, et le second geste refuse sous ce verrou.

## Les messages

Trois courriels, `app/Mail/Auth/` · l'invitation, le lien du mot de passe oublié,
et l'adresse de connexion changée. **`MAIL_FROM_ADDRESS` et `MAIL_FROM_NAME` les
signent**, et le nom qu'ils donnent au site est celui de `MAIL_FROM_NAME` · un seul
réglage pour les trois, à mettre sur le serveur. Un envoi qui échoue s'écrit au
journal, et la liste de l'équipe dit une invitation qui n'est pas partie.

## Le premier compte

```bash
php artisan db:seed --class=AdminSeeder --force
```

Rejouable sans danger · il crée le compte d'origine, Propriétaire, et ne touche
plus à rien s'il existe. Le mot de passe initial est `password` · le changer
aussitôt sur `/admin/profil`.
