<?php
/**
 * Быстрая диагностика без полного Laravel (если artisan падает).
 * https://crynova.io/check.php
 * Удалите после проверки!
 */
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
$manifest = $root.'/public/build/manifest.json';
$manifestData = is_file($manifest) ? json_decode(file_get_contents($manifest), true) : null;
$css = $manifestData['resources/css/app.css']['file'] ?? null;
$js = $manifestData['resources/js/app.js']['file'] ?? null;

$required = ['bcmath', 'curl', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo_mysql', 'tokenizer', 'xml', 'zip'];
$extensions = [];
foreach ($required as $ext) {
    $extensions[$ext] = extension_loaded($ext);
}

echo json_encode([
    'ok' => ! in_array(false, $extensions, true) && is_file($root.'/.env') && is_file($root.'/vendor/autoload.php'),
    'php' => PHP_VERSION,
    'document_root' => __DIR__,
    'env_exists' => is_file($root.'/.env'),
    'vendor_exists' => is_file($root.'/vendor/autoload.php'),
    'manifest_exists' => is_file($manifest),
    'css_built' => $css ? is_file($root.'/public/build/'.$css) : false,
    'js_built' => $js ? is_file($root.'/public/build/'.$js) : false,
    'css_file' => $css,
    'js_file' => $js,
    'storage_writable' => is_writable($root.'/storage'),
    'bootstrap_cache_writable' => is_writable($root.'/bootstrap/cache'),
    'extensions' => $extensions,
    'next_step' => 'SSH: cd site && php artisan crynova:doctor',
    'time' => date('c'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
