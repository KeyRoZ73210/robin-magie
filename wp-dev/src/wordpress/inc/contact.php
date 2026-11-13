<?php
/**
 * Formulaire de contact : réception AJAX sécurisée.
 *
 * Remplace l'ancien mailer/send-mail.php (qui chargeait wp-load.php à la main, sans jeton ni nettoyage des champs).
 *
 * Côté front, le formulaire envoie un POST (XHR) vers admin-ajax.php avec :
 *   action   = theme_contact
 *   nonce    = valeur de theme_contact_nonce()          (jeton de sécurité, valable ~12 h)
 *   website  = champ piège, invisible, qui doit rester vide (theme_contact_honeypot())
 *   email, message                                       (obligatoires)
 *   prenom, nom, entreprise, tel, pro, opt-in           (facultatifs)
 *
 * Réponse JSON : { success: true|false, data: { message: "…" } }
 *
 * Réglages par filtres (tous facultatifs) :
 *   theme_contact_recipient          destinataire (par défaut : option ACF « mail » du thème, sinon l'e-mail admin)
 *   theme_contact_subject            objet du message
 *   theme_contact_send_confirmation  true = envoyer un accusé de réception au visiteur (désactivé par défaut)
 *
 * Attention aux pages mises en cache : le jeton expire. Si la page du formulaire est en cache, prévoyez de le
 * redemander (ex. exclure la page du cache).
 */

if (!defined('ABSPATH')) {
	exit;
}

const THEME_CONTACT_ACTION = 'theme_contact';
const THEME_CONTACT_MAX_MESSAGE = 5000;
const THEME_CONTACT_MAX_FIELD = 200;
const THEME_CONTACT_RATE_LIMIT = 5;       // messages acceptés par adresse IP…
const THEME_CONTACT_RATE_WINDOW = 600;    // …pendant cette durée (secondes)

/** Champ caché portant le jeton de sécurité, à placer dans le formulaire. */
function theme_contact_nonce_field(): string {
	return '<input type="hidden" name="nonce" value="' . esc_attr(wp_create_nonce(THEME_CONTACT_ACTION)) . '">'
		. '<input type="hidden" name="action" value="' . esc_attr(THEME_CONTACT_ACTION) . '">';
}

/** Champ piège : invisible pour un humain, rempli par la plupart des robots. */
function theme_contact_honeypot(): string {
	return '<div style="position:absolute;left:-9999px" aria-hidden="true">'
		. '<label>Ne pas remplir<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
}

/** Adresse d'envoi AJAX, à passer au JavaScript du formulaire. */
function theme_contact_url(): string {
	return admin_url('admin-ajax.php');
}

/** Lit un champ texte du formulaire : une seule ligne, nettoyé, tronqué. */
function theme_contact_field(string $key, int $max = THEME_CONTACT_MAX_FIELD): string {
	$raw = $_POST[$key] ?? '';
	if (!is_scalar($raw)) {
		return '';
	}

	return mb_substr(sanitize_text_field(wp_unslash((string) $raw)), 0, $max);
}

/** Trop de messages depuis la même adresse IP ? (compte un envoi par appel) */
function theme_contact_rate_limited(): bool {
	$key = 'theme_contact_' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
	$count = (int) get_transient($key);
	if ($count >= THEME_CONTACT_RATE_LIMIT) {
		return true;
	}
	set_transient($key, $count + 1, THEME_CONTACT_RATE_WINDOW);

	return false;
}

function theme_contact_recipient(): string {
	$to = function_exists('getThemeOption') ? (string) getThemeOption('mail') : '';
	if (!is_email($to)) {
		$to = (string) get_option('admin_email');
	}

	return (string) apply_filters('theme_contact_recipient', $to);
}

function theme_contact_handle(): void {
	$nonce = isset($_POST['nonce']) && is_scalar($_POST['nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['nonce'])) : '';
	if (!wp_verify_nonce($nonce, THEME_CONTACT_ACTION)) {
		wp_send_json_error(['message' => 'Votre session a expiré, rechargez la page puis réessayez.'], 403);
	}

	// Un robot a rempli le piège : on répond « envoyé » sans rien envoyer, pour ne pas le renseigner.
	if (theme_contact_field('website', 50) !== '') {
		wp_send_json_success(['message' => 'Merci, votre message a bien été envoyé.']);
	}

	if (theme_contact_rate_limited()) {
		wp_send_json_error(['message' => 'Trop de messages envoyés, réessayez dans quelques minutes.'], 429);
	}

	$email = sanitize_email(theme_contact_field('email'));
	$message = '';
	if (isset($_POST['message']) && is_scalar($_POST['message'])) {
		$message = mb_substr(trim(sanitize_textarea_field(wp_unslash((string) $_POST['message']))), 0, THEME_CONTACT_MAX_MESSAGE);
	}

	if (!is_email($email)) {
		wp_send_json_error(['message' => 'Merci d’indiquer une adresse e-mail valide.', 'field' => 'email'], 422);
	}
	if ($message === '') {
		wp_send_json_error(['message' => 'Merci d’écrire votre message.', 'field' => 'message'], 422);
	}

	$prenom = theme_contact_field('prenom');
	$nom = theme_contact_field('nom');
	$entreprise = theme_contact_field('entreprise');
	$tel = theme_contact_field('tel', 30);
	$pro = !empty($_POST['pro']);
	$optIn = !empty($_POST['opt-in']);

	// Chaque valeur est échappée : aucune donnée du visiteur n'est écrite telle quelle dans le HTML du message.
	$lines = [];
	if ($pro) {
		$lines[] = 'Je suis un professionnel';
	}
	$lines[] = 'E-mail : ' . esc_html($email);
	$lines[] = 'Prénom : ' . esc_html($prenom);
	$lines[] = 'Nom : ' . esc_html($nom);
	if ($entreprise !== '') {
		$lines[] = 'Entreprise : ' . esc_html($entreprise);
	}
	if ($tel !== '') {
		$lines[] = 'Téléphone : ' . esc_html($tel);
	}
	$lines[] = 'Accepte de recevoir des informations : ' . ($optIn ? 'oui' : 'non');
	$lines[] = '';
	$lines[] = 'Message :';
	$lines[] = nl2br(esc_html($message));

	$subject = (string) apply_filters('theme_contact_subject', 'Nouveau message du site ' . sanitize_text_field(wp_specialchars_decode(get_bloginfo('name'))));
	$headers = [
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $email, // $email est déjà validé par is_email() : il ne peut contenir aucun retour à la ligne
	];

	if (!wp_mail(theme_contact_recipient(), $subject, implode('<br>', $lines), $headers)) {
		error_log('[theme] contact : wp_mail a échoué');
		wp_send_json_error(['message' => 'Le message n’a pas pu être envoyé, réessayez plus tard.'], 500);
	}

	// Accusé de réception : désactivé par défaut. Activé, le formulaire envoie un e-mail à n'importe quelle adresse
	// saisie par un inconnu : c'est une porte d'entrée à spam (et à la mise sur liste noire du domaine).
	if (apply_filters('theme_contact_send_confirmation', false)) {
		wp_mail($email, 'Nous avons bien reçu votre message', 'Bonjour' . ($prenom !== '' ? ' ' . esc_html($prenom) : '') . ',<br><br>Merci de nous avoir écrit, nous revenons vers vous rapidement.', ['Content-Type: text/html; charset=UTF-8']);
	}

	wp_send_json_success(['message' => 'Merci, votre message a bien été envoyé.']);
}
add_action('wp_ajax_nopriv_' . THEME_CONTACT_ACTION, 'theme_contact_handle');
add_action('wp_ajax_' . THEME_CONTACT_ACTION, 'theme_contact_handle');
