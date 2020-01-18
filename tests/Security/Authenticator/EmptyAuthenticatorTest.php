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

namespace eTraxis\Security\Authenticator;

use eTraxis\Entity\User;
use eTraxis\MessageBus\Contracts\EventBusInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @coversDefaultClass \eTraxis\Security\Authenticator\EmptyAuthenticator
 */
class EmptyAuthenticatorTest extends TestCase
{
    /**
     * @var EmptyAuthenticator
     */
    private $authenticator;

    protected function setUp()
    {
        parent::setUp();

        /** @var EventBusInterface $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);

        $this->authenticator = new EmptyAuthenticator($eventBus);
    }

    /**
     * @covers ::start
     */
    public function testStart()
    {
        $request  = $this->createMock(Request::class);
        $response = $this->authenticator->start($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers ::supports
     */
    public function testSupports()
    {
        $request = $this->createMock(Request::class);

        self::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @covers ::getCredentials
     */
    public function testGetCredentials()
    {
        $request = $this->createMock(Request::class);

        self::assertNull($this->authenticator->getCredentials($request));
    }

    /**
     * @covers ::getUser
     */
    public function testGetUser()
    {
        $provider = $this->createMock(UserProviderInterface::class);

        self::assertNull($this->authenticator->getUser(null, $provider));
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentials()
    {
        $user = $this->createMock(UserInterface::class);

        self::assertFalse($this->authenticator->checkCredentials(null, $user));
    }

    /**
     * @covers ::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccess()
    {
        $request = $this->createMock(Request::class);
        $token   = $this->authenticator->createAuthenticatedToken(new User(), 'main');

        self::assertNull($this->authenticator->onAuthenticationSuccess($request, $token, 'main'));
    }

    /**
     * @covers ::onAuthenticationFailure
     * @covers ::start
     */
    public function testOnAuthenticationFailure()
    {
        $request   = $this->createMock(Request::class);
        $exception = $this->createMock(AuthenticationException::class);

        self::assertNull($this->authenticator->onAuthenticationFailure($request, $exception));
    }

    /**
     * @covers ::supportsRememberMe
     */
    public function testSupportsRememberMe()
    {
        self::assertFalse($this->authenticator->supportsRememberMe());
    }
}
