
<?php
require_once '../config/auth.php';
requireAdmin();

require_once '../config/db.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}


$totalUsers = (int)$conn->query(
    "SELECT COUNT(*) FROM users WHERE role = 'user'"
)->fetchColumn();

$totalAdmins = (int)$conn->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
)->fetchColumn();

$totalAlerts = (int)$conn->query(
    "SELECT COUNT(*) FROM alerts"
)->fetchColumn();


$statusCounts = [
    'Pending' => 0,
    'Responding' => 0,
    'Resolved' => 0
];

$statusQuery = $conn->query(
    "SELECT status, COUNT(*) AS total
     FROM alerts
     GROUP BY status"
);

foreach ($statusQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int)$row['total'];
    }
}


$incidentQuery = $conn->query(
    "SELECT incident_type, COUNT(*) AS total
     FROM alerts
     GROUP BY incident_type
     ORDER BY total DESC"
);

$incidentRows = $incidentQuery->fetchAll(PDO::FETCH_ASSOC);

$incidentLabels = [];
$incidentValues = [];

foreach ($incidentRows as $row) {
    $incidentLabels[] = $row['incident_type'] ?: 'Unspecified';
    $incidentValues[] = (int)$row['total'];
}


$dailyQuery = $conn->query(
    "SELECT DATE(created_at) AS report_date,
            COUNT(*) AS total
     FROM alerts
     WHERE created_at >= CURDATE() - INTERVAL 6 DAY
       AND created_at < CURDATE() + INTERVAL 1 DAY
     GROUP BY DATE(created_at)
     ORDER BY report_date ASC"
);

$dailyRows = $dailyQuery->fetchAll(PDO::FETCH_ASSOC);

$dailyLookup = [];

foreach ($dailyRows as $row) {
    $dailyLookup[$row['report_date']] = (int)$row['total'];
}

$dailyLabels = [];
$dailyValues = [];

$today = new DateTimeImmutable('today');

for ($i = 6; $i >= 0; $i--) {
    $date = $today->modify("-$i days");
    $dateKey = $date->format('Y-m-d');

    $dailyLabels[] = $date->format('D, d M');
    $dailyValues[] = $dailyLookup[$dateKey] ?? 0;
}


$recentQuery = $conn->query(
    "SELECT a.id,
            a.incident_type,
            a.status,
            a.created_at,
            u.fullname
     FROM alerts a
     JOIN users u ON a.user_id = u.id
     ORDER BY a.created_at DESC
     LIMIT 5"
);

$recentAlerts = $recentQuery->fetchAll(PDO::FETCH_ASSOC);

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP |
             JSON_HEX_APOS | JSON_HEX_QUOT;

$statusLabelsJson = json_encode(
    array_keys($statusCounts),
    $jsonFlags
);

$statusValuesJson = json_encode(
    array_values($statusCounts),
    $jsonFlags
);

$incidentLabelsJson = json_encode(
    $incidentLabels,
    $jsonFlags
);

$incidentValuesJson = json_encode(
    $incidentValues,
    $jsonFlags
);

$dailyLabelsJson = json_encode(
    $dailyLabels,
    $jsonFlags
);

$dailyValuesJson = json_encode(
    $dailyValues,
    $jsonFlags
);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>SafeGuard | Admin Analytics</title>

    <link rel="stylesheet" href="style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        body {
            background: #f4f7fb;
        }

        .analytics-header {
            margin-bottom: 25px;
        }

        .analytics-header p {
            color: #6c757d;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin: 25px 0;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-left: 5px solid #1479a5;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 30px;
            font-weight: bold;
            color: #0b3d5c;
        }

        .stat-card.pending {
            border-left-color: #f0ad4e;
        }

        .stat-card.responding {
            border-left-color: #1479a5;
        }

        .stat-card.resolved {
            border-left-color: #198754;
        }

        .stat-card.users {
            border-left-color: #7047a3;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
            margin: 25px 0;
        }

        .chart-card {
            background: white;
            padding: 23px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            min-width: 0;
        }

        .chart-card h2 {
            font-size: 19px;
            margin-bottom: 8px;
        }

        .chart-card p {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .wide-chart {
            grid-column: 1 / -1;
        }

        .recent-table {
            margin-top: 25px;
        }

        .page-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        @media (max-width: 800px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }

            .wide-chart {
                grid-column: auto;
            }
        }

    </style>

</head>

<body>

<nav>

    <div class="logo">SAFEGUARD ADMIN</div>

    <div class="navigation">
        <a href="admin_dashboard.php">DASHBOARD</a>
        <a href="admin_users.php">USERS</a>
        <a href="admin_alert_details.php">ALERTS</a>
        <a href="admin_reports.php">REPORTS</a>
        <a href="logout.php">LOGOUT</a>
    </div>

</nav>

