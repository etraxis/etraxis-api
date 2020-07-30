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

use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Entity\User;
use eTraxis\MessageBus\Contracts\EventBusInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * @coversDefaultClass \eTraxis\Security\Authenticator\DatabaseAuthenticator
 */
class DatabaseAuthenticatorTest extends TestCase
{
    private DatabaseAuthenticator $authenticator;
    private User $user;

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

        /** @var RouterInterface $router */
        $utils = new HttpUtils($router);

        $encoder = $this->createMock(UserPasswordEncoderInterface::class);
        $encoder
            ->method('isPasswordValid')
            ->willReturnMap([
                [$this->user, 'secret', true],
                [$this->user, 'wrong', false],
            ]);

        $eventBus = $this->createMock(EventBusInterface::class);

        /** @var UserPasswordEncoderInterface $encoder */
        /** @var EventBusInterface $eventBus */
        $this->authenticator = new DatabaseAuthenticator($utils, $encoder, $eventBus);
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserSuccess()
    {
        $credentials = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        $provider = $this->createMock(UserProviderInterface::class);
        $provider
            ->method('loadUserByUsername')
            ->with('admin')
            ->willReturn($this->user);

        /** @var UserProviderInterface $provider */
        self::assertSame($this->user, $this->authenticator->getUser($credentials, $provider));
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserExternal()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $credentials = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        $this->user->account->provider = AccountProvider::LDAP;

        $provider = $this->createMock(UserProviderInterface::class);
        $provider
            ->method('loadUserByUsername')
            ->with('admin')
            ->willReturn($this->user);

        /** @var UserProviderInterface $provider */
        $this->authenticator->getUser($credentials, $provider);
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserNotFound()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $credentials = [
            'username' => 'unknown',
            'password' => 'secret',
        ];

        $provider = $this->createMock(UserProviderInterface::class);
        $provider
            ->method('loadUserByUsername')
            ->with('unknown')
            ->willThrowException(new UsernameNotFoundException('Not found.'));

        /** @var UserProviderInterface $provider */
        $this->authenticator->getUser($credentials, $provider);
    }

    /**
     * @covers ::getPassword
     */
    public function testGetPassword()
    {
        $credentials = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        self::assertSame('secret', $this->authenticator->getPassword($credentials));
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentialsSuccess()
    {
        $credentials = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        self::assertTrue($this->authenticator->checkCredentials($credentials, $this->user));
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentialsFailure()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $credentials = [
            'username' => 'admin',
            'password' => 'wrong',
        ];

        $this->authenticator->checkCredentials($credentials, $this->user);
    }
}
