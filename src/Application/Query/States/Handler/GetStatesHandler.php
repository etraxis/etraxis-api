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

namespace eTraxis\Application\Query\States\Handler;

use Doctrine\ORM\QueryBuilder;
use eTraxis\Application\Query\Collection;
use eTraxis\Application\Query\States\GetStatesQuery;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetStatesHandler
{
    private AuthorizationCheckerInterface $security;
    private StateRepositoryInterface      $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param StateRepositoryInterface      $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, StateRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Query handler.
     *
     * @param GetStatesQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Collection
     */
    public function __invoke(GetStatesQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException();
        }

        $collection = new Collection();

        $dql = $this->repository->createQueryBuilder('state');

        // Include templates.
        $dql->innerJoin('state.template', 'template');
        $dql->addSelect('template');

        // Include projects.
        $dql->innerJoin('template.project', 'project');
        $dql->addSelect('project');

        // Search.
        $this->querySearch($dql, $query->search);

        // Filter.
        foreach ($query->filter as $property => $value) {
            $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $total = clone $dql;
        $total->select('COUNT(state.id)');
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
                'LOWER(state.name) LIKE :search'
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

            case State::JSON_PROJECT:

                $dql->andWhere('template.project = :project');
                $dql->setParameter('project', (int) $value);

                break;

            case State::JSON_TEMPLATE:

                $dql->andWhere('state.template = :template');
                $dql->setParameter('template', (int) $value);

                break;

            case State::JSON_NAME:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('state.name IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(state.name) LIKE LOWER(:name)');
                    $dql->setParameter('name', "%{$value}%");
                }

                break;

            case State::JSON_TYPE:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('state.type IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(state.type) = LOWER(:type)');
                    $dql->setParameter('type', $value);
                }

                break;

            case State::JSON_RESPONSIBLE:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('state.responsible IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(state.responsible) = LOWER(:responsible)');
                    $dql->setParameter('responsible', $value);
                }

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
            State::JSON_ID          => 'state.id',
            State::JSON_PROJECT     => 'project.name',
            State::JSON_TEMPLATE    => 'template.name',
            State::JSON_NAME        => 'state.name',
            State::JSON_TYPE        => 'state.type',
            State::JSON_RESPONSIBLE => 'state.responsible',
        ];

        if (mb_strtoupper($direction) !== GetStatesQuery::SORT_DESC) {
            $direction = GetStatesQuery::SORT_ASC;
        }

        return $dql->addOrderBy($map[$property], $direction);
    }
}
