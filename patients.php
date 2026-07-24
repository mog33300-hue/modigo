<?php
require 'auth.php';
require 'config.php';

$societe_id = $_SESSION['societe_id'] ?? 1;

/* SUPPRESSION PATIENT */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $pdo->prepare("
        DELETE FROM patients
        WHERE id=?
        AND societe_id=?
    ");

    $stmt->execute([$id, $societe_id]);

    header("Location: patients.php");
    exit;
}

/* RECHERCHE */
$search = trim($_GET['search'] ?? '');

$sql = "
SELECT *
FROM patients
WHERE societe_id=?
";

$params = [$societe_id];

if (!empty($search)) {

    $sql .= "
    AND (
        nom LIKE ?
        OR prenom LIKE ?
        OR telephone LIKE ?
        OR ville LIKE ?
    )
    ";

    $search_like = "%".$search."%";

    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

$sql .= " ORDER BY nom ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_patients = count($patients);

$patients_pmr = 0;
foreach ($patients as $p) {
    $fauteuil = strtolower(trim(($p['fauteuil'] ?? '') . ' ' . ($p['pmr'] ?? '')));
    if (
        $fauteuil === '1' ||
        $fauteuil === 'oui' ||
        str_contains($fauteuil, 'pmr') ||
        str_contains($fauteuil, 'fauteuil')
    ) {
        $patients_pmr++;
    }
}
?>

<?php
$page_title='MODIGO - Patients';
include __DIR__.'/includes/header.php';
include __DIR__.'/includes/menu.php';
?>

<div class="topbar">
    <div>
        <h1>Patients</h1>
        <p>Base patients MODIGO · Recherche, appels et fiches rapides</p>
    </div>

    <div class="topbar-actions">
        <a href="dashboard.php" class="btn btn-glass">🏠 Dashboard</a>
        <a href="add_patient.php" class="btn btn-white">➕ Nouveau patient</a>
    </div>
</div>

<section class="hero">
    <div>
        <div class="badge-title">Module patients</div>
        <h2>Gestion des patients</h2>
        <p>
            Consultez rapidement vos patients, leurs coordonnées, leur ville
            et accédez aux actions essentielles depuis une interface adaptée au PC et au téléphone.
        </p>
    </div>
</section>

<div class="stats-grid">

<div class="stat-card">
<small>👥 Patients affichés</small>
<h2><?= intval($total_patients) ?></h2>
</div>

<div class="stat-card">
<small>♿ PMR / fauteuil</small>
<h2><?= intval($patients_pmr) ?></h2>
</div>

<div class="stat-card">
<small>🔎 Recherche</small>
<h2><?= !empty($search) ? 'ON' : 'OFF' ?></h2>
</div>

</div>

<div class="panel">

<form method="GET" class="search-form">

<input
    type="text"
    name="search"
    placeholder="Rechercher par nom, prénom, téléphone ou ville..."
    value="<?= htmlspecialchars($search) ?>"
>

<button type="submit" class="btn btn-white">
🔍 Rechercher
</button>

<?php if(!empty($search)): ?>
<a href="patients.php" class="btn btn-glass">
✖ Effacer
</a>
<?php endif; ?>

</form>

</div>

<?php if(empty($patients)): ?>

<div class="empty">
Aucun patient trouvé
</div>

<?php else: ?>

<div class="patient-grid">

<?php foreach($patients as $patient): ?>

<?php
$nom_complet = trim(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? ''));
$telephone = trim($patient['telephone'] ?? '');
$ville = trim($patient['ville'] ?? '');
$adresse = trim($patient['adresse'] ?? '');
$pmr_txt = trim(($patient['fauteuil'] ?? '') . ' ' . ($patient['pmr'] ?? ''));
?>

<div class="patient-card">

<div class="patient-head">

<div class="patient-name">
<div class="avatar">👤</div>
<div>
<strong><?= htmlspecialchars($nom_complet ?: 'Patient') ?></strong>
<small>ID patient #<?= intval($patient['id']) ?></small>
</div>
</div>

<span class="status">Actif</span>

</div>

<div class="info">

<div>
<span>📞</span>
<div><?= !empty($telephone) ? htmlspecialchars($telephone) : 'Téléphone non renseigné' ?></div>
</div>

<div>
<span>📍</span>
<div>
<?= !empty($adresse) ? htmlspecialchars($adresse) : 'Adresse non renseignée' ?>
<?php if(!empty($ville)): ?>
<br><?= htmlspecialchars($ville) ?>
<?php endif; ?>
</div>
</div>

<div>
<span>♿</span>
<div><?= !empty($pmr_txt) ? htmlspecialchars($pmr_txt) : 'PMR non renseigné' ?></div>
</div>

</div>

<div class="card-actions">

<?php if(!empty($telephone)): ?>
<a href="tel:<?= htmlspecialchars($telephone) ?>" class="btn btn-call">
📞 Appeler
</a>
<?php endif; ?>

<a
href="edit_patient.php?id=<?= intval($patient['id']) ?>"
class="btn btn-edit"
>
✏ Modifier
</a>

<a
href="patients.php?delete=<?= intval($patient['id']) ?>"
class="btn btn-delete"
onclick="return confirm('Supprimer ce patient ?')"
>
🗑 Supprimer
</a>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<?php include __DIR__.'/includes/footer.php'; ?>
