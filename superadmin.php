<?php
require 'auth.php';
require 'config.php';

/* =====================================================
   SUPERADMIN ONLY
===================================================== */

if (($_SESSION['role'] ?? '') !== 'superadmin') {

    die("Accès refusé");
}

/* =====================================================
   STATS
===================================================== */

$stats = [];

$stats['societes'] =
$pdo->query("
SELECT COUNT(*)
FROM societes
")->fetchColumn();

$stats['users'] =
$pdo->query("
SELECT COUNT(*)
FROM users
")->fetchColumn();

$stats['courses'] =
$pdo->query("
SELECT COUNT(*)
FROM courses
")->fetchColumn();

$stats['patients'] =
$pdo->query("
SELECT COUNT(*)
FROM patients
")->fetchColumn();

/* =====================================================
   ACTIVER / DESACTIVER
===================================================== */

if(isset($_GET['toggle'])){

    $id = intval($_GET['toggle']);

    $stmt = $pdo->prepare("
    SELECT statut
    FROM societes
    WHERE id=?
    ");

    $stmt->execute([$id]);

    $societe = $stmt->fetch(PDO::FETCH_ASSOC);

    if($societe){

        $newStatut =
        ($societe['statut'] === 'active')
        ? 'inactive'
        : 'active';

        $up = $pdo->prepare("
        UPDATE societes
        SET statut=?
        WHERE id=?
        ");

        $up->execute([

            $newStatut,
            $id
        ]);
    }

    header("Location: superadmin.php");

    exit;
}

/* =====================================================
   DELETE SOCIETE
===================================================== */

if(isset($_GET['delete'])){

    $id = intval($_GET['delete']);

    /* ================================================
       SECURITE :
       IMPOSSIBLE DE SUPPRIMER
       SA PROPRE SOCIETE
    ================================================ */

    if($id == intval($_SESSION['societe_id'])){

        die("
        <h2>
        Impossible de supprimer votre propre société.
        </h2>
        ");
    }

    if($id > 0){

        /* ============================================
           RECUP DOCS
        ============================================ */

        $stmt = $pdo->prepare("
        SELECT
            logo,
            kbis,
            assurance
        FROM societes
        WHERE id=?
        ");

        $stmt->execute([$id]);

        $societe = $stmt->fetch(PDO::FETCH_ASSOC);

        if($societe){

            /* ========================================
               DELETE FILES
            ======================================== */

            foreach([

                $societe['logo'] ?? '',

                $societe['kbis'] ?? '',

                $societe['assurance'] ?? ''

            ] as $file){

                if(

                    !empty($file) &&

                    file_exists(
                        __DIR__ .
                        '/' .
                        $file
                    )

                ){

                    @unlink(
                        __DIR__ .
                        '/' .
                        $file
                    );
                }
            }

            /* ========================================
               DELETE COURSES
            ======================================== */

            $stmt = $pdo->prepare("
            DELETE FROM courses
            WHERE societe_id=?
            ");

            $stmt->execute([$id]);

            /* ========================================
               DELETE PATIENTS
            ======================================== */

            $stmt = $pdo->prepare("
            DELETE FROM patients
            WHERE societe_id=?
            ");

            $stmt->execute([$id]);

            /* ========================================
               DELETE USERS
            ======================================== */

            $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE societe_id=?
            ");

            $stmt->execute([$id]);

            /* ========================================
               DELETE SOCIETE
            ======================================== */

            $stmt = $pdo->prepare("
            DELETE FROM societes
            WHERE id=?
            ");

            $stmt->execute([$id]);
        }
    }

    header("Location: superadmin.php");

    exit;
}

/* =====================================================
   LISTE SOCIETES
===================================================== */

$stmt = $pdo->query("
SELECT

    s.*,

    COUNT(DISTINCT u.id) AS nb_users,

    COUNT(DISTINCT p.id) AS nb_patients,

    COUNT(DISTINCT c.id) AS nb_courses

FROM societes s

LEFT JOIN users u
ON u.societe_id = s.id

LEFT JOIN patients p
ON p.societe_id = s.id

LEFT JOIN courses c
ON c.societe_id = s.id

GROUP BY s.id

ORDER BY s.id DESC
");

$societes =
$stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1"
>

<title>
Super Admin - Medigo SaaS
</title>

<link rel="stylesheet" href="style.css">

<style>

.stats-grid{

    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(220px,1fr));

    gap:15px;

    margin-bottom:20px;
}

.stat-card{

    background:white;

    border-radius:14px;

    padding:20px;

    box-shadow:0 2px 8px rgba(0,0,0,0.06);
}

.stat-card h2{

    font-size:34px;

    margin:0;
}

.badge-active{

    background:#dcfce7;

    color:#166534;

    padding:6px 10px;

    border-radius:999px;

    font-weight:bold;
}

.badge-inactive{

    background:#fee2e2;

    color:#991b1b;

    padding:6px 10px;

    border-radius:999px;

    font-weight:bold;
}

.actions-flex{

    display:flex;

    gap:8px;

    flex-wrap:wrap;
}

.btn-delete{

    background:#dc2626;

    color:white;
}

.btn-delete:hover{

    background:#b91c1c;
}

</style>

</head>

<body>

<?php include 'menu.php'; ?>

<div class="main">

<a
href="index.php"
class="btn btn-back"
>
⬅ Retour
</a>

<h1>
🛡️ Super Admin SaaS
</h1>

<!-- STATS -->

<div class="stats-grid">

<div class="stat-card">

<h2>

<?= intval($stats['societes']) ?>

</h2>

<p>
🏢 Sociétés
</p>

</div>

<div class="stat-card">

<h2>

<?= intval($stats['users']) ?>

</h2>

<p>
👤 Utilisateurs
</p>

</div>

<div class="stat-card">

<h2>

<?= intval($stats['courses']) ?>

</h2>

<p>
🚗 Courses
</p>

</div>

<div class="stat-card">

<h2>

<?= intval($stats['patients']) ?>

</h2>

<p>
👥 Patients
</p>

</div>

</div>

<!-- TABLE -->

<div class="card">

<h2>
🏢 Sociétés clientes
</h2>

<div class="table-scroll">

<table class="table-pro">

<tr>

<th>ID</th>

<th>Société</th>

<th>Email</th>

<th>Plan</th>

<th>Statut</th>

<th>Expiration</th>

<th>Users</th>

<th>Patients</th>

<th>Courses</th>

<th>Actions</th>

</tr>

<?php foreach($societes as $s): ?>

<tr>

<td>

<?= intval($s['id']) ?>

</td>

<td>

<?= htmlspecialchars($s['nom'] ?? '') ?>

</td>

<td>

<?= htmlspecialchars($s['email'] ?? '') ?>

</td>

<td>

<?= htmlspecialchars($s['plan'] ?? 'basic') ?>

</td>

<td>

<?php if(
($s['statut'] ?? '') === 'active'
): ?>

<span class="badge-active">

Active

</span>

<?php else: ?>

<span class="badge-inactive">

Inactive

</span>

<?php endif; ?>

</td>

<td>

<?= htmlspecialchars(
$s['date_expiration'] ?? '-'
) ?>

</td>

<td>

<?= intval($s['nb_users']) ?>

</td>

<td>

<?= intval($s['nb_patients']) ?>

</td>

<td>

<?= intval($s['nb_courses']) ?>

</td>

<td>

<div class="actions-flex">

<a
href="superadmin.php?toggle=<?= intval($s['id']) ?>"
class="btn btn-edit"
onclick="return confirm(
'Changer le statut de cette société ?'
)"
>

🔄 Activer / désactiver

</a>

<?php if(
intval($s['id']) !== intval($_SESSION['societe_id'])
): ?>

<a
href="superadmin.php?delete=<?= intval($s['id']) ?>"
class="btn btn-delete"
onclick="return confirm(
'Supprimer définitivement cette société ?'
)"
>

🗑 Supprimer

</a>

<?php else: ?>

<span
style="
color:#6b7280;
font-size:13px;
padding:8px;
"
>

Société principale

</span>

<?php endif; ?>

</div>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

<!-- FOOTER -->

<div class="footer">

Medigo SaaS V1.0

<br>

Super administration

</div>

</div>

</body>

</html>