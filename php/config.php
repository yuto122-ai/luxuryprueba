<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===========================
// BASE DE DATOS
// ===========================

define('DB_HOST', 'localhost');
define('DB_USER', 'luxury_app');
define('DB_PASS', 'PonUnaClaveFuerte_2026');
define('DB_NAME', 'black_clothes');

define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$tableName]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$tableName, $columnName]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureDatabaseCompatibility(PDO $pdo): void {
    // Backward-compatibility for old dumps that don't include dynamic color tables.
    if (!tableExists($pdo, 'colors')) {
        $pdo->exec("CREATE TABLE colors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            hex VARCHAR(7) NOT NULL DEFAULT '#000000',
            extra_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            image_path VARCHAR(255) NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } else {
        if (!columnExists($pdo, 'colors', 'hex')) {
            $pdo->exec("ALTER TABLE colors ADD COLUMN hex VARCHAR(7) NOT NULL DEFAULT '#000000' AFTER name");
        }
        if (!columnExists($pdo, 'colors', 'extra_price')) {
            $pdo->exec("ALTER TABLE colors ADD COLUMN extra_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER hex");
        }
        if (!columnExists($pdo, 'colors', 'image_path')) {
            $pdo->exec("ALTER TABLE colors ADD COLUMN image_path VARCHAR(255) NULL AFTER extra_price");
        }
        if (!columnExists($pdo, 'colors', 'active')) {
            $pdo->exec("ALTER TABLE colors ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER image_path");
        }
    }

    if (!tableExists($pdo, 'product_colors')) {
        $pdo->exec("CREATE TABLE product_colors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            color_id INT NOT NULL,
            extra_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            image_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_product_color (product_id, color_id),
            KEY idx_color_id (color_id),
            CONSTRAINT fk_product_colors_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            CONSTRAINT fk_product_colors_color FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } else {
        if (!columnExists($pdo, 'product_colors', 'product_id')) {
            $pdo->exec("ALTER TABLE product_colors ADD COLUMN product_id INT NULL AFTER id");
        }
        if (!columnExists($pdo, 'product_colors', 'color_id')) {
            $pdo->exec("ALTER TABLE product_colors ADD COLUMN color_id INT NULL AFTER product_id");
        }
        if (!columnExists($pdo, 'product_colors', 'extra_price')) {
            $pdo->exec("ALTER TABLE product_colors ADD COLUMN extra_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER color_id");
        }
        if (!columnExists($pdo, 'product_colors', 'image_path')) {
            $pdo->exec("ALTER TABLE product_colors ADD COLUMN image_path VARCHAR(255) NULL AFTER extra_price");
        }
        if (!columnExists($pdo, 'product_colors', 'created_at')) {
            $pdo->exec("ALTER TABLE product_colors ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER image_path");
        }
    }

    // Some older schemas have product_variants without price column.
    if (tableExists($pdo, 'product_variants')) {
        if (!columnExists($pdo, 'product_variants', 'price')) {
            $pdo->exec("ALTER TABLE product_variants ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER size");
        }
        if (!columnExists($pdo, 'product_variants', 'price_individual')) {
            $pdo->exec("ALTER TABLE product_variants ADD COLUMN price_individual DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price");
        }
        if (!columnExists($pdo, 'product_variants', 'price_wholesale')) {
            $pdo->exec("ALTER TABLE product_variants ADD COLUMN price_wholesale DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price_individual");
        }
        if (!columnExists($pdo, 'product_variants', 'stock')) {
            $pdo->exec("ALTER TABLE product_variants ADD COLUMN stock INT NOT NULL DEFAULT 0 AFTER price_wholesale");
        }

        // Backfill legacy values so older rows keep working.
        $pdo->exec("UPDATE product_variants
            SET price_individual = CASE WHEN price_individual = 0 THEN COALESCE(price, 0) ELSE COALESCE(price_individual, 0) END,
                price_wholesale  = CASE WHEN price_wholesale  = 0 THEN COALESCE(price, 0) ELSE COALESCE(price_wholesale, 0)  END");
    }

    if (tableExists($pdo, 'product_colors')) {
        if (!columnExists($pdo, 'product_colors', 'extra_price_individual')) {
            $pdo->exec("ALTER TABLE product_colors ADD COLUMN extra_price_individual DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER extra_price");
        }
        if (!columnExists($pdo, 'product_colors', 'extra_price_wholesale')) {
            $pdo->exec("ALTER TABLE product_colors ADD COLUMN extra_price_wholesale DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER extra_price_individual");
        }

        // Backfill legacy values so older rows keep working.
        $pdo->exec("UPDATE product_colors
            SET extra_price_individual = CASE WHEN extra_price_individual = 0 THEN COALESCE(extra_price, 0) ELSE COALESCE(extra_price_individual, 0) END,
                extra_price_wholesale  = CASE WHEN extra_price_wholesale  = 0 THEN COALESCE(extra_price, 0) ELSE COALESCE(extra_price_wholesale, 0)  END");
    }

    if (tableExists($pdo, 'cart') && !columnExists($pdo, 'cart', 'image_path')) {
        $pdo->exec("ALTER TABLE cart ADD COLUMN image_path VARCHAR(255) NULL AFTER variant_id");
    }

    // Legacy products tables may miss color fields used by admin/product viewer.
    if (tableExists($pdo, 'products')) {
        if (!columnExists($pdo, 'products', 'color')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN color VARCHAR(50) NOT NULL DEFAULT 'blanco' AFTER featured");
        }
        if (!columnExists($pdo, 'products', 'color_price_modifier')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN color_price_modifier DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER color");
        }
    }

    $colorsCount = (int)$pdo->query("SELECT COUNT(*) FROM colors")->fetchColumn();
    if ($colorsCount === 0) {
        $pdo->exec("INSERT INTO colors (name, hex, extra_price, active) VALUES
            ('Blanco', '#ffffff', 0.00, 1),
            ('Negro', '#000000', 0.00, 1),
            ('Rojo', '#cc0000', 0.00, 1),
            ('Azul', '#0044cc', 0.00, 1),
            ('Verde', '#118833', 0.00, 1)");
    }
}

function getDB() {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            ensureDatabaseCompatibility($pdo);
        } catch (PDOException $e) {
            die("Error conexión DB: " . $e->getMessage());
        }
    }
    return $pdo;
}

// ===========================
// FUNCIONES GENERALES
// ===========================

function sanitize($text) {
    return htmlspecialchars(strip_tags(trim($text)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireAdmin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo "Acceso solo para administradores";
        exit;
    }
}

function generateOrderNumber() {
    return "BC" . date("Ymd") . rand(1000, 9999);
}

// ===========================
// TELEGRAM BOT CONFIG
// BUG 22 FIX: guarda el token en variables de entorno o en un archivo fuera del webroot.
// Por ahora se deja aquí para compatibilidad — cámbialo antes de subir a producción.
// ===========================

define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('TELEGRAM_CHAT_ID',   'YOUR_CHAT_ID_HERE');

/**
 * BUG 23 FIX: agregado parse_mode HTML para que los mensajes con <b> funcionen.
 */
function sendTelegramMessage($message) {
    $token   = TELEGRAM_BOT_TOKEN;
    $chat_id = TELEGRAM_CHAT_ID;

    if ($token === 'YOUR_BOT_TOKEN_HERE' || $chat_id === 'YOUR_CHAT_ID_HERE') {
        return false;
    }

    $url  = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id'    => $chat_id,
        'text'       => $message,
        'parse_mode' => 'HTML',   // ← BUG 23 FIX
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10,
        ]
    ];

    $context = stream_context_create($options);
    $result  = @file_get_contents($url, false, $context);
    return $result !== false;
}
