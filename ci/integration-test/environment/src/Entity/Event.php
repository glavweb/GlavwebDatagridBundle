<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Event.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[ORM\Table(name: 'events')]
#[ORM\Entity]
class Event
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string')]
    private string $name;

    #[ORM\ManyToOne(targetEntity: EventGroup::class, inversedBy: 'events')]
    private EventGroup $eventGroup;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'events')]
    #[ORM\OrderBy(['id' => 'asc'])]
    private Collection $tags;

    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'events')]
    #[ORM\OrderBy(['id' => 'asc'])]
    private Collection $articles;

    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'event')]
    #[ORM\OrderBy(['id' => 'asc'])]
    private Collection $sessions;

    #[ORM\OneToOne(targetEntity: EventDetail::class, inversedBy: 'event')]
    private EventDetail $eventDetail;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @return Event
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set eventGroup.
     *
     * @return Event
     */
    public function setEventGroup(?EventGroup $eventGroup = null): static
    {
        $this->eventGroup = $eventGroup;

        return $this;
    }

    /**
     * Get eventGroup.
     */
    public function getEventGroup(): EventGroup
    {
        return $this->eventGroup;
    }

    /**
     * Add tag.
     *
     * @return Event
     */
    public function addTag(Tag $tag): static
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     */
    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags.
     */
    public function getTags(): ArrayCollection
    {
        return $this->tags;
    }

    /**
     * Add article.
     *
     * @return Event
     */
    public function addArticle(Article $article): static
    {
        $this->articles[] = $article;

        return $this;
    }

    /**
     * Remove article.
     */
    public function removeArticle(Article $article): void
    {
        $this->articles->removeElement($article);
    }

    /**
     * Get articles.
     */
    public function getArticles(): ArrayCollection
    {
        return $this->articles;
    }

    /**
     * Add session.
     *
     * @return Event
     */
    public function addSession(Session $session): static
    {
        $this->sessions[] = $session;

        return $this;
    }

    /**
     * Remove session.
     */
    public function removeSession(Session $session): void
    {
        $this->sessions->removeElement($session);
    }

    /**
     * Get sessions.
     */
    public function getSessions(): ArrayCollection
    {
        return $this->sessions;
    }

    /**
     * Set eventDetail.
     *
     * @return Event
     */
    public function setEventDetail(?EventDetail $eventDetail = null): static
    {
        $this->eventDetail = $eventDetail;

        return $this;
    }

    /**
     * Get eventDetail.
     */
    public function getEventDetail(): EventDetail
    {
        return $this->eventDetail;
    }
}
