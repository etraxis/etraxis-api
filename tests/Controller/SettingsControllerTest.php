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
 * @coversDefaultClass \eTraxis\Controller\SettingsController
 */
class SettingsControllerTest extends WebTestCase
{
    /**
     * @covers ::index
     */
    public function testIndex()
    {
        $uri = '/settings';

        $this->client->request(Request::METHOD_GET, $uri);
        static::assertTrue($this->client->getResponse()->isRedirect('http://localhost/login'));

        $this->loginAs('artem@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        static::assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers ::cities
     */
    public function testCitiesSuccess()
    {
        $this->loginAs('artem@example.com');

        $expected = [
            'Pacific/Auckland' => 'Auckland',
            'Pacific/Chatham'  => 'Chatham',
        ];

        $uri = '/settings/cities/NZ';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        static::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    /**
     * @covers ::cities
     */
    public function testCities401()
    {
        $uri = '/settings/cities/NZ';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
