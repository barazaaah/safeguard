<?php

require_once  '../config/db.php';
require_once  '../config/escalation.php';

try {
    $count = checkEmergencyEscalations($conn);

    echo "SafeGuard escalation check completed.\n";
    echo "Alerts checked and escalated: " . $count . "\n";

} catch (PDOException $e) {
    error_log("SafeGuard cron error: " . $e->getMessage());
    echo "An error occurred while checking alerts.\n";
}

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Access denied');
}