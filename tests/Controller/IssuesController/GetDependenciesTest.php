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

namespace eTraxis\Controller\IssuesController;

use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::getDependencies
 */
class GetDependenciesTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $expected = [
            ['Distinctio', 'Development task 8'],
            ['Distinctio', 'Support request 1'],
        ];

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame(0, $content['from']);
        self::assertSame(1, $content['to']);
        self::assertSame(2, $content['total']);

        usort($content['data'], function ($issue1, $issue2) {
            return strcmp($issue1['subject'], $issue2['subject']);
        });

        $actual = array_map(function (array $row) {
            return [$row['state']['template']['project']['name'], $row['subject']];
        }, $content['data']);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $uri = sprintf('/api/issues/%s/dependencies', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
