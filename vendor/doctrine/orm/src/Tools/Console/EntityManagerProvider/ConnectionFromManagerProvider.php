<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;

final class ConnectionFromManagerProvider implements ConnectionProvider
{
    public function __construct(private readonly EntityManagerProvider $entityManagerProvider)
    {
    }

    public function getDefaultConnection(): Connection
    {
        return $this->entityManagerProvider->getDefaultManager()->getConnection();
    }

    public function getConnection(string $name): Connection
    {
        return $this->entityManagerProvider->getManager($name)->getConnection();
    }
}
