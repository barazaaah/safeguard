<?php
require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../config/escalation.php';

requireAdmin();

// Backup check whenever the admin dashboard is opened.
// The Windows Task Scheduler should still run the checker every minute.
try {
    checkEmergencyEscalations($conn);
} catch (Exception $e) {
    error_log("Dashboard escalation check: " . $e->getMessage());
}

// Dashboard statistics
$totalUsers = (int)$conn->query(
    "SELECT COUNT(*) FROM users WHERE role = 'user'"
)->fetchColumn();

$totalAdmins = (int)$conn->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
)->fetchColumn();

$totalAlerts = (int)$conn->query(
    "SELECT COUNT(*) FROM alerts"
)->fetchColumn();

$pendingAlerts = (int)$conn->query(
    "SELECT COUNT(*) FROM alerts WHERE status = 'Pending'"
)->fetchColumn();

$respondingAlerts = (int)$conn->query(
    "SELECT COUNT(*) FROM alerts WHERE status = 'Responding'"
)->fetchColumn();

$resolvedAlerts = (int)$conn->query(
    "SELECT COUNT(*) FROM alerts WHERE status = 'Resolved'"
)->fetchColumn();

$escalatedAlerts = (int)$conn->query(
    "SELECT COUNT(*) FROM alerts WHERE escalation_status = 'Escalated'"
)->fetchColumn();

