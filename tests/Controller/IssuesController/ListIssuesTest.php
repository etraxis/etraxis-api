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

use eTraxis\Entity\Issue;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::listIssues
 */
class ListIssuesTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('fdooley@example.com');

        $expected = array_map(fn (Issue $issue) => [
            $issue->id,
            $issue->subject,
        ], $this->doctrine->getRepository(Issue::class)->findBy([], ['id' => 'ASC']));

        $uri = '/api/issues';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent(), true);

        static::assertSame(0, $content['from']);
        static::assertSame(41, $content['to']);
        static::assertSame(42, $content['total']);

        usort($content['data'], fn ($issue1, $issue2) => $issue1['id'] - $issue2['id']);

        $actual = array_map(fn (array $row) => [
            $row['id'],
            $row['subject'],
        ], $content['data']);

        static::assertSame($expected, $actual);
    }

    public function test401()
    {
        $uri = '/api/issues';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
