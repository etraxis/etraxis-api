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

namespace eTraxis\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * "Sticky" locale.
 */
class StickyLocale implements EventSubscriberInterface
{
    private SessionInterface $session;
    private string           $locale;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param SessionInterface $session
     * @param string           $locale
     */
    public function __construct(SessionInterface $session, string $locale)
    {
        $this->session = $session;
        $this->locale  = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'saveLocale',
            SecurityEvents::SWITCH_USER       => 'onSwitchUser',
            KernelEvents::REQUEST             => ['setLocale', 20],
        ];
    }

    /**
     * Saves user's locale when one has been authenticated.
     *
     * @param InteractiveLoginEvent $event
     */
    public function saveLocale(InteractiveLoginEvent $event): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $event->getAuthenticationToken()->getUser();

        $this->session->set('_locale', $user->locale);
    }

    /**
     * Overrides current locale with the locale of impersonated user.
     *
     * @param SwitchUserEvent $event
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session) {
            /** @var \eTraxis\Entity\User $user */
            $user = $event->getTargetUser();

            $session->set('_locale', $user->locale);
        }
    }

    /**
     * Overrides current locale with one saved in the session.
     *
     * @param RequestEvent $event
     */
    public function setLocale(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->hasPreviousSession()) {
            $request->setLocale($request->getSession()->get('_locale', $this->locale));
        }
    }
}
