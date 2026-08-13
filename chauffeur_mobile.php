<?php
require 'config.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    die('<h2>Token invalide</h2>');
}

/* CHAUFFEUR */
$stmt = $pdo->prepare("SELECT * FROM users WHERE token = ? AND role = 'chauffeur' LIMIT 1");
$stmt->execute([$token]);
$chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeur) {
    die('<h2>Chauffeur introuvable</h2>');
}

/* ACTION COURSE */
if (isset($_GET['course'], $_GET['action'])) {
    $course_id = (int)($_GET['course'] ?? 0);
    $action = trim((string)($_GET['action'] ?? ''));

    if ($course_id > 0) {
        if ($action === 'arrive') {
            $stmt = $pdo->prepare("UPDATE courses SET statut = 'arrivé sur place' WHERE id = ? AND chauffeur_id = ?");
            $stmt->execute([$course_id, $chauffeur['id']]);
        }

        if ($action === 'bord') {
            $stmt = $pdo->prepare("UPDATE courses SET statut = 'patient à bord' WHERE id = ? AND chauffeur_id = ?");
            $stmt->execute([$course_id, $chauffeur['id']]);
        }

        if ($action === 'depart') {
            $stmt = $pdo->prepare("UPDATE courses SET statut = 'en cours', depart_reel = COALESCE(depart_reel, CURTIME()) WHERE id = ? AND chauffeur_id = ?");
            $stmt->execute([$course_id, $chauffeur['id']]);
        }

        if ($action === 'termine') {
            $stmt = $pdo->prepare("UPDATE courses SET statut = 'terminée', arrivee_reelle = CURTIME() WHERE id = ? AND chauffeur_id = ?");
            $stmt->execute([$course_id, $chauffeur['id']]);
        }
    }

    header('Location: chauffeur_mobile.php?token=' . urlencode($token));
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
    $gps_statut = mb_strtolower(trim((string)($gps_course['statut'] ?? '')));
    if (in_array($gps_statut, ['en cours', 'en_cours', 'patient à bord', 'patient a bord'], true)) {
        $gps_course_id = (int)$gps_course['id'];
        break;
    }
}
if ($gps_course_id === 0 && !empty($courses)) {
    $gps_course_id = (int)$courses[0]['id'];
}

