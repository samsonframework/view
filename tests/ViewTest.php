<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 20.02.16 at 13:13
 */
namespace samsonframework\view\tests;

use samsonframework\view\View;

require 'TestObject.php';
require 'TestRenderInterfaceObject.php';

class ViewTest extends \PHPUnit_Framework_TestCase
{
    public function testSet()
    {
        $view = new View();

        // Prepare all value types
        $values = array(
            'testString' => 'testStringValue',
            'testInteger' => 77,
            'testDouble' => 66.66,
            'testBoolean' => true,
            'testArray' => array(1,3,5,7,9),
            'testAssocArray' => array('first' => 1, ' second' => 3, 'third' => 5),
            'testObject' => new TestObject(),
        );

        // Try setting and getting them
        foreach ($values as $key => $value) {
            $view->set($value, $key);
            $this->assertEquals($value, $view->$key);
            $this->assertEquals(gettype($value), gettype($view->$key));
        }

        $this->setExpectedException('\samsonframework\view\exception\VariableKeyNotFound');
        $var = $view->notExistingKey;
    }

    public function testSetAssocArray()
    {
        $view = new View();
        $view->set(array('first' => 123, 'second' => 323, 'third' => 523), 'testAssocArray');

        $this->assertEquals(123, $view->first);
        $this->assertEquals(323, $view->second);
        $this->assertEquals(523, $view->third);
    }

    public function testSetObject()
    {
        $view = new View();
        $object = new TestRenderInterfaceObject();
        $view->set($object, 'testObject');

        $this->assertEquals(get_class($object), $view->testObjectClassName);
    }

    public function testView()
    {
        $view = new View();
        $view->view(__DIR__.'/testView.vphp');

        $this->setExpectedException('\samsonframework\view\exception\ViewFileNotFound');
        $view->view('testView');
    }

    public function testOutput()
    {
        $object = new TestRenderInterfaceObject();

        $view = new View();

        $rendered = $view->view(__DIR__.'/testView.vphp')
            ->set($object, 'testObject')
            ->set(77, 'numericValue')
            ->output();

        $this->assertTrue(strpos($rendered, get_class($object)) !== false);
        $this->assertTrue(strpos($rendered, (string)$view->numericValue) !== false);
    }
}
