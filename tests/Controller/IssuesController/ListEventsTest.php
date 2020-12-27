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

namespace eTraxis\Controller\IssuesController;

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Issue;
use eTraxis\Entity\State;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::listEvents
 */
class ListEventsTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $expected = [
            EventType::ISSUE_CREATED,
            EventType::ISSUE_EDITED,
            EventType::FILE_ATTACHED,
            EventType::STATE_CHANGED,
            EventType::ISSUE_ASSIGNED,
            EventType::DEPENDENCY_ADDED,
            EventType::PUBLIC_COMMENT,
            EventType::ISSUE_CLOSED,
        ];

        $expectedFirst = [
            'type'      => 'issue.created',
            'user'      => [
                'id'       => $issue->author->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $issue->createdAt,
            'state'     => [
                'id'          => $state->id,
                'name'        => 'New',
                'type'        => 'initial',
                'responsible' => 'remove',
            ],
        ];

        $uri = sprintf('/api/issues/%s/events', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $actual  = array_map(fn ($row) => $row['type'], $content);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        static::assertSame($expected, $actual);
        static::assertSame($expectedFirst, $content[0]);
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/events', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/events', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $uri = sprintf('/api/issues/%s/events', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
