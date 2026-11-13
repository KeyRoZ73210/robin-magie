# Forge — WordPress Modern Boilerplate

[![license](https://flat.badgen.net/badge/license/ISC/orange)](https://fr.wikipedia.org/wiki/Licence_ISC)
[![wordpress](https://flat.badgen.net/badge/wordpress/6.9.1/blue)](https://fr.wordpress.org/)
[![author](https://flat.badgen.net/badge/author/Thomas%20Fleury/green)](https://gitlab.com/thomas.fleury73)

---

# 🚀 Forge — WordPress Modern Boilerplate (v2)

## Overview

WordPress Modern Boilerplate is a modern development architecture designed to build scalable, modular WordPress themes.

Version **2.0.0** introduces a new **Blocks Architecture**, replacing the legacy `modules/` system.

The boilerplate now provides:

- A fully modular **Blocks system**
- `render.php` + `view.pug` separation
- Automated block loading
- Modern JS & Sass toolchain
- Environment-based configuration via `.env`
- CI/CD deployment via GitLab pipelines

---

# 🆕 What's New in v2.0.0

### 🚀 Major Architecture Refactor

- Replace `modules/` with `blocks/`
- Introduce `render.php` (logic) + `view.pug` (markup)
- New block loader system
- Gulp updated to compile `blocks/**/view.pug`
- Strict block encapsulation pattern
- Cleaner separation between theme structure and flexible content

### ⚠️ Breaking Changes

- `modules/` structure is no longer supported
- `template.pug` convention removed
- Old loader system deprecated
- Previous block naming patterns incompatible

---

# 🧱 Blocks Architecture (v2)

Each block follows this structure:

```
src/blocks/
  white-space/
    render.php
    view.pug
    style.sass
    script.js (optional)
    preview.jpg (optional)
```

### render.php

Handles logic, data preparation and rendering.

### view.pug

Contains markup only (compiled to `view.php`).

### style.sass

Block-specific styles (auto-injected into main.sass).

---

# ⚙️ Stack

- Gulp 4
- ESBuild (ES2019 target)
- Dart Sass (`@use` / `@forward`)
- PostCSS (Autoprefixer + cssnano)
- BrowserSync
- Node >= 20
- npm >= 9.4
- Composer >= 2
- phpdotenv

---

# 📦 Installation

---

# 🍺 Prérequis — CLI à installer via Homebrew

Le script de setup automatise plusieurs étapes. Certains outils CLI doivent être installés **une seule fois** sur ta machine.

| Outil                                                         | Usage                                   | Commande                    |
| ------------------------------------------------------------- | --------------------------------------- | --------------------------- |
| [WP-CLI](https://wp-cli.org/)                                 | Installation et configuration WordPress | `brew install wp-cli`       |
| [glab](https://gitlab.com/gitlab-org/cli)                     | Création du dépôt GitLab                | `brew install glab`         |
| [mysql-client](https://formulae.brew.sh/formula/mysql-client) | Connexion à la base de données          | `brew install mysql-client` |

> Les outils manquants déclenchent un warning dans le script de setup — le reste continue sans eux.

Pour tout installer en une fois :

```bash
brew install wp-cli glab mysql-client
```

---

# 📥 Install the Boilerplate

```bash
composer create-project --prefer-source \
  --repository='{"type":"vcs","url":"https://github.com/KeyRoZ73210/Forge.git","no-api":true}' \
  thomasfleury/forge mon-projet dev-main

cd mon-projet
php scripts/setup.php
```

Les dépôts `Forge` et `forge-core` sont privés : la machine doit pouvoir y accéder en HTTPS (gestionnaire d'identifiants Git ou jeton GitHub). `--prefer-source` fait cloner les dépôts par Git, au lieu de télécharger une archive par l'API GitHub (qui répond 404 sans jeton).

> **PHP 8.5.1 :** Composer plante (SIGSEGV) avec cette version précise. Corrigé dans PHP 8.5.12 (`brew upgrade php`).

---

# 🚀 CI/CD — Automated Deployment

The boilerplate includes a `.gitlab-ci.yml` that automatically builds and deploys to your server on every push to the `production` branch.

## How It Works

```
push to production
       ↓
build (npm run build)
       ↓
deploy via rsync + SSH
       ↓
composer install --no-dev on server
```

---

## 1️⃣ Generate a Dedicated SSH Key (no passphrase)

Run locally:

```bash
ssh-keygen -t ed25519 -C "ci@example.com" -f ~/.ssh/gitlab_ci -N ""
```

---

## 2️⃣ Add the Public Key to Your Server

Connect to your server via SSH and run:

```bash
cd ~
mkdir .ssh
chmod 700 .ssh
nano .ssh/authorized_keys
```

Paste the content of:

```bash
cat ~/.ssh/gitlab_ci.pub
```

Then set permissions:

```bash
chmod 600 .ssh/authorized_keys
```

---

## 3️⃣ Encode the Private Key in Base64

```bash
base64 -i ~/.ssh/gitlab_ci | tr -d '\n'
```

Copy the output.

---

## 4️⃣ Add CI/CD Variables in GitLab

`setup-gitlab.php` pushes most CI/CD variables automatically (deploy access + environment config read from `.env`):

| Key                                    | Source      | Visibility |
| -------------------------------------- | ----------- | ---------- |
| `SSH_PRIVATE_KEY`                      | auto-pushed | Masked     |
| `SSH_HOST` / `SSH_USER` / `THEME_NAME` | auto-pushed | Visible    |
| `WP_ENVIRONMENT_TYPE`                  | auto-pushed | Visible    |
| `WP_DB_PREFIXE`                        | auto-pushed | Visible    |
| `WP_*_B64U` (8 salts)                  | auto-pushed | Masked     |

The **4 database credentials must be set manually** in **GitLab → Settings → CI/CD → Variables** with the production server values (the local `.env` holds dev credentials):

| Key              | Visibility |
| ---------------- | ---------- |
| `WP_DB_HOST`     | Visible    |
| `WP_DB_NAME`     | Visible    |
| `WP_DB_USER`     | Visible    |
| `WP_DB_PASSWORD` | Masked     |

At deploy, the pipeline injects all these variables into a managed `# BEGIN ENV … # END ENV` block in the server's `.htaccess` (`SetEnv`), read by `wp-config.php` via `getenv()`. The WordPress rewrite block is left untouched.

---

## 5️⃣ Deploy

Push to the `production` branch to trigger the pipeline:

```bash
git checkout production
git merge main
git push origin production
```

The pipeline will:

1. Build assets via `npm run build`
2. Deploy all files (except `wp-dev/`, `.env`, `vendor/`) via `rsync`
3. Run `composer install --no-dev` on the server

---

# 📁 Project Structure (v2)

```
/your-project
│
├── scripts/                # Setup scripts
│
├── web/                    # WordPress runtime
│   └── wp-content/
│
├── wp-dev/                 # Theme development workspace
│   ├── gulpfile.js
│   ├── package.json
│   └── src/
│       ├── blocks/         # Flexible Content blocks
│       ├── assets/
│       ├── wordpress/
│       └── partials/
│
├── .gitlab-ci.yml          # CI/CD pipeline
├── .env                    # Local environment (never committed)
├── composer.json
└── wp-config.php
```

---

# 🛠 Development

```bash
npm run dev
```

Features:

- Sass compilation
- ESBuild bundling
- Sourcemaps
- BrowserSync
- Watch mode

---

# 🏗 Production Build

```bash
npm run build
```

- CSS minification
- JS minification
- Production-safe output
- Stable cascade order

---

# 🔐 Environment Configuration

`.env` is generated automatically:

```env
WP_DB_NAME=project
WP_DB_USER=root
WP_DB_PASSWORD=root
WP_DB_HOST=localhost

THEME_NAME=project
SITE_URL=http://project.test
```

Security salts are auto-generated and never versioned.

---

# 🔄 Versioning

This project follows **Semantic Versioning**.

- `1.x` → Legacy modules architecture
- `2.0.0` → Blocks architecture refactor
- Future `2.x` → Backward-compatible improvements
- Future `3.0.0` → Next breaking architecture shift

See full details in `CHANGELOG.md`.

---

# 🔒 Security

```bash
npm audit --omit=dev
```

Current status (v2.0.0):
✔ 0 production vulnerabilities

---

# 🧪 Testing

The boilerplate includes a **Playwright** test suite to ensure code quality on every project.

## Available Tests

| Test             | Description                                                 |
| ---------------- | ----------------------------------------------------------- |
| Console errors   | Checks no `console.log`, `error` or `warning` in production |
| Broken links     | Checks all internal links return a 200 status               |
| Load time        | Checks pages load in under 10 seconds                       |
| Image alt        | Checks all images have an `alt` attribute                   |
| External links   | Checks all `target="_blank"` links have `rel="noreferrer"`  |
| Meta description | Checks meta description is present (skipped by default)     |

## Setup

Install Playwright browsers:

```bash
npx playwright install chromium
```

## Configuration

Edit `tests/pages.js` to add your project pages:

```js
export const pages = ["/", "/about", "/contact"];
```

## Run Tests

```bash
npm run test
```

View the HTML report:

```bash
npm run test:report
```

## Skip a Test

Replace `test` with `test.skip` :

```js
test.skip(`check meta description on ${url}`, async ({ page }) => {
```

---

# 🧩 Troubleshooting

### Reset node modules

```bash
rm -rf node_modules package-lock.json
npm install
```

### Environment not loading

- Ensure `.env` exists
- Ensure Composer was executed
- Ensure `vendor/autoload.php` exists

### BrowserSync not working

- Check `SITE_URL`
- Ensure WordPress is running
- Verify `WP_ENVIRONMENT_TYPE` is set

### CI/CD pipeline failing

- Verify the SSH + DB variables are set in GitLab CI/CD settings (DB credentials are manual)
- Ensure the SSH key has no passphrase
- Ensure the public key is in `~/.ssh/authorized_keys` on the server
- Check that `SSH_HOST` contains only the hostname (no username)

---

# 🏁 Conclusion

Version **2.0.0** establishes a clean, scalable and modular WordPress theme architecture.

It formalizes the separation between:

- Theme logic
- Block rendering
- View layer
- Build system
- Environment configuration
- Automated deployment
