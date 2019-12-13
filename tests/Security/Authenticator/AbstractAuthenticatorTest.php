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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

/**
 * @coversDefaultClass \eTraxis\Security\Authenticator\AbstractAuthenticator
 */
class AbstractAuthenticatorTest extends TestCase
{
    /**
     * @var DatabaseAuthenticator
     */
    private $authenticator;

    /**
     * @var User
     */
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = new User();

        $this->user->password = 'secret';

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnMap([
                ['homepage', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/'],
                ['login', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/login'],
            ]);

        $session = $this->createMock(SessionInterface::class);
        $session
            ->method('get')
            ->willReturnMap([
                [Security::AUTHENTICATION_ERROR, null, null],
                ['_security.main.target_path', null, 'http://localhost/profile'],
            ]);

        $encoder = $this->createMock(UserPasswordEncoderInterface::class);
        $encoder
            ->method('isPasswordValid')
            ->willReturnMap([
                [$this->user, 'secret', true],
                [$this->user, 'wrong', false],
            ]);

        $eventBus = $this->createMock(EventBusInterface::class);

        /** @var RouterInterface $router */
        /** @var SessionInterface $session */
        /** @var UserPasswordEncoderInterface $encoder */
        /** @var EventBusInterface $eventBus */
        $this->authenticator = new DatabaseAuthenticator($router, $session, $encoder, $eventBus);
    }

    /**
     * @covers ::start
     */
    public function testStart()
    {
        $request = new Request();

        $response = $this->authenticator->start($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('location'));
    }

    /**
     * @covers ::start
     */
    public function testStartAjax()
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->authenticator->start($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Authentication required.', json_decode($response->getContent(), true));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsSuccess()
    {
        $request = new Request([], [
            '_username' => 'admin',
            '_password' => 'secret',
        ]);

        self::assertTrue($this->authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsMissing()
    {
        $request = new Request();

        self::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @covers ::getCredentials
     */
    public function testGetCredentials()
    {
        $expected = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        $request = new Request([], [
            '_username' => 'admin',
            '_password' => 'secret',
        ]);

        self::assertSame($expected, $this->authenticator->getCredentials($request));
    }

    /**
     * @covers ::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccess()
    {
        $token = $this->authenticator->createAuthenticatedToken(new User(), 'main');

        $request  = new Request();
        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('http://localhost/profile', $response->headers->get('Location'));
    }

    /**
     * @covers ::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccessAjax()
    {
        $token = $this->authenticator->createAuthenticatedToken(new User(), 'main');

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([], json_decode($response->getContent(), true));
    }

    /**
     * @covers ::onAuthenticationFailure
     * @covers ::start
     */
    public function testOnAuthenticationFailure()
    {
        $request   = new Request();
        $exception = new AuthenticationException('Bad credentials.');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('location'));
    }

    /**
     * @covers ::onAuthenticationFailure
     * @covers ::start
     */
    public function testOnAuthenticationFailureAjax()
    {
        $exception = new AuthenticationException('Bad credentials.');

        $session = $this->createMock(SessionInterface::class);
        $session
            ->method('get')
            ->willReturnMap([
                [Security::AUTHENTICATION_ERROR, null, $exception],
                ['_security.main.target_path', null, 'http://localhost/profile'],
            ]);

        $reflection = new \ReflectionProperty(DatabaseAuthenticator::class, 'session');
        $reflection->setAccessible(true);
        $reflection->setValue($this->authenticator, $session);

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Bad credentials.', json_decode($response->getContent(), true));
    }

    /**
     * @covers ::supportsRememberMe
     */
    public function testSupportsRememberMe()
    {
        self::assertTrue($this->authenticator->supportsRememberMe());
    }
}
