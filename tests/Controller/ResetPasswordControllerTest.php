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
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \eTraxis\Controller\ResetPasswordController
 */
class ResetPasswordControllerTest extends WebTestCase
{
    /**
     * @covers ::index
     */
    public function testIndex()
    {
        $uri = '/reset/9d73b1c922794370903898dae9ee8ada';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());

        $this->loginAs('admin@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    /**
     * @covers ::resetPassword
     */
    public function testResetPassword()
    {
        $uri = '/reset/9d73b1c922794370903898dae9ee8ada';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, ['password' => 'secret']);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);
        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->loginAs('admin@example.com');

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
