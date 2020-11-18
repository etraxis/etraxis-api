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

namespace eTraxis;

/**
 * Extended web test case with every test wrapped into database transaction.
 *
 * @coversNothing
 */
class TransactionalTestCase extends WebTestCase
{
    /**
     * Maximum value of signed 32-bits integer which can be used as an ID of non-existing entity.
     * The "PHP_INT_MAX" cannot be used as it causes "value 9223372036854775807 is out of range for type integer"
     * SQL driver error for PostgreSQL on 64-bits platforms.
     */
    protected const UNKNOWN_ENTITY_ID = 0x7FFFFFFF;

    /**
     * Begins new transaction.
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();
        $manager->beginTransaction();
    }

    /**
     * Rolls back current transaction.
     */
    protected function tearDown(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();
        $manager->rollback();

        parent::tearDown();
    }
}
