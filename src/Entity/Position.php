<?php

namespace App\Entity;

use App\Repository\PositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PositionRepository::class)
 */
class Position
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="float")
     */
    private $value;

    /**
     * @ORM\OneToMany(targetEntity=InvoicePosition::class, mappedBy="position", orphanRemoval=true)
     */
    private $positionInvoice;

    public function __construct()
    {
        $this->positionInvoice = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Collection|InvoicePosition[]
     */
    public function getPositionInvoice(): Collection
    {
        return $this->positionInvoice;
    }

    public function addPositionInvoice(InvoicePosition $positionInvoice): self
    {
        if (!$this->positionInvoice->contains($positionInvoice)) {
            $this->positionInvoice[] = $positionInvoice;
            $positionInvoice->setPosition($this);
        }

        return $this;
    }

    public function removePositionInvoice(InvoicePosition $positionInvoice): self
    {
        if ($this->positionInvoice->removeElement($positionInvoice)) {
            // set the owning side to null (unless already changed)
            if ($positionInvoice->getPosition() === $this) {
                $positionInvoice->setPosition(null);
            }
        }

        return $this;
    }
}
