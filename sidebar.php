<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$company_name = $_SESSION['company_name'] ?? 'Medigo';
?>

<div class="sidebar">

    <div class="logo">
        🚑 Medigo
    </div>

    <div style="padding: 10px 20px; color: #ffffff; font-size: 14px;">
        <?= htmlspecialchars($company_name) ?>
    </div>

    <div class="menu-header">
        MENU
    </div>

    <nav class="menu-links">

        <a href="index.php" class="<?= $current == 'index.php' ? 'active' : '' ?>">
            🏠 Dashboard
        </a>

        <a href="agenda.php" class="<?= $current == 'agenda.php' ? 'active' : '' ?>">
            📅 Agenda
        </a>

        <a href="planning_global.php" class="<?= $current == 'planning_global.php' ? 'active' : '' ?>">
            🚗 Planning global
        </a>

        <a href="historique.php" class="<?= $current == 'historique.php' ? 'active' : '' ?>">
            📚 Historique
        </a>

        <a href="patients.php" class="<?= $current == 'patients.php' ? 'active' : '' ?>">
            👥 Patients
        </a>

        <a href="chauffeurs.php" class="<?= $current == 'chauffeurs.php' ? 'active' : '' ?>">
            🚘 Chauffeurs
        </a>

        <a href="create_course.php" class="<?= $current == 'create_course.php' ? 'active' : '' ?>">
            ➕ Créer une course
        </a>

        <a href="statistiques.php" class="<?= $current == 'statistiques.php' ? 'active' : '' ?>">
            📊 Statistiques
        </a>

        <a href="export_courses.php" class="<?= $current == 'export_courses.php' ? 'active' : '' ?>">
            📁 Export CSV
        </a>

        <?php if (in_array($role, ['admin', 'superadmin'])): ?>

            <a href="users.php" class="<?= $current == 'users.php' ? 'active' : '' ?>">
                👤 Utilisateurs
            </a>

            <a href="subscription.php" class="<?= $current == 'subscription.php' ? 'active' : '' ?>">
                💳 Abonnement
            </a>

            <a href="backup.php" class="<?= $current == 'backup.php' ? 'active' : '' ?>">
                💾 Sauvegarde
            </a>

        <?php endif; ?>

        <?php if ($role === 'superadmin'): ?>

            <div class="menu-header">
                SAAS
            </div>

            <a href="companies.php" class="<?= $current == 'companies.php' ? 'active' : '' ?>">
                🏢 Entreprises
            </a>

            <a href="saas_stats.php" class="<?= $current == 'saas_stats.php' ? 'active' : '' ?>">
                📈 Stats SaaS
            </a>

        <?php endif; ?>

    </nav>

    <div class="menu-bottom">

        <a href="logout.php" class="logout">
            🚪 Déconnexion
        </a>

        <div class="version">
            Medigo V1.0
            <br>
            Gestion intelligente du transport médical
            <br><br>
            <a href="rgpd.php">
                🔒 RGPD
            </a>
        </div>

    </div>

</div>