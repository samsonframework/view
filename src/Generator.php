<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 18.02.16 at 14:17
 */
namespace samsonframework\view;

use samsonframework\view\exception\GeneratedViewPathHasReservedWord;

/**
 * Views generator, this class scans resource for view files and creates
 * appropriate View class ancestors with namespace as relative view location
 * and file name as View class name ending with "View".
 *
 * Generator also analyzes view files content and creates protected class field
 * members for every variable used inside with chainable setter for this field,
 * to help IDE and developer in creating awesome code.
 *
 * TODO: Check for reserved keywords(like list) in namespaces
 * TODO: Somehow know view variable type(typehint??) and add comments and type-hints to generated classes.
 * TODO: Clever analysis for foreach, if, and so on language structures, we do not need to create variables for loop iterator.
 * TODO: If a variable is used in foreach - this is an array or Iteratable ancestor - we can add typehint automatically
 * TODO: Analyze view file php doc comments to get variable types
 * TODO: If a token variable is not $this and has "->" - this is object, maybe type-hint needs to be added.
 * TODO: Add caching logic to avoid duplicate file reading
 *
 * @package samsonframework\view
 */
class Generator
{
    /** string All generated view classes will end with this suffix */
    const VIEW_CLASSNAME_SUFFIX = 'View';

    /** @var array Collection of PHP reserved words */
    protected static $reservedWords = array(
        'list'
    );

    /** @var Metadata[] Collection of view metadata */
    protected $metadata = array();

    /** @var \samsonphp\generator\Generator */
    protected $generator;

    /** @var string Generated classes namespace prefix */
    protected $namespacePrefix;

    /** @var string Collection of namespace parts to be ignored in generated namespaces */
    protected $ignoreNamespace = array();

    /**
     * Generator constructor.
     *
     * @param \samsonphp\generator\Generator $generator
     * @param string                         $namespacePrefix
     * @param array                          $ignoreNamespace
     */
    public function __construct(\samsonphp\generator\Generator $generator, $namespacePrefix, array $ignoreNamespace = array())
    {
        $this->generator = $generator;
        $this->ignoreNamespace = $ignoreNamespace;
        $this->namespacePrefix = rtrim(ltrim($namespacePrefix, '\\'), '\\').'\\';
    }

    /**
     * Change variable name to camel caps format.
     *
     * @param string $variable
     *
     * @return string Changed variable name
     */
    public function changeName($variable)
    {
        return lcfirst(
            implode(
                '',
                array_map(
                    function ($element) { return ucfirst($element);},
                    explode('_', $variable)
                )
            )
        );
    }

    /**
     * Recursively scan path for files with specified extensions.
     *
     * @param string $source     Entry point path
     * @param string $path       Entry path for scanning
     * @param array  $extensions Collection of file extensions without dot
     */
    public function scan($source, array $extensions = array(View::DEFAULT_EXT), $path = null)
    {
        $path = isset($path) ? $path : $source;

        // Recursively go deeper into inner folders for scanning
        $folders  = glob($path.'/*', GLOB_ONLYDIR);
        foreach ($folders as $folder) {
            $this->scan($source, $extensions, $folder);
        }

        // Iterate file extensions
        foreach ($extensions as $extension) {
            foreach (glob(rtrim($path, '/') . '/*.'.$extension) as $file) {
                $this->metadata[$file] = $this->analyze($file);
                $this->metadata[$file]->path = str_replace($source, '', $file);
                list($this->metadata[$file]->className,
                    $this->metadata[$file]->namespace) = $this->generateClassName($file, $source);
            }
        }
    }

    /**
     * Analyze view file and create its metadata.
     *
     * @param string $file Path to view file
     *
     * @return Metadata View file metadata
     */
    public function analyze($file)
    {
        $metadata = new Metadata();
        // Use PHP tokenizer to find variables
        foreach ($tokens = token_get_all(file_get_contents($file)) as $idx => $token) {
            if (!is_string($token) && $token[0] === T_VARIABLE) {
                // Store variable
                $variableText = $token[1];
                // Store variable name
                $variableName = ltrim($token[1], '$');
                // If next token is object operator
                if ($tokens[$idx + 1][0] === T_OBJECT_OPERATOR) {
                    $variableName = $tokens[$idx + 2][1];
                    // And two more tokens
                    $variableText .= $tokens[$idx + 1][1] . $variableName;
                }
                // Store view variable key - actual object name => full varaible usage
                $metadata->variables[$this->changeName($variableName)] = $variableText;
            }
        }

        return $metadata;
    }

    /**
     * Generic class name and its name space generator.
     *
     * @param string $file      Full path to view file
     * @param string $entryPath Entry path
     *
     * @return array Class name[0] and namespace[1]
     * @throws GeneratedViewPathHasReservedWord
     */
    protected function generateClassName($file, $entryPath)
    {
        // Get only file name as a class name with suffix
        $className = ucfirst(pathinfo($file, PATHINFO_FILENAME) . self::VIEW_CLASSNAME_SUFFIX);

        // Get namespace as part of file path relatively to entry path
        $nameSpace = rtrim(ltrim(
            str_replace(
                '/',
                '\\',
                str_replace($entryPath, '', pathinfo($file, PATHINFO_DIRNAME))
            ),
            '\\'
        ), '\\');

        // Remove ignored parts from namespaces
        $nameSpace = str_replace($this->ignoreNamespace, '', $nameSpace);

        // Check generated namespaces
        foreach (static::$reservedWords as $reservedWord) {
            if (strpos($nameSpace, '\\' . $reservedWord) !== false) {
                throw new GeneratedViewPathHasReservedWord($file.'('.$reservedWord.')');
            }
        }

        // Return collection for further usage
        return array($className, rtrim($this->namespacePrefix . $nameSpace, '\\'));
    }

    /**
     * Generate view classes.
     *
     * @param string $path Entry path for generated classes and folders
     */
    public function generate($path = __DIR__)
    {
        foreach ($this->metadata as $metadata) {
            $this->generateViewClass($metadata, $path);
        }
    }

    /**
     * Create View class ancestor.
     *
     * @param Metadata $metadata View file metadata
     * @param string   $path Entry path for generated classes and folders
     */
    protected function generateViewClass(Metadata $metadata, $path)
    {
        $this->generator
            ->defNamespace($metadata->namespace)
            ->multiComment(array('Class for view "'.$metadata->path.'" rendering'))
            ->defClass($metadata->className, '\\' . View::class)
            ->commentVar('string', 'Path to view file')
            ->defClassVar('$path', 'protected', $metadata->path)
            ->commentVar('array', 'Collection of view variables')
            ->defClassVar('$variables', 'public static', array_keys($metadata->variables));

        // Iterate all view variables
        foreach (array_keys($metadata->variables) as $name) {
            $this->generator
                ->commentVar('mixed', 'View variable')
                ->defClassVar('$'.$name, 'public')
                ->text($this->generateViewVariableSetter($name));
        }

        // Iterate namespace and create folder structure
        $path .= '/'.str_replace('\\', '/', $metadata->namespace);
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        file_put_contents(
            $path.'/'.$metadata->className.'.php',
            '<?php'.$this->generator->endClass()->flush()
        );
    }

    /**
     * Generate constructor for application class.
     *
     * @param string $variable View variable name
     *
     * @return string View variable setter method
     */
    protected function generateViewVariableSetter($variable)
    {
        $class = "\n\t" . '/**';
        $class .= "\n\t" . ' * Setter for ' . $variable . ' view variable';
        $class .= "\n\t" . ' *';
        $class .= "\n\t" . ' * @param mixed $value View variable value';
        $class .= "\n\t" . ' * @return $this Chaining';
        $class .= "\n\t" . ' */';
        $class .= "\n\t" . 'public function ' . $variable . '($value)';
        $class .= "\n\t" . '{';
        $class .= "\n\t\t" . 'return parent::set($value, \'' . $variable . '\');';
        $class .= "\n\t" . '}' . "\n";

        return $class;
    }
}
