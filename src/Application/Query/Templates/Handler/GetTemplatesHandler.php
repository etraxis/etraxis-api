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

namespace eTraxis\Application\Query\Templates\Handler;

use Doctrine\ORM\QueryBuilder;
use eTraxis\Application\Query\Collection;
use eTraxis\Application\Query\Templates\GetTemplatesQuery;
use eTraxis\Entity\Template;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
class GetTemplatesHandler
{
    private $security;
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TemplateRepositoryInterface   $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, TemplateRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Query handler.
     *
     * @param GetTemplatesQuery $query
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Collection
     */
    public function __invoke(GetTemplatesQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException();
        }

        $collection = new Collection();

        $dql = $this->repository->createQueryBuilder('template');

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
        $total->select('COUNT(template.id)');
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

            $dql->where($dql->expr()->orX(
                'LOWER(template.name) LIKE :search',
                'LOWER(template.prefix) LIKE :search',
                'LOWER(template.description) LIKE :search'
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

            case Template::JSON_PROJECT:

                $dql->andWhere('template.project = :project');
                $dql->setParameter('project', (int) $value);

                break;

            case Template::JSON_NAME:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('template.name IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(template.name) LIKE LOWER(:name)');
                    $dql->setParameter('name', "%{$value}%");
                }

                break;

            case Template::JSON_PREFIX:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('template.prefix IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(template.prefix) LIKE LOWER(:prefix)');
                    $dql->setParameter('prefix', "%{$value}%");
                }

                break;

            case Template::JSON_DESCRIPTION:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('template.description IS NULL');
                }
                else {
                    $dql->andWhere('LOWER(template.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case Template::JSON_CRITICAL:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('template.criticalAge IS NULL');
                }
                else {
                    $dql->andWhere('template.criticalAge = :criticalAge');
                    $dql->setParameter('criticalAge', (int) $value);
                }

                break;

            case Template::JSON_FROZEN:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('template.frozenTime IS NULL');
                }
                else {
                    $dql->andWhere('template.frozenTime = :frozenTime');
                    $dql->setParameter('frozenTime', (int) $value);
                }

                break;

            case Template::JSON_LOCKED:

                $dql->andWhere('template.isLocked = :locked');
                $dql->setParameter('locked', (bool) $value);

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
            Template::JSON_ID          => 'template.id',
            Template::JSON_PROJECT     => 'project.name',
            Template::JSON_NAME        => 'template.name',
            Template::JSON_PREFIX      => 'template.prefix',
            Template::JSON_DESCRIPTION => 'template.description',
            Template::JSON_CRITICAL    => 'template.criticalAge',
            Template::JSON_FROZEN      => 'template.frozenTime',
            Template::JSON_LOCKED      => 'template.isLocked',
        ];

        if (mb_strtoupper($direction) !== GetTemplatesQuery::SORT_DESC) {
            $direction = GetTemplatesQuery::SORT_ASC;
        }

        return $dql->addOrderBy($map[$property], $direction);
    }
}
