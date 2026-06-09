<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$font = 'C:\\Windows\\Fonts\\georgiab.ttf';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "The GD extension is required.\n");
    exit(1);
}

if (!is_file($font)) {
    fwrite(STDERR, "Required font not found: {$font}\n");
    exit(1);
}

$previewPath = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'favicon-brand.png';
$icoPath = $root . DIRECTORY_SEPARATOR . 'favicon.ico';

ensureDirectory(dirname($previewPath));

$previewImage = renderFavicon(256, $font);
imagepng($previewImage, $previewPath);
imagedestroy($previewImage);

$sizes = [16, 32, 48, 64, 128, 256];
$blobs = [];

foreach ($sizes as $size) {
    $image = renderFavicon($size, $font);

    ob_start();
    imagepng($image);
    $blobs[] = [
        'size' => $size,
        'data' => (string) ob_get_clean(),
    ];

    imagedestroy($image);
}

writeIco($icoPath, $blobs);

echo "Generated {$icoPath}\n";
echo "Generated {$previewPath}\n";

function renderFavicon(int $size, string $font): GdImage
{
    $image = imagecreatetruecolor($size, $size);
    imagealphablending($image, true);
    imagesavealpha($image, true);

    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);
    imageantialias($image, true);

    $copper = imagecolorallocate($image, 180, 108, 48);
    $charcoal = imagecolorallocate($image, 23, 21, 19);
    $paper = imagecolorallocate($image, 251, 245, 235);

    $padding = max(1, (int) round($size * 0.08));
    $diameter = $size - ($padding * 2);
    $ring = max(1, (int) round($size * 0.055));
    $innerDiameter = max(2, $diameter - ($ring * 2));
    $center = (int) floor($size / 2);

    imagefilledellipse($image, $center, $center, $diameter, $diameter, $copper);
    imagefilledellipse($image, $center, $center, $innerDiameter, $innerDiameter, $charcoal);

    $letter = 'K';
    $fontSize = (float) ($size * 0.62);
    $bbox = imagettfbbox($fontSize, 0, $font, $letter);

    if ($bbox === false) {
        throw new RuntimeException('Unable to calculate text box for favicon glyph.');
    }

    $textWidth = (int) abs($bbox[2] - $bbox[0]);
    $textHeight = (int) abs($bbox[7] - $bbox[1]);
    $x = (int) round(($size - $textWidth) / 2) - $bbox[0];
    $y = (int) round(($size + $textHeight) / 2) - $bbox[1];

    // Lift the glyph slightly so the serif sits optically centered inside the badge.
    $y -= max(0, (int) round($size * 0.035));

    imagettftext($image, $fontSize, 0, $x, $y, $paper, $font, $letter);

    return $image;
}

function writeIco(string $path, array $images): void
{
    $count = count($images);
    $header = pack('vvv', 0, 1, $count);
    $directory = '';
    $offset = 6 + ($count * 16);
    $payload = '';

    foreach ($images as $image) {
        $size = (int) $image['size'];
        $data = (string) $image['data'];
        $entrySize = $size === 256 ? 0 : $size;

        $directory .= pack(
            'CCCCvvVV',
            $entrySize,
            $entrySize,
            0,
            0,
            1,
            32,
            strlen($data),
            $offset
        );

        $payload .= $data;
        $offset += strlen($data);
    }

    file_put_contents($path, $header . $directory . $payload);
}

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException(sprintf('Unable to create directory: %s', $path));
    }
}
