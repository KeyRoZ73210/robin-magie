<?php
/**
 * Fonctions et réglages du thème.
 *
 * @package wp-dev
 */

if (!defined('ABSPATH')) {
	exit;
}

// Constantes préfixées : un plugin qui définirait VERSION ou ROOT ne peut plus provoquer d'erreur fatale.
if (!defined('THEME_VERSION')) {
	define('THEME_VERSION', wp_get_theme()->get('Version') ?: '1.0');
}
if (!defined('THEME_URI')) {
	define('THEME_URI', get_stylesheet_directory_uri());
}
if (!defined('THEME_IMG')) {
	define('THEME_IMG', THEME_URI . '/assets/img');
}
// show_admin_bar(false);

require_once get_template_directory() . '/inc/contact.php';

/* ========================================================================= *\
**  === SECURE API REST                                                      *|
\* ========================================================================= */

// Seule la liste des utilisateurs est réservée aux personnes connectées (elle révèle les identifiants de connexion).
// Le reste de l'API reste ouvert : des extensions (boutique, formulaires…) en ont besoin côté visiteur.
add_filter('rest_authentication_errors', function ($result) {
	// Une vérification précédente a déjà tranché : on la respecte.
	if (true === $result || is_wp_error($result)) {
		return $result;
	}

	$route = (string) ($GLOBALS['wp']->query_vars['rest_route'] ?? '');
	if (!is_user_logged_in() && preg_match('#^/wp/v2/users(/|$)#', $route)) {
		return new WP_Error(
			'rest_not_logged_in',
			__('You are not currently logged in.'),
			['status' => 401]
		);
	}

	return $result;
});

// /?author=1 redirige vers /author/identifiant/ : cette forme d'URL révèle les identifiants de connexion, on répond 404.
add_action('template_redirect', function () {
	if (isset($_GET['author']) && !is_admin()) {
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
		nocache_headers();
	}
}, 1);

// En-têtes de sécurité envoyés sur toutes les pages du site.
add_filter('wp_headers', function ($headers) {
	$headers['X-Content-Type-Options'] = 'nosniff';
	$headers['X-Frame-Options']        = 'SAMEORIGIN';
	$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
	$headers['Permissions-Policy']     = 'camera=(), microphone=(), geolocation=()';
	return $headers;
});

/* ========================================================================= *\
**  === AFTER SETUP THEME.                                                   *|
\* ========================================================================= */

add_action('after_setup_theme', 'theme_setup');
function theme_setup() {
	load_theme_textdomain('wp-dev-translate', get_template_directory() . '/languages');

	// Remplit la balise title en fonction du titre de la page
	add_theme_support('title-tag');

	// Images à la une des pages et des articles
	add_theme_support('post-thumbnails');

	// Partage les styles des blocs avec leur aperçu dans l'éditeur.
	add_theme_support('editor-styles');
	add_editor_style('assets/css/main.min.css');

	// HTML5 pour les formulaires de recherche et les images à légende
	add_theme_support('html5', ['search-form', 'caption', 'gallery', 'style', 'script']);
}

/* ========================================================================= *\
**  === ENQUEUE SCRIPTS AND STYLES                                           *|
\* ========================================================================= */

function theme_scripts_styles() {
	wp_enqueue_style('style', THEME_URI . '/style.css', [], THEME_VERSION);

	// En local : fichiers non minifiés, avec sourcemaps. Ailleurs : fichiers minifiés.
	$is_dev = in_array(wp_get_environment_type(), ['local', 'development'], true);

	$css_file = $is_dev ? '/assets/css/main.css' : '/assets/css/main.min.css';
	$js_file  = $is_dev ? '/assets/js/main.js'  : '/assets/js/main.min.js';

	// Si le build demandé n'existe pas (ex. build de production testé en local), on prend l'autre.
	$dir = get_template_directory();
	if (!file_exists($dir . $css_file)) {
		$css_file = $is_dev ? '/assets/css/main.min.css' : '/assets/css/main.css';
	}
	if (!file_exists($dir . $js_file)) {
		$js_file = $is_dev ? '/assets/js/main.min.js' : '/assets/js/main.js';
	}

	$css_path = $dir . $css_file;
	$js_path  = $dir . $js_file;

	// La date du fichier sert de version : le navigateur recharge dès que le fichier change.
	$css_version = file_exists($css_path) ? filemtime($css_path) : THEME_VERSION;
	$js_version  = file_exists($js_path)  ? filemtime($js_path)  : THEME_VERSION;

	wp_enqueue_style('main', get_template_directory_uri() . $css_file, [], $css_version);
	wp_enqueue_script('main', get_template_directory_uri() . $js_file, [], $js_version, true);
}
add_action('wp_enqueue_scripts', 'theme_scripts_styles');

// Le script principal est chargé en module (le navigateur le diffère tout seul).
add_filter('script_loader_tag', function ($tag, $handle, $src) {
	if ($handle === 'main') {
		return sprintf(
			'<script type="module" src="%s" id="%s"></script>' . "\n",
			esc_url($src),
			esc_attr($handle . '-js')
		);
	}
	return $tag;
}, 10, 3);

// Nettoyage du <head>
remove_action('wp_head', 'wp_generator');

/* ========================================================================= *\
**  === MENU                                                                 *|
\* ========================================================================= */

add_action('init', function () {
	register_nav_menu('header', __('Header', 'wp-dev-translate'));
	register_nav_menu('menu', __('Menu plein écran', 'wp-dev-translate'));
	register_nav_menu('footer', __('Footer', 'wp-dev-translate'));
});

/* ========================================================================= *\
**  === THEME OPTIONS (Secure Custom Fields)                                                *|
\* ========================================================================= */

add_action('acf/init', function () {
	if (!function_exists('acf_add_options_page')) {
		return;
	}

	acf_add_options_page([
		'page_title' => 'Theme Options',
		'menu_title' => 'Theme Options',
		'menu_slug'  => 'theme-options',
		// Éditeurs et administrateurs seulement : cette page règle le destinataire du formulaire de contact.
		'capability' => 'edit_others_posts',
		'icon_url'   => 'dashicons-admin-generic',
		'position'   => 2,
	]);

	acf_add_options_sub_page([
		'page_title'  => 'Settings',
		'menu_title'  => 'Settings',
		'menu_slug'   => 'theme-options-settings',
		'parent_slug' => 'theme-options',
		'capability'  => 'edit_others_posts',
	]);
});

// Champs de la page d'options, déclarés en PHP (versionnés dans Git).
add_action('acf/include_fields', function () {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}
	acf_add_local_field_group([
		'key'      => 'group_theme_options',
		'title'    => 'Réglages du thème',
		'fields'   => [
			['key' => 'field_theme_mail', 'name' => 'mail', 'label' => 'E-mail de réception du formulaire de contact', 'type' => 'email'],
		],
		'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => 'theme-options']]],
	]);
});

if (!function_exists('getThemeOption')) {
	/**
	 * Lit un champ de la page d'options du thème (SCF).
	 *
	 * @param string|null $targetedField Nom du champ, ou null pour tous les champs.
	 * @return mixed Valeur du champ (ou tableau de tous les champs si $targetedField = null).
	 */
	function getThemeOption($targetedField = null) {
		if ($targetedField === null) {
			return get_fields('option');
		}
		return get_field($targetedField, 'option');
	}
}

/* ========================================================================= *\
**  === PREVIEW                                                              *|
\* ========================================================================= */
/**
 * Active automatiquement l’aperçu dans l’inserteur pour tous les blocs SCF.
 */
add_filter('register_block_type_args', function ($args, $block_name) {
	if (!str_starts_with($block_name, 'acf/')) {
		return $args;
	}

	$args['example'] = [
		'attributes' => [
			'mode' => 'preview',
			'data' => [
				'is_example' => true,
			],
		],
	];

	return $args;
}, 10, 2);

/** Remplace le template par son image preview dans le rendu AJAX de SCF. */
if (!class_exists('Theme_Block_Preview')) {
	final class Theme_Block_Preview {
		private static $buffer_levels = [];

		private static function image($block, $is_preview) {
			$block_name = $block['name'] ?? '';
			$is_example = !empty($block['data']['is_example']);

			if (!$is_preview || !$is_example || !str_starts_with($block_name, 'acf/')) {
				return null;
			}

			$slug      = sanitize_file_name(substr($block_name, 4));
			$directory = get_template_directory() . "/blocks/{$slug}";

			foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
				if (file_exists("{$directory}/preview.{$extension}")) {
					return [
						'slug' => $slug,
						'url'  => get_template_directory_uri() . "/blocks/{$slug}/preview.{$extension}",
					];
				}
			}

			return null;
		}

		public static function before($block, $content, $is_preview) {
			if (self::image($block, $is_preview) === null) {
				return;
			}

			self::$buffer_levels[] = ob_get_level();
			ob_start();
		}

		public static function after($block, $content, $is_preview) {
			$image = self::image($block, $is_preview);
			if ($image === null || empty(self::$buffer_levels)) {
				return;
			}

			$buffer_level = array_pop(self::$buffer_levels);
			while (ob_get_level() > $buffer_level) {
				ob_end_clean();
			}

			printf(
				'<img class="block-preview" src="%s" alt="%s">',
				esc_url($image['url']),
				esc_attr(sprintf(__('Aperçu du bloc %s', 'wp-dev-translate'), $image['slug']))
			);
		}
	}
}

add_action('acf/blocks/pre_block_template_render', ['Theme_Block_Preview', 'before'], 10, 3);
add_action('acf/blocks/post_block_template_render', ['Theme_Block_Preview', 'after'], 10, 3);

/* ========================================================================= *\
**  === CATEGORIES                                                           *|
\* ========================================================================= */
/**
 * Catégorie réservée aux blocs personnalisés du thème.
 */
add_filter('block_categories_all', function ($categories) {
	array_unshift($categories, [
		'slug'  => 'forge-blocks',
		'title' => __('Forge blocks', 'wp-dev-translate'),
		'icon'  => 'dashicons-smiley',
	]);

	return $categories;
});

/* ========================================================================= *\
**  === THUMBNAILS                                                           *|
\* ========================================================================= */

// Exemple : add_image_size('full-width', 1500); add_image_size('rounded', 300, 300, true);

if (!function_exists('wp_img')) {
	/** Balise <img> d'un média, avec srcset et dimensions (à utiliser plutôt qu'un <img src> écrit à la main). */
	function wp_img($id, $size = 'full', $attr = '') {
		return wp_get_attachment_image($id, $size, false, $attr);
	}
}

/* ========================================================================= *\
**  === SCF BLOCKS                                                           *|
\* ========================================================================= */

// Chaque dossier qui contient un block.json est un bloc natif de l'éditeur.
add_action('init', function () {
	foreach (glob(get_template_directory() . '/blocks/*/block.json') ?: [] as $metadata) {
		register_block_type(dirname($metadata));
	}
});

// Les champs des blocs sont gérés dans SCF > Groupes de champs (emplacement : Bloc).
// JSON local : les champs d'un bloc sont enregistrés dans le dossier du bloc (blocks/NOM/group_….json),
// les autres groupes (page d'options…) dans acf-json/. Gulp les recopie dans src (donc dans Git).
// Sur un autre site, SCF propose de « synchroniser » ces fichiers : les champs apparaissent sans rien recréer.
add_filter('acf/json/save_paths', function ($paths, $post) {
	foreach ((array) ($post['location'] ?? []) as $rules) {
		foreach ((array) $rules as $rule) {
			$value = (string) ($rule['value'] ?? '');
			if (($rule['param'] ?? '') === 'block' && ($rule['operator'] ?? '') === '==' && str_starts_with($value, 'acf/')) {
				$dir = get_stylesheet_directory() . '/blocks/' . substr($value, 4);
				if (is_dir($dir)) {
					return [$dir];
				}
			}
		}
	}
	return $paths;
}, 10, 2);

add_filter('acf/settings/save_json', function () {
	return get_stylesheet_directory() . '/acf-json';
});

add_filter('acf/settings/load_json', function ($paths) {
	$paths[] = get_stylesheet_directory() . '/acf-json';
	foreach (glob(get_stylesheet_directory() . '/blocks/*', GLOB_ONLYDIR) ?: [] as $dir) {
		$paths[] = $dir;
	}
	return array_unique($paths);
});

/* ========================================================================= *\
**  === SMTP                                                                 *|
\* ========================================================================= */

// Envoi par un serveur SMTP, seulement si les identifiants sont définis dans wp-config.php :
//   define('THEME_SMTP_HOST', 'smtp.exemple.fr');
//   define('THEME_SMTP_USER', '…');
//   define('THEME_SMTP_PASS', '…');
//   define('THEME_SMTP_PORT', 587);      // facultatif
//   define('THEME_SMTP_SECURE', 'tls');  // facultatif : 'tls' ou 'ssl'
// Les identifiants ne sont jamais écrits dans le thème (donc jamais dans Git).
add_action('phpmailer_init', function ($phpmailer) {
	if (!defined('THEME_SMTP_HOST') || !defined('THEME_SMTP_USER') || !defined('THEME_SMTP_PASS')) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = THEME_SMTP_HOST;
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Username   = THEME_SMTP_USER;
	$phpmailer->Password   = THEME_SMTP_PASS;
	$phpmailer->Port       = defined('THEME_SMTP_PORT') ? (int) THEME_SMTP_PORT : 587;
	$phpmailer->SMTPSecure = defined('THEME_SMTP_SECURE') ? THEME_SMTP_SECURE : 'tls';
});
