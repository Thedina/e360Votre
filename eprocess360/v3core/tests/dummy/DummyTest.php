<?php
    class DummyTest extends PHPUnit_Framework_TestCase {
        public function testNothing() {
            $this->assertEquals(1, 1);
        }

        public function testCheckTestModeFlag() {
            $this->assertEquals(TEST_FLAG, 1);
        }
    }
?>