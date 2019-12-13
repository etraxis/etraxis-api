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

use eTraxis\Application\Event\Users\LoginFailedEvent;
use eTraxis\Application\Event\Users\LoginSuccessfulEvent;
use eTraxis\MessageBus\Contracts\EventBusInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Authenticates a user via eTraxis database.
 */
class DatabaseAuthenticator extends AbstractAuthenticator
{
    private $encoder;
    private $eventBus;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param RouterInterface              $router
     * @param SessionInterface             $session
     * @param UserPasswordEncoderInterface $encoder
     * @param EventBusInterface            $eventBus
     */
    public function __construct(
        RouterInterface              $router,
        SessionInterface             $session,
        UserPasswordEncoderInterface $encoder,
        EventBusInterface            $eventBus
    )
    {
        parent::__construct($router, $session);

        $this->encoder  = $encoder;
        $this->eventBus = $eventBus;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            /** @var \eTraxis\Entity\User $user */
            $user = $userProvider->loadUserByUsername($credentials['username']);

            if ($user->isAccountExternal()) {
                throw new UsernameNotFoundException();
            }

            return $user;
        }
        catch (UsernameNotFoundException $e) {
            throw new AuthenticationException('Bad credentials.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!$this->encoder->isPasswordValid($user, $credentials['password'])) {

            $event = new LoginFailedEvent($credentials);
            $this->eventBus->sendAsync($event);

            throw new AuthenticationException('Bad credentials.');
        }

        $event = new LoginSuccessfulEvent($credentials);
        $this->eventBus->sendAsync($event);

        return true;
    }
}
