<?php
require __DIR__ . '/inc/lib.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// --- Connexion / déconnexion ---
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    redirect('admin.php');
}

if (empty($_SESSION['admin'])) {
    $loginError = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        if (hash_equals(ADMIN_PASSWORD, (string) ($_POST['password'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            redirect('admin.php');
        }
        $loginError = 'Mot de passe incorrect.';
        sleep(1); // ralentit le brute force
    }
    page_header('Administration — Connexion');
    ?>
    <section class="section_admin">
      <div class="padding-global">
        <div class="container-small">
          <div class="padding-section-medium">
            <form method="post" class="card_component">
              <h1 class="heading-style-h3">Administration</h1>
              <?php if ($loginError): ?><div class="alert is-error"><?= e($loginError) ?></div><?php endif; ?>
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <label class="form_label" for="password">Mot de passe</label>
              <input class="form_input" type="password" id="password" name="password" required autofocus>
              <button type="submit" class="button is-full">Se connecter</button>
            </form>
          </div>
        </div>
      </div>
    </section>
    <?php
    page_footer();
    exit;
}

// --- Actions admin ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'upload':
            $file  = $_FILES['image'] ?? null;
            $title = trim((string) ($_POST['title'] ?? ''));
            $data  = load_data();

            if (count($data['images']) >= MAX_IMAGES) {
                flash('error', 'Maximum ' . MAX_IMAGES . ' images.');
                break;
            }
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                flash('error', "Erreur lors de l'upload.");
                break;
            }
            if ($file['size'] > MAX_UPLOAD_SIZE) {
                flash('error', 'Fichier trop lourd (max ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . ' Mo).');
                break;
            }
            // Vérification du vrai type MIME (pas de l'extension envoyée)
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $mime    = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if (!isset($allowed[$mime]) || !getimagesize($file['tmp_name'])) {
                flash('error', 'Format non supporté (JPG, PNG, WEBP ou GIF).');
                break;
            }
            $id   = bin2hex(random_bytes(8));
            $name = $id . '.' . $allowed[$mime];
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
                flash('error', "Impossible d'enregistrer le fichier.");
                break;
            }
            $added = update_data(function (array &$d) use ($id, $name, $title) {
                if (count($d['images']) >= MAX_IMAGES) {
                    return false;
                }
                $d['images'][] = ['id' => $id, 'file' => $name, 'title' => $title, 'votes' => 0];
                return true;
            });
            if (!$added) {
                @unlink(UPLOAD_DIR . '/' . $name);
                flash('error', 'Maximum ' . MAX_IMAGES . ' images.');
                break;
            }
            flash('success', 'Image ajoutée.');
            break;

        case 'title':
            $id    = (string) ($_POST['id'] ?? '');
            $title = trim((string) ($_POST['title'] ?? ''));
            update_data(function (array &$d) use ($id, $title) {
                foreach ($d['images'] as &$img) {
                    if ($img['id'] === $id) {
                        $img['title'] = $title;
                    }
                }
            });
            flash('success', 'Titre mis à jour.');
            break;

        case 'delete':
            $id = (string) ($_POST['id'] ?? '');
            $removed = update_data(function (array &$d) use ($id) {
                foreach ($d['images'] as $k => $img) {
                    if ($img['id'] === $id) {
                        array_splice($d['images'], $k, 1);
                        return $img['file'];
                    }
                }
                return null;
            });
            if ($removed) {
                @unlink(UPLOAD_DIR . '/' . basename($removed));
            }
            flash('success', 'Image supprimée.');
            break;

        case 'open':
        case 'close':
            update_data(function (array &$d) use ($action) { $d['status'] = $action === 'open' ? 'open' : 'closed'; });
            flash('success', $action === 'open' ? 'Vote ouvert.' : 'Vote clôturé, résultats publiés.');
            break;

        case 'toggle_ip':
            $on = update_data(function (array &$d) { return $d['check_ip'] = empty($d['check_ip']); });
            flash('success', $on ? 'Blocage par adresse IP activé.' : 'Blocage par adresse IP désactivé.');
            break;

        case 'reset':
            update_data(function (array &$d) {
                foreach ($d['images'] as &$img) {
                    $img['votes'] = 0;
                }
                $d['voters'] = [];
                $d['status'] = 'open';
            });
            flash('success', 'Votes remis à zéro.');
            break;
    }
    redirect('admin.php');
}

$data   = load_data();
$total  = total_votes($data);
$isOpen = $data['status'] === 'open';
$checkIp = !empty($data['check_ip']);
$csrf   = e(csrf_token());

page_header('Administration');
?>
<section class="section_admin">
  <div class="padding-global">
    <div class="container-large">
      <div class="padding-section-small">
        <div class="admin_header">
          <div>
            <h1 class="heading-style-h2">Administration</h1>
            <p class="text-color-muted">
              Statut :
              <span class="status-badge <?= $isOpen ? 'is-open' : 'is-closed' ?>"><?= $isOpen ? 'Vote ouvert' : 'Vote clôturé' ?></span>
              · <?= $total ?> vote<?= $total > 1 ? 's' : '' ?>
            </p>
          </div>
          <a href="admin.php?logout=1" class="button is-secondary is-small">Déconnexion</a>
        </div>

        <?php if ($flash): ?>
          <div class="alert is-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>

        <div class="admin_grid">
          <!-- Colonne gauche : contenu du concours -->
          <div class="admin_col">
            <div class="card_component">
              <h2 class="heading-style-h5">Images (<?= count($data['images']) ?>/<?= MAX_IMAGES ?>)</h2>

              <?php if (count($data['images']) < MAX_IMAGES): ?>
                <form method="post" enctype="multipart/form-data" class="admin_upload">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="upload">
                  <label class="form_label" for="image">Fichier (JPG, PNG, WEBP, GIF)</label>
                  <input class="form_input" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
                  <label class="form_label" for="title">Titre (optionnel)</label>
                  <input class="form_input" type="text" id="title" name="title" maxlength="80">
                  <button type="submit" class="button is-small">Ajouter l'image</button>
                </form>
              <?php endif; ?>

              <div class="admin_images">
                <?php foreach ($data['images'] as $img): ?>
                  <div class="admin_image-item">
                    <img src="uploads/<?= e($img['file']) ?>" alt="" class="admin_thumb">
                    <form method="post" class="admin_image-form">
                      <input type="hidden" name="csrf" value="<?= $csrf ?>">
                      <input type="hidden" name="action" value="title">
                      <input type="hidden" name="id" value="<?= e($img['id']) ?>">
                      <input class="form_input is-small" type="text" name="title" value="<?= e($img['title']) ?>" placeholder="Titre" maxlength="80">
                      <button type="submit" class="button is-secondary is-small">OK</button>
                    </form>
                    <form method="post" onsubmit="return confirm('Supprimer cette image et ses votes ?');">
                      <input type="hidden" name="csrf" value="<?= $csrf ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= e($img['id']) ?>">
                      <button type="submit" class="button is-danger is-small" aria-label="Supprimer">✕</button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Colonne droite : pilotage + résultats -->
          <div class="admin_col">
            <div class="card_component">
              <h2 class="heading-style-h5">Pilotage du vote</h2>
              <div class="admin_actions">
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <?php if ($isOpen): ?>
                    <input type="hidden" name="action" value="close">
                    <button type="submit" class="button" onclick="return confirm('Clôturer le vote et publier les résultats ?');">Clôturer et publier</button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="open">
                    <button type="submit" class="button">Rouvrir le vote</button>
                  <?php endif; ?>
                </form>
                <form method="post" onsubmit="return confirm('Remettre tous les votes à zéro ?');">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="reset">
                  <button type="submit" class="button is-danger">Réinitialiser les votes</button>
                </form>
              </div>
              <div class="admin_option">
                <div>
                  <p class="text-weight-medium">Blocage par adresse IP :
                    <span class="status-badge <?= $checkIp ? 'is-open' : 'is-closed' ?>"><?= $checkIp ? 'Activé' : 'Désactivé' ?></span>
                  </p>
                  <p class="text-size-small text-color-muted">Activé, une seule personne peut voter par connexion internet (bureau, wifi partagé…).</p>
                </div>
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="toggle_ip">
                  <button type="submit" class="button is-secondary is-small"><?= $checkIp ? 'Désactiver' : 'Activer' ?></button>
                </form>
              </div>
            </div>

            <div class="card_component">
              <h2 class="heading-style-h5">Résultats en direct</h2>
              <?php if ($data['images']): ?>
                <?php include __DIR__ . '/inc/ranking.php'; ?>
              <?php else: ?>
                <p class="text-color-muted">Aucune image.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php page_footer();
