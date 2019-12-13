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

/**
 * @coversDefaultClass \eTraxis\Controller\OAuth2Controller
 */
class OAuth2ControllerTest extends WebTestCase
{
    /**
     * @covers ::google
     */
    public function testGoogle()
    {
        $uri = '/oauth/google';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirection());

        $location = $this->client->getResponse()->headers->get('Location');
        self::assertRegExp('/^(https:\/\/accounts.google.com\/)(.)+$/i', $location);

        $this->loginAs('artem@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    /**
     * @covers ::github
     */
    public function testGithub()
    {
        $uri = '/oauth/github';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirection());

        $location = $this->client->getResponse()->headers->get('Location');
        self::assertRegExp('/^(https:\/\/github.com\/)(.)+$/i', $location);

        $this->loginAs('artem@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }
}
