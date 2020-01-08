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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Application\Dictionary\TemplatePermission;
use eTraxis\Application\Query\Collection;
use eTraxis\Application\Query\Issues\GetDependenciesQuery;
use eTraxis\Entity\Dependency;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetDependenciesHandler extends AbstractIssuesHandler
{
    protected $security;
    protected $tokenStorage;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param EntityManagerInterface        $manager
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokenStorage
     * @param IssueRepositoryInterface      $repository
     */
    public function __construct(
        EntityManagerInterface        $manager,
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokenStorage,
        IssueRepositoryInterface      $repository
    )
    {
        parent::__construct($manager);

        $this->security     = $security;
        $this->tokenStorage = $tokenStorage;
        $this->repository   = $repository;
    }

    /**
     * Query handler.
     *
     * @param GetDependenciesQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return Collection
     */
    public function __invoke(GetDependenciesQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_USER)) {
            throw new AccessDeniedHttpException();
        }

        $issue = $this->repository->find($query->issue);

        if (!$issue) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue)) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $collection = new Collection();

        $dql = $this->repository->createQueryBuilder('issue');

        // Include states.
        $dql->innerJoin('issue.state', 'state');
        $dql->addSelect('state');

        // Include templates.
        $dql->innerJoin('state.template', 'template');
        $dql->addSelect('template');

        // Include projects.
        $dql->innerJoin('template.project', 'project');
        $dql->addSelect('project');

        // Include author.
        $dql->innerJoin('issue.author', 'author');
        $dql->addSelect('author');

        // Include responsible.
        $dql->leftJoin('issue.responsible', 'responsible');
        $dql->addSelect('responsible');

        // Retrieve only issues the user is allowed to see.
        $dql
            ->leftJoin('template.rolePermissionsCollection', 'trp', Join::WITH, 'trp.permission = :permission')
            ->leftJoin('template.groupPermissionsCollection', 'tgp', Join::WITH, 'tgp.permission = :permission')
            ->andWhere($dql->expr()->orX(
                'issue.author = :user',
                'issue.responsible = :user',
                'trp.role = :role',
                $dql->expr()->in('tgp.group', ':groups')
            ))
            ->setParameters([
                'permission' => TemplatePermission::VIEW_ISSUES,
                'role'       => SystemRole::ANYONE,
                'user'       => $user,
                'groups'     => $user->groups,
            ]);

        // Restrict to dependencies of the specified issue.
        $subquery = $this->manager->createQueryBuilder()
            ->select('IDENTITY(dependency.dependency)')
            ->from(Dependency::class, 'dependency')
            ->where('dependency.issue = :issue');

        $dql
            ->andWhere($dql->expr()->in('issue', $subquery->getDQL()))
            ->setParameter('issue', $issue);

        // Search.
        $this->querySearch($dql, $query->search);

        // Filter.
        foreach ($query->filter as $property => $value) {
            $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $total = clone $dql;
        $total->distinct();
        $total->select('issue.id');
        $collection->total = count($total->getQuery()->execute());

        // Issues age.
        $dql->addSelect('CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400) AS age');
        $dql->setParameter('now', time());

        // Sorting.
        foreach ($query->sort as $property => $direction) {
            $dql = $this->queryOrder($dql, $property, $direction);
        }

        // Pagination.
        $dql->setFirstResult($query->offset);
        $dql->setMaxResults($query->limit);

        // Execute query.
        $collection->data = array_map(function ($entry) {
            return reset($entry);
        }, $dql->getQuery()->getResult());

        $collection->from = $query->offset;
        $collection->to   = count($collection->data) + $query->offset - 1;

        return $collection;
    }
}
