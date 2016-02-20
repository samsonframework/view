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
        $generator = new Generator(new \samsonphp\generator\Generator());

        $generator->scan(__DIR__);
    }
}
