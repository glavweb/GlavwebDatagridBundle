<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\JoinMap\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class JoinMap.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class JoinMap
{
    /**
     * Constants of join types.
     */
    public const string JOIN_TYPE_LEFT = 'left';

    public const string JOIN_TYPE_INNER = 'inner';

    public const string JOIN_TYPE_NONE = 'none';

    /**
     * @var mixed[]
     */
    private array $joinMap = [];

    /**
     * JoinMap constructor.
     */
    public function __construct(private readonly string $alias, private readonly ClassMetadata $classMetadata)
    {
        $this->joinMap[$this->alias] = [];
    }

    public static function makeAlias(string $path, string $field): string
    {
        return str_replace('.', '_', $path).'_'.$field;
    }

    /**
     * @return $this
     */
    public function join(
        string $path,
        string $field,
        bool $hasSelect = true,
        array $selectFields = [],
        string $joinType = 'left',
        string $conditionType = Join::WITH,
        ?string $condition = null,
    ): static {
        $this->joinMap[$path][] = [
            'field' => $field,
            'hasSelect' => $hasSelect,
            'selectFields' => $selectFields,
            'joinType' => $joinType,
            'conditionType' => $conditionType,
            'condition' => $condition,
        ];

        return $this;
    }

    public function merge(self $joinMap): void
    {
        $this->joinMap = array_merge_recursive($this->joinMap, $joinMap->getJoinMap());
    }

    /**
     * @return mixed[]
     */
    public function getJoinMap(): array
    {
        return $this->joinMap;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }
}
