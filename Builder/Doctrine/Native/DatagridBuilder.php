<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Builder\Doctrine\Native;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManager;
use Glavweb\DatagridBundle\Builder\DatagridBuilderInterface;
use Glavweb\DatagridBundle\Builder\Doctrine\AbstractDatagridBuilder;
use Glavweb\DatagridBundle\Datagrid\Doctrine\Native\Datagrid;
use Glavweb\DatagridBundle\Datagrid\EmptyDatagrid;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder;
use Glavweb\DatagridBundle\Exception\BuildException;
use Glavweb\DatagridBundle\Exception\Exception;
use Glavweb\DataSchemaBundle\DataSchema\DataSchema;

/**
 * Class Builder.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridBuilder extends AbstractDatagridBuilder implements DatagridBuilderInterface
{
    /**
     * @return Datagrid
     *
     * @throws BuildException
     */
    public function build(array $parameters = [], ?\Closure $callback = null): EmptyDatagrid|Datagrid
    {
        if (!$this->doctrine->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            throw new BuildException('The Native Datagrid support only PostgreSQL database.');
        }

        if (!$this->dataSchema instanceof DataSchema) {
            throw new BuildException('The Data Schema is not defined.');
        }

        $orderings = $this->getOrderings();
        $firstResult = $this->getFirstResult();
        $maxResults = $this->getMaxResults();
        $alias = $this->getAlias();

        try {
            /** @var EntityManager $em */
            $em = $this->doctrine->getManager();
            $queryBuilder = $this->createQueryBuilder($parameters);

            $datagridContext = new DatagridContext(
                $this->getEntityClassName(),
                $em,
                $queryBuilder,
                $this->filterStack,
                $this->dataSchema,
                $orderings,
                (int) $firstResult,
                $maxResults,
                $alias,
                $parameters
            );

            if (\is_callable($callback)) {
                $callback($datagridContext);
            }

            $datagrid = new Datagrid($datagridContext);
        } catch (Exception) {
            $datagrid = new EmptyDatagrid();
        }

        return $datagrid;
    }

    protected function createQueryBuilder(array $parameters): QueryBuilder
    {
        $alias = $this->getAlias();

        return $this->queryBuilderFactory->create(
            $parameters,
            $alias,
            $this->dataSchema,
            $this->filterStack,
            $this->getJoinMap()
        );
    }
}
