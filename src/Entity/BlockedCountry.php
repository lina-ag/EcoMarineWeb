<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\BlockedCountryRepository;

#[ORM\Entity(repositoryClass: BlockedCountryRepository::class)]
#[ORM\Table(name: 'blocked_country')]
class BlockedCountry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 2, unique: true)]
    private string $countryCode; // ex: TN, FR, US

    #[ORM\Column(length: 100)]
    private string $countryName;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $blockedAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    public function __construct(string $countryCode, string $countryName, ?string $reason = null)
    {
        $this->countryCode = strtoupper($countryCode);
        $this->countryName = $countryName;
        $this->blockedAt   = new \DateTime();
        $this->reason      = $reason;
    }

    public function getId(): ?int { return $this->id; }
    public function getCountryCode(): string { return $this->countryCode; }
    public function getCountryName(): string { return $this->countryName; }
    public function getBlockedAt(): \DateTimeInterface { return $this->blockedAt; }
    public function getReason(): ?string { return $this->reason; }
}