<?php

echo "\n";
echo "Setup WordPress via WP-CLI\n";
echo "======================================================\n\n";

// Vérifications préalables
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
  echo "vendor/autoload.php introuvable. Lance d'abord : composer install\n";
  exit(1);
}
require_once $autoload;

$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
  echo ".env introuvable. Lance d'abord : php scripts/setup.php\n";
  exit(1);
}

$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$projectName = basename(dirname(__DIR__));
$siteUrl     = $_ENV['SITE_URL']    ?? "http://{$projectName}.test";
$themeName   = $_ENV['THEME_NAME']  ?? $projectName;
$wpPath      = '--path=' . escapeshellarg(dirname(__DIR__) . '/web');

// Vérifier WP-CLI — installation automatique via Homebrew si absent
$wpCli = trim((string) shell_exec('which wp 2>/dev/null'));
if (empty($wpCli)) {
  echo "WP-CLI introuvable. Tentative d'installation via Homebrew...\n";

  $brew = trim((string) shell_exec('which brew 2>/dev/null'));
  if (empty($brew)) {
    echo "Homebrew introuvable. Installe WP-CLI manuellement : https://wp-cli.org/\n";
    exit(1);
  }

  passthru("{$brew} install wp-cli 2>&1", $brewCode);

  if ($brewCode !== 0) {
    echo "Échec de l'installation via Homebrew.\n";
    exit(1);
  }

  $wpCli = trim((string) shell_exec('which wp 2>/dev/null'));
  if (empty($wpCli)) {
    echo "WP-CLI toujours introuvable après installation. Relance le script.\n";
    exit(1);
  }

  echo "WP-CLI installé.\n";
}
echo "WP-CLI : {$wpCli}\n\n";

// -------------------------------------------------------
// 1. Installation WordPress
// -------------------------------------------------------
$siteTitle = $projectName;

exec("{$wpCli} core is-installed {$wpPath} 2>/dev/null", $_out, $isInstalled);

if ($isInstalled === 0) {
  exec("{$wpCli} option get blogname {$wpPath} 2>/dev/null", $_out);
  $siteTitle = trim($_out[0] ?? $projectName);
  unset($_out);
  echo "WordPress déjà installé. Passage aux étapes suivantes.\n\n";
} else {
  $siteTitle  = $_ENV['WP_ADMIN_TITLE']      ?? $projectName;
  $adminUser  = $_ENV['WP_ADMIN_USER']        ?? 'admin';
  $adminPass  = $_ENV['WP_ADMIN_PASS']        ?? '';
  $adminEmail = $_ENV['WP_ADMIN_EMAIL']       ?? '';
  $acfLicense = $_ENV['WP_ADMIN_LICENCE_ACF'] ?? '';

  if (empty($adminEmail)) {
    echo "⚠️  WP_ADMIN_EMAIL introuvable dans .env — installation annulée.\n";
    exit(1);
  }

  if (empty($adminPass)) {
    echo "⚠️  WP_ADMIN_PASS introuvable dans .env — installation annulée.\n";
    exit(1);
  }

  echo "\nInstallation de WordPress...\n";

  $cmd = "{$wpCli} core install {$wpPath}"
    . " --url=" . escapeshellarg($siteUrl)
    . " --title=" . escapeshellarg($siteTitle)
    . " --admin_user=" . escapeshellarg($adminUser)
    . " --admin_password=" . escapeshellarg($adminPass)
    . " --admin_email=" . escapeshellarg($adminEmail)
    . " --locale=fr_FR"
    . " --skip-email 2>&1";

  exec($cmd, $installOutput, $installCode);

  if ($installCode !== 0) {
    echo "Erreur lors de l'installation :\n";
    echo implode("\n", $installOutput) . "\n";
    exit(1);
  }

  echo "WordPress installé.\n";
  echo "------------------------------------------------------\n";
  echo "   URL admin : {$siteUrl}/wp-admin\n";
  echo "   Login     : {$adminUser}\n";
  echo "------------------------------------------------------\n\n";
}

// -------------------------------------------------------
// 2. Mises à jour WordPress + plugins
// -------------------------------------------------------
echo "Mise à jour de WordPress...\n";
exec("{$wpCli} core update {$wpPath} 2>&1", $out, $code);
echo ($code === 0 ? "WordPress à jour.\n" : "⚠️  " . implode(' ', $out) . "\n");
unset($out);

echo "Mise à jour des plugins...\n";
exec("{$wpCli} plugin update --all {$wpPath} 2>&1", $out, $code);
echo ($code === 0 ? "Plugins à jour.\n" : "⚠️  " . implode(' ', $out) . "\n");
unset($out);

echo "Mise à jour des traductions...\n";
exec("{$wpCli} language core update {$wpPath} 2>&1", $out, $code);
echo ($code === 0 ? "Traductions core à jour.\n" : "⚠️  " . implode(' ', $out) . "\n");
unset($out);
exec("{$wpCli} language plugin update --all {$wpPath} 2>&1", $out, $code);
echo ($code === 0 ? "Traductions plugins à jour.\n\n" : "⚠️  " . implode(' ', $out) . "\n\n");
unset($out);

