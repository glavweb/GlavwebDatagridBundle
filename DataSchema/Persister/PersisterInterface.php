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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Glavweb\DatagridBundle\DataSchema\DataSchema;

/**
 * Class PersisterInterface
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
interface PersisterInterface
{
    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @return array
     */
    public function getManyToManyData(array $associationMapping, $id, array $databaseFields, array $conditions = []);

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @return array
     */
    public function getOneToManyData(array $associationMapping, $id, array $databaseFields, array $conditions = []);

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @return array
     */
    public function getManyToOneData(array $associationMapping, $id, array $databaseFields, array $conditions = []);

    /**
     * @param array $associationMapping
     * @param mixed $id
     * @param array $databaseFields
     * @param array $conditions
     * @return array
     */
    public function getOneToOneData(array $associationMapping, $id, array $databaseFields, array $conditions = []);
}
