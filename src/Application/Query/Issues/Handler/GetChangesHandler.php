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

use Doctrine\ORM\Query\Expr\Join;
use eTraxis\Application\Dictionary\FieldPermission;
use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Application\Query\Issues\GetChangesQuery;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\ChangeRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetChangesHandler
{
    protected AuthorizationCheckerInterface $security;
    protected TokenStorageInterface         $tokenStorage;
    protected IssueRepositoryInterface      $issueRepository;
    protected ChangeRepositoryInterface     $changeRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokenStorage
     * @param IssueRepositoryInterface      $issueRepository
     * @param ChangeRepositoryInterface     $changeRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokenStorage,
        IssueRepositoryInterface      $issueRepository,
        ChangeRepositoryInterface     $changeRepository
    )
    {
        $this->security         = $security;
        $this->tokenStorage     = $tokenStorage;
        $this->issueRepository  = $issueRepository;
        $this->changeRepository = $changeRepository;
    }

    /**
     * Query handler.
     *
     * @param GetChangesQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return \eTraxis\Entity\Comment[]
     */
    public function __invoke(GetChangesQuery $query): array
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

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $dql = $this->changeRepository->createQueryBuilder('change')
            ->innerJoin('change.event', 'event')
            ->addSelect('event')
            ->innerJoin('event.issue', 'issue')
            ->addSelect('issue')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->leftJoin('change.field', 'field')
            ->addSelect('field')
            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->addOrderBy('field.position', 'ASC');

        // Retrieve only fields the user is allowed to see.
        $dql
            ->leftJoin('field.rolePermissionsCollection', 'frp_anyone', Join::WITH, 'frp_anyone.role = :role_anyone')
            ->leftJoin('field.rolePermissionsCollection', 'frp_author', Join::WITH, 'frp_author.role = :role_author')
            ->leftJoin('field.rolePermissionsCollection', 'frp_responsible', Join::WITH, 'frp_responsible.role = :role_responsible')
            ->leftJoin('field.groupPermissionsCollection', 'fgp')
            ->andWhere($dql->expr()->orX(
                'change.field IS NULL',
                $dql->expr()->in('frp_anyone.permission', [FieldPermission::READ_ONLY, FieldPermission::READ_WRITE]),
                $dql->expr()->andX(
                    'issue.author = :user',
                    $dql->expr()->in('frp_author.permission', [FieldPermission::READ_ONLY, FieldPermission::READ_WRITE])
                ),
                $dql->expr()->andX(
                    'issue.responsible = :user',
                    $dql->expr()->in('frp_responsible.permission', [FieldPermission::READ_ONLY, FieldPermission::READ_WRITE])
                ),
                $dql->expr()->in('fgp.group', ':groups')
            ));

        $dql->setParameters([
            'role_anyone'      => SystemRole::ANYONE,
            'role_author'      => SystemRole::AUTHOR,
            'role_responsible' => SystemRole::RESPONSIBLE,
            'issue'            => $issue,
            'user'             => $user,
            'groups'           => $user->groups,
        ]);

        return $dql->getQuery()->getResult();
    }
}
