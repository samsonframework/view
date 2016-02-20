<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 18.02.16 at 14:17
 */
namespace samsonframework\view;

/**
 * Views generator, this class scans resource for view files and creates
 * appropriate View class ancestors with namespace as relative view location
 * and file name as View class name ending with "View".
 *
 * Generator also analyzes view files content and creates protected class field
 * members for every variable used inside with chainable setter for this field,
 * to help IDE and developer in creating awesome code.
 *
 * @package samsonframework\view
 */
class Generator
{
    /** @var Metadata[] Collection of view metadata */
    protected $metadata = array();

    /** @var \samsonphp\generator\Generator */
    protected $generator;

    /**
     * Generator constructor.
     *
     * @param \samsonphp\generator\Generator $generator
     */
    public function __construct(\samsonphp\generator\Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Recursively scan path for files with specified extensions.
     *
     * @param string $path Entry path for scanning
     * @param array  $extensions Collection of file extensions without dot
     */
    public function scan($path, array $extensions = array(View::DEFAULT_EXT))
    {
        foreach ($extensions as $extension) {
            foreach (glob(rtrim($path, '/') . '/*.'.$extension) as $file) {
                $this->metadata[$file] = $this->analyze($file);
            }
        }
    }

    /**
     * Analyze view file and create its metadata.
     *
     * @param string $file Path to view file
     * @return Metadata View file metadata
     */
    public function analyze($file)
    {
        $metadata = new Metadata();
        $metadata->path = $file;
        // Use PHP tokenizer to find variables
        foreach ($tokens = token_get_all(file_get_contents($file)) as $idx => $token) {
            if (!is_string($token) && $token[0] === T_VARIABLE) {
                // Store variable
                $variableText = $token[1];
                // Store variable name
                $variableName = ltrim($token[1], '$');
                // If next token is object operator
                if ($tokens[$idx+1][0] === T_OBJECT_OPERATOR) {
                    $variableName = $tokens[$idx+2][1];
                    // And two more tokens
                    $variableText .= $tokens[$idx+1][1].$variableName;
                }
                // Store view variable key - actual object name => full varaible usage
                $metadata->variables[$variableName] = $variableText;
            }
        }

        var_dump($metadata);
    }

    public function generate(Metadata $metadata)
    {
        //$navigationID, $navigationName, $entityName, $navigationFields, $parentClass = '\samsoncms\api\query\Entity'
//        $this->generator
//            ->multiComment(array('Class for fetching "'.$metadata->entityRealName.'" instances from database'))
//            ->defClass($metadata->entity.$suffix, $defaultParent)
//        ;
//
//        foreach ($metadata->allFieldIDs as $fieldID => $fieldName) {
//
//        }
//
//        return $this->generator
//            ->commentVar('array', 'Collection of real additional field names')
//            ->defClassVar('$fieldRealNames', 'public static', $metadata->realNames)
//            ->commentVar('array', 'Collection of additional field names')
//            ->defClassVar('$fieldNames', 'public static', $metadata->allFieldNames)
//            // TODO: two above fields should be protected
//            ->commentVar('array', 'Collection of navigation identifiers')
//            ->defClassVar('$navigationIDs', 'protected static', array($metadata->entityID))
//            ->commentVar('string', 'Entity full class name')
//            ->defClassVar('$identifier', 'protected static', $this->fullEntityName($metadata->entity, $namespace))
//            ->commentVar('array', 'Collection of localized additional fields identifiers')
//            ->defClassVar('$localizedFieldIDs', 'protected static', $metadata->localizedFieldIDs)
//            ->commentVar('array', 'Collection of NOT localized additional fields identifiers')
//            ->defClassVar('$notLocalizedFieldIDs', 'protected static', $metadata->notLocalizedFieldIDs)
//            ->commentVar('array', 'Collection of localized additional fields identifiers')
//            ->defClassVar('$fieldIDs', 'protected static', $metadata->allFieldIDs)
//            ->commentVar('array', 'Collection of additional fields value column names')
//            ->defClassVar('$fieldValueColumns', 'protected static', $metadata->allFieldValueColumns)
//            ->endClass()
//            ->flush()
//            ;
    }
}
