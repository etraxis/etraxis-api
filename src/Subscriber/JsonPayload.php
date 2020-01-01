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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Extracts JSON data payload for POST/PUT/PATCH requests.
 */
class JsonPayload implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onJson',
        ];
    }

    /**
     * Extracts JSON data payload for POST/PUT/PATCH requests.
     *
     * @param ControllerEvent $event
     */
    public function onJson(ControllerEvent $event)
    {
        $request = $event->getRequest();
        $method  = $request->getMethod();

        if ($method === Request::METHOD_POST || $method === Request::METHOD_PUT || $method === Request::METHOD_PATCH) {
            if ($request->getContentType() === 'json') {
                $data = json_decode($request->getContent(), true);
                $request->request->replace($data ?? []);
            }
        }
    }
}
