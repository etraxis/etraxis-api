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

use eTraxis\Application\Command\Users\RegisterExternalAccountCommand;
use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Authenticates a user against Bitbucket OAuth2 server.
 */
class BitbucketAuthenticator extends AbstractAuthenticator
{
    private $commandBus;
    private $client;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param RouterInterface     $router
     * @param SessionInterface    $session
     * @param CommandBusInterface $commandBus
     * @param ClientRegistry      $clientRegistry
     */
    public function __construct(
        RouterInterface     $router,
        SessionInterface    $session,
        CommandBusInterface $commandBus,
        ClientRegistry      $clientRegistry
    )
    {
        parent::__construct($router, $session);

        $this->commandBus = $commandBus;
        $this->client     = $clientRegistry->getClient('bitbucket');
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        $route    = $request->attributes->get('_route');
        $hasState = $request->query->has('state');
        $hasCode  = $request->query->has('code');

        return $route === 'oauth_bitbucket' && $hasState && $hasCode;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            /** @var \Stevenmaguire\OAuth2\Client\Provider\BitbucketResourceOwner $owner */
            $owner = $this->client->fetchUserFromToken($credentials);

            $command = new RegisterExternalAccountCommand([
                'provider' => AccountProvider::BITBUCKET,
                'uid'      => $owner->getId(),
                'fullname' => $owner->getName(),
            ]);

            $provider = $this->client->getOAuth2Provider();

            $request = $provider->getAuthenticatedRequest(
                Request::METHOD_GET,
                'https://api.bitbucket.org/2.0/user/emails',
                $credentials
            );

            $response = $provider->getResponse($request);
            $contents = json_decode($response->getBody()->getContents(), true);
            $emails   = $contents['values'] ?? [];

            foreach ($emails as $email) {
                if ($email['is_primary'] ?? false) {
                    $command->email = $email['email'];
                    break;
                }
            }

            return $this->commandBus->handle($command);
        }
        catch (\Throwable $throwable) {
            throw new AuthenticationException('Bad credentials.', 0, $throwable);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }
}
