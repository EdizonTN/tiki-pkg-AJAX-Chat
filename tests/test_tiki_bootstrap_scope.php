<?php
require_once(__DIR__ . '/../src/lib/tiki-bootstrap.php');

function assert_tiki_bootstrap_scope($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
    echo "PASS: " . $message . "\n";
}

if (!class_exists('TikiDb')) {
    class TikiDb
    {
        public static function isAvailable()
        {
            return true;
        }
    }
}

$originalDirectory = getcwd();
$tempRoot = sys_get_temp_dir() . '/ajax-chat-tiki-bootstrap-' . bin2hex(random_bytes(6));
if (!mkdir($tempRoot, 0700)) {
    fwrite(STDERR, "FAIL: could not create temporary Tiki root\n");
    exit(1);
}

$setupFile = $tempRoot . '/tiki-setup.php';
$setupSource = "<?php\n"
    . "\$user = 'scope-test-user';\n"
    . "\$prefs = array('scope_test' => 'loaded');\n"
    . "\$tiki_p_admin = 'y';\n";
file_put_contents($setupFile, $setupSource);

$user = null;
$prefs = array();
$tiki_p_admin = null;

try {
    AjaxChatBootstrapTiki(array(
        'paths' => array(
            'tikiRootPath' => $tempRoot,
            'tikiUrlPath' => '/',
        ),
    ));
} catch (Throwable $e) {
    chdir($originalDirectory);
    unlink($setupFile);
    rmdir($tempRoot);
    fwrite(STDERR, "FAIL: bootstrap threw " . $e->getMessage() . "\n");
    exit(1);
}

assert_tiki_bootstrap_scope($user === 'scope-test-user', 'Tiki user is available in global scope');
assert_tiki_bootstrap_scope(isset($prefs['scope_test']) && $prefs['scope_test'] === 'loaded', 'Tiki preferences are available in global scope');
assert_tiki_bootstrap_scope($tiki_p_admin === 'y', 'Tiki admin permission is available in global scope');
assert_tiki_bootstrap_scope($_SERVER['SCRIPT_NAME'] === '/index.php', 'Tiki setup receives the Tiki front-controller script path');

chdir($originalDirectory);
unlink($setupFile);
rmdir($tempRoot);

echo "All Tiki bootstrap scope tests passed.\n";
