<?php
/**
 * Importe en base les groupes de champs du thème (fichiers JSON de src), pour les modifier depuis l'admin.
 *
 * Les champs fonctionnent déjà sans cet import : SCF lit les JSON du thème. L'import sert après avoir
 * modifié un JSON à la main, pour que l'admin affiche la même version que le fichier.
 *
 *   wp-dev/src/blocks/NOM/group_*.json   champs d'un bloc
 *   wp-dev/src/acf-json/*.json           autres groupes (variables, page d'options…)
 *
 * Usage : php scripts/sync-acf.php
 */

require_once __DIR__ . '/../web/wp-load.php';

if (!function_exists('acf_import_field_group')) {
  echo "❌ Secure Custom Fields n'est pas actif.\n";
  exit(1);
}

$src = dirname(__DIR__) . '/wp-dev/src';
$json_files = array_merge(
  glob($src . '/blocks/*/group_*.json') ?: [],
  glob($src . '/acf-json/*.json') ?: []
);

if (empty($json_files)) {
  echo "⚠️  Aucun groupe de champs trouvé dans wp-dev/src.\n";
  exit(0);
}

echo "\n📦 Synchronisation des groupes de champs...\n";
echo "──────────────────────────────────────\n";

$seen   = [];
$errors = 0;

foreach ($json_files as $file) {
  $label = str_replace($src . '/', '', $file);
  $group = json_decode((string) file_get_contents($file), true);

  if (!is_array($group) || empty($group['key'])) {
    echo "⚠️  JSON invalide : {$label}\n";
    $errors++;
    continue;
  }

  // Deux fichiers avec la même clé : SCF n'en garderait qu'un, sans prévenir.
  if (isset($seen[$group['key']])) {
    echo "⚠️  Clé en double ({$group['key']}) : {$label} ignoré, déjà lue dans {$seen[$group['key']]}\n";
    $errors++;
    continue;
  }
  $seen[$group['key']] = $label;

  // Le groupe existe déjà en base : on le met à jour au lieu d'en créer un second.
  // Recherche directe en base : acf_get_field_group() renvoie la version JSON (sans identifiant) quand elle existe.
  $existing = get_posts([
    'post_type'      => 'acf-field-group',
    'name'           => $group['key'],
    'post_status'    => 'any',
    'posts_per_page' => 1,
    'orderby'        => 'ID',
    'order'          => 'ASC',
    'fields'         => 'ids',
  ]);
  if ($existing) {
    $group['ID'] = (int) $existing[0];
  }

  acf_import_field_group($group);
  echo "✅ {$group['title']} ({$label})\n";
}

echo "──────────────────────────────────────\n";
echo $errors ? "⚠️  Terminé avec {$errors} fichier(s) ignoré(s).\n\n" : "🎉 Sync terminé.\n\n";
exit($errors ? 1 : 0);
