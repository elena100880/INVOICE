<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvoiceRepository::class)
 */
class Invoice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Supplier::class, inversedBy="invoices")
     * @ORM\JoinColumn(nullable=false)
     */
    private $supplier;

    /**
     * @ORM\ManyToOne(targetEntity=Recipient::class, inversedBy="invoices")
     * @ORM\JoinColumn(nullable=false)
     */
    private $recipient;

    /**
     * @ORM\OneToMany(targetEntity=InvoicePosition::class, mappedBy="invoice", orphanRemoval=true)
     */
    private $invoicePosition;

    public function __construct()
    {
        $this->invoicePosition = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    public function setRecipient(?Recipient $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return Collection|InvoicePosition[]
     */
    public function getInvoicePosition(): Collection
    {
        return $this->invoicePosition;
    }

    public function addInvoicePosition(InvoicePosition $invoicePosition): self
    {
        if (!$this->invoicePosition->contains($invoicePosition)) {
            $this->invoicePosition[] = $invoicePosition;
            $invoicePosition->setInvoice($this);
        }

        return $this;
    }

    public function removeInvoicePosition(InvoicePosition $invoicePosition): self
    {
        if ($this->invoicePosition->removeElement($invoicePosition)) {
            // set the owning side to null (unless already changed)
            if ($invoicePosition->getInvoice() === $this) {
                $invoicePosition->setInvoice(null);
            }
        }

        return $this;
    }
}
