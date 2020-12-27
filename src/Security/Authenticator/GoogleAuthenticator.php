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

use eTraxis\Application\Command\Users\RegisterExternalAccountCommand;
use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Authenticates a user against Google OAuth2 server.
 */
class GoogleAuthenticator extends AbstractAuthenticator
{
    private CommandBusInterface   $commandBus;
    private OAuth2ClientInterface $client;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param HttpUtils           $utils
     * @param CommandBusInterface $commandBus
     * @param ClientRegistry      $clientRegistry
     */
    public function __construct(
        HttpUtils           $utils,
        CommandBusInterface $commandBus,
        ClientRegistry      $clientRegistry
    )
    {
        parent::__construct($utils);

        $this->commandBus = $commandBus;
        $this->client     = $clientRegistry->getClient('google');
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request)
    {
        $route    = $request->attributes->get('_route');
        $hasState = $request->query->has('state');
        $hasCode  = $request->query->has('code');

        return $route === 'oauth_google' && $hasState && $hasCode;
    }

    /**
     * {@inheritDoc}
     */
    public function getCredentials(Request $request)
    {
        try {
            return $this->client->getAccessToken();
        }
        catch (\Throwable $throwable) {
            throw new AuthenticationException('Bad credentials.', 0, $throwable);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            /** @var \League\OAuth2\Client\Provider\GoogleUser $owner */
            $owner = $this->client->fetchUserFromToken($credentials);

            $command = new RegisterExternalAccountCommand([
                'provider' => AccountProvider::GOOGLE,
                'uid'      => $owner->getId(),
                'email'    => $owner->getEmail(),
                'fullname' => $owner->getName(),
            ]);

            return $this->commandBus->handle($command);
        }
        catch (\Throwable $throwable) {
            throw new AuthenticationException('Bad credentials.', 0, $throwable);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }
}
