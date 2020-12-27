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

use eTraxis\Entity\Field;
use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::cloneIssue
 */
class CloneIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        static::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 2,
            ],
        ];

        $uri = sprintf('/api/issues/%s', $origin->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        static::assertNotNull($issue);

        static::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        static::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/issues/{$issue->id}"));
    }

    public function test400()
    {
        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        static::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
        ];

        $uri = sprintf('/api/issues/%s', $origin->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        static::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 2,
            ],
        ];

        $uri = sprintf('/api/issues/%s', $origin->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        static::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 2,
            ],
        ];

        $uri = sprintf('/api/issues/%s', $origin->id);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('nhills@example.com');

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        static::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 2,
            ],
        ];

        $uri = sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
