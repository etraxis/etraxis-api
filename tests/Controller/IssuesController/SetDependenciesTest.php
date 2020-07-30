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
 * @covers \eTraxis\Controller\API\IssuesController::setDependencies
 */
class SetDependenciesTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $new */
        [/* skipping */, /* skipping */, $new] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $expected = [
            $existing->id,
        ];

        $actual = array_map(fn (Issue $issue) => $issue->id, $issue->dependencies);

        self::assertSame($expected, $actual);

        $data = [
            'add'    => [
                $new->id,
            ],
            'remove' => [
                $existing->id,
            ],
        ];

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($issue);

        $expected = [
            $new->id,
        ];

        $actual = array_map(fn (Issue $issue) => $issue->id, $issue->dependencies);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $new */
        [/* skipping */, /* skipping */, $new] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $data = [
            'add'    => [
                $new->id,
            ],
            'remove' => [
                $existing->id,
            ],
        ];

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $new */
        [/* skipping */, /* skipping */, $new] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $data = [
            'add'    => [
                $new->id,
            ],
            'remove' => [
                $existing->id,
            ],
        ];

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var Issue $existing */
        [/* skipping */, /* skipping */, $existing] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        /** @var Issue $new */
        [/* skipping */, /* skipping */, $new] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $data = [
            'add'    => [
                $new->id,
            ],
            'remove' => [
                $existing->id,
            ],
        ];

        $uri = sprintf('/api/issues/%s/dependencies', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
