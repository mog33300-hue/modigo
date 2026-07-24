<?php
require 'auth.php';
require 'config.php';

$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    die('Accès refusé');
}

$page_title = 'MODIGO - À propos';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';

function modigo_format_bytes($bytes): string {
    $bytes = max(0, (float)$bytes);
    $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    $power = min($power, count($units) - 1);
    return number_format($bytes / (1024 ** $power), 2, ',', ' ') . ' ' . $units[$power];
}

function modigo_status(bool $ok, string $okText = 'Opérationnel', string $badText = 'À vérifier'): string {
    $class = $ok ? 'ok' : 'warning';
    $text = $ok ? $okText : $badText;
    $dot = $ok ? '●' : '●';
    return '<span class="about-status ' . $class . '">' . $dot . ' ' . htmlspecialchars($text) . '</span>';
}

$db_version = 'Indisponible';
$db_ok = false;

try {
    $db_version = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    $db_ok = true;
} catch (Throwable $e) {
    $db_version = 'Erreur de connexion';
}

$project_path = __DIR__;
$free_space = @disk_free_space($project_path);
$total_space = @disk_total_space($project_path);
$free_percent = ($free_space !== false && $total_space) ? round(($free_space / $total_space) * 100, 1) : null;

$checks = [
    'Base de données' => $db_ok,
    'Dossier projet accessible' => is_readable($project_path),
    'Dossier sauvegardes' => is_dir(__DIR__ . '/sauvegardes'),
    'Bibliothèque TCPDF' => is_file(__DIR__ . '/tcpdf/tcpdf.php'),
    'Bibliothèque PHPMailer' => is_dir(__DIR__ . '/phpmailer/src'),
    'Bibliothèque Stripe' => is_file(__DIR__ . '/stripe-php/init.php'),
    'Son d’alarme GPS' => is_file(__DIR__ . '/sounds/alarm.mp3'),
];

$societe = $_SESSION['societe_nom'] ?? 'MODIGO';
$utilisateur = $_SESSION['prenom'] ?? 'Utilisateur';
?>

<div class="modigo-page-title">
    <div>
        <h1>À propos de MODIGO</h1>
        <p>Informations techniques, version et état général de l'installation.</p>
    </div>

    <div class="topbar-actions">
        <a href="#" class="btn btn-back" data-modigo-back>⬅ Retour</a>
        <a href="dashboard.php" class="btn btn-white">🏠 Dashboard</a>
    </div>
</div>

<div class="about-grid">

    <section class="about-card">
        <h2>🚑 Version du logiciel</h2>

        <div class="about-list">
            <div class="about-row">
                <span>Produit</span>
                <strong>MODIGO</strong>
            </div>
            <div class="about-row">
                <span>Version</span>
                <strong>V1.0 Stable</strong>
            </div>
            <div class="about-row">
                <span>Build</span>
                <strong>2026.07.19</strong>
            </div>
            <div class="about-row">
                <span>Société connectée</span>
                <strong><?= htmlspecialchars($societe) ?></strong>
            </div>
            <div class="about-row">
                <span>Utilisateur</span>
                <strong><?= htmlspecialchars($utilisateur) ?></strong>
            </div>
            <div class="about-row">
                <span>Rôle</span>
                <strong><?= htmlspecialchars($role) ?></strong>
            </div>
        </div>
    </section>

    <section class="about-card">
        <h2>🖥️ Environnement serveur</h2>

        <div class="about-list">
            <div class="about-row">
                <span>PHP</span>
                <strong><?= htmlspecialchars(PHP_VERSION) ?></strong>
            </div>
            <div class="about-row">
                <span>MariaDB / MySQL</span>
                <strong><?= htmlspecialchars($db_version) ?></strong>
            </div>
            <div class="about-row">
                <span>Système</span>
                <strong><?= htmlspecialchars(PHP_OS_FAMILY) ?></strong>
            </div>
            <div class="about-row">
                <span>Serveur web</span>
                <strong><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Non renseigné') ?></strong>
            </div>
            <div class="about-row">
                <span>Fuseau horaire</span>
                <strong><?= htmlspecialchars(date_default_timezone_get()) ?></strong>
            </div>
        </div>
    </section>

    <section class="about-card">
        <h2>💾 Stockage</h2>

        <div class="about-list">
            <div class="about-row">
                <span>Espace total</span>
                <strong><?= $total_space !== false ? modigo_format_bytes($total_space) : 'Indisponible' ?></strong>
            </div>
            <div class="about-row">
                <span>Espace libre</span>
                <strong><?= $free_space !== false ? modigo_format_bytes($free_space) : 'Indisponible' ?></strong>
            </div>
            <div class="about-row">
                <span>Pourcentage libre</span>
                <strong><?= $free_percent !== null ? htmlspecialchars((string)$free_percent) . ' %' : 'Indisponible' ?></strong>
            </div>
            <div class="about-row">
                <span>Dossier du projet</span>
                <strong><?= htmlspecialchars($project_path) ?></strong>
            </div>
        </div>
    </section>

    <section class="about-card">
        <h2>🟢 État des services</h2>

        <div class="about-list">
            <?php foreach ($checks as $label => $ok): ?>
            <div class="about-row">
                <span><?= htmlspecialchars($label) ?></span>
                <strong><?= modigo_status((bool)$ok) ?></strong>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="about-actions">
            <a href="gps_admin.php" class="btn btn-glass">📍 Tester le GPS</a>
            <a href="planning_global.php" class="btn btn-glass">📅 Tester le planning</a>
        </div>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
