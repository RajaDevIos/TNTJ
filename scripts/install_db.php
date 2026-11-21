<?php
// Prepare the database: on an empty database load schema.sql + seed.sql, then apply every
// file in database/migrations (they are safe to re-run). Optionally create the first admin
// from ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD when no user exists yet.
// Usage: php scripts/install_db.php        (runs automatically when the Docker container starts)
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require __DIR__ . '/../app/bootstrap.php';
use App\Core\DB;

$pdo = DB::pdo();

/** Run a .sql file statement by statement (statements end with ";" at the end of a line). */
$runFile = function (string $file) use ($pdo): int {
    $sql = file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $n = 0;
    foreach (preg_split('/;\s*$/m', $sql) as $stmt) {
        if (trim($stmt) === '') continue;
        $pdo->exec($stmt); $n++;
    }
    return $n;
};

$fresh = !$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
if ($fresh) {
    echo 'Empty database: loading schema … ', $runFile(BASE_PATH . '/database/schema.sql'), " statements\n";
    echo 'Loading seed data … ', $runFile(BASE_PATH . '/database/seed.sql'), " statements\n";
} else {
    echo "Database already set up: applying migrations only\n";
}

$migrations = glob(BASE_PATH . '/database/migrations/*.sql');
sort($migrations);
foreach ($migrations as $m) echo 'Migration ', basename($m), ' … ', $runFile($m), " statements\n";

// First admin from the environment (only when there are no users at all)
$email = strtolower((string)getenv('ADMIN_EMAIL'));
$pass = (string)getenv('ADMIN_PASSWORD');
if ($email !== '' && $pass !== '' && !(int)DB::value('SELECT COUNT(*) FROM users')) {
    if (strlen($pass) < 8 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "ADMIN_EMAIL / ADMIN_PASSWORD invalid (password needs 8+ characters): no admin created\n";
    } else {
        DB::insert('users', ['name' => getenv('ADMIN_NAME') ?: 'Admin', 'email' => $email,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'superadmin']);
        echo "Created super admin $email\n";
    }
}
echo "Done\n";
