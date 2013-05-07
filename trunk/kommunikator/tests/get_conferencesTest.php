<?php

class get_conferencesTest extends PHPUnit_Framework_TestCase {

    public function testConferences() {
        $params = array('action' => 'get_conferences', 'session' => file_get_contents(sys_get_temp_dir().'/session'));
        $out = shell_exec("php data_.php " . addslashes(json_encode($params)));
        $res = json_decode($out);
        $this->assertEquals(true, $res->success);
    }

}

?>