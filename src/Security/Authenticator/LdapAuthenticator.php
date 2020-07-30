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
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Authenticates user against LDAP server.
 */
class LdapAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface
{
    private CommandBusInterface $commandBus;
    private ?string             $basedn;
    private LdapUri             $uri;
    private LdapInterface       $ldap;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param HttpUtils           $utils
     * @param CommandBusInterface $commandBus
     * @param null|string         $url
     * @param null|string         $basedn
     */
    public function __construct(
        HttpUtils           $utils,
        CommandBusInterface $commandBus,
        ?string             $url,
        ?string             $basedn
    )
    {
        parent::__construct($utils);

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
        if ($this->uri->scheme === LdapUri::SCHEME_NULL || mb_strlen($this->basedn) === 0) {
            return false;
        }

        return parent::supports($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $attrname = LdapServerType::get($this->uri->type);

        try {
            $dn = mb_strpos($this->uri->username, '=') === false
                ? sprintf('%s=%s,%s', $attrname, $this->uri->username, $this->basedn)
                : $this->uri->username;

            $this->ldap->bind($dn, $this->uri->password);

            $username = $this->ldap->escape($credentials['username'], '', LdapInterface::ESCAPE_FILTER);
            $query    = $this->ldap->query($this->basedn, sprintf('(mail=%s)', $username));
            $entries  = $query->execute();
        }
        catch (ConnectionException $e) {
            throw new UsernameNotFoundException();
        }

        if (count($entries) === 0) {
            throw new UsernameNotFoundException();
        }

        $attributes = $entries[0]->getAttributes();

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
        catch (ConnectionException $e) {
            return false;
        }

        return true;
    }
}
