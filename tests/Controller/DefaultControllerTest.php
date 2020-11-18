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
 * @coversDefaultClass \eTraxis\Controller\DefaultController
 */
class DefaultControllerTest extends WebTestCase
{
    /**
     * @covers ::homepage
     */
    public function testHomepage()
    {
        $uri = '/';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('http://localhost/login'));

        $this->loginAs('artem@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers ::admin
     */
    public function testAdmin()
    {
        $uri = '/admin/';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('http://localhost/login'));

        $this->loginAs('artem@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isForbidden());

        $this->loginAs('admin@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/admin/users'));
    }
}
