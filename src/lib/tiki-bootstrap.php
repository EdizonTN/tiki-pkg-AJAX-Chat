<?php
/**
 * Bootstrap TikiWiki for AJAX Chat requests.
 *
 * TikiDb is the database authority for the integrated chat. The installer
 * stores the filesystem path and URL; runtime code never performs path discovery.
 */
function mzChatBootstrapTiki(array $config)
{
    $paths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : array();
    $tikiRoot = isset($paths['tikiRootPath']) ? $paths['tikiRootPath'] : '';
    $tikiUrlPath = isset($paths['tikiUrlPath']) ? $paths['tikiUrlPath'] : '';

    if (!is_string($tikiRoot) || $tikiRoot === '' || !is_string($tikiUrlPath) || $tikiUrlPath === '') {
        throw new RuntimeException('Tiki paths are not configured.');
    }

    $tikiRoot = rtrim($tikiRoot, '/\\').DIRECTORY_SEPARATOR;
    $tikiSetup = $tikiRoot.'tiki-setup.php';
    if (!is_file($tikiSetup)) {
        throw new RuntimeException('Tiki setup file was not found at the configured path.');
    }

    if (!chdir($tikiRoot)) {
        throw new RuntimeException('Tiki root directory is not accessible.');
    }

    $tikiIndexPath = rtrim($tikiUrlPath, '/').'/index.php';
    if ($tikiIndexPath === 'index.php') {
        $tikiIndexPath = '/index.php';
    }

    // Tiki setup expects the request script to be rooted at the Tiki front controller.
    $_SERVER['SCRIPT_NAME'] = $tikiIndexPath;
    $_SERVER['PHP_SELF'] = $tikiIndexPath;
    $_SERVER['REQUEST_URI'] = $tikiIndexPath;
    $_SERVER['SCRIPT_FILENAME'] = $tikiRoot.'index.php';

    require_once($tikiSetup);

    if (!class_exists('TikiDb') || !TikiDb::isAvailable()) {
        throw new RuntimeException('Tiki database connection is not available.');
    }

    return $tikiRoot;
}
