---
paths:
  - 'packages/falcon-booking/src/Actions/**'
---

# Actions

## Un geste sur un ensemble s'écrit en une poignée d'instructions, jamais N fois une
Dès que N est décidé par la donnée (une règle de répétition, une portée, un import) et non par le formulaire, le geste se conçoit comme une écriture de N lignes. Une transaction, un verrou par agenda, et des instructions groupées.

La grille à appliquer avant d'écrire la boucle — chaque chose que fait son corps :
(a) décidée par un attribut de la ligne → sort de la boucle, se trie en mémoire ;
(b) identique pour toutes les lignes → se calcule une fois ;
(c) vraiment propre à chaque ligne → devient une instruction sur N lignes ;
(d) une garde qui ne peut plus se déclencher → se supprime.

(d) est le piège cher : trois mécanismes morts ont été trouvés ainsi dans ce paquet — le saut d'une date « pas libre », une boucle de reprise, un catch sur un refus impossible. Une garde morte impose la boucle et masque les vrais incidents.

Les quatre chemins de série (`RepeatAppointmentAction`, `DeleteSeriesAction`, `UpdateSeriesAction`, `ChangeSeriesRecurrenceAction`) sont tous passés par là et délèguent à `Services/Admin/Booking/Series{Write,Erasure,Rewrite}Service`. Chacun porte un test de budget d'instructions.

Doctrine complète et cas d'école chiffré : `project-management/implementation-rules/gestes-sur-un-ensemble.md`.
