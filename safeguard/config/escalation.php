<?php

function checkEmergencyEscalations($conn)
{
    
    $settingsStmt = $conn->query("
        SELECT *
        FROM escalation_settings
        WHERE id = 1
        LIMIT 1
    ");

    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

    
    if (!$settings || (int)$settings['auto_escalation_enabled'] !== 1) {
        return 0;
    }

    
    $minutes = max(1, min(60, (int)$settings['response_minutes']));

    $contactName = trim($settings['police_contact_name'] ?? '');
    $contactPhone = trim($settings['police_phone'] ?? '');

    $recipient = $contactName !== ''
        ? $contactName
        : 'Configured emergency contact (demo)';

    if ($contactPhone !== '') {
        $recipient .= ' - ' . $contactPhone;
    }

    
    $stmt = $conn->query("
        SELECT
            a.id,
            a.incident_type,
            a.description,
            a.location,
            a.latitude,
            a.longitude,
            a.created_at,
            u.fullname,
            u.phone
        FROM alerts a
        JOIN users u ON u.id = a.user_id
        WHERE a.status = 'Pending'
          AND a.acknowledged_at IS NULL
          AND a.escalation_status = 'Waiting'
          AND a.created_at <= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)
    ");

    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $escalatedCount = 0;

    foreach ($alerts as $alert) {

        try {
            $conn->beginTransaction();

            
            $update = $conn->prepare("
                UPDATE alerts
                SET escalation_status = 'Escalated',
                    escalated_at = NOW()
                WHERE id = ?
                  AND status = 'Pending'
                  AND acknowledged_at IS NULL
                  AND escalation_status = 'Waiting'
            ");

            $update->execute([$alert['id']]);

            if ($update->rowCount() === 0) {
                $conn->commit();
                continue;
            }

            
            if (
                $alert['latitude'] !== null &&
                $alert['longitude'] !== null &&
                $alert['latitude'] !== '' &&
                $alert['longitude'] !== ''
            ) {
                $location = "https://www.google.com/maps?q="
                    . urlencode(
                        $alert['latitude'] . ',' . $alert['longitude']
                    );
            } else {
                $location = $alert['location'] ?: 'Location not provided';
            }

            
            $message =
                "SAFEGUARD EMERGENCY ESCALATION\n" .
                "Alert ID: " . $alert['id'] . "\n" .
                "Person: " . $alert['fullname'] . "\n" .
                "Phone: " . $alert['phone'] . "\n" .
                "Incident: " . $alert['incident_type'] . "\n" .
                "Description: " . $alert['description'] . "\n" .
                "Location: " . $location . "\n" .
                "Time: " . $alert['created_at'] . "\n" .
                "No administrator acknowledgement within "
                . $minutes . " minutes.";

            
            $log = $conn->prepare("
                INSERT INTO escalation_logs
                    (alert_id, recipient, message, delivery_status)
                VALUES (?, ?, ?, 'Simulated')
            ");

            $log->execute([
                $alert['id'],
                $recipient,
                $message
            ]);

            $conn->commit();
            $escalatedCount++;

        } catch (Exception $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            error_log(
                "SafeGuard escalation failed for alert "
                . $alert['id'] . ": " . $e->getMessage()
            );
        }
    }

    return $escalatedCount;
}
