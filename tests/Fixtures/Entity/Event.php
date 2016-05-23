<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Event
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 *
 * @ORM\Table(name="events")
 * @ORM\Entity
 */
class Event
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var EventGroup
     *
     * @ORM\ManyToOne(targetEntity="EventGroup", inversedBy="events")
     */
    private $eventGroup;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="events")
     */
    private $tags;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Article", mappedBy="events")
     */
    private $articles;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Session", mappedBy="event")
     */
    private $sessions;

    /**
     * @var EventDetail
     *
     * @ORM\OneToOne(targetEntity="EventDetail", inversedBy="event")
     */
    private $eventDetail;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tags     = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Event
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set eventGroup
     *
     * @param EventGroup $eventGroup
     *
     * @return Event
     */
    public function setEventGroup(EventGroup $eventGroup = null)
    {
        $this->eventGroup = $eventGroup;

        return $this;
    }

    /**
     * Get eventGroup
     *
     * @return EventGroup
     */
    public function getEventGroup()
    {
        return $this->eventGroup;
    }

    /**
     * Add tag
     *
     * @param Tag $tag
     *
     * @return Event
     */
    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add article
     *
     * @param Article $article
     *
     * @return Event
     */
    public function addArticle(Article $article)
    {
        $this->articles[] = $article;

        return $this;
    }

    /**
     * Remove article
     *
     * @param Article $article
     */
    public function removeArticle(Article $article)
    {
        $this->articles->removeElement($article);
    }

    /**
     * Get articles
     *
     * @return ArrayCollection
     */
    public function getArticles()
    {
        return $this->articles;
    }

    /**
     * Add session
     *
     * @param Session $session
     *
     * @return Event
     */
    public function addSession(Session $session)
    {
        $this->sessions[] = $session;

        return $this;
    }

    /**
     * Remove session
     *
     * @param Session $session
     */
    public function removeSession(Session $session)
    {
        $this->sessions->removeElement($session);
    }

    /**
     * Get sessions
     *
     * @return ArrayCollection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * Set eventDetail
     *
     * @param EventDetail $eventDetail
     *
     * @return Event
     */
    public function setEventDetail(EventDetail $eventDetail = null)
    {
        $this->eventDetail = $eventDetail;

        return $this;
    }

    /**
     * Get eventDetail
     *
     * @return EventDetail
     */
    public function getEventDetail()
    {
        return $this->eventDetail;
    }
}
