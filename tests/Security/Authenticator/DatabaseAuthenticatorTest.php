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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @coversDefaultClass \eTraxis\Security\Authenticator\DatabaseAuthenticator
 */
class DatabaseAuthenticatorTest extends TestCase
{
    /**
     * @var DatabaseAuthenticator
     */
    protected $authenticator;

    /**
     * @var User
     */
    protected $user;

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

        /** @var RouterInterface $router */
        /** @var SessionInterface $session */
        /** @var UserPasswordEncoderInterface $encoder */
        $this->authenticator = new DatabaseAuthenticator($router, $session, $encoder);
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
