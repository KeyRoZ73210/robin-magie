<?php

$repoRoot    = dirname(__DIR__);
$projectName = ucfirst(basename($repoRoot));
$urlProject  = strtolower($projectName);

//- 1Password vault name
$titleOp = $projectName . " - WP Admin";
$usernameOp = "admin";

$env = [];
foreach (file($repoRoot . '/.env') ?: [] as $line) {
  $line = trim($line);
  if ($line === '' || str_starts_with($line, '#')) continue;
  if (!str_contains($line, '=')) continue;
  [$key, $val] = explode('=', $line, 2);
  $env[trim($key)] = trim($val, " \t\"");
}

$sshHost   = $env['SSH_HOST'] ?? '';
$sshUser   = $env['SSH_USER'] ?? '';
$themeName = $env['THEME_NAME'] ?? $urlProject;

// Réglés par le setup (scripts/env.php), propres à chaque équipe.
$gitlabGroup = $env['GITLAB_GROUP'] ?? '';
$opVault     = ($env['OP_VAULT'] ?? '') ?: 'Private';
$urlWpAdmin  = rtrim($env['SITE_URL'] ?? "http://{$urlProject}.test", '/') . '/wp-admin';
