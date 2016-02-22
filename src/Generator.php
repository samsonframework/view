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
 * TODO: Do not generate class fields with empty values
 * TODO: Generate constants with field names
 *
 * @package samsonframework\view
 */
class Generator
{
    /** string All generated view classes will end with this suffix */
    const VIEW_CLASSNAME_SUFFIX = 'View';

    /** @var array Collection of PHP reserved words */
    protected static $reservedWords = array('list');

    /** @var Metadata[] Collection of view metadata */
    protected $metadata = array();

    /** @var \samsonphp\generator\Generator */
    protected $generator;

    /** @var string Generated classes namespace prefix */
    protected $namespacePrefix;

    /** @var string Collection of namespace parts to be ignored in generated namespaces */
    protected $ignoreNamespace = array();

    /** @var array Collection of view files */
    protected $files = array();

    /** @var string Scanning entry path */
    protected $entryPath;

    /** @var string Parent view class name */
    protected $parentViewClass;

    /**
     * Generator constructor.
     *
     * @param \samsonphp\generator\Generator $generator PHP code generator instance
     * @param string                         $namespacePrefix Generated classes namespace will have it
     * @param array                          $ignoreNamespace Namespace parts that needs to ignored
     * @param string                         $parentViewClass Generated classes will extend it
     */
    public function __construct(
        \samsonphp\generator\Generator $generator,
        $namespacePrefix,
        array $ignoreNamespace = array(),
        $parentViewClass = \samsonframework\view\View::class
    )
    {
        $this->generator = $generator;
        $this->parentViewClass = $parentViewClass;
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
        $this->entryPath = $source;

        $path = isset($path) ? $path : $source;

        // Recursively go deeper into inner folders for scanning
        $folders  = glob($path.'/*', GLOB_ONLYDIR);
        foreach ($folders as $folder) {
            $this->scan($source, $extensions, $folder);
        }

        // Iterate file extensions
        foreach ($extensions as $extension) {
            foreach (glob(rtrim($path, '/') . '/*.'.$extension) as $file) {
                $this->files[str_replace($source, '', $file)] = $file;
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

                // Ignore static variables
                if (isset($tokens[$idx-1]) && $tokens[$idx-1][0] === T_DOUBLE_COLON) {
                    $metadata->static[$variableName] = $variableText;
                    continue;
                }

                // If next token is object operator
                if ($tokens[$idx + 1][0] === T_OBJECT_OPERATOR) {
                    // Ignore $this
                    if ($variableName === 'this') {
                        continue;
                    }

                    // And two more tokens
                    $variableText .= $tokens[$idx + 1][1] . $tokens[$idx + 2][1];

                    // Store object variable
                    $metadata->variables[$this->changeName($variableName)] = $variableText;
                    // Store view variable key - actual object name => full variable usage
                    $metadata->originalVariables[$this->changeName($variableName)] = $variableName;
                } else {
                    // Store original variable name
                    $metadata->originalVariables[$this->changeName($variableName)] = $variableName;
                    // Store view variable key - actual object name => full variable usage
                    $metadata->variables[$this->changeName($variableName)] = $variableText;
                }
            } elseif ($token[0] === T_DOC_COMMENT) { // Match doc block comments
                // Parse variable type and name
                if (preg_match('/@var\s+(?<type>[^ ]+)\s+(?<variable>[^*]+)/', $token[1], $matches)) {
                    $metadata->types[substr(trim($matches['variable']), 1)] = $matches['type'];
                }
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
        foreach ($this->files as $relativePath => $absolutePath) {
            $this->metadata[$absolutePath] = $this->analyze($absolutePath);
            $this->metadata[$absolutePath]->path = $absolutePath;
            list($this->metadata[$absolutePath]->className,
                $this->metadata[$absolutePath]->namespace) = $this->generateClassName($absolutePath, $this->entryPath);
        }

        foreach ($this->metadata as $metadata) {
            $this->generateViewClass($metadata, $path);
        }
    }

    /** @return string Hash representing generator state */
    public function hash()
    {
        $hash = '';
        foreach ($this->files as $relativePath => $absolutePath) {
            $hash .= md5($relativePath.filemtime($absolutePath));
        }

        return md5($hash);
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
            ->defClass($metadata->className, '\\' . $this->parentViewClass)
            ->commentVar('string', 'Path to view file')
            ->defClassVar('$file', 'protected', $metadata->path)
            ->commentVar('string', 'View source code')
            ->defClassVar('$source', 'protected', '<<<\'EOT\'' . "\n" . file_get_contents($metadata->path) . "\n" . 'EOT');
            //->commentVar('array', 'Collection of view variables')
            //->defClassVar('$variables', 'public static', array_keys($metadata->variables))
            //->commentVar('array', 'Collection of view variable types')
            //->defClassVar('$types', 'public static', $metadata->types)
        ;

        // Iterate all view variables
        foreach (array_keys($metadata->variables) as $name) {
            $type = array_key_exists($name, $metadata->types) ? $metadata->types[$name] : 'mixed';
            $static = array_key_exists($name, $metadata->static) ? ' static' : '';
            $this->generator
                ->commentVar($type, 'View variable')
                ->defClassVar('$'.$name, 'public'.$static);

            // Do not generate setters for static variables
            if ($static !== ' static') {
                $this->generator->text($this->generateViewVariableSetter(
                    $name,
                    $metadata->originalVariables[$name],
                    $type
                ));
            }
        }

        // Iterate namespace and create folder structure
        $path .= '/'.str_replace('\\', '/', $metadata->namespace);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $newClassFile = $path.'/'.$metadata->className.'.php';
        file_put_contents(
            $newClassFile,
            '<?php'.$this->generator->endClass()->flush()
        );

        // Make generated cache files accessible
        chmod($newClassFile, 0777);
    }

    /**
     * Generate constructor for application class.
     *
     * @param string $variable View variable name
     * @param string $original Original view variable name
     * @param string $type Variable type
     *
     * @return string View variable setter method
     */
    protected function generateViewVariableSetter($variable, $original, $type = 'mixed')
    {
        // Define type hint
        $typeHint = strpos($type, '\\') !== false ? $type.' ' : '';

        $class = "\n\t" . '/**';
        $class .= "\n\t" . ' * Setter for ' . $variable . ' view variable';
        $class .= "\n\t" . ' *';
        $class .= "\n\t" . ' * @param '.$type.' $value View variable value';
        $class .= "\n\t" . ' * @return $this Chaining';
        $class .= "\n\t" . ' */';
        $class .= "\n\t" . 'public function ' . $variable . '('.$typeHint.'$value)';
        $class .= "\n\t" . '{';
        $class .= "\n\t\t" . 'return parent::set($value, \'' . $original . '\');';
        $class .= "\n\t" . '}' . "\n";

        return $class;
    }
}
