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

namespace eTraxis\Application\Command\Issues\Handler;

use eTraxis\Application\Command\Issues\DeleteFileCommand;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\FileRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteFileHandler
{
    private AuthorizationCheckerInterface $security;
    private TokenStorageInterface         $tokenStorage;
    private EventRepositoryInterface      $eventRepository;
    private FileRepositoryInterface       $fileRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokenStorage
     * @param EventRepositoryInterface      $eventRepository
     * @param FileRepositoryInterface       $fileRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokenStorage,
        EventRepositoryInterface      $eventRepository,
        FileRepositoryInterface       $fileRepository
    )
    {
        $this->security        = $security;
        $this->tokenStorage    = $tokenStorage;
        $this->eventRepository = $eventRepository;
        $this->fileRepository  = $fileRepository;
    }

    /**
     * Command handler.
     *
     * @param DeleteFileCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(DeleteFileCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\eTraxis\Entity\File $file */
        $file = $this->fileRepository->find($command->file);

        if (!$file || $file->isRemoved) {
            throw new NotFoundHttpException('Unknown file.');
        }

        if (!$this->security->isGranted(IssueVoter::DELETE_FILE, $file->event->issue)) {
            throw new AccessDeniedHttpException('You are not allowed to delete this file.');
        }

        $event = new Event(EventType::FILE_DELETED, $file->event->issue, $user, $file->id);

        $file->remove();

        $this->eventRepository->persist($event);
        $this->fileRepository->persist($file);

        $filename = $this->fileRepository->getFullPath($file);

        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}
