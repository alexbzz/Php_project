<?php
// Charger le .env
$envPaths = [
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env',
];

$loadedEnv = null;
foreach ($envPaths as $envPath) {
    if (!is_file($envPath) || !is_readable($envPath)) {
        continue;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        continue;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($key === '') {
            continue;
        }

        // Supporte les valeurs entre guillemets
        $first = $value[0] ?? '';
        $last = $value !== '' ? $value[strlen($value) - 1] : '';
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }

    $loadedEnv = $envPath;
    break;
}

$required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
$missing = [];
foreach ($required as $k) {
    if (!array_key_exists($k, $_ENV)) {
        $missing[] = $k;
    }
}
if ($missing) {
    throw new RuntimeException(
        "Clés manquantes dans le .env: " . implode(', ', $missing) .
        ". Fichiers recherchés: " . implode(', ', $envPaths) .
        ($loadedEnv ? ". Chargé: {$loadedEnv}" : ". Aucun .env trouvé.")
    );
}

if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
    $drivers = PDO::getAvailableDrivers();
    throw new RuntimeException(
        "Driver PDO MySQL introuvable (pdo_mysql). Drivers disponibles: " .
        (empty($drivers) ? '(aucun)' : implode(', ', $drivers)) .
        ". Activez/installez l'extension pdo_mysql dans le PHP utilisé par votre serveur (WAMP/XAMPP/Apache)."
    );
}

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // Message propre au lieu d'une PDOException non détectée.
    throw new RuntimeException("Connexion DB échouée: " . $e->getMessage());
}
