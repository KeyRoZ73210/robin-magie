<?php

echo "\n";
echo "🚀 Bienvenue dans le setup de ton projet WordPress !\n";
echo "------------------------------------------------------\n";

// Récupération du nom du projet à partir du nom du dossier courant
$projectName = basename(getcwd());

// Création du préfixe de la base à partir du nom du projet
$projectPrefix = strtolower(substr(preg_replace('/[^a-zA-Z0-9]/', '', $projectName), 0, 2)) . '_';

echo "------------------------------------------------------\n";

// Demande à l'utilisateur de nommer la BDD
echo "Quel est le nom de la BDD ? : ";

$projectBDD = trim(fgets(STDIN));

echo "------------------------------------------------------\n";

// Demande à l'utilisateur qu'est-ce que l'admin de la BDD
echo "Quel est l'utilisateur de la BDD ? : ";

$projectUser = trim(fgets(STDIN));

echo "------------------------------------------------------\n";

// Demande à l'utilisateur le mot de passe de la BDD
echo "Quel est le mot de passe de la BDD ? : ";

$projectPassword = trim(fgets(STDIN));

echo "------------------------------------------------------\n";

// Demande à l'utilisateur l'url de connexion de la BDD
echo "Quel est l'URL de connexion à la BDD ? : ";

$projectURL_BDD = trim(fgets(STDIN));

echo "------------------------------------------------------\n";

// Demande à l'utilisateur le type d'URL qu'il souhaite utiliser pour son projet
echo "Quel type d'URL veux-tu utiliser ?\n";
echo "1. {$projectName}.test\n";
echo "2. localhost:8888/{$projectName}\n";
echo "3. Personnalisée\n";
echo "Ton choix (1/2/3) : ";

$choice = trim(fgets(STDIN));

switch ($choice) {
  case '2':
    $siteUrl = "localhost:8888/{$projectName}";
    break;
  case '3':
    echo "Entre ton URL personnalisée : ";
    $siteUrl = trim(fgets(STDIN));
    break;
  default:
    $siteUrl = "{$projectName}.test";
    break;
}

echo "------------------------------------------------------\n";

// Serveur de déploiement : aucune valeur par défaut, chaque projet a le sien (laisser vide pour le régler plus tard dans le .env).
echo "SSH Host : ";
$sshHost = trim(fgets(STDIN));

echo "SSH User : ";
$sshUser = trim(fgets(STDIN));

echo "Groupe GitLab du dépôt (laisser vide pour ne pas créer de dépôt) : ";
$gitlabGroup = trim(fgets(STDIN));

echo "Coffre 1Password [Private] : ";
$input   = trim(fgets(STDIN));
$opVault = $input ?: 'Private';

$sshPrivateKey = null;
foreach (['id_ed25519', 'id_ecdsa', 'id_rsa'] as $candidate) {
  if (trim((string) shell_exec("ls ~/.ssh/{$candidate} 2>/dev/null")) !== '') {
    $sshPrivateKey = "~/.ssh/{$candidate}";
    break;
  }
}
if ($sshPrivateKey) {
  echo "Clé SSH détectée : {$sshPrivateKey}\n";
} else {
  echo "SSH Private Key : ";
  $sshPrivateKey = trim(fgets(STDIN));
}

echo "------------------------------------------------------\n";
echo "Compte administrateur WordPress\n";
echo "------------------------------------------------------\n";

echo "Titre du site [{$projectName}] : ";
$input   = trim(fgets(STDIN));
$wpTitle = $input ?: $projectName;

echo "Utilisateur admin [admin] : ";
$input   = trim(fgets(STDIN));
$wpAdmin = $input ?: 'admin';

// Pas d'adresse par défaut : WordPress envoie ici les alertes et les réinitialisations de mot de passe.
do {
  echo "Email admin : ";
  $wpEmail = trim(fgets(STDIN));
} while (!filter_var($wpEmail, FILTER_VALIDATE_EMAIL));

echo "Licence ACF Pro [optionnel] : ";
$wpAcf   = trim(fgets(STDIN));

$chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@%^&*';
$wpPass = '';
for ($i = 0; $i < 32; $i++) {
  $wpPass .= $chars[random_int(0, strlen($chars) - 1)];
}
echo "Mot de passe admin généré.\n";

echo "------------------------------------------------------\n";

// Fonction pour définir une valeur par défaut si l'utilisateur n'en saisit pas
function setDefault(&$var, $default, $label) {
  if (empty($var)) {
    $var = $default;
    echo "Aucun {$label} saisi, utilisation de : {$var}\n";
  }
}

setDefault($projectName,     'my-wordpress-project',  'nom');
setDefault($projectBDD,      'my-wordpress-bdd',      'nom de BDD');
setDefault($projectUser,     'my-wordpress-user',     'utilisateur');
setDefault($projectPassword, 'my-wordpress-password', 'mot de passe');
setDefault($projectURL_BDD,  'localhost',              'URL BDD');
setDefault($siteUrl,         "{$projectName}.test",  'URL du site');


