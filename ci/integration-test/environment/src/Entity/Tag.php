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
 * Tag.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[ORM\Table(name: 'tags')]
#[ORM\Entity]
class Tag
{
    #[ORM\Column(name: 'id', type: 'integer', options: ['comment' => 'ID'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255, options: ['comment' => 'Название'])]
    private string $name;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'tags')]
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
