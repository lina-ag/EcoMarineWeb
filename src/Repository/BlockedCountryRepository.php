<?php
namespace App\Repository;

use App\Entity\BlockedCountry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BlockedCountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlockedCountry::class);
    }

    public function isCountryBlocked(string $countryCode): bool
    {
        return $this->findOneBy(['countryCode' => strtoupper($countryCode)]) !== null;
    }
}