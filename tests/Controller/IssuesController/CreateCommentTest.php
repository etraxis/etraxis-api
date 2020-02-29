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

use eTraxis\Entity\Comment;
use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::createComment
 */
class CreateCommentTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('jmueller@example.com');

        /** @var \eTraxis\Repository\Contracts\CommentRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(Comment::class);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $comments = count($repository->findAll());

        $data = [
            'body'    => 'Lorem ipsum',
            'private' => false,
        ];

        $uri = sprintf('/api/issues/%s/comments', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertCount($comments + 1, $repository->findAll());
    }

    public function test400()
    {
        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/comments', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $data = [
            'body'    => 'Lorem ipsum',
            'private' => false,
        ];

        $uri = sprintf('/api/issues/%s/comments', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $data = [
            'body'    => 'Lorem ipsum',
            'private' => false,
        ];

        $uri = sprintf('/api/issues/%s/comments', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('jmueller@example.com');

        $data = [
            'body'    => 'Lorem ipsum',
            'private' => false,
        ];

        $uri = sprintf('/api/issues/%s/comments', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
