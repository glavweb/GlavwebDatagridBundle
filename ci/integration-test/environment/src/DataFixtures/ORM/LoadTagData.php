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
use App\Entity\Tag;

/**
 * Class LoadTagData
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class LoadTagData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            ['name' => 'Tag 1', 'reference' => 'tag-1'],
            ['name' => 'Tag 2', 'reference' => 'tag-2'],
            ['name' => 'Tag 3', 'reference' => 'tag-3'],
            ['name' => 'Tag 4', 'reference' => 'tag-4'],
            ['name' => 'Tag 5', 'reference' => 'tag-5'],
        ];

        foreach ($data as $item) {
            $tag = new Tag();
            $tag->setName($item['name']);
            $manager->persist($tag);

            $this->addReference($item['reference'], $tag);
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