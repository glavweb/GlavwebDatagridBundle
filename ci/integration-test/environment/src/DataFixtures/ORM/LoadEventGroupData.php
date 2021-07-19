<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\EventGroup;

/**
 * Class LoadEventGroupData
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class LoadEventGroupData extends Fixture implements OrderedFixtureInterface
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