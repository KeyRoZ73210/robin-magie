<?php
/**
 * Configuration de Forge Core pour ce projet.
 *
 * À copier dans config/forge.php à la racine du projet.
 * Un module absent ou réglé à false est désactivé.
 * true = activé avec ses options par défaut ; un tableau = activé avec ces options.
 * Les options disponibles sont décrites dans le README de forge-core.
 */

return [
    'modules' => [
    //     'consent' => [
    //     'ga4_id'         => 'G-XXXXXXXXXX',   // ou laisse vide si tu n'as pas GA4
    //     'privacy_url'    => '/politique-de-confidentialite/',
    //     'policy_version' => '1',              // à changer quand ta politique change : tout le monde est re-sollicité
    //     'log'            => true,             // active la preuve
    //     'log_days'       => 395,
    // ],
        // Remplace Classic Editor.
        // 'classic-editor' => true,

        // Remplace Duplicate Page.
        // 'duplicate' => [
        //     'post_types' => ['post', 'page'],
        // ],

        // Remplace WP Custom Admin Interface.
        // 'admin' => [
        //     'login_logo' => null,            // ex. '/wp-content/themes/mon-theme/assets/img/logo.svg'
        //     'footer_text' => null,           // ex. 'Site réalisé par …'
        //     'hide_menus' => ['tools.php'],   // masqués pour les non-administrateurs
        //     'disable_comments' => true,
        // ],

        // // Remplace Safe SVG.
        // 'svg' => true,

        // Remplace ACF Beautiful Flexible (prévisualisations) et ACF Hide Layout (natif dans ACF Pro 6.5+).
        // 'acf-layouts' => true,

        // Remplace Yoast SEO.
        // 'seo' => false,
    ],

    // Modules propres au projet : 'identifiant' => \Namespace\Classe::class (doit implémenter Forge\Core\Module).
    'extensions' => [],
];
