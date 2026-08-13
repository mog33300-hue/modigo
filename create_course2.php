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

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   DONNÉES DU FORMULAIRE
========================= */

/* PATIENTS */
$stmt = $pdo->prepare("SELECT * FROM patients WHERE societe_id = ? ORDER BY nom ASC");
$stmt->execute([$societe_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* CHAUFFEURS */
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'chauffeur' AND societe_id = ? ORDER BY prenom ASC");
$stmt->execute([$societe_id]);
$chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* VÉHICULES */
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE company_id = ? ORDER BY plate ASC");
$stmt->execute([$societe_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';

$values = [
    'patient_id'      => (string)($_POST['patient_id'] ?? ''),
    'chauffeur_id'    => (string)($_POST['chauffeur_id'] ?? ''),
    'vehicle_id'      => (string)($_POST['vehicle_id'] ?? ''),
    'date_course'     => (string)($_POST['date_course'] ?? date('Y-m-d')),
    'heure_pickup'    => (string)($_POST['heure_pickup'] ?? ''),
    'adresse_depart'  => (string)($_POST['adresse_depart'] ?? ''),
    'ville_depart'    => (string)($_POST['ville_depart'] ?? ''),
    'adresse_arrivee' => (string)($_POST['adresse_arrivee'] ?? ''),
    'ville_arrivee'   => (string)($_POST['ville_arrivee'] ?? ''),
    'statut'          => (string)($_POST['statut'] ?? 'prévue'),
    'observations'    => (string)($_POST['observations'] ?? ''),
];

/* =========================
   CRÉATION DE LA COURSE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id   = (int)$values['patient_id'];
    $chauffeur_id = (int)$values['chauffeur_id'];
    $vehicle_id   = (int)$values['vehicle_id'];

    if ($patient_id <= 0 || $chauffeur_id <= 0 || trim($values['date_course']) === '') {
        $error = 'Veuillez sélectionner un patient, un chauffeur et une date.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT prenom, nom, telephone FROM patients WHERE id = ? AND societe_id = ? LIMIT 1");
            $stmt->execute([$patient_id, $societe_id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$patient) {
                throw new Exception('Patient introuvable.');
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'chauffeur' AND societe_id = ? LIMIT 1");
            $stmt->execute([$chauffeur_id, $societe_id]);
            if (!$stmt->fetchColumn()) {
                throw new Exception('Chauffeur introuvable.');
            }

            if ($vehicle_id > 0) {
                $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND company_id = ? LIMIT 1");
                $stmt->execute([$vehicle_id, $societe_id]);
                if (!$stmt->fetchColumn()) {
                    throw new Exception('Véhicule introuvable.');
                }
            }

            $client_nom = trim(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? ''));
            $telephone  = trim((string)($patient['telephone'] ?? ''));

            $stmt = $pdo->prepare("\n                INSERT INTO courses (\n                    societe_id, patient_id, client_nom, telephone, chauffeur_id, vehicle_id,\n                    date_course, heure_pickup, adresse_depart, ville_depart, adresse_arrivee,\n                    ville_arrivee, statut, observations, created_at\n                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())\n            ");

            $stmt->execute([
                $societe_id,
                $patient_id,
                $client_nom,
                $telephone,
                $chauffeur_id,
                $vehicle_id > 0 ? $vehicle_id : null,
                trim($values['date_course']),
                trim($values['heure_pickup']),
                trim($values['adresse_depart']),
                trim($values['ville_depart']),
                trim($values['adresse_arrivee']),
                trim($values['ville_arrivee']),
                trim($values['statut']),
                trim($values['observations']),
            ]);

            header('Location: courses.php?created=1');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$page_title = 'MODIGO - Nouvelle course';
$modigo_page_class = 'create-course-premium';
$modigo_extra_head = <<<'HTML'
<style>
.create-course-premium{background:radial-gradient(circle at 80% 10%,rgba(37,99,235,.22),transparent 30%),linear-gradient(135deg,#0f1d3b,#173a82);color:#fff}.create-course-premium .main{padding:26px 28px 34px}.cc-wrap{max-width:1550px;margin:0 auto}.cc-top{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:18px}.cc-eyebrow{display:block;color:#bfdbfe;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.cc-top h1{margin:7px 0 5px;font-size:34px;line-height:1.05}.cc-top p{color:#dbeafe;font-size:14px}.cc-version{padding:10px 14px;border:1px solid rgba(255,255,255,.18);border-radius:14px;background:rgba(255,255,255,.08);font-size:12px;font-weight:900;white-space:nowrap}.cc-progress{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:17px}.cc-step{display:flex;align-items:center;gap:10px;padding:13px 14px;border:1px solid rgba(255,255,255,.12);border-radius:16px;background:rgba(255,255,255,.06)}.cc-step-icon{display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:rgba(255,255,255,.12);font-size:17px}.cc-step strong{display:block;font-size:12px}.cc-step span{display:block;margin-top:2px;color:#a9c6f8;font-size:10px}.cc-alert{margin-bottom:16px;padding:15px 17px;border:1px solid rgba(248,113,113,.45);border-radius:15px;background:rgba(127,29,29,.45);color:#fee2e2;font-weight:800}.cc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.cc-card{padding:20px;border:1px solid rgba(255,255,255,.13);border-radius:20px;background:linear-gradient(145deg,rgba(255,255,255,.11),rgba(255,255,255,.065));box-shadow:0 16px 40px rgba(0,0,0,.16)}.cc-card.full{grid-column:1/-1}.cc-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}.cc-card-title{display:flex;align-items:center;gap:10px}.cc-card-icon{display:grid;place-items:center;width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.12);font-size:19px}.cc-card h2{font-size:18px}.cc-card small{color:#a9c6f8;font-size:11px}.cc-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.cc-field.full{grid-column:1/-1}.cc-field label{display:flex;justify-content:space-between;gap:10px;margin-bottom:7px;color:#dbeafe;font-size:11px;font-weight:900}.cc-required{color:#fda4af}.cc-input{width:100%;min-height:44px;padding:11px 13px;border:1px solid rgba(255,255,255,.16);border-radius:13px;background:rgba(9,21,49,.58);color:#fff;font:inherit;font-size:13px;outline:none}.cc-input:focus{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(59,130,246,.16)}.cc-input::placeholder{color:#7893bf}.cc-input option{color:#111827;background:#fff}.cc-address-row{display:grid;grid-template-columns:1fr auto;gap:8px}.cc-map-btn{min-width:44px;padding:0 11px;border:1px solid rgba(255,255,255,.17);border-radius:13px;background:rgba(255,255,255,.09);color:#fff;cursor:pointer}.cc-map-btn:hover{background:rgba(255,255,255,.16)}.cc-patient-preview{display:none;margin-top:12px;padding:12px;border:1px solid rgba(96,165,250,.25);border-radius:13px;background:rgba(30,64,175,.18);font-size:12px;line-height:1.55}.cc-patient-preview.visible{display:block}.cc-preview-name{font-weight:900}.cc-trip-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:13px}.cc-summary-box{padding:12px;border-radius:13px;background:rgba(15,23,42,.34);border:1px solid rgba(255,255,255,.09)}.cc-summary-box span{display:block;color:#a9c6f8;font-size:10px;font-weight:800;text-transform:uppercase}.cc-summary-box strong{display:block;margin-top:5px;font-size:13px}.cc-actions{position:sticky;bottom:12px;z-index:5;display:flex;align-items:center;justify-content:space-between;gap:15px;margin-top:18px;padding:14px 16px;border:1px solid rgba(255,255,255,.15);border-radius:18px;background:rgba(13,31,70,.92);box-shadow:0 18px 45px rgba(0,0,0,.28);backdrop-filter:blur(16px)}.cc-help{color:#bcd2f8;font-size:12px}.cc-buttons{display:flex;gap:9px}.cc-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:43px;padding:10px 15px;border:1px solid rgba(255,255,255,.16);border-radius:13px;background:rgba(255,255,255,.09);color:#fff;text-decoration:none;font:inherit;font-size:12px;font-weight:900;cursor:pointer}.cc-btn:hover{background:rgba(255,255,255,.16)}.cc-btn.primary{background:linear-gradient(135deg,#2563eb,#1d4ed8);border-color:#60a5fa;min-width:190px}.cc-footer{display:flex;justify-content:space-between;gap:15px;padding:15px 3px 0;color:#8eaddd;font-size:11px}@media(max-width:1150px){.cc-progress{grid-template-columns:repeat(2,1fr)}.cc-grid{grid-template-columns:1fr}.cc-card.full{grid-column:auto}}@media(max-width:760px){.create-course-premium .main{padding:16px}.cc-top,.cc-actions,.cc-footer{flex-direction:column;align-items:stretch}.cc-top h1{font-size:28px}.cc-progress,.cc-form-grid,.cc-trip-summary{grid-template-columns:1fr}.cc-field.full{grid-column:auto}.cc-buttons{display:grid;grid-template-columns:1fr}.cc-btn{width:100%}.cc-version{align-self:flex-start}}
</style>
HTML;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';
?>

<div class="cc-wrap">
    <header class="cc-top">
        <div>
            <span class="cc-eyebrow">Assistant d'exploitation</span>
            <h1>➕ Nouvelle course</h1>
            <p>Préparez la mission, affectez les ressources et transmettez des consignes claires au chauffeur.</p>
        </div>
        <div class="cc-version">Sprint 3.5.003</div>
    </header>

    <section class="cc-progress" aria-label="Étapes de création">
        <div class="cc-step"><div class="cc-step-icon">👤</div><div><strong>1. Patient</strong><span>Identité et contact</span></div></div>
        <div class="cc-step"><div class="cc-step-icon">📍</div><div><strong>2. Trajet</strong><span>Départ et destination</span></div></div>
        <div class="cc-step"><div class="cc-step-icon">🚑</div><div><strong>3. Affectation</strong><span>Chauffeur et véhicule</span></div></div>
        <div class="cc-step"><div class="cc-step-icon">✅</div><div><strong>4. Validation</strong><span>Contrôle avant création</span></div></div>
    </section>

    <?php if ($error !== ''): ?>
        <div class="cc-alert">⚠️ <?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" id="courseForm" autocomplete="off">
        <div class="cc-grid">
            <section class="cc-card">
                <div class="cc-card-head">
                    <div class="cc-card-title"><div class="cc-card-icon">👤</div><div><h2>Patient</h2><small>Personne prise en charge</small></div></div>
                    <a href="patients.php" class="cc-btn">Gérer les patients</a>
                </div>
                <div class="cc-field">
                    <label for="patient_id"><span>Patient</span><span class="cc-required">Obligatoire</span></label>
                    <select class="cc-input" name="patient_id" id="patient_id" required>
                        <option value="">Sélectionner un patient</option>
                        <?php foreach ($patients as $patient):
                            $patientName = trim(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? ''));
                            $patientAddress = trim(($patient['adresse'] ?? '') . ' ' . ($patient['ville'] ?? ''));
                        ?>
                            <option value="<?= (int)$patient['id'] ?>"
                                data-name="<?= h($patientName) ?>"
                                data-phone="<?= h($patient['telephone'] ?? '') ?>"
                                data-address="<?= h($patientAddress) ?>"
                                <?= $values['patient_id'] === (string)$patient['id'] ? 'selected' : '' ?>>
                                <?= h($patientName) ?><?= !empty($patient['telephone']) ? ' — ' . h($patient['telephone']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="cc-patient-preview" id="patientPreview">
                        <div class="cc-preview-name" id="previewName"></div>
                        <div id="previewPhone"></div>
                        <div id="previewAddress"></div>
                    </div>
                </div>
            </section>

            <section class="cc-card">
                <div class="cc-card-head"><div class="cc-card-title"><div class="cc-card-icon">📅</div><div><h2>Date et organisation</h2><small>Programmation de la mission</small></div></div></div>
                <div class="cc-form-grid">
                    <div class="cc-field"><label for="date_course"><span>Date de course</span><span class="cc-required">Obligatoire</span></label><input class="cc-input" type="date" name="date_course" id="date_course" value="<?= h($values['date_course']) ?>" required></div>
                    <div class="cc-field"><label for="heure_pickup"><span>Heure de prise en charge</span></label><input class="cc-input" type="time" name="heure_pickup" id="heure_pickup" value="<?= h($values['heure_pickup']) ?>"></div>
                    <div class="cc-field full"><label for="statut"><span>Statut initial</span></label><select class="cc-input" name="statut" id="statut"><option value="prévue" <?= $values['statut'] === 'prévue' ? 'selected' : '' ?>>Prévue</option><option value="en cours" <?= $values['statut'] === 'en cours' ? 'selected' : '' ?>>En cours</option><option value="terminée" <?= $values['statut'] === 'terminée' ? 'selected' : '' ?>>Terminée</option></select></div>
                </div>
            </section>

            <section class="cc-card">
                <div class="cc-card-head"><div class="cc-card-title"><div class="cc-card-icon">🟢</div><div><h2>Adresse de départ</h2><small>Lieu de prise en charge</small></div></div></div>
                <div class="cc-form-grid">
                    <div class="cc-field full"><label for="adresse_depart"><span>Adresse</span></label><div class="cc-address-row"><input class="cc-input" type="text" name="adresse_depart" id="adresse_depart" value="<?= h($values['adresse_depart']) ?>" placeholder="Numéro, voie, établissement..."><button class="cc-map-btn" type="button" data-map="depart" title="Vérifier sur OpenStreetMap">🗺️</button></div></div>
                    <div class="cc-field full"><label for="ville_depart"><span>Ville</span></label><input class="cc-input" type="text" name="ville_depart" id="ville_depart" value="<?= h($values['ville_depart']) ?>" placeholder="Ville ou code postal"></div>
                </div>
            </section>

            <section class="cc-card">
                <div class="cc-card-head"><div class="cc-card-title"><div class="cc-card-icon">🔴</div><div><h2>Adresse d'arrivée</h2><small>Destination de la mission</small></div></div></div>
                <div class="cc-form-grid">
                    <div class="cc-field full"><label for="adresse_arrivee"><span>Adresse</span></label><div class="cc-address-row"><input class="cc-input" type="text" name="adresse_arrivee" id="adresse_arrivee" value="<?= h($values['adresse_arrivee']) ?>" placeholder="Hôpital, cabinet, domicile..."><button class="cc-map-btn" type="button" data-map="arrivee" title="Vérifier sur OpenStreetMap">🗺️</button></div></div>
                    <div class="cc-field full"><label for="ville_arrivee"><span>Ville</span></label><input class="cc-input" type="text" name="ville_arrivee" id="ville_arrivee" value="<?= h($values['ville_arrivee']) ?>" placeholder="Ville ou code postal"></div>
                </div>
            </section>

            <section class="cc-card">
                <div class="cc-card-head"><div class="cc-card-title"><div class="cc-card-icon">🚑</div><div><h2>Affectation</h2><small>Ressources opérationnelles</small></div></div></div>
                <div class="cc-form-grid">
                    <div class="cc-field"><label for="chauffeur_id"><span>Chauffeur</span><span class="cc-required">Obligatoire</span></label><select class="cc-input" name="chauffeur_id" id="chauffeur_id" required><option value="">Choisir un chauffeur</option><?php foreach ($chauffeurs as $chauffeur): ?><option value="<?= (int)$chauffeur['id'] ?>" <?= $values['chauffeur_id'] === (string)$chauffeur['id'] ? 'selected' : '' ?>><?= h(trim(($chauffeur['prenom'] ?? '') . ' ' . ($chauffeur['nom'] ?? ''))) ?></option><?php endforeach; ?></select></div>
                    <div class="cc-field"><label for="vehicle_id"><span>Véhicule</span></label><select class="cc-input" name="vehicle_id" id="vehicle_id"><option value="">Aucun véhicule</option><?php foreach ($vehicles as $vehicle): ?><option value="<?= (int)$vehicle['id'] ?>" <?= $values['vehicle_id'] === (string)$vehicle['id'] ? 'selected' : '' ?>><?= h($vehicle['plate'] ?? '') ?><?= !empty($vehicle['name']) ? ' — ' . h($vehicle['name']) : '' ?></option><?php endforeach; ?></select></div>
                </div>
            </section>

            <section class="cc-card">
                <div class="cc-card-head"><div class="cc-card-title"><div class="cc-card-icon">📝</div><div><h2>Consignes chauffeur</h2><small>Informations utiles à la prise en charge</small></div></div></div>
                <div class="cc-field"><label for="observations"><span>Observations</span></label><textarea class="cc-input" name="observations" id="observations" rows="6" placeholder="PMR, étage, accompagnant, accès, matériel, consignes particulières..."><?= h($values['observations']) ?></textarea></div>
            </section>

            <section class="cc-card full">
                <div class="cc-card-head"><div class="cc-card-title"><div class="cc-card-icon">🔎</div><div><h2>Contrôle de la mission</h2><small>Résumé mis à jour avant l'enregistrement</small></div></div></div>
                <div class="cc-trip-summary">
                    <div class="cc-summary-box"><span>Patient</span><strong id="summaryPatient">Non sélectionné</strong></div>
                    <div class="cc-summary-box"><span>Horaire</span><strong id="summarySchedule">À renseigner</strong></div>
                    <div class="cc-summary-box"><span>Affectation</span><strong id="summaryAssignment">À renseigner</strong></div>
                </div>
            </section>
        </div>

        <div class="cc-actions">
            <div class="cc-help">Les champs Patient, Chauffeur et Date sont obligatoires. Vérifiez les adresses avant validation.</div>
            <div class="cc-buttons"><a href="courses.php" class="cc-btn">← Annuler</a><button type="submit" class="cc-btn primary" id="submitButton">💾 Créer la course</button></div>
        </div>
    </form>

    <footer class="cc-footer"><span>MODIGO — Assistant de création de course</span><span>Patient : <?= count($patients) ?> · Chauffeurs : <?= count($chauffeurs) ?> · Véhicules : <?= count($vehicles) ?></span></footer>
</div>

<script>
(function(){
    const patient = document.getElementById('patient_id');
    const chauffeur = document.getElementById('chauffeur_id');
    const vehicle = document.getElementById('vehicle_id');
    const date = document.getElementById('date_course');
    const time = document.getElementById('heure_pickup');
    const preview = document.getElementById('patientPreview');

    function selectedText(select){
        return select && select.selectedIndex > 0 ? select.options[select.selectedIndex].text.split(' — ')[0].trim() : '';
    }

    function updatePatient(){
        const option = patient.options[patient.selectedIndex];
        const name = option ? option.dataset.name || '' : '';
        const phone = option ? option.dataset.phone || '' : '';
        const address = option ? option.dataset.address || '' : '';
        document.getElementById('previewName').textContent = name;
        document.getElementById('previewPhone').textContent = phone ? '📞 ' + phone : '📞 Téléphone non renseigné';
        document.getElementById('previewAddress').textContent = address ? '📍 ' + address : '📍 Adresse non renseignée';
        preview.classList.toggle('visible', !!name);
        document.getElementById('summaryPatient').textContent = name || 'Non sélectionné';
    }

    function updateSummary(){
        const d = date.value ? new Date(date.value + 'T12:00:00').toLocaleDateString('fr-FR') : '';
        document.getElementById('summarySchedule').textContent = d ? d + (time.value ? ' à ' + time.value : '') : 'À renseigner';
        const driver = selectedText(chauffeur);
        const car = selectedText(vehicle);
        document.getElementById('summaryAssignment').textContent = driver ? driver + (car ? ' · ' + car : '') : 'À renseigner';
    }

    patient.addEventListener('change', updatePatient);
    [chauffeur, vehicle, date, time].forEach(el => el.addEventListener('change', updateSummary));

    document.querySelectorAll('[data-map]').forEach(button => {
        button.addEventListener('click', function(){
            const type = this.dataset.map;
            const address = document.getElementById('adresse_' + type).value.trim();
            const city = document.getElementById('ville_' + type).value.trim();
            const query = [address, city].filter(Boolean).join(', ');
            if (!query) {
                alert('Renseignez une adresse ou une ville avant d’ouvrir la carte.');
                return;
            }
            window.open('https://www.openstreetmap.org/search?query=' + encodeURIComponent(query), '_blank', 'noopener');
        });
    });

    document.getElementById('courseForm').addEventListener('submit', function(){
        const button = document.getElementById('submitButton');
        button.disabled = true;
        button.textContent = '⏳ Création en cours...';
    });

    updatePatient();
    updateSummary();
})();
</script>

</main>
</div>
</body>
</html>
