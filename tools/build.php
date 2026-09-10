<?php
/**
 * Rebuilds the static blog pages from the post files.
 *
 *     php tools/build.php
 *
 * The admin panel does this automatically on every save, so you normally never
 * need to run it by hand. It is here for a first build, or after editing the
 * JSON files directly.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this from the command line only.\n");
}

require_once __DIR__ . '/../includes/render.php';

$result = build_static_site();

foreach ($result['written'] as $file) {
    echo "  wrote    $file\n";
}
foreach ($result['removed'] as $file) {
    echo "  removed  $file\n";
}
foreach ($result['errors'] as $error) {
    fwrite(STDERR, "  ERROR    $error\n");
}

echo "\n" . count($result['written']) . " page(s) written";
echo $result['removed'] ? ', ' . count($result['removed']) . ' removed' : '';
echo ".\n";

exit($result['errors'] ? 1 : 0);
