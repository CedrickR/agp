<?php
require __DIR__ . '/inc/lib.php';

$data  = load_data();
$total = total_votes($data);

page_header('Résultats du vote');
?>
<section class="section_results">
  <div class="padding-global">
    <div class="container-medium">
      <div class="padding-section-small text-align-center">
        <span class="tag">Résultats</span>
        <h1 class="heading-style-h1">Le verdict</h1>
      </div>

      <div class="padding-bottom padding-large">
        <?php if ($data['status'] !== 'closed'): ?>
          <div class="message_component">
            <p class="heading-style-h4">Le vote est en cours</p>
            <p class="text-color-muted">Les résultats seront visibles ici dès la clôture du vote.</p>
            <a href="index.php" class="button">Aller voter</a>
          </div>
        <?php else: ?>
          <?php
          $ranking = ranking($data);
          $winner  = $ranking[0] ?? null;
          ?>
          <?php if ($winner && $total > 0): ?>
            <div class="winner_component">
              <img src="uploads/<?= e($winner['file']) ?>" alt="<?= e($winner['title']) ?>" class="winner_image">
              <div class="winner_body">
                <span class="tag is-accent">🏆 Gagnante</span>
                <h2 class="heading-style-h3"><?= e($winner['title'] ?: 'Image gagnante') ?></h2>
                <p class="text-size-medium"><?= (int) $winner['votes'] ?> vote<?= $winner['votes'] > 1 ? 's' : '' ?> · <?= round($winner['votes'] * 100 / $total) ?>&nbsp;%</p>
              </div>
            </div>
          <?php endif; ?>

          <?php include __DIR__ . '/inc/ranking.php'; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php page_footer();
