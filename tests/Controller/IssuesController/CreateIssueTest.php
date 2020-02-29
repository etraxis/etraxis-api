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

use eTraxis\Entity\Field;
use eTraxis\Entity\Issue;
use eTraxis\Entity\Template;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\IssuesController::createIssue
 */
class CreateIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('nhills@example.com');

        /** @var Template $template */
        [/* skipping */, /* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'template' => $template->id,
            'subject'  => 'Test issue',
            'fields'   => [
                $field->id => 2,
            ],
        ];

        $uri = '/api/issues';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNotNull($issue);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/issues/{$issue->id}"));
    }

    public function test400()
    {
        $this->loginAs('nhills@example.com');

        /** @var Template $template */
        [/* skipping */, /* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'template' => $template->id,
            'subject'  => 'Test issue',
        ];

        $uri = '/api/issues';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Template $template */
        [/* skipping */, /* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'template' => $template->id,
            'subject'  => 'Test issue',
            'fields'   => [
                $field->id => 2,
            ],
        ];

        $uri = '/api/issues';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [/* skipping */, /* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'template' => $template->id,
            'subject'  => 'Test issue',
            'fields'   => [
                $field->id => 2,
            ],
        ];

        $uri = '/api/issues';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('nhills@example.com');

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'template' => self::UNKNOWN_ENTITY_ID,
            'subject'  => 'Test issue',
            'fields'   => [
                $field->id => 2,
            ],
        ];

        $uri = '/api/issues';

        $this->client->xmlHttpRequest(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
