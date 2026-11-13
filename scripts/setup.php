<?php
if (!file_exists(__DIR__ . '/env.php')) {
  echo "⚠️  Le script env.php est introuvable.\n";
  exit(1);
} else {
  require_once __DIR__ . '/env.php';
}

if (!file_exists(__DIR__ . '/npm.php')) {
  echo "⚠️  Le script npm.php est introuvable.\n";
  exit(1);
} else {
  require_once __DIR__ . '/npm.php';
}

if (file_exists(__DIR__ . '/vhost.sh')) {
  exec("bash " . escapeshellarg(__DIR__ . '/vhost.sh') . " " . escapeshellarg($projectName) . " " . escapeshellarg(dirname(__DIR__) . '/web'));
} else {
  echo "⚠️  Le script vhost.sh est introuvable.\n";
}


echo "\n";
// echo "🎉 Création des branches main et production\n";
// echo "------------------------------------------------------\n\n";

// exec("git checkout -b main");
// exec("git checkout -b production");
// exec("git checkout main");

echo "------------------------------------------------------\n";
echo "🎉 Projet \"{$projectName}\" initialisé avec succès !\n";
echo "------------------------------------------------------\n\n";
echo "👉 Avant de continuer :\n\n";
echo "   1. Redémarre MAMP\n";
echo "   2. Crée la base de données sur le panel OVH\n\n";
echo "Appuie sur [Entrée] quand c'est fait...";
fgets(STDIN);

require_once __DIR__ . '/setup-1p.php';

require_once __DIR__ . '/setup-wordpress.php';

require_once __DIR__ . '/setup-gitlab.php';

// Le mot de passe admin ne reste pas en clair dans le .env une fois WordPress installé et le mot de passe dans 1Password.
$envPath = dirname(__DIR__) . '/.env';
if (!empty($opSaved) && is_file($envPath)) {
  $envLines = array_filter(file($envPath), fn($line) => !str_starts_with($line, 'WP_ADMIN_PASS='));
  file_put_contents($envPath, implode('', $envLines));
  echo "Mot de passe admin retiré du .env (il est dans 1Password).\n\n";
} else {
  echo "⚠️  Mot de passe admin toujours dans le .env (WP_ADMIN_PASS) : enregistre-le dans 1Password puis supprime la ligne.\n\n";
}

echo "======================================================\n";
echo "🎉 Projet prêt !\n\n";
echo "   Admin  : {$siteUrl}/wp-admin\n";
echo "   Front  : {$siteUrl}\n\n";
echo "   Prochaine étape : cd wp-dev && npm run watch\n";
echo "======================================================\n\n";