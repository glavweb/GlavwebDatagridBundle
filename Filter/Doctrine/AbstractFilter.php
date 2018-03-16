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

use Doctrine\ORM\Mapping\ClassMetadata;
use Glavweb\DatagridBundle\Filter\FilterInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class AbstractFilter
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * Operator types
     */
    const EQ           = '=';
    const NEQ          = '<>';
    const LT           = '<';
    const LTE          = '<=';
    const GT           = '>';
    const GTE          = '>=';
    const IN           = 'IN';
    const NIN          = 'NIN';
    const CONTAINS     = 'CONTAINS';
    const NOT_CONTAINS = '!';

    /**
     * @var int
     */
    protected static $uniqueParameterId = 0;

    /**
     * @var array
     */
    protected $options = [
        'field_type' => null,
        'operator'   => null,
        'param_name' => null,
        'join_map'   => null,
    ];

    /**
     * @var string
     */
    protected $name;


    /**
     * @param mixed $queryBuilder
     * @param string $alias
     * @param string $value
     */
    public abstract function filter($queryBuilder, $alias, $value);

    /**
     * @return array
     */
    protected abstract function getAllowOperators();

    /**
     * Default operator. Use if operator can't defined.
     *
     * @return string
     */
    protected abstract function getDefaultOperator();

    /**
     * @param string $value
     * @param array  $allowOperators
     * @param string $defaultOperator
     * @return array
     */
    public static function separateOperator($value, array $allowOperators = null, $defaultOperator = self::CONTAINS)
    {
        $operators = array(
            '<>' => self::NEQ,
            '<=' => self::LTE,
            '>=' => self::GTE,
            '<'  => self::LT,
            '>'  => self::GT,
            '='  => self::EQ,
            '!=' => self::NEQ,
            '!'  => self::NOT_CONTAINS,
        );

        if ($allowOperators === null) {
            $allowOperators = array_keys($operators);
        }

        $operator = null;
        if (preg_match('/^(?:\s*(<>|<=|>=|<|>|=|!=|!))?(.*)$/', $value, $matches)) {
            $operator = isset($operators[$matches[1]]) ? $operators[$matches[1]] : null;
            $value    = $matches[2];
        }

        if (!$operator || !in_array($operator, $allowOperators)) {
            $operator = $defaultOperator;
        }

        return array($operator, $value);
    }

    /**
     * @param string $field
     * @return string
     */
    public static function makeParamName($field)
    {
        self::$uniqueParameterId++;

        return str_replace('.', '_', $field) . '_' . self::$uniqueParameterId;
    }

    /**
     * Filter constructor.
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, array $options = [])
    {
        $this->name    = $name;
        $this->options = array_merge($this->options, $options);

        if (!isset($this->options['param_name'])) {
            $this->options['param_name'] = $name;
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption($name)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new \RuntimeException(sprintf('Option "%s" not found.', $name));
        }

        return $this->options[$name];
    }

    /**
     * @return JoinMap|null
     */
    public function getJoinMap()
    {
        return $this->getOption('join_map');
    }

    /**
     * @return bool
     */
    public function hasJoinMap()
    {
        return (bool)$this->getJoinMap();
    }

    /**
     * @return mixed
     */
    public function getParamName()
    {
        return $this->getOption('param_name');
    }

    /**
     * @param mixed  $value
     * @param string $currentOperator
     * @param array  $replaces
     * @param array  $allowOperators
     * @param string $defaultOperator
     * @return array
     */
    public static function guessOperator($value, $currentOperator = null, array $replaces = [], array $allowOperators = null, $defaultOperator = self::CONTAINS)
    {
        if (is_array($value)) {
            // Validate operator
            if ($currentOperator && !in_array($currentOperator, [self::EQ, self::NEQ])) {
                throw new \RuntimeException(sprintf('Operator "%s" is not valid.', $currentOperator));
            }

            $operator = $currentOperator == self::NEQ ? self::NIN : self::IN;

        } else {
            $value = trim($value);

            $operator = $currentOperator;
            if (!$operator) {
                list($operator, $value) = self::separateOperator($value, $allowOperators, $defaultOperator);
            }
        }

        foreach ($replaces as $replaceFrom => $replaceTo) {
            if ($operator == $replaceFrom) {
                $operator = $replaceTo;
            }
        }

        return [$operator, $value];
    }

    /**
     * @param mixed $value
     * @param array $replaces
     * @return array
     */
    protected function getOperatorAndValue($value, array $replaces = [])
    {
        $currentOperator = $this->getOption('operator');

        list($operator, $value) = self::guessOperator(
            $value,
            $currentOperator,
            $replaces,
            $this->getAllowOperators(),
            $this->getDefaultOperator()
        );

        $this->checkOperator($operator);

        return [$operator, $value];
    }

    /**
     * @param string $operator
     */
    protected function checkOperator($operator)
    {
        $allowOperators = $this->getAllowOperators();

        if (!in_array($operator, $allowOperators)) {
            throw new \RuntimeException(sprintf('Operator "%s" not allowed.', $operator));
        }
    }

    /**
     * @param array $values
     * @return bool
     */
    protected function existsOperatorsInValues(array $values)
    {
        foreach ($values as $value) {
            list($operator) = self::separateOperator($value, null, null);

            if ($operator) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function getColumnName(string $fieldName): string
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getOption('class_metadata');

        if (!$classMetadata instanceof ClassMetadata) {
            throw new \RuntimeException('The ClassMetadata is not defined.');
        }

        return $classMetadata->getColumnName($fieldName);
    }
}
