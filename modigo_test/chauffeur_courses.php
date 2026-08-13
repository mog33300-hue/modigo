<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$chauffeur_id = intval($_SESSION['user_id']);
$societe_id = intval($_SESSION['societe_id'] ?? 0);
$role = $_SESSION['role'] ?? '';
$prenom = $_SESSION['prenom'] ?? 'Chauffeur';

if ($societe_id <= 0) {
    die("Société invalide");
}

if ($role !== 'chauffeur' && $role !== 'admin' && $role !== 'superadmin') {
    die("Accès refusé");
}

/* ACTIONS CHAUFFEUR */
if (isset($_GET['action'], $_GET['id'])) {

    $action = trim($_GET['action']);
    $course_id = intval($_GET['id']);

    if ($course_id > 0) {

        if ($action === 'depart') {
            $stmt = $pdo->prepare("
                UPDATE courses
                SET depart_reel = CURTIME(),
                    statut = 'en cours'
                WHERE id = ?
                AND chauffeur_id = ?
                AND societe_id = ?
            ");
            $stmt->execute([$course_id, $chauffeur_id, $societe_id]);
        }

        if ($action === 'terminer') {
            $stmt = $pdo->prepare("
                UPDATE courses
                SET arrivee_reelle = CURTIME(),
                    statut = 'terminée'
                WHERE id = ?
                AND chauffeur_id = ?
                AND societe_id = ?
            ");
            $stmt->execute([$course_id, $chauffeur_id, $societe_id]);
        }if ($action === 'incident') {

    $stmt = $pdo->prepare("
        UPDATE courses
        SET statut = 'incident'
        WHERE id = ?
        AND chauffeur_id = ?
        AND societe_id = ?
    ");

    $stmt->execute([
        $course_id,
        $chauffeur_id,
        $societe_id
    ]);
}
    }
if (
    $action === 'depart'
    && isset($_GET['waze'])
) {

    $stmt = $pdo->prepare("
        SELECT
            adresse_arrivee,
            ville_arrivee
        FROM courses
        WHERE id=?
        LIMIT 1
    ");

    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    $destination = urlencode(
        trim(
            ($course['adresse_arrivee'] ?? '') .
            ' ' .
            ($course['ville_arrivee'] ?? '')
        )
    );

    header(
        "Location: https://waze.com/ul?q=".$destination."&navigate=yes"
    );

    exit;
}
    header("Location: chauffeur_courses.php");
    exit;
}

/* COURSES DU CHAUFFEUR */
$stmt = $pdo->prepare("
SELECT
    c.*,
    v.plate AS vehicule,
    v.name AS vehicule_nom
FROM courses c
LEFT JOIN vehicles v ON v.id = c.vehicle_id
WHERE c.societe_id = ?
AND c.chauffeur_id = ?
AND c.statut NOT IN (
    'terminée',
    'terminee',
    'terminé',
    'termine',
    'TERMINEE',
    'TERMINE'
)
ORDER BY c.date_course ASC, c.heure_pickup ASC
");

$stmt->execute([$societe_id, $chauffeur_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($courses);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Espace chauffeur - Medigo</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:'Inter',sans-serif;
    background:#f3f4f6;
    color:#111827;
}

.topbar{
    background:white;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,.06);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
}

.topbar h1{
    color:#2563eb;
    font-size:28px;
}

.topbar p{
    color:#6b7280;
    margin-top:5px;
}

.btn-logout{
    background:#111827;
    color:white;
    padding:12px 18px;
    border-radius:12px;
    text-decoration:none;
    font-weight:600;
}

.container{
    max-width:1100px;
    margin:auto;
    padding:25px;
}

.stat-card,
.course-card,
.empty{
    background:white;
    border-radius:22px;
    box-shadow:0 5px 18px rgba(0,0,0,.05);
}

.stat-card{
    padding:25px;
    margin-bottom:25px;
}

.stat-card h2{
    font-size:42px;
    color:#2563eb;
}

.course-card{
    padding:25px;
    margin-bottom:20px;
    border-left:8px solid #16a34a;
}

.course-ok{
    border-left-color:#16a34a;
}

.course-soon{
    border-left-color:#f59e0b;
}

.course-late{
    border-left-color:#dc2626;
    animation: blink 1s infinite;
}

@keyframes blink{
    0%{box-shadow:0 0 0 rgba(220,38,38,0);}
    50%{box-shadow:0 0 25px rgba(220,38,38,.45);}
    100%{box-shadow:0 0 0 rgba(220,38,38,0);}
}

.time-alert{
    margin-top:10px;
    font-weight:800;
}

.time-ok{color:#16a34a;}
.time-soon{color:#f59e0b;}
.time-late{color:#dc2626;}

.course-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:15px;
    margin-bottom:15px;
}

.course-title{
    font-size:22px;
    font-weight:700;
}

.course-time{
    background:#dbeafe;
    color:#1d4ed8;
    padding:8px 12px;
    border-radius:999px;
    font-weight:700;
}

.info{
    margin-top:10px;
    color:#374151;
    line-height:1.7;
}

.actions{
    display:flex;
    flex-wrap:nowrap;
    gap:10px;
    overflow-x:auto;
}

.btn{
    display:inline-block;
    padding:14px 18px;
    border-radius:14px;
    text-decoration:none;
    font-weight:700;
}

.btn-start{
    background:#fef3c7;
    color:#92400e;
}

.btn-finish{
    background:#dcfce7;
    color:#166534;
}

.badge{
    display:inline-block;
    padding:7px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
}

.badge-prevue{
    background:#dbeafe;
    color:#1d4ed8;
}

.badge-cours{
    background:#fef3c7;
    color:#92400e;
}

.empty{
    padding:40px;
    text-align:center;
    color:#6b7280;
}

@media(max-width:768px){
    .topbar,
    .course-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .btn,
    .btn-logout{
        width:100%;
        text-align:center;
    }
}

.gps-panel{background:#fff;border-radius:22px;padding:20px;margin-bottom:22px;box-shadow:0 5px 18px rgba(0,0,0,.05);border-left:8px solid #64748b}.gps-panel.on{border-left-color:#16a34a}.gps-panel.error{border-left-color:#dc2626}.gps-row{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.gps-state{font-size:20px;font-weight:800}.gps-details{margin-top:10px;color:#4b5563;line-height:1.6}.service-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:15px}.service-actions button{border:0;cursor:pointer}.btn-service{background:#16a34a;color:#fff}.btn-pause{background:#f59e0b;color:#fff}.btn-stop{background:#dc2626;color:#fff}.https-warning{background:#fff7ed;color:#9a3412;padding:12px;border-radius:12px;margin-top:12px;font-weight:600}@media(max-width:768px){.service-actions .btn{width:100%}}
</style>
</head>

<body>

<div class="topbar">

<div>
<h1>🚑 Espace chauffeur</h1>
<p>Bonjour <?= htmlspecialchars($prenom) ?> — Vos courses du jour et à venir</p>
</div>

<div style="display:flex;gap:10px;flex-wrap:wrap;">
<a href="chauffeur_historique.php" class="btn-logout">📜 Mon historique</a>
<a href="logout.php" class="btn-logout">🚪 Déconnexion</a>
</div>

</div>

<div class="container">
<div id="gpsPanel" class="gps-panel">
 <div class="gps-row"><div><div id="gpsState" class="gps-state">⚪ GPS en attente</div><div id="serviceState">Service arrêté</div></div><button type="button" class="btn btn-start" id="gpsRetry">📍 Activer le GPS</button></div>
 <div class="gps-details">Dernière transmission : <strong id="lastSend">—</strong><br>Précision : <strong id="accuracy">—</strong><br>Réseau : <strong id="network">—</strong></div>
 <div class="service-actions"><button type="button" class="btn btn-service" data-service="en_service">▶ Début de service</button><button type="button" class="btn btn-pause" data-service="pause">⏸ Pause</button><button type="button" class="btn btn-stop" data-service="hors_service">■ Fin de service</button></div>
 <div id="httpsWarning" class="https-warning" hidden>La géolocalisation exige HTTPS avec un certificat valide sur Android et iPhone.</div>
</div>

<div class="stat-card">
<h2><?= intval($total) ?></h2>
<p>Course(s) active(s)</p>
</div>

<?php if(empty($courses)): ?>

<div class="empty">
Aucune course active pour le moment.
</div>

<?php endif; ?>

<?php foreach($courses as $c): ?>

<?php
$statut = strtolower(trim($c['statut'] ?? 'prévue'));

$badge = '<span class="badge badge-prevue">Prévue</span>';

if ($statut === 'en cours') {
    $badge = '<span class="badge badge-cours">En cours</span>';
}

$heure = !empty($c['heure_pickup']) ? substr($c['heure_pickup'], 0, 5) : '--:--';

$course_class = 'course-ok';
$time_alert = '🟢 Course prévue';
$time_class = 'time-ok';

if (!empty($c['date_course']) && !empty($c['heure_pickup']) && empty($c['depart_reel'])) {

    $pickup_time = strtotime($c['date_course'].' '.$c['heure_pickup']);
    $now_time = time();
    $diff_minutes = round(($pickup_time - $now_time) / 60);

    if ($diff_minutes <= 15 && $diff_minutes >= 0) {
        $course_class = 'course-soon';
        $time_alert = '🟠 Départ dans '.$diff_minutes.' min';
        $time_class = 'time-soon';
    }

    if ($diff_minutes < 0) {
        $course_class = 'course-late';
        $time_alert = '🔴 En retard de '.abs($diff_minutes).' min';
        $time_class = 'time-late';
    }
}

if (!empty($c['depart_reel'])) {
    $course_class = 'course-soon';
    $time_alert = '🟠 Course en cours';
    $time_class = 'time-soon';
}
?>

<div class="course-card <?= htmlspecialchars($course_class) ?>">

<div class="course-header">

<div>
<div class="course-title">
<?= htmlspecialchars($c['client_nom'] ?? 'Patient') ?>
</div>

<div class="info">
<?= $badge ?>

<div class="time-alert <?= htmlspecialchars($time_class) ?>">
<?= htmlspecialchars($time_alert) ?>
</div>

</div>
</div>

<div class="course-time">
<?= htmlspecialchars($heure) ?>
</div>

</div>

<div class="info">

<strong>Date :</strong>
<?= !empty($c['date_course']) ? date('d/m/Y', strtotime($c['date_course'])) : '-' ?>
<br>

<strong>Départ :</strong>
<?= htmlspecialchars($c['adresse_depart'] ?? '-') ?>
<?php if(!empty($c['ville_depart'])): ?>
, <?= htmlspecialchars($c['ville_depart']) ?>
<?php endif; ?>
<br>

<strong>Arrivée :</strong>
<?= htmlspecialchars($c['adresse_arrivee'] ?? '-') ?>
<?php if(!empty($c['ville_arrivee'])): ?>
, <?= htmlspecialchars($c['ville_arrivee']) ?>
<?php endif; ?>
<br>

<strong>Véhicule :</strong>
<?= htmlspecialchars($c['vehicule'] ?? '-') ?>

<?php if(!empty($c['vehicule_nom'])): ?>
- <?= htmlspecialchars($c['vehicule_nom']) ?>
<?php endif; ?>

<br>

<strong>Départ réel :</strong>
<?= !empty($c['depart_reel']) ? substr($c['depart_reel'],0,5) : '-' ?>
<br>

<strong>Arrivée réelle :</strong>
<?= !empty($c['arrivee_reelle']) ? substr($c['arrivee_reelle'],0,5) : '-' ?>

<?php if(!empty($c['observations'])): ?>
<br>
<strong>Observations :</strong>
<?= nl2br(htmlspecialchars($c['observations'])) ?>
<?php endif; ?>

</div>

<div class="actions">

<?php if(!empty($c['telephone'])): ?>
<a href="tel:<?= htmlspecialchars($c['telephone']) ?>" class="btn btn-finish">📞 Appeler patient</a>
<?php endif; ?>

<a href="tel:0672438682" class="btn btn-finish">☎️ Régulation</a>

<?php
$destination = trim(
    ($c['adresse_arrivee'] ?? '') . ' ' .
    ($c['ville_arrivee'] ?? '')
);

$destination_url = urlencode($destination);
?>

<a
href="https://waze.com/ul?q=<?= $destination_url ?>&navigate=yes"
target="_blank"
class="btn btn-start"
>
🧭 Waze
</a>

<a
href="https://www.google.com/maps/dir/?api=1&destination=<?= $destination_url ?>"
target="_blank"
class="btn btn-start"
>
🗺️ Maps
</a>
<?php if(empty($c['depart_reel'])): ?>

<a
href="chauffeur_courses.php?action=depart&id=<?= intval($c['id']) ?>&waze=1"
class="btn btn-start"
onclick="return confirm('Démarrer cette course ?');"
>
🚗 Démarrer
</a>
<a
href="chauffeur_courses.php?action=incident&id=<?= intval($c['id']) ?>"
class="btn btn-finish"
style="background:#fee2e2;color:#991b1b;"
onclick="return confirm('Déclarer un incident sur cette course ?');"
>
🚨 Incident
</a>

<?php endif; ?>

<?php if(!empty($c['depart_reel'])): ?>

<a
href="chauffeur_courses.php?action=terminer&id=<?= intval($c['id']) ?>"
class="btn btn-finish"
onclick="return confirm('Terminer cette course ?');"
>
✅ Terminer
</a>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>

<script>
let watchId=null,lastSent=0,currentService='hors_service',batteryLevel=null;
const panel=document.getElementById('gpsPanel'),stateEl=document.getElementById('gpsState'),lastEl=document.getElementById('lastSend'),accuracyEl=document.getElementById('accuracy'),networkEl=document.getElementById('network'),serviceEl=document.getElementById('serviceState');
function setGpsState(type,text){stateEl.textContent=text;panel.classList.remove('on','error');if(type==='on')panel.classList.add('on');if(type==='error')panel.classList.add('error');}
function networkType(){const c=navigator.connection||navigator.mozConnection||navigator.webkitConnection;return c?(c.effectiveType||c.type||'connecté'):(navigator.onLine?'connecté':'hors ligne');}
async function postData(data){
 const r=await fetch('save_position.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},credentials:'same-origin',cache:'no-store',body:JSON.stringify(data)});
 const text=await r.text();
 let j;
 try{j=JSON.parse(text);}catch(e){throw new Error('Réponse serveur invalide (HTTP '+r.status+')');}
 if(!r.ok||!j.ok)throw new Error(j.message||'Erreur serveur');
 return j;
}
async function setService(status){try{await postData({action:'service',status});currentService=status;serviceEl.textContent=status==='en_service'?'🟢 En service':status==='pause'?'🟠 En pause':'⚫ Service arrêté';if(status==='en_service')startGps();if(status==='hors_service')stopGps();}catch(e){setGpsState('error','🔴 '+e.message);}}
document.querySelectorAll('[data-service]').forEach(b=>b.addEventListener('click',()=>setService(b.dataset.service)));
async function sendPosition(p){if(currentService==='hors_service')return;const now=Date.now();if(now-lastSent<5000)return;lastSent=now;const c=p.coords;try{const j=await postData({action:'position',lat:c.latitude,lng:c.longitude,accuracy:c.accuracy,speed:c.speed===null?null:c.speed*3.6,heading:c.heading,battery:batteryLevel,network:networkType(),status:currentService});setGpsState('on','🟢 GPS connecté');lastEl.textContent=j.received_at||new Date().toLocaleTimeString();accuracyEl.textContent=Math.round(c.accuracy)+' m';networkEl.textContent=networkType();}catch(e){setGpsState('error','🔴 '+e.message);}}
function gpsError(e){let m='Position indisponible';if(e.code===1)m='Autorisation GPS refusée';if(e.code===2)m='GPS indisponible';if(e.code===3)m='Délai GPS dépassé';setGpsState('error','🔴 '+m);}
function startGps(){if(!window.isSecureContext){document.getElementById('httpsWarning').hidden=false;setGpsState('error','🔴 Connexion HTTPS requise');return;}if(!navigator.geolocation){setGpsState('error','🔴 GPS non pris en charge');return;}if(watchId!==null)navigator.geolocation.clearWatch(watchId);setGpsState('', '🟠 Recherche de la position…');watchId=navigator.geolocation.watchPosition(sendPosition,gpsError,{enableHighAccuracy:true,maximumAge:3000,timeout:20000});}
function stopGps(){if(watchId!==null){navigator.geolocation.clearWatch(watchId);watchId=null;}setGpsState('','⚪ GPS arrêté');}
document.getElementById('gpsRetry').addEventListener('click',()=>{if(currentService==='hors_service')setService('en_service');else startGps();});
window.addEventListener('online',()=>{networkEl.textContent=networkType();if(currentService!=='hors_service')startGps();});window.addEventListener('offline',()=>{networkEl.textContent='hors ligne';setGpsState('error','🔴 Réseau indisponible');});
if(navigator.getBattery){navigator.getBattery().then(b=>{const upd=()=>batteryLevel=Math.round(b.level*100);upd();b.addEventListener('levelchange',upd);}).catch(()=>{});}
networkEl.textContent=networkType();
</script></body></html>