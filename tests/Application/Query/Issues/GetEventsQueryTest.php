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

namespace eTraxis\Application\Query\Issues;

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Issues\Handler\GetEventsHandler
 */
class GetEventsQueryTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testSuccessWithPrivate()
    {
        $expected = [
            [EventType::ISSUE_CREATED,   'Dorcas Ernser'],
            [EventType::STATE_CHANGED,   'Leland Doyle'],
            [EventType::ISSUE_ASSIGNED,  'Leland Doyle'],
            [EventType::FILE_ATTACHED,   'Leland Doyle'],
            [EventType::FILE_ATTACHED,   'Leland Doyle'],
            [EventType::PUBLIC_COMMENT,  'Leland Doyle'],
            [EventType::ISSUE_CLOSED,    'Dennis Quigley'],
            [EventType::ISSUE_REOPENED,  'Dorcas Ernser'],
            [EventType::STATE_CHANGED,   'Dorcas Ernser'],
            [EventType::ISSUE_ASSIGNED,  'Dorcas Ernser'],
            [EventType::FILE_DELETED,    'Dorcas Ernser'],
            [EventType::PRIVATE_COMMENT, 'Dorcas Ernser'],
            [EventType::FILE_ATTACHED,   'Dennis Quigley'],
            [EventType::PUBLIC_COMMENT,  'Dennis Quigley'],
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $query = new GetEventsQuery([
            'issue' => $issue->id,
        ]);

        /** @var Event[] $events */
        $events = $this->queryBus->execute($query);

        $actual = array_map(fn (Event $event) => [
            $event->type,
            $event->user->fullname,
        ], $events);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testSuccessNoPrivate()
    {
        $expected = [
            [EventType::ISSUE_CREATED,  'Dorcas Ernser'],
            [EventType::STATE_CHANGED,  'Leland Doyle'],
            [EventType::ISSUE_ASSIGNED, 'Leland Doyle'],
            [EventType::FILE_ATTACHED,  'Leland Doyle'],
            [EventType::FILE_ATTACHED,  'Leland Doyle'],
            [EventType::PUBLIC_COMMENT, 'Leland Doyle'],
            [EventType::ISSUE_CLOSED,   'Dennis Quigley'],
            [EventType::ISSUE_REOPENED, 'Dorcas Ernser'],
            [EventType::STATE_CHANGED,  'Dorcas Ernser'],
            [EventType::ISSUE_ASSIGNED, 'Dorcas Ernser'],
            [EventType::FILE_DELETED,   'Dorcas Ernser'],
            [EventType::FILE_ATTACHED,  'Dennis Quigley'],
            [EventType::PUBLIC_COMMENT, 'Dennis Quigley'],
        ];

        $this->loginAs('fdooley@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $query = new GetEventsQuery([
            'issue' => $issue->id,
        ]);

        /** @var Event[] $events */
        $events = $this->queryBus->execute($query);

        $actual = array_map(fn (Event $event) => [
            $event->type,
            $event->user->fullname,
        ], $events);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDeniedAnonymous()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs(null);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $query = new GetEventsQuery([
            'issue' => $issue->id,
        ]);

        $this->queryBus->execute($query);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDeniedPermissions()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('lucas.oconnell@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $query = new GetEventsQuery([
            'issue' => $issue->id,
        ]);

        $this->queryBus->execute($query);
    }

    /**
     * @covers ::__invoke
     */
    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('ldoyle@example.com');

        $query = new GetEventsQuery([
            'issue' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->queryBus->execute($query);
    }
}
