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

namespace eTraxis\Controller;

use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \eTraxis\Controller\ForgotPasswordController
 */
class ForgotPasswordControllerTest extends WebTestCase
{
    /**
     * @covers ::index
     */
    public function testIndex()
    {
        $uri = '/forgot';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());

        $this->loginAs('admin@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    /**
     * @covers ::forgotPassword
     */
    public function testForgotPassword()
    {
        $uri = '/forgot';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);
        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->loginAs('admin@example.com');

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
