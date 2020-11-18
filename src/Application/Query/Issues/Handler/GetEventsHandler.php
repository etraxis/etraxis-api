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

namespace eTraxis\Application\Query\Issues\Handler;

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Query\Issues\GetEventsQuery;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetEventsHandler
{
    protected AuthorizationCheckerInterface $security;
    protected IssueRepositoryInterface      $issueRepository;
    protected EventRepositoryInterface      $eventRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param IssueRepositoryInterface      $issueRepository
     * @param EventRepositoryInterface      $eventRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        IssueRepositoryInterface      $issueRepository,
        EventRepositoryInterface      $eventRepository
    )
    {
        $this->security        = $security;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Query handler.
     *
     * @param GetEventsQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return \eTraxis\Entity\Comment[]
     */
    public function __invoke(GetEventsQuery $query): array
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

        $dql = $this->eventRepository->createQueryBuilder('event')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->setParameter('issue', $issue);

        if (!$this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issue)) {
            $dql->andWhere('event.type != :private');
            $dql->setParameter('private', EventType::PRIVATE_COMMENT);
        }

        return $dql->getQuery()->getResult();
    }
}
