<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter\Doctrine\ORM;

use Glavweb\DatagridBundle\Filter\Doctrine\AbstractFilterFactory;

/**
 * Class FilterFactory
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class FilterFactory extends AbstractFilterFactory
{
    /**
     * @var array
     */
    protected $types = [
        'string'   => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\StringFilter',
        'number'   => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\NumberFilter',
        'boolean'  => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\BooleanFilter',
        'datetime' => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\DateTimeFilter',
        'enum'     => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\EnumFilter',
        'model'    => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\ModelFilter',
        'callback' => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\CallbackFilter',
    ];
}