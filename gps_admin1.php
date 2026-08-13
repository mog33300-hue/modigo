<?php
require 'auth.php';
require 'config.php';

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'superadmin'], true)) {
    die('Accès refusé');
}

$societeId = (int)($_SESSION['societe_id'] ?? 0);
$rows = [];
$gpsError = '';

$sql = "
    SELECT
        p.*,
        u.prenom,
        u.email,
        u.telephone,
        u.vehicule
    FROM chauffeur_positions p
    LEFT JOIN users u ON u.id = p.chauffeur_id
    WHERE p.societe_id = ?
    ORDER BY p.updated_at DESC
";

try {
    $st = $pdo->prepare($sql);
    $st->execute([$societeId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $gpsError = $e->getMessage();
}

$now = time();
$online = 0;
$service = 0;
$stale = 0;
$markers = [];

foreach ($rows as $r) {
    $updatedTs = !empty($r['updated_at']) ? strtotime($r['updated_at']) : 0;
    $age = $updatedTs > 0 ? max(0, $now - $updatedTs) : 999999;
    $lat = (float)($r['latitude'] ?? 0);
    $lng = (float)($r['longitude'] ?? 0);
    $valid = ($lat !== 0.0 || $lng !== 0.0);
    $fresh = $age <= 60;

    if ($fresh && $valid) {
        $online++;
    }
    if (($r['service_status'] ?? '') === 'en_service') {
        $service++;
    }
    if (!$fresh && $valid) {
        $stale++;
    }

    if ($valid) {
        $markers[] = [
            'lat' => $lat,
            'lng' => $lng,
            'name' => trim((string)($r['prenom'] ?? '')) ?: 'Chauffeur',
            'vehicle' => trim((string)($r['vehicule'] ?? '')) ?: 'Véhicule non renseigné',
            'status' => (string)($r['service_status'] ?? '-'),
            'age' => $age,
            'updated' => (string)($r['updated_at'] ?? ''),
            'speed' => $r['speed'] ?? null,
            'accuracy' => $r['accuracy'] ?? null,
            'battery' => $r['battery_level'] ?? null,
            'network' => $r['network_type'] ?? null,
            'fresh' => $fresh,
        ];
    }
}

$page_title = 'MODIGO - Centre GPS';
$modigo_extra_head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';
?>
<style>
.gps-wrap{padding:22px}.gps-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}.gps-stat,.driver-card{background:#fff;border-radius:16px;padding:18px;box-shadow:0 4px 16px #0001}.gps-stat strong{font-size:30px;display:block;margin-top:6px}.gps-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(300px,1fr);gap:18px}#map{height:650px;border-radius:18px}.drivers{max-height:650px;overflow:auto}.driver-card{margin-bottom:12px;border-left:6px solid #64748b}.driver-card.fresh{border-left-color:#16a34a}.driver-card.stale{border-left-color:#dc2626}.driver-card h3{margin:0 0 8px}.muted{color:#64748b;line-height:1.55}.warn{background:#fee2e2;color:#991b1b;padding:16px;border-radius:14px;margin-bottom:15px}.ok{background:#dcfce7;color:#166534;padding:12px 16px;border-radius:14px;margin-bottom:15px}@media(max-width:900px){.gps-stats{grid-template-columns:1fr 1fr}.gps-grid{grid-template-columns:1fr}#map{height:450px}}
</style>

<div class="gps-wrap">
    <h1>📍 Centre GPS MODIGO</h1>
    <p>Positions des chauffeurs · actualisation automatique toutes les 5 secondes</p>

    <?php if ($gpsError !== ''): ?>
        <div class="warn">Lecture GPS impossible : <?= htmlspecialchars($gpsError) ?></div>
    <?php elseif (!empty($rows)): ?>
        <div class="ok">Connexion GPS opérationnelle.</div>
    <?php endif; ?>

    <div class="gps-stats">
        <div class="gps-stat">🟢 GPS actifs<strong><?= $online ?></strong></div>
        <div class="gps-stat">🚘 En service<strong><?= $service ?></strong></div>
        <div class="gps-stat">⚠️ GPS anciens<strong><?= $stale ?></strong></div>
        <div class="gps-stat">👨‍✈️ Chauffeurs suivis<strong><?= count($rows) ?></strong></div>
    </div>

    <div class="gps-grid">
        <div id="map"></div>
        <div class="drivers">
            <?php if (!$rows && $gpsError === ''): ?>
                <div class="driver-card">
                    <h3>Aucune position reçue</h3>
                    <p class="muted">Ouvrez l'espace chauffeur sur le téléphone, appuyez sur « Début de service » puis autorisez la localisation.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($rows as $r):
                $updatedTs = !empty($r['updated_at']) ? strtotime($r['updated_at']) : 0;
                $age = $updatedTs > 0 ? max(0, $now - $updatedTs) : 999999;
                $fresh = $age <= 60;
                $name = trim((string)($r['prenom'] ?? '')) ?: 'Chauffeur';
                $vehicle = trim((string)($r['vehicule'] ?? '')) ?: 'Véhicule non renseigné';
            ?>
                <article class="driver-card <?= $fresh ? 'fresh' : 'stale' ?>">
                    <h3><?= htmlspecialchars($name) ?></h3>
                    <div class="muted">
                        <?= htmlspecialchars($vehicle) ?><br>
                        <strong><?= $fresh ? '🟢 GPS reçu' : '🔴 GPS perdu' ?></strong> · il y a <?= $age ?> s<br>
                        Statut : <?= htmlspecialchars((string)($r['service_status'] ?? '-')) ?>
                        <?php if (($r['accuracy'] ?? null) !== null): ?><br>Précision : <?= round((float)$r['accuracy']) ?> m<?php endif; ?>
                        <?php if (($r['speed'] ?? null) !== null): ?><br>Vitesse : <?= round((float)$r['speed']) ?> km/h<?php endif; ?>
                        <?php if (($r['battery_level'] ?? null) !== null): ?><br>Batterie : <?= (int)$r['battery_level'] ?> %<?php endif; ?>
                        <?php if (!empty($r['network_type'])): ?><br>Réseau : <?= htmlspecialchars((string)$r['network_type']) ?><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const data = <?= json_encode($markers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const map = L.map('map').setView([44.8378, -0.5792], 11);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

const bounds = [];

data.forEach(m => {
    const color = m.fresh ? '#16a34a' : '#dc2626';
    L.circleMarker([m.lat, m.lng], {
        radius: 12,
        color,
        fillColor: color,
        fillOpacity: 0.9
    }).addTo(map).bindPopup(
        `<b>${m.name}</b><br>` +
        `${m.vehicle}<br>` +
        `GPS : il y a ${m.age} s<br>` +
        `Statut : ${m.status}<br>` +
        `Précision : ${m.accuracy === null ? '-' : Math.round(m.accuracy) + ' m'}<br>` +
        `Vitesse : ${m.speed === null ? '-' : Math.round(m.speed) + ' km/h'}<br>` +
        `Batterie : ${m.battery === null ? '-' : m.battery + ' %'}<br>` +
        `Réseau : ${m.network || '-'}`
    );
    bounds.push([m.lat, m.lng]);
});

if (bounds.length) {
    map.fitBounds(bounds, {padding: [30, 30], maxZoom: 16});
}

setTimeout(() => location.reload(), 5000);
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