// Clés de sécurité WordPress, générées sur ce poste (aucun appel réseau).
// Deux jeux distincts : un pour le local, un pour le serveur. Une fuite du .env local ne compromet pas la production.
echo "Génération des clés de sécurité WordPress...\n";
function generateSecret(int $length = 64): string {
  // Pas de guillemet, de dollar, de # ni d'antislash : la valeur s'écrit telle quelle dans le .env.
  $chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@%^&*()-_[]{}<>~+=,.;:/?|';
  $secret = '';
  for ($i = 0; $i < $length; $i++) {
    $secret .= $chars[random_int(0, strlen($chars) - 1)];
  }
  return $secret;
}
$saltKeys = [];
$prodKeys = [];
foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $saltName) {
  $saltKeys[$saltName] = generateSecret();
  $prodKeys[$saltName] = generateSecret();
}

// Encodage base64url des clés WordPress pour les variables serveur / GitLab CI
$keyMap = [
  'WP_AUTH_KEY_B64U'          => $prodKeys['AUTH_KEY'],
  'WP_SECURE_AUTH_KEY_B64U'   => $prodKeys['SECURE_AUTH_KEY'],
  'WP_LOGGED_IN_KEY_B64U'     => $prodKeys['LOGGED_IN_KEY'],
  'WP_NONCE_KEY_B64U'         => $prodKeys['NONCE_KEY'],
  'WP_AUTH_SALT_B64U'         => $prodKeys['AUTH_SALT'],
  'WP_SECURE_AUTH_SALT_B64U'  => $prodKeys['SECURE_AUTH_SALT'],
  'WP_LOGGED_IN_SALT_B64U'    => $prodKeys['LOGGED_IN_SALT'],
  'WP_NONCE_SALT_B64U'        => $prodKeys['NONCE_SALT'],
];

$encodedKeys = [];
foreach ($keyMap as $varName => $value) {
  $encodedKeys[$varName] = rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

// Création du fichier .env
$envContent = "# Fichier de configuration pour le projet WordPress
WP_DB_NAME={$projectBDD}
WP_DB_USER={$projectUser}
WP_DB_PASSWORD={$projectPassword}
WP_DB_HOST={$projectURL_BDD}
WP_DB_PREFIXE={$projectPrefix}

# Environnement : \"local\" sur ton poste (active le debug). Sans cette ligne, le site se considère en production.
WP_ENVIRONMENT_TYPE=local

# Configuration du thème et de l'URL du site
THEME_NAME={$projectName}
SITE_URL=http://{$siteUrl}

# Serveur de déploiement
SSH_HOST={$sshHost}
SSH_PRIVATE_KEY={$sshPrivateKey}
SSH_USER={$sshUser}

# Outils du setup
GITLAB_GROUP={$gitlabGroup}
OP_VAULT=\"{$opVault}\"

# Clés de sécurité du site local
WP_AUTH_KEY=\"{$saltKeys['AUTH_KEY']}\"
WP_SECURE_AUTH_KEY=\"{$saltKeys['SECURE_AUTH_KEY']}\"
WP_LOGGED_IN_KEY=\"{$saltKeys['LOGGED_IN_KEY']}\"
WP_NONCE_KEY=\"{$saltKeys['NONCE_KEY']}\"
WP_AUTH_SALT=\"{$saltKeys['AUTH_SALT']}\"
WP_SECURE_AUTH_SALT=\"{$saltKeys['SECURE_AUTH_SALT']}\"
WP_LOGGED_IN_SALT=\"{$saltKeys['LOGGED_IN_SALT']}\"
WP_NONCE_SALT=\"{$saltKeys['NONCE_SALT']}\"

# Compte administrateur WordPress (WP_ADMIN_PASS est retiré de ce fichier une fois enregistré dans 1Password)
WP_ADMIN_TITLE=\"{$wpTitle}\"
WP_ADMIN_USER={$wpAdmin}
WP_ADMIN_EMAIL={$wpEmail}
WP_ADMIN_PASS=\"{$wpPass}\"
WP_ADMIN_LICENCE_ACF={$wpAcf}

# Infomaniak — clés du serveur, différentes des clés locales, encodées en base64url (à copier dans les variables d'environnement serveur / GitLab CI)
WP_AUTH_KEY_B64U={$encodedKeys['WP_AUTH_KEY_B64U']}
WP_SECURE_AUTH_KEY_B64U={$encodedKeys['WP_SECURE_AUTH_KEY_B64U']}
WP_LOGGED_IN_KEY_B64U={$encodedKeys['WP_LOGGED_IN_KEY_B64U']}
WP_NONCE_KEY_B64U={$encodedKeys['WP_NONCE_KEY_B64U']}
WP_AUTH_SALT_B64U={$encodedKeys['WP_AUTH_SALT_B64U']}
WP_SECURE_AUTH_SALT_B64U={$encodedKeys['WP_SECURE_AUTH_SALT_B64U']}
WP_LOGGED_IN_SALT_B64U={$encodedKeys['WP_LOGGED_IN_SALT_B64U']}
WP_NONCE_SALT_B64U={$encodedKeys['WP_NONCE_SALT_B64U']}\n";

file_put_contents(dirname(__DIR__) . '/.env', $envContent);

echo "\n";
echo "Variables d'environnement initialisées avec succès !\n";
echo "------------------------------------------------------\n\n";

echo "🔐 Clés WordPress encodées en base64url (déjà ajoutées au .env, section Infomaniak) :\n";
echo "------------------------------------------------------\n";

foreach ($encodedKeys as $varName => $encoded) {
  echo "{$varName}={$encoded}\n";
}

echo "------------------------------------------------------\n\n";