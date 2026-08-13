---
paths:
  - 'packages/falcon-booking/**'
---

# Falcon Booking

## Commentaires du paquet : régime 2 (doctrine pro), déjà appliqué
Le chantier de nettoyage `commentaires.md` a été fait sur tout falcon-booking. L'état livré est le régime 2, pas le régime 1 : ne pas y revenir en ajoutant du contexte de session.

Ce qui est interdit dans ce paquet :
- toute mention d'un client ou d'un cas d'usage particulier (c'est un paquet généraliste, pas un développement sur mesure) ;
- la narration : historique d'un bug corrigé, « avant, l'écran faisait… », dates de décision, références de lot ou de sprint, renvois au cahier des charges ;
- les tournures genrées désignant l'utilisatrice (« she », « her »), et les données de jeu d'essai reprenant le nom d'un vrai établissement ;
- le tiret cadratin U+2014 (utiliser « : », « , » ou reformuler).

Ce qui est attendu : PHPDoc concis en anglais décrivant ce que fait la méthode, plus le « pourquoi » non évident quand il existe. Rien sur le trivial, rien en décoration de vue. Seuls les TODO restent tolérés, en anglais et sans référence interne.

Les strings runtime (messages de validation, toasts, exceptions, textes UI) restent en français et ne se touchent pas : des tests les assertent.