// -------------------------------------------------------
// 3. Suppression du contenu par défaut
// -------------------------------------------------------
echo "Suppression du contenu par défaut...\n";
exec("{$wpCli} post delete 1 {$wpPath} --force 2>/dev/null");    // Hello World
exec("{$wpCli} post delete 2 {$wpPath} --force 2>/dev/null");    // Sample Page
exec("{$wpCli} post delete 3 {$wpPath} --force 2>/dev/null");    // Privacy Policy
exec("{$wpCli} comment delete 1 {$wpPath} --force 2>/dev/null");
echo "Contenu par défaut supprimé.\n\n";

// -------------------------------------------------------
// 4. Réglages de base
// -------------------------------------------------------
echo "Application des réglages WordPress...\n";

$options = [
  // Identité
  'blogdescription'                => $siteTitle,

  // Lecture
  'blog_public'                    => '0',       // désindexation
  'show_on_front'                  => 'page',    // page statique en accueil
  'rss_use_excerpt'                => '1',       // flux : extrait uniquement

  // Notifications e-mail
  'comments_notify'                => '0',       // nouveau commentaire
  'moderation_notify'              => '0',       // commentaire en attente

  // Commentaires
  'default_comment_status'         => 'closed',
  'default_ping_status'            => 'closed',
  'default_pingback_flag'          => '0',
  'ping_sites'                     => '',
  'require_name_email'             => '1',
  'comment_registration'           => '1',
  'close_comments_for_old_posts'   => '1',
  'close_comments_days_old'        => '0',
  'comment_max_links'              => '0',
  'show_comments_cookies_opt_in'   => '1',
  'thread_comments'                => '1',
  'thread_comments_depth'          => '3',
  'comment_moderation'             => '1',
  'comment_previously_approved'    => '1',

  // Permaliens
  'permalink_structure'            => '/%postname%/',

  // Fuseau & format
  'timezone_string'                => 'Europe/Paris',
  'date_format'                    => 'j F Y',
  'time_format'                    => 'G\hi',
  'start_of_week'                  => '1',
];

foreach ($options as $key => $val) {
  exec("{$wpCli} option update " . escapeshellarg($key) . " " . escapeshellarg($val) . " {$wpPath} 2>/dev/null");
}

exec("{$wpCli} rewrite flush {$wpPath} 2>/dev/null");
echo "Réglages appliqués.\n\n";

// -------------------------------------------------------
// 4b. Page d'accueil statique
// -------------------------------------------------------
echo "Création de la page d'accueil...\n";

exec("{$wpCli} post create {$wpPath}"
  . " --post_type=page"
  . " --post_title=" . escapeshellarg($siteTitle)
  . " --post_name=accueil"
  . " --post_status=publish"
  . " --porcelain 2>/dev/null",
  $pageOut, $pageCode
);

if ($pageCode === 0 && !empty($pageOut[0])) {
  $homePageId = (int) $pageOut[0];
  exec("{$wpCli} option update show_on_front page {$wpPath} 2>/dev/null");
  exec("{$wpCli} option update page_on_front {$homePageId} {$wpPath} 2>/dev/null");
  echo "Page d'accueil créée (ID {$homePageId}) et définie comme page statique.\n\n";
} else {
  echo "Impossible de créer la page d'accueil.\n\n";
}

// -------------------------------------------------------
// 5. Langue
// -------------------------------------------------------
echo "Langue fr_FR...\n";
exec("{$wpCli} language core install fr_FR {$wpPath} 2>/dev/null");
exec("{$wpCli} site switch-language fr_FR {$wpPath} 2>/dev/null");
echo "Langue : fr_FR\n\n";

// -------------------------------------------------------
// 6. Activation des plugins
// -------------------------------------------------------
// Le thème repose sur l'éditeur de blocs et sur Secure Custom Fields (blocs déclarés par block.json).
// Duplication, SVG, interface d'admin… sont des modules de Forge Core (config/forge.php), plus des extensions.
$plugins = [
  'secure-custom-fields',
];

echo "Activation des plugins...\n";
foreach ($plugins as $plugin) {
  exec("{$wpCli} plugin activate {$plugin} {$wpPath} 2>/dev/null", $_out, $code);
  echo ($code === 0 ? "   " : "   ⚠️ ") . " {$plugin}\n";
  unset($_out);
}

if (!empty($acfLicense)) {
  exec("{$wpCli} option update acf_pro_license " . escapeshellarg($acfLicense) . " {$wpPath} 2>/dev/null", $_out, $code);
  echo ($code === 0 ? "   Licence ACF Pro enregistrée.\n" : "   Échec enregistrement licence ACF Pro.\n");
  unset($_out);
}
echo "\n";

// -------------------------------------------------------
// 7. Build du thème + activation
// -------------------------------------------------------
echo "Build du thème \"{$themeName}\"...\n";

$wpDevPath = dirname(__DIR__) . '/wp-dev';
exec("cd " . escapeshellarg($wpDevPath) . " && npm run build 2>&1", $buildOutput, $buildCode);

if ($buildCode !== 0) {
  echo "Erreur lors du build :\n";
  echo implode("\n", $buildOutput) . "\n\n";
  echo "Lance manuellement : cd wp-dev && npm run build\n";
  echo "puis : wp theme activate {$themeName}\n\n";
} else {
  echo "Build terminé.\n";

  exec("{$wpCli} theme activate {$themeName} {$wpPath} 2>/dev/null", $_out, $code);
  echo ($code === 0
    ? "Thème \"{$themeName}\" activé.\n\n"
    : "Thème \"{$themeName}\" introuvable après build (vérifie THEME_NAME dans .env).\n\n"
  );
}

