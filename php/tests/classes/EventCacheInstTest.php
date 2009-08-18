<?php
require_once dirname(dirname(dirname(__FILE__))).'/classes/EventCache.php';

class EventCacheInstTest extends PHPUnit_Framework_TestCase {
    public $EventCacheInst;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->EventCacheInst = new EventCacheInst(array(
            'app' => 'testapp',
            'trackEvents' => true,
        ));

        $this->EventCacheInst->clear();
        #$this->EventCacheInst->flush();
    }

    public function testListAdd() {
        $this->EventCacheInst->flush();
        
        $this->EventCacheInst->listAdd('EventCacheLogEntries', null, 'Kevin van Zonneveld');
        $this->EventCacheInst->listAdd('EventCacheLogEntries', null, 'Kevin');

        $list = $this->EventCacheInst->read('EventCacheLogEntries');

        $this->assertEquals(array(
            'Kevin van Zonneveld',
            'Kevin',
        ), $list);

        
        $this->EventCacheInst->flush();
        
        $this->EventCacheInst->listAdd('EventCacheLogEntries', 'a', 'Kevin van Zonneveld');
        $this->EventCacheInst->listAdd('EventCacheLogEntries', 'b', 'Kevin');

        $list = $this->EventCacheInst->read('EventCacheLogEntries');

        $this->assertEquals(array(
            'a' => 'Kevin van Zonneveld',
            'b' => 'Kevin',
        ), $list);
    }

    public function testWrite() {
        $this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
            'Employee::afterSave',
            'Employee::afterDelete',
        ));
        $this->assertEquals('Kevin van Zonneveld', $this->EventCacheInst->read('name'));
    }

    public function testTrigger() {
        $this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
            'Employee::afterSave',
            'Employee::afterDelete',
        ));
        $this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
            'Server::afterSave',
            'Server::afterDelete',
        ));
        
        $this->EventCacheInst->trigger('Server::afterSave');

        $this->assertEquals(false, $this->EventCacheInst->read('hostname'));
        $this->assertEquals('Kevin van Zonneveld', $this->EventCacheInst->read('name'));
    }

    public function testRegister() {
        $this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
            'Employee::afterSave',
            'Employee::afterDelete',
        ));
        $this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
            'Server::afterSave',
            'Server::afterDelete',
        ));
        
        $this->EventCacheInst->register('name', 'Server::afterSave');
        
        $this->EventCacheInst->trigger('Server::afterSave');

        $this->assertEquals(false, $this->EventCacheInst->read('hostname'));
        $this->assertEquals(false, $this->EventCacheInst->read('name'));
    }

    public function testUnregister() {
        $this->EventCacheInst->flush();
        $this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
            'Employee::afterSave',
            'Employee::afterDelete',
            'Server::afterDelete',
        ));
        $this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
            'Server::afterSave',
            'Server::afterDelete',
        ));
        
        $keys = $this->EventCacheInst->getKeys('Server::afterDelete');
        $this->assertEquals(array(
            'testapp-key-hostname' => 'hostname',
            'testapp-key-name' => 'name',
        ), $keys);
        
        $this->EventCacheInst->unregister('name', 'Server::afterDelete');
        $keys = $this->EventCacheInst->getKeys('Server::afterDelete');
        $this->assertEquals(array(
            'testapp-key-hostname' => 'hostname',
        ), $keys);
    }

    public function testGetEvents() {
        $this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
            'Employee::afterSave',
            'Employee::afterDelete',
        ));
        $this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
            'Server::afterSave',
            'Server::afterDelete',
        ));
        
        $events = $this->EventCacheInst->getEvents();
        
        $this->assertEquals(array(
            'testapp-event-Employee__afterSave' => 'Employee::afterSave',
            'testapp-event-Employee__afterDelete' => 'Employee::afterDelete',
            'testapp-event-Server__afterSave' => 'Server::afterSave',
            'testapp-event-Server__afterDelete' => 'Server::afterDelete',
        ), $events);
    }

    public function testGetKeys() {
        $this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
            'Employee::afterSave',
            'Employee::afterDelete',
            'Server::afterDelete',
        ));
        $keys = $this->EventCacheInst->getKeys('Server::afterDelete');
        $this->assertEquals(array(
            'testapp-key-name' => 'name',
        ), $keys);


        $this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
            'Server::afterSave',
            'Server::afterDelete',
        ));

        $keys = $this->EventCacheInst->getKeys('Server::afterDelete');
        $this->assertEquals(array(
            'testapp-key-hostname' => 'hostname',
            'testapp-key-name' => 'name',
        ), $keys);
    }

    public function testSave() {
        $this->assertEquals('asdf__235@____b______', $this->EventCacheInst->sane('asdf~!235@#$%^b&_-=*('));
    }

    public function testClear() {
        $this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
            'Employee::afterSave',
            'Employee::afterDelete',
            'Server::afterDelete',
        ));

        $this->EventCacheInst->clear();

        $this->assertEquals(false, $this->EventCacheInst->read('hostname'));
        $this->assertEquals(false, $this->EventCacheInst->read('name'));
    }
}
?>