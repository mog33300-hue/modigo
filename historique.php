<?php
require 'auth.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$societe_id = (int)($_SESSION['societe_id'] ?? 0);
if ($societe_id <= 0) {
    die('<h2>Société invalide</h2>');
}

$date_debut = trim((string)($_GET['date_debut'] ?? ''));
$date_fin   = trim((string)($_GET['date_fin'] ?? ''));

$sql = "
SELECT c.*, u.prenom AS chauffeur
FROM courses c
LEFT JOIN users u ON u.id = c.chauffeur_id
WHERE c.societe_id = ?
AND c.statut IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE')
";
$params = [$societe_id];

if ($date_debut !== '') {
    $sql .= ' AND c.date_course >= ? ';
    $params[] = $date_debut;
}
if ($date_fin !== '') {
    $sql .= ' AND c.date_course <= ? ';
    $params[] = $date_fin;
}

$sql .= ' ORDER BY c.date_course DESC, c.heure_pickup DESC ';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function heure_courte(?string $value): string
{
    if (empty($value)) {
        return '-';
    }
    return substr($value, 0, 5);
}

function calculer_duree(?string $depart, ?string $arrivee): array
{
    if (empty($depart) || empty($arrivee)) {
        return ['texte' => '-', 'minutes' => 0];
    }

    $departTime = strtotime($depart);
    $arriveeTime = strtotime($arrivee);
    if ($departTime === false || $arriveeTime === false || $arriveeTime < $departTime) {
        return ['texte' => '-', 'minutes' => 0];
    }

    $minutes = (int)floor(($arriveeTime - $departTime) / 60);
    $heures = intdiv($minutes, 60);
    $reste = $minutes % 60;

    return [
        'texte' => $heures > 0 ? $heures . 'h ' . $reste . 'min' : $reste . ' min',
        'minutes' => $minutes,
    ];
}

$total = count($courses);
$totalMinutes = 0;
$avecDuree = 0;
$aujourdhui = 0;
$cetteSemaine = 0;
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));

foreach ($courses as $course) {
    $duree = calculer_duree($course['depart_reel'] ?? null, $course['arrivee_reelle'] ?? null);
    if ($duree['minutes'] > 0) {
        $totalMinutes += $duree['minutes'];
        $avecDuree++;
    }
    $dateCourse = (string)($course['date_course'] ?? '');
    if ($dateCourse === $today) {
        $aujourdhui++;
    }
    if ($dateCourse >= $weekStart && $dateCourse <= $today) {
        $cetteSemaine++;
    }
}

$moyenneMinutes = $avecDuree > 0 ? (int)round($totalMinutes / $avecDuree) : 0;
$moyenneTexte = $moyenneMinutes >= 60
    ? intdiv($moyenneMinutes, 60) . 'h ' . ($moyenneMinutes % 60) . 'min'
    : $moyenneMinutes . ' min';

$page_title = 'MODIGO - Historique des missions';
$modigo_page_class = 'historique-premium';
$modigo_extra_head = <<<'HTML'
<style>
.historique-premium .main{padding:28px;min-width:0}.hist-wrap{display:grid;gap:20px}.hist-top{display:flex;justify-content:space-between;align-items:flex-start;gap:18px}.hist-eyebrow{color:#bfdbfe;font-size:11px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}.hist-top h1{font-size:36px;margin:5px 0 6px}.hist-top p{color:var(--modigo-muted,#94a3b8)}.hist-version{padding:11px 15px;border-radius:16px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);font-size:12px;font-weight:800;white-space:nowrap}.hist-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.hist-stat,.hist-panel{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);box-shadow:0 18px 45px rgba(0,0,0,.16)}.hist-stat{position:relative;padding:18px 20px;border-radius:22px;overflow:hidden}.hist-stat-icon{position:absolute;right:17px;top:15px;width:42px;height:42px;display:grid;place-items:center;border-radius:14px;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.12);font-size:21px}.hist-stat span{display:block;padding-right:54px;color:var(--modigo-muted,#94a3b8);font-size:12px;font-weight:800}.hist-stat strong{display:block;font-size:30px;margin:7px 0 3px}.hist-stat small{color:#cbd5e1}.hist-panel{padding:20px;border-radius:26px}.hist-panel-head{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:17px}.hist-panel-head h2{font-size:20px}.hist-actions{display:flex;gap:9px;flex-wrap:wrap}.hist-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:10px 14px;border:1px solid rgba(255,255,255,.15);border-radius:13px;background:rgba(255,255,255,.09);color:#fff;text-decoration:none;font:inherit;font-size:13px;font-weight:800;cursor:pointer}.hist-btn:hover{background:rgba(255,255,255,.16)}.hist-btn.primary{background:#2563eb;border-color:#3b82f6}.hist-btn.danger{background:rgba(127,29,29,.45)}.hist-filter-grid{display:grid;grid-template-columns:1fr 1fr 1.4fr auto;gap:12px;align-items:end}.hist-field label{display:block;margin-bottom:7px;color:#cbd5e1;font-size:12px;font-weight:800}.hist-input{width:100%;height:44px;padding:0 13px;border-radius:13px;border:1px solid rgba(255,255,255,.16);background:rgba(15,23,42,.5);color:#fff;font:inherit;outline:none}.hist-input:focus{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(59,130,246,.17)}.hist-shortcuts{display:flex;gap:8px;flex-wrap:wrap;margin-top:13px}.hist-shortcuts button{padding:8px 11px;border:1px solid rgba(255,255,255,.14);border-radius:999px;background:rgba(255,255,255,.07);color:#e2e8f0;font-size:12px;font-weight:800;cursor:pointer}.hist-shortcuts button:hover{background:rgba(255,255,255,.14)}.hist-table-wrap{overflow:auto;border-radius:18px;border:1px solid rgba(255,255,255,.1)}.hist-table{width:100%;min-width:1220px;border-collapse:collapse}.hist-table th{position:sticky;top:0;z-index:2;padding:14px 13px;background:#111c32;color:#bfdbfe;text-align:left;font-size:11px;letter-spacing:.05em;text-transform:uppercase}.hist-table td{padding:11px 13px;border-top:1px solid rgba(255,255,255,.08);vertical-align:top;font-size:13px}.hist-table tbody tr{background:rgba(255,255,255,.025)}.hist-table tbody tr:hover{background:rgba(255,255,255,.07)}.hist-patient{font-weight:900;color:#fff}.hist-sub{display:block;margin-top:4px;color:#94a3b8;font-size:11px}.hist-time{font-weight:800;white-space:nowrap}.hist-address{max-width:240px;line-height:1.4;color:#dbeafe}.hist-badge{display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:999px;background:linear-gradient(135deg,rgba(22,163,74,.32),rgba(5,150,105,.20));border:1px solid rgba(74,222,128,.38);box-shadow:0 5px 14px rgba(16,185,129,.10);color:#a7f3d0;font-size:11px;font-weight:900;white-space:nowrap}.hist-empty{padding:50px 20px;text-align:center;color:#94a3b8}.hist-footer{display:flex;justify-content:space-between;gap:15px;color:#94a3b8;font-size:12px}.hist-count{font-weight:900;color:#fff}.hist-hidden{display:none!important}@media(max-width:1200px){.hist-stats{grid-template-columns:repeat(2,1fr)}.hist-filter-grid{grid-template-columns:1fr 1fr}.hist-filter-grid .hist-search{grid-column:1/-1}}@media(max-width:760px){.historique-premium .main{padding:16px}.hist-top,.hist-panel-head,.hist-footer{flex-direction:column;align-items:flex-start}.hist-top h1{font-size:29px}.hist-stats{grid-template-columns:1fr}.hist-filter-grid{grid-template-columns:1fr}.hist-filter-grid .hist-search{grid-column:auto}.hist-actions{width:100%}.hist-btn{flex:1}.hist-panel{padding:15px}}
@media print{.sidebar,.hist-actions,.hist-shortcuts,.hist-filter-panel{display:none!important}.historique-premium .main{padding:0}.hist-stat,.hist-panel{box-shadow:none;color:#000;background:#fff;border:1px solid #ccc}.hist-top p,.hist-stat span,.hist-stat small,.hist-footer{color:#444}.hist-table th{background:#eee;color:#000}.hist-table td{color:#000;border-color:#ddd}.hist-patient,.hist-count{color:#000}}
</style>
HTML;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';
?>

<div class="hist-wrap">
    <header class="hist-top">
        <div>
            <span class="hist-eyebrow">Centre de supervision</span>
            <h1>📊 Historique des missions</h1>
            <p>Consultez les courses terminées, les horaires réels et la durée des prises en charge.</p>
        </div>
        <div class="hist-version">Sprint 3.5.002.2</div>
    </header>

    <section class="hist-stats">
        <article class="hist-stat"><div class="hist-stat-icon">📋</div><span>Missions affichées</span><strong><?= $total ?></strong><small>selon les filtres actifs</small></article>
        <article class="hist-stat"><div class="hist-stat-icon">🚑</div><span>Aujourd'hui</span><strong><?= $aujourdhui ?></strong><small>courses terminées</small></article>
        <article class="hist-stat"><div class="hist-stat-icon">📅</div><span>Cette semaine</span><strong><?= $cetteSemaine ?></strong><small>depuis lundi</small></article>
        <article class="hist-stat"><div class="hist-stat-icon">⏱️</div><span>Durée moyenne</span><strong><?= h($moyenneTexte) ?></strong><small>sur les horaires renseignés</small></article>
    </section>

    <section class="hist-panel hist-filter-panel">
        <div class="hist-panel-head">
            <div><span class="hist-eyebrow">Recherche et période</span><h2>Filtrer l'historique</h2></div>
            <div class="hist-actions">
                <button type="button" class="hist-btn" onclick="window.print()">🖨️ Imprimer / PDF</button>
                <button type="button" class="hist-btn" onclick="exportCSV()">📄 Export CSV</button>
            </div>
        </div>

        <form method="get" id="filterForm">
            <div class="hist-filter-grid">
                <div class="hist-field"><label for="date_debut">Date de début</label><input class="hist-input" type="date" id="date_debut" name="date_debut" value="<?= h($date_debut) ?>"></div>
                <div class="hist-field"><label for="date_fin">Date de fin</label><input class="hist-input" type="date" id="date_fin" name="date_fin" value="<?= h($date_fin) ?>"></div>
                <div class="hist-field hist-search"><label for="liveSearch">Recherche instantanée</label><input class="hist-input" type="search" id="liveSearch" placeholder="Patient, chauffeur, adresse, heure..."></div>
                <button type="submit" class="hist-btn primary">🔍 Appliquer</button>
            </div>
            <div class="hist-shortcuts">
                <button type="button" data-range="today">Aujourd'hui</button>
                <button type="button" data-range="yesterday">Hier</button>
                <button type="button" data-range="week">Cette semaine</button>
                <button type="button" data-range="month">Ce mois</button>
                <a class="hist-btn danger" href="historique.php">Réinitialiser</a>
            </div>
        </form>
    </section>

    <section class="hist-panel">
        <div class="hist-panel-head">
            <div><span class="hist-eyebrow">Archives opérationnelles</span><h2>Courses terminées</h2></div>
            <div><span class="hist-count" id="visibleCount"><?= $total ?></span> résultat(s)</div>
        </div>

        <div class="hist-table-wrap">
            <table class="hist-table" id="historyTable">
                <thead><tr><th>Date</th><th>Prévue</th><th>Patient</th><th>Chauffeur</th><th>Départ réel</th><th>Arrivée réelle</th><th>Durée</th><th>Adresse départ</th><th>Adresse arrivée</th><th>Statut</th></tr></thead>
                <tbody>
                <?php if (empty($courses)): ?>
                    <tr><td colspan="10"><div class="hist-empty">Aucune course terminée pour cette période.</div></td></tr>
                <?php else: ?>
                    <?php foreach ($courses as $course):
                        $duree = calculer_duree($course['depart_reel'] ?? null, $course['arrivee_reelle'] ?? null);
                        $dateAffichee = !empty($course['date_course']) ? date('d/m/Y', strtotime($course['date_course'])) : '-';
                        $patient = trim((string)($course['client_nom'] ?? '')) ?: 'Patient non renseigné';
                        $chauffeur = trim((string)($course['chauffeur'] ?? '')) ?: 'Non affecté';
                    ?>
                    <tr class="course-row">
                        <td class="hist-time"><?= h($dateAffichee) ?></td>
                        <td class="hist-time"><?= h(heure_courte($course['heure_pickup'] ?? null)) ?></td>
                        <td><span class="hist-patient"><?= h($patient) ?></span><span class="hist-sub">Mission #<?= (int)($course['id'] ?? 0) ?></span></td>
                        <td><?= h($chauffeur) ?></td>
                        <td class="hist-time"><?= h(heure_courte($course['depart_reel'] ?? null)) ?></td>
                        <td class="hist-time"><?= h(heure_courte($course['arrivee_reelle'] ?? null)) ?></td>
                        <td class="hist-time"><?= h($duree['texte']) ?></td>
                        <td class="hist-address"><?= h($course['adresse_depart'] ?? '') ?></td>
                        <td class="hist-address"><?= h($course['adresse_arrivee'] ?? '') ?></td>
                        <td><span class="hist-badge">✓ Terminée</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="hist-footer" style="margin-top:15px">
            <span>Les résultats affichés respectent la société actuellement connectée.</span>
            <span>Actualisé le <?= date('d/m/Y à H:i') ?></span>
        </div>
    </section>
</div>

<script>
(function(){
    const search = document.getElementById('liveSearch');
    const rows = Array.from(document.querySelectorAll('.course-row'));
    const count = document.getElementById('visibleCount');
    if (search) {
        search.addEventListener('input', function(){
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            rows.forEach(row => {
                const show = !q || row.innerText.toLowerCase().includes(q);
                row.classList.toggle('hist-hidden', !show);
                if (show) visible++;
            });
            count.textContent = visible;
        });
    }

    const pad = n => String(n).padStart(2,'0');
    const fmt = d => d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
    document.querySelectorAll('[data-range]').forEach(btn => btn.addEventListener('click', function(){
        const now = new Date();
        let start = new Date(now), end = new Date(now);
        if (this.dataset.range === 'yesterday') { start.setDate(now.getDate()-1); end = new Date(start); }
        if (this.dataset.range === 'week') { const day = now.getDay() || 7; start.setDate(now.getDate()-day+1); }
        if (this.dataset.range === 'month') { start = new Date(now.getFullYear(), now.getMonth(), 1); }
        document.getElementById('date_debut').value = fmt(start);
        document.getElementById('date_fin').value = fmt(end);
        document.getElementById('filterForm').submit();
    }));
})();

function exportCSV(){
    const table = document.getElementById('historyTable');
    const lines = [];
    table.querySelectorAll('tr').forEach(row => {
        if (row.classList.contains('hist-hidden')) return;
        const cells = Array.from(row.querySelectorAll('th,td')).map(cell => '"' + cell.innerText.replace(/"/g,'""').replace(/\s+/g,' ').trim() + '"');
        lines.push(cells.join(';'));
    });
    const blob = new Blob(['\ufeff' + lines.join('\n')], {type:'text/csv;charset=utf-8;'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'historique_modigo_<?= date('Y-m-d') ?>.csv';
    link.click();
    URL.revokeObjectURL(link.href);
}
</script>

</main>
</div>
</body>
</html>