<div class="container">

    <div class="analytics-header">

        <h1>SYSTEM ANALYTICS AND STATISTICS</h1>

        <p>
            Monitor emergency reports, users and system activity
            through visual statistics.
        </p>

        <div class="page-actions">

            <a href="admin_dashboard.php" class="btn btn-primary">
                BACK TO DASHBOARD
            </a>

            <button type="button"
                    class="btn btn-success"
                    onclick="window.location.reload()">
                REFRESH STATISTICS
            </button>

            <a href="reports.php" class="btn btn-secondary">
                VIEW REPORTS
            </a>

        </div>

    </div>


    <h2>SYSTEM OVERVIEW</h2>

    <div class="stats-grid">

        <div class="stat-card">
            <h3>TOTAL EMERGENCY ALERTS</h3>
            <div class="number"><?= $totalAlerts ?></div>
        </div>

        <div class="stat-card users">
            <h3>REGISTERED USERS</h3>
            <div class="number"><?= $totalUsers ?></div>
        </div>

        <div class="stat-card">
            <h3>ADMINISTRATORS</h3>
            <div class="number"><?= $totalAdmins ?></div>
        </div>

        <div class="stat-card pending">
            <h3>PENDING ALERTS</h3>
            <div class="number"><?= $statusCounts['Pending'] ?></div>
        </div>

        <div class="stat-card responding">
            <h3>RESPONDING ALERTS</h3>
            <div class="number"><?= $statusCounts['Responding'] ?></div>
        </div>

        <div class="stat-card resolved">
            <h3>RESOLVED ALERTS</h3>
            <div class="number"><?= $statusCounts['Resolved'] ?></div>
        </div>

    </div>


    <h2>GRAPHICAL ANALYSIS</h2>

    <div class="chart-grid">

        <div class="chart-card">

            <h2>EMERGENCY STATUS BREAKDOWN</h2>

            <p>Distribution of all recorded emergency alerts.</p>

            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>

        </div>

        <div class="chart-card">

            <h2>EMERGENCY TYPES</h2>

            <p>Number of alerts submitted under each incident type.</p>

            <div class="chart-container">
                <canvas id="incidentChart"></canvas>
            </div>

        </div>

        <div class="chart-card wide-chart">

            <h2>EMERGENCY ACTIVITY - LAST 7 DAYS</h2>

            <p>Daily trend of emergency alerts submitted to SafeGuard.</p>

            <div class="chart-container">
                <canvas id="dailyLineChart"></canvas>
            </div>

        </div>


        <div class="chart-card wide-chart">

            <h2>EMERGENCY ACTIVITY - LAST 7 DAYS</h2>

            <p>Compare the number of emergency alerts reported each day.</p>

            <div class="chart-container">
                <canvas id="dailyBarChart"></canvas>
            </div>

        </div>

    </div>

    <div class="card recent-table">

        <h2>RECENT EMERGENCY ACTIVITY</h2>

        <div class="table-container">

            <table>

                <thead>

                    <tr>
                        <th>ALERT ID</th>
                        <th>USER</th>
                        <th>INCIDENT</th>
                        <th>STATUS</th>
                        <th>DATE</th>
                        <th>ACTION</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($recentAlerts) > 0): ?>

                        <?php foreach ($recentAlerts as $alert): ?>

                            <tr>

                                <td>#<?= e($alert['id']) ?></td>

                                <td><?= e($alert['fullname']) ?></td>

                                <td><?= e($alert['incident_type']) ?></td>

                                <td><?= e($alert['status']) ?></td>

                                <td><?= e($alert['created_at']) ?></td>

                                <td>
                                    <a href="admin_alert_details.php?id=<?= (int)$alert['id'] ?>"
                                       class="btn btn-primary">
                                        VIEW
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6">
                                NO EMERGENCY ALERTS RECORDED YET.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<footer>

    <p>SafeGuard Emergency SOS and Personal Safety Management System</p>

    <p>Administrator Analytics Dashboard</p>

</footer>


<script>

const statusLabels = <?= $statusLabelsJson ?>;
const statusValues = <?= $statusValuesJson ?>;

const incidentLabels = <?= $incidentLabelsJson ?>;
const incidentValues = <?= $incidentValuesJson ?>;

const dailyLabels = <?= $dailyLabelsJson ?>;
const dailyValues = <?= $dailyValuesJson ?>;


Chart.defaults.font.family = 'Arial';
Chart.defaults.plugins.legend.position = 'bottom';


new Chart(document.getElementById('statusChart'), {

    type: 'pie',

    data: {
        labels: statusLabels,

        datasets: [{
            data: statusValues,

            backgroundColor: [
                '#f0ad4e',
                '#1479a5',
                '#198754'
            ],

            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }

});

// 2. INCIDENT DOUGHNUT CHART

new Chart(document.getElementById('incidentChart'), {

    type: 'doughnut',

    data: {
        labels: incidentLabels,

        datasets: [{
            data: incidentValues,

            backgroundColor: [
                '#0b3d5c',
                '#1479a5',
                '#198754',
                '#f0ad4e',
                '#dc3545',
                '#7047a3',
                '#20a4a8',
                '#e76f51'
            ],

            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }

});


new Chart(document.getElementById('dailyLineChart'), {

    type: 'line',

    data: {
        labels: dailyLabels,

        datasets: [{

            label: 'Emergency Alerts',

            data: dailyValues,

            borderColor: '#1479a5',

            backgroundColor: 'rgba(20, 121, 165, 0.15)',

            fill: true,

            tension: 0.35,

            pointRadius: 5,

            pointBackgroundColor: '#0b3d5c'

        }]
    },

    options: {

        responsive: true,
        maintainAspectRatio: false,

        scales: {

            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }

        },

        plugins: {

            legend: {
                display: true
            }

        }

    }

});


new Chart(document.getElementById('dailyBarChart'), {

    type: 'bar',

    data: {
        labels: dailyLabels,

        datasets: [{

            label: 'Alerts Per Day',

            data: dailyValues,

            backgroundColor: '#1479a5',

            borderRadius: 6,

            borderSkipped: false

        }]
    },

    options: {

        responsive: true,
        maintainAspectRatio: false,

        scales: {

            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }

        },

        plugins: {

            legend: {
                display: true
            }

        }

    }

});

</script>

</body>
</html>
