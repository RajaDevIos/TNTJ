<?php
// Create (or reset the password of) an admin user from the command line.
// Usage: php scripts/create_admin.php "Name" email@example.com [superadmin|editor]
// The password is read from the terminal (not passed as an argument, so it never lands in shell history).
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require __DIR__ . '/../app/bootstrap.php';
use App\Core\DB;

[$_, $name, $email, $role] = $argv + [null, null, null, 'superadmin'];
if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) exit("Usage: php scripts/create_admin.php \"Name\" email@example.com [superadmin|editor]\n");
$role = in_array($role, ['superadmin', 'editor'], true) ? $role : 'editor';

$pass = getenv('ADMIN_PASSWORD') ?: '';
if ($pass === '') {
    echo 'Password (min 8 chars): ';
    if (stripos(PHP_OS, 'WIN') !== 0) system('stty -echo');
    $pass = trim((string)fgets(STDIN));
    if (stripos(PHP_OS, 'WIN') !== 0) system('stty echo');
    echo "\n";
}
if (strlen($pass) < 8) exit("Password must be at least 8 characters\n");

$hash = password_hash($pass, PASSWORD_DEFAULT);
$email = strtolower($email);
if (DB::value('SELECT id FROM users WHERE email = ?', [$email])) {
    DB::run('UPDATE users SET name = ?, password_hash = ?, role = ?, active = 1 WHERE email = ?', [$name, $hash, $role, $email]);
    DB::run('DELETE t FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE u.email = ?', [$email]);
    echo "Updated $email ($role)\n";
} else {
    DB::insert('users', ['name' => $name, 'email' => $email, 'password_hash' => $hash, 'role' => $role]);
    echo "Created $email ($role)\n";
}