// Recent emergency alerts
$stmt = $conn->query("
    SELECT
        a.id,
        a.incident_type,
        a.status,
        a.escalation_status,
        a.created_at,
        u.fullname,
        u.phone
    FROM alerts a
    JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC
    LIMIT 8
");

$recentAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent escalation logs
$recentEscalations = $conn->query("
    SELECT
        e.alert_id,
        e.recipient,
        e.delivery_status,
        e.created_at,
        u.fullname
    FROM escalation_logs e
    JOIN alerts a ON a.id = e.alert_id
    JOIN users u ON u.id = a.user_id
    ORDER BY e.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$adminName = $_SESSION['fullname'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SafeGuard</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .admin-dashboard {
            min-height: 100vh;
            background: #f3f6fa;
            display: flex;
        }

        .admin-sidebar {
            width: 245px;
            background: #10283f;
            color: white;
            padding: 25px 16px;
            flex-shrink: 0;
        }

        .admin-brand {
            font-size: 25px;
            font-weight: 800;
            margin: 5px 12px 35px;
        }

        .admin-brand span {
            color: #ff5964;
        }

        .admin-sidebar a {
            display: block;
            color: #d5e1ed;
            text-decoration: none;
            padding: 13px 14px;
            margin-bottom: 7px;
            border-radius: 8px;
            font-size: 14px;
        }

        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            background: #21415e;
            color: white;
        }

        .admin-main {
            flex: 1;
            min-width: 0;
            padding: 28px;
        }

        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .admin-topbar h1 {
            margin: 0;
            color: #10283f;
            font-size: 27px;
        }

        .admin-topbar p {
            color: #64748b;
            margin: 7px 0 0;
        }

        .admin-profile {
            background: white;
            border: 1px solid #e3eaf1;
            border-radius: 10px;
            padding: 12px 17px;
            color: #10283f;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .admin-stat {
            background: white;
            border-radius: 13px;
            padding: 21px;
            box-shadow: 0 3px 12px rgba(16,40,63,.05);
            border: 1px solid #e8edf3;
        }

        .admin-stat small {
            color: #64748b;
            font-weight: 600;
        }

        .admin-stat h2 {
            font-size: 30px;
            margin: 12px 0 0;
            color: #10283f;
        }

        .admin-stat.danger {
            border-left: 4px solid #d92d3a;
        }

        .admin-stat.warning {
            border-left: 4px solid #ed9b25;
        }

        .admin-stat.success {
            border-left: 4px solid #169b70;
        }

        .admin-stat.info {
            border-left: 4px solid #087f8c;
        }

        .escalation-warning {
            background: #fff0f0;
            border: 1px solid #f5c5c8;
            border-left: 5px solid #d92d3a;
            border-radius: 10px;
            padding: 19px;
            margin-bottom: 24px;
            color: #842029;
        }

        .escalation-warning h3 {
            margin: 0 0 7px;
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 13px;
            margin-bottom: 25px;
        }

        .admin-action {
            display: block;
            text-decoration: none;
            background: white;
            padding: 17px;
            border-radius: 10px;
            color: #10283f;
            font-weight: 700;
            border: 1px solid #e4eaf0;
        }

        .admin-action:hover {
            border-color: #087f8c;
            transform: translateY(-2px);
        }

        .admin-panel {
            background: white;
            padding: 22px;
            border-radius: 13px;
            margin-bottom: 23px;
            box-shadow: 0 3px 12px rgba(16,40,63,.05);
            overflow-x: auto;
        }

        .admin-panel h3 {
            margin: 0 0 18px;
            color: #10283f;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .admin-table th,
        .admin-table td {
            padding: 13px;
            border-bottom: 1px solid #edf0f4;
            text-align: left;
            font-size: 13px;
        }

        .admin-table th {
            color: #64748b;
            background: #f8fafc;
        }

        .admin-table a {
            color: #087f8c;
            font-weight: 700;
            text-decoration: none;
        }

        .admin-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-responding {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-escalated {
            background: #fee2e2;
            color: #b91c1c;
        }

        .admin-footer {
            color: #718096;
            font-size: 12px;
            text-align: center;
            padding: 15px;
        }

        @media(max-width: 1000px) {
            .admin-stats,
            .admin-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media(max-width: 650px) {
            .admin-dashboard {
                display: block;
            }

            .admin-sidebar {
                width: auto;
                padding: 12px;
            }

            .admin-brand {
                margin: 5px 10px 15px;
            }

            .admin-sidebar nav {
                display: flex;
                overflow-x: auto;
                gap: 5px;
            }

            .admin-sidebar a {
                white-space: nowrap;
                margin: 0;
            }

            .admin-main {
                padding: 16px;
            }

            .admin-stats,
            .admin-actions {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .admin-stat {
                padding: 14px;
            }

            .admin-stat h2 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="admin-dashboard">

    <aside class="admin-sidebar">

        <div class="admin-brand">
            SAFE<span>GUARD</span>
        </div>

        <nav>
            <a href="admin_dashboard.php" class="active">DASHBOARD</a>
            <a href="admin_alert.php">EMERGENCY ALERTS</a>
            <a href="escalations.php">ESCALATION HISTORY</a>
            <a href="admin_users.php">MANAGE USERS</a>
            <a href="reports.php">REPORTS</a>
            <a href="admin_analytics.php">ANALYTICS</a>
            <a href="escalation_settings.php">ESCALATION SETTINGS</a>
            <a href="logout.php">LOGOUT</a>
        </nav>

    </aside>

    <main class="admin-main">

        <header class="admin-topbar">
            <div>
                <h1>ADMIN DASHBOARD</h1>
                <p>Emergency monitoring and response management</p>
            </div>

            <div class="admin-profile">
                Administrator:
                <strong>
                    <?php echo htmlspecialchars($adminName); ?>
                </strong>
            </div>
        </header>

        <?php if ($escalatedAlerts > 0): ?>
            <div class="escalation-warning">
                <h3>Emergency Escalation Warning</h3>
                <p>
                    <strong><?php echo $escalatedAlerts; ?></strong>
                    emergency alert(s) have been escalated because they
                    were not acknowledged within the response deadline.
                </p>
                <a href="escalations.php">View escalated emergencies →</a>
            </div>
        <?php endif; ?>

        <section class="admin-stats">

            <div class="admin-stat info">
                <small>REGISTERED USERS</small>
                <h2><?php echo $totalUsers; ?></h2>
            </div>

            <div class="admin-stat">
                <small>ADMINISTRATORS</small>
                <h2><?php echo $totalAdmins; ?></h2>
            </div>

            <div class="admin-stat danger">
                <small>TOTAL EMERGENCY ALERTS</small>
                <h2><?php echo $totalAlerts; ?></h2>
            </div>

            <div class="admin-stat warning">
                <small>PENDING EMERGENCY ALERTS</small>
                <h2><?php echo $pendingAlerts; ?></h2>
            </div>

            <div class="admin-stat info">
                <small>RESPONDING ALERTS</small>
                <h2><?php echo $respondingAlerts; ?></h2>
            </div>

            <div class="admin-stat success">
                <small>Resolved Emergencies</small>
                <h2><?php echo $resolvedAlerts; ?></h2>
            </div>

            <div class="admin-stat danger">
                <small>ESCALATED ALERTS</small>
                <h2><?php echo $escalatedAlerts; ?></h2>
            </div>

            <div class="admin-stat">
                <small>RESPONSE PROGRESS</small>
                <h2>
                    <?php
                    echo $totalAlerts > 0
                        ? round(($resolvedAlerts / $totalAlerts) * 100) . '%'
                        : '0%';
                    ?>
                </h2>
            </div>

        </section>

        <section>
            <h3>QUICK OPERATIONS</h3>

            <div class="admin-actions">
                <a class="admin-action" href="admin_alert.php">
                    VIEW ALERTS →
                </a>

                <a class="admin-action" href="escalations.php">
                    ESCALATION HISTORY →
                </a>

                <a class="admin-action" href="admin_users.php">
                    MANAGE USERS →
                </a>

                <a class="admin-action" href="reports.php">
                    GENERATE REPORTS →
                </a>
            </div>
        </section>

        <section class="admin-panel">
            <h3>RECENT ALERTS</h3>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ALERTS ID</th>
                        <th>PERSON</th>
                        <th>INCIDENT</th>
                        <th>STATUS</th>
                        <th>ESCALATION</th>
                        <th>DATE</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($recentAlerts): ?>

                    <?php foreach ($recentAlerts as $alert): ?>
                    <tr>
                        <td>#<?php echo (int)$alert['id']; ?></td>

                        <td>
                            <?php echo htmlspecialchars($alert['fullname']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($alert['incident_type']); ?>
                        </td>

                        <td>
                            <?php
                            $statusClass = 'badge-pending';

                            if ($alert['status'] === 'Responding') {
                                $statusClass = 'badge-responding';
                            } elseif ($alert['status'] === 'Resolved') {
                                $statusClass = 'badge-resolved';
                            }
                            ?>
                            <span class="admin-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($alert['status']); ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($alert['escalation_status'] === 'Escalated'): ?>
                                <span class="admin-badge badge-escalated">
                                    ESCALATED
                                </span>
                            <?php else: ?>
                                <span class="admin-badge badge-pending">
                                    WAITING
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($alert['created_at']); ?>
                        </td>

                        <td>
                            <a href="admin_alert_details.php?id=<?php echo (int)$alert['id']; ?>">
                                View Details
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7">No emergency alerts recorded yet.</td>
                    </tr>

                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="admin-panel">
            <h3>LATEST ESCALATION ACTIVITY</h3>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ALERT</th>
                        <th>PERSON</th>
                        <th>RECIPIENT</th>
                        <th>NOTIFICATION STATUS</th>
                        <th>DATE</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($recentEscalations): ?>

                    <?php foreach ($recentEscalations as $item): ?>
                    <tr>
                        <td>
                            #<?php echo (int)$item['alert_id']; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item['fullname']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item['recipient']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item['delivery_status']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item['created_at']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">No escalation activity yet.</td>
                    </tr>

                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <div class="admin-footer">
            SafeGuard Emergency Management System |
            Protecting Lives Through Technology
        </div>

    </main>
</div>

</body>
</html>
