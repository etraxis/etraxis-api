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

namespace eTraxis\Application\Command\Templates\Handler;

use eTraxis\Application\Command\Templates\DeleteTemplateCommand;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\Voter\TemplateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteTemplateHandler
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
     * @param DeleteTemplateCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteTemplateCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Template $template */
        $template = $this->repository->find($command->template);

        if ($template) {

            if (!$this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $template)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($template);
        }
    }
}
