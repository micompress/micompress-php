<?php
// End-to-end smoke test against the live API: php tests/smoke.php
require __DIR__ . '/../vendor/autoload.php';

$dir = sys_get_temp_dir() . '/micompress-php-' . bin2hex(random_bytes(4));
mkdir($dir);
$img = imagecreatetruecolor(400, 300);
for ($x = 0; $x < 400; $x++) {
    for ($y = 0; $y < 300; $y++) {
        imagesetpixel($img, $x, $y, imagecolorallocate($img, $x % 256, $y % 256, ($x * $y) % 256));
    }
}
imagepng($img, "$dir/test.png");

$client = new MiCompress\Client();
$r = $client->compressFile("$dir/test.png");
assert(is_file($r['output']) && filesize($r['output']) < filesize("$dir/test.png"));
printf("ok — %d -> %d bytes (%d%% smaller)\n", $r['inputSize'], $r['outputSize'], $r['savedPercent']);

try {
    $client->compress("$dir/file.unknownext");
    exit(1);
} catch (MiCompress\CompressException $e) {
    assert($e->getErrorCode() === 'UNSUPPORTED');
    echo "ok — unsupported type rejected\n";
}
