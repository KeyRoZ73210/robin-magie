---
description: Conventions PHP — render.php, helpers ACF, syntaxe. S'applique à tous les fichiers .php.
paths:
  - "**/*.php"
---

## render.php — Pattern fixe

`render.php` est le `renderTemplate` du `block.json`. Il lit les champs, les nettoie, puis inclut la vue. Pas de fonction, pas de Flexible Content.

```php
<?php

$fields = get_fields() ?: [];
$color  = $fields['color'] ?? 'none';
// Garde-fou : un champ cloné peut revenir sous forme de tableau ou de valeur vide.
if (is_array($color)) {
	$color = $color['color'] ?? 'none';
}
$color  = in_array($color, ['none', 'primary', 'secondary', 'tertiary'], true) ? $color : 'none';
$title  = (string) ($fields['title'] ?? '');

// Rien à montrer : rien sur le site, un cadre vide seulement dans l'éditeur.
if ($title === '' && !($is_preview ?? false)) {
	return;
}

include __DIR__ . '/view.php';
```

- Chaque valeur est typée dans `render.php` et échappée dans `view.pug` (`esc_html`, `esc_attr`, `esc_url`).
- Une valeur qui finit dans une classe CSS passe par une liste de valeurs autorisées (`in_array`).

---

## Helpers ACF

```php
get_field('nom_du_champ')            // Champ simple
getThemeOption('nom_option')         // Theme Options (Options Page)
wp_img($id)                          // Image avec srcset
```

---

## Images et SVG

```php
// Image standard
if (isset($img['url'])) {
  echo wp_img($img['id']);
}

// SVG avec fallback image
$url = getThemeOption('logo')['url'];
$ext = pathinfo($url, PATHINFO_EXTENSION);
if ($ext == 'svg') {
  echo file_get_contents($url);
} else {
  echo wp_img(getThemeOption('logo')['id']);
}
```

---

## Syntaxe

- Syntaxe alternative obligatoire pour les structures de contrôle inline :
  `if() :` / `endif;` — `foreach() :` / `endforeach;`
- Nommage fonctions helpers : snake_case (`wp_img`, `wp_env`)
- Nommage fonctions thème : camelCase (`getThemeOption`)
- Variables : snake_case

---

## Header / Footer

Pas de `render.php` — le PHP est inline directement dans le Pug.
