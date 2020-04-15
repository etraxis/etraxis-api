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

/** @noinspection PhpInternalEntityUsedInspection */

namespace eTraxis\Subscriber;

use eTraxis\WebTestCase;
use Symfony\Component\Mailer\DelayedEnvelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * @coversDefaultClass \eTraxis\Subscriber\MessageSender
 */
class MessageSenderTest extends WebTestCase
{
    /**
     * @var MessageSender
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = new MessageSender('noreply@example.com');
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents()
    {
        $expected = [
            MessageEvent::class,
        ];

        self::assertSame($expected, array_keys(MessageSender::getSubscribedEvents()));
    }

    /**
     * @covers ::onMessage
     */
    public function testHasFrom()
    {
        $email = new Email();

        $email
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test message')
            ->text('Message body');

        $event = new MessageEvent($email, new DelayedEnvelope($email), 'smtp://null');

        $this->subscriber->onMessage($event);

        /** @var Email $message */
        $message = $event->getMessage();

        $from = $message->getFrom();
        self::assertSame('sender@example.com', $from[0]->getAddress());

        $replyTo = $message->getReplyTo();
        self::assertSame('sender@example.com', $replyTo[0]->getAddress());
    }

    /**
     * @covers ::onMessage
     */
    public function testHasFromAndReplyTo()
    {
        $email = new Email();

        $email
            ->from('sender@example.com')
            ->replyTo('reply@example.com')
            ->to('recipient@example.com')
            ->subject('Test message')
            ->text('Message body');

        $event = new MessageEvent($email, new DelayedEnvelope($email), 'smtp://null');

        $this->subscriber->onMessage($event);

        /** @var Email $message */
        $message = $event->getMessage();

        $from = $message->getFrom();
        self::assertSame('sender@example.com', $from[0]->getAddress());

        $replyTo = $message->getReplyTo();
        self::assertSame('reply@example.com', $replyTo[0]->getAddress());
    }

    /**
     * @covers ::onMessage
     */
    public function testNoFrom()
    {
        $email = new Email();

        $email
            ->to('recipient@example.com')
            ->subject('Test message')
            ->text('Message body');

        $event = new MessageEvent($email, new DelayedEnvelope($email), 'smtp://null');

        $this->subscriber->onMessage($event);

        /** @var Email $message */
        $message = $event->getMessage();

        $from = $message->getFrom();
        self::assertSame('noreply@example.com', $from[0]->getAddress());

        $replyTo = $message->getReplyTo();
        self::assertSame('noreply@example.com', $replyTo[0]->getAddress());
    }

    /**
     * @covers ::onMessage
     */
    public function testNotMessage()
    {
        $email = new Email();

        $email
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test message')
            ->text('Message body');

        $event = new MessageEvent(new RawMessage($email->toIterable()), new DelayedEnvelope($email), 'smtp://null');

        $this->subscriber->onMessage($event);

        $message = $event->getMessage();

        foreach ($message->toIterable() as $entry) {
            self::assertNotRegExp('/Reply\-To\: /', $entry);
        }
    }
}
