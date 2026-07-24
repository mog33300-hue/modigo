<?php
// API GPS : cette page doit toujours répondre en JSON, jamais en HTML.
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function json_response(array $data, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Ne pas charger auth.php ici : il peut rediriger vers une page HTML.
    require_once __DIR__ . '/config.php';

    if (empty($_SESSION['user_id'])) {
        json_response(['ok' => false, 'message' => 'Session expirée. Reconnectez-vous.'], 401);
    }

    $chauffeurId = (int) $_SESSION['user_id'];
    $societeId   = (int) ($_SESSION['societe_id'] ?? 0);
    $role        = (string) ($_SESSION['role'] ?? '');

    if ($societeId <= 0 || !in_array($role, ['chauffeur', 'admin', 'superadmin'], true)) {
        json_response(['ok' => false, 'message' => 'Accès GPS refusé.'], 403);
    }

    $raw = file_get_contents('php://input');
    $input = [];

    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            json_response(['ok' => false, 'message' => 'Données JSON invalides.'], 400);
        }
        $input = $decoded;
    } else {
        $input = $_POST;
    }

    $action = (string) ($input['action'] ?? 'position');

    if ($action === 'service') {
        $status = (string) ($input['status'] ?? 'hors_service');
        if (!in_array($status, ['hors_service', 'en_service', 'pause'], true)) {
            json_response(['ok' => false, 'message' => 'Statut de service invalide.'], 422);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO chauffeur_positions
             (societe_id, chauffeur_id, latitude, longitude, service_status, updated_at)
             VALUES (?, ?, 0, 0, ?, NOW())
             ON DUPLICATE KEY UPDATE
                service_status = VALUES(service_status),
                updated_at = NOW()"
        );
        $stmt->execute([$societeId, $chauffeurId, $status]);

        json_response([
            'ok' => true,
            'status' => $status,
            'received_at' => date('H:i:s')
        ]);
    }

    $lat = filter_var($input['lat'] ?? null, FILTER_VALIDATE_FLOAT);
    $lng = filter_var($input['lng'] ?? null, FILTER_VALIDATE_FLOAT);

    if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        json_response(['ok' => false, 'message' => 'Coordonnées GPS invalides.'], 422);
    }

    $accuracy = is_numeric($input['accuracy'] ?? null) ? (float) $input['accuracy'] : null;
    $speed    = is_numeric($input['speed'] ?? null) ? max(0, (float) $input['speed']) : null;
    $heading  = is_numeric($input['heading'] ?? null) ? (float) $input['heading'] : null;
    $battery  = is_numeric($input['battery'] ?? null) ? max(0, min(100, (int) $input['battery'])) : null;
    $network  = substr(trim((string) ($input['network'] ?? '')), 0, 30);
    $status   = (string) ($input['status'] ?? 'en_service');

    if (!in_array($status, ['hors_service', 'en_service', 'pause'], true)) {
        $status = 'en_service';
    }

    $stmt = $pdo->prepare(
        "INSERT INTO chauffeur_positions
         (societe_id, chauffeur_id, latitude, longitude, accuracy, speed, heading,
          battery_level, network_type, service_status, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            accuracy = VALUES(accuracy),
            speed = VALUES(speed),
            heading = VALUES(heading),
            battery_level = VALUES(battery_level),
            network_type = VALUES(network_type),
            service_status = VALUES(service_status),
            updated_at = NOW()"
    );

    $stmt->execute([
        $societeId, $chauffeurId, $lat, $lng, $accuracy, $speed,
        $heading, $battery, $network, $status
    ]);

    // Compatibilité avec l’ancienne supervision.
    $stmt = $pdo->prepare(
        "UPDATE courses
         SET latitude = ?, longitude = ?
         WHERE societe_id = ?
           AND chauffeur_id = ?
           AND statut NOT IN ('terminée','terminee','terminé','termine','TERMINEE','TERMINE')"
    );
    $stmt->execute([$lat, $lng, $societeId, $chauffeurId]);

    json_response([
        'ok' => true,
        'received_at' => date('H:i:s')
    ]);

} catch (Throwable $e) {
    error_log('save_position.php : ' . $e->getMessage());
    json_response([
        'ok' => false,
        'message' => 'Erreur serveur GPS : ' . $e->getMessage()
    ], 500);
}
