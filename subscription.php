<?php
require 'auth.php';
require 'config.php';

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("Société invalide");
}

$stmt = $pdo->prepare("
SELECT id, nom, plan, date_expiration, statut
FROM societes
WHERE id = ?
LIMIT 1
");
$stmt->execute([$societe_id]);
$societe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$societe) {
    die("Société introuvable");
}

$nom_societe = $societe['nom'] ?? '';
$date_expiration = $societe['date_expiration'] ?? null;
$plan = strtolower($societe['plan'] ?? 'basic');

$days_left = 0;

if (!empty($date_expiration)) {
    $today = new DateTime(date('Y-m-d'));
    $expire = new DateTime($date_expiration);
    $diff = $today->diff($expire);
    $days_left = intval($diff->format('%r%a'));
    if ($days_left < 0) $days_left = 0;
}

$stmt = $pdo->prepare("
SELECT id, plan, montant, facture_pdf, statut, created_at
FROM paiements
WHERE societe_id = ?
ORDER BY id DESC
");
$stmt->execute([$societe_id]);
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Abonnement - Medigo Synology</title>

<style>
body{margin:0;font-family:Arial,sans-serif;background:#f3f4f6;color:#111827}
.topbar{background:#111827;color:white;padding:18px 30px;display:flex;justify-content:space-between;align-items:center}
.logo{font-size:24px;font-weight:bold}
.logout{background:white;color:#111827;padding:10px 15px;border-radius:8px;text-decoration:none;font-weight:bold}
.container{max-width:1100px;margin:30px auto;padding:20px}
.card{background:white;border-radius:16px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:25px}
.plan-current{font-size:28px;font-weight:bold;margin-bottom:20px}
.bad{color:#dc2626;font-size:20px;font-weight:bold}
.good{color:#16a34a;font-size:20px;font-weight:bold}
.plan-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin-top:30px}
.plan-box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;padding:22px;text-align:center}
.current-plan{background:#dcfce7;color:#166534;padding:8px;border-radius:8px;font-weight:bold;margin-bottom:15px}
.plan-title{font-size:24px;font-weight:bold}
.plan-price{font-size:34px;font-weight:bold;margin:15px 0}
.plan-features{text-align:left;line-height:1.8;margin:20px 0}
.btn{display:inline-block;padding:12px 16px;border-radius:10px;color:white;text-decoration:none;font-weight:bold}
.btn-basic{background:#16a34a}
.btn-pro{background:#f59e0b}
.btn-premium{background:#2563eb}
.btn-disabled{background:#9ca3af;cursor:not-allowed}
.btn-dashboard{background:#111827;margin-top:15px}
.table{width:100%;border-collapse:collapse;margin-top:15px}
.table th{background:#f3f4f6;padding:10px;text-align:left}
.table td{padding:10px;border-top:1px solid #ddd}
.footer{text-align:center;color:#6b7280;margin-top:30px}
</style>
</head>

<body>

<div class="topbar">
    <div class="logo">🚑 Medigo Synology</div>
    <a href="logout.php" class="logout">Déconnexion</a>
</div>

<div class="container">

<h1>💳 Mon abonnement</h1>

<div class="card">

<div class="plan-current">
Plan actuel : <?= htmlspecialchars(strtoupper($plan)) ?>
</div>

<p>🏢 Société : <strong><?= htmlspecialchars($nom_societe) ?></strong></p>

<p>📅 Expiration : <strong><?= htmlspecialchars($date_expiration ?? '-') ?></strong></p>

<?php if($days_left == 0): ?>
<p class="bad">⚠ Votre abonnement est expiré</p>
<p>Choisissez une formule pour réactiver votre accès.</p>
<?php elseif($days_left <= 7): ?>
<p class="bad">⚠ Attention : <?= $days_left ?> jour(s) restants</p>
<a href="dashboard.php" class="btn btn-dashboard">🏠 Retour au logiciel</a>
<?php else: ?>
<p class="good">✅ <?= $days_left ?> jour(s) restants</p>
<a href="dashboard.php" class="btn btn-dashboard">🏠 Retour au logiciel</a>
<?php endif; ?>

<div class="plan-grid">

<div class="plan-box">
<?php if($plan === 'basic'): ?>
<div class="current-plan">PLAN ACTUEL</div>
<div class="plan-title">Basic</div>
<div class="plan-price">99€ <span style="font-size:14px;">/mois</span></div>
<div class="plan-features">
✅ Jusqu'à 5 chauffeurs<br>
✅ Jusqu'à 3 utilisateurs<br>
✅ Gestion patients<br>
✅ Gestion courses<br>
✅ Planning semaine<br>
✅ Support email
</div>
<span class="btn btn-disabled">✔ Déjà actif</span>
<?php else: ?>
<div class="plan-title">Basic</div>
<div class="plan-price">99€ <span style="font-size:14px;">/mois</span></div>
<div class="plan-features">
✅ Jusqu'à 5 chauffeurs<br>
✅ Jusqu'à 3 utilisateurs<br>
✅ Gestion patients<br>
✅ Gestion courses<br>
✅ Planning semaine<br>
✅ Support email
</div>
<a href="payment.php?plan=basic" class="btn btn-basic">💳 Choisir Basic</a>
<?php endif; ?>
</div>

<div class="plan-box">
<?php if($plan === 'pro'): ?>
<div class="current-plan">PLAN ACTUEL</div>
<div class="plan-title">Pro</div>
<div class="plan-price">149€ <span style="font-size:14px;">/mois</span></div>
<div class="plan-features">
✅ Tout Basic<br>
✅ Jusqu'à 15 chauffeurs<br>
✅ Jusqu'à 10 utilisateurs<br>
✅ GPS chauffeurs<br>
✅ Export CSV<br>
✅ Statistiques avancées
</div>
<span class="btn btn-disabled">✔ Déjà actif</span>
<?php else: ?>
<div class="plan-title">Pro</div>
<div class="plan-price">149€ <span style="font-size:14px;">/mois</span></div>
<div class="plan-features">
✅ Tout Basic<br>
✅ Jusqu'à 15 chauffeurs<br>
✅ Jusqu'à 10 utilisateurs<br>
✅ GPS chauffeurs<br>
✅ Export CSV<br>
✅ Statistiques avancées
</div>
<a href="payment.php?plan=pro" class="btn btn-pro">🚀 Passer Pro</a>
<?php endif; ?>
</div>

<div class="plan-box">
<?php if($plan === 'premium'): ?>
<div class="current-plan">PLAN ACTUEL</div>
<div class="plan-title">Premium</div>
<div class="plan-price">199€ <span style="font-size:14px;">/mois</span></div>
<div class="plan-features">
✅ Tout Pro<br>
✅ Chauffeurs illimités<br>
✅ Utilisateurs illimités<br>
✅ Historique illimité<br>
✅ Support prioritaire<br>
✅ Futures options incluses
</div>
<span class="btn btn-disabled">✔ Déjà actif</span>
<?php else: ?>
<div class="plan-title">Premium</div>
<div class="plan-price">199€ <span style="font-size:14px;">/mois</span></div>
<div class="plan-features">
✅ Tout Pro<br>
✅ Chauffeurs illimités<br>
✅ Utilisateurs illimités<br>
✅ Historique illimité<br>
✅ Support prioritaire<br>
✅ Futures options incluses
</div>
<a href="payment.php?plan=premium" class="btn btn-premium">⭐ Choisir Premium</a>
<?php endif; ?>
</div>

</div>
</div>

<div class="card">

<h2>📄 Historique des paiements</h2>

<?php if(empty($paiements)): ?>

<p>Aucun paiement enregistré.</p>

<?php else: ?>

<table class="table">
<tr>
<th>Date</th>
<th>Plan</th>
<th>Montant</th>
<th>Statut</th>
<th>Facture</th>
</tr>

<?php foreach($paiements as $p): ?>
<tr>
<td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
<td><?= htmlspecialchars(strtoupper($p['plan'])) ?></td>
<td><?= number_format(floatval($p['montant']), 2, ',', ' ') ?> €</td>
<td><?= htmlspecialchars($p['statut']) ?></td>
<td>
<?php if(!empty($p['facture_pdf'])): ?>
<a href="<?= htmlspecialchars($p['facture_pdf']) ?>" target="_blank" class="btn btn-basic">📄 Télécharger</a>
<?php else: ?>
-
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>

<?php endif; ?>

</div>

<div class="footer">
Medigo Synology<br><br>
🔒 Abonnement sécurisé Stripe
</div>

</div>

</body>
</html>