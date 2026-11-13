# Passer un site de ACF Pro à Secure Custom Fields (SCF)

Testé sur WordPress 7.0 + SCF 6.9.8 (site de test, pas sur un site en production).

## Principe

SCF est le fork d'ACF maintenu par WordPress.org. Il garde les mêmes fonctions (`get_field`, `have_rows`, `acf_add_options_page`…)
et les mêmes tables : les valeurs déjà saisies restent lisibles. Il offre gratuitement répéteur, clone, galerie et pages d'options.
Il ne dépend plus de la licence de l'agence.

Dans ce boilerplate, les champs de chaque bloc sont dans des fichiers JSON versionnés (`src/blocks/<nom>/group_block_<nom>.json`),
et le groupe des options du thème est déclaré en PHP : rien à recréer à la main sur un nouveau site.

## Étapes (sur une copie du site, jamais directement en production)

1. Sauvegarder la base. Exporter les groupes de champs existants (ACF → Outils → Exporter, JSON).
2. Installer et activer « Secure Custom Fields » : cela désactive ACF / ACF Pro.
3. Déployer le thème construit (`npm run build`). Les blocs et leurs champs se chargent depuis les JSON.
4. Reprendre les anciennes sections du contenu flexible sous forme de blocs (voir ci-dessous).
5. Vérifier : une page composée de blocs, la page d'options du thème (e-mail du formulaire de contact), le formulaire de contact.

## Points de vigilance

- Les valeurs d'un ancien champ flexible `structure` ne sont **pas** converties en blocs : chaque section doit être recréée en bloc.
- Un ancien groupe créé dans l'admin et enregistré en base peut doubler un groupe du thème (deux boîtes « Structure ») : le désactiver dans SCF → Groupes de champs.
- Les groupes de clones (couleur de fond…) sont des groupes sans emplacement ; garder leur clé telle quelle.
- Vérifier les types de champs utilisés par tes modules : certains champs d'ACF Pro peuvent être absents ou différents dans SCF.
- Un champ renommé doit l'être aussi dans le `render.php` du bloc, sinon il s'affiche vide sans erreur.
