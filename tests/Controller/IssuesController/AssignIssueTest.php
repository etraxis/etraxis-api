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
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::assignIssue
 */
class AssignIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        self::assertNotSame($user, $issue->responsible);

        $uri = sprintf('/api/issues/%s/assign/%s', $issue->id, $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame($user, $issue->responsible);
    }

    public function test401()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $uri = sprintf('/api/issues/%s/assign/%s', $issue->id, $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $uri = sprintf('/api/issues/%s/assign/%s', $issue->id, $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $uri = sprintf('/api/issues/%s/assign/%s', self::UNKNOWN_ENTITY_ID, $user->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test404user()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/assign/%s', $issue->id, self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
