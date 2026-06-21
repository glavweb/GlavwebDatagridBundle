<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Filter\Doctrine\Native;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Glavweb\DatagridBundle\Filter\Doctrine\AbstractFilterFactory;
use Glavweb\DatagridBundle\JoinMap\Doctrine\JoinBuilderInterface;
use Glavweb\DatagridBundle\JoinMap\Doctrine\Native\JoinBuilder;

/**
 * Class FilterFactory.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class FilterFactory extends AbstractFilterFactory
{
    /**
     * FilterFactory constructor.
     */
    public function __construct(Registry $doctrine, private readonly JoinBuilder $joinBuilder)
    {
        parent::__construct($doctrine);
    }

    protected function getTypes(): array
    {
        return [
            'string' => StringFilter::class,
            'number' => NumberFilter::class,
            'boolean' => BooleanFilter::class,
            'datetime' => DateTimeFilter::class,
            'enum' => EnumFilter::class,
            'model' => ModelFilter::class,
            'callback' => CallbackFilter::class,
        ];
    }

    protected function getJoinBuilder(): JoinBuilderInterface
    {
        return $this->joinBuilder;
    }
}
