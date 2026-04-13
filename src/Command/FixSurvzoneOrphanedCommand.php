<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-survzone-orphaned',
    description: 'Fix orphaned survzone records with non-existent zone references',
)]
class FixSurvzoneOrphanedCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get all Zone IDs that exist
        $validZoneIds = $this->entityManager->getConnection()->executeQuery(
            'SELECT idZone FROM zonep'
        )->fetchFirstColumn();

        // Find orphaned survzone records
        $orphanedCount = $this->entityManager->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM survzone WHERE idZone IS NOT NULL AND idZone NOT IN (?)',
            [array_values($validZoneIds)],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
        )->fetchOne();

        if ($orphanedCount == 0) {
            $io->success('No orphaned records found.');
            return Command::SUCCESS;
        }

        $io->warning("Found {$orphanedCount} orphaned survzone records.");

        // Set orphaned records to NULL
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE survzone SET idZone = NULL WHERE idZone IS NOT NULL AND idZone NOT IN (?)',
            [array_values($validZoneIds)],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
        );

        $io->success("Fixed {$orphanedCount} orphaned survzone records by setting idZone to NULL.");

        return Command::SUCCESS;
    }
}
