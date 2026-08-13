<?php
require 'auth.php';
require 'config.php';

$user_id = intval($_SESSION['user_id'] ?? 0);
$societe_id = intval($_SESSION['societe_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT u.*, s.nom AS societe_nom
    FROM users u
    LEFT JOIN societes s ON s.id = u.societe_id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

function count_sql(PDO $pdo, string $sql, array $params = []): int
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$courses_today = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM courses
     WHERE societe_id = ?
     AND date_course = CURDATE()",
    [$societe_id]
);

$courses_week = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM courses
     WHERE societe_id = ?
     AND YEARWEEK(date_course, 1) = YEARWEEK(CURDATE(), 1)",
    [$societe_id]
);

$patients = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM patients
     WHERE societe_id = ?",
    [$societe_id]
);

$chauffeurs = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM users
     WHERE societe_id = ?
     AND role = 'chauffeur'",
    [$societe_id]
);

$vehicules = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM vehicles
     WHERE company_id = ?",
    [$societe_id]
);

$missions = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM courses
     WHERE societe_id = ?
     AND statut = 'en cours'",
    [$societe_id]
);

$incidents = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM courses
     WHERE societe_id = ?
     AND statut = 'incident'",
    [$societe_id]
);

$retards = count_sql(
    $pdo,
    "
    SELECT COUNT(*)
    FROM courses
    WHERE societe_id = ?
    AND depart_reel IS NULL
    AND date_course IS NOT NULL
    AND heure_pickup IS NOT NULL
    AND CONCAT(date_course, ' ', heure_pickup) < NOW()
    AND statut NOT IN (
        'terminée',
        'terminee',
        'terminé',
        'termine',
        'TERMINEE',
        'TERMINE',
        'incident',
        'en cours'
    )
    ",
    [$societe_id]
);

$late_courses = [];

try {
    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.client_nom,
            c.date_course,
            c.heure_pickup,
            c.adresse_depart,
            c.adresse_arrivee,
            c.statut,
            u.prenom AS chauffeur,
            TIMESTAMPDIFF(
                MINUTE,
                CONCAT(c.date_course, ' ', c.heure_pickup),
                NOW()
            ) AS minutes_retard
        FROM courses c
        LEFT JOIN users u ON u.id = c.chauffeur_id
        WHERE c.societe_id = ?
        AND c.depart_reel IS NULL
        AND c.date_course IS NOT NULL
        AND c.heure_pickup IS NOT NULL
        AND CONCAT(c.date_course, ' ', c.heure_pickup) < NOW()
        AND c.statut NOT IN (
            'terminée',
            'terminee',
            'terminé',
            'termine',
            'TERMINEE',
            'TERMINE',
            'incident',
            'en cours'
        )
        ORDER BY minutes_retard DESC
        LIMIT 5
    ");

    $stmt->execute([$societe_id]);
    $late_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $late_courses = [];
}

$next_courses = [];

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.prenom AS chauffeur
        FROM courses c
        LEFT JOIN users u ON u.id = c.chauffeur_id
        WHERE c.societe_id = ?
        ORDER BY c.date_course ASC, c.heure_pickup ASC
        LIMIT 8
    ");

    $stmt->execute([$societe_id]);
    $next_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $next_courses = [];
}

$societe_nom = $user['societe_nom'] ?? 'MODIGO';
$prenom = $user['prenom'] ?? 'Utilisateur';

$en_attente = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM courses
     WHERE societe_id = ?
     AND statut IN ('prévue', 'prévu', 'prevue', 'prevu')",
    [$societe_id]
);

$terminees_today = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM courses
     WHERE societe_id = ?
     AND date_course = CURDATE()
     AND statut IN (
        'terminée',
        'terminee',
        'terminé',
        'termine',
        'TERMINEE',
        'TERMINE'
     )",
    [$societe_id]
);

$gps_actifs = count_sql(
    $pdo,
    "SELECT COUNT(*) FROM courses
     WHERE societe_id = ?
     AND latitude IS NOT NULL
     AND longitude IS NOT NULL
     AND statut NOT IN (
        'terminée',
        'terminee',
        'terminé',
        'termine',
        'TERMINEE',
        'TERMINE'
     )",
    [$societe_id]
);

$vehicules_disponibles = max(
    0,
    intval($vehicules) - intval($missions)
);

$page_title = 'MODIGO - Centre de supervision 3.5.001';
$modigo_page_class = 'dashboard-premium-v2';

$modigo_extra_head = '
<link rel="stylesheet" href="assets/css/dashboard_v2.css?v=3.5.001">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';
?>

<div class="dpv2">

    <header class="dpv2-top">

        <div>
            <span>Centre de commandement</span>
            <h1>Centre de supervision</h1>

            <p>
                <?= htmlspecialchars($societe_nom) ?>
                ·
                <?= date('d/m/Y H:i') ?>
            </p>
        </div>

        <div class="dpv2-user">
            <b><?= htmlspecialchars($prenom) ?></b>
            <small>Session active · Sprint 3.5.001</small>
        </div>

    </header>

    <section class="dpv2-hero">

        <div>

            <em>Régulation sanitaire intelligente</em>

            <h2>Tout le service en un coup d'œil</h2>

            <p>
                Courses, chauffeurs, patients, véhicules, planning,
                retards, incidents et GPS réunis dans un seul centre
                de pilotage.
            </p>

            <div class="dpv2-actions">

                <a class="btn btn-white" href="create_course.php">
                    ➕ Nouvelle course
                </a>

                <a class="btn btn-glass" href="gps_admin.php">
                    📍 Centre GPS
                </a>

                <a class="btn btn-glass" href="planning_global.php">
                    📅 Planning
                </a>

            </div>

        </div>

        <div class="dpv2-priority">

            <div>
                <span>🟠 En mission</span>
                <strong><?= intval($missions) ?></strong>
            </div>

            <div>
                <span>🔴 Retards</span>
                <strong><?= intval($retards) ?></strong>
            </div>

            <div>
                <span>🚨 Incidents</span>
                <strong><?= intval($incidents) ?></strong>
            </div>

            <div>
                <span>🟢 Disponibles</span>
                <strong>
                    <?= max(0, intval($chauffeurs) - intval($missions)) ?>
                </strong>
            </div>

        </div>

    </section>

    <section class="dpv2-stats">

        <a href="courses.php">
            <span>🚑</span>
            <small>Courses aujourd'hui</small>
            <strong><?= intval($courses_today) ?></strong>
        </a>

        <a href="courses.php?statut=prévue">
            <span>🕒</span>
            <small>En attente</small>
            <strong><?= intval($en_attente) ?></strong>
        </a>

        <a href="patients.php">
            <span>👥</span>
            <small>Patients</small>
            <strong><?= intval($patients) ?></strong>
        </a>

        <a href="chauffeurs.php">
            <span>🚘</span>
            <small>Chauffeurs</small>
            <strong><?= intval($chauffeurs) ?></strong>
        </a>

        <a href="vehicles.php">
            <span>🚐</span>
            <small>Véhicules disponibles</small>
            <strong><?= intval($vehicules_disponibles) ?></strong>
        </a>

        <a href="gps_admin.php">
            <span>📍</span>
            <small>GPS actifs</small>
            <strong><?= intval($gps_actifs) ?></strong>
        </a>

    </section>

    <section class="dpv2-grid">

        <!-- SUPERVISION GPS -->
        <div class="dpv2-panel dpv2-map">

            <div class="dpv2-head">

                <div>
                    <small>Supervision cartographique</small>
                    <h2>📍 Supervision GPS en temps réel</h2>
                </div>

                <a class="btn btn-glass" href="gps_admin.php">
                    Ouvrir le GPS
                </a>

            </div>

            <div id="dashboardMap"></div>

            <div class="dpv2-mapmsg">

    <b>📡 État du réseau GPS</b>

    <?php if ($gps_actifs <= 0): ?>

        <span class="gps-offline">
            🟠 0 chauffeur connecté
        </span>

        <small>
            Aucun chauffeur connecté pour l'instant.
        </small>

    <?php elseif ($gps_actifs == 1): ?>

        <span class="gps-online">
            🟢 1 chauffeur connecté
        </span>

        <small>
            Dernière position GPS reçue à <?= date('H:i') ?>.
        </small>

    <?php else: ?>

        <span class="gps-online">
            🟢 <?= intval($gps_actifs) ?> chauffeurs connectés
        </span>

        <small>
            Dernière position GPS reçue à <?= date('H:i') ?>.
        </small>

    <?php endif; ?>

</div>

            </div>

            <div class="dpv2-alerts">

                <div class="dpv2-alerts-title">
                    <div>
                        <small>Priorités régulation</small>
                        <h2>⚠️ Alertes</h2>
                    </div>

                    <a href="planning_global.php">Voir le planning</a>
                </div>

                <?php if ($incidents > 0): ?>

                    <a class="danger dpv2-alert-main" href="gps_admin.php">
                        <span class="dpv2-alert-icon">🚨</span>

                        <span>
                            <b><?= intval($incidents) ?> incident(s)</b>
                            <small>Intervention nécessaire.</small>
                        </span>
                    </a>

                <?php endif; ?>

                <?php if (!empty($late_courses)): ?>

                    <div class="dpv2-delay-list">

                        <div class="dpv2-delay-summary">
                            <span>⏰</span>

                            <div>
                                <b><?= intval($retards) ?> retard(s) détecté(s)</b>
                                <small>Courses nécessitant une vérification.</small>
                            </div>
                        </div>

                        <?php foreach ($late_courses as $late): ?>

                            <a
                                class="dpv2-delay-item"
                                href="edit_course.php?id=<?= intval($late['id']) ?>"
                            >
                                <div class="dpv2-delay-time">
                                    <?= !empty($late['heure_pickup'])
                                        ? htmlspecialchars(substr($late['heure_pickup'], 0, 5))
                                        : '--:--'
                                    ?>
                                </div>

                                <div class="dpv2-delay-info">
                                    <b>
                                        <?= htmlspecialchars(
                                            $late['client_nom'] ?? 'Patient non renseigné'
                                        ) ?>
                                    </b>

                                    <small>
                                        <?= htmlspecialchars(
                                            $late['chauffeur'] ?? 'Chauffeur non assigné'
                                        ) ?>
                                    </small>
                                </div>

                                <strong class="dpv2-delay-badge">
                                    +<?= max(1, intval($late['minutes_retard'] ?? 0)) ?> min
                                </strong>
                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php elseif ($retards > 0): ?>

                    <a class="warning dpv2-alert-main" href="planning_global.php">
                        <span class="dpv2-alert-icon">⏰</span>

                        <span>
                            <b><?= intval($retards) ?> retard(s)</b>
                            <small>Consultez le planning.</small>
                        </span>
                    </a>

                <?php endif; ?>

                <?php if ($incidents === 0 && $retards === 0): ?>

                    <div class="success dpv2-alert-main">
                        <span class="dpv2-alert-icon">🟢</span>

                        <span>
                            <b>Aucune alerte critique</b>
                            <small>Le service fonctionne normalement.</small>
                        </span>
                    </div>

                <?php endif; ?>

            </div>

            <div class="dpv2-availability">

                <div>
                    <small>Chauffeurs disponibles</small>
                    <strong>
                        <?= max(0, intval($chauffeurs) - intval($missions)) ?>
                    </strong>
                </div>

                <div>
                    <small>Véhicules disponibles</small>
                    <strong><?= intval($vehicules_disponibles) ?></strong>
                </div>

            </div>

        </div>

    </section>

    <section class="dpv2-grid bottom">

        <!-- PROCHAINES COURSES -->
        <div class="dpv2-panel">

            <div class="dpv2-head">

                <div>
                    <small>Planning opérationnel</small>
                    <h2>🚑 Prochaines courses</h2>
                </div>

                <a class="btn btn-glass" href="planning_global.php">
                    Voir le planning
                </a>

            </div>

            <div class="dpv2-courses">

                <?php if (empty($next_courses)): ?>

                    <div class="dpv2-empty">
                        Aucune course planifiée.
                    </div>

                <?php else: ?>

                    <?php foreach (array_slice($next_courses, 0, 5) as $c): ?>

                        <a href="edit_course.php?id=<?= intval($c['id']) ?>">

                            <div>

                                <b>
                                    <?= !empty($c['heure_pickup'])
                                        ? htmlspecialchars(substr($c['heure_pickup'], 0, 5))
                                        : '--:--'
                                    ?>
                                </b>

                                <small>
                                    <?= !empty($c['date_course'])
                                        ? date('d/m', strtotime($c['date_course']))
                                        : '--/--'
                                    ?>
                                </small>

                            </div>

                            <div>

                                <b>
                                    <?= htmlspecialchars($c['client_nom'] ?? '-') ?>
                                </b>

                                <small>
                                    <?= htmlspecialchars($c['chauffeur'] ?? 'Non assigné') ?>
                                    ·
                                    <?= htmlspecialchars($c['adresse_arrivee'] ?? '-') ?>
                                </small>

                            </div>

                            <span>
                                <?= htmlspecialchars($c['statut'] ?? 'Prévue') ?>
                            </span>

                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

        <!-- ACCÈS RAPIDES -->
        <div class="dpv2-panel">

            <div class="dpv2-head">

                <div>
                    <small>Actions fréquentes</small>
                    <h2>⚡ Accès rapides</h2>
                </div>

            </div>

            <div class="dpv2-quick">

                <a href="create_course.php">
                    ➕
                    <b>Nouvelle course</b>
                </a>

                <a href="add_patient.php">
                    👤
                    <b>Nouveau patient</b>
                </a>

                <a href="add_chauffeur.php">
                    🚘
                    <b>Nouveau chauffeur</b>
                </a>

                <a href="vehicles.php">
                    🚐
                    <b>Véhicules</b>
                </a>

                <a href="planning_global.php">
                    📅
                    <b>Planning</b>
                </a>

                <a href="historique.php">
                    📊
                    <b>Historique</b>
                </a>

            </div>

            <div class="dpv2-summary">

                <div>
                    <small>Courses semaine</small>
                    <strong><?= intval($courses_week) ?></strong>
                </div>

                <div>
                    <small>Terminées aujourd'hui</small>
                    <strong><?= intval($terminees_today) ?></strong>
                </div>

            </div>

        </div>

    </section>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(function () {

    if (typeof L === 'undefined') {
        return;
    }

    const map = L.map('dashboardMap', {
        zoomControl: false
    }).setView([44.8378, -0.5792], 10);

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }
    ).addTo(map);

    setTimeout(function () {
        map.invalidateSize();
    }, 300);

    // Actualisation automatique du centre de supervision toutes les 60 secondes.
    window.setTimeout(function () {
        window.location.reload();
    }, 60000);

})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>