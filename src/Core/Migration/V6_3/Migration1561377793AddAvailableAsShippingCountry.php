<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @deprecated tag:v6.5.0 - reason:becomes-internal - Migrations will be internal in v6.5.0
 */
class Migration1561377793AddAvailableAsShippingCountry extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1561377793;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `country` ADD `shipping_available` TinyInt(1) NOT NULL DEFAULT 1;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
