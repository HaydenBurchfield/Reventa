<?php
// ============================================================
// config.php  —  place at your project root: REVENTA/config.php
// ============================================================
// Detects the subfolder the project lives in automatically.
// Works whether the site is at /  OR  /REVENTA/  OR  /anything/
// ============================================================

if (!defined('BASE_URL')) {
    // Strip /index.php or trailing filename from SCRIPT_NAME to get the folder
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    // Walk up until we find config.php (i.e. the project root URL path)
    // Since config.php is in the project root, __DIR__ is the doc-root subfolder.
    // We detect it by comparing realpath of __DIR__ against DOCUMENT_ROOT.
    $docRoot    = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');
    $scriptPath = rtrim(realpath(__DIR__), '/\\');

    // URL path to project root
    $basePath = str_replace($docRoot, '', $scriptPath);
    $basePath = str_replace('\\', '/', $basePath); // Windows compat
    $basePath = rtrim($basePath, '/');

    // e.g.  ""  if at web root,  "/REVENTA"  if in a subfolder
    define('BASE_URL', $basePath);

    // Absolute filesystem path to uploads dir (inside project)
    define('UPLOAD_DIR',      __DIR__ . '/uploads/');
    define('AVATAR_DIR',      __DIR__ . '/uploads/avatars/');
    define('LISTING_IMG_DIR', __DIR__ . '/uploads/listings/');

    // Web-accessible URL paths
    define('UPLOAD_URL',      BASE_URL . '/uploads/');
    define('AVATAR_URL',      BASE_URL . '/uploads/avatars/');
    define('LISTING_IMG_URL', BASE_URL . '/uploads/listings/');
}
?>