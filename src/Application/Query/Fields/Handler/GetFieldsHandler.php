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

namespace eTraxis\Application\Query\Fields\Handler;

use Doctrine\ORM\QueryBuilder;
use eTraxis\Application\Query\Collection;
use eTraxis\Application\Query\Fields\GetFieldsQuery;
use eTraxis\Entity\Field;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetFieldsHandler
{
    private $security;
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param FieldRepositoryInterface      $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, FieldRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Query handler.
     *
     * @param GetFieldsQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Collection
     */
    public function __invoke(GetFieldsQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException();
        }

        $collection = new Collection();

        $dql = $this->repository->createQueryBuilder('field');

        // Include states.
        $dql->innerJoin('field.state', 'state');
        $dql->addSelect('state');

        // Include templates.
        $dql->innerJoin('state.template', 'template');
        $dql->addSelect('template');

        // Include projects.
        $dql->innerJoin('template.project', 'project');
        $dql->addSelect('project');

        // Ignore removed fields.
        $dql->where('field.removedAt IS NULL');

        // Search.
        $this->querySearch($dql, $query->search);

        // Filter.
        foreach ($query->filter as $property => $value) {
            $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $total = clone $dql;
        $total->select('COUNT(field.id)');
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
                'LOWER(field.name) LIKE :search',
                'LOWER(field.description) LIKE :search'
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

            case Field::JSON_PROJECT:

                $dql->andWhere('template.project = :project');
                $dql->setParameter('project', (int) $value);

                break;

            case Field::JSON_TEMPLATE:

                $dql->andWhere('state.template = :template');
                $dql->setParameter('template', (int) $value);

                break;

            case Field::JSON_STATE:

                $dql->andWhere('field.state= :state');
                $dql->setParameter('state', (int) $value);

                break;

            case Field::JSON_NAME:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('field.name IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(field.name) LIKE LOWER(:name)');
                    $dql->setParameter('name', "%{$value}%");
                }

                break;

            case Field::JSON_TYPE:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('field.type IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(field.type) = LOWER(:type)');
                    $dql->setParameter('type', $value);
                }

                break;

            case Field::JSON_DESCRIPTION:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('field.description IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(field.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case Field::JSON_POSITION:

                $dql->andWhere('field.position = :position');
                $dql->setParameter('position', (int) $value);

                break;

            case Field::JSON_REQUIRED:

                $dql->andWhere('field.isRequired = :required');
                $dql->setParameter('required', (bool) $value);

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
            Field::JSON_ID          => 'field.id',
            Field::JSON_PROJECT     => 'project.name',
            Field::JSON_TEMPLATE    => 'template.name',
            Field::JSON_STATE       => 'state.name',
            Field::JSON_NAME        => 'field.name',
            Field::JSON_TYPE        => 'field.type',
            Field::JSON_DESCRIPTION => 'field.description',
            Field::JSON_POSITION    => 'field.position',
            Field::JSON_REQUIRED    => 'field.isRequired',
        ];

        if (mb_strtoupper($direction) !== GetFieldsQuery::SORT_DESC) {
            $direction = GetFieldsQuery::SORT_ASC;
        }

        return $dql->addOrderBy($map[$property], $direction);
    }
}
