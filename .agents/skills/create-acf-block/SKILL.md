---
name: acf-block-creator
description: Génère la structure complète d'un bloc ACF WordPress (acf-*.json, render.php, view.pug, style.sass, scripts.js) à partir d'un nom, d'une description fonctionnelle et d'une liste de champs. Utiliser ce skill dès que l'utilisateur mentionne la création d'un bloc, d'un module, d'une section WordPress, ou qu'il donne un nom de bloc avec des champs ACF. Même si la demande est courte ("crée un bloc cards avec titre et image"), déclencher ce skill immédiatement.
---

# ACF Block Creator

Génère les 5 fichiers d'un bloc ACF à partir d'un nom, d'une description et de champs.

## Ce que le skill produit

```
wp-dev/src/blocks/[nom-bloc]/
├── acf-[nom-bloc].json   ← Groupe ACF avec champs + clone couleur de fond
├── render.php            ← Fonction d'inclusion + helper create_*
├── view.pug              ← Template HTML avec PHP ACF câblé
├── style.sass            ← Styles avec @use config
└── scripts.js            ← Classe ES6
```

---

## Étape 1 — Collecter les infos

Si l'utilisateur n'a pas tout fourni, demander :

1. **Nom du bloc** (snake_case, ex: `card-list`)
2. **Description fonctionnelle** : ce que le bloc affiche/fait visuellement
3. **Liste des champs ACF** : nom + type pour chaque champ
4. **Options** :
   - Slider Swiper ? (non par défaut) → si oui, demander les paramètres (loop, autoplay, slidesPerView, etc.)
   - Couleur de fond ? (**oui par défaut**, clone du groupe `group_6112421b0f6ff`)

---

## Étape 2 — Générer les fichiers

### `acf-[nom-bloc].json`

Lire `/mnt/skills/user/acf-block-creator/references/acf-field-types.md` pour les schémas de champs.

Règles :
- `key` du groupe : `group_[nom_bloc]_001`
- `key` des champs : `field_[nom_bloc]_[nom_champ]`
- `title` du groupe : `MODULE - [Nom Bloc]`
- Toujours ajouter le champ clone couleur de fond EN DERNIER dans le tableau `fields` (sauf si l'utilisateur le désactive explicitement) :

```json
{
  "key": "field_[nom_bloc]_color",
  "label": "Couleur de fond",
  "name": "color",
  "type": "clone",
  "clone": ["group_6112421b0f6ff"],
  "display": "seamless",
  "layout": "block",
  "prefix_label": 0,
  "prefix_name": 0
}
```

- `location` : toujours `post_type == page`
- `active`: false

---

### `render.php`

Pattern fixe à respecter :

```php
<?php

  function [nom_bloc]($section) {
    extract($section);
    include __DIR__.'/view.php';
  }

  if (!function_exists('create_[nom_bloc]_block')) {
      function create_[nom_bloc]_block($post_id, $json_data) {
          $data = is_array($json_data) ? $json_data : json_decode($json_data, true);
          if (!$data) return false;

          $nom_du_champ_flexible = 'structure';

          $row = [
              'acf_fc_layout' => '[nom_bloc]',
              // un champ par ligne, basé sur les champs ACF du bloc
              '[champ]' => $data['[champ]'] ?? '',
          ];

          if ($post_id) {
              return add_row($nom_du_champ_flexible, $row, $post_id);
          }

          return false;
      }
  }

?>
```

---

### `view.pug`

Règles :
- Balise racine : `section.[nom-bloc]` — ajouter `.colored--secondary` si couleur de fond activée
- Structure HTML basée sur la **description fonctionnelle** du bloc
- PHP inline pour chaque champ :
  - Texte/textarea : `<?php if(strlen($champ)) : ?>` … `<?= $champ ?>` … `<?php endif; ?>`
  - Image : `<?php if(isset($[champ]['url'])) : ?>` … `<?= wp_img($[champ]['id']) ?>` … `<?php endif; ?>`
  - Booléen/select : `<?php if($champ) : ?>` … `<?php endif; ?>`
  - Repeater : `<?php if(have_rows('[champ]')) : while(have_rows('[champ]')) : the_row(); ?>` … `<?php endwhile; endif; ?>`
- Toujours wrapper dans `.container-ext > .row > .col-12` sauf si la description indique autrement (ex: image plein écran)
- Si Swiper : ajouter `.swiper > .swiper-wrapper > .swiper-slide` avec les slides en repeater

---

### `style.sass`

Règles :
- Toujours commencer par `@use '../../assets/sass/config' as *`
- Si Swiper : ajouter `@import 'swiper/swiper-bundle.css'` **après** le `@use`
- Classe racine : `.nom-bloc`
- Structure de base adaptée à la description (flex/grid selon le layout)
- Toujours utiliser les mixins breakpoint : `@include breakpoint-lg`, etc. — jamais `@media` directement
- Toujours utiliser les variables `$color-X` pour les couleurs

---

### `scripts.js`

Règles :
- Classe ES6 exportée en default : `export default class NomBloc`
- Constructor prend `el` en paramètre
- Sans Swiper : classe minimale avec `constructor(el)` + `init()` vide prête à l'emploi
- Avec Swiper :

```js
import Swiper from 'swiper/bundle'

export default class NomBloc {
  constructor(el) {
    this.el = el
    this.swiper = null
    this.init()
  }

  init() {
    this.swiper = new Swiper(this.el.querySelector('.swiper'), {
      // paramètres fournis par l'utilisateur
      loop: true,
      // etc.
    })
  }
}
```

---

## Étape 3 — Rappel post-génération

Après avoir généré tous les fichiers, toujours rappeler :

```
✅ Fichiers générés dans wp-dev/src/blocks/[nom-bloc]/

À faire :
1. Enregistrer le bloc dans le Flexible Content ACF (ajouter 'nom-bloc' comme layout)
2. Importer la classe dans le JS global
3. php scripts/sync-acf.php
```