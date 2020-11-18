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

namespace eTraxis\Controller;

use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \eTraxis\Controller\LoginController
 */
class LoginControllerTest extends WebTestCase
{
    /**
     * @covers ::index
     */
    public function testIndex()
    {
        $uri = '/login';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());

        $this->loginAs('artem@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }
}
