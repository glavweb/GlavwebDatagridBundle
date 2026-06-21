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
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinBuilderInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinMap;

/**
 * Class AbstractFilter.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * Operator types.
     */
    public const string EQ = '=';

    public const string NEQ = '<>';

    public const string LT = '<';

    public const string LTE = '<=';

    public const string GT = '>';

    public const string GTE = '>=';

    public const string IN = 'IN';

    public const string NIN = 'NIN';

    public const string CONTAINS = 'CONTAINS';

    public const string NOT_CONTAINS = '!';

    protected static int $uniqueParameterId = 0;

    /**
     * @var array<string, null>
     */
    protected array $options = [
        'field_type' => null,
        'operator' => null,
        'param_name' => null,
    ];

    abstract public function filter(mixed $queryBuilder, string $alias, string $value);

    abstract protected function getAllowOperators(): array;

    /**
     * Default operator. Use if operator can't be defined.
     */
    abstract protected function getDefaultOperator(): ?string;

    /**
     * @return array<int, string>
     */
    public static function separateOperator(string $value, ?array $allowOperators = null, string $defaultOperator = self::CONTAINS): array
    {
        $operators = [
            '<>' => self::NEQ,
            '<=' => self::LTE,
            '>=' => self::GTE,
            '<' => self::LT,
            '>' => self::GT,
            '=' => self::EQ,
            '!=' => self::NEQ,
            '!' => self::NOT_CONTAINS,
        ];

        if ($allowOperators === null) {
            $allowOperators = array_keys($operators);
        }

        $operator = null;
        if (preg_match('/^(?:\s*(<>|<=|>=|<|>|=|!=|!))?(.*)$/', $value, $matches)) {
            $operator = $operators[$matches[1]] ?? null;
            $value = $matches[2];
        }

        if (!$operator || !\in_array($operator, $allowOperators)) {
            $operator = $defaultOperator;
        }

        return [$operator, $value];
    }

    public static function makeParamName(string $field): string
    {
        ++self::$uniqueParameterId;

        return str_replace('.', '_', $field).'_'.self::$uniqueParameterId;
    }

    /**
     * Filter constructor.
     */
    public function __construct(
        protected string $name,
        array $options,
        protected string $fieldName,
        protected ClassMetadata $classMetadata,
        protected JoinBuilderInterface $joinBuilder,
        protected ?JoinMap $joinMap = null,
    ) {
        $this->options = array_merge($this->options, $options);

        if (!isset($this->options['param_name'])) {
            $this->options['param_name'] = $this->name;
        }
    }

    public function getName(): mixed
    {
        return $this->name;
    }

    public function setName(mixed $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array<string, null>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOption(string $name): mixed
    {
        if (!\array_key_exists($name, $this->options)) {
            throw new \RuntimeException(\sprintf('Option "%s" not found.', $name));
        }

        return $this->options[$name];
    }

    public function getJoinMap(): ?JoinMap
    {
        return $this->joinMap;
    }

    public function hasJoinMap(): bool
    {
        return (bool) $this->getJoinMap();
    }

    public function getParamName(): string
    {
        return $this->getOption('param_name');
    }

    /**
     * @return array<int, mixed>
     */
    public static function guessOperator(
        mixed $value,
        ?string $currentOperator = null,
        array $replaces = [],
        ?array $allowOperators = null,
        string $defaultOperator = self::CONTAINS,
    ): array {
        if (\is_array($value)) {
            // Validate operator
            if ($currentOperator && !\in_array($currentOperator, [self::EQ, self::NEQ], true)) {
                throw new \RuntimeException(\sprintf('Operator "%s" is not valid.', $currentOperator));
            }

            $operator = $currentOperator == self::NEQ ? self::NIN : self::IN;
        } else {
            $value = trim((string) $value);

            $operator = $currentOperator;
            if (!$operator) {
                [$operator, $value] = self::separateOperator($value, $allowOperators, $defaultOperator);
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
     * @return array<int, mixed>
     */
    protected function getOperatorAndValue(mixed $value, array $replaces = []): array
    {
        $currentOperator = $this->getOption('operator');

        [$operator, $value] = self::guessOperator(
            $value,
            $currentOperator,
            $replaces,
            $this->getAllowOperators(),
            $this->getDefaultOperator()
        );

        $this->checkOperator($operator);

        return [$operator, $value];
    }

    protected function checkOperator(string $operator): void
    {
        $allowOperators = $this->getAllowOperators();

        if (!\in_array($operator, $allowOperators)) {
            throw new \RuntimeException(\sprintf('Operator "%s" not allowed.', $operator));
        }
    }

    protected function existsOperatorsInValues(array $values): bool
    {
        foreach ($values as $value) {
            [$operator] = self::separateOperator($value, null, null);

            if ($operator) {
                return true;
            }
        }

        return false;
    }

    protected function getColumnName(string $fieldName): string
    {
        return $this->classMetadata->getColumnName($fieldName);
    }
}
