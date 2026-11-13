<?php
require __DIR__ . '/config.php';

if ($gitlabGroup === '') {
  echo "GITLAB_GROUP vide dans .env — création du dépôt GitLab ignorée.\n\n";
  return;
}

echo "Création du dépôt GitLab...\n";

$glabCli = trim((string) shell_exec('which glab 2>/dev/null'));

if (empty($glabCli)) {
  echo "glab CLI introuvable — crée le dépôt manuellement :\n";
  echo "   1. brew install glab\n";
  echo "   2. glab auth login\n";
  echo "   3. glab repo create {$gitlabGroup}/{$urlProject} --private\n\n";
  return;
}

exec("{$glabCli} auth status 2>&1", $_authOut, $authCode);
unset($_authOut);

if ($authCode !== 0) {
  echo "glab non authentifié — crée le dépôt manuellement :\n";
  echo "   1. glab auth login\n";
  echo "   2. glab repo create {$gitlabGroup}/{$urlProject} --private\n\n";
  return;
}

/* ========================= SET REPO NAME AND URL ========================= */


exec("{$glabCli} auth status --show-token 2>&1", $tokenOut);
$glabToken = '';
foreach ($tokenOut as $line) {
  if (preg_match('/Token found:\s*(\S+)/', $line, $matches)) {
    $glabToken = $matches[1];
    break;
  }
}
unset($tokenOut);
if (empty($glabToken)) {
  echo "Token GitLab introuvable — lance 'glab auth login' puis réessaie.\n";
  return;
}

// Le jeton n'est jamais écrit dans l'URL du dépôt (elle est enregistrée en clair dans .git/config).
// Il est transmis au premier push par des variables d'environnement, le temps de la commande.
$repoUrl = "https://gitlab.com/{$gitlabGroup}/{$urlProject}.git";

exec(
  "{$glabCli} repo create {$gitlabGroup}/{$urlProject} -n \"{$projectName}\" --private --skipGitInit 2>&1",
  $glabOut, $glabCode
); 

/* ========================= CHECK GITLAB CREATION ========================= */

if ($glabCode !== 0) {
  echo "Échec création GitLab : " . implode("\n", $glabOut) . "\n";
  echo "Crée le dépôt manuellement : glab repo create {$gitlabGroup}/{$urlProject} --private\n\n";
  return;
}

echo "Dépôt gitlab.com/{$gitlabGroup}/{$urlProject} créé.\n";

/* ========================= INITIALIZE LOCAL REPO ========================= */

exec("git -C " . escapeshellarg($repoRoot) . " init --initial-branch=main --object-format=sha1 2>&1", $gitOut, $gitCode);
if ($gitCode !== 0) {
  echo "Échec de l'initialisation du dépôt local : " . implode("\n", $gitOut) . "\n";
  return;
}

echo ".git initialisé.\n";

/* ========================= SET REMOTE URL ORIGIN ========================= */

exec("git -C " . escapeshellarg($repoRoot) . " remote add origin " . escapeshellarg($repoUrl) . " 2>&1", $_out, $code);
unset($_out);

/* ========================= ADD ALL FILES ========================= */

exec("git -C " . escapeshellarg($repoRoot) . " add -A 2>&1", $_out, $code);
unset($_out);

/* ========================= COMMIT ========================= */

exec("git -C " . escapeshellarg($repoRoot) . " commit -m 'INIT : First commit' 2>&1", $_out, $code);
unset($_out);

/* ========================= FIRST PUSH ========================= */

putenv('GIT_CONFIG_COUNT=1');
putenv('GIT_CONFIG_KEY_0=http.extraHeader');
putenv('GIT_CONFIG_VALUE_0=Authorization: Basic ' . base64_encode("oauth2:{$glabToken}"));
exec("git -C " . escapeshellarg($repoRoot) . " push --set-upstream origin main 2>&1", $pushOut, $pushCode);
putenv('GIT_CONFIG_COUNT');
putenv('GIT_CONFIG_KEY_0');
putenv('GIT_CONFIG_VALUE_0');
echo ($pushCode === 0
  ? "Premier push effectué.\n\n"
  : "Échec du push : " . implode(' ', $pushOut) . "\n\n"
);

/* ========================= ADD VARIABLES ========================= */

echo "Ajout des variables GitLab CI...\n";

