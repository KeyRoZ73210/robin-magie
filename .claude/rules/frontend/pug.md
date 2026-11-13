---
description: Conventions Pug — structure HTML, PHP inline, helpers ACF. S'applique à tous les fichiers .pug.
paths:
  - "**/*.pug"
---

## Structure d'un bloc

Balise racine : `section.nom-bloc` (nom du bloc ACF en kebab-case).

Structure par défaut :

```pug
section.nom-bloc
  .container-ext
    .row
      .col-12
        // contenu
```

- Utiliser `.container-fluid` uniquement si demandé explicitement (composant hors colonnes)
- Ne jamais supprimer le wrapper `.container-ext > .row > .col-12` sans instruction explicite

---

## PHP inline

Syntaxe alternative obligatoire :

```pug
<?php if($champ) : ?>
  p= $champ
<?php endif; ?>
```

### Champs par type

**Texte / textarea**

```pug
<?php if(strlen($champ)) : ?>
  p= $champ
<?php endif; ?>
```

**Image**

```pug
<?php if(isset($img['url'])) : ?>
  .bloc--img
    <?= wp_img($img['id']) ?>
<?php endif; ?>
```

**SVG (logo, icône)**

```pug
<?php $url = getThemeOption('logo')['url']; $ext = pathinfo($url, PATHINFO_EXTENSION); if($ext == 'svg') : ?>
  <?= file_get_contents($url); ?>
<?php else : ?>
  <?= wp_img(getThemeOption('logo')['id']) ?>
<?php endif; ?>
```

**Booléen / select**

```pug
<?php if($champ) : ?>
  // ...
<?php endif; ?>
```

**Repeater**

`extract($section)` rend directement le repeater disponible comme tableau PHP (chaque ligne = tableau associatif des sous-champs) — ne jamais utiliser `have_rows()` / `the_row()` / `get_sub_field()` dans un `view.pug`, c'est l'API `render.php` qui a déjà résolu les champs en amont :

```pug
<?php if(isset($items[0])) : ?>
  <?php foreach($items as $item) : ?>
    .bloc--item
      // $item['sous_champ']
  <?php endforeach; ?>
<?php endif; ?>
```

- Condition d'affichage : `isset($champ[0])`, jamais `have_rows()`
- Boucle : `foreach($champ as $item) : ... : endforeach;`, jamais `while(have_rows())`

---

## Boutons (CTA)

Champ repeater nommé `btns` (jamais `boutons`/`buttons`), sub_fields = clone du groupe ACF partagé `button` (`group_63593570001c9`, seamless) + éventuellement un champ `icon` (image) en plus du clone.

```pug
<?php if(isset($btns[0])) : ?>
  .nom-bloc--actions
    <?php foreach($btns as $btn) : ?>
      .nom-bloc--btn
        include ../button/variables-btns.pug
    <?php endforeach; ?>
<?php endif; ?>
```

- Wrapper de la liste : `.nom-bloc--actions`
- Wrapper par bouton : `.nom-bloc--btn`
- Le partial `include ../button/variables-btns.pug` attend une variable `$btn` (la ligne courante du foreach) — jamais de `have_rows`/`get_sub_field` autour
- Icône optionnelle : `$btn['icon']` (le partial gère déjà le `isset($btn['icon']['url'])`)

---

## Helpers ACF

```php
get_field('nom_du_champ')       // Champ simple
getThemeOption('nom_option')    // Theme Options (Options Page)
wp_img($id)                     // Image avec srcset
```

---

## Header / Footer

Pas de `render.php` intermédiaire — le PHP est inline directement dans le Pug.

---

## Couleurs de fond

Le champ `color` génère une classe utilitaire `colored--{value}` sur la balise racine :

| Valeur ACF      | Classe               |
| --------------- | -------------------- |
| `none`          | aucune classe        |
| `primary`       | `colored--primary`   |
| `secondary`     | `colored--secondary` |
| `tertiary`      | `colored--tertiary`  |
| `4` / `5` / `6` | `colored--4` etc.    |
