<?php
session_start();

// Configuration (move to config file in production)
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD_HASH = password_hash('admin123', PASSWORD_DEFAULT); // Change this!

// Logout handler
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Authentication check
if (!isset($_SESSION['admin_authenticated']) || !$_SESSION['admin_authenticated']) {
    // Process login
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        if ($_POST['username'] === $ADMIN_USERNAME && 
            password_verify($_POST['password'], $ADMIN_PASSWORD_HASH)) {
            $_SESSION['admin_authenticated'] = true;
            header('Location: admin.php');
            exit;
        }
        $error = "Vale kasutajanimi või parool!";
    }
    
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="et">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .login-container {
                max-width: 400px;
                margin: 100px auto;
                padding: 30px;
                border-radius: 10px;
                background-color: rgba(255,255,255,0.9);
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            body {
                background-color: #fff5f7;
                background-image: url('pics/lill1.jpeg'), url('pics/lill2.svg');
                background-position: left top, right bottom;
                background-repeat: no-repeat;
                background-size: 200px, 150px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="login-container">
                <h2 class="text-center mb-4">Admin login</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kasutajanimi:</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Parool:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Sisene</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Admin dashboard
require 'db_connect.php';

// In admin.php (after authentication)
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_visitors,
        SUM(is_returning = 0) as new_visitors,
        SUM(is_returning = 1) as returning_visitors,
        DATE(visit_time) as visit_date,
        COUNT(DISTINCT COALESCE(name, CONCAT('anonymous-', id))) as unique_visitors
    FROM visitors
    GROUP BY visit_date
    ORDER BY visit_date DESC
    LIMIT 30
")->fetch_all(MYSQLI_ASSOC);

// Get recent visitors
$recent_visitors = $conn->query("
    SELECT name, is_returning, visit_time 
    FROM visitors 
    ORDER BY visit_time DESC 
    LIMIT 10
");

$emotion_stats = $conn->query("
    SELECT 
        emotion_state,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM visitors), 1) as percentage
    FROM visitors
    WHERE emotion_state IS NOT NULL
    GROUP BY emotion_state
    ORDER BY count DESC
")->fetch_all(MYSQLI_ASSOC);

// Get daily emotion trends
$emotion_trends = $conn->query("
    SELECT 
        DATE(visit_time) as date,
        SUM(emotion_state = 'good') as good,
        SUM(emotion_state = 'okay') as okay,
        SUM(emotion_state = 'bad') as bad,
        SUM(emotion_state = 'very_bad') as very_bad
    FROM visitors
    WHERE visit_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY date
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin statistika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        /* Emotion Stats Styles */
        .emotion-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .bg-emotion-good { background-color: #4ade80; color: white; }
        .bg-emotion-okay { background-color: #60a5fa; color: white; }
        .bg-emotion-bad { background-color: #fbbf24; color: black; }
        .bg-emotion-very-bad { background-color: #f87171; color: white; }

        .chart-container {
            position: relative;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><span class="text-pink">Külastajate statistika</span> 🌸</h1>
            <a href="?logout" class="btn btn-danger">Logi välja</a>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="stat-card card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Uued külastajad</h5>
                        <h2><?= end($stats)['new_visitors'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Tagasipöördujad</h5>
                        <h2><?= end($stats)['returning_visitors'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="chart-container mt-4">
            <canvas id="visitorChart"></canvas>
        </div>

        <!-- Recent Visitors -->
        <div class="card mt-4">
            <div class="card-header bg-pink text-white">
                <h5 class="mb-0 text-dark">Viimased 10 külastajat</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tüüp</th>
                            <th>Külastusaeg</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($visitor = $recent_visitors->fetch_assoc()): ?>
                        <tr>
                            <td><?= $visitor['is_returning'] ? 'Tagasipöörduja' : 'Uus' ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($visitor['visit_time'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mt-4">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0">Kasutajate meeleolud</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Pie Chart -->
            <div class="col-md-6">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="emotionChart"></canvas>
                </div>
            </div>
            
            <!-- Stats Table -->
            <div class="col-md-6">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Meeleseisund</th>
                            <th>Külastusi</th>
                            <th>Protsent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emotion_stats as $stat): ?>
                        <tr>
                            <td>
                                <?php 
                                $emotion_label = [
                                    'good' => $translations['emotion_good'],
                                    'okay' => $translations['emotion_okay'],
                                    'bad' => $translations['emotion_bad'],
                                    'very_bad' => $translations['emotion_very_bad']
                                ][$stat['emotion_state']] ?? $stat['emotion_state'];
                                echo $emotion_label;
                                ?>
                            </td>
                            <td><?= $stat['count'] ?></td>
                            <td><?= $stat['percentage'] ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Trend Chart -->
        <div class="mt-4">
            <h4 class="mb-3">Viimase 30 päeva trend</h4>
            <div class="chart-container" style="height: 300px;">
                <canvas id="emotionTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>
    </div>

    <script>
        // Visitor chart
        const ctx = document.getElementById('visitorChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($stats, 'date')) ?>,
                datasets: [
                    {
                        label: 'Uued külastajad',
                        data: <?= json_encode(array_column($stats, 'new_visitors')) ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.3
                    },
                    {
                        label: 'Tagasipöördujad',
                        data: <?= json_encode(array_column($stats, 'returning_visitors')) ?>,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Külastajate trend (viimased 30 päeva)'
                    }
                }
            }
        });
    </script>
    <script>
// Emotion Distribution Pie Chart
new Chart(document.getElementById('emotionChart'), {
    type: 'pie',
    data: {
        labels: [
            '<?= $translations['emotion_good'] ?>',
            '<?= $translations['emotion_okay'] ?>', 
            '<?= $translations['emotion_bad'] ?>',
            '<?= $translations['emotion_very_bad'] ?>'
        ],
        datasets: [{
            data: [
                <?= $emotion_stats[array_search('good', array_column($emotion_stats, 'emotion_state'))]['count'] ?? 0 ?>,
                <?= $emotion_stats[array_search('okay', array_column($emotion_stats, 'emotion_state'))]['count'] ?? 0 ?>,
                <?= $emotion_stats[array_search('bad', array_column($emotion_stats, 'emotion_state'))]['count'] ?? 0 ?>,
                <?= $emotion_stats[array_search('very_bad', array_column($emotion_stats, 'emotion_state'))]['count'] ?? 0 ?>
            ],
            backgroundColor: [
                '#4ade80', // Good - green
                '#60a5fa', // Okay - blue
                '#fbbf24', // Bad - yellow
                '#f87171'  // Very bad - red
            ]
        }]
    }
});

// Emotion Trend Chart
new Chart(document.getElementById('emotionTrendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($emotion_trends, 'date')) ?>,
        datasets: [
            {
                label: '<?= $translations['emotion_good'] ?>',
                data: <?= json_encode(array_column($emotion_trends, 'good')) ?>,
                borderColor: '#4ade80',
                backgroundColor: 'rgba(74, 222, 128, 0.1)',
                tension: 0.3
            },
            {
                label: '<?= $translations['emotion_okay'] ?>',
                data: <?= json_encode(array_column($emotion_trends, 'okay')) ?>,
                borderColor: '#60a5fa',
                backgroundColor: 'rgba(96, 165, 250, 0.1)',
                tension: 0.3
            },
            {
                label: '<?= $translations['emotion_bad'] ?>',
                data: <?= json_encode(array_column($emotion_trends, 'bad')) ?>,
                borderColor: '#fbbf24',
                backgroundColor: 'rgba(251, 191, 36, 0.1)',
                tension: 0.3
            },
            {
                label: '<?= $translations['emotion_very_bad'] ?>',
                data: <?= json_encode(array_column($emotion_trends, 'very_bad')) ?>,
                borderColor: '#f87171',
                backgroundColor: 'rgba(248, 113, 113, 0.1)',
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Meeleseisundite trend'
            }
        }
    }
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>