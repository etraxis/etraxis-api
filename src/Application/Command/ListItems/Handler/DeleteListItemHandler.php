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

namespace eTraxis\Application\Command\ListItems\Handler;

use eTraxis\Application\Command\ListItems\DeleteListItemCommand;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\Voter\ListItemVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteListItemHandler
{
    private $security;
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ListItemRepositoryInterface   $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, ListItemRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteListItemCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteListItemCommand $command): void
    {
        /** @var null|\eTraxis\Entity\ListItem $item */
        $item = $this->repository->find($command->item);

        if ($item) {

            if (!$this->security->isGranted(ListItemVoter::DELETE_ITEM, $item)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($item);
        }
    }
}
