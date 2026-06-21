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

use Doctrine\ORM\Mapping as ORM;

/**
 * Session.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[ORM\Table(name: 'event_sessions')]
#[ORM\Entity]
class Session
{
    #[ORM\Column(name: 'id', type: 'integer', options: ['comment' => 'ID'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string')]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'sessions')]
    private Event $event;

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
     * Set event.
     */
    public function setEvent(?Event $event = null): static
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }
}
