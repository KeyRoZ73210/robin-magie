<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta content="telephone=no" name="format-detection" />
  <meta name="theme-color" content="#ffffff" />
  <link type="text/plain" rel="author" href="/humans.txt" />

  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>

  <?php get_template_part( 'blocks/header/view' ); ?>
