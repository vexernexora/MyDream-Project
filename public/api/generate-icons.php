<?php
/**
 * API: Generowanie ikon aplikacji w różnych rozmiarach
 */

require_once __DIR__ . '/common.php';

// Rozmiary ikon do wygenerowania
$sizes = [16, 32, 57, 60, 72, 76, 96, 114, 120, 128, 144, 152, 180, 192, 384, 512];

$baseDir = __DIR__ . '/../img';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}

foreach ($sizes as $size) {
    $filename = "{$baseDir}/icon-{$size}.png";

    // Pomiń jeśli ikona już istnieje
    if (file_exists($filename)) {
        continue;
    }

    // Utwórz obrazek
    $img = imagecreatetruecolor($size, $size);

    // Kolory
    $bg = imagecolorallocate($img, 15, 15, 15); // #0f0f0f
    $red = imagecolorallocate($img, 220, 38, 38); // #dc2626
    $white = imagecolorallocate($img, 241, 241, 241);

    // Wypełnij tło
    imagefill($img, 0, 0, $bg);

    // Narysuj prostokąt play button (YouTube style)
    $padding = (int)($size * 0.15);
    $rectX1 = $padding;
    $rectY1 = $padding;
    $rectX2 = $size - $padding;
    $rectY2 = $size - $padding;

    // Zaokrąglony prostokąt (w przybliżeniu)
    imagefilledrectangle($img, $rectX1, $rectY1, $rectX2, $rectY2, $red);

    // Trójkąt play
    $trianglePadding = (int)($size * 0.3);
    $points = [
        $trianglePadding + (int)($size * 0.05), $trianglePadding,
        $size - $trianglePadding, $size / 2,
        $trianglePadding + (int)($size * 0.05), $size - $trianglePadding
    ];
    imagefilledpolygon($img, $points, 3, $white);

    // Zapisz
    imagepng($img, $filename);
    imagedestroy($img);

    echo "Generated: icon-{$size}.png\n";
}

// Utwórz również screenshot placeholder
$screenshotFile = "{$baseDir}/screenshot-1.png";
if (!file_exists($screenshotFile)) {
    $screenshot = imagecreatetruecolor(1280, 720);
    $bg = imagecolorallocate($screenshot, 15, 15, 15);
    $red = imagecolorallocate($screenshot, 220, 38, 38);
    $white = imagecolorallocate($screenshot, 241, 241, 241);

    imagefill($screenshot, 0, 0, $bg);

    // Dodaj tekst
    $text = "MyDream Video Player";
    $font = 5; // Built-in font
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $x = (1280 - $textWidth) / 2;
    $y = (720 - $textHeight) / 2;

    imagestring($screenshot, $font, (int)$x, (int)$y, $text, $white);

    imagepng($screenshot, $screenshotFile);
    imagedestroy($screenshot);

    echo "Generated: screenshot-1.png\n";
}

// Utwórz no-thumbnail.jpg jeśli nie istnieje
$noThumbFile = "{$baseDir}/no-thumbnail.jpg";
if (!file_exists($noThumbFile)) {
    $noThumb = imagecreatetruecolor(1280, 720);
    $bg = imagecolorallocate($noThumb, 31, 31, 31);
    $gray = imagecolorallocate($noThumb, 170, 170, 170);

    imagefill($noThumb, 0, 0, $bg);

    // Ikona wideo
    $centerX = 640;
    $centerY = 360;
    $size = 100;

    imagefilledrectangle($noThumb, $centerX - $size, $centerY - $size/2, $centerX + $size, $centerY + $size/2, $gray);

    // Trójkąt
    $points = [
        $centerX - 30, $centerY - 40,
        $centerX + 50, $centerY,
        $centerX - 30, $centerY + 40
    ];
    imagefilledpolygon($noThumb, $points, 3, $bg);

    imagejpeg($noThumb, $noThumbFile, 85);
    imagedestroy($noThumb);

    echo "Generated: no-thumbnail.jpg\n";
}

echo "All icons generated successfully!\n";
