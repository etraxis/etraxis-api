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

use eTraxis\Application\Query\Users\GetNewIssueProjectsQuery;
use eTraxis\Application\Query\Users\GetNewIssueTemplatesQuery;
use eTraxis\Entity\Template;
use eTraxis\MessageBus\Contracts\QueryBusInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Query handler.
 */
class GetNewIssueProjectsHandler
{
    private QueryBusInterface       $queryBus;
    private UserRepositoryInterface $userRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param QueryBusInterface       $queryBus
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(QueryBusInterface $queryBus, UserRepositoryInterface $userRepository)
    {
        $this->queryBus       = $queryBus;
        $this->userRepository = $userRepository;
    }

    /**
     * Query handler.
     *
     * @param GetNewIssueProjectsQuery $query
     *
     * @throws NotFoundHttpException
     *
     * @return \eTraxis\Entity\Project[]
     */
    public function __invoke(GetNewIssueProjectsQuery $query): array
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->userRepository->find($query->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $subQuery = new GetNewIssueTemplatesQuery([
            'user' => $query->user,
        ]);

        /** @var Template[] $templates */
        $templates = $this->queryBus->execute($subQuery);

        $projects = array_map(fn (Template $template) => $template->project, $templates);

        return array_values(array_unique($projects, SORT_REGULAR));
    }
}
