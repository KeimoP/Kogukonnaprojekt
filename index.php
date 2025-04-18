<?php
session_start();

// Only track if this is a NEW session
if (empty($_SESSION['visitor_tracked'])) {
    try {
        require 'db_connect.php';
        
        $name = $_POST['name'] ?? null;
        $is_returning = isset($_POST['returning_user']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO visitors (name, is_returning, visit_time) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $name, $is_returning);
        
        if ($stmt->execute()) {
            $_SESSION['visitor_tracked'] = true; // Mark as tracked
            $_SESSION['visitor_id'] = $stmt->insert_id; // Store ID if needed
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Tracking error: ".$e->getMessage());
    }
}

// Your existing redirect logic...
if (isset($_SESSION['authenticated'])) {
    header('Location: questions.php');
    exit;
}

// Language selection
$available_langs = ['et' => 'Eesti', 'en' => 'English', 'ru' => 'Русский'];
$lang = $_GET['lang'] ?? 'et';
if (!array_key_exists($lang, $available_langs)) {
    $lang = 'et';
}
$_SESSION['lang'] = $lang;

// Load language file
$translations = json_decode(file_get_contents("lang/{$lang}.json"), true);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translations['welcome_title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .flower-bg {
            background-color: #fff5f7;
            background-image: url('assets/images/lill1.jpeg'), url('assets/images/lill2.svg');
            background-position: left top, right bottom;
            background-repeat: no-repeat;
            background-size: 200px, 150px;
            min-height: 100vh;
        }
        .welcome-card {
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 30px rgba(255, 182, 193, 0.3);
            border: 1px solid #ffccd5;
        }
        .language-selector .btn {
            margin: 0 5px;
        }
        .language-selector .active {
            background-color: #d63384;
            color: white !important;
        }
        .btn {
            text-decoration: none;
        }
    </style>
</head>
<body class="flower-bg">
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="welcome-card p-5" style="max-width: 600px;">
            <h1 class="text-center mb-4 text-pink"><?= $translations['welcome_heading'] ?> <span class="flower-emoji">🌸</span></h1>
            
            <div class="language-selector text-center mb-4">
                <?php foreach ($available_langs as $code => $name): ?>
                    <a href="?lang=<?= $code ?>" class="btn btn-sm btn-outline-pink <?= $code === $lang ? 'active' : '' ?>">
                        <?= $name ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" action="questions.php">
                <div class="mb-3">
                    <label for="name" class="form-label text-pink"><?= $translations['name_prompt'] ?></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="<?= $translations['name_placeholder'] ?>" required>
                </div>
                <button type="submit" class="btn btn-pink w-100"><?= $translations['continue_button'] ?></button>
            </form>
            <div class="mt-3 text-center">
                <form method="POST">
                    <input type="hidden" name="returning_user" value="1">
                    <button type="submit" class="btn btn-link text-pink">
                        <span class="flower-emoji">🌼</span> <?= $translations['returning_user'] ?>
                    </button>
                </form>
            </div>
            
            <div class="mt-3 p-3 bg-light-pink rounded text-center small">
                <?= $translations['privacy_notice'] ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>