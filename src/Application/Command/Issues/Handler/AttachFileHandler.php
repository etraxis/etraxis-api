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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Issues\AttachFileCommand;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Dictionary\MimeType;
use eTraxis\Entity\Event;
use eTraxis\Entity\File;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\FileRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class AttachFileHandler
{
    private const MEGABYTE = 1048576;

    private AuthorizationCheckerInterface $security;
    private TokenStorageInterface         $tokenStorage;
    private IssueRepositoryInterface      $issueRepository;
    private EventRepositoryInterface      $eventRepository;
    private FileRepositoryInterface       $fileRepository;
    private EntityManagerInterface        $manager;

    /**
     * @var int Maximum allowed size of a single file.
     */
    private int $maxsize;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokenStorage
     * @param IssueRepositoryInterface      $issueRepository
     * @param EventRepositoryInterface      $eventRepository
     * @param FileRepositoryInterface       $fileRepository
     * @param EntityManagerInterface        $manager
     * @param int                           $maxsize
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokenStorage,
        IssueRepositoryInterface      $issueRepository,
        EventRepositoryInterface      $eventRepository,
        FileRepositoryInterface       $fileRepository,
        EntityManagerInterface        $manager,
        int                           $maxsize
    )
    {
        $this->security        = $security;
        $this->tokenStorage    = $tokenStorage;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
        $this->fileRepository  = $fileRepository;
        $this->manager         = $manager;
        $this->maxsize         = $maxsize;
    }

    /**
     * Command handler.
     *
     * @param AttachFileCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return File
     */
    public function __invoke(AttachFileCommand $command): File
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::ATTACH_FILE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to attach a file to this issue.');
        }

        if ($command->file->getSize() > $this->maxsize * self::MEGABYTE) {
            throw new BadRequestHttpException(sprintf('The file size must not exceed %d MB.', $this->maxsize));
        }

        $event = new Event(EventType::FILE_ATTACHED, $issue, $user);

        $file = new File(
            $event,
            $command->file->getClientOriginalName(),
            $command->file->getSize(),
            $command->file->getMimeType() ?? MimeType::FALLBACK
        );

        $this->eventRepository->persist($event);
        $this->fileRepository->persist($file);

        $this->manager->flush();

        $query = $this->manager->createQueryBuilder()
            ->update(Event::class, 'event')
            ->set('event.parameter', $file->id)
            ->where('event.id = :event')
            ->setParameter('event', $event->id);

        $query->getQuery()->execute();

        $this->manager->refresh($event);

        $directory = dirname($this->fileRepository->getFullPath($file));
        $command->file->move($directory, $file->uuid);

        return $file;
    }
}
