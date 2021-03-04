<?php

namespace App\Entity;

use App\Repository\RecipientRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecipientRepository::class)
 */
class Recipient
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
     * @ORM\Column(type="string", length=255)
     */
    private $family;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $address;

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

    public function getFamily(): ?string
    {
        return $this->family;
    }

    public function setFamily(string $family): self
    {
        $this->family = $family;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }
}
