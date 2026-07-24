<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("<h2>Société invalide</h2>");
}

$stmt = $pdo->prepare("
SELECT
    c.*,
    u.prenom AS chauffeur,
    v.plate AS vehicule
FROM courses c
LEFT JOIN users u
ON u.id = c.chauffeur_id
LEFT JOIN vehicles v
ON v.id = c.vehicle_id
WHERE c.societe_id=?
AND c.statut NOT IN (
    'terminée',
    'terminee',
    'terminé',
    'termine',
    'TERMINEE',
    'TERMINE'
)
ORDER BY
c.date_course ASC,
c.heure_pickup ASC
");

$stmt->execute([$societe_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($courses);

$today = 0;

foreach($courses as $c){
    if(!empty($c['date_course']) && $c['date_course'] === date('Y-m-d')){
        $today++;
    }
}
?>

<?php
$page_title='MODIGO - Planning';
include __DIR__.'/includes/header.php';
include __DIR__.'/includes/menu.php';
?>

<div class="topbar"><div><h1>Planning</h1><p>Organisation chronologique des transports actifs MODIGO</p></div><div class="topbar-actions"><a href="dashboard.php" class="btn btn-glass">🏠 Dashboard</a><a href="create_course.php" class="btn btn-white">➕ Nouvelle course</a><a href="#" onclick="window.print();return false;" class="btn btn-glass">🖨️ Imprimer</a></div></div>
<section class="hero"><div class="badge-title">Planning de régulation</div><h2>Vue chronologique des courses</h2><p>Suivez les missions actives, les chauffeurs, les véhicules, les départs, les arrivées et les retards depuis une vue claire adaptée au PC et au mobile.</p></section>
<?php
$retards = 0;
$chauffeurs_utilises = [];
$vehicules_utilises = [];
foreach($courses as $c){
    if(!empty($c['chauffeur'])) $chauffeurs_utilises[$c['chauffeur']] = true;
    if(!empty($c['vehicule'])) $vehicules_utilises[$c['vehicule']] = true;
    if (!empty($c['date_course']) && !empty($c['heure_pickup']) && empty($c['depart_reel'])) {
        $heure_prevue = strtotime($c['date_course'] . ' ' . $c['heure_pickup']);
        if (time() > $heure_prevue) { $retards++; }
    }
}
?>
<div class="stats-grid">
<div class="stat-card"><small>🚑 Courses actives</small><h2><?= intval($total) ?></h2></div>
<div class="stat-card"><small>📅 Aujourd'hui</small><h2><?= intval($today) ?></h2></div>
<div class="stat-card"><small>🚘 Chauffeurs</small><h2><?= intval(count($chauffeurs_utilises)) ?></h2></div>
<div class="stat-card"><small>⚠ Retards</small><h2><?= intval($retards) ?></h2></div>
</div>
<?php if(empty($courses)): ?>
<div class="empty">Aucune course active dans le planning.</div>
<?php else: ?>
<div class="planning-list">
<?php
$current_day = '';
foreach($courses as $course):
    $date_key = $course['date_course'] ?? '';
    if($date_key !== $current_day):
        $current_day = $date_key;
?>
<div class="day-separator">📅 <?= !empty($current_day) ? date('d/m/Y', strtotime($current_day)) : 'Date non renseignée' ?></div>
<?php endif; ?>
<?php
$statut = strtolower(trim($course['statut'] ?? ''));
$statusClass = 'status-prevu'; $statusLabel = 'Prévue';
if($statut === 'en cours' || $statut === 'en_cours'){ $statusClass = 'status-cours'; $statusLabel = 'En cours'; }
if (!empty($course['date_course']) && !empty($course['heure_pickup']) && empty($course['depart_reel'])) {
    $heure_prevue = strtotime($course['date_course'] . ' ' . $course['heure_pickup']);
    if (time() > $heure_prevue) { $statusClass = 'status-retard'; $statusLabel = 'En retard'; }
}
?>
<div class="course-card">
<div class="time-block"><strong><?= !empty($course['heure_pickup']) ? htmlspecialchars(substr($course['heure_pickup'],0,5)) : '--:--' ?></strong><span>Pickup</span></div>
<div class="course-main">
<strong>👤 <?= htmlspecialchars($course['client_nom'] ?? 'Patient') ?></strong>
<div class="meta">
<div><span>🚘</span><div><?= !empty($course['chauffeur']) ? htmlspecialchars($course['chauffeur']) : 'Chauffeur non assigné' ?></div></div>
<div><span>🚐</span><div><?= !empty($course['vehicule']) ? htmlspecialchars($course['vehicule']) : 'Véhicule non assigné' ?></div></div>
<div><span>📍</span><div><?= htmlspecialchars($course['adresse_depart'] ?? '-') ?></div></div>
<div><span>🏁</span><div><?= htmlspecialchars($course['adresse_arrivee'] ?? '-') ?></div></div>
</div>
</div>
<div class="course-actions">
<span class="status <?= $statusClass ?>"><?= $statusLabel ?></span>
<?php $destination = trim(($course['adresse_arrivee'] ?? '') . ' ' . ($course['ville_arrivee'] ?? '')); $destination_url = urlencode($destination); ?>
<?php if(!empty($destination)): ?><a href="https://www.openstreetmap.org/search?query=<?= $destination_url ?>" target="_blank" class="btn btn-white">📍 Carte</a><?php endif; ?>
<?php if(!empty($course['id'])): ?><a href="edit_course.php?id=<?= intval($course['id']) ?>" class="btn btn-edit">✏ Modifier</a><?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__.'/includes/footer.php'; ?>
