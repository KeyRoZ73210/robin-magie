---
description: Structure du projet, règles d'architecture, commandes, CI/CD. S'applique à toute modification de fichiers ou dossiers.
---

## Règle absolue

**Ne jamais écrire dans `web/`** sauf si explicitement demandé. Tout passe par `wp-dev/src/` compilé par Gulp.

---

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

---

## Structure d'un bloc ACF

```
wp-dev/src/blocks/mon-bloc/
├── block.json      ← Déclaration du bloc (`name`: `acf/mon-bloc`, `renderTemplate`: `render.php`)
├── render.php      ← Lit et nettoie les champs, puis inclut view.php
├── view.pug        ← Template HTML (compilé en view.php)
├── style.sass      ← Styles du bloc
├── scripts.js      ← JS du bloc (classe ES6), facultatif
├── group_*.json    ← Champs SCF du bloc — un seul fichier par bloc
└── preview.webp    ← Aperçu dans l'inserteur, facultatif
```

- Tout dossier contenant un `block.json` est enregistré automatiquement comme bloc de l'éditeur.
- Pas de `render.php` ni de `block.json` pour `header` et `footer` — le PHP est inline dans le Pug.
- Après toute modification d'un `group_*.json` à la main : lancer `php scripts/sync-acf.php`
- Un groupe modifié dans l'admin est réécrit dans le thème, puis recopié dans `src` par Gulp (`npm run dev` ou `npm run build`).

---

## Commandes

```bash
# Depuis wp-dev/
npm run build        # Build une fois
npm run dev          # Watcher dev

# Synchronisation ACF
php scripts/sync-acf.php
```

---

## Menus WordPress

| Slug | Description |
|---|---|
| `header` | Menu de navigation principal |
| `menu` | Menu du panneau plein écran |
| `footer` | Menu footer |

---

## Formulaire de contact

```
wp-dev/src/wordpress/inc/contact.php   ← action AJAX `theme_contact` (nonce, champ piège, limite par IP)
```

Voir `wp-dev/README.md` pour l'utilisation (champs du formulaire, destinataire, SMTP).
L'ancien dossier `mailer/` (send-mail.php, templates) a été retiré.

Champs du formulaire : `nom`, `prenom`, `email` (obligatoire), `message` (obligatoire)

---

## CI/CD GitLab

| Branche | Job | Serveur |
|---|---|---|
| `main` | `deploy_preprod` | `~/sites/[PROJET]-preprod/web/` |
| `production` | `deploy_prod` | `~/sites/[PROJET]/web/` |

### Variables GitLab requises

| Variable | Description |
|---|---|
| `SSH_PRIVATE_KEY` | Clé privée SSH encodée en base64 |
| `SSH_HOST` | Hostname serveur |
| `SSH_USER` | Utilisateur SSH |
| `THEME_NAME` | `[THEME_NAME]` |
| `THEME_NAME_PREPROD` | `[THEME_NAME]-preprod` |

### Variables d'environnement serveur

```
WP_DB_NAME / WP_DB_USER / WP_DB_PASSWORD / WP_DB_HOST
WP_ENVIRONMENT_TYPE=staging      # preprod
WP_ENVIRONMENT_TYPE=production

# Clés WordPress encodées en base64url
WP_AUTH_KEY_B64U / WP_SECURE_AUTH_KEY_B64U / ...
```

### wp-config.php

- Lit les variables via `wp_env()` : `$_ENV` → `$_SERVER` → `getenv()`
- En local : lit le `.env` via dotenv, clés en clair
- En prod : clés encodées en base64url
- `WP_DEBUG` activé uniquement si `WP_ENVIRONMENT_TYPE=local`
