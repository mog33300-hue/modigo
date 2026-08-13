<?php
session_start();
require __DIR__ . '/config.php';

/* =====================================================
   SECURITE MODIGO
===================================================== */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$timeout = 3600;
if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['last_activity'] = time();

/* Recharge l'utilisateur depuis la base afin d'utiliser le rôle réel */
$stmtUser = $pdo->prepare('SELECT id, prenom, email, role, societe_id FROM users WHERE id = ? LIMIT 1');
$stmtUser->execute([(int)$_SESSION['user_id']]);
$connectedUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$connectedUser) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['role'] = $connectedUser['role'] ?? '';
$_SESSION['societe_id'] = $connectedUser['societe_id'] ?? 0;
$_SESSION['prenom'] = $connectedUser['prenom'] ?? '';

$role = strtolower((string)($_SESSION['role'] ?? ''));
if (!in_array($role, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    exit('Accès réservé à l’administration MODIGO.');
}

/* =====================================================
   CHAUFFEURS
===================================================== */
if ($role === 'superadmin') {
    $stmt = $pdo->query("\n        SELECT id, prenom, email, telephone, vehicule, token, societe_id\n        FROM users\n        WHERE role = 'chauffeur'\n        ORDER BY prenom, email\n    ");
} else {
    $stmt = $pdo->prepare("\n        SELECT id, prenom, email, telephone, vehicule, token, societe_id\n        FROM users\n        WHERE role = 'chauffeur' AND societe_id = ?\n        ORDER BY prenom, email\n    ");
    $stmt->execute([(int)$_SESSION['societe_id']]);
}
$chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* URL de base automatique */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'minicartransgps.synology.me';
$directory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/minicartrans')), '/');
$baseUrl = $scheme . '://' . $host . ($directory ? $directory : '');

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Codes Chauffeurs - MODIGO</title>
    <style>
        :root{
            --primary:#2563eb;
            --primary-dark:#1e40af;
            --bg:#f4f7fb;
            --card:#ffffff;
            --text:#172033;
            --muted:#667085;
            --border:#e4e9f2;
            --success:#15803d;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Inter,Arial,sans-serif;
            background:var(--bg);
            color:var(--text);
        }
        .topbar{
            background:linear-gradient(135deg,var(--primary),var(--primary-dark));
            color:#fff;
            padding:22px 18px;
            box-shadow:0 8px 24px rgba(30,64,175,.22);
        }
        .topbar-inner{max-width:1200px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:16px}
        .brand h1{margin:0;font-size:25px}
        .brand p{margin:5px 0 0;opacity:.9;font-size:14px}
        .back{
            color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.45);
            border-radius:12px;padding:10px 14px;font-weight:700;white-space:nowrap;
        }
        main{max-width:1200px;margin:28px auto;padding:0 16px 40px}
        .intro{
            background:#fff;border:1px solid var(--border);border-radius:18px;padding:18px 20px;
            margin-bottom:22px;box-shadow:0 8px 24px rgba(16,24,40,.05)
        }
        .intro strong{color:var(--primary-dark)}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
        .card{
            background:var(--card);border:1px solid var(--border);border-radius:22px;padding:20px;
            box-shadow:0 10px 28px rgba(16,24,40,.07);text-align:center;
        }
        .avatar{
            width:58px;height:58px;border-radius:50%;margin:0 auto 10px;
            display:flex;align-items:center;justify-content:center;background:#eaf1ff;color:var(--primary);
            font-size:27px;font-weight:800;
        }
        h2{margin:0 0 6px;font-size:20px}
        .meta{color:var(--muted);font-size:14px;line-height:1.55;min-height:44px}
        .qr-wrap{margin:16px auto;background:#fff;border:1px solid var(--border);border-radius:16px;padding:12px;width:224px;height:224px;display:flex;align-items:center;justify-content:center}
        .qr-wrap img{width:200px;height:200px;display:block}
        .buttons{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
        .btn{
            border:0;border-radius:12px;padding:12px 10px;font-weight:750;cursor:pointer;text-decoration:none;
            display:flex;align-items:center;justify-content:center;gap:6px;font-size:14px;
        }
        .btn-primary{background:var(--primary);color:#fff}
        .btn-light{background:#eef3fb;color:#24324b}
        .btn-success{background:#eaf8ef;color:var(--success)}
        .full{grid-column:1/-1}
        .missing{padding:22px;border-radius:14px;background:#fff3cd;color:#7a5900;margin-top:15px}
        .empty{background:#fff;border:1px solid var(--border);border-radius:20px;padding:36px;text-align:center;color:var(--muted)}
        .token-note{font-size:12px;color:var(--muted);margin-top:12px}
        @media(max-width:520px){
            .topbar-inner{align-items:flex-start;flex-direction:column}
            .back{width:100%;text-align:center}
            .buttons{grid-template-columns:1fr}
            .full{grid-column:auto}
        }
        @media print{
            body{background:#fff}
            .topbar,.intro,.no-print{display:none!important}
            main{margin:0;max-width:none;padding:0}
            .grid{display:block}
            .card{page-break-after:always;box-shadow:none;border:none;padding-top:35px}
            .card:last-child{page-break-after:auto}
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <div class="brand">
            <h1>🚑 MODIGO — QR Codes Chauffeurs</h1>
            <p>Connexion rapide et sécurisée à l’espace mobile</p>
        </div>
        <a class="back" href="chauffeurs.php">← Retour aux chauffeurs</a>
    </div>
</header>

<main>
    <div class="intro">
        Bonjour <strong><?= h($_SESSION['prenom'] ?? 'Administrateur') ?></strong>. Présente le QR Code au chauffeur : il le scanne avec son téléphone et son espace MODIGO s’ouvre directement.
    </div>

    <?php if (!$chauffeurs): ?>
        <div class="empty">Aucun chauffeur n’est enregistré pour cette société.</div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($chauffeurs as $chauffeur): ?>
                <?php
                    $prenom = trim((string)($chauffeur['prenom'] ?? ''));
                    $email = trim((string)($chauffeur['email'] ?? ''));
                    $token = trim((string)($chauffeur['token'] ?? ''));
                    $mobileUrl = $token !== '' ? $baseUrl . '/chauffeur_mobile.php?token=' . rawurlencode($token) : '';
                    $qrUrl = $mobileUrl !== ''
                        ? 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=12&data=' . rawurlencode($mobileUrl)
                        : '';
                    $initial = $prenom !== '' ? mb_strtoupper(mb_substr($prenom, 0, 1)) : 'C';
                ?>
                <section class="card">
                    <div class="avatar"><?= h($initial) ?></div>
                    <h2><?= h($prenom !== '' ? $prenom : 'Chauffeur') ?></h2>
                    <div class="meta">
                        <?= h($email) ?><br>
                        <?= h($chauffeur['vehicule'] ?? 'Véhicule non renseigné') ?>
                    </div>

                    <?php if ($mobileUrl !== ''): ?>
                        <div class="qr-wrap">
                            <img id="qr-<?= (int)$chauffeur['id'] ?>" src="<?= h($qrUrl) ?>" alt="QR Code de <?= h($prenom) ?>">
                        </div>

                        <div class="buttons no-print">
                            <a class="btn btn-primary" href="<?= h($mobileUrl) ?>" target="_blank" rel="noopener">📱 Ouvrir</a>
                            <button class="btn btn-light" type="button" onclick="printCard(this)">🖨️ Imprimer</button>
                            <button class="btn btn-success full" type="button" onclick="downloadQr('<?= (int)$chauffeur['id'] ?>','<?= h($prenom !== '' ? $prenom : 'chauffeur') ?>')">⬇️ Télécharger le QR Code</button>
                        </div>
                        <div class="token-note">Le token reste contenu dans le QR Code et n’est pas affiché à l’écran.</div>
                    <?php else: ?>
                        <div class="missing">Ce chauffeur n’a pas encore de token. Il faut lui en générer un dans la base avant de créer son QR Code.</div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
function printCard(button) {
    const card = button.closest('.card');
    const allCards = document.querySelectorAll('.card');
    allCards.forEach(item => {
        if (item !== card) item.dataset.hiddenBeforePrint = item.style.display || '';
        if (item !== card) item.style.display = 'none';
    });
    window.print();
    allCards.forEach(item => {
        if (item !== card) item.style.display = item.dataset.hiddenBeforePrint || '';
    });
}

async function downloadQr(id, name) {
    const img = document.getElementById('qr-' + id);
    if (!img) return;
    try {
        const response = await fetch(img.src);
        if (!response.ok) throw new Error('Téléchargement impossible');
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'MODIGO_QR_' + name.replace(/[^a-z0-9_-]/gi, '_') + '.png';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    } catch (error) {
        window.open(img.src, '_blank');
    }
}
</script>
</body>
</html>
