<?php
session_start();

try {
    require 'db_connect.php';
    
    $name = $_POST['name'] ?? 'Anonymous';
    $is_returning = isset($_POST['returning_user']) ? 1 : 0;

    $_SESSION['user_name'] = $name; // Save to session for use in questions.php
    
    $stmt = $conn->prepare("INSERT INTO visitors (name, is_returning, visit_time) VALUES (?, ?, NOW())");
    $stmt->bind_param("si", $name, $is_returning);
    
    if ($stmt->execute()) {
        $_SESSION['visitor_tracked'] = true;
        $_SESSION['visitor_id'] = $stmt->insert_id;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Tracking error: ".$e->getMessage());
}

// Language setup
$available_langs = ['et' => 'Eesti', 'en' => 'English', 'ru' => 'Русский'];
$lang = $_GET['lang'] ?? 'et';
if (!array_key_exists($lang, $available_langs)) {
    $lang = 'et';
}
$_SESSION['lang'] = $lang;

// Load translations
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
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --text-color: #2b2d42;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .welcome-container {
            max-width: 500px;
            margin: auto;
            padding: 2rem;
        }
        
        .welcome-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            border: none;
        }
        
        .welcome-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .welcome-header h1 {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .welcome-header .icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .btn-link:hover {
            text-decoration: underline;
        }
        
        .language-selector {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .language-selector .btn {
            border-radius: 20px;
            padding: 0.25rem 1rem;
            border: 1px solid #e0e0e0;
            color: var(--text-color);
        }
        
        .language-selector .active {
            background-color: var(--primary-color);
            color: white !important;
            border-color: var(--primary-color);
        }
        
        .privacy-notice {
            background-color: rgba(67, 97, 238, 0.05);
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.85rem;
            color: #666;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="welcome-card">
            <div class="welcome-header">
                <div class="icon">👋</div>
                <h1><?= $translations['welcome_heading'] ?></h1>
            </div>
            
            <div class="language-selector">
                <?php foreach ($available_langs as $code => $name): ?>
                    <a href="?lang=<?= $code ?>" class="btn btn-sm <?= $code === $lang ? 'active' : '' ?>">
                        <?= $name ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" action="questions.php">
                <div class="mb-3">
                    <label for="name" class="form-label"><?= $translations['name_prompt'] ?></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="<?= $translations['name_placeholder'] ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3"><?= $translations['continue_button'] ?></button>
            </form>
            
            <div class="text-center">
                <form method="POST">
                    <input type="hidden" name="returning_user" value="1">
                    <button type="submit" class="btn btn-link">
                        <?= $translations['returning_user'] ?>
                    </button>
                </form>
            </div>
            
            <div class="privacy-notice">
                <?= $translations['privacy_notice'] ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>