<?php
require __DIR__ . '/inc/lib.php';

page_header('Merci pour ton vote');
?>
<section class="section_thanks">
  <div class="padding-global">
    <div class="container-small">
      <div class="padding-section-large text-align-center">
        <div class="thanks_icon" aria-hidden="true">✓</div>
        <h1 class="heading-style-h2">Merci pour ton vote&nbsp;!</h1>
        <p class="text-size-medium text-color-muted">Ton choix a bien été enregistré. Les résultats seront dévoilés à la fin du vote.</p>
        <div class="padding-top padding-medium">
          <a href="resultats.php" class="button is-secondary">Page des résultats</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php page_footer();
