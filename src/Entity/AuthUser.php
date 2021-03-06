<?php
declare(strict_types=1);

namespace TechnoBureau\mezzioPDOAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mezzio\Authentication\UserInterface as MezzioUserInterface;

#[ORM\Table(name: "auth_user")]
#[ORM\Entity(repositoryClass: \TechnoBureau\mezzioPDOAuth\Repository\AuthUserRepository::class)]
class AuthUser implements MezzioUserInterface
{
    #[ORM\Column(name: "id", type: "integer", options: ["unsigned" => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private string $first_name;

    #[ORM\Column(name: "role", type: "string", length: 255, nullable: true)]
    private string $role;

    #[ORM\Column(name: "active", type: "boolean", options: ["default" => 0])]
    private bool $isActive = false;

    private array $roles = [];

    private array $details = [];

    public function __construct()
    {
        $this->id = 0;
        $this->email = '';
        $this->first_name = '';
        $this->password = '';
        $this->role = '';
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setIsActive(bool $isActive = true): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getIdentifier(): string
    {
        return $this->getEmail();
    }

    public function getIdentity(): string
    {
        return $this->getEmail();
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getDetail(string $name, $default = null)
    {
        return $this->details[$name] ?? $default;
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }

    /** @psalm-suppress MixedReturnTypeCoercion */
    public function getDetails(): array
    {
        /** @psalm-suppress MixedReturnTypeCoercion */
        return $this->details;
    }
}