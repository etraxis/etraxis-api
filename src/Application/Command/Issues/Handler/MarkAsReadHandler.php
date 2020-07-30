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
use eTraxis\Application\Command\Issues\MarkAsReadCommand;
use eTraxis\Application\Dictionary\TemplatePermission;
use eTraxis\Entity\Issue;
use eTraxis\Entity\LastRead;
use eTraxis\Entity\Template;
use eTraxis\Repository\Contracts\LastReadRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
class MarkAsReadHandler
{
    private TokenStorageInterface       $tokenStorage;
    private LastReadRepositoryInterface $repository;
    private EntityManagerInterface      $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TokenStorageInterface       $tokenStorage
     * @param LastReadRepositoryInterface $repository
     * @param EntityManagerInterface      $manager
     */
    public function __construct(
        TokenStorageInterface       $tokenStorage,
        LastReadRepositoryInterface $repository,
        EntityManagerInterface      $manager
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->repository   = $repository;
        $this->manager      = $manager;
    }

    /**
     * Command handler.
     *
     * @param MarkAsReadCommand $command
     */
    public function __invoke(MarkAsReadCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

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

        $issues = $query->getQuery()->execute([
            'user'      => $user,
            'templates' => $templates,
            'issues'    => $command->issues,
        ]);

        // Delete existing reads of resulted issues.
        $query = $this->manager->createQueryBuilder();

        $query
            ->delete(LastRead::class, 'r')
            ->where('r.user = :user')
            ->andWhere($query->expr()->in('r.issue', ':issues'));

        $query->getQuery()->execute([
            'user'   => $user,
            'issues' => $issues,
        ]);

        // Mark resulted issues as read.
        foreach ($issues as $issue) {
            $read = new LastRead($issue, $user);
            $this->repository->persist($read);
        }
    }
}
