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
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Class FilterTypeGuesser
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class FilterTypeGuesser
{
    /**
     * {@inheritdoc}
     */
    public function guessType($propertyName, ClassMetadata $metadata, array $options = [])
    {
        // Is association
        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);
            $types = [
                ClassMetadataInfo::ONE_TO_ONE,
                ClassMetadataInfo::ONE_TO_MANY,
                ClassMetadataInfo::MANY_TO_ONE,
                ClassMetadataInfo::MANY_TO_MANY
            ];

            if (in_array($mapping['type'], $types)) {
                return new TypeGuess('doctrine_orm_model', $options, Guess::HIGH_CONFIDENCE);
            }
        }

        if (!isset($metadata->fieldMappings[$propertyName]['fieldName'])) {
            throw new \RuntimeException(sprintf('Field name "%s" not found in class "%s".', $propertyName, $metadata->getName()));
        }

        $options['field_name'] = $metadata->fieldMappings[$propertyName]['fieldName'];
        $options['field_type'] = $metadata->getTypeOfField($propertyName);

        // Is field type
        switch ($options['field_type']) {
            case 'boolean':
                return new TypeGuess('doctrine_orm_boolean', $options, Guess::HIGH_CONFIDENCE);

            case 'datetime':
            case 'vardatetime':
            case 'datetimetz':
            case 'time':
            case 'date':
                return new TypeGuess('doctrine_orm_datetime', $options, Guess::HIGH_CONFIDENCE);

            case 'decimal':
            case 'float':
            case 'integer':
            case 'bigint':
            case 'smallint':
                return new TypeGuess('doctrine_orm_number', $options, Guess::HIGH_CONFIDENCE);

            case 'string':
            case 'text':
                return new TypeGuess('doctrine_orm_string', $options, Guess::HIGH_CONFIDENCE);
        }

        // If enum type
        $reflectionClass = new \ReflectionClass(Type::getType($options['field_type']));
        if ($reflectionClass->isSubclassOf('\Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType')) {
            return new TypeGuess('doctrine_orm_enum', $options, Guess::HIGH_CONFIDENCE);
        }

        return new TypeGuess('doctrine_orm_string', $options, Guess::LOW_CONFIDENCE);
    }
}
