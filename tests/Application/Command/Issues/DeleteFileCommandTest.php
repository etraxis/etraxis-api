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

namespace eTraxis\Application\Command\Issues;

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Seconds;
use eTraxis\Entity\File;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\FileRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\DeleteFileHandler::__invoke
 */
class DeleteFileCommandTest extends TransactionalTestCase
{
    private FileRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(File::class);
    }

    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        static::assertNotNull($file);
        static::assertFalse($file->isRemoved);

        $filename = 'var' . \DIRECTORY_SEPARATOR . $file->uuid;
        file_put_contents($filename, str_repeat('*', $file->size));
        static::assertFileExists($filename);

        $events = count($file->issue->events);
        $files  = count($this->repository->findAll());

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($file->issue);

        static::assertCount($events + 1, $file->issue->events);
        static::assertCount($files, $this->repository->findAll());

        $events = $file->issue->events;
        $event  = end($events);

        static::assertSame(EventType::FILE_DELETED, $event->type);
        static::assertSame($file->issue, $event->issue);
        static::assertSame($user, $event->user);
        static::assertLessThanOrEqual(2, time() - $event->createdAt);
        static::assertSame($file->id, $event->parameter);

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        static::assertNotNull($file);
        static::assertTrue($file->isRemoved);
        static::assertFileNotExists($filename);
    }

    public function testUnknownFile()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown file.');

        $this->loginAs('ldoyle@example.com');

        $command = new DeleteFileCommand([
            'file' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }

    public function testRemovedFile()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown file.');

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Possimus sapiente.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this file.');

        $this->loginAs('fdooley@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [$file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $file->issue->suspend(time() + Seconds::ONE_DAY);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testFrozenIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $file->issue->template->frozenTime = 1;

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandBus->handle($command);
    }
}
