<?php

$pwd = getcwd();
chdir(dirname(__FILE__) . '/../src');
require_once 'config.php';
chdir($pwd);

class configTest extends PHPUnit_Framework_TestCase {

    public function testDBConn() {
        global $conn;

        $this->assertEquals('DB_mysql', get_class($conn));
    }

}

?>
