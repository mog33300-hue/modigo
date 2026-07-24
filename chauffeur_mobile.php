<?php
require 'config.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    die("<h2>Token invalide</h2>");
}

/* CHAUFFEUR */
$stmt = $pdo->prepare("
SELECT *
FROM users
WHERE token = ?
AND role = 'chauffeur'
LIMIT 1
");
$stmt->execute([$token]);
$chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeur) {
    die("<h2>Chauffeur introuvable</h2>");
}

/* ACTION COURSE */
if (isset($_GET['course'], $_GET['action'])) {

    $course_id = intval($_GET['course']);
    $action = trim($_GET['action']);

    if ($course_id > 0) {

        if ($action === 'depart') {
            $stmt = $pdo->prepare("
            UPDATE courses
            SET statut = 'en cours',
                depart_reel = CURTIME()
            WHERE id = ?
            AND chauffeur_id = ?
            ");
            $stmt->execute([$course_id, $chauffeur['id']]);
        }

        if ($action === 'termine') {
            $stmt = $pdo->prepare("
            UPDATE courses
            SET statut = 'terminée',
                arrivee_reelle = CURTIME()
            WHERE id = ?
            AND chauffeur_id = ?
            ");
            $stmt->execute([$course_id, $chauffeur['id']]);
        }
    }

    header("Location: chauffeur_mobile.php?token=" . urlencode($token));
    exit;
}

/* COURSES ACTIVES */
$stmt = $pdo->prepare("
SELECT *
FROM courses
WHERE chauffeur_id = ?
AND statut NOT IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE')
ORDER BY date_course ASC, heure_pickup ASC
");
$stmt->execute([$chauffeur['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gps_course_id = 0;
foreach ($courses as $gps_course) {
    $gps_statut = strtolower(trim($gps_course['statut'] ?? ''));
    if ($gps_statut === 'en cours' || $gps_statut === 'en_cours') {
        $gps_course_id = (int)$gps_course['id'];
        break;
    }
}
if ($gps_course_id === 0 && !empty($courses)) {
    $gps_course_id = (int)$courses[0]['id'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">


<title>Espace Chauffeur</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:'Inter',sans-serif;
    background:#f3f4f6;
    padding:20px;
    color:#111827;
}

.top{
    background:white;
    padding:22px;
    border-radius:22px;
    margin-bottom:20px;
    box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.top h1{
    font-size:28px;
    color:#2563eb;
}

.top p{
    margin-top:8px;
    color:#6b7280;
}

.btn-son{
    width:100%;
    margin-top:15px;
    padding:15px;
    border:0;
    border-radius:14px;
    background:#dc2626;
    color:white;
    font-weight:700;
    font-size:16px;
}

.btn-son-ok{
    background:#16a34a;
}

.alert-orange{
    background:#f59e0b;
    color:white;
    padding:20px;
    border-radius:20px;
    margin-bottom:20px;
    font-weight:700;
    font-size:18px;
}

.alert-red{
    background:#dc2626;
    color:white;
    padding:20px;
    border-radius:20px;
    margin-bottom:20px;
    font-weight:700;
    font-size:18px;
}

.card{
    background:white;
    border-radius:22px;
    padding:22px;
    margin-bottom:20px;
    box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.card h2{
    font-size:20px;
    margin-bottom:15px;
}

.info{
    margin-bottom:10px;
    font-size:15px;
}

.label{
    font-weight:700;
    color:#374151;
}

.badge{
    display:inline-block;
    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    margin-top:10px;
}

.badge-prevu{
    background:#dbeafe;
    color:#1d4ed8;
}

.badge-cours{
    background:#fef3c7;
    color:#92400e;
}

.actions{
    display:flex;
    gap:10px;
    margin-top:18px;
    flex-wrap:wrap;
}

.btn{
    flex:1;
    padding:15px;
    border-radius:14px;
    text-decoration:none;
    text-align:center;
    font-weight:700;
    font-size:15px;
}

.btn-gps{
    background:#f59e0b;
    color:white;
}

.btn-start{
    background:#2563eb;
    color:white;
}

.btn-end{
    background:#16a34a;
    color:white;
}

.gps-status{margin-top:15px;padding:13px 15px;border-radius:14px;background:#e5e7eb;color:#374151;font-weight:700}.gps-status.ok{background:#dcfce7;color:#166534}.gps-status.err{background:#fee2e2;color:#991b1b}

.empty{
    background:white;
    padding:40px;
    border-radius:22px;
    text-align:center;
    color:#6b7280;
}
</style>

</head>

<body>

<div class="top">
    <h1>🚘 Bonjour <?= htmlspecialchars($chauffeur['prenom'] ?? '') ?></h1>
    <p>Planning chauffeur — mise à jour automatique toutes les 30 secondes</p>

    <button id="btnSon" class="btn-son" onclick="activerSon()">
        🔔 Activer les alertes sonores
    </button>
    <div id="gpsStatus" class="gps-status">📍 Préparation du suivi GPS…</div>
</div>

<?php
foreach($courses as $alerte_course){

    $statut_alerte = strtolower(trim($alerte_course['statut'] ?? ''));

    if ($statut_alerte === 'en cours' || $statut_alerte === 'en_cours') {
        continue;
    }

    if(empty($alerte_course['date_course']) || empty($alerte_course['heure_pickup'])){
        continue;
    }

    $heure_course = strtotime(
        $alerte_course['date_course'].' '.$alerte_course['heure_pickup']
    );

    $minutes = floor(($heure_course - time()) / 60);

    if($minutes >= 0 && $minutes <= 15){

        echo '
        <div id="alerte-course" class="alert-red">
        🚨 COURSE IMMINENTE<br>
        Patient : '.htmlspecialchars($alerte_course['client_nom'] ?? '').'<br>
        Heure : '.htmlspecialchars(substr($alerte_course['heure_pickup'] ?? '',0,5)).'<br>
        Départ dans '.$minutes.' minute(s)
        </div>';

        break;
    }

    if($minutes > 15 && $minutes <= 60){

        echo '
        <div class="alert-orange">
        🔔 COURSE DANS MOINS D\'1 HEURE<br>
        Patient : '.htmlspecialchars($alerte_course['client_nom'] ?? '').'<br>
        Heure : '.htmlspecialchars(substr($alerte_course['heure_pickup'] ?? '',0,5)).'<br>
        Départ dans '.$minutes.' minute(s)
        </div>';

        break;
    }
}
?>

<?php if(empty($courses)): ?>

<div class="empty">
Aucune course active
</div>

<?php endif; ?>

<?php foreach($courses as $course): ?>

<?php
$statut = strtolower(trim($course['statut'] ?? 'prévue'));

$badge = '<span class="badge badge-prevu">Prévue</span>';

if ($statut === 'en cours' || $statut === 'en_cours') {
    $badge = '<span class="badge badge-cours">En cours</span>';
}
?>

<div class="card">

<h2>
📅 <?= !empty($course['date_course']) ? date('d/m/Y', strtotime($course['date_course'])) : '-' ?>
-
<?= htmlspecialchars(substr($course['heure_pickup'] ?? '', 0, 5)) ?>
</h2>

<div class="info">
<span class="label">Patient :</span>
<?= htmlspecialchars($course['client_nom'] ?? '') ?>
</div>

<div class="info">
<span class="label">Départ :</span>
<?= htmlspecialchars($course['adresse_depart'] ?? '') ?>
</div>

<div class="info">
<span class="label">Arrivée :</span>
<?= htmlspecialchars($course['adresse_arrivee'] ?? '') ?>
</div>

<?= $badge ?>

<?php if(!empty($course['depart_reel'])): ?>
<div class="info">
<span class="label">Départ réel :</span>
<?= htmlspecialchars(substr($course['depart_reel'], 0, 5)) ?>
</div>
<?php endif; ?>

<div class="actions">

<a
class="btn btn-gps"
target="_blank"
href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($course['adresse_depart'] ?? '') ?>"
>
🧭 GPS
</a>

<?php if($statut !== 'en cours' && $statut !== 'en_cours'): ?>

<a
class="btn btn-start"
href="chauffeur_mobile.php?token=<?= urlencode($token) ?>&course=<?= intval($course['id']) ?>&action=depart"
>
🚗 Départ
</a>

<?php endif; ?>

<a
class="btn btn-end"
href="chauffeur_mobile.php?token=<?= urlencode($token) ?>&course=<?= intval($course['id']) ?>&action=termine"
onclick="return confirm('Marquer cette course comme terminée ?')"
>
✅ Terminée
</a>

</div>

</div>

<?php endforeach; ?>

<script>
const gpsCourseId = <?= (int)$gps_course_id ?>;
const gpsToken = <?= json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let gpsWatchId = null;

function afficherEtatGps(message, type = '') {
    const zone = document.getElementById('gpsStatus');
    if (!zone) return;
    zone.className = 'gps-status' + (type ? ' ' + type : '');
    zone.textContent = message;
}

function envoyerPosition(position) {
    if (!gpsCourseId) {
        afficherEtatGps('📍 Aucune course active à suivre.', 'err');
        return;
    }

    const data = new URLSearchParams();
    data.append('token', gpsToken);
    data.append('course_id', String(gpsCourseId));
    data.append('lat', String(position.coords.latitude));
    data.append('lng', String(position.coords.longitude));

    fetch('save_position.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
        body: data.toString(),
        cache: 'no-store'
    })
    .then(r => r.json().then(j => ({status:r.status, json:j})))
    .then(result => {
        if (!result.json.ok) throw new Error(result.json.message || 'Transmission refusée');
        afficherEtatGps('✅ GPS transmis à ' + result.json.received_at, 'ok');
    })
    .catch(error => afficherEtatGps('⚠️ GPS non transmis : ' + error.message, 'err'));
}

function erreurGps(error) {
    const messages = {
        1: 'Autorisation GPS refusée.',
        2: 'Position GPS indisponible.',
        3: 'Délai GPS dépassé.'
    };
    afficherEtatGps('⚠️ ' + (messages[error.code] || 'Erreur GPS inconnue.'), 'err');
}

function demarrerSuiviGps() {
    if (!gpsCourseId) {
        afficherEtatGps('📍 Aucune course active à suivre.', 'err');
        return;
    }
    if (!navigator.geolocation) {
        afficherEtatGps('⚠️ Ce téléphone ne prend pas en charge le GPS.', 'err');
        return;
    }
    afficherEtatGps('📍 Autorisez la localisation sur le téléphone…');
    gpsWatchId = navigator.geolocation.watchPosition(envoyerPosition, erreurGps, {
        enableHighAccuracy: true,
        maximumAge: 10000,
        timeout: 20000
    });
}

let sonAutorise = false;

const audioAlerte = new Audio(
    "https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg"
);

function activerSon(){

    audioAlerte.play()
    .then(() => {

        audioAlerte.pause();
        audioAlerte.currentTime = 0;

        sonAutorise = true;
        localStorage.setItem("medigo_son", "1");

        let btn = document.getElementById("btnSon");

        if(btn){
            btn.innerHTML = "✅ Alertes sonores activées";
            btn.classList.add("btn-son-ok");
        }

        if ("Notification" in window && Notification.permission !== "granted") {
            Notification.requestPermission();
        }

    })
    .catch(() => {
        alert("Le navigateur bloque encore le son. Touchez l'écran puis réessayez.");
    });
}

function alerteCourse(){

    let alerte = document.getElementById("alerte-course");

    if(!alerte){
        return;
    }

    try{
        navigator.vibrate([700,300,700,300,700]);
    }catch(e){}

    if(sonAutorise){
        audioAlerte.play().catch(function(){});
    }

    try{
        if("Notification" in window && Notification.permission === "granted"){
            new Notification("🚨 Course imminente", {
                body: alerte.innerText
            });
        }
    }catch(e){}
}

window.onload = function(){

    demarrerSuiviGps();

    if(localStorage.getItem("medigo_son") === "1"){

        sonAutorise = true;

        let btn = document.getElementById("btnSon");

        if(btn){
            btn.innerHTML = "✅ Alertes sonores activées";
            btn.classList.add("btn-son-ok");
        }
    }

    if("Notification" in window && Notification.permission !== "granted"){
        Notification.requestPermission();
    }

    if(document.getElementById("alerte-course")){
        setTimeout(alerteCourse, 800);
    }
};
</script>

</body>
</html>