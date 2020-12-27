<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration as DoctrineMigration;
use eTraxis\Application\Dictionary\DatabasePlatform;

/**
 * Base migration.
 */
abstract class AbstractMigration extends DoctrineMigration
{
    /**
     * Returns version string for the migration.
     *
     * @return string
     */
    abstract public function getVersion(): string;

    /**
     * Checks whether the current database platform is MySQL.
     *
     * @return bool
     */
    public function isMysql(): bool
    {
        return DatabasePlatform::MYSQL === $this->platform->getName();
    }

    /**
     * Checks whether the current database platform is PostgreSQL.
     *
     * @return bool
     */
    public function isPostgresql(): bool
    {
        return DatabasePlatform::POSTGRESQL === $this->platform->getName();
    }

    /**
     * {@inheritDoc}
     */
    final public function getDescription(): string
    {
        return $this->getVersion();
    }

    /**
     * {@inheritDoc}
     */
    public function preUp(Schema $schema): void
    {
        $platform = $this->platform->getName();

        $this->abortIf(
            !DatabasePlatform::has($platform),
            'Unsupported database platform - ' . $platform
        );
    }

    /**
     * {@inheritDoc}
     */
    public function preDown(Schema $schema): void
    {
        $platform = $this->platform->getName();

        $this->abortIf(
            !DatabasePlatform::has($platform),
            'Unsupported database platform - ' . $platform
        );
    }
}
