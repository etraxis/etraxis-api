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

namespace eTraxis\Application\Query\Users\Handler;

use Doctrine\ORM\Query\Expr\Join;
use eTraxis\Application\Dictionary\SystemRole;
use eTraxis\Application\Dictionary\TemplatePermission;
use eTraxis\Application\Query\Users\GetNewIssueTemplatesQuery;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Query handler.
 */
class GetNewIssueTemplatesHandler
{
    private TemplateRepositoryInterface $templateRepository;
    private UserRepositoryInterface     $userRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TemplateRepositoryInterface $templateRepository
     * @param UserRepositoryInterface     $userRepository
     */
    public function __construct(TemplateRepositoryInterface $templateRepository, UserRepositoryInterface $userRepository)
    {
        $this->templateRepository = $templateRepository;
        $this->userRepository     = $userRepository;
    }

    /**
     * Query handler.
     *
     * @param GetNewIssueTemplatesQuery $query
     *
     * @throws NotFoundHttpException
     *
     * @return \eTraxis\Entity\Template[]
     */
    public function __invoke(GetNewIssueTemplatesQuery $query): array
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->userRepository->find($query->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $dql = $this->templateRepository->createQueryBuilder('template');

        $dql
            ->distinct()
            ->innerJoin('template.project', 'project', Join::WITH, 'project.isSuspended = :suspended')
            ->addSelect('project')
            ->leftJoin('template.rolePermissionsCollection', 'trp', Join::WITH, 'trp.permission = :permission')
            ->leftJoin('template.groupPermissionsCollection', 'tgp', Join::WITH, 'tgp.permission = :permission')
            ->where('template.isLocked = :locked')
            ->andWhere($dql->expr()->orX(
                'trp.role = :role',
                $dql->expr()->in('tgp.group', ':groups')
            ))
            ->orderBy('project.name')
            ->addOrderBy('template.name')
            ->setParameters([
                'suspended'  => false,
                'locked'     => false,
                'permission' => TemplatePermission::CREATE_ISSUES,
                'role'       => SystemRole::ANYONE,
                'groups'     => $user->groups,
            ]);

        return $dql->getQuery()->getResult();
    }
}
