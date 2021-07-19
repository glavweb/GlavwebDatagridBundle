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
use App\Entity\Event;
use App\Entity\EventDetail;
use App\Entity\EventGroup;
use App\Entity\Tag;

/**
 * Class LoadEventData
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class LoadEventData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $data = [
            [
                'name'        => 'Event 1',
                'eventDetail' => 'event-detail-1',
                'eventGroup'  => 'event-group-1',
                'tags'        => ['tag-1', 'tag-2'],
                'reference'   => 'event-1'
            ],
            [
                'name'        => 'Event 2',
                'eventDetail' => 'event-detail-2',
                'eventGroup'  => 'event-group-1',
                'tags'        => ['tag-3'],
                'reference'   => 'event-2'
            ],
            [
                'name'        => 'Event 3',
                'eventDetail' => 'event-detail-3',
                'eventGroup'  => 'event-group-2',
                'tags'        => ['tag-4', 'tag-5'],
                'reference'   => 'event-3'
            ],
        ];

        foreach ($data as $item) {
            /** @var EventDetail $eventDetail */
            /** @var EventGroup $eventGroup */
            $eventDetail = $this->getReference($item['eventDetail']);
            $eventGroup  = $this->getReference($item['eventGroup']);

            $event = new Event();
            $event->setName($item['name']);
            $event->setEventDetail($eventDetail);
            $event->setEventGroup($eventGroup);

            foreach ($item['tags'] as $tag) {
                /** @var Tag $tag */
                $tag = $this->getReference($tag);
                $event->addTag($tag);
            }

            $manager->persist($event);

            $this->addReference($item['reference'], $event);
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
        return 2;
    }
}