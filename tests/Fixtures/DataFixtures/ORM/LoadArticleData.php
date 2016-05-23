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
use Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article;
use Glavweb\DatagridBundle\Tests\Fixtures\Entity\Event;

/**
 * Class LoadArticleData
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class LoadArticleData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            [
                'name'        => 'Article 1',
                'slug'        => 'article-1',
                'body'        => 'Article about Katy',
                'countEvents' => 2,
                'publish'     => true,
                'publishAt'   => '2016-05-09 10:00',
                'events'      => ['event-1', 'event-2'],
                'reference'   => 'article-1'
            ],
            [
                'name'        => 'Article 2',
                'slug'        => 'article-2',
                'body'        => 'Article about Mary',
                'countEvents' => 1,
                'publish'     => false,
                'publishAt'   => '2016-05-10 11:00',
                'events'      => ['event-3'],
                'reference'   => 'article-2'
            ],
            [
                'name'        => 'Article 3',
                'slug'        => 'article-3',
                'body'        => 'Article about Niki',
                'countEvents' => 0,
                'publish'     => false,
                'publishAt'   => '2016-05-11 12:00',
                'events'      => [],
                'reference'   => 'article-3'
            ],
        ];

        foreach ($data as $item) {
            $article = new Article();
            $article->setName($item['name']);
            $article->setSlug($item['slug']);
            $article->setBody($item['body']);
            $article->setCountEvents($item['countEvents']);
            $article->setPublish($item['publish']);
            $article->setPublishAt(new \DateTime($item['publishAt']));

            foreach ($item['events'] as $event) {
                /** @var Event $event */
                $event = $this->getReference($event);
                $article->addEvent($event);
            }

            $manager->persist($article);

            $this->addReference($item['reference'], $article);
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