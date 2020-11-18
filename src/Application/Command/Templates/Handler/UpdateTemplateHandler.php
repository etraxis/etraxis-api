<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Application\Command\Templates\Handler;

use eTraxis\Application\Command\Templates\UpdateTemplateCommand;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\Voter\TemplateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateTemplateHandler
{
    private AuthorizationCheckerInterface $security;
    private ValidatorInterface            $validator;
    private TemplateRepositoryInterface   $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TemplateRepositoryInterface   $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TemplateRepositoryInterface   $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateTemplateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UpdateTemplateCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Template $template */
        $template = $this->repository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template)) {
            throw new AccessDeniedHttpException();
        }

        $template->name        = $command->name;
        $template->prefix      = $command->prefix;
        $template->description = $command->description;
        $template->criticalAge = $command->critical;
        $template->frozenTime  = $command->frozen;

        $errors = $this->validator->validate($template);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($template);
    }
}
