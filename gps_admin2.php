<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

$role = (string)($_SESSION['role'] ?? '');
if (!in_array($role, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    exit('Accès refusé');
}

$societeId = (int)($_SESSION['societe_id'] ?? 0);

function gps_rows(PDO $pdo, int $societeId): array
{
    $sql = "SELECT p.*, u.prenom, u.email, u.telephone, u.vehicule
            FROM chauffeur_positions p
            LEFT JOIN users u ON u.id = p.chauffeur_id
            WHERE p.societe_id = ?
            ORDER BY p.updated_at DESC";
    $st = $pdo->prepare($sql);
    $st->execute([$societeId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function gps_payload(array $rows): array
{
    $now = time();
    $drivers = [];
    $stats = ['connected' => 0, 'in_service' => 0, 'available' => 0, 'alerts' => 0, 'total' => count($rows)];

    foreach ($rows as $r) {
        $updatedTs = !empty($r['updated_at']) ? (int)strtotime((string)$r['updated_at']) : 0;
        $age = $updatedTs > 0 ? max(0, $now - $updatedTs) : 999999;
        $lat = isset($r['latitude']) ? (float)$r['latitude'] : 0.0;
        $lng = isset($r['longitude']) ? (float)$r['longitude'] : 0.0;
        $hasPosition = ($lat !== 0.0 || $lng !== 0.0);
        $connected = $hasPosition && $age <= 90;
        $status = (string)($r['service_status'] ?? 'hors_service');
        $battery = is_numeric($r['battery_level'] ?? null) ? (int)$r['battery_level'] : null;
        $accuracy = is_numeric($r['accuracy'] ?? null) ? (float)$r['accuracy'] : null;

        if ($connected) $stats['connected']++;
        if ($status === 'en_service') $stats['in_service']++;
        if ($connected && $status === 'hors_service') $stats['available']++;

        $alerts = [];
        if (!$connected) $alerts[] = ['type' => 'danger', 'label' => 'GPS non disponible'];
        if ($battery !== null && $battery <= 20) $alerts[] = ['type' => 'warning', 'label' => 'Batterie faible'];
        if ($accuracy !== null && $accuracy > 100) $alerts[] = ['type' => 'warning', 'label' => 'Précision GPS faible'];
        $stats['alerts'] += count($alerts);

        $displayStatus = 'Hors ligne';
        $color = 'red';
        if ($connected && $status === 'en_service') { $displayStatus = 'En service'; $color = 'green'; }
        elseif ($connected && $status === 'pause') { $displayStatus = 'Pause'; $color = 'purple'; }
        elseif ($connected) { $displayStatus = 'Disponible'; $color = 'blue'; }

        $drivers[] = [
            'id' => (int)($r['chauffeur_id'] ?? 0),
            'name' => trim((string)($r['prenom'] ?? '')) ?: 'Chauffeur',
            'phone' => trim((string)($r['telephone'] ?? '')) ?: 'Non renseigné',
            'vehicle' => trim((string)($r['vehicule'] ?? '')) ?: 'Véhicule non renseigné',
            'lat' => $lat,
            'lng' => $lng,
            'hasPosition' => $hasPosition,
            'connected' => $connected,
            'status' => $displayStatus,
            'rawStatus' => $status,
            'color' => $color,
            'age' => $age,
            'updated' => (string)($r['updated_at'] ?? ''),
            'speed' => is_numeric($r['speed'] ?? null) ? round((float)$r['speed']) : null,
            'heading' => is_numeric($r['heading'] ?? null) ? (float)$r['heading'] : null,
            'accuracy' => $accuracy !== null ? round($accuracy) : null,
            'battery' => $battery,
            'network' => trim((string)($r['network_type'] ?? '')) ?: 'Non renseigné',
            'alerts' => $alerts,
        ];
    }

    return ['ok' => true, 'generatedAt' => date('H:i:s'), 'stats' => $stats, 'drivers' => $drivers];
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    try {
        echo json_encode(gps_payload(gps_rows($pdo, $societeId)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$initial = ['ok' => true, 'generatedAt' => date('H:i:s'), 'stats' => ['connected'=>0,'in_service'=>0,'available'=>0,'alerts'=>0,'total'=>0], 'drivers' => []];
$gpsError = '';
try { $initial = gps_payload(gps_rows($pdo, $societeId)); }
catch (Throwable $e) { $gpsError = $e->getMessage(); }

$page_title = 'MODIGO - Centre de supervision';
$modigo_extra_head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
if (is_file(__DIR__ . '/includes/header.php')) {
    include __DIR__ . '/includes/header.php';
    if (is_file(__DIR__ . '/includes/menu.php')) include __DIR__ . '/includes/menu.php';
} else {
    ?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($page_title) ?></title><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"></head><body><?php
    if (is_file(__DIR__ . '/menu.php')) include __DIR__ . '/menu.php';
}
?>
<style>
:root{--bg:#06111f;--panel:#0b1b2d;--panel2:#10243a;--line:#1d3852;--text:#f5f8fc;--muted:#91a4ba;--blue:#2583ff;--green:#22c55e;--amber:#f59e0b;--red:#ef4444;--purple:#9b5de5}
*{box-sizing:border-box}.supervision-shell{min-height:calc(100vh - 70px);background:linear-gradient(145deg,#06111f,#081728);color:var(--text);padding:14px;font-family:Arial,sans-serif}.sup-header{display:flex;align-items:center;gap:16px;margin-bottom:12px}.sup-brand{min-width:240px}.sup-brand h1{font-size:23px;margin:0}.sup-brand small{color:#54a3ff;font-weight:700}.sup-kpis{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:10px;flex:1}.kpi{background:linear-gradient(145deg,var(--panel),var(--panel2));border:1px solid var(--line);border-radius:13px;padding:12px 14px}.kpi-label{font-size:11px;color:var(--muted);text-transform:uppercase}.kpi-value{font-size:25px;font-weight:800;margin-top:5px}.kpi small{color:var(--muted)}.sup-main{display:grid;grid-template-columns:270px minmax(420px,1fr) 300px;gap:12px;height:calc(100vh - 190px);min-height:610px}.panel{background:rgba(9,25,42,.97);border:1px solid var(--line);border-radius:13px;overflow:hidden}.panel-title{padding:14px;border-bottom:1px solid var(--line);font-size:13px;font-weight:800;text-transform:uppercase}.driver-tools{padding:10px}.driver-tools input{width:100%;background:#071523;border:1px solid var(--line);color:white;border-radius:8px;padding:10px}.driver-list{height:calc(100% - 96px);overflow:auto;padding:0 8px 10px}.driver-item{display:block;width:100%;text-align:left;background:#0b1d30;border:1px solid #18334d;border-radius:10px;padding:11px;margin:7px 0;color:white;cursor:pointer}.driver-item:hover,.driver-item.active{border-color:#2a8cff;background:#102945}.driver-line{display:flex;justify-content:space-between;gap:6px}.driver-name{font-weight:800}.driver-meta{font-size:12px;color:var(--muted);margin-top:4px}.status{display:inline-flex;align-items:center;gap:5px;font-size:11px;margin-top:7px}.dot{width:8px;height:8px;border-radius:50%}.green{background:var(--green)}.blue{background:var(--blue)}.red{background:var(--red)}.purple{background:var(--purple)}.map-panel{position:relative}.map-wrap,#map{width:100%;height:100%}.sync-badge{position:absolute;z-index:500;top:10px;right:10px;background:#071523dd;border:1px solid var(--line);padding:8px 11px;border-radius:9px;font-size:12px}.detail-content{padding:16px;overflow:auto;height:calc(100% - 47px)}.driver-hero{display:flex;gap:12px;align-items:center;padding-bottom:15px;border-bottom:1px solid var(--line)}.vehicle-icon{width:56px;height:56px;border-radius:50%;display:grid;place-items:center;font-size:27px;background:#173653}.detail-row{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-bottom:1px solid #17314a;font-size:13px}.detail-row span:first-child{color:var(--muted)}.center-btn{width:100%;border:0;background:#1769d2;color:white;border-radius:8px;padding:12px;font-weight:700;margin-top:15px;cursor:pointer}.alerts-bar{margin-top:12px;background:var(--panel);border:1px solid var(--line);border-radius:13px;padding:10px 14px;display:flex;gap:10px;align-items:center;min-height:58px}.alert-chip{padding:8px 11px;border-radius:8px;font-size:12px;background:#2a1820;border:1px solid #73303c}.empty{padding:20px;color:var(--muted);text-align:center}.leaflet-popup-content-wrapper,.leaflet-popup-tip{background:#0b1b2d;color:white}.vehicle-marker{display:grid;place-items:center;width:38px;height:38px;border:3px solid white;border-radius:50%;box-shadow:0 4px 12px #0008;font-size:19px}.marker-green{background:var(--green)}.marker-blue{background:var(--blue)}.marker-red{background:var(--red)}.marker-purple{background:var(--purple)}
@media(max-width:1100px){.sup-main{grid-template-columns:240px 1fr}.detail-panel{display:none}.sup-kpis{grid-template-columns:repeat(2,1fr)}.sup-main{height:auto}.map-panel{height:620px}.driver-panel{height:620px}}
@media(max-width:720px){.sup-header{display:block}.sup-brand{margin-bottom:10px}.sup-kpis{grid-template-columns:1fr 1fr}.sup-main{display:block}.driver-panel{height:330px;margin-bottom:10px}.map-panel{height:500px}.alerts-bar{display:block}.alert-chip{display:block;margin:6px 0}}
</style>

<div class="supervision-shell">
  <header class="sup-header">
    <div class="sup-brand"><h1>🚑 MODIGO</h1><small>Centre de supervision</small></div>
    <div class="sup-kpis">
      <div class="kpi"><div class="kpi-label">Chauffeurs connectés</div><div class="kpi-value" id="kpiConnected">0</div><small id="kpiConnectedSub">sur 0</small></div>
      <div class="kpi"><div class="kpi-label">En service</div><div class="kpi-value" id="kpiService">0</div><small>en temps réel</small></div>
      <div class="kpi"><div class="kpi-label">Disponibles</div><div class="kpi-value" id="kpiAvailable">0</div><small>GPS actif</small></div>
      <div class="kpi"><div class="kpi-label">Dernière synchro</div><div class="kpi-value" id="kpiSync">--:--:--</div><small>actualisation 5 secondes</small></div>
    </div>
  </header>

  <?php if ($gpsError !== ''): ?><div class="alert-chip">Erreur GPS : <?= htmlspecialchars($gpsError) ?></div><?php endif; ?>

  <main class="sup-main">
    <section class="panel driver-panel"><div class="panel-title">Liste des chauffeurs</div><div class="driver-tools"><input id="driverSearch" type="search" placeholder="Rechercher un chauffeur..."></div><div id="driverList" class="driver-list"></div></section>
    <section class="panel map-panel"><div id="map"></div><div class="sync-badge" id="syncBadge">Synchronisation...</div></section>
    <aside class="panel detail-panel"><div class="panel-title">Détails du chauffeur</div><div id="driverDetail" class="detail-content"><div class="empty">Sélectionnez un chauffeur</div></div></aside>
  </main>
  <section class="alerts-bar"><strong>⚠️ Alertes en cours</strong><div id="alertsList" style="display:flex;gap:8px;flex-wrap:wrap"></div></section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const initialData = <?= json_encode($initial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const map = L.map('map', {zoomControl:true}).setView([44.8378,-0.5792],11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);
let currentData = initialData, markers = new Map(), selectedId = null, firstFit = true;
const esc = s => String(s ?? '').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
const ageText = s => s < 60 ? `il y a ${s} s` : s < 3600 ? `il y a ${Math.floor(s/60)} min` : `il y a ${Math.floor(s/3600)} h`;
function markerIcon(d){return L.divIcon({className:'',html:`<div class="vehicle-marker marker-${d.color}">🚑</div>`,iconSize:[38,38],iconAnchor:[19,19]});}
function render(data){
 currentData=data; const s=data.stats;
 document.getElementById('kpiConnected').textContent=s.connected;
 document.getElementById('kpiConnectedSub').textContent=`sur ${s.total}`;
 document.getElementById('kpiService').textContent=s.in_service;
 document.getElementById('kpiAvailable').textContent=s.available;
 document.getElementById('kpiSync').textContent=data.generatedAt;
 document.getElementById('syncBadge').textContent=`● Mise à jour ${data.generatedAt}`;
 const q=document.getElementById('driverSearch').value.toLowerCase().trim();
 const visible=data.drivers.filter(d=>!q||d.name.toLowerCase().includes(q)||d.vehicle.toLowerCase().includes(q));
 document.getElementById('driverList').innerHTML=visible.length?visible.map(d=>`<button class="driver-item ${selectedId===d.id?'active':''}" onclick="selectDriver(${d.id})"><div class="driver-line"><span class="driver-name">${esc(d.name)}</span><span>${d.battery===null?'--':d.battery+'%'}</span></div><div class="driver-meta">${esc(d.vehicle)}</div><div class="status"><i class="dot ${d.color}"></i>${esc(d.status)} · ${ageText(d.age)}</div></button>`).join(''):'<div class="empty">Aucun chauffeur trouvé</div>';
 const ids=new Set(); const bounds=[];
 data.drivers.forEach(d=>{if(!d.hasPosition)return;ids.add(d.id);bounds.push([d.lat,d.lng]);const popup=`<b>${esc(d.name)}</b><br>${esc(d.vehicle)}<br>${esc(d.status)}<br>${ageText(d.age)}`;if(markers.has(d.id)){markers.get(d.id).setLatLng([d.lat,d.lng]).setIcon(markerIcon(d)).setPopupContent(popup);}else{markers.set(d.id,L.marker([d.lat,d.lng],{icon:markerIcon(d)}).addTo(map).bindPopup(popup).on('click',()=>selectDriver(d.id)));}});
 markers.forEach((m,id)=>{if(!ids.has(id)){map.removeLayer(m);markers.delete(id);}});
 if(firstFit&&bounds.length){map.fitBounds(bounds,{padding:[40,40],maxZoom:15});firstFit=false;}
 const alerts=[];data.drivers.forEach(d=>d.alerts.forEach(a=>alerts.push(`<span class="alert-chip">${esc(a.label)} · ${esc(d.name)}</span>`)));
 document.getElementById('alertsList').innerHTML=alerts.length?alerts.join(''):'<span style="color:#91a4ba">Aucune alerte active</span>';
 if(selectedId) showDetail(selectedId);
}
function showDetail(id){const d=currentData.drivers.find(x=>x.id===id);if(!d)return;document.getElementById('driverDetail').innerHTML=`<div class="driver-hero"><div class="vehicle-icon">🚑</div><div><h2 style="margin:0 0 4px">${esc(d.name)}</h2><div style="color:#91a4ba">${esc(d.vehicle)}</div><div class="status"><i class="dot ${d.color}"></i>${esc(d.status)}</div></div></div><div class="detail-row"><span>Téléphone</span><b>${esc(d.phone)}</b></div><div class="detail-row"><span>Batterie</span><b>${d.battery===null?'--':d.battery+' %'}</b></div><div class="detail-row"><span>Qualité GPS</span><b>${d.accuracy===null?'--':d.accuracy+' m'}</b></div><div class="detail-row"><span>Vitesse</span><b>${d.speed===null?'--':d.speed+' km/h'}</b></div><div class="detail-row"><span>Réseau</span><b>${esc(d.network)}</b></div><div class="detail-row"><span>Dernière transmission</span><b>${ageText(d.age)}</b></div><button class="center-btn" onclick="centerDriver(${d.id})">⊙ Centrer sur la carte</button>`;}
function selectDriver(id){selectedId=id;showDetail(id);render(currentData);centerDriver(id);}
function centerDriver(id){const d=currentData.drivers.find(x=>x.id===id);if(d&&d.hasPosition){map.setView([d.lat,d.lng],16);markers.get(id)?.openPopup();}}
async function refresh(){try{const r=await fetch('gps_admin.php?ajax=1',{cache:'no-store'});const j=await r.json();if(!j.ok)throw new Error(j.message||'Erreur');render(j);}catch(e){document.getElementById('syncBadge').textContent='⚠ Synchronisation impossible';}}
document.getElementById('driverSearch').addEventListener('input',()=>render(currentData));render(initialData);setInterval(refresh,5000);
</script>
<?php
if (is_file(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php';
else echo '</body></html>';
?>
