<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 20.02.16 at 13:17
 */
namespace samsonframework\view\tests;

use samsonframework\core\RenderInterface;

class TestRenderInterfaceObject extends TestObject implements RenderInterface
{
    public function toView($prefix = null, array $restricted = array())
    {
        return array($prefix.'ClassName' => get_class($this));
    }
}
