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

namespace eTraxis\Application\Query\Projects\Handler;

use Doctrine\ORM\QueryBuilder;
use eTraxis\Application\Query\Collection;
use eTraxis\Application\Query\Projects\GetProjectsQuery;
use eTraxis\Entity\Project;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetProjectsHandler
{
    private AuthorizationCheckerInterface $security;
    private ProjectRepositoryInterface    $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ProjectRepositoryInterface    $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, ProjectRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Query handler.
     *
     * @param GetProjectsQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Collection
     */
    public function __invoke(GetProjectsQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException();
        }

        $collection = new Collection();

        $dql = $this->repository->createQueryBuilder('project');

        // Search.
        $this->querySearch($dql, $query->search);

        // Filter.
        foreach ($query->filter as $property => $value) {
            $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $total = clone $dql;
        $total->select('COUNT(project.id)');
        $collection->total = (int) $total->getQuery()->getSingleScalarResult();

        // Sorting.
        foreach ($query->sort as $property => $direction) {
            $dql = $this->queryOrder($dql, $property, $direction);
        }

        // Pagination.
        $dql->setFirstResult($query->offset);
        $dql->setMaxResults($query->limit);

        // Execute query.
        $collection->data = $dql->getQuery()->getResult();
        $collection->from = $query->offset;
        $collection->to   = count($collection->data) + $query->offset - 1;

        return $collection;
    }

    /**
     * Alters query in accordance with the specified search.
     *
     * @param QueryBuilder $dql
     * @param string       $search
     *
     * @return QueryBuilder
     */
    private function querySearch(QueryBuilder $dql, ?string $search): QueryBuilder
    {
        if (mb_strlen($search) !== 0) {

            $dql->andWhere($dql->expr()->orX(
                'LOWER(project.name) LIKE :search',
                'LOWER(project.description) LIKE :search'
            ));

            $dql->setParameter('search', mb_strtolower("%{$search}%"));
        }

        return $dql;
    }

    /**
     * Alters query to filter by the specified property.
     *
     * @param QueryBuilder $dql
     * @param string       $property
     * @param mixed        $value
     *
     * @return QueryBuilder
     */
    private function queryFilter(QueryBuilder $dql, string $property, $value = null): QueryBuilder
    {
        switch ($property) {

            case Project::JSON_NAME:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('project.name IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(project.name) LIKE LOWER(:name)');
                    $dql->setParameter('name', "%{$value}%");
                }

                break;

            case Project::JSON_DESCRIPTION:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('project.description IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(project.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case Project::JSON_SUSPENDED:

                $dql->andWhere('project.isSuspended = :suspended');
                $dql->setParameter('suspended', (bool) $value);

                break;
        }

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     *
     * @param QueryBuilder $dql
     * @param string       $property
     * @param string       $direction
     *
     * @return QueryBuilder
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $map = [
            Project::JSON_ID          => 'project.id',
            Project::JSON_NAME        => 'project.name',
            Project::JSON_DESCRIPTION => 'project.description',
            Project::JSON_CREATED     => 'project.createdAt',
            Project::JSON_SUSPENDED   => 'project.isSuspended',
        ];

        if (mb_strtoupper($direction) !== GetProjectsQuery::SORT_DESC) {
            $direction = GetProjectsQuery::SORT_ASC;
        }

        return $dql->addOrderBy($map[$property], $direction);
    }
}
