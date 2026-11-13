# wp-dev — sources du thème

Gulp 4 + esbuild + Dart Sass + Pug + BrowserSync. Les sources de `src/` sont compilées vers
`../web/wp-content/themes/$THEME_NAME`.

## Démarrage

```bash
cd wp-dev
npm install
npm run dev      # compilation + BrowserSync (SITE_URL dans ../.env)
npm run build    # production : CSS/JS minifiés
```

`.env` (à la racine du projet) : `THEME_NAME` (nom du dossier du thème, avec tirets) et `SITE_URL`.

## Structure

- `src/blocks/<nom>/` : un bloc de l'éditeur. Fichiers :
  - `block.json` : métadonnées du bloc (nom `acf/<nom>`, titre, icône, rendu `render.php`) ;
  - `group_block_<nom>.json` : ses champs SCF (voir plus bas) ;
  - `render.php` : lit les champs avec `get_fields()` et décide quoi afficher ;
  - `view.pug` : le HTML, compilé en `view.php` ;
  - `style.sass` : son style, injecté dans `main.css`.
  Les blocs fournis : `hero`, `text-img`, `slider-img`, `white-space`.
  Ajouter un bloc = ajouter un dossier ; `functions.php` enregistre automatiquement son `block.json`.
- `src/acf-json/` : groupes de champs qui ne sont pas liés à un bloc (aucun pour l'instant).
- `src/assets/` : sass global, JS (esbuild), images, polices, sprite SVG.
- `src/wordpress/` : fichiers PHP du thème (copiés tels quels). `inc/contact.php` : formulaire de contact AJAX sécurisé.
  Les templates affichent les blocs avec `the_content()`.

## Champs : Secure Custom Fields (SCF)

Le plugin **Secure Custom Fields** doit être actif (gratuit, wordpress.org, fork d'ACF : répéteur, clone, galerie, pages d'options).
Les fonctions restent `get_field()`, `get_fields()`, `acf_add_options_page()`… Pour passer un site existant d'ACF Pro à SCF : `MIGRATION-SCF.md`.

**Les champs voyagent avec le code.** Chaque bloc a son fichier `group_block_<nom>.json`, à côté de son `block.json`.

1. Créer ou modifier les champs dans **SCF → Groupes de champs**, avec la règle d'emplacement `Bloc == acf/<nom>`, puis **Enregistrer**.
2. SCF écrit le JSON dans le thème construit ; `npm run dev` le recopie aussitôt dans `src/blocks/<nom>/`. Sans `npm run dev` : `npx gulp acf_pull`.
3. Commiter le fichier. Sur un autre site, `npm run build` le remet dans le thème et SCF le charge tout seul.

Règles à respecter :
- **Ne jamais changer la clé** d'un groupe ou d'un champ qui a déjà du contenu : les pages gardent la référence de l'ancienne clé. Convention des nouveaux blocs : groupe `group_block_<nom>`, champ `field_block_<nom>_<champ>`.
- Un bloc a **un seul** fichier de groupe. Si un second fichier vise le même bloc (groupe renommé), le build l'ignore et supprime sa copie dans le thème.
- Le groupe des options du thème (adresse du formulaire de contact) reste déclaré en PHP, dans `src/wordpress/functions.php`.
- Un champ renommé dans l'admin doit l'être aussi dans `render.php` (le hero lit `btns`, pas `buttons`).

## Nettoyage au build

Gulp ne supprime pas de lui-même un fichier retiré de `src/`. Le build s'en charge : à chaque `npm run build` / `npm run dev`,
les `.php` de `blocks/`, `modules/`, `inc/` et de la racine du thème qui n'existent plus dans `src/` sont supprimés.
Images, JSON et autres fichiers ne sont jamais supprimés : ils sont seulement signalés (`⚠`), sauf les groupes de champs en double décrits plus haut.
`npx gulp clean` lance ce nettoyage seul.

## Formulaire de contact

Action AJAX `theme_contact` (POST) : nonce, champ piège `website`, limite de 5 envois / 10 min / IP, champs nettoyés.
Dans le formulaire : `theme_contact_nonce_field()` et `theme_contact_honeypot()`, envoi vers `theme_contact_url()`.
Destinataire : option ACF `mail`, sinon l'email admin (filtre `theme_contact_recipient`).

SMTP (facultatif) : constantes `THEME_SMTP_HOST`, `THEME_SMTP_USER`, `THEME_SMTP_PASS` (+ `THEME_SMTP_PORT`, `THEME_SMTP_SECURE`) dans `wp-config.php`.

## Git

`node_modules/`, `.env` et `_to_delete/` sont ignorés (`.gitignore`). `_to_delete/` reçoit les fichiers qu'un outil a mis de côté sans pouvoir les supprimer : à vider à la main.

## Qualité

- `npm run lint` : stylelint, interdiction de `breakpoint_max()` (mobile first), détection de doublons (jscpd).
  Lancé automatiquement avant chaque `git push` (husky, installé par `npm install`).
  `src/blocks/white-space/style.sass` est exclu de stylelint (le parseur ne lit pas ses boucles).
- `SITE_URL=https://… npm test` : tests Playwright (console, liens cassés, temps de chargement). Pages à tester : `tests/page.js`.
