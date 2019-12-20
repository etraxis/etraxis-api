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

namespace eTraxis\Application\Command\Issues\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use eTraxis\Application\Command\Issues\RemoveDependenciesCommand;
use eTraxis\Application\Dictionary\TemplatePermission;
use eTraxis\Entity\Dependency;
use eTraxis\Entity\Issue;
use eTraxis\Entity\Template;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class RemoveDependenciesHandler
{
    private $security;
    private $tokens;
    private $issueRepository;
    private $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokens
     * @param IssueRepositoryInterface      $issueRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokens,
        IssueRepositoryInterface      $issueRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->security        = $security;
        $this->tokens          = $tokens;
        $this->issueRepository = $issueRepository;
        $this->manager         = $manager;
    }

    /**
     * Command handler.
     *
     * @param RemoveDependenciesCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(RemoveDependenciesCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to remove dependencies.');
        }

        // Find all templates which issues the user has access to.
        $query = $this->manager->createQueryBuilder();

        $query
            ->distinct()
            ->select('t')
            ->from(Template::class, 't')
            ->innerJoin('t.groupPermissionsCollection', 'tp', Join::WITH, 'tp.permission = :permission')
            ->innerJoin('tp.group', 'g')
            ->innerJoin('g.membersCollection', 'u', Join::WITH, 'u = :user');

        $templates = $query->getQuery()->execute([
            'permission' => TemplatePermission::VIEW_ISSUES,
            'user'       => $user,
        ]);

        // Filter specified issues to those the user has access to.
        $query = $this->manager->createQueryBuilder();

        $query
            ->distinct()
            ->select('i')
            ->from(Issue::class, 'i')
            ->innerJoin('i.state', 's')
            ->where($query->expr()->in('i.id', ':issues'))
            ->andWhere($query->expr()->orX(
                'i.author = :user',
                'i.responsible = :user',
                $query->expr()->in('s.template', ':templates')
            ));

        $dependencies = $query->getQuery()->execute([
            'user'      => $user,
            'templates' => $templates,
            'issues'    => $command->dependencies,
        ]);

        if (count($dependencies) !== count(array_unique($command->dependencies))) {

            $ids = array_map(function (Issue $issue) {
                return $issue->id;
            }, $dependencies);

            $diff = array_diff($command->dependencies, $ids);

            throw new NotFoundHttpException(sprintf('Unremovable dependencies - %s.', implode(',', $diff)));
        }

        // Delete specified dependencies.
        $query = $this->manager->createQueryBuilder();

        $query
            ->delete(Dependency::class, 'd')
            ->where('d.issue = :issue')
            ->andWhere($query->expr()->in('d.dependency', ':dependencies'));

        $query->getQuery()->execute([
            'issue'        => $issue,
            'dependencies' => $dependencies,
        ]);
    }
}
