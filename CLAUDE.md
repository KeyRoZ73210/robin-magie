# CLAUDE.md — Projet [NOM_PROJET]

## Structure du projet

```
[nom-projet]/
├── wp-dev/src/          ← Sources (Pug, SASS, JS) — toujours modifier ici
│   ├── blocks/          ← Un dossier par bloc ACF
│   ├── assets/
│   │   ├── sass/        ← SASS global (config, framework, utilities)
│   │   ├── fonts/       ← Polices locales
│   │   └── img/         ← Images statiques
│   └── wordpress/       ← PHP global (functions.php, header.php, footer.php, inc/)
├── web/                 ← WordPress compilé — NE JAMAIS modifier directement
│   └── wp-content/themes/[THEME_NAME]/
├── scripts/             ← sync-acf.php, utilitaires
└── .gitlab-ci.yml
```

**Ne jamais écrire dans `web/`** — tout passe par `wp-dev/src/` compilé par Gulp.

---

## Variables SASS disponibles

```sass
// Fonts — à adapter selon le projet
$font-primary
$font-secondary
$font-tertiary
$font-serif

// Font-variation-settings (si variable fonts)
$thin / $light / $regular / $medium / $semi-bold / $bold / $extra-bold / $black

// Couleurs — retournent var(--color-X), pas utilisables comme valeurs statiques CSS
$color-primary
$color-secondary
$color-tertiary
$color-4
$color-5
$color-6
```

---

## Couleurs de fond (ACF)

Le champ `color` génère une classe `colored--{value}` sur la balise racine :

| Valeur ACF | Classe CSS |
|---|---|
| `none` | aucune |
| `primary` | `colored--primary` |
| `secondary` | `colored--secondary` |
| `tertiary` | `colored--tertiary` |
| `4` / `5` / `6` | `colored--4` etc. |

### Ajouter la couleur de fond à un bloc

Dans le groupe de champs du bloc, ajoute un champ **clone** du groupe « VARIABLES - Background colors » (`group_6af19d556ecb0`), réglé ainsi :

- `name` : **`background_clone`**. Ne jamais l'appeler `color` : c'est le nom du menu déroulant qu'il contient, et ACF enregistre alors une valeur vide (la couleur ne s'applique jamais).
- `label` : « Couleur de fond », `display` : `seamless`, `prefix_name` et `prefix_label` à `0`.

Dans `render.php`, on lit le menu déroulant par son nom, `color`, et on borne sa valeur :

```php
$color = $fields['color'] ?? 'none';
if (is_array($color)) {
	$color = $color['color'] ?? 'none';
}
$color = in_array($color, ['none', 'primary', 'secondary', 'tertiary'], true) ? $color : 'none';
```

Dans `view.pug`, la classe se pose sur la balise racine : `colored--<?= esc_attr($color) ?>`.

---

## Menus WordPress

| Slug | Description |
|---|---|
| `header` | Menu de navigation principal |
| `menu` | Menu du panneau plein écran |
| `footer` | Menu footer |
