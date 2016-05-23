<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Tests\Fixtures\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Glavweb\DatagridBundle\Tests\Fixtures\Entity\EventGroup;

/**
 * Class LoadEventGroupData
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class LoadEventGroupData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            ['name' => 'Event group 1', 'reference' => 'event-group-1'],
            ['name' => 'Event group 2', 'reference' => 'event-group-2'],
        ];

        foreach ($data as $item) {
            $eventGroup = new EventGroup();
            $eventGroup->setName($item['name']);
            $manager->persist($eventGroup);

            $this->addReference($item['reference'], $eventGroup);
        }

        $manager->flush();
    }

    /**
     * Set loading order.
     *
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }
}