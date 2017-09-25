<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Hydrator\Doctrine;

use Doctrine\ORM\Internal\Hydration\ArrayHydrator;

/**
 * Class DatagridHydrator
 *
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class DatagridHydrator extends ArrayHydrator
{
    /**
     * @return array
     */
    protected function hydrateAllData()
    {
        $result = parent::hydrateAllData();

        if (isset($result[0]) && is_array($result[0]) && isset($result[0][0])) {
            $mergedResult = [];

            foreach($result as $row) {
                $entityData = $row[0];
                unset($row[0]);

                $mergedResult[] = array_merge($entityData, $row);
            }

            return $mergedResult;
        }

        return $result;
    }
}
