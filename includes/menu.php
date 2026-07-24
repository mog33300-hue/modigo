<?php
$current = basename($_SERVER['PHP_SELF']);

if (!function_exists('modigo_active')) {
    function modigo_active(string $page): string {
        global $current;
        return $current === $page ? 'active' : '';
    }
}

$menu_societe = $_SESSION['societe_nom'] ?? 'MODIGO';
$menu_prenom = $_SESSION['prenom'] ?? 'Utilisateur';
$menu_role = $_SESSION['role'] ?? '';
?>

<aside class="sidebar">

    <div class="brand">
        <div class="brand-icon">🚑</div>
        <div>
            <h1>MODIGO</h1>
            <p>Transport sanitaire intelligent</p>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?= strtoupper(substr($menu_prenom, 0, 1)) ?>
        </div>
        <div>
            <strong><?= htmlspecialchars($menu_prenom) ?></strong>
            <span><?= htmlspecialchars($menu_societe) ?></span>
        </div>
    </div>

    <nav class="nav">

        <a href="dashboard.php" class="<?= modigo_active('dashboard.php') ?>">
            🏠 Tableau de bord
        </a>

        <a href="courses.php" class="<?= modigo_active('courses.php') ?>">
            🚗 Courses
        </a>

        <a href="create_course.php" class="<?= modigo_active('create_course.php') ?>">
            ➕ Nouvelle course
        </a>

        <a href="patients.php" class="<?= modigo_active('patients.php') ?>">
            👥 Patients
        </a>

        <a href="chauffeurs.php" class="<?= modigo_active('chauffeurs.php') ?>">
            🚘 Chauffeurs
        </a>

        <a href="vehicles.php" class="<?= modigo_active('vehicles.php') ?>">
            🚐 Véhicules
        </a>

        <a href="planning_global.php" class="<?= modigo_active('planning_global.php') ?>">
            📅 Planning
        </a>

        <a href="gps_admin.php" class="<?= modigo_active('gps_admin.php') ?>">
            📍 Régulation GPS
        </a>

        <a href="historique.php" class="<?= modigo_active('historique.php') ?>">
            📊 Historique
        </a>


        <a href="about.php" class="<?= modigo_active('about.php') ?>">
            ℹ️ À propos
        </a>

        <a href="logout.php" class="logout">
            🚪 Déconnexion
        </a>

    </nav>

    <div class="sidebar-status">
        <div><span class="service-dot online"></span> Base de données</div>
        <div><span class="service-dot online"></span> MODIGO actif</div>
        <div><span class="service-dot online"></span> OpenStreetMap</div>
    </div>

    <div class="sidebar-version">
        <strong>MODIGO V1.0 Stable</strong>
        <span>Build 2026.07.19</span>
    </div>

</aside>

<main class="main">
