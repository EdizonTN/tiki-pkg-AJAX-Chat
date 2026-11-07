<?php
class TikiDb
{
    const ERR_EXCEPTION = 'exception';
    private static $instance;
    public $lastSql = '';
    public $lastValues = null;

    public static function setInstance($instance) { self::$instance = $instance; }
    public static function isAvailable() { return self::$instance instanceof self; }
    public static function get() { return self::$instance; }
    public function qstr($value) { return "'".str_replace("'", "''", $value)."'"; }
    public function lastInsertId() { return '123'; }
    public function query($sql, $values = [], $numRows = -1, $offset = -1, $reportErrors = self::ERR_EXCEPTION)
    {
        $this->lastSql = $sql;
        $this->lastValues = $values;
        return new TestTikiPdoResult();
    }
}

class TestTikiPdoResult
{
    private $rows = [['id' => 7]];
    public function fetchRow() { return count($this->rows) ? array_shift($this->rows) : false; }
    public function numRows() { return 1; }
}

require_once(__DIR__.'/../chat/lib/class/AJAXChatTikiQuery.php');
require_once(__DIR__.'/../chat/lib/class/AJAXChatTikiDataBase.php');

function check_tiki_adapter($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: ".$message."\n");
        exit(1);
    }
    echo "PASS: ".$message."\n";
}

$db = new TikiDb();
TikiDb::setInstance($db);
$config = ['type' => 'tikidb', 'link' => null, 'name' => ''];
$adapter = new AJAXChatDataBaseTiki($config);

check_tiki_adapter($config['link'] === $db, 'adapter reuses the active TikiDb instance');
check_tiki_adapter($config['name'] === '', 'adapter does not select a second database');
check_tiki_adapter($adapter->connect($config), 'adapter confirms the active TikiDb connection');
check_tiki_adapter($adapter->select(''), 'adapter keeps the database selected by Tiki');
check_tiki_adapter(!$adapter->select('other_database'), 'adapter refuses to switch databases');
check_tiki_adapter($adapter->makeSafe("O'Reilly") === "'O''Reilly'", 'string quoting delegates to TikiDb');
check_tiki_adapter($adapter->getName() === '', 'table names are not qualified with a second database');
check_tiki_adapter($adapter->getLastInsertedID() === '123', 'insert ID delegates to TikiDb');

$result = $adapter->sqlQuery('SELECT id FROM tikiwiki_ext_ajax_chat_rooms');
check_tiki_adapter($db->lastSql === 'SELECT id FROM tikiwiki_ext_ajax_chat_rooms', 'queries are delegated to TikiDb');
check_tiki_adapter($result->fetch() === ['id' => 7], 'Tiki result rows are exposed through the chat adapter');
check_tiki_adapter($result->numRows() === 1, 'result row count is exposed through the chat adapter');
check_tiki_adapter($result->affectedRows() === 1, 'affected row count is exposed through the chat adapter');
check_tiki_adapter(!$result->error(), 'successful TikiDb query is not reported as an error');

echo "TikiDb adapter checks passed.\n";
