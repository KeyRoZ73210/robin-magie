---
description: Conventions SASS — variables, breakpoints, structure. S'applique à tous les fichiers .sass/.scss/.css.
paths:
  - "**/*.scss"
  - "**/*.css"
  - "**/*.sass"
---

## Déclaration des fonts

Toujours déclarer `woff2` en premier, `woff` en second :

```sass
@font-face
  font-family: 'MaFont'
  src: url('../fonts/ma-font.woff2'), url('../fonts/ma-font.woff')
  font-display: swap
```

- `woff2` en premier — meilleure compression, prioritaire pour les navigateurs modernes
- `woff` en fallback uniquement

---

## Import obligatoire

Toujours en première ligne de chaque fichier bloc :

```sass
@use '../../assets/sass/config' as *
```

- Utiliser `@use` uniquement — jamais `@import` (déprécié en Dart Sass)
- Tous les `@use` doivent être avant toute autre règle
- Le CSS Swiper (`@import 'swiper/swiper-bundle.css'`) est importé **uniquement dans `main.sass`**, jamais dans un fichier de bloc

---

## Structure d'un bloc

```sass
@use '../../assets/sass/config' as *

.nom-bloc
  // styles racine

  &--element
    // styles élément
```

---

## Variables disponibles

### Couleurs

```sass
$color-primary     // var(--color-primary)
$color-secondary   // var(--color-secondary)
$color-tertiary    // var(--color-tertiary)
$color-4
$color-5
$color-6
```

> `$color-X` retourne `var(--color-X)` — ne pas utiliser dans des libs tierces qui attendent une valeur statique CSS.

### Typographie

```sass
$font-primary
$font-secondary
$font-tertiary
$font-serif

// Font-variation-settings
$thin / $light / $regular / $medium / $semi-bold / $bold / $extra-bold / $black
```

---

## Tailles fluides

Toujours utiliser `Max()` ou `max()` pour les valeurs qui doivent être fluides (font-size, padding, etc.) — les deux fonctionnent en Dart Sass moderne :

```sass
font-size: Max(0.875rem, 1.11vw)
padding: Max(0.75rem, 1.2vw) Max(1.5rem, 1.7vw)
```

- Premier argument = valeur minimale en **`rem`**, calibrée sur iPhone SE (320px, base 16px)
- Second argument = valeur fluide en **`vw`**, calibrée pour 1440px de large : `px_maquette / 1440 * 100`
- Jamais de `px` seul pour font-size ou padding
- `letter-spacing` en `em` uniquement (déjà relatif à la font-size)
- Pour les **hauteurs** : toujours utiliser `vw` plutôt que `vh` — le `vh` est instable sur mobile (barre d'URL du navigateur)

---

## Breakpoints

Toujours utiliser les `@include breakpoint_lg()` à la fin du fichier style.sass et reprendre la structure .nom-bloc &--element, jamais `@media` directement :

```sass
@include breakpoint_sm   // 576px
@include breakpoint_md   // 768px
@include breakpoint_lg   // 992px
@include breakpoint_xl   // 1200px
@include breakpoint_xxl  // 1400px
```

---

## BEM

```sass
.nom-bloc
  &--element
    // pas de __
```

- Classe racine = nom du bloc ACF en kebab-case
- Pas de double underscore `__`
- Modificateurs avec `--`
