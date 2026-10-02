<?php
require __DIR__ . '/inc/lib.php';

ensure_voter_cookie();
$error = '';

// Traitement du vote
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $imageId = (string) ($_POST['image'] ?? '');

    $result = update_data(function (array &$data) use ($imageId) {
        if ($data['status'] !== 'open') {
            return 'closed';
        }
        if (has_voted($data)) {
            return 'already';
        }
        foreach ($data['images'] as &$img) {
            if ($img['id'] === $imageId) {
                $img['votes']++;
                foreach (voter_keys() as $k) {
                    $data['voters'][$k] = time();
                }
                return 'ok';
            }
        }
        return 'invalid';
    });

    if ($result === 'ok' || $result === 'already') {
        redirect('merci.php');
    }
    $error = $result === 'closed'
        ? 'Le vote est clôturé.'
        : 'Sélectionne une image avant de valider.';
}

$data   = load_data();
$voted  = has_voted($data);
$isOpen = $data['status'] === 'open';

page_header('Vote — Élis la meilleure image');
?>
<section class="section_hero">
  <div class="padding-global">
    <div class="container-medium">
      <div class="padding-section-small text-align-center">
        <span class="tag">Concours d'images IA</span>
        <h1 class="heading-style-h1">Élis la meilleure image</h1>
        <?php if ($data['prompt'] !== ''): ?>
          <div class="hero_prompt">
            <span class="hero_prompt-label">Prompt</span>
            <p class="text-size-medium">« <?= e($data['prompt']) ?> »</p>
          </div>
        <?php endif; ?>
        <p class="text-size-regular text-color-muted">Un seul vote par personne. Choisis bien&nbsp;!</p>
      </div>
    </div>
  </div>
</section>

<section class="section_vote">
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-bottom padding-large">
        <?php if (!$isOpen): ?>
          <div class="message_component">
            <p class="heading-style-h4">Le vote est terminé</p>
            <a href="resultats.php" class="button">Voir les résultats</a>
          </div>
        <?php elseif ($voted): ?>
          <div class="message_component">
            <p class="heading-style-h4">Tu as déjà voté, merci&nbsp;!</p>
            <p class="text-color-muted">Les résultats seront publiés à la fin du vote.</p>
          </div>
        <?php elseif (!$data['images']): ?>
          <div class="message_component">
            <p class="heading-style-h4">Aucune image pour le moment</p>
            <p class="text-color-muted">Reviens un peu plus tard.</p>
          </div>
        <?php else: ?>
          <?php if ($error): ?><div class="alert is-error"><?= e($error) ?></div><?php endif; ?>
          <form method="post" class="vote_form" id="vote-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="vote_grid">
              <?php foreach ($data['images'] as $i => $img): ?>
                <label class="vote_card">
                  <input type="radio" name="image" value="<?= e($img['id']) ?>" class="vote_radio" required>
                  <div class="vote_image-wrapper">
                    <img src="uploads/<?= e($img['file']) ?>" alt="<?= e($img['title'] ?: 'Image ' . ($i + 1)) ?>" class="vote_image" loading="lazy">
                    <span class="vote_check" aria-hidden="true">✓</span>
                  </div>
                  <div class="vote_card-body">
                    <span class="vote_number">#<?= $i + 1 ?></span>
                    <span class="vote_title"><?= e($img['title'] ?: 'Image ' . ($i + 1)) ?></span>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="vote_actions">
              <button type="submit" class="button is-large" id="vote-submit" disabled>Valider mon vote</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>
  // Active le bouton dès qu'une image est sélectionnée + confirmation
  (function () {
    var form = document.getElementById('vote-form');
    if (!form) return;
    var btn = document.getElementById('vote-submit');
    form.addEventListener('change', function () { btn.disabled = false; });
    form.addEventListener('submit', function (e) {
      if (!confirm('Confirmer ton vote ? Il ne pourra pas être modifié.')) e.preventDefault();
    });
  })();
</script>
<?php page_footer();
