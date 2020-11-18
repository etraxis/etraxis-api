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

use eTraxis\Application\Command\Templates\UnlockTemplateCommand;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\Voter\TemplateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class UnlockTemplateHandler
{
    private AuthorizationCheckerInterface $security;
    private TemplateRepositoryInterface   $repository;

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
     * Command handler.
     *
     * @param UnlockTemplateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UnlockTemplateCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Template $template */
        $template = $this->repository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $template)) {
            throw new AccessDeniedHttpException();
        }

        if ($template->isLocked) {

            $template->isLocked = false;

            $this->repository->persist($template);
        }
    }
}
