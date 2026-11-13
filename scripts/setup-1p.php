<?php

require __DIR__ . '/config.php';

$adminUser = $env['WP_ADMIN_USER'] ?? $usernameOp;
$adminPass = $env['WP_ADMIN_PASS'] ?? '';

if (empty($adminPass)) {
  echo "⚠️  WP_ADMIN_PASS introuvable dans .env — création 1Password annulée.\n\n";
  return;
}

echo "Création de l'entrée 1Password \"{$titleOp}\"...\n";

exec("op item create --category=login"
  . " --title=" . escapeshellarg($titleOp)
  . " --vault=" . escapeshellarg($opVault)
  . " --url=" . escapeshellarg($urlWpAdmin)
  . " username=" . escapeshellarg($adminUser)
  . " password=" . escapeshellarg($adminPass)
  . " 2>&1",
  $out, $code
);

// Lu par setup.php : le mot de passe n'est retiré du .env que s'il est bien dans 1Password.
$opSaved = ($code === 0);

echo ($opSaved ? "Entrée créée.\n\n" : "⚠️  Échec 1Password (code {$code}).\n\n");
