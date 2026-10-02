<?php
// Classement partagé entre resultats.php et admin.php — attend $data et $total
$ranking = ranking($data);
?>
<div class="ranking_component">
  <p class="text-size-small text-color-muted"><?= $total ?> vote<?= $total > 1 ? 's' : '' ?> au total</p>
  <ol class="ranking_list">
    <?php foreach ($ranking as $pos => $img): ?>
      <?php $pct = $total ? round($img['votes'] * 100 / $total, 1) : 0; ?>
      <li class="ranking_item<?= $pos === 0 && $total ? ' is-first' : '' ?>">
        <span class="ranking_pos"><?= $pos + 1 ?></span>
        <img src="uploads/<?= e($img['file']) ?>" alt="" class="ranking_thumb">
        <div class="ranking_body">
          <div class="ranking_row">
            <span class="ranking_title"><?= e($img['title'] ?: 'Sans titre') ?></span>
            <span class="ranking_score"><?= (int) $img['votes'] ?> · <?= $pct ?>&nbsp;%</span>
          </div>
          <div class="ranking_bar"><div class="ranking_bar-fill" style="width: <?= $pct ?>%"></div></div>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
</div>
