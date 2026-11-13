---
description: Conventions JavaScript — classes ES6, Swiper, patterns. S'applique à tous les fichiers .js.
paths:
  - "**/*.js"
---

## Pattern de base

Chaque bloc exporte une classe ES6 en default, PascalCase :

```js
export default class NomBloc {
  constructor(el) {
    this.el = el
    this.init()
  }

  init() {
    // logique du bloc
  }
}
```

- `el` = élément DOM racine du bloc
- `init()` toujours présent, même vide
- Nom de classe en PascalCase dérivé du nom du bloc (`card-list` → `CardList`)

---

## Avec Swiper

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
      loop: true,
      // autres paramètres selon le bloc
    })
  }
}
```

---

## Règles

- Pas de `var` — utiliser `const` / `let`
- Pas de jQuery
- Commentaires en français uniquement si le comportement est non-évident
