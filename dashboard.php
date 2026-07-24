<?php
require 'auth.php';
require 'config.php';

$user_id = intval($_SESSION['user_id'] ?? 0);
$societe_id = intval($_SESSION['societe_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT u.*, s.nom AS societe_nom
    FROM users u
    LEFT JOIN societes s ON s.id = u.societe_id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

function count_sql(PDO $pdo, string $sql, array $params = []): int {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$courses_today = count_sql($pdo, "SELECT COUNT(*) FROM courses WHERE societe_id = ? AND date_course = CURDATE()", [$societe_id]);
$courses_week = count_sql($pdo, "SELECT COUNT(*) FROM courses WHERE societe_id = ? AND YEARWEEK(date_course,1) = YEARWEEK(CURDATE(),1)", [$societe_id]);
$patients = count_sql($pdo, "SELECT COUNT(*) FROM patients WHERE societe_id = ?", [$societe_id]);
$chauffeurs = count_sql($pdo, "SELECT COUNT(*) FROM users WHERE societe_id = ? AND role = 'chauffeur'", [$societe_id]);
$vehicules = count_sql($pdo, "SELECT COUNT(*) FROM vehicles WHERE company_id = ?", [$societe_id]);
$missions = count_sql($pdo, "SELECT COUNT(*) FROM courses WHERE societe_id = ? AND statut = 'en cours'", [$societe_id]);
$incidents = count_sql($pdo, "SELECT COUNT(*) FROM courses WHERE societe_id = ? AND statut = 'incident'", [$societe_id]);
$retards = count_sql($pdo, "
    SELECT COUNT(*) FROM courses
    WHERE societe_id = ?
    AND depart_reel IS NULL
    AND date_course IS NOT NULL
    AND heure_pickup IS NOT NULL
    AND CONCAT(date_course,' ',heure_pickup) < NOW()
    AND statut NOT IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE','incident','en cours')
", [$societe_id]);

$next_courses = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.prenom AS chauffeur
        FROM courses c
        LEFT JOIN users u ON u.id = c.chauffeur_id
        WHERE c.societe_id = ?
        ORDER BY c.date_course ASC, c.heure_pickup ASC
        LIMIT 8
    ");
    $stmt->execute([$societe_id]);
    $next_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $next_courses = [];
}

$societe_nom = $user['societe_nom'] ?? 'MODIGO';
$prenom = $user['prenom'] ?? 'Utilisateur';
?>
<?php
$en_attente = count_sql($pdo, "SELECT COUNT(*) FROM courses WHERE societe_id=? AND statut IN ('prévue','prévu','prevue','prevu')", [$societe_id]);
$terminees_today = count_sql($pdo, "SELECT COUNT(*) FROM courses WHERE societe_id=? AND date_course=CURDATE() AND statut IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE')", [$societe_id]);
$gps_actifs = count_sql($pdo, "SELECT COUNT(*) FROM courses WHERE societe_id=? AND latitude IS NOT NULL AND longitude IS NOT NULL AND statut NOT IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE')", [$societe_id]);
$vehicules_disponibles = max(0, intval($vehicules)-intval($missions));
$page_title='MODIGO - Dashboard Premium V2';
$modigo_page_class='dashboard-premium-v2';
$modigo_extra_head='<link rel="stylesheet" href="assets/css/dashboard_v2.css?v=2.0"><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
include __DIR__.'/includes/header.php';
include __DIR__.'/includes/menu.php';
?>

<div class="dpv2">
<header class="dpv2-top">
  <div><span>Centre de commandement</span><h1>Centre de supervision</h1><p><?= htmlspecialchars($societe_nom) ?> · <?= date('d/m/Y H:i') ?></p></div>
  <div class="dpv2-user"><b><?= htmlspecialchars($prenom) ?></b><small>Session active · MODIGO V1.0</small></div>
</header>

<section class="dpv2-hero">
  <div><em>Régulation sanitaire intelligente</em><h2>Tout le service en un coup d'œil</h2><p>Courses, chauffeurs, patients, véhicules, planning, retards, incidents et GPS réunis dans un seul centre de pilotage.</p><div class="dpv2-actions"><a class="btn btn-white" href="create_course.php">➕ Nouvelle course</a><a class="btn btn-glass" href="gps_admin.php">📍 Centre GPS</a><a class="btn btn-glass" href="planning_global.php">📅 Planning</a></div></div>
  <div class="dpv2-priority">
    <div><span>🟠 En mission</span><strong><?= intval($missions) ?></strong></div>
    <div><span>🔴 Retards</span><strong><?= intval($retards) ?></strong></div>
    <div><span>🚨 Incidents</span><strong><?= intval($incidents) ?></strong></div>
    <div><span>🟢 Disponibles</span><strong><?= max(0,intval($chauffeurs)-intval($missions)) ?></strong></div>
  </div>
</section>

<section class="dpv2-stats">
  <a href="courses.php"><span>🚑</span><small>Courses aujourd'hui</small><strong><?= intval($courses_today) ?></strong></a>
  <a href="courses.php?statut=prévue"><span>🕒</span><small>En attente</small><strong><?= intval($en_attente) ?></strong></a>
  <a href="patients.php"><span>👥</span><small>Patients</small><strong><?= intval($patients) ?></strong></a>
  <a href="chauffeurs.php"><span>🚘</span><small>Chauffeurs</small><strong><?= intval($chauffeurs) ?></strong></a>
  <a href="vehicles.php"><span>🚐</span><small>Véhicules disponibles</small><strong><?= intval($vehicules_disponibles) ?></strong></a>
  <a href="gps_admin.php"><span>📍</span><small>GPS actifs</small><strong><?= intval($gps_actifs) ?></strong></a>
</section>

<section class="dpv2-grid">
  <div class="dpv2-panel dpv2-map"><div class="dpv2-head"><div><small>Supervision cartographique</small><h2>📍 Mini-carte GPS</h2></div><a class="btn btn-glass" href="gps_admin.php">Ouvrir le GPS</a></div><div id="dashboardMap"></div><?php if($gps_actifs===0): ?><div class="dpv2-mapmsg"><b>🗺️ Carte prête</b><span>Aucune position GPS transmise.</span></div><?php endif; ?></div>
  <div class="dpv2-panel"><div class="dpv2-head"><div><small>Priorités régulation</small><h2>⚠️ Alertes</h2></div></div>
    <div class="dpv2-alerts">
      <?php if($incidents>0): ?><a class="danger" href="gps_admin.php">🚨 <b><?= intval($incidents) ?> incident(s)</b><span>Intervention nécessaire.</span></a><?php endif; ?>
      <?php if($retards>0): ?><a class="warning" href="planning_global.php">⏰ <b><?= intval($retards) ?> retard(s)</b><span>Consultez le planning.</span></a><?php endif; ?>
      <?php if($incidents===0 && $retards===0): ?><a class="success" href="dashboard.php">🟢 <b>Aucune alerte critique</b><span>Service normal.</span></a><?php endif; ?>
    </div>
    <div class="dpv2-availability"><div><small>Chauffeurs disponibles</small><strong><?= max(0,intval($chauffeurs)-intval($missions)) ?></strong></div><div><small>Véhicules disponibles</small><strong><?= intval($vehicules_disponibles) ?></strong></div></div>
  </div>
</section>

<section class="dpv2-grid bottom">
  <div class="dpv2-panel"><div class="dpv2-head"><div><small>Planning opérationnel</small><h2>🚑 Prochaines courses</h2></div><a class="btn btn-glass" href="planning_global.php">Voir le planning</a></div><div class="dpv2-courses">
  <?php if(empty($next_courses)): ?><div class="dpv2-empty">Aucune course planifiée.</div><?php else: foreach(array_slice($next_courses,0,5) as $c): ?>
    <a href="edit_course.php?id=<?= intval($c['id']) ?>"><div><b><?= !empty($c['heure_pickup'])?htmlspecialchars(substr($c['heure_pickup'],0,5)):'--:--' ?></b><small><?= !empty($c['date_course'])?date('d/m',strtotime($c['date_course'])):'--/--' ?></small></div><div><b><?= htmlspecialchars($c['client_nom']??'-') ?></b><small><?= htmlspecialchars($c['chauffeur']??'Non assigné') ?> · <?= htmlspecialchars($c['adresse_arrivee']??'-') ?></small></div><span><?= htmlspecialchars($c['statut']??'Prévue') ?></span></a>
  <?php endforeach; endif; ?>
  </div></div>
  <div class="dpv2-panel"><div class="dpv2-head"><div><small>Actions fréquentes</small><h2>⚡ Accès rapides</h2></div></div><div class="dpv2-quick"><a href="create_course.php">➕<b>Nouvelle course</b></a><a href="add_patient.php">👤<b>Nouveau patient</b></a><a href="add_chauffeur.php">🚘<b>Nouveau chauffeur</b></a><a href="vehicles.php">🚐<b>Véhicules</b></a><a href="planning_global.php">📅<b>Planning</b></a><a href="historique.php">📊<b>Historique</b></a></div><div class="dpv2-summary"><div><small>Courses semaine</small><strong><?= intval($courses_week) ?></strong></div><div><small>Terminées aujourd'hui</small><strong><?= intval($terminees_today) ?></strong></div></div></div>
</section>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>(function(){if(typeof L==='undefined')return;const map=L.map('dashboardMap',{zoomControl:false}).setView([44.8378,-0.5792],10);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);setTimeout(()=>map.invalidateSize(),300);})();</script>
<?php include __DIR__.'/includes/footer.php'; ?>
