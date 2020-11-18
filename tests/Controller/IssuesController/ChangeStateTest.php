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
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::changeState
 */
class ChangeStateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        self::assertNotSame($state, $issue->state);
        self::assertNull($issue->responsible);

        $data = [
            'responsible' => $user->id,
        ];

        $uri = sprintf('/api/issues/%s/state/%s', $issue->id, $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame($state, $issue->state);
        self::assertSame($user, $issue->responsible);
    }

    public function test400()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/state/%s', $issue->id, $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'responsible' => $user->id,
        ];

        $uri = sprintf('/api/issues/%s/state/%s', $issue->id, $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'responsible' => $user->id,
        ];

        $uri = sprintf('/api/issues/%s/state/%s', $issue->id, $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */, /* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'responsible' => $user->id,
        ];

        $uri = sprintf('/api/issues/%s/state/%s', self::UNKNOWN_ENTITY_ID, $state->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test404state()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'responsible' => $user->id,
        ];

        $uri = sprintf('/api/issues/%s/state/%s', $issue->id, self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
