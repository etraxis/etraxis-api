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
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Message;

/**
 * Sets sender info for each outgoing email.
 */
class MessageSender implements EventSubscriberInterface
{
    private string $sender;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param string $sender
     */
    public function __construct(string $sender)
    {
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }

    /**
     * Sets sender info for each outgoing email.
     *
     * @param MessageEvent $event
     */
    public function onMessage(MessageEvent $event)
    {
        $message = $event->getMessage();

        if (!$message instanceof Message) {
            return;
        }

        $headers = $message->getHeaders();

        if (!$headers->has('from')) {
            $headers->addMailboxListHeader('from', [new Address($this->sender, 'eTraxis')]);
        }

        if (!$headers->has('reply-to')) {
            $from = $headers->get('from')->getBody();
            $headers->addMailboxListHeader('reply-to', $from);
        }

        $message->setHeaders($headers);
    }
}
