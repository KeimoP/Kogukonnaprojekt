<?php
session_start();
require 'db_connect.php';

// Kui kasutaja on juba küsimuste lehel, suuna sinna
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: questions.php');
    exit;
}

<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tere tulemast</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .welcome-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container welcome-container">
        <h1 class="text-center mb-4">Tere tulemast!</h1>
        
        <?php if (!isset($_POST['name']) && !isset($_POST['returning'])): ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Palun sisesta oma nimi:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Edasi</button>
            </form>
            
            <div class="mt-3 text-center">
                <form method="POST">
                    <input type="hidden" name="returning" value="1">
                    <button type="submit" class="btn btn-link">Olen juba käinud siin</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>