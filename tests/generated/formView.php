<?php
namespace test\view;

/** Class for view "/form.vphp" rendering */
class formView extends \samsonframework\view\View
{
    /** @var array Collection of view variables */
    public static $variables = array(
        '0' => 'name',
        '1' => 'surname',
        '2' => 'email',
        '3' => 'number',
        '4' => 'places',
        '5' => 'place',
    );
    /** @var mixed View variable */
    public $name;
    /** @var mixed View variable */
    public $surname;
    /** @var mixed View variable */
    public $email;
    /** @var mixed View variable */
    public $number;
    /** @var mixed View variable */
    public $places;
    /** @var mixed View variable */
    public $place;
    /** @var string Path to view file */
    protected $path = '/form.vphp';

    /**
     * Setter for name view variable
     *
     * @param mixed $value View variable value
     * @return $this Chaining
     */
    public function name($value)
    {
        return parent::set($value, 'name');
    }

    /**
     * Setter for surname view variable
     *
     * @param mixed $value View variable value
     * @return $this Chaining
     */
    public function surname($value)
    {
        return parent::set($value, 'surname');
    }

    /**
     * Setter for email view variable
     *
     * @param mixed $value View variable value
     * @return $this Chaining
     */
    public function email($value)
    {
        return parent::set($value, 'email');
    }

    /**
     * Setter for number view variable
     *
     * @param mixed $value View variable value
     * @return $this Chaining
     */
    public function number($value)
    {
        return parent::set($value, 'number');
    }

    /**
     * Setter for places view variable
     *
     * @param mixed $value View variable value
     * @return $this Chaining
     */
    public function places($value)
    {
        return parent::set($value, 'places');
    }

    /**
     * Setter for place view variable
     *
     * @param mixed $value View variable value
     * @return $this Chaining
     */
    public function place($value)
    {
        return parent::set($value, 'place');
    }

}