// Clé de déploiement propre au projet : la clé personnelle du développeur n'est jamais envoyée à GitLab.
$sshKeyPath = $_SERVER['HOME'] . '/.ssh/deploy_' . preg_replace('/[^a-z0-9_-]/', '', $urlProject);
if (!file_exists($sshKeyPath)) {
  exec(
    "ssh-keygen -t ed25519 -N '' -C " . escapeshellarg("deploy-{$urlProject}") . " -f " . escapeshellarg($sshKeyPath) . " 2>&1",
    $_keyOut, $keyCode
  );
  unset($_keyOut);
  if ($keyCode !== 0) {
    echo "Impossible de créer la clé de déploiement : {$sshKeyPath}\n";
    return;
  }
  echo "Clé de déploiement créée : {$sshKeyPath}\n";
}
$sshPrivateKey = base64_encode(file_get_contents($sshKeyPath));
$sshPublicKey  = trim((string) file_get_contents($sshKeyPath . '.pub'));

$ciVars = [
  'SSH_HOST'        => ['value' => $sshHost,                    'masked' => false],
  'SSH_USER'        => ['value' => $sshUser,                    'masked' => false],
  'SSH_PRIVATE_KEY' => ['value' => $sshPrivateKey,              'masked' => true],
  'THEME_NAME'      => ['value' => $themeName,                  'masked' => false],
  // 'THEME_NAME_PREPROD' => ['value' => $themeName . '-preprod',  'masked' => false],

  // Type d'environnement — surchargé en "staging" par le job preprod dans .gitlab-ci.yml.
  // Jamais repris du .env : celui-ci vaut "local" et activerait le debug sur le serveur.
  'WP_ENVIRONMENT_TYPE' => ['value' => 'production', 'masked' => false],

  // Base de données — préfixe identique partout
  'WP_DB_PREFIXE'  => ['value' => $env['WP_DB_PREFIXE']  ?? '', 'masked' => false],

  // WP_DB_HOST / WP_DB_NAME / WP_DB_USER / WP_DB_PASSWORD : à SAISIR À LA MAIN dans
  // GitLab (Settings → CI/CD → Variables) avec les identifiants du serveur de production.
  // Non poussés ici car le .env local contient les identifiants de dev.

  // Salts WordPress (base64url) — secrets
  'WP_AUTH_KEY_B64U'         => ['value' => $env['WP_AUTH_KEY_B64U']         ?? '', 'masked' => true],
  'WP_SECURE_AUTH_KEY_B64U'  => ['value' => $env['WP_SECURE_AUTH_KEY_B64U']  ?? '', 'masked' => true],
  'WP_LOGGED_IN_KEY_B64U'    => ['value' => $env['WP_LOGGED_IN_KEY_B64U']    ?? '', 'masked' => true],
  'WP_NONCE_KEY_B64U'        => ['value' => $env['WP_NONCE_KEY_B64U']        ?? '', 'masked' => true],
  'WP_AUTH_SALT_B64U'        => ['value' => $env['WP_AUTH_SALT_B64U']        ?? '', 'masked' => true],
  'WP_SECURE_AUTH_SALT_B64U' => ['value' => $env['WP_SECURE_AUTH_SALT_B64U'] ?? '', 'masked' => true],
  'WP_LOGGED_IN_SALT_B64U'   => ['value' => $env['WP_LOGGED_IN_SALT_B64U']   ?? '', 'masked' => true],
  'WP_NONCE_SALT_B64U'       => ['value' => $env['WP_NONCE_SALT_B64U']       ?? '', 'masked' => true],
];

foreach ($ciVars as $key => $var) {
  $masked = $var['masked'] ? ' --masked' : '';
  exec(
    "{$glabCli} variable set {$key} -v " . escapeshellarg($var['value']) . "{$masked} --protected 2>&1",
    $varOut, $varCode
  );
  echo ($varCode === 0 ? "  ✓ {$key}\n" : "  ✗ {$key} : " . implode(' ', $varOut) . "\n");
  unset($varOut);
}

echo "\n👉 Autorise la clé de déploiement sur le serveur : ajoute cette ligne à ~/.ssh/authorized_keys ({$sshUser}@{$sshHost})\n";
echo "   {$sshPublicKey}\n";

echo "\nSetup terminé.\n";