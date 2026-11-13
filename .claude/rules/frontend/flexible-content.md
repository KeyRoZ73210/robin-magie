---
description: Enregistrement des blocs de l'éditeur (block.json), champs SCF, import JS global. S'applique lors de la création ou modification d'un bloc.
---

## Après chaque création de bloc

Les blocs sont des blocs natifs de l'éditeur, déclarés par un `block.json`. Il n'y a plus de Flexible Content `structure` : rien à enregistrer à la main, `functions.php` enregistre tout dossier de `blocks/` qui contient un `block.json`.

### 1. Relier les champs au bloc

Dans le `group_*.json` du bloc, la `location` vise le bloc lui-même :

```json
"location": [[{ "param": "block", "operator": "==", "value": "acf/[nom-bloc]" }]]
```

Un dossier de bloc contient **un seul** fichier `group_*.json`. Un second fichier (reste d'une copie d'un autre bloc) crée un groupe en double.

### 2. Compiler puis synchroniser

```bash
cd wp-dev && npm run build
php scripts/sync-acf.php
```

### 3. Importer la classe JS (seulement si le bloc a un `scripts.js`)

Dans `wp-dev/src/assets/js/main.js` :

```js
import NomBloc from '../../blocks/nom-bloc/scripts.js'

document.querySelectorAll('.nom-bloc').forEach(el => new NomBloc(el))
```

---

## Checklist création de bloc

- [ ] Fichiers générés dans `wp-dev/src/blocks/[nom-bloc]/` (`block.json`, `render.php`, `view.pug`, `style.sass`, `group_*.json`)
- [ ] `name` du `block.json` = `acf/[nom-bloc]`, identique à la `location` du groupe de champs
- [ ] Un seul `group_*.json` dans le dossier
- [ ] `npm run build` puis `php scripts/sync-acf.php` lancés
- [ ] Classe JS importée et instanciée dans `main.js` si le bloc a un `scripts.js`