$prenom = trim((string)($chauffeur['prenom'] ?? ''));
$nom = trim((string)($chauffeur['nom'] ?? ''));
$nom_chauffeur = trim($prenom . ' ' . $nom);
if ($nom_chauffeur === '') {
    $nom_chauffeur = 'Chauffeur';
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statut_normalise(string $statut): string {
    return mb_strtolower(trim($statut));
}

function badge_statut(string $statut): array {
    $s = statut_normalise($statut);
    if (in_array($s, ['en cours', 'en_cours'], true)) return ['En route', 'route'];
    if (in_array($s, ['arrivé sur place', 'arrive sur place'], true)) return ['Arrivé sur place', 'arrive'];
    if (in_array($s, ['patient à bord', 'patient a bord'], true)) return ['Patient à bord', 'bord'];
    return ['Prévue', 'prevue'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#063b68">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="MODIGO Chauffeur">
<title>MODIGO Chauffeur</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --navy:#063b68;
    --blue:#0b73d1;
    --blue-soft:#e8f3ff;
    --green:#1aa36f;
    --green-soft:#e6f7f0;
    --orange:#f59e0b;
    --orange-soft:#fff4dc;
    --red:#dc3545;
    --ink:#102033;
    --muted:#6b7b8d;
    --line:#e6edf4;
    --surface:#ffffff;
    --bg:#f3f7fb;
}
*{box-sizing:border-box;margin:0;padding:0}
html{background:var(--bg)}
body{
    min-height:100vh;
    font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    color:var(--ink);
    background:var(--bg);
    padding-bottom:calc(92px + env(safe-area-inset-bottom));
}
a{color:inherit}
.app-shell{max-width:720px;margin:0 auto;min-height:100vh}
.header{
    position:sticky;top:0;z-index:20;
    background:rgba(255,255,255,.96);
    backdrop-filter:blur(16px);
    border-bottom:1px solid var(--line);
    padding:14px 18px 12px;
}
.header-row{display:flex;align-items:center;justify-content:space-between;gap:12px}
.brand{display:flex;align-items:center;gap:11px}
.brand-mark{
    width:43px;height:43px;border-radius:14px;
    display:grid;place-items:center;
    background:linear-gradient(145deg,var(--navy),var(--blue));
    color:#fff;font-weight:800;font-size:18px;
    box-shadow:0 7px 18px rgba(6,59,104,.22)
}
.brand-title{font-weight:800;font-size:18px;letter-spacing:-.4px;color:var(--navy)}
.brand-title span{color:var(--green)}
.brand-sub{font-size:11px;color:var(--muted);margin-top:2px;font-weight:600}
.online{
    display:flex;align-items:center;gap:7px;
    background:var(--green-soft);color:#117755;
    border:1px solid #bde8d6;border-radius:999px;
    padding:8px 11px;font-size:12px;font-weight:800;white-space:nowrap
}
.online-dot{width:8px;height:8px;border-radius:50%;background:var(--green);box-shadow:0 0 0 4px rgba(26,163,111,.13)}
.content{padding:18px}
.welcome{margin-bottom:16px}
.welcome h1{font-size:25px;line-height:1.2;letter-spacing:-.7px}
.welcome p{margin-top:6px;color:var(--muted);font-size:14px}
.status-grid{display:grid;grid-template-columns:1fr 1fr;gap:11px;margin-bottom:16px}
.status-card{
    background:var(--surface);border:1px solid var(--line);border-radius:18px;
    padding:14px;box-shadow:0 7px 20px rgba(30,60,90,.05)
}
.status-card small{display:block;color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.status-value{display:flex;align-items:center;gap:8px;margin-top:8px;font-weight:800;font-size:14px}
.status-icon{width:30px;height:30px;border-radius:10px;display:grid;place-items:center;background:var(--green-soft)}
.gps-status{
    margin-bottom:16px;padding:13px 14px;border-radius:15px;
    background:#edf2f7;color:#425466;border:1px solid #dfe7ef;font-size:13px;font-weight:700
}
.gps-status.ok{background:var(--green-soft);color:#116b4c;border-color:#bde8d6}
.gps-status.err{background:#fff0f1;color:#9f2330;border-color:#ffd0d5}
.alert{
    margin-bottom:16px;border-radius:18px;padding:16px 17px;color:#fff;
    font-weight:800;line-height:1.45;box-shadow:0 8px 22px rgba(0,0,0,.08)
}
.alert.orange{background:linear-gradient(135deg,#f59e0b,#f97316)}
.alert.red{background:linear-gradient(135deg,#dc3545,#b91c1c)}
.section-title{font-size:13px;text-transform:uppercase;letter-spacing:.75px;color:var(--muted);font-weight:800;margin:4px 2px 10px}
.mission-card{
    background:var(--surface);border:1px solid var(--line);border-radius:24px;
    overflow:hidden;margin-bottom:16px;box-shadow:0 12px 32px rgba(28,58,88,.08)
}
.mission-card.primary{border:2px solid #cfe6fb;box-shadow:0 15px 36px rgba(11,115,209,.12)}
.mission-head{padding:17px 18px 14px;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;background:linear-gradient(180deg,#fff,#fbfdff)}
.mission-date{font-size:12px;color:var(--muted);font-weight:700}
.mission-time{font-size:27px;font-weight:800;color:var(--navy);margin-top:3px;letter-spacing:-1px}
.badge{padding:8px 11px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}
.badge.prevue{background:var(--blue-soft);color:#075fae}
.badge.arrive{background:#fff3cd;color:#8a6200}
.badge.bord{background:#fff0df;color:#a14c00}
.badge.route{background:var(--green-soft);color:#116b4c}
.mission-body{padding:2px 18px 18px}
.patient{display:flex;align-items:center;gap:11px;padding:14px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.avatar{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:var(--blue-soft);font-size:20px}
.patient small{font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase}
.patient strong{display:block;margin-top:3px;font-size:16px}
.route{padding:15px 0 4px}
.route-row{display:grid;grid-template-columns:24px 1fr;gap:10px;position:relative;padding-bottom:15px}
.route-row:not(:last-child):after{content:'';position:absolute;left:11px;top:23px;width:2px;height:calc(100% - 8px);background:#d9e5ef}
.route-dot{width:24px;height:24px;border-radius:8px;display:grid;place-items:center;font-size:12px;background:var(--blue-soft);color:var(--blue);font-weight:800;z-index:1}
.route-row:last-child .route-dot{background:var(--green-soft);color:var(--green)}
.route-label{font-size:11px;text-transform:uppercase;color:var(--muted);font-weight:800;letter-spacing:.4px}
.route-address{font-size:14px;font-weight:700;line-height:1.4;margin-top:3px}
.action-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
.btn{
    min-height:50px;border:0;border-radius:15px;padding:12px 10px;
    display:flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;
    font-family:inherit;font-size:13px;font-weight:800;cursor:pointer;text-align:center
}
.btn.nav{background:var(--navy);color:#fff;grid-column:1/-1}
.btn.arrive{background:var(--blue-soft);color:#075fae}
.btn.bord{background:var(--orange-soft);color:#9a5900}
.btn.start{background:var(--green);color:#fff}
.btn.end{background:#edf2f7;color:#44566c}
.btn.end.ready{background:#daf5e9;color:#0f704c}
.real-time{margin-top:12px;padding:10px 12px;border-radius:13px;background:#f7fafc;color:var(--muted);font-size:12px;font-weight:700}
.empty{
    background:#fff;border:1px solid var(--line);border-radius:24px;padding:42px 24px;
    text-align:center;box-shadow:0 10px 28px rgba(30,60,90,.06)
}
.empty-icon{font-size:42px;margin-bottom:12px}.empty h2{font-size:19px}.empty p{color:var(--muted);font-size:14px;margin-top:7px;line-height:1.5}
.sound-btn{
    width:100%;margin-top:12px;border:0;border-radius:14px;padding:13px 15px;
    background:#fff0f1;color:#b42335;font-family:inherit;font-weight:800;font-size:13px
}
.sound-btn.ok{background:var(--green-soft);color:#116b4c}
.bottom-nav{
    position:fixed;left:50%;bottom:0;transform:translateX(-50%);z-index:30;
    width:min(720px,100%);background:rgba(255,255,255,.97);backdrop-filter:blur(16px);
    border-top:1px solid var(--line);padding:9px 18px calc(9px + env(safe-area-inset-bottom));
    display:grid;grid-template-columns:repeat(3,1fr);gap:8px
}
.nav-item{text-decoration:none;text-align:center;color:var(--muted);font-size:10px;font-weight:800;padding:5px 2px;border-radius:12px}
.nav-item span{display:block;font-size:20px;margin-bottom:3px}.nav-item.active{color:var(--blue);background:var(--blue-soft)}
@media (min-width:620px){.content{padding:24px}.action-grid{grid-template-columns:repeat(4,1fr)}.btn.nav{grid-column:auto}.welcome h1{font-size:30px}}
</style>
</head>
<body>
<div class="app-shell">
    <header class="header">
        <div class="header-row">
            <div class="brand">
                <div class="brand-mark">M</div>
                <div>
                    <div class="brand-title">MODIGO <span>Chauffeur</span></div>
                    <div class="brand-sub">Transport sanitaire intelligent</div>
                </div>
            </div>
            <div class="online"><span class="online-dot"></span> En ligne</div>
        </div>
    </header>

    <main class="content">
        <section class="welcome">
            <h1>Bonjour <?= e($prenom !== '' ? $prenom : $nom_chauffeur) ?> 👋</h1>
            <p><?= e(date('d/m/Y')) ?> · Vos missions du jour</p>
        </section>

        <section class="status-grid">
            <div class="status-card">
                <small>Service</small>
                <div class="status-value"><span class="status-icon">🟢</span> En service</div>
            </div>
            <div class="status-card">
                <small>Missions actives</small>
                <div class="status-value"><span class="status-icon">🚘</span> <?= count($courses) ?> course<?= count($courses) > 1 ? 's' : '' ?></div>
            </div>
        </section>

        <div id="gpsStatus" class="gps-status">📍 Préparation du suivi GPS…</div>

        <button id="btnSon" class="sound-btn" type="button" onclick="activerSon()">🔔 Activer les alertes sonores</button>

        <?php foreach ($courses as $alerte_course):
            $statut_alerte = statut_normalise((string)($alerte_course['statut'] ?? ''));
            if (in_array($statut_alerte, ['en cours','en_cours'], true)) continue;
            if (empty($alerte_course['date_course']) || empty($alerte_course['heure_pickup'])) continue;
            $heure_course = strtotime($alerte_course['date_course'].' '.$alerte_course['heure_pickup']);
            $minutes = (int)floor(($heure_course - time()) / 60);
        ?>
            <?php if ($minutes >= 0 && $minutes <= 15): ?>
                <div id="alerte-course" class="alert red">🚨 COURSE IMMINENTE<br>Patient : <?= e($alerte_course['client_nom'] ?? '') ?><br>Départ dans <?= $minutes ?> minute<?= $minutes > 1 ? 's' : '' ?></div>
                <?php break; ?>
            <?php elseif ($minutes > 15 && $minutes <= 60): ?>
                <div class="alert orange">🔔 COURSE DANS MOINS D'UNE HEURE<br>Patient : <?= e($alerte_course['client_nom'] ?? '') ?><br>Départ dans <?= $minutes ?> minutes</div>
                <?php break; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="section-title"><?= empty($courses) ? 'Planning' : 'Prochaine mission' ?></div>

        <?php if (empty($courses)): ?>
            <section class="empty">
                <div class="empty-icon">✅</div>
                <h2>Aucune mission active</h2>
                <p>Vous serez averti dès qu'une nouvelle course vous sera attribuée.</p>
            </section>
        <?php endif; ?>

        <?php foreach ($courses as $index => $course):
            $statut = statut_normalise((string)($course['statut'] ?? 'prévue'));
            [$badge_label, $badge_class] = badge_statut($statut);
            $gps_destination = $course['adresse_depart'] ?? '';
            if (in_array($statut, ['en cours','en_cours','patient à bord','patient a bord'], true)) {
                $gps_destination = $course['adresse_arrivee'] ?? $gps_destination;
            }
        ?>
            <article class="mission-card <?= $index === 0 ? 'primary' : '' ?>">
                <div class="mission-head">
                    <div>
                        <div class="mission-date"><?= !empty($course['date_course']) ? e(date('d/m/Y', strtotime($course['date_course']))) : 'Date non renseignée' ?></div>
                        <div class="mission-time"><?= e(substr((string)($course['heure_pickup'] ?? '--:--'), 0, 5)) ?></div>
                    </div>
                    <span class="badge <?= e($badge_class) ?>"><?= e($badge_label) ?></span>
                </div>

                <div class="mission-body">
                    <div class="patient">
                        <div class="avatar">👤</div>
                        <div>
                            <small>Patient</small>
                            <strong><?= e($course['client_nom'] ?? 'Non renseigné') ?></strong>
                        </div>
                    </div>

                    <div class="route">
                        <div class="route-row">
                            <div class="route-dot">A</div>
                            <div><div class="route-label">Départ</div><div class="route-address"><?= e($course['adresse_depart'] ?? 'Adresse non renseignée') ?></div></div>
                        </div>
                        <div class="route-row">
                            <div class="route-dot">B</div>
                            <div><div class="route-label">Destination</div><div class="route-address"><?= e($course['adresse_arrivee'] ?? 'Adresse non renseignée') ?></div></div>
                        </div>
                    </div>

                    <div class="action-grid">
                        <a class="btn nav" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= urlencode((string)$gps_destination) ?>">🧭 Ouvrir le GPS</a>

                        <?php if (!in_array($statut, ['arrivé sur place','arrive sur place','patient à bord','patient a bord','en cours','en_cours'], true)): ?>
                            <a class="btn arrive" href="chauffeur_mobile.php?token=<?= urlencode($token) ?>&course=<?= (int)$course['id'] ?>&action=arrive">📍 Je suis arrivé</a>
                        <?php endif; ?>

                        <?php if (in_array($statut, ['arrivé sur place','arrive sur place'], true)): ?>
                            <a class="btn bord" href="chauffeur_mobile.php?token=<?= urlencode($token) ?>&course=<?= (int)$course['id'] ?>&action=bord">👤 Patient à bord</a>
                        <?php endif; ?>

                        <?php if (in_array($statut, ['patient à bord','patient a bord'], true)): ?>
                            <a class="btn start" href="chauffeur_mobile.php?token=<?= urlencode($token) ?>&course=<?= (int)$course['id'] ?>&action=depart">🚗 Démarrer</a>
                        <?php endif; ?>

                        <?php if (in_array($statut, ['en cours','en_cours'], true)): ?>
                            <a class="btn end ready" href="chauffeur_mobile.php?token=<?= urlencode($token) ?>&course=<?= (int)$course['id'] ?>&action=termine" onclick="return confirm('Confirmer la fin de cette mission ?')">✅ Mission terminée</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($course['depart_reel'])): ?>
                        <div class="real-time">Départ réel enregistré à <?= e(substr((string)$course['depart_reel'], 0, 5)) ?></div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </main>
</div>

<nav class="bottom-nav" aria-label="Navigation chauffeur">
    <a class="nav-item active" href="#"><span>🏠</span>Accueil</a>
    <a class="nav-item" href="#gpsStatus"><span>📍</span>GPS</a>
    <a class="nav-item" href="javascript:location.reload()"><span>🔄</span>Actualiser</a>
</nav>

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
        headers: {'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
        body: data.toString(),
        cache: 'no-store'
    })
    .then(r => r.json().then(j => ({status:r.status, json:j})))
    .then(result => {
        if (!result.json.ok) throw new Error(result.json.message || 'Transmission refusée');
        afficherEtatGps('✅ GPS actif · dernière transmission ' + result.json.received_at, 'ok');
    })
    .catch(error => afficherEtatGps('⚠️ GPS non transmis : ' + error.message, 'err'));
}

function erreurGps(error) {
    const messages = {1:'Autorisation GPS refusée.',2:'Position GPS indisponible.',3:'Délai GPS dépassé.'};
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
        enableHighAccuracy:true,
        maximumAge:10000,
        timeout:20000
    });
}

let sonAutorise = false;
const audioAlerte = new Audio('https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg');

function activerSon() {
    audioAlerte.play().then(() => {
        audioAlerte.pause();
        audioAlerte.currentTime = 0;
        sonAutorise = true;
        localStorage.setItem('modigo_son', '1');
        const btn = document.getElementById('btnSon');
        if (btn) {
            btn.innerHTML = '✅ Alertes sonores activées';
            btn.classList.add('ok');
        }
        if ('Notification' in window && Notification.permission !== 'granted') {
            Notification.requestPermission();
        }
    }).catch(() => alert('Le navigateur bloque encore le son. Touchez l’écran puis réessayez.'));
}

function alerteCourse() {
    const alerte = document.getElementById('alerte-course');
    if (!alerte) return;
    try { navigator.vibrate([700,300,700,300,700]); } catch(e) {}
    if (sonAutorise) audioAlerte.play().catch(() => {});
    try {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('🚨 Course imminente', {body:alerte.innerText});
        }
    } catch(e) {}
}

window.addEventListener('load', () => {
    demarrerSuiviGps();
    if (localStorage.getItem('modigo_son') === '1') {
        sonAutorise = true;
        const btn = document.getElementById('btnSon');
        if (btn) {
            btn.innerHTML = '✅ Alertes sonores activées';
            btn.classList.add('ok');
        }
    }
    if (document.getElementById('alerte-course')) setTimeout(alerteCourse, 800);
});
</script>
</body>
</html>
