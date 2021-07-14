<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Glavweb\DatagridBundle\Filter\TypeGuess;

/**
 * Class FilterTypeGuesser
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class FilterTypeGuesser
{
    /**
     * @param EntityManager $entityManager
     * @param               $propertyName
     * @param ClassMetadata $metadata
     * @param array         $options
     * @return TypeGuess
     * @throws \Doctrine\DBAL\Exception
     */
    public function guessType(EntityManager $entityManager, $propertyName, ClassMetadata $metadata, array $options = [])
    {
        if (!isset($metadata->fieldMappings[$propertyName]['fieldName'])) {
            $found = false;
            foreach ($metadata->subClasses as $subClass) {
                $subClassMetadata = $entityManager->getClassMetadata($subClass);

                if ($subClassMetadata->hasField($propertyName)) {
                    $metadata = $subClassMetadata;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new \RuntimeException(
                    sprintf('Field name "%s" not found in class "%s".', $propertyName, $metadata->getName())
                );
            }
        }

        if (isset($metadata->fieldMappings[$propertyName]['id']) && $metadata->fieldMappings[$propertyName]['id']) {
            return new TypeGuess('model', $options, TypeGuess::HIGH_CONFIDENCE);
        }

        $options['field_name'] = $metadata->fieldMappings[$propertyName]['fieldName'];
        $options['field_type'] = $metadata->getTypeOfField($propertyName);

        // Is field type
        switch ($options['field_type']) {
            case 'boolean':
                return new TypeGuess('boolean', $options, TypeGuess::HIGH_CONFIDENCE);

            case 'datetime':
            case 'vardatetime':
            case 'datetimetz':
            case 'time':
            case 'date':
                return new TypeGuess('datetime', $options, TypeGuess::HIGH_CONFIDENCE);

            case 'decimal':
            case 'float':
            case 'integer':
            case 'bigint':
            case 'smallint':
                return new TypeGuess('number', $options, TypeGuess::HIGH_CONFIDENCE);

            case 'string':
            case 'text':
                return new TypeGuess('string', $options, TypeGuess::HIGH_CONFIDENCE);
        }

        // If enum type
        $reflectionClass = new \ReflectionClass(Type::getType($options['field_type']));
        if ($reflectionClass->isSubclassOf('\Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType')) {
            return new TypeGuess('enum', $options, TypeGuess::HIGH_CONFIDENCE);
        }

        return new TypeGuess('string', $options, TypeGuess::LOW_CONFIDENCE);
    }
}
