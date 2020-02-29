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
use eTraxis\Entity\User;
use eTraxis\Entity\Watcher;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::watchMultipleIssues
 */
class WatchMultipleIssuesTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        /** @var Issue $watching */
        [$watching] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        self::assertNotNull($this->doctrine->getRepository(Watcher::class)->findOneBy(['issue' => $watching, 'user' => $user]));
        self::assertNull($this->doctrine->getRepository(Watcher::class)->findOneBy(['issue' => $unwatching, 'user' => $user]));
        self::assertNull($this->doctrine->getRepository(Watcher::class)->findOneBy(['issue' => $forbidden, 'user' => $user]));

        $data = [
            'issues' => [
                $watching->id,
                $unwatching->id,
                $forbidden->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $uri = '/api/issues/watch';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertNotNull($this->doctrine->getRepository(Watcher::class)->findOneBy(['issue' => $watching, 'user' => $user]));
        self::assertNotNull($this->doctrine->getRepository(Watcher::class)->findOneBy(['issue' => $unwatching, 'user' => $user]));
        self::assertNull($this->doctrine->getRepository(Watcher::class)->findOneBy(['issue' => $forbidden, 'user' => $user]));
    }

    public function test401()
    {
        /** @var Issue $watching */
        [$watching] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $data = [
            'issues' => [
                $watching->id,
                $unwatching->id,
                $forbidden->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $uri = '/api/issues/watch';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
