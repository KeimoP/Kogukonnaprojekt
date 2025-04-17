<?php
// Lihtne turvalisus (päris rakenduses kasuta paremat autentimist)
$admin_password = "admin123"; // Asenda tugevama parooliga

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        $error = "Vale parool!";
    }
}

if (!isset($_SESSION['admin_authenticated']) {
    ?>
    <!DOCTYPE html>
    <html lang="et">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5" style="max-width: 400px;">
            <h2 class="mb-4">Admin login</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="password" class="form-label">Parool:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Sisene</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

require 'db_connect.php';

// Küsime statistika
$new_visitors = $conn->query("SELECT COUNT(*) FROM visitors WHERE is_returning = 0")->fetch_row()[0];
$returning_visitors = $conn->query("SELECT COUNT(*) FROM visitors WHERE is_returning = 1")->fetch_row()[0];
$total_visitors = $new_visitors + $returning_visitors;
?>

<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin leht</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Statistika</h1>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Uued külastajad</div>
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $new_visitors; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Tagasipöördujad</div>
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $returning_visitors; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-header">Kokku külastusi</div>
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $total_visitors; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="mt-5 mb-3">Viimased külastajad</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nimi</th>
                    <th>Tagasipöörduja</th>
                    <th>Külastuse aeg</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM visitors ORDER BY visit_time DESC LIMIT 10");
                while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['is_returning'] ? 'Jah' : 'Ei'; ?></td>
                        <td><?php echo $row['visit_time']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <a href="?logout" class="btn btn-danger mt-3">Logi välja</a>
    </div>
</body>
</html>

<?php
// Logout funktsionaalsus
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    header('Location: admin.php');
    exit;
}
?>