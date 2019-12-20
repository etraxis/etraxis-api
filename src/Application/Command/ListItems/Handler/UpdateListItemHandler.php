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

use eTraxis\Application\Command\ListItems\UpdateListItemCommand;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\Voter\ListItemVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateListItemHandler
{
    private $security;
    private $validator;
    private $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ListItemRepositoryInterface   $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ListItemRepositoryInterface   $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateListItemCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UpdateListItemCommand $command): void
    {
        /** @var null|\eTraxis\Entity\ListItem $item */
        $item = $this->repository->find($command->item);

        if (!$item) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ListItemVoter::UPDATE_ITEM, $item)) {
            throw new AccessDeniedHttpException();
        }

        $item->value = $command->value;
        $item->text  = $command->text;

        $errors = $this->validator->validate($item);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($item);
    }
}
