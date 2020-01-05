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

use eTraxis\Application\Query\Issues\GetCommentsQuery;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\CommentRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetCommentsHandler
{
    protected $security;
    protected $issueRepository;
    protected $commentRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param IssueRepositoryInterface      $issueRepository
     * @param CommentRepositoryInterface    $commentRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        IssueRepositoryInterface      $issueRepository,
        CommentRepositoryInterface    $commentRepository
    )
    {
        $this->security          = $security;
        $this->issueRepository   = $issueRepository;
        $this->commentRepository = $commentRepository;
    }

    /**
     * Query handler.
     *
     * @param GetCommentsQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return \eTraxis\Entity\Comment[]
     */
    public function __invoke(GetCommentsQuery $query): array
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

        $dql = $this->commentRepository->createQueryBuilder('comment')
            ->innerJoin('comment.event', 'event')
            ->addSelect('event')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->setParameter('issue', $issue);

        if (!$this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issue)) {
            $dql->andWhere('comment.isPrivate = :private');
            $dql->setParameter('private', false);
        }

        return $dql->getQuery()->getResult();
    }
}
