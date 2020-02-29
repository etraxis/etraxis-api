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

namespace eTraxis\Controller\FilesController;

use eTraxis\Entity\File;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\FilesController::deleteFile
 */
class DeleteFileTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);
        self::assertFalse($file->isRemoved);

        $uri = sprintf('/api/files/%s', $file->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doctrine->getManager()->refresh($file);

        self::assertTrue($file->isRemoved);
    }

    public function test401()
    {
        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $uri = sprintf('/api/files/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function test404removed()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Possimus sapiente.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $this->client->xmlHttpRequest(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
