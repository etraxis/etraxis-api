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

namespace eTraxis\Application\Query\Groups\Handler;

use Doctrine\ORM\QueryBuilder;
use eTraxis\Application\Query\Collection;
use eTraxis\Application\Query\Groups\GetGroupsQuery;
use eTraxis\Entity\Group;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetGroupsHandler
{
    private AuthorizationCheckerInterface $security;
    private GroupRepositoryInterface      $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param GroupRepositoryInterface      $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, GroupRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Query handler.
     *
     * @param GetGroupsQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Collection
     */
    public function __invoke(GetGroupsQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException();
        }

        $collection = new Collection();

        $dql = $this->repository->createQueryBuilder('grp');

        // Include projects.
        $dql->leftJoin('grp.project', 'project');
        $dql->addSelect('project');

        // Search.
        $this->querySearch($dql, $query->search);

        // Filter.
        foreach ($query->filter as $property => $value) {
            $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $total = clone $dql;
        $total->select('COUNT(grp.id)');
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
     * @param null|string  $search
     *
     * @return QueryBuilder
     */
    private function querySearch(QueryBuilder $dql, ?string $search): QueryBuilder
    {
        if (mb_strlen($search) !== 0) {

            $dql->andWhere($dql->expr()->orX(
                'LOWER(grp.name) LIKE :search',
                'LOWER(grp.description) LIKE :search'
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

            case Group::JSON_PROJECT:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('grp.project IS NULL');
                }
                else {
                    $dql->andWhere('grp.project = :project');
                    $dql->setParameter('project', (int) $value);
                }

                break;

            case Group::JSON_NAME:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('grp.name IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(grp.name) LIKE LOWER(:name)');
                    $dql->setParameter('name', "%{$value}%");
                }

                break;

            case Group::JSON_DESCRIPTION:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('grp.description IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(grp.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case Group::JSON_GLOBAL:

                $dql->andWhere($value ? 'grp.project IS NULL' : 'grp.project IS NOT NULL');

                break;
        }

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     *
     * @param QueryBuilder $dql
     * @param string       $property
     * @param null|string  $direction
     *
     * @return QueryBuilder
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $map = [
            Group::JSON_ID          => 'grp.id',
            Group::JSON_PROJECT     => 'project.name',
            Group::JSON_NAME        => 'grp.name',
            Group::JSON_DESCRIPTION => 'grp.description',
            Group::JSON_GLOBAL      => 'project.id - project.id',
        ];

        if (mb_strtoupper($direction) !== GetGroupsQuery::SORT_DESC) {
            $direction = GetGroupsQuery::SORT_ASC;
        }

        return $dql->addOrderBy($map[$property], $direction);
    }
}
