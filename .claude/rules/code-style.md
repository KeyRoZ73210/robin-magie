---
description: Conventions de code transversales — nommage, commentaires, Git. S'applique à tous les fichiers du projet.
---

## Nommage

### CSS / Pug
- Classe racine = nom du bloc ACF (ex: bloc `card-list` → `.card-list`)
- BEM simplifié : `&--element` uniquement, pas de `__`
- Classes utilitaires globales : `colored--{value}`, `container-ext`, `container-fluid`

### JavaScript
- Classes ES6 en PascalCase : `CardList`, `HeroSlider`
- Fichiers en kebab-case : `scripts.js` (nom fixe par bloc)

### PHP
- Fonctions helpers : snake_case (`wp_img`, `wp_env`)
- Fonctions thème custom : camelCase (`getThemeOption`)
- Variables : snake_case

---

## Commentaires

- En français uniquement
- Uniquement quand le comportement est non-évident
- Pas de commentaires qui décrivent ce que le code fait déjà clairement

---

## Git — Messages de commit

Format : `TYPE: description courte`

| Type | Usage |
|---|---|
| `FEAT:` | Nouvelle fonctionnalité |
| `FIX:` | Correction de bug |
| `REFACTOR:` | Restructuration sans changement fonctionnel |
| `CHORE:` | Maintenance, dépendances, config |
| `DOCS:` | Documentation uniquement |

---

## Règles générales

- Ne jamais modifier `web/` directement — tout passe par `wp-dev/src/`
- Après toute modif d'un `group_*.json` à la main : lancer `php scripts/sync-acf.php`
