<?php
require_once __DIR__ . '/../config.php';

session_start();

function default_data(): array
{
    return [
        'status' => 'open', // open | closed
        'check_ip' => VOTE_CHECK_IP, // blocage par adresse IP, modifiable dans l'admin
        'images' => [],
        'voters' => [],
    ];
}

function load_data(): array
{
    if (!is_file(DATA_FILE)) {
        return default_data();
    }
    $json = json_decode((string) file_get_contents(DATA_FILE), true);
    return is_array($json) ? array_merge(default_data(), $json) : default_data();
}

/**
 * Lecture + modification + écriture sous verrou exclusif,
 * pour éviter de perdre des votes simultanés.
 */
function update_data(callable $fn)
{
    if (!is_dir(dirname(DATA_FILE))) {
        mkdir(dirname(DATA_FILE), 0775, true);
    }
    $fp = fopen(DATA_FILE, 'c+');
    flock($fp, LOCK_EX);
    $raw  = stream_get_contents($fp);
    $data = json_decode($raw ?: '', true);
    $data = is_array($data) ? array_merge(default_data(), $data) : default_data();

    $result = $fn($data);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $result;
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Requête invalide.');
    }
}

/** Empreintes identifiant le votant (cookie + IP optionnelle) */
function voter_keys(array $data): array
{
    $keys = [];
    if (!empty($_COOKIE['voter_id']) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE['voter_id'])) {
        $keys[] = 'c:' . hash('sha256', $_COOKIE['voter_id']);
    }
    if (!empty($data['check_ip'])) {
        $keys[] = 'i:' . hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . __DIR__);
    }
    return $keys;
}

function ensure_voter_cookie(): void
{
    if (empty($_COOKIE['voter_id']) || !preg_match('/^[a-f0-9]{32}$/', $_COOKIE['voter_id'])) {
        $id = bin2hex(random_bytes(16));
        setcookie('voter_id', $id, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        $_COOKIE['voter_id'] = $id;
    }
}

function has_voted(array $data): bool
{
    foreach (voter_keys($data) as $k) {
        if (isset($data['voters'][$k])) {
            return true;
        }
    }
    return false;
}

function total_votes(array $data): int
{
    return array_sum(array_column($data['images'], 'votes'));
}

/** Images triées par nombre de votes décroissant */
function ranking(array $data): array
{
    $images = $data['images'];
    usort($images, fn($a, $b) => $b['votes'] <=> $a['votes']);
    return $images;
}

function page_header(string $title): void
{
    ?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="page-wrapper">
  <header class="header_component">
    <div class="padding-global">
      <div class="container-large">
        <div class="header_wrapper">
          <a href="index.php" class="header_logo">Prompt<span class="text-color-accent">Vote</span></a>
          <nav class="header_nav">
            <a href="index.php" class="header_link">Voter</a>
            <a href="resultats.php" class="header_link">Résultats</a>
          </nav>
        </div>
      </div>
    </div>
  </header>
  <main class="main-wrapper">
<?php
}

function page_footer(): void
{
    ?>
  </main>
  <footer class="footer_component">
    <div class="padding-global">
      <div class="container-large">
        <p class="text-size-small text-color-muted">© <?= date('Y') ?> PromptVote</p>
      </div>
    </div>
  </footer>
</div>
</body>
</html>
<?php
}
