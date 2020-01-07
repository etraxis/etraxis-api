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

namespace eTraxis\Application\Query\Issues\Handler;

use eTraxis\Application\Query\Issues\GetFilesQuery;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\FileRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetFilesHandler
{
    protected $security;
    protected $issueRepository;
    protected $fileRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param IssueRepositoryInterface      $issueRepository
     * @param FileRepositoryInterface       $fileRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        IssueRepositoryInterface      $issueRepository,
        FileRepositoryInterface       $fileRepository
    )
    {
        $this->security        = $security;
        $this->issueRepository = $issueRepository;
        $this->fileRepository  = $fileRepository;
    }

    /**
     * Query handler.
     *
     * @param GetFilesQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return \eTraxis\Entity\File[]
     */
    public function __invoke(GetFilesQuery $query): array
    {
        if (!$this->security->isGranted(User::ROLE_USER)) {
            throw new AccessDeniedHttpException();
        }

        $issue = $this->issueRepository->find($query->issue);

        if (!$issue) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue)) {
            throw new AccessDeniedHttpException();
        }

        $dql = $this->fileRepository->createQueryBuilder('file')
            ->innerJoin('file.event', 'event')
            ->addSelect('event')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->where('event.issue = :issue')
            ->andWhere('file.removedAt IS NULL')
            ->orderBy('event.createdAt', 'ASC')
            ->setParameter('issue', $issue);

        return $dql->getQuery()->getResult();
    }
}
