<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 20.02.16 at 14:39
 */
namespace samsonframework\view\tests;

use samsonframework\view\Generator;
use test\view\FormView;
use test\view\ItemView;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerator()
    {
        $generator = new Generator(new \samsonphp\generator\Generator(), '\test\view\\');

        $generator->scan(__DIR__ . '/product');
        $generator->generate(__DIR__.'/generated');

        require 'generated/test/view/FormView.php';

        $output = (new FormView())
            ->product(new \samsonframework\view\tests\TestObject())
            ->surname('MMMM')
            ->email('sdfsdf')
            ->number(1)
            ->places(array(1))
            ->output();

        $this->assertTrue(strpos($output, 'Name') > 0);
    }

    public function testExtend()
    {
        $generator = new Generator(new \samsonphp\generator\Generator(), '\test\view\\');

        $generator->scan(__DIR__ . '/extend');
        $generator->generate(__DIR__.'/generated');

        require_once 'generated/test/view/ItemView.php';
        (new ItemView())->title('innerTitle')->output();

    }

    public function testKeywordException()
    {
        $this->setExpectedException('\samsonframework\view\exception\GeneratedViewPathHasReservedWord');
        $generator = new Generator(new \samsonphp\generator\Generator(), '\test\view\\');

        $generator->scan(__DIR__);
        $generator->generate(__DIR__.'/generated');
    }

    public function testHash()
    {
        $generator = new Generator(new \samsonphp\generator\Generator(), '\test\view\\');
        $generator->scan(__DIR__);
        $this->assertTrue(strlen($generator->hash()) > 0);
    }
}
