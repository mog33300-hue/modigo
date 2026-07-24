<?php

if (session_status() === PHP_SESSION_NONE) {

    session_start();
}

$current = basename($_SERVER['PHP_SELF']);

$role = $_SESSION['role'] ?? '';

$prenom = $_SESSION['prenom'] ?? 'Utilisateur';

$societe = $_SESSION['societe_nom'] ?? 'Medigo';

$plan = $_SESSION['plan'] ?? 'basic';

?>

<div class="sidebar">

    <!-- LOGO -->

    <div class="logo">

        🚑 Medigo

    </div>

    <!-- SOCIETE -->

    <div class="company-box">

        <strong>

            <?= htmlspecialchars($societe) ?>

        </strong>

        <br>

        <small>

            👤 <?= htmlspecialchars($prenom) ?>

        </small>

        <br>

        <small>

            💳 Plan :
            <?= htmlspecialchars(strtoupper($plan)) ?>

        </small>

    </div>

    <!-- MENU -->

    <nav class="menu-links">

        <!-- DASHBOARD -->

        <a
        href="index.php"
        class="<?= $current == 'index.php' ? 'active' : '' ?>"
        >
            🏠 Dashboard
        </a>

        <!-- PROFIL SOCIETE -->

        <a
        href="societe_profile.php"
        class="<?= $current == 'societe_profile.php' ? 'active' : '' ?>"
        >
            🏢 Profil société
        </a>

        <!-- PATIENTS -->

        <a
        href="patients.php"
        class="<?= $current == 'patients.php' ? 'active' : '' ?>"
        >
            👥 Patients
        </a>

        <!-- CHAUFFEURS -->

        <a
        href="chauffeurs.php"
        class="<?= $current == 'chauffeurs.php' ? 'active' : '' ?>"
        >
            🚘 Chauffeurs
        </a>

        <!-- COURSE -->

        <a
        href="create_course.php"
        class="<?= $current == 'create_course.php' ? 'active' : '' ?>"
        >
            ➕ Créer course
        </a>

        <!-- PLANNING -->

        <a
        href="planning_global.php"
        class="<?= $current == 'planning_global.php' ? 'active' : '' ?>"
        >
            📅 Planning
        </a>

        <!-- HISTORIQUE -->

        <a
        href="historique.php"
        class="<?= $current == 'historique.php' ? 'active' : '' ?>"
        >
            📚 Historique
        </a>

        <!-- STATS -->

        <a
        href="statistiques.php"
        class="<?= $current == 'statistiques.php' ? 'active' : '' ?>"
        >
            📊 Statistiques
        </a>
        <?php if(
    $role === 'admin' ||
    $role === 'superadmin'
): ?>

<a
href="maintenance.php"
class="<?= $current == 'maintenance.php' ? 'active' : '' ?>"
>
🧹 Maintenance
</a>
<a
href="utilisateurs.php"
class="<?= $current == 'utilisateurs.php' ? 'active' : '' ?>"
>
👥 Utilisateurs
</a>
<?php endif; ?>
        <!-- EXPORT -->

        <a
        href="export_courses.php"
        class="<?= $current == 'export_courses.php' ? 'active' : '' ?>"
        >
            📁 Export CSV
        </a>

        <!-- ABONNEMENT -->

        <a
        href="subscription.php"
        class="<?= $current == 'subscription.php' ? 'active' : '' ?>"
        >
            💳 Abonnement
        </a>

        <!-- ADMIN -->

        <?php if(
            $role === 'admin' ||
            $role === 'superadmin'
        ): ?>

        <div class="menu-separator">

            Administration

        </div>

        <a
        href="register.php"
        class="<?= $current == 'register.php' ? 'active' : '' ?>"
        >
            ➕ Nouvelle société
        </a>

        <?php endif; ?>

        <!-- SUPERADMIN -->

        <?php if($role === 'superadmin'): ?>

        <div class="menu-separator">

            SaaS

        </div>

        <a
        href="superadmin.php"
        class="<?= $current == 'superadmin.php' ? 'active' : '' ?>"
        >
            🛡️ Super Admin
        </a>

        <?php endif; ?>

        <!-- SESSION -->

        <div class="menu-separator">

            Session

        </div>

        <a href="logout.php">

            🚪 Déconnexion

        </a>

    </nav>

    <!-- FOOTER -->

    <div class="sidebar-footer">

        Medigo SaaS V1.0

        <br><br>

        🔒 Plateforme sécurisée

    </div>

</div>

<style>

.sidebar{

    width:260px;

    background:#111827;

    color:white;

    position:fixed;

    top:0;

    left:0;

    height:100vh;

    overflow-y:auto;

    padding:20px;

    box-sizing:border-box;
}

.logo{

    font-size:28px;

    font-weight:bold;

    margin-bottom:25px;

    text-align:center;
}

.company-box{

    background:rgba(255,255,255,0.08);

    padding:15px;

    border-radius:12px;

    margin-bottom:25px;

    line-height:1.7;
}

.menu-links{

    display:flex;

    flex-direction:column;

    gap:10px;
}

.menu-links a{

    color:white;

    text-decoration:none;

    padding:12px 14px;

    border-radius:10px;

    transition:0.2s;

    background:rgba(255,255,255,0.03);

    font-size:15px;
}

.menu-links a:hover{

    background:#2563eb;
}

.menu-links a.active{

    background:#2563eb;
}

.menu-separator{

    margin-top:20px;

    margin-bottom:8px;

    font-size:13px;

    color:#9ca3af;

    text-transform:uppercase;

    letter-spacing:1px;
}

.sidebar-footer{

    margin-top:30px;

    font-size:12px;

    color:#9ca3af;

    text-align:center;

    line-height:1.5;
}

/* MAIN */

.main{

    margin-left:280px;

    padding:25px;
}

/* MOBILE */

@media(max-width:900px){

    .sidebar{

        position:relative;

        width:100%;

        height:auto;
    }

    .main{

        margin-left:0;
    }
}

</style>