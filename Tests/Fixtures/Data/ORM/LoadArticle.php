<?php

namespace Glavweb\DatagridBundle\Tests\Fixtures\Data;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Glavweb\DatagridBundle\Tests\Fixtures\Entity\Article;

class LoadArticle implements OrderedFixtureInterface, FixtureInterface
{
    /**
     * Set loading order.
     *
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }

    public function load(ObjectManager $manager)
    {
        $article = new Article();
        $article->setUsername('admin');
        $article->setPassword('test');

        $manager->persist($article);
        $manager->flush();
    }
}