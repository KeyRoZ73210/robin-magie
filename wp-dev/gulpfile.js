const nodePath = require('path');
require('dotenv').config({ path: nodePath.resolve(__dirname, '../.env') });

const themeName = process.env.THEME_NAME;
const siteUrl = process.env.SITE_URL;

const esbuild = require('esbuild');

const gulp = require('gulp');
const sass = require('gulp-dart-sass');
const autoprefixer = require('autoprefixer');
const postcss = require('gulp-postcss');
const postcssImport = require('postcss-import');
const cssnano = require('cssnano');
const browserSync = require('browser-sync').create();
const plumber = require('gulp-plumber');
const pug = require('gulp-pug');
const rename = require('gulp-rename');
const svgSprite = require('gulp-svg-sprite');
const inject = require('gulp-inject');

if (!themeName) throw new Error('THEME_NAME manquant dans .env');
// siteUrl uniquement requis pour BrowserSync (dev local)

/* ------------------------------------------------------------------------- *\
**  === PATHS                                                                *|
\* ------------------------------------------------------------------------- */
const base = `../web/wp-content/themes/${themeName}`;

const paths = {
  modules: {
    src: './src/modules/**/*.php',
    dest: base + '/modules/',
  },

  blocks: {
    // ✅ Uniquement les views de block
    pug: './src/blocks/**/view.pug',
    // ✅ Uniquement les styles de block
    sass: './src/blocks/**/style.sass',

    // ✅ Copie tout sauf view.pug et style.sass
    assets: [
      './src/blocks/**/*',
      '!./src/blocks/**/view.pug',
      '!./src/blocks/**/style.sass',
    ],

    dest: base + '/blocks/',
    watch: './src/blocks/**/*',
  },

  sass: {
    src: './src/assets/sass/main.sass',
    dest: base + '/assets/css',
    watch: ['./src/assets/sass/**/*.sass', './src/assets/sass/**/*.scss', './src/assets/sass/**/*.css'],
  },

  js: {
    entry: './src/assets/js/main.js',
    watch: './src/assets/js/**/*.js',
    dest: base + '/assets/js',
  },

  php: {
    src: './src/wordpress/**/*.php',
    dest: base + '/',
  },

  svg: {
    src: './src/assets/img/svg/*.svg',
    dest: base + '/assets/img',
  },

  font: {
    src: './src/assets/fonts/*',
    dest: base + '/assets/fonts',
  },

  screenshot: {
    src: './src/wordpress/*.png',
    dest: base + '/',
  },

  style: {
    src: './src/wordpress/style.css',
    dest: base + '/',
  },

  // Champs SCF (JSON local) : src/acf-json -> thème (chargement), thème -> src/acf-json (enregistrement depuis l'admin)
  acfJson: {
    src: './src/acf-json/*.json',
    dest: base + '/acf-json/',
    watch: [base + '/acf-json/*.json', base + '/blocks/*/group_*.json'],
    back: './src/acf-json/',
  },

  settings: {
    src: './src/settings/*.json',
    dest: base + '/settings/',
    watch: './src/settings/*.json',
  },
};

/* ------------------------------------------------------------------------- *\
**  === BROWSER SYNC                                                         *|
\* ------------------------------------------------------------------------- */
function browser_init(open = false) {
  browserSync.init({
    // Mode snippet : pas de proxy, on accède au site via son vhost custom.
    // Le client BS est injecté manuellement dans footer.php.
    open,
    port: 3000,
    ui: false,
    notify: false,
    server: false,
    proxy: false,
    files: [
      `${base}/**/*.php`,
      `${base}/**/*.js`,
      `${base}/blocks/**/*`,
      `${base}/assets/img/**/*`,
      `${base}/assets/fonts/**/*`,
      `${base}/settings/**/*`,
    ],
  });
}

/* ------------------------------------------------------------------------- *\
**  === TASKS : MODULES (runtime)                                            *|
\* ------------------------------------------------------------------------- */
function settings_copy() {
  return gulp.src(paths.settings.src, { allowEmpty: true })
    .pipe(gulp.dest(paths.settings.dest));
}

// src/acf-json -> thème. Le dossier est créé même vide : SCF n'écrit son JSON que si le dossier existe.
function acf_json_push(done) {
  require('fs').mkdirSync(paths.acfJson.dest, { recursive: true });
  return gulp.src(paths.acfJson.src, { allowEmpty: true })
    .pipe(gulp.dest(paths.acfJson.dest))
    .on('end', done);
}

// Bloc (« acf/hero ») auquel un fichier de groupe de champs est rattaché, d'après sa règle d'emplacement.
function acf_group_block(file) {
  const fs = require('fs');
  try {
    const group = JSON.parse(fs.readFileSync(file, 'utf8'));
    for (const rules of group.location || []) {
      for (const rule of rules) {
        if (rule.param === 'block' && rule.operator === '==') return String(rule.value);
      }
    }
  } catch (e) { /* JSON illisible : traité comme sans bloc */ }
  return null;
}

// Un dossier de bloc a UN fichier de groupe. Un second fichier visant le même bloc est un reste (groupe renommé) : à ignorer.
function acf_block_has_group(dir, block, except) {
  const fs = require('fs');
  if (!block || !fs.existsSync(dir)) return false;
  return fs.readdirSync(dir)
    .filter((f) => /^group_.*\.json$/.test(f) && f !== except)
    .some((f) => acf_group_block(nodePath.join(dir, f)) === block);
}

// thème -> src : à chaque enregistrement d'un groupe de champs dans l'admin, le JSON revient dans src (donc dans Git).
//  - acf-json/*.json         -> src/acf-json/
//  - blocks/NOM/group_*.json -> src/blocks/NOM/
// On n'écrit que si le contenu a changé : sinon la copie src -> thème relancerait la surveillance en boucle.
function acf_json_pull(done) {
  const fs = require('fs');
  const themeRoot = base;
  const pairs = [];
  const addDir = (from, to) => {
    if (!fs.existsSync(from)) return;
    fs.readdirSync(from)
      .filter((f) => /^group_.*\.json$/.test(f) || (from.endsWith('acf-json') && f.endsWith('.json')))
      .forEach((f) => pairs.push([nodePath.join(from, f), nodePath.join(to, f)]));
  };
  addDir(nodePath.join(themeRoot, 'acf-json'), './src/acf-json');
  const blocksDir = nodePath.join(themeRoot, 'blocks');
  if (fs.existsSync(blocksDir)) {
    fs.readdirSync(blocksDir, { withFileTypes: true })
      .filter((d) => d.isDirectory())
      .forEach((d) => addDir(nodePath.join(blocksDir, d.name), nodePath.join('./src/blocks', d.name)));
  }
  pairs.forEach(([from, to]) => {
    const content = fs.readFileSync(from);
    if (fs.existsSync(to) && fs.readFileSync(to).equals(content)) return;
    // Fichier du thème absent de src alors que src a déjà le groupe de ce bloc : reste d'un renommage, pas une nouveauté.
    if (!fs.existsSync(to) && to.includes(nodePath.join('src', 'blocks')) && acf_block_has_group(nodePath.dirname(to), acf_group_block(from), nodePath.basename(to))) return;
    fs.mkdirSync(nodePath.dirname(to), { recursive: true });
    fs.writeFileSync(to, content);
  });
  done();
}

function php_modules() {
  return gulp.src(paths.modules.src, { allowEmpty: true })
    .pipe(gulp.dest(paths.modules.dest))
    .pipe(browserSync.stream());
}

/* ------------------------------------------------------------------------- *\
**  === TASKS : BLOCKS                                                       *|
\* ------------------------------------------------------------------------- */

// Copie tout sauf view.pug et style.sass (jpg, js, php, etc.)
function blocks_assets_copy() {
  // encoding: false = copie à l'identique (Gulp 5 lit en UTF-8 par défaut, ce qui abîme les images).
  return gulp.src(paths.blocks.assets, { dot: true, allowEmpty: true, encoding: false })
    .pipe(gulp.dest(paths.blocks.dest))
    .pipe(browserSync.stream());
}

// view.pug -> view.php (même dossier)
function blocks_pug_to_php() {
  return gulp.src(paths.blocks.pug, { allowEmpty: true })
    .pipe(plumber())
    .pipe(pug({ pretty: '\t' }))
    .on('error', function (err) { console.log(err); })
    .pipe(rename({ basename: 'view', extname: '.php' })) // => view.php
    .pipe(gulp.dest(paths.blocks.dest))
    .pipe(browserSync.stream());
}

// Injecte tous les blocks/**/style.sass dans main.sass
// Nécessite dans main.sass :
//   // inject:blocks
//   // endinject
function inject_blocks_sass() {
  return gulp.src(paths.sass.src, { allowEmpty: true })
    .pipe(inject(gulp.src(paths.blocks.sass, { read: false, allowEmpty: true }), {
      starttag: '// inject:blocks',
      endtag: '// endinject',
      transform: function (filepath) {
        // filepath exemple : /src/blocks/white-space/style.sass
        const normalized = filepath.replace(/\\/g, '/');
        const fromSrc = normalized.replace(/^\/?src\//, ''); // retire "src/"
        const withoutExt = fromSrc.replace(/\.sass$/, '');
        // main.sass est dans src/assets/sass -> ../../blocks/...
        // return `@use "../../${withoutExt}"`;
        // Si tu veux "déverser" les mixins/vars globalement :
        return `@use "../../${withoutExt}" as *`;
      }
    }))
    .pipe(gulp.dest('./src/assets/sass/'));
}

const gulp_blocks = gulp.series(blocks_assets_copy, blocks_pug_to_php, inject_blocks_sass);

/* ------------------------------------------------------------------------- *\
**  === TASKS : WORDPRESS PHP / SASS / ASSETS                                *|
\* ------------------------------------------------------------------------- */
function wordpress_php() {
  return gulp.src(paths.php.src, { allowEmpty: true })
    .pipe(gulp.dest(paths.php.dest))
    .pipe(browserSync.stream());
}

function style_sass() {
  const plugins = [postcssImport(), autoprefixer()];

  return gulp.src(paths.sass.src, { allowEmpty: true, sourcemaps: true })
    .pipe(plumber())
    .pipe(sass({ loadPaths: ['node_modules'] }).on('error', sass.logError))
    .pipe(postcss(plugins))
    .pipe(gulp.dest(paths.sass.dest, { sourcemaps: '.' }))
    .pipe(browserSync.stream({ match: '**/*.css' }));
}

function min_css() {
  const plugins = [postcssImport({ path: ['node_modules'] }), autoprefixer(), cssnano()];

  return gulp.src(nodePath.join(paths.sass.dest, 'main.css'), { allowEmpty: true })
    .pipe(postcss(plugins))
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.sass.dest));
}

function js_bundle(done) {
  const isProd = process.env.NODE_ENV === 'production';

  esbuild.build({
    entryPoints: [paths.js.entry],
    bundle: true,
    minify: isProd,
    sourcemap: true,
    target: ['es2019'],
    outfile: nodePath.join(paths.js.dest, isProd ? 'main.min.js' : 'main.js'),
  }).then(() => {
    browserSync.reload();
    done();
  }).catch((err) => {
    console.error(err);
    done(err);
  });
}

function svg_sprite() {
  return gulp.src(paths.svg.src, { allowEmpty: true })
    .pipe(svgSprite({
      mode: {
        symbol: {
          render: { css: true, scss: true },
          dest: '.',
          sprite: 'sprite.svg',
          example: false,
        }
      }
    }))
    .pipe(gulp.dest(paths.svg.dest))
    .pipe(browserSync.stream());
}

function font_copy() {
  return gulp.src(paths.font.src, { allowEmpty: true, encoding: false })
    .pipe(gulp.dest(paths.font.dest))
    .pipe(browserSync.stream());
}

function screenshot_copy() {
  return gulp.src(paths.screenshot.src, { allowEmpty: true, encoding: false })
    .pipe(gulp.dest(paths.screenshot.dest))
    .pipe(browserSync.stream());
}

function style_copy() {
  return gulp.src(paths.style.src, { allowEmpty: true })
    .pipe(gulp.dest(paths.style.dest))
    .pipe(browserSync.stream());
}

/* ------------------------------------------------------------------------- *\
**  === NETTOYAGE                                                            *|
\* ------------------------------------------------------------------------- */

// Supprime du thème les fichiers qui n'existent plus dans src (Gulp copie, mais ne supprime jamais).
// Concerne blocks/, modules/, inc/ et les fichiers à la racine du thème. Les CSS/JS compilés ne sont pas touchés.
// Seuls les fichiers .php obsolètes sont supprimés. Les fields.php (peuvent être la seule copie de champs), les images et le reste sont seulement signalés.
function clean_orphans(done) {
  const fs = require('fs');
  const themeDir = nodePath.resolve(base);
  if (!fs.existsSync(themeDir)) return done();

  const walk = (dir, root = dir) => {
    if (!fs.existsSync(dir)) return [];
    return fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
      const full = nodePath.join(dir, e.name);
      return e.isDirectory() ? walk(full, root) : [nodePath.relative(root, full).split(nodePath.sep).join('/')];
    });
  };

  // Fichiers attendus dans le thème, d'après src.
  const expected = new Set();
  walk('./src/blocks').forEach((f) => {
    if (f.endsWith('/style.sass')) return;                       // compilé dans main.css
    expected.add('blocks/' + f.replace(/\/view\.pug$/, '/view.php'));
  });
  walk('./src/modules').forEach((f) => expected.add('modules/' + f));
  walk('./src/wordpress').forEach((f) => expected.add(f));

  // Fichiers présents dans les dossiers gérés par le build.
  const actual = [
    ...walk(nodePath.join(themeDir, 'blocks')).map((f) => 'blocks/' + f),
    ...walk(nodePath.join(themeDir, 'modules')).map((f) => 'modules/' + f),
    ...walk(nodePath.join(themeDir, 'inc')).map((f) => 'inc/' + f),
    ...fs.readdirSync(themeDir, { withFileTypes: true }).filter((e) => e.isFile()).map((e) => e.name),
  ];

  const kept = [];
  actual.forEach((f) => {
    const name = f.split('/').pop();
    if (expected.has(f) || name === '.DS_Store') return;
    // Groupe de champs en double dans le thème (reste d'un renommage) : supprimé si src a déjà le groupe de ce bloc.
    if (/^group_.*\.json$/.test(name) && f.startsWith('blocks/')) {
      const srcDir = nodePath.join('./src', nodePath.dirname(f));
      if (acf_block_has_group(srcDir, acf_group_block(nodePath.join(themeDir, f)), name)) {
        fs.rmSync(nodePath.join(themeDir, f));
        console.log('  nettoyage : supprimé ' + f + ' (doublon du groupe de ce bloc)');
        return;
      }
    }
    if (name === 'fields.php' || !name.endsWith('.php')) { kept.push(f); return; }   // images, JSON… : jamais supprimés automatiquement
    fs.rmSync(nodePath.join(themeDir, f));
    console.log('  nettoyage : supprimé ' + f + ' (absent de src)');
  });
  kept.forEach((f) => console.log('  ⚠ ' + f + ' absent de src, non supprimé' + (f.endsWith('fields.php') ? ' (à convertir en JSON puis supprimer à la main)' : ' (à ajouter dans src ou supprimer à la main)')));

  // Dossiers devenus vides.
  const prune = (dir) => {
    if (!fs.existsSync(dir)) return;
    fs.readdirSync(dir, { withFileTypes: true }).filter((e) => e.isDirectory()).forEach((e) => prune(nodePath.join(dir, e.name)));
    if (!fs.readdirSync(dir).filter((n) => n !== '.DS_Store').length) fs.rmSync(dir, { recursive: true, force: true });
  };
  ['blocks', 'modules', 'inc'].forEach((d) => {
    const p = nodePath.join(themeDir, d);
    fs.existsSync(p) && fs.readdirSync(p, { withFileTypes: true }).filter((e) => e.isDirectory()).forEach((e) => prune(nodePath.join(p, e.name)));
  });
  done();
}

/* ------------------------------------------------------------------------- *\
**  === WATCH                                                                *|
\* ------------------------------------------------------------------------- */
function watch_files() {
  browser_init();

  // blocks (views/styles/assets) -> rebuild + recompile css
  gulp.watch(paths.blocks.watch, gulp.series(gulp_blocks, style_sass, min_css, js_bundle));
  gulp.watch(paths.settings.watch, settings_copy);

  // champs SCF enregistrés depuis l'admin -> src/acf-json
  gulp.watch(paths.acfJson.watch, acf_json_pull);

  // sass global
  gulp.watch(paths.sass.watch, gulp.series(style_sass, min_css));

  // js
  gulp.watch(paths.js.watch, js_bundle);

  // php
  gulp.watch(paths.php.src, wordpress_php);

  // modules runtime
  gulp.watch(paths.modules.src, php_modules);

  // assets
  gulp.watch(paths.svg.src, svg_sprite);
  gulp.watch(paths.font.src, font_copy);
  gulp.watch(paths.screenshot.src, screenshot_copy);
  gulp.watch(paths.style.src, style_copy);
}

/* ------------------------------------------------------------------------- *\
**  === EXPORTS                                                              *|
\* ------------------------------------------------------------------------- */
const gulp_sass = gulp.series(style_sass);
const gulp_js = gulp.series(js_bundle);
const gulp_php = gulp.series(wordpress_php);
const gulp_modules = gulp.series(php_modules);
const gulp_svg = gulp.series(svg_sprite);
const gulp_font = gulp.series(font_copy);
const gulp_screenshot = gulp.series(screenshot_copy);
const gulp_style = gulp.series(style_copy);

const gulp_dist = gulp.parallel(
  gulp_blocks,
  gulp_modules,
  gulp_sass,
  gulp_php,
  gulp_js,
  gulp_font,
  gulp_screenshot,
  gulp_svg,
  gulp_style,
  settings_copy,
  acf_json_push
);

// Avant de nettoyer, on récupère les JSON enregistrés depuis l'admin (rien ne se perd).
const gulp_build = gulp.series(acf_json_pull, clean_orphans, gulp_dist, min_css);
const gulp_watch = gulp.series(acf_json_pull, clean_orphans, gulp.parallel(gulp_dist, watch_files));

module.exports = {
  blocks: gulp_blocks,
  sass: gulp_sass,
  js: gulp_js,
  svg: gulp_svg,
  dist: gulp_dist,
  watch: gulp_watch,
  acf_pull: acf_json_pull,
  clean: gulp.series(acf_json_pull, clean_orphans),
  build: gulp_build,
  default: gulp_watch
};