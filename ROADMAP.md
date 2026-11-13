# Roadmap

Planned evolution of the WordPress Modern Boilerplate.

---

## v2.x — Internal Stabilization & Team Standardization

Focus: consolidating the new Blocks architecture for team usage.

### Architecture

* Harden block loader system
* Add strict block naming conventions
* Introduce optional block config file (`block.json`)
* Add block-level dependency registration (JS/CSS enqueue system)
* Improve fallback handling for missing or invalid blocks

### Developer Experience

* Add block scaffolding command (CLI generator)
* Improve error handling during render
* Add project health diagnostics command
* Improve onboarding documentation for new developers

### Setup Script

* **Auto-install `op` CLI (1Password) via Homebrew** si absent lors du setup — à discuter en équipe (dépendance implicite vs. prérequis documenté)
* Investiguer l'échec silencieux de la création de la page d'accueil (`wp post create`) — retirer le `2>/dev/null` pour identifier la cause

### Build System

* Improve Sass injection order determinism
* Optimize incremental rebuild performance
* Improve production build safety checks
* Reduce unnecessary rebuild triggers

### Code Quality

* Introduce ESLint (no TypeScript yet)
* Add optional Prettier config
* Add consistent formatting rules
* Enforce minimal architectural standards

---

## v2.x (Advanced Internal Phase)

Focus: preparing for possible public distribution.

* Add “clean mode” installer (no internal dependencies)
* Improve README clarity
* Improve setup script robustness
* Add environment validation checks
* Improve Composer project metadata

---

## v3.0.0 — Future Evolution (Only if justified)

⚠️ Only if necessary and justified by real constraints.

Possible directions:

* Migration to Vite (if build complexity increases)
* Native ESM architecture
* Full TypeScript support (optional)
* Strong separation between:

  * Workspace
  * Theme package

If no strong reason appears → remain on optimized Gulp.

---

## Infrastructure Evolution

Since multiple devs are involved:

* Evaluate Docker or DDEV (only if onboarding becomes painful)
* Standardize local environment
* Add CI pipeline for:

  * Build validation
  * Linting
  * Production CSS test
* Optional automated deployment flow

---

## Long-Term Principles

* Stability over trend adoption
* Minimal dependencies
* Predictable builds
* Clear architecture
* Low cognitive load for new devs
* Maintain backward compatibility within major versions

---

# Important Strategic Advice

Given your situation:

You should **not** move to Vite yet.

Your current stack:

* Gulp
* ESBuild
* Dart Sass
* PostCSS

is:

* stable
* predictable
* low complexity
* enough for agency work

Vite would only be justified if:

* you need HMR on complex SPAs
* you move toward headless
* you adopt heavy TS architecture

For now: consolidate v2.