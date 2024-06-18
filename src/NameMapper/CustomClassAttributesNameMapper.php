<?php

declare(strict_types=1);

namespace Brick\JsonMapper\NameMapper;

use Brick\JsonMapper\NameMapper;
use ReflectionClass;

class CustomClassAttributesNameMapper implements NameMapper
{
    private const DOC_FIELD_NAME = "@jsonMapper";

    /**
     * Array made like:  
     * KEY: json prop  
     * VALUE: class prop (can be empty if no class prop has a link, in this case the mapName returns the json value and let handle the extra property process to the json mapper)
     *
     * @var array[string]
     */
    protected array $attributesLinking;

    public function __construct(string $mappingClass, bool $phpToJson) {
        $this->attributesLinking = [];
        
        $refClass = new ReflectionClass($mappingClass);
        $refProps = $refClass->getProperties();
        for ($i=0; $i < count($refProps); $i++) { 
            $refProp = $refProps[$i];
            $refPropDocs = $refProp->getDocComment();

            if(!$refPropDocs)
            {
                continue;
            }
            else
            {
                if(str_contains($refPropDocs, self::DOC_FIELD_NAME))
                {
                    $startPos = strpos($refPropDocs, self::DOC_FIELD_NAME) + strlen(self::DOC_FIELD_NAME);
                    $len = strpos($refPropDocs, PHP_EOL, $startPos) - $startPos;
                    $jsonPropName = trim(substr($refPropDocs, $startPos, $len));

                    if($phpToJson)
                    {
                        $this->attributesLinking[$refProp->getName()] = $jsonPropName;
                    }
                    else
                    {
                        $this->attributesLinking[$jsonPropName] = $refProp->getName();
                    }
                }
                else
                {
                    continue;
                }
            }
        }
    }

    public function mapName(string $name): string
    {
        if(array_key_exists($name, $this->attributesLinking))
        {
            return $this->attributesLinking[$name];
        }
        else
        {
            return $name;
        }
    }
}
