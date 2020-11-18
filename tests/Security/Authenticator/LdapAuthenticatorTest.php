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

namespace eTraxis\Security\Authenticator;

use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Entity\User;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * @coversDefaultClass \eTraxis\Security\Authenticator\LdapAuthenticator
 */
class LdapAuthenticatorTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private UserProviderInterface   $provider;
    private UserRepositoryInterface $repository;
    private HttpUtils               $utils;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider   = self::$container->get('etraxis.user.provider');
        $this->repository = $this->doctrine->getRepository(User::class);

        /** @var RouterInterface $router */
        $router      = $this->createMock(RouterInterface::class);
        $this->utils = new HttpUtils($router);
    }

    /**
     * @covers ::__construct
     * @covers ::supports
     */
    public function testSupportsWithLdap()
    {
        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'ldap://localhost',
            'dc=example,dc=com'
        );

        $request = new Request([], [
            '_username' => 'admin',
            '_password' => 'secret',
        ]);

        self::assertTrue($authenticator->supports($request));

        $request = new Request();

        self::assertFalse($authenticator->supports($request));
    }

    /**
     * @covers ::__construct
     * @covers ::supports
     */
    public function testSupportsNoLdap()
    {
        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'null://localhost',
            'dc=example,dc=com'
        );

        $request = new Request([], [
            '_username' => 'admin',
            '_password' => 'secret',
        ]);

        self::assertFalse($authenticator->supports($request));
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserNew()
    {
        $entry = $this->createMock(Entry::class);
        $entry
            ->method('getAttributes')
            ->willReturn([
                'uid'  => ['newton'],
                'mail' => ['newton@example.com'],
                'cn'   => ['Isaac Newton'],
            ]);

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn([$entry]);

        $ldap = $this->createMock(LdapInterface::class);
        $ldap
            ->method('escape')
            ->willReturn('newton@example.com');
        $ldap
            ->method('query')
            ->willReturn($query);

        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'ldap://localhost',
            'dc=example,dc=com'
        );

        $this->setProperty($authenticator, 'ldap', $ldap);

        $count = count($this->repository->findAll());

        $credentials = [
            'username' => 'newton@example.com',
            'password' => 'secret',
        ];

        /** @var User $user */
        $user = $authenticator->getUser($credentials, $this->provider);

        self::assertInstanceOf(User::class, $user);
        self::assertSame(AccountProvider::LDAP, $user->account->provider);
        self::assertSame('newton', $user->account->uid);
        self::assertSame('newton@example.com', $user->email);
        self::assertSame('Isaac Newton', $user->fullname);
        self::assertCount($count + 1, $this->repository->findAll());
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserExisting()
    {
        $entry = $this->createMock(Entry::class);
        $entry
            ->method('getAttributes')
            ->willReturn([
                'uid'  => ['ldap-9fc3012e'],
                'mail' => ['einstein@example.com'],
                'cn'   => ['A. Einstein'],
            ]);

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn([$entry]);

        $ldap = $this->createMock(LdapInterface::class);
        $ldap
            ->method('escape')
            ->willReturn('einstein@example.com');
        $ldap
            ->method('query')
            ->willReturn($query);

        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'ldap://localhost',
            'dc=example,dc=com'
        );

        $this->setProperty($authenticator, 'ldap', $ldap);

        $count = count($this->repository->findAll());

        $credentials = [
            'username' => 'einstein@example.com',
            'password' => 'secret',
        ];

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('einstein@ldap.forumsys.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame(AccountProvider::LDAP, $user->account->provider);
        self::assertSame('einstein@ldap.forumsys.com', $user->email);
        self::assertSame('Albert Einstein', $user->fullname);

        /** @var User $user */
        $user = $authenticator->getUser($credentials, $this->provider);

        self::assertInstanceOf(User::class, $user);
        self::assertSame(AccountProvider::LDAP, $user->account->provider);
        self::assertSame('ldap-9fc3012e', $user->account->uid);
        self::assertSame('einstein@example.com', $user->email);
        self::assertSame('A. Einstein', $user->fullname);
        self::assertCount($count, $this->repository->findAll());
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserIncomplete()
    {
        $this->expectException(UsernameNotFoundException::class);

        $entry = $this->createMock(Entry::class);
        $entry
            ->method('getAttributes')
            ->willReturn([
                'mail' => ['newton@example.com'],
                'cn'   => ['Isaac Newton'],
            ]);

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn([$entry]);

        $ldap = $this->createMock(LdapInterface::class);
        $ldap
            ->method('escape')
            ->willReturn('newton@example.com');
        $ldap
            ->method('query')
            ->willReturn($query);

        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'ldap://localhost',
            'dc=example,dc=com'
        );

        $this->setProperty($authenticator, 'ldap', $ldap);

        $count = count($this->repository->findAll());

        $credentials = [
            'username' => 'newton@example.com',
            'password' => 'secret',
        ];

        /** @var User $user */
        $user = $authenticator->getUser($credentials, $this->provider);

        self::assertNull($user);
        self::assertCount($count, $this->repository->findAll());
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserUnknown()
    {
        $this->expectException(UsernameNotFoundException::class);

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn([]);

        $ldap = $this->createMock(LdapInterface::class);
        $ldap
            ->method('escape')
            ->willReturn('newton@example.com');
        $ldap
            ->method('query')
            ->willReturn($query);

        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'ldap://localhost',
            'dc=example,dc=com'
        );

        $this->setProperty($authenticator, 'ldap', $ldap);

        $count = count($this->repository->findAll());

        $credentials = [
            'username' => 'newton@example.com',
            'password' => 'secret',
        ];

        /** @var User $user */
        $user = $authenticator->getUser($credentials, $this->provider);

        self::assertNull($user);
        self::assertCount($count, $this->repository->findAll());
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserConnectionException()
    {
        $this->expectException(UsernameNotFoundException::class);

        $ldap = $this->createMock(LdapInterface::class);
        $ldap
            ->method('bind')
            ->willThrowException(new ConnectionException());

        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'ldap://localhost',
            'dc=example,dc=com'
        );

        $this->setProperty($authenticator, 'ldap', $ldap);

        $credentials = [
            'username' => 'newton@example.com',
            'password' => 'secret',
        ];

        $authenticator->getUser($credentials, $this->provider);
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentialsValid()
    {
        $ldap = $this->createMock(LdapInterface::class);
        $ldap
            ->method('bind')
            ->willReturn(true);

        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'ldap://localhost',
            'dc=example,dc=com'
        );

        $this->setProperty($authenticator, 'ldap', $ldap);

        $credentials = [
            'username' => 'newton@example.com',
            'password' => 'secret',
        ];

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('einstein@ldap.forumsys.com');

        self::assertTrue($authenticator->checkCredentials($credentials, $user));
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentialsWrong()
    {
        $ldap = $this->createMock(LdapInterface::class);
        $ldap
            ->method('bind')
            ->willThrowException(new ConnectionException());

        $authenticator = new LdapAuthenticator(
            $this->utils,
            $this->commandBus,
            'ldap://localhost',
            'dc=example,dc=com'
        );

        $this->setProperty($authenticator, 'ldap', $ldap);

        $credentials = [
            'username' => 'newton@example.com',
            'password' => 'secret',
        ];

        /** @var User $user */
        $user = $this->repository->loadUserByUsername('einstein@ldap.forumsys.com');

        self::assertFalse($authenticator->checkCredentials($credentials, $user));
    }
}
