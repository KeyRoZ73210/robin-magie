<?php
/**
 * Plugin Name: Forge Core
 * Description: Socle fonctionnel du site (éditeur, médias, admin…), configuré dans config/forge.php.
 * Author: Thomas Fleury
 *
 * À copier dans web/wp-content/mu-plugins/forge-core.php.
 * Le code de Forge Core est installé par Composer dans vendor/ ; ce fichier ne fait que le démarrer.
 */

defined('ABSPATH') || exit;

// Racine du projet : le dossier qui contient web/, vendor/ et config/.
$forge_root = dirname(ABSPATH);

// wp-config.php charge normalement déjà l'autoload de Composer.
if (!class_exists(\Forge\Core\Kernel::class) && is_file($forge_root . '/vendor/autoload.php')) {
    require_once $forge_root . '/vendor/autoload.php';
}

if (!class_exists(\Forge\Core\Kernel::class)) {
    error_log('[forge-core] Paquet introuvable : lancez « composer install ».');
    add_action('admin_notices', static function (): void {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p><strong>Forge Core</strong> n\'est pas installé : lancez <code>composer install</code>.</p></div>';
        }
    });
    return;
}

\Forge\Core\Kernel::boot($forge_root . '/config/forge.php');

unset($forge_root);
