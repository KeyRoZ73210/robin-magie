  <?php get_template_part( 'blocks/footer/view' ); ?>
  <?php wp_footer(); ?>

  <?php if (function_exists('wp_get_environment_type') && in_array(wp_get_environment_type(), ['local', 'development'], true)) : ?>
    <script id="__bs_script__">
      (function () {
        var s = document.createElement('script');
        s.async = true;
        s.src = 'http://localhost:3000/browser-sync/browser-sync-client.js';
        document.head.appendChild(s);
      })();
    </script>
  <?php endif; ?>
</body>

</html>