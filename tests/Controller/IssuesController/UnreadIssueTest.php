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
use eTraxis\Entity\LastRead;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::unreadIssue
 */
class UnreadIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        static::assertNotNull($this->doctrine->getRepository(LastRead::class)->findOneBy(['issue' => $issue, 'user' => $user]));

        $uri = sprintf('/api/issues/%s/unread', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        static::assertNull($this->doctrine->getRepository(LastRead::class)->findOneBy(['issue' => $issue, 'user' => $user]));
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/unread', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
