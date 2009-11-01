<?php
require_once 'PHPUnit/Framework.php';
$test = true;
include dirname(dirname(__FILE__)).'/vblog.php';

/**
 * Test class for VBlog.
 * Generated by PHPUnit on 2009-11-01 at 16:38:47.
 */
class VBlogTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    VBlog
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->object = new VBlog;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }

    /**
     * @todo Implement testItemToPost().
     */
    public function testItemToPost()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testIncludeMedia().
     */
    public function testIncludeMedia()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCanPost().
     */
    public function testCanPost()
    {

        $s = array(10, 11, 14, 19, 20);

        $this->assertTrue ($this->object->canPost($s, 9, 10));
        $this->assertFalse($this->object->canPost($s, 10, 9));
        $this->assertFalse($this->object->canPost($s, 14, 23));
        $this->assertFalse($this->object->canPost($s, 3, 4));
        $this->assertFalse($this->object->canPost($s, 14, 14));
        $this->assertFalse($this->object->canPost($s, 0, 9));
    }

    /**
     * @todo Implement testRun().
     */
    public function testRun()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
?>
