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
use Glavweb\DatagridBundle\Tests\Fixtures\Entity\EventDetail;

/**
 * Class LoadEventDetailData
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class LoadEventDetailData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
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
     *
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }
}