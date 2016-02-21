<?php
namespace test\view;

/** Class for view "/testView.vphp" rendering */
class testViewView extends \samsonframework\view\View
{
    /** @var array Collection of view variables */
    public static $variables = array(
        '0' => 'testObjectClassName',
        '1' => 'numericValue',
    );
    /** @var mixed View variable */
    public $testObjectClassName;
    /** @var mixed View variable */
    public $numericValue;
    /** @var string Path to view file */
    protected $path = '/testView.vphp';

    /**
     * Setter for testObjectClassName view variable
     *
     * @param mixed $value View variable value
     * @return $this Chaining
     */
    public function testObjectClassName($value)
    {
        return parent::set($value, 'testObjectClassName');
    }

    /**
     * Setter for numericValue view variable
     *
     * @param mixed $value View variable value
     * @return $this Chaining
     */
    public function numericValue($value)
    {
        return parent::set($value, 'numericValue');
    }

}
