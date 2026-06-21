<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Datagrid\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Glavweb\DatagridBundle\Datagrid\DatagridInterface;

/**
 * Class AbstractDatagrid.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractDatagrid implements DatagridInterface
{
    /**
     * @var mixed[]
     */
    protected array $orderings;

    protected ?int $firstResult = null;

    protected ?int $maxResults = null;

    protected string|int $hydrationMode = AbstractQuery::HYDRATE_ARRAY;

    abstract public function getList(): array;

    abstract public function getTotal(): int;

    /**
     * @return mixed[]
     */
    public function getOrderings(): array
    {
        return $this->orderings;
    }

    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function getHydrationMode(): int|string
    {
        return $this->hydrationMode;
    }

    public function setHydrationMode(int|string $hydrationMode): void
    {
        $this->hydrationMode = $hydrationMode;
    }

    protected function clearParameters(array $parameters): array
    {
        return array_filter($parameters, static function ($value): bool {
            if ($value === []) {
                return false;
            }

            return $value !== null;
        });
    }
}
