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
use Doctrine\ORM\QueryBuilder;
use eTraxis\Application\Query\Issues\GetIssuesQuery;
use eTraxis\Entity\Comment;
use eTraxis\Entity\Dependency;
use eTraxis\Entity\Issue;

/**
 * Abstract query handler.
 */
abstract class AbstractIssuesHandler
{
    protected EntityManagerInterface $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Alters query in accordance with the specified search.
     *
     * @param QueryBuilder $dql
     * @param string       $search
     *
     * @return QueryBuilder
     */
    protected function querySearch(QueryBuilder $dql, ?string $search): QueryBuilder
    {
        if (mb_strlen($search) !== 0) {

            // Search in comments.
            $comments = $this->manager->createQueryBuilder()
                ->select('issue.id')
                ->from(Comment::class, 'comment')
                ->innerJoin('comment.event', 'event')
                ->innerJoin('event.issue', 'issue')
                ->where('LOWER(comment.body) LIKE LOWER(:search)')
                ->andWhere('comment.isPrivate = :isPrivate')
                ->setParameters([
                    'search'    => "%{$search}%",
                    'isPrivate' => false,
                ]);

            $issues = array_map(fn ($entry) => $entry['id'], $comments->getQuery()->execute());

            $dql->andWhere($dql->expr()->orX(
                'LOWER(issue.subject) LIKE :search',
                $dql->expr()->in('issue', ':comments')
            ));

            $dql->setParameter('search', mb_strtolower("%{$search}%"));
            $dql->setParameter('comments', $issues);
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
    protected function queryFilter(QueryBuilder $dql, string $property, $value = null): QueryBuilder
    {
        switch ($property) {

            case Issue::JSON_ID:

                if (mb_strlen($value) !== 0) {
                    // Issues human-readable ID.
                    $dql->andWhere('LOWER(CONCAT(template.prefix, \'-\', LPAD(CONCAT(\'\', issue.id), GREATEST(3, LENGTH(CONCAT(\'\', issue.id))), \'0\'))) LIKE LOWER(:full_id)');
                    $dql->setParameter('full_id', "%{$value}%");
                }

                break;

            case Issue::JSON_SUBJECT:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(issue.subject) LIKE LOWER(:subject)');
                    $dql->setParameter('subject', "%{$value}%");
                }

                break;

            case Issue::JSON_AUTHOR:

                $dql->andWhere('issue.author = :author');
                $dql->setParameter('author', (int) $value);

                break;

            case Issue::JSON_AUTHOR_NAME:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(author.fullname) LIKE LOWER(:author_name)');
                    $dql->setParameter('author_name', "%{$value}%");
                }

                break;

            case Issue::JSON_PROJECT:

                $dql->andWhere('template.project = :project');
                $dql->setParameter('project', (int) $value);

                break;

            case Issue::JSON_PROJECT_NAME:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(project.name) LIKE LOWER(:project_name)');
                    $dql->setParameter('project_name', "%{$value}%");
                }

                break;

            case Issue::JSON_TEMPLATE:

                $dql->andWhere('state.template = :template');
                $dql->setParameter('template', (int) $value);

                break;

            case Issue::JSON_TEMPLATE_NAME:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(template.name) LIKE LOWER(:template_name)');
                    $dql->setParameter('template_name', "%{$value}%");
                }

                break;

            case Issue::JSON_STATE:

                $dql->andWhere('issue.state = :state');
                $dql->setParameter('state', (int) $value);

                break;

            case Issue::JSON_STATE_NAME:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(state.name) LIKE LOWER(:state_name)');
                    $dql->setParameter('state_name', "%{$value}%");
                }

                break;

            case Issue::JSON_RESPONSIBLE:

                if (mb_strlen($value) === 0) {
                    $dql->andWhere('issue.responsible IS NULL');
                }
                else {
                    $dql->andWhere('issue.responsible = :responsible');
                    $dql->setParameter('responsible', (int) $value);
                }

                break;

            case Issue::JSON_RESPONSIBLE_NAME:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('LOWER(responsible.fullname) LIKE LOWER(:responsible_name)');
                    $dql->setParameter('responsible_name', "%{$value}%");
                }

                break;

            case Issue::JSON_IS_CLONED:

                $dql->andWhere($value ? 'issue.origin IS NOT NULL' : 'issue.origin IS NULL');

                break;

            case Issue::JSON_AGE:

                if (mb_strlen($value) !== 0) {
                    $dql->andWhere('CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400) = :age');
                    $dql->setParameter('age', (int) $value);
                    $dql->setParameter('now', time());
                }

                break;

            case Issue::JSON_IS_CRITICAL:

                if ($value) {
                    $expr = $dql->expr()->andX(
                        'template.criticalAge IS NOT NULL',
                        'issue.closedAt IS NULL',
                        'template.criticalAge < CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400)'
                    );
                }
                else {
                    $expr = $dql->expr()->orX(
                        'template.criticalAge IS NULL',
                        'issue.closedAt IS NOT NULL',
                        'template.criticalAge >= CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400)'
                    );
                }

                $dql->andWhere($expr);
                $dql->setParameter('now', time());

                break;

            case Issue::JSON_IS_SUSPENDED:

                if ($value) {
                    $expr = $dql->expr()->andX(
                        'issue.resumesAt IS NOT NULL',
                        'issue.resumesAt > :now'
                    );
                }
                else {
                    $expr = $dql->expr()->orX(
                        'issue.resumesAt IS NULL',
                        'issue.resumesAt <= :now'
                    );
                }

                $dql->andWhere($expr);
                $dql->setParameter('now', time());

                break;

            case Issue::JSON_IS_CLOSED:

                $dql->andWhere($value ? 'issue.closedAt IS NOT NULL' : 'issue.closedAt IS NULL');

                break;

            case Issue::JSON_DEPENDENCY:

                $dependencies = $this->manager->createQueryBuilder()
                    ->select('dependency')
                    ->from(Dependency::class, 'dependency')
                    ->where('dependency.issue = :issue')
                    ->setParameter('issue', (int) $value);

                $issues = array_map(fn (Dependency $entry) => $entry->dependency->id, $dependencies->getQuery()->execute());

                $dql->andWhere($dql->expr()->in('issue', ':dependencies'));
                $dql->setParameter('dependencies', $issues);

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
    protected function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $map = [
            Issue::JSON_ID          => 'issue.id',
            Issue::JSON_SUBJECT     => 'issue.subject',
            Issue::JSON_CREATED_AT  => 'issue.createdAt',
            Issue::JSON_CHANGED_AT  => 'issue.changedAt',
            Issue::JSON_CLOSED_AT   => 'issue.closedAt',
            Issue::JSON_AUTHOR      => 'author.fullname',
            Issue::JSON_PROJECT     => 'project.name',
            Issue::JSON_TEMPLATE    => 'template.name',
            Issue::JSON_STATE       => 'state.name',
            Issue::JSON_RESPONSIBLE => 'responsible.fullname',
            Issue::JSON_AGE         => 'age',
        ];

        if (mb_strtoupper($direction) !== GetIssuesQuery::SORT_DESC) {
            $direction = GetIssuesQuery::SORT_ASC;
        }

        return $dql->addOrderBy($map[$property], $direction);
    }
}
