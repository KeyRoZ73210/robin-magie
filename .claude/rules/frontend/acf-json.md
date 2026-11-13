---
description: Conventions ACF JSON — nommage des keys, structure des groupes, types de champs. S'applique à tous les fichiers group_*.json.
paths:
  - "**/*.json"
---

## Nommage

| Élément      | Format                         | Exemple                 |
| ------------ | ------------------------------ | ----------------------- |
| Fichier      | `group_[clé].json`, un seul par bloc | `group_block_card_list.json` |
| Key groupe   | `group_block_[nom_bloc]`       | `group_block_card_list` |
| Key champ    | `field_[nom_bloc]_[nom_champ]` | `field_card_list_title` |
| Titre groupe | `Bloc : [Nom Bloc]`            | `Bloc : Card list`      |
| Name champ   | snake_case                     | `background_color`      |

---

## Structure de base d'un groupe

```json
{
  "key": "group_block_[nom_bloc]",
  "title": "Bloc : [Nom Bloc]",
  "fields": [],
  "location": [
    [
      {
        "param": "block",
        "operator": "==",
        "value": "acf/[nom-bloc]"
      }
    ]
  ],
  "active": true
}
```

- `location` : toujours le bloc lui-même (`block == acf/[nom-bloc]`). Avec une autre valeur, les champs n'apparaissent pas dans le bloc.
- Le fichier vit dans le dossier du bloc : `wp-dev/src/blocks/[nom-bloc]/group_*.json`. Les groupes partagés (variables, options) vont dans `wp-dev/src/acf-json/`.

---

## Champ clone couleur de fond

Toujours ajouté en premier dans `fields`, sauf si désactivé explicitement :

```json
{
  "key": "field_[nom_bloc]_color",
  "label": "Couleur de fond",
  "name": "background_clone",
  "type": "clone",
  "clone": ["group_6af19d556ecb0"],
  "display": "seamless",
  "layout": "block",
  "prefix_label": 0,
  "prefix_name": 0
}
```

Le `name` du clone est `background_clone`, jamais `color` : `color` est le nom du menu déroulant cloné, et SCF enregistrerait une valeur vide. Dans `render.php`, la valeur se lit quand même par `$fields['color']`.

---

## Types de champs courants

**Texte**

```json
{
  "key": "field_[nom_bloc]_title",
  "label": "Titre",
  "name": "title",
  "type": "text"
}
```

**Textarea**

```json
{
  "key": "field_[nom_bloc]_text",
  "label": "Texte",
  "name": "text",
  "type": "textarea"
}
```

**Image**

```json
{
  "key": "field_[nom_bloc]_img",
  "label": "Image",
  "name": "img",
  "type": "image",
  "return_format": "array"
}
```

**Lien**

```json
{
  "key": "field_[nom_bloc]_link",
  "label": "Lien",
  "name": "link",
  "type": "link",
  "return_format": "array"
}
```

**Repeater**

```json
{
  "key": "field_[nom_bloc]_items",
  "label": "Éléments",
  "name": "items",
  "type": "repeater",
  "sub_fields": []
}
```

**Booléen**

```json
{
  "key": "field_[nom_bloc]_toggle",
  "label": "Activer",
  "name": "toggle",
  "type": "true_false",
  "default_value": 0
}
```

**Boutons (CTA)**

Toujours nommer le repeater `btns` (jamais `boutons`/`buttons`) — voir [[pug.md]] pour la convention de boucle côté vue. `sub_fields` = clone du groupe partagé `button` + un champ `icon` optionnel :

```json
{
  "key": "field_[nom_bloc]_btns",
  "label": "Boutons",
  "name": "btns",
  "type": "repeater",
  "button_label": "Ajouter un bouton",
  "sub_fields": [
    {
      "key": "field_[nom_bloc]_btns_icon",
      "label": "Icône",
      "name": "icon",
      "type": "image",
      "return_format": "array",
      "parent_repeater": "field_[nom_bloc]_btns"
    },
    {
      "key": "field_[nom_bloc]_btns_content",
      "label": "Bouton",
      "name": "",
      "type": "clone",
      "clone": ["group_63593570001c9"],
      "display": "seamless",
      "layout": "block",
      "prefix_label": 0,
      "prefix_name": 0,
      "parent_repeater": "field_[nom_bloc]_btns"
    }
  ]
}
```

---

## Après toute modification

```bash
php scripts/sync-acf.php
```
