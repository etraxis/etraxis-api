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

namespace eTraxis;

use eTraxis\Entity\User;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use eTraxis\MessageBus\Contracts\EventBusInterface;
use eTraxis\MessageBus\Contracts\QueryBusInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Extended web test case with an autoboot kernel and few helpers.
 *
 * @coversNothing
 */
class WebTestCase extends SymfonyWebTestCase
{
    protected KernelBrowser       $client;
    protected ManagerRegistry     $doctrine;
    protected CommandBusInterface $commandBus;
    protected QueryBusInterface   $queryBus;
    protected EventBusInterface   $eventBus;

    /**
     * Boots the kernel and retrieve most often used services.
     *
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->doctrine   = self::$container->get('doctrine');
        $this->commandBus = self::$container->get('eTraxis\MessageBus\Contracts\CommandBusInterface');
        $this->queryBus   = self::$container->get('eTraxis\MessageBus\Contracts\QueryBusInterface');
        $this->eventBus   = self::$container->get('eTraxis\MessageBus\Contracts\EventBusInterface');
    }

    /**
     * Emulates authentication by specified user.
     *
     * @param null|string $email Login (`null` to authenticate as anonymous user).
     *
     * @return null|User Whether user was authenticated (`null` for anonymous user).
     */
    protected function loginAs(?string $email): ?User
    {
        /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
        $session = $this->client->getContainer()->get('session');

        /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface $repository */
        $repository = $this->client->getContainer()->get('doctrine')->getRepository(User::class);

        /** @var User $user */
        $user = $email ? $repository->loadUserByUsername($email) : null;

        if ($user) {

            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->client->getContainer()->get('security.token_storage')->setToken($token);

            $session->set('_security_main', serialize($token));
            $session->save();

            $cookie = new Cookie($session->getName(), $session->getId());
            $this->client->getCookieJar()->set($cookie);
        }
        else {

            $token = new AnonymousToken('', 'anon.');
            $this->client->getContainer()->get('security.token_storage')->setToken($token);

            $session->set('_security_main', serialize($token));
            $session->save();

            $cookie = new Cookie($session->getName(), $session->getId());
            $this->client->getCookieJar()->set($cookie);
        }

        return $user;
    }
}
