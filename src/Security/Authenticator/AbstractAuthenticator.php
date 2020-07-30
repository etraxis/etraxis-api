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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Abstract authenticator.
 */
abstract class AbstractAuthenticator extends AbstractGuardAuthenticator implements AuthenticatorInterface
{
    use TargetPathTrait;

    protected HttpUtils $utils;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param HttpUtils $utils
     */
    public function __construct(HttpUtils $utils)
    {
        $this->utils = $utils;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, ?AuthenticationException $authException = null)
    {
        // Do not redirect the user if it's an AJAX request.
        if ($request->isXmlHttpRequest() || $request->getContentType() === 'json') {

            $message = $authException
                ? $authException->getMessage()
                : 'Authentication required.';

            return new JsonResponse($message, JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->utils->createRedirectResponse($request, 'login');
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        return $request->request->has('_username') && $request->request->has('_password');
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::LAST_USERNAME, $request->request->get('_username'));
        }

        return [
            'username' => $request->request->get('_username'),
            'password' => $request->request->get('_password'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($request->hasSession()) {

            $session = $request->getSession();

            $session->remove(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::LAST_USERNAME);

            // An URL the user was trying to reach before authentication.
            $targetPath = $this->getTargetPath($session, $providerKey);
        }

        // Do not redirect the user if it's an AJAX request.
        return $request->isXmlHttpRequest() || $request->getContentType() === 'json'
            ? new JsonResponse()
            : $this->utils->createRedirectResponse($request, $targetPath ?? 'homepage');
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return true;
    }
}
