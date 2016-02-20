<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 20.02.16 at 14:39
 */
namespace samsonframework\view\tests;

use samsonframework\view\Generator;
use samsonframework\view\View;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerator()
    {
        $generator = new Generator(new \samsonphp\generator\Generator(), '\test\view\\');

        $generator->scan(__DIR__, array(View::DEFAULT_EXT), __DIR__);
        $generator->generate(__DIR__.'/generated');
    }
}
