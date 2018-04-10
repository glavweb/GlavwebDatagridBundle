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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinBuilderInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\ORM\JoinBuilder;
use Glavweb\DatagridBundle\Filter\Doctrine\AbstractFilterFactory;

/**
 * Class FilterFactory
 * @package Glavweb\DatagridBundle
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class FilterFactory extends AbstractFilterFactory
{
    /**
     * @var JoinBuilder
     */
    private $joinBuilder;

    /**
     * FilterFactory constructor.
     *
     * @param Registry $doctrine
     * @param JoinBuilder $joinBuilder
     */
    public function __construct(Registry $doctrine, JoinBuilder $joinBuilder)
    {
        parent::__construct($doctrine);

        $this->joinBuilder = $joinBuilder;
    }

    /**
     * @return array
     */
    protected function getTypes(): array
    {
        return [
            'string'   => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\StringFilter',
            'number'   => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\NumberFilter',
            'boolean'  => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\BooleanFilter',
            'datetime' => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\DateTimeFilter',
            'enum'     => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\EnumFilter',
            'model'    => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\ModelFilter',
            'callback' => 'Glavweb\DatagridBundle\Filter\Doctrine\ORM\CallbackFilter',
        ];
    }

    /**
     * @return JoinBuilderInterface
     */
    protected function getJoinBuilder(): JoinBuilderInterface
    {
        return $this->joinBuilder;
    }
}