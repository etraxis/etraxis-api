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

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param RouterInterface              $router
     * @param SessionInterface             $session
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(
        RouterInterface              $router,
        SessionInterface             $session,
        UserPasswordEncoderInterface $encoder
    )
    {
        parent::__construct($router, $session);

        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            /** @var \eTraxis\Entity\User $user */
            $user = $userProvider->loadUserByUsername($credentials['username']);

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
            throw new AuthenticationException('Bad credentials.');
        }

        return true;
    }
}
