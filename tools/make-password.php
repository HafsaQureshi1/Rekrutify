<?php
/**
 * Generates the ADMIN_PASSWORD_HASH value for includes/config.php.
 *
 *   php tools/make-password.php "your new password"
 *
 * Paste the printed line into includes/config.php, replacing the existing
 * const ADMIN_PASSWORD_HASH = '...'; line.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this from the command line only.\n");
}

$password = $argv[1] ?? '';
if ($password === '') {
    fwrite(STDERR, "Usage: php tools/make-password.php \"your new password\"\n");
    exit(1);
}
if (strlen($password) < 10) {
    fwrite(STDERR, "Use at least 10 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "\nCopy this line into includes/config.php:\n\n";
echo "const ADMIN_PASSWORD_HASH = '" . $hash . "';\n\n";
