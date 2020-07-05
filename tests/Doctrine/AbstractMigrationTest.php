<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \eTraxis\Doctrine\AbstractMigration
 */
class AbstractMigrationTest extends TestCase
{
    /**
     * @covers ::getDescription
     * @covers ::getVersion
     */
    public function testVersion()
    {
        $expected = '4.0.0';

        $migration = $this->getMigration(MySqlPlatform::class);

        self::assertSame($expected, $migration->getVersion());
        self::assertSame($expected, $migration->getDescription());
    }

    /**
     * @covers ::isMysql
     * @covers ::isPostgresql
     */
    public function testIsMysql()
    {
        $migration = $this->getMigration(MySqlPlatform::class);

        self::assertTrue($migration->isMysql());
        self::assertFalse($migration->isPostgresql());
    }

    /**
     * @covers ::isMysql
     * @covers ::isPostgresql
     */
    public function testIsPostgresql()
    {
        $migration = $this->getMigration(PostgreSqlPlatform::class);

        self::assertTrue($migration->isPostgresql());
        self::assertFalse($migration->isMysql());
    }

    /**
     * @covers ::preUp
     */
    public function testUpSuccess()
    {
        $schema    = new Schema();
        $migration = $this->getMigration(MySqlPlatform::class);

        $this->expectOutputString('migrating up');
        $migration->preUp($schema);
        $migration->up($schema);
    }

    /**
     * @covers ::preDown
     */
    public function testDownSuccess()
    {
        $schema    = new Schema();
        $migration = $this->getMigration(MySqlPlatform::class);

        $this->expectOutputString('migrating down');
        $migration->preDown($schema);
        $migration->down($schema);
    }

    /**
     * @covers ::preUp
     */
    public function testUpFailure()
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Unsupported database platform - sqlite');

        $schema    = new Schema();
        $migration = $this->getMigration(SqlitePlatform::class);

        $migration->preUp($schema);
        $migration->up($schema);
    }

    /**
     * @covers ::preDown
     */
    public function testDownFailure()
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Unsupported database platform - sqlite');

        $schema    = new Schema();
        $migration = $this->getMigration(SqlitePlatform::class);

        $migration->preDown($schema);
        $migration->down($schema);
    }

    private function getMigration(string $class)
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getSchemaManager')
            ->willReturn(null);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new $class());

        return new class($connection, new NullLogger()) extends AbstractMigration {
            public function getVersion(): string
            {
                return '4.0.0';
            }

            public function up(Schema $schema): void
            {
                echo 'migrating up';
            }

            public function down(Schema $schema): void
            {
                echo 'migrating down';
            }
        };
    }
}
