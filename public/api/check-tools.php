<?php
/**
 * API: Sprawdzanie dostępności narzędzi (megatools, wget, curl, ffmpeg)
 */

require_once __DIR__ . '/common.php';

try {
    $tools = [
        'megatools' => checkTool('megatools'),
        'wget' => checkTool('wget'),
        'curl' => checkTool('curl'),
        'ffmpeg' => checkTool('ffmpeg'),
        'php_exec' => function_exists('exec')
    ];

    echo json_encode([
        'success' => true,
        'tools' => $tools,
        'mega_available' => $tools['megatools'] && $tools['php_exec'],
        'url_download_available' => ($tools['wget'] || $tools['curl']) && $tools['php_exec'],
        'thumbnail_available' => $tools['ffmpeg'] && $tools['php_exec']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Sprawdza czy narzędzie jest dostępne
 */
function checkTool(string $tool): bool
{
    if (!function_exists('exec')) {
        return false;
    }

    exec("which $tool 2>&1", $output, $returnCode);
    return $returnCode === 0;
}
