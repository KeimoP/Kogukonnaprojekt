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

// DEBUG: Show raw visitor data
$debug_result = $conn->query("SELECT * FROM visitors ORDER BY visit_time DESC LIMIT 5");
echo "<pre>Last 5 visitors:\n";
while ($row = $debug_result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

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
            <div class="col-md-4">
                <div class="stat-card card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Uued külastajad</h5>
                        <h2><?= end($stats)['new_visitors'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Tagasipöördujad</h5>
                        <h2><?= end($stats)['returning_visitors'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Unikaalsed külastajad</h5>
                        <h2><?= end($stats)['unique_visitors'] ?? 0 ?></h2>
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
                <h5 class="mb-0">Viimased 10 külastajat</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nimi</th>
                            <th>Tüüp</th>
                            <th>Külastusaeg</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($visitor = $recent_visitors->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($visitor['name'] ?: 'Anonüümne') ?></td>
                            <td><?= $visitor['is_returning'] ? 'Tagasipöörduja' : 'Uus' ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($visitor['visit_time'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
</body>
</html>