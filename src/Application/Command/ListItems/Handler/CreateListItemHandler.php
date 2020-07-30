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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\ListItems\CreateListItemCommand;
use eTraxis\Entity\ListItem;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\Voter\ListItemVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateListItemHandler
{
    private AuthorizationCheckerInterface $security;
    private ValidatorInterface            $validator;
    private FieldRepositoryInterface      $fieldRepository;
    private ListItemRepositoryInterface   $itemRepository;
    private EntityManagerInterface        $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param FieldRepositoryInterface      $fieldRepository
     * @param ListItemRepositoryInterface   $itemRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        FieldRepositoryInterface      $fieldRepository,
        ListItemRepositoryInterface   $itemRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->security        = $security;
        $this->validator       = $validator;
        $this->fieldRepository = $fieldRepository;
        $this->itemRepository  = $itemRepository;
        $this->manager         = $manager;
    }

    /**
     * Command handler.
     *
     * @param CreateListItemCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return ListItem
     */
    public function __invoke(CreateListItemCommand $command): ListItem
    {
        /** @var null|\eTraxis\Entity\Field $field */
        $field = $this->fieldRepository->find($command->field);

        if (!$field) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ListItemVoter::CREATE_ITEM, $field)) {
            throw new AccessDeniedHttpException();
        }

        $item = new ListItem($field);

        $item->value = $command->value;
        $item->text  = $command->text;

        $errors = $this->validator->validate($item);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->itemRepository->persist($item);

        return $item;
    }
}
