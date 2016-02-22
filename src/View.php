<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 18.02.16 at 14:15
 */
namespace samsonframework\view;

use samsonframework\core\RenderInterface;
use samsonframework\core\ViewInterface;
use samsonframework\view\exception\VariableKeyNotFound;
use samsonframework\view\exception\ViewFileNotFound;

/**
 * View class for rendering.
 * @package samsonframework\view
 */
class View implements ViewInterface
{
    /** Default view file extension */
    const DEFAULT_EXT = 'vphp';

    /** @var array Collection of $key => $value view data */
    protected $data = array();

    /** @var string Full path to view file */
    protected $file;

    /** @var string View souce code */
    protected $source;

    /** @var string Rendered view contents */
    protected $output;

    /**
     * Set current view for rendering.
     * Method searches for the shortest matching view path by $pathPattern,
     * from loaded views.
     *
     * Module saves all view data that has been set to a specific view in appropriate
     * view data collection entry. By default module creates vied data entry - VD_POINTER_DEF,
     * and until any call of iModule::view() or iModule::output(), all data that is iModule::set(),
     * is stored to that location.
     *
     * On the first call of iModule::view() or iModule::output(), this method changes the view data
     * pointer to actual relative view path, and copies(actually just sets view data pointer) all view
     * data set before to new view data pointer. This guarantees backward compatibility and gives
     * opportunity not to set the view path before setting view data to it.
     *
     * @param string $pathPattern Path pattern for view searching
     *
     * @return $this Chaining
     * @throws \Exception
     */
    public function view($pathPattern)
    {
        if (file_exists($pathPattern)) {
            $this->file = $pathPattern;
            return $this;
        }

        throw new ViewFileNotFound($pathPattern);
    }

    /**
     * Render current view.
     * Method uses current view context and outputs rendering
     * result.
     *
     * @return string Rendered view
     */
    public function output()
    {
        // Start buffering
        ob_start();

        // Make variables accessible directly in view
        extract($this->data);

        // Render view from source
        if (!empty($this->source)) {
            eval(' ?>' . $this->source . '<?php ');
        } else { // Render view from file
            include($this->file);
        }

        // Store buffer output
        $this->output = ob_get_contents();

        // Clear buffer
        ob_end_clean();

        // Remove variables from context to free memory
        foreach ($this->data as $key => $value) {
            unset($key);
        }

        // Returned rendered view
        return $this->output;
    }

    /**
     * Magic method for getting view variables.
     *
     * @param string $name Variable key
     *
     * @return mixed Value
     * @throws VariableKeyNotFound
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        throw new VariableKeyNotFound($name);
    }

    /**
     * Magic method for setting view variables.
     *
     * @param string $name      Variable key
     * @param array  $arguments Variable value
     *
     * @return $this Chaining
     * @throws VariableKeyNotFound
     */
    public function __call($name, $arguments)
    {
        if (count($arguments)) {
            $this->set($arguments[0], $name);
        }

        return $this;
    }

    /**
     * Set view variable.
     *
     * Passing an array as $value will add array key => values into current
     * view data collection. If $key is passed then an array variable with this
     * key will be added to view data collection beside adding array key => values.
     *
     * @param mixed       $value Variable value
     * @param string|null $key   Variable key\prefix for objects and arrays
     *
     * @return $this Chaining
     */
    public function set($value, $key = null)
    {
        // RenderInterface implementation
        if (is_object($value) && is_a($value, RenderInterface::class)) {
            $this->setRenderableObject($value, $key);
        } elseif (is_array($value)) { // Merge array into view data
            $this->data = array_merge($this->data, $value);
        }

        // Store key value
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Set renderable object as view variable.
     *
     * @param mixed       $object Object instance for rendering
     * @param string|null $key    Variable key\prefix for objects and arrays
     */
    protected function setRenderableObject($object, $key)
    {
        /** @var RenderInterface $object */
        // Generate objects view array data and merge it with view data
        $this->data = array_merge(
            $this->data,
            $object->toView(null !== $key ? $key : get_class($object))
        );
    }
}
