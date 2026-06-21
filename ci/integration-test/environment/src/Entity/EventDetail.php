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
 * EventDetail.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[ORM\Table(name: 'event_details')]
#[ORM\Entity]
class EventDetail
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'body', type: 'text')]
    private string $body;

    #[ORM\OneToOne(targetEntity: Event::class, mappedBy: 'eventDetail')]
    private Event $event;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
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
