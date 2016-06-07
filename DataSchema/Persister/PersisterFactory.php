<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\DataSchema\Persister;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Glavweb\DatagridBundle\DataSchema\DataSchema;

/**
 * Class PersisterFactory
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class PersisterFactory
{
    /**
     * Constants for db drivers
     */
    const DB_DRIVER_ORM = 'orm';

    /**
     * PersisterFactory constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine   = $doctrine;
    }

    /**
     * @param string $dbDriver
     * @param DataSchema $dataSchema
     * @return EntityPersister
     */
    public function createPersister($dbDriver, DataSchema $dataSchema)
    {
        switch ($dbDriver) {
            case self::DB_DRIVER_ORM:
                return
                    new EntityPersister($this->doctrine, $dataSchema);
                break;
        }

        throw new \RuntimeException(sprintf('Db driver "%s" not support.', $dbDriver));
    }
}