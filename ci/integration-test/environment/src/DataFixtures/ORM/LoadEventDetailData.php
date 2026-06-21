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

use App\Entity\EventDetail;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Class LoadEventDetailData.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class LoadEventDetailData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            ['body' => 'Body for event detail 1', 'reference' => 'event-detail-1'],
            ['body' => 'Body for event detail 2', 'reference' => 'event-detail-2'],
            ['body' => 'Body for event detail 3', 'reference' => 'event-detail-3'],
        ];

        foreach ($data as $item) {
            $eventDetail = new EventDetail();
            $eventDetail->setBody($item['body']);
            $manager->persist($eventDetail);

            $this->addReference($item['reference'], $eventDetail);
        }

        $manager->flush();
    }

    /**
     * Set loading order.
     */
    public function getOrder(): int
    {
        return 1;
    }
}
