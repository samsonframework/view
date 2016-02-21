<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 20.02.16 at 14:39
 */
namespace samsonframework\view\tests;

use samsonframework\view\Generator;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerator()
    {
        $generator = new Generator(new \samsonphp\generator\Generator(), '\test\view\\');

        $generator->scan(__DIR__ . '/product');
        $generator->generate(__DIR__.'/generated');
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
