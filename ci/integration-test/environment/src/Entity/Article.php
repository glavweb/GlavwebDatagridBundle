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
 * Article.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[ORM\Table(name: 'articles')]
#[ORM\Entity]
class Article
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string')]
    private string $name;

    #[ORM\Column(name: 'slug', type: 'string')]
    private string $slug;

    #[ORM\Column(name: 'body', type: 'text')]
    private string $body;

    #[ORM\Column(name: 'count_events', type: 'integer')]
    private int $countEvents;

    #[ORM\Column(name: 'is_publish', type: 'boolean')]
    private bool $publish;

    #[ORM\Column(name: 'publish_at', type: 'datetime')]
    private \DateTime $publishAt;

    #[ORM\ManyToMany(targetEntity: Event::class, inversedBy: 'articles')]
    private Collection $events;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
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
     * Set slug.
     */
    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Set body.
     */
    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    public function setCountEvents(int $countEvents): void
    {
        $this->countEvents = $countEvents;
    }

    public function getCountEvents(): int
    {
        return $this->countEvents;
    }

    public function setPublish(bool $publish): static
    {
        $this->publish = $publish;

        return $this;
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }

    public function setPublishAt(\DateTime $publishAt): static
    {
        $this->publishAt = $publishAt;

        return $this;
    }

    public function getPublishAt(): \DateTime
    {
        return $this->publishAt;
    }

    /**
     * Add event.
     */
    public function addEvent(Event $event): static
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event.
     */
    public function removeEvent(Event $event): void
    {
        $this->events->removeElement($event);
    }

    /**
     * Get events.
     */
    public function getEvents(): ArrayCollection
    {
        return $this->events;
    }
}
