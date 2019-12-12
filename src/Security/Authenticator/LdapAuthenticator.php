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
use eTraxis\Application\Dictionary\LdapServerType;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authenticates user against LDAP server.
 */
class LdapAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    private $commandBus;
    private $basedn;

    /**
     * @var LdapUri
     */
    private $uri;

    /**
     * @var LdapInterface
     */
    private $ldap;

    /**
     * Dependency Injection constructor.
     *
     * @param RouterInterface     $router
     * @param SessionInterface    $session
     * @param CommandBusInterface $commandBus
     * @param null|string         $url
     * @param null|string         $basedn
     */
    public function __construct(
        RouterInterface     $router,
        SessionInterface    $session,
        CommandBusInterface $commandBus,
        ?string             $url,
        ?string             $basedn
    )
    {
        parent::__construct($router, $session);

        $this->commandBus = $commandBus;
        $this->basedn     = $basedn;

        $this->uri = new LdapUri($url ?? 'null://localhost');

        if ($this->uri->scheme !== LdapUri::SCHEME_NULL) {
            $this->ldap = Ldap::create('ext_ldap', [
                'host'       => $this->uri->host,
                'port'       => $this->uri->port,
                'encryption' => $this->uri->encryption,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        if ($this->uri->scheme === LdapUri::SCHEME_NULL) {
            return false;
        }

        return parent::supports($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $this->ldap->bind($this->uri->getBindUser($this->basedn ?? ''), $this->uri->password);

        $username = $this->ldap->escape($credentials['username'], '', LdapInterface::ESCAPE_FILTER);
        $query    = $this->ldap->query($this->basedn ?? '', sprintf('(mail=%s)', $username));
        $entries  = $query->execute();

        if (count($entries) === 0) {
            throw new UsernameNotFoundException();
        }

        $attributes = $entries[0]->getAttributes();

        $attrname = LdapServerType::get($this->uri->type);

        $uid      = $attributes[$attrname][0] ?? null;
        $fullname = $attributes['cn'][0]      ?? null;

        if ($uid === null || $fullname === null) {
            throw new UsernameNotFoundException();
        }

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::LDAP,
            'uid'      => $uid,
            'email'    => $credentials['username'],
            'fullname' => $fullname,
        ]);

        return $this->commandBus->handle($command);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        try {
            $attrname = LdapServerType::get($this->uri->type);

            /** @var \eTraxis\Entity\User $user */
            $dn = sprintf('%s=%s,%s', $attrname, $user->account->uid, $this->basedn);
            $this->ldap->bind($dn, $credentials['password']);
        }
        catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return null;
    }
}
