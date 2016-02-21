<?php
namespace test\view;

/** Class for view "/testGeneratorView.vphp" rendering */
class testGeneratorViewView extends \samsonframework\view\View
{
    /** @var array Collection of view variables */
    public static $variables = array(
        '0' => 'testObjectClassName',
        '1' => 'numericValue',
        '2' => 'notExists',
    );
    /** @var mixed View variable */
    public $testObjectClassName;
    /** @var mixed View variable */
    public $numericValue;
    /** @var mixed View variable */
    public $notExists;
    /** @var string Path to view file */
    protected $path = '/testGeneratorView.vphp';

    /**
     * Setter for testObjectClassName view variable
     *
     * @param mixed $value View variable value
     *
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
     *
     * @return $this Chaining
     */
    public function numericValue($value)
    {
        return parent::set($value, 'numericValue');
    }

    /**
     * Setter for notExists view variable
     *
     * @param mixed $value View variable value
     *
     * @return $this Chaining
     */
    public function notExists($value)
    {
        return parent::set($value, 'notExists');
    }

}
