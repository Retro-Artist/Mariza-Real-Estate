<?php
// generate_password.php
// Usage: php generate_password.php your-plain-text-password

if (PHP_SAPI !== 'cli') {
    die("This script is intended for CLI use only.\n");
}

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php generate_password.php <plain-password>\n");
    exit(1);
}

$plain = $argv[1];
$hash  = password_hash($plain, PASSWORD_DEFAULT);

if ($hash === false) {
    fwrite(STDERR, "Error generating hash.\n");
    exit(1);
}

echo $hash . "\n";
