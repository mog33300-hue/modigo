<?php
$current = basename($_SERVER['PHP_SELF']);

if (!function_exists('modigo_active')) {
    function modigo_active(string $page): string
    {
        global $current;
        return $current === $page ? 'active' : '';
    }
}

/*
 * Identité affichée dans MODIGO
 *
 * On privilégie l'adresse e-mail réelle du compte connecté afin
 * d'éviter l'affichage d'un ancien nom de démonstration.
 */
$menu_email = trim((string) (
    $user['email']
    ?? $_SESSION['email']
    ?? ''
));

$menu_societe = trim((string) (
    $user['societe_nom']
    ?? $_SESSION['societe_nom']
    ?? 'MODIGO'
));

$menu_utilisateur = $menu_email !== ''
    ? $menu_email
    : 'Utilisateur connecté';

$menu_initiale = strtoupper(
    function_exists('mb_substr')
        ? mb_substr($menu_utilisateur, 0, 1, 'UTF-8')
        : substr($menu_utilisateur, 0, 1)
);

/*
 * Le dashboard utilise la variable $prenom dans son encart supérieur.
 * Cette affectation garantit que le même compte réel est affiché partout.
 */
$prenom = $menu_utilisateur;
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
            <?= htmlspecialchars($menu_initiale, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div>
            <strong>
                <?= htmlspecialchars($menu_utilisateur, ENT_QUOTES, 'UTF-8') ?>
            </strong>

            <span>
                <?= htmlspecialchars($menu_societe, ENT_QUOTES, 'UTF-8') ?>
            </span>
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
        <div>
            <span class="service-dot online"></span>
            Base de données
        </div>

        <div>
            <span class="service-dot online"></span>
            MODIGO actif
        </div>

        <div>
            <span class="service-dot online"></span>
            OpenStreetMap
        </div>
    </div>

    <div class="sidebar-version">
        <strong>MODIGO V1.0 Stable</strong>
        <span>Build 2026.07.30</span>
    </div>

</aside>

<main class="main">
