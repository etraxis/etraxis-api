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

use eTraxis\Entity\File;
use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\IssuesController::createFile
 */
class CreateFileTest extends TransactionalTestCase
{
    /**
     * @var UploadedFile
     */
    private $file;

    protected function setUp(): void
    {
        parent::setUp();

        $filename = getcwd() . '/var/_' . md5('test.txt');
        file_put_contents($filename, 'Lorem ipsum');
        $this->file = new UploadedFile($filename, 'test.txt', 'text/plain', null, true);
    }

    protected function tearDown(): void
    {
        $filename = getcwd() . '/var/_' . md5('test.txt');

        if (file_exists($filename)) {
            unlink($filename);
        }

        parent::tearDown();
    }

    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var \eTraxis\Repository\Contracts\FileRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(File::class);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $files = count($repository->findAll());

        $uri = sprintf('/api/issues/%s/files', $issue->id);

        $this->client->request(Request::METHOD_POST, $uri, [], ['attachment' => $this->file], ['CONTENT_TYPE' => 'application/json']);

        /** @var File $file */
        $file = $repository->findOneBy(['name' => 'test.txt']);
        self::assertNotNull($file);
        self::assertSame($issue, $file->issue);

        if (file_exists($repository->getFullPath($file))) {
            unlink($repository->getFullPath($file));
        }

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->isRedirect("http://localhost/api/files/{$file->id}"));

        self::assertCount($files + 1, $repository->findAll());
    }

    public function test400()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/files', $issue->id);

        $this->client->request(Request::METHOD_POST, $uri, [], [], ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function test401()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/files', $issue->id);

        $this->client->request(Request::METHOD_POST, $uri, [], ['attachment' => $this->file], ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/files', $issue->id);

        $this->client->request(Request::METHOD_POST, $uri, [], ['attachment' => $this->file], ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $uri = sprintf('/api/issues/%s/files', self::UNKNOWN_ENTITY_ID);

        $this->client->request(Request::METHOD_POST, $uri, [], ['attachment' => $this->file], ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
