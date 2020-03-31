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
use eTraxis\TransactionalTestCase;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Exception\InvalidStateException;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * @coversDefaultClass \eTraxis\Security\Authenticator\GoogleAuthenticator
 */
class GoogleAuthenticatorTest extends TransactionalTestCase
{
    /**
     * @var HttpUtils
     */
    private $utils;

    /**
     * @var ClientRegistry
     */
    private $clientRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = $this->client->getContainer()->get('router');

        $this->utils          = new HttpUtils($router);
        $this->clientRegistry = $this->client->getContainer()->get('knpu.oauth2.registry');
    }

    /**
     * @covers ::supports
     */
    public function testSupportsSuccess()
    {
        $authenticator = new GoogleAuthenticator($this->utils, $this->commandBus, $this->clientRegistry);

        $request = new Request([
            'code'  => 'valid-code',
            'state' => 'secret',
        ], [], [
            '_route' => 'oauth_google',
        ]);

        self::assertTrue($authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsMissing()
    {
        $authenticator = new GoogleAuthenticator($this->utils, $this->commandBus, $this->clientRegistry);

        $request = new Request([], [], [
            '_route' => 'oauth_google',
        ]);

        self::assertFalse($authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsWrongRoute()
    {
        $authenticator = new GoogleAuthenticator($this->utils, $this->commandBus, $this->clientRegistry);

        $request = new Request([
            'code'  => 'valid-code',
            'state' => 'secret',
        ], [], [
            '_route' => 'login',
        ]);

        self::assertFalse($authenticator->supports($request));
    }

    /**
     * @covers ::getCredentials
     */
    public function testGetCredentials()
    {
        $token = $this->createMock(AccessToken::class);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->method('getAccessToken')
            ->willReturn($token);

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->method('getClient')
            ->willReturnMap([
                ['google', $client],
            ]);

        /** @var ClientRegistry $clientRegistry */
        $authenticator = new GoogleAuthenticator($this->utils, $this->commandBus, $clientRegistry);

        $result = $authenticator->getCredentials(new Request());

        self::assertInstanceOf(AccessToken::class, $result);
    }

    /**
     * @covers ::getCredentials
     */
    public function testGetCredentialsException()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->method('getAccessToken')
            ->willThrowException(new InvalidStateException());

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->method('getClient')
            ->willReturnMap([
                ['google', $client],
            ]);

        /** @var ClientRegistry $clientRegistry */
        $authenticator = new GoogleAuthenticator($this->utils, $this->commandBus, $clientRegistry);

        $result = $authenticator->getCredentials(new Request());

        self::assertNotInstanceOf(AccessToken::class, $result);
    }

    /**
     * @covers ::getUser
     */
    public function testGetUser()
    {
        $owner = new GoogleUser([
            'sub'   => '423729',
            'email' => 'anna@example.com',
            'name'  => 'Anna Rodygina',
        ]);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->method('fetchUserFromToken')
            ->willReturn($owner);

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->method('getClient')
            ->willReturnMap([
                ['google', $client],
            ]);

        /** @var ClientRegistry $clientRegistry */
        $authenticator = new GoogleAuthenticator($this->utils, $this->commandBus, $clientRegistry);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNull($entity);

        $token        = $this->createMock(AccessToken::class);
        $userProvider = $this->createMock(UserProviderInterface::class);

        $user = $authenticator->getUser($token, $userProvider);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNotNull($entity);

        self::assertSame($entity, $user);
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserException()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->method('fetchUserFromToken')
            ->willThrowException(new MissingAuthorizationCodeException());

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->method('getClient')
            ->willReturnMap([
                ['google', $client],
            ]);

        /** @var ClientRegistry $clientRegistry */
        $authenticator = new GoogleAuthenticator($this->utils, $this->commandBus, $clientRegistry);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNull($entity);

        $token        = $this->createMock(AccessToken::class);
        $userProvider = $this->createMock(UserProviderInterface::class);

        $authenticator->getUser($token, $userProvider);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNull($entity);
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentials()
    {
        $authenticator = new GoogleAuthenticator($this->utils, $this->commandBus, $this->clientRegistry);

        self::assertTrue($authenticator->checkCredentials([], new User()));
    }
}