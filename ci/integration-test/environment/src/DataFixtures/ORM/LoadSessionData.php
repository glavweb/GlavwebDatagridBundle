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
use App\Entity\Session;

/**
 * Class LoadSessionData
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class LoadSessionData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            ['name' => 'Session 1', 'event' => 'event-1', 'reference' => 'session-1'],
            ['name' => 'Session 2', 'event' => 'event-1', 'reference' => 'session-2'],
            ['name' => 'Session 3', 'event' => 'event-2', 'reference' => 'session-3'],
            ['name' => 'Session 4', 'event' => 'event-2', 'reference' => 'session-4'],
            ['name' => 'Session 5', 'event' => 'event-2', 'reference' => 'session-5'],
            ['name' => 'Session 6', 'event' => 'event-2', 'reference' => 'session-6'],
            ['name' => 'Session 7', 'event' => 'event-2', 'reference' => 'session-7'],
            ['name' => 'Session 8', 'event' => 'event-3', 'reference' => 'session-8'],
            ['name' => 'Session 9', 'event' => 'event-3', 'reference' => 'session-9'],
            ['name' => 'Session 10', 'event' => 'event-3', 'reference' => 'session-10'],
            ['name' => 'Session 11', 'event' => 'event-3', 'reference' => 'session-11'],
            ['name' => 'Session 12', 'event' => 'event-3', 'reference' => 'session-12'],
            ['name' => 'Session 13', 'event' => 'event-3', 'reference' => 'session-13'],
            ['name' => 'Session 14', 'event' => 'event-3', 'reference' => 'session-14'],
            ['name' => 'Session 15', 'event' => 'event-3', 'reference' => 'session-15'],
        ];

        foreach ($data as $item) {
            /** @var Event $event */
            $event = $this->getReference($item['event']);

            $session = new Session();
            $session->setName($item['name']);
            $session->setEvent($event);
            $manager->persist($session);

            $this->addReference($item['reference'], $session);
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
        return 3;
    }
}