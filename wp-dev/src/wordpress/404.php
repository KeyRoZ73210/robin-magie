<?php

/** The template for displaying 404 pages (Not Found) */
		
get_header(); ?>

<main class="site-main">

	<section class="error-404">
		<div class="container-ext">
			<h1><?php esc_html_e('Page introuvable', 'wp-dev-translate'); ?></h1>
			<p><?php esc_html_e('La page que vous cherchez n’existe pas ou a été déplacée.', 'wp-dev-translate'); ?></p>
			<p><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Retour à l’accueil', 'wp-dev-translate'); ?></a></p>
		</div>
	</section>

</main>

<?php	get_footer();
