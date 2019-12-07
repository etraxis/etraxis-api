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

namespace eTraxis;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;

/**
 * Extended web test case with an autoboot kernel and few helpers.
 *
 * @coversNothing
 */
class WebTestCase extends SymfonyWebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    protected $client;

    /**
     * @var \Symfony\Bridge\Doctrine\ManagerRegistry
     */
    protected $doctrine;

    /**
     * Boots the kernel and retrieve most often used services.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->doctrine = self::$container->get('doctrine');
    }
}
