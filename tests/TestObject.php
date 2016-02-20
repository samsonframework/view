<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 20.02.16 at 13:17
 */
namespace samsonframework\view\tests;

class TestObject
{
    public $stringField = 'stringFieldValue';
    public $integerField = 77;
    public $doubleField = 66.66;
    public $booleanField = true;
    public $resourceField;
    public $arrayField = array(1,2,3);
    public $assocArrayField = array('first' => 1, ' second' => 3, 'third' => 5);
    public $objectField;
    public $nullField = null;
}
