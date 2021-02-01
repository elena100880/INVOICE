<?php

namespace App\Entity;

use App\Repository\InvoicePositionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvoicePositionRepository::class)
 */
class InvoicePosition
{
    
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Invoice::class, inversedBy="invoicePosition")
     * @ORM\JoinColumn(nullable=false)
     */
    private $invoice;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Position::class, inversedBy="positionInvoice")
     * @ORM\JoinColumn(nullable=false)
     */
    private $position;

    
    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): self
    {
        $this->position = $position;

        return $this;
    }
}
