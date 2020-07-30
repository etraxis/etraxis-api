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

namespace eTraxis\Application\Command\Issues;

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\File;
use eTraxis\Entity\Issue;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Issues\Handler\AttachFileHandler::__invoke
 */
class AttachFileCommandTest extends TransactionalTestCase
{
    private const MEGABYTE = 1024 * 1024;

    private IssueRepositoryInterface $repository;
    private UploadedFile             $file;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);

        $filename = getcwd() . '/var/_' . md5('test.txt');
        file_put_contents($filename, str_repeat('*', self::MEGABYTE * 2));
        $this->file = new UploadedFile($filename, 'test.txt', 'text/plain', null, true);
    }

    protected function tearDown(): void
    {
        foreach (['test.txt', 'huge.txt'] as $basename) {
            $filename = getcwd() . '/var/_' . md5($basename);

            if (file_exists($filename)) {
                unlink($filename);
            }
        }

        parent::tearDown();
    }

    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $events = count($issue->events);
        $files  = count($this->doctrine->getRepository(File::class)->findAll());

        $command = new AttachFileCommand([
            'issue' => $issue->id,
            'file'  => $this->file,
        ]);

        $result = $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($events + 1, $issue->events);
        self::assertCount($files + 1, $this->doctrine->getRepository(File::class)->findAll());

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::FILE_ATTACHED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
        self::assertSame($result->id, $event->parameter);

        /** @var File $file */
        $file = $this->doctrine->getRepository(File::class)->findOneBy(['event' => $event]);
        self::assertSame($result, $file);

        self::assertSame('test.txt', $file->name);
        self::assertSame(self::MEGABYTE * 2, $file->size);
        self::assertSame('text/plain', $file->type);
        self::assertRegExp('/^([[:xdigit:]]{32})$/is', $file->uuid);
        self::assertFalse($file->isRemoved);

        $filename = 'var' . \DIRECTORY_SEPARATOR . $file->uuid;
        self::assertFileExists($filename);
        unlink($filename);
    }

    public function testMaxSize()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The file size must not exceed 2 MB.');

        $this->loginAs('ldoyle@example.com');

        $filename = getcwd() . '/var/_' . md5('huge.txt');
        file_put_contents($filename, str_repeat('*', self::MEGABYTE * 2 + 1));
        $file = new UploadedFile($filename, 'huge.txt', 'text/plain', null, true);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new AttachFileCommand([
            'issue' => $issue->id,
            'file'  => $file,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('ldoyle@example.com');

        $command = new AttachFileCommand([
            'issue' => self::UNKNOWN_ENTITY_ID,
            'file'  => $this->file,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to attach a file to this issue.');

        $this->loginAs('akoepp@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new AttachFileCommand([
            'issue' => $issue->id,
            'file'  => $this->file,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new AttachFileCommand([
            'issue' => $issue->id,
            'file'  => $this->file,
        ]);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new AttachFileCommand([
            'issue' => $issue->id,
            'file'  => $this->file,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $command = new AttachFileCommand([
            'issue' => $issue->id,
            'file'  => $this->file,
        ]);

        $this->commandBus->handle($command);
    }

    public function testFrozenIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $issue->template->frozenTime = 1;

        $command = new AttachFileCommand([
            'issue' => $issue->id,
            'file'  => $this->file,
        ]);

        $this->commandBus->handle($command);
    }
}
