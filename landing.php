<?php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Medigo Synology - Medigo Synology</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial,sans-serif;
    background:#f3f4f6;
    color:#1f2937;
}

header{
    background:#2563eb;
    color:white;
    padding:20px;
}

.header-content{
    max-width:1200px;
    margin:auto;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.logo{
    font-size:28px;
    font-weight:bold;
}

.btn-login{
    background:white;
    color:#2563eb;
    padding:12px 18px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
}

.hero{
    max-width:1200px;
    margin:auto;
    text-align:center;
    padding:80px 20px 50px;
}

.hero h1{
    font-size:52px;
    margin-bottom:10px;
    color:#111827;
}

.hero .brand{
    color:#2563eb;
    font-weight:bold;
    margin-bottom:25px;
}

.hero p{
    font-size:22px;
    color:#6b7280;
    margin-bottom:35px;
}

.btn-primary{
    background:#16a34a;
    color:white;
    text-decoration:none;
    padding:18px 28px;
    border-radius:12px;
    font-size:18px;
    font-weight:bold;
    display:inline-block;
}

.hero-note{
    margin-top:18px;
    color:#6b7280;
    font-size:15px;
    line-height:1.8;
}

.features{
    max-width:1200px;
    margin:40px auto;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
    padding:20px;
}

.feature{
    background:white;
    border-radius:15px;
    padding:25px;
    box-shadow:0 3px 12px rgba(0,0,0,.08);
}

.feature h3{
    margin-bottom:10px;
}

.pricing{
    max-width:1200px;
    margin:70px auto;
    text-align:center;
    padding:20px;
}

.pricing h2{
    margin-bottom:10px;
    font-size:34px;
}

.pricing-subtitle{
    color:#6b7280;
    margin-bottom:35px;
}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:25px;
    align-items:stretch;
}

.card{
    background:white;
    border-radius:18px;
    padding:30px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
    position:relative;
}

.card-popular{
    border:3px solid #2563eb;
    transform:scale(1.02);
}

.popular{
    background:#2563eb;
    color:white;
    padding:9px;
    border-radius:10px;
    margin-bottom:18px;
    font-weight:bold;
}

.card h3{
    font-size:26px;
}

.price{
    font-size:42px;
    color:#2563eb;
    margin:20px 0 5px;
    font-weight:bold;
}

.per-month{
    color:#6b7280;
    margin-bottom:20px;
}

.target{
    font-weight:bold;
    margin-bottom:20px;
}

.features-list{
    text-align:left;
    line-height:1.9;
    margin-bottom:25px;
}

.btn-plan{
    display:inline-block;
    background:#2563eb;
    color:white;
    padding:14px 20px;
    border-radius:10px;
    text-decoration:none;
    margin-top:15px;
    font-weight:bold;
}

.btn-plan-premium{
    background:#111827;
}

.trust{
    max-width:1200px;
    margin:40px auto;
    padding:20px;
    text-align:center;
}

.trust-box{
    background:white;
    border-radius:18px;
    padding:30px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
}

.trust-box h2{
    margin-bottom:15px;
}

.trust-box p{
    color:#6b7280;
    line-height:1.8;
}

footer{
    margin-top:80px;
    background:#111827;
    color:white;
    text-align:center;
    padding:30px;
}

footer a{
    color:white;
    text-decoration:none;
    margin:0 8px;
}

@media(max-width:768px){
    .header-content{
        flex-direction:column;
        gap:15px;
    }

    .hero h1{
        font-size:40px;
    }

    .card-popular{
        transform:none;
    }
}
</style>
</head>

<body>

<header>
<div class="header-content">

<div class="logo">
🏢 Medigo Synology
</div>

<a href="login.php" class="btn-login">
🔐 Connexion
</a>

</div>
</header>

<section class="hero">

<h1>🚑 Medigo Synology</h1>

<div class="brand">
par Medigo Synology
</div>

<p>
Solution SaaS de gestion VSL, Taxi, TPMR et Ambulances
</p>

<a href="inscription_societe.php" class="btn-primary">
🆓 Essai gratuit 30 jours
</a>
<p style="
margin-top:25px;
font-size:22px;
font-weight:600;
color:#1f2937;
">
Gagnez du temps chaque jour dans la gestion de vos transports.
</p>

<p style="
margin-top:10px;
font-size:18px;
color:#6b7280;
">
Patients, chauffeurs, planning et courses centralisés sur une seule plateforme.
</p>
<div class="hero-note">
✓ Sans engagement<br>
✓ Sans carte bancaire pendant l'essai<br>
✓ Activation immédiate
</div>

</section>

<section class="features">

<div class="feature">
<h3>📅 Planning intelligent</h3>
<p>Organisation complète des chauffeurs et des courses.</p>
</div>

<div class="feature">
<h3>🚑 Gestion des patients</h3>
<p>Suivi des patients sans stockage inutile de données sensibles.</p>
</div>

<div class="feature">
<h3>📍 GPS temps réel</h3>
<p>Suivi des chauffeurs et des courses en direct selon votre abonnement.</p>
</div>

<div class="feature">
<h3>📊 Statistiques</h3>
<p>Analyse de l'activité, historique et suivi des courses.</p>
</div>

<div class="feature">
<h3>👥 Multi-utilisateurs</h3>
<p>Admin, gérant, contrôleur et chauffeur.</p>
</div>

<div class="feature">
<h3>☁ SaaS sécurisé</h3>
<p>Accessible partout depuis internet, avec séparation par société.</p>
</div>

</section>

<section class="pricing">

<h2>💳 Nos abonnements</h2>

<p class="pricing-subtitle">
Choisissez l'offre adaptée à la taille de votre structure.
</p>

<div class="cards">

<div class="card">

<h3>Basic</h3>

<div class="price">99€</div>

<div class="per-month">
/ mois
</div>

<div class="target">
Petites structures
</div>

<div class="features-list">
✅ Jusqu'à 5 chauffeurs<br>
✅ Jusqu'à 3 utilisateurs<br>
✅ Gestion patients<br>
✅ Gestion courses<br>
✅ Planning semaine<br>
✅ Historique des courses<br>
✅ Support email
</div>

<a href="inscription_societe.php?plan=basic" class="btn-plan">
Choisir Basic
</a>

</div>

<div class="card card-popular">

<div class="popular">
⭐ Offre la plus populaire
</div>

<h3>Pro</h3>

<div class="price">149€</div>

<div class="per-month">
/ mois
</div>

<div class="target">
Sociétés en croissance
</div>

<div class="features-list">
✅ Jusqu'à 15 chauffeurs<br>
✅ Jusqu'à 10 utilisateurs<br>
✅ Gestion patients<br>
✅ Gestion courses<br>
✅ Planning semaine<br>
✅ GPS chauffeurs<br>
✅ Export CSV<br>
✅ Statistiques avancées<br>
✅ Support prioritaire email
</div>

<a href="inscription_societe.php?plan=pro" class="btn-plan">
Choisir Pro
</a>

</div>

<div class="card">

<h3>Premium</h3>

<div class="price">199€</div>

<div class="per-month">
/ mois
</div>

<div class="target">
Solution complète
</div>

<div class="features-list">
✅ Chauffeurs illimités<br>
✅ Utilisateurs illimités<br>
✅ Gestion patients<br>
✅ Gestion courses<br>
✅ Planning semaine<br>
✅ GPS chauffeurs<br>
✅ Export CSV<br>
✅ Statistiques avancées<br>
✅ Historique illimité<br>
✅ Support prioritaire<br>
✅ Futures évolutions incluses
</div>

<a href="inscription_societe.php?plan=premium" class="btn-plan btn-plan-premium">
Choisir Premium
</a>

</div>

</div>

</section>

<section class="trust">

<div class="trust-box">

<h2>Pourquoi choisir Medigo Synology ?</h2>

<p>
Medigo Synology est pensé pour les professionnels du transport sanitaire :
VSL, Taxi conventionné, TPMR et ambulances.
Centralisez vos patients, vos chauffeurs, vos courses, vos plannings,
vos statistiques et vos factures sur une seule plateforme simple.
</p>

</div>

</section>

<footer>

<p>
© <?= date('Y') ?> Medigo Synology - Tous droits réservés
</p>

<br>

<a href="contact.php">📧 Contact</a>
|
<a href="mentions_legales.php">📜 Mentions légales</a>
|
<a href="rgpd.php">🔒 RGPD</a>
|
<a href="cgv.php">📄 CGV</a>

<br><br>

<a href="login.php">🔐 Connexion</a>

</footer>

</body>
</html>