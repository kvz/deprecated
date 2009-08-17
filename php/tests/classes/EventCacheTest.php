<?php
require_once dirname(dirname(dirname(__FILE__))).'/classes/EventCache.php';

class EventCacheTest extends PHPUnit_Framework_TestCase {

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        EventCache::setOption(array(
            'app' => 'testapp',
        ));
    }
    
    public function testRead() {
        EventCache::write('name', 'Kevin');
        $this->assertEquals('Kevin', EventCache::read('name'));
    }
}
?>