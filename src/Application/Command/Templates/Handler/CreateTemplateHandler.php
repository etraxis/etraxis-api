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

use eTraxis\Application\Command\Templates\CreateTemplateCommand;
use eTraxis\Entity\Template;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
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
class CreateTemplateHandler
{
    private AuthorizationCheckerInterface $security;
    private ValidatorInterface            $validator;
    private ProjectRepositoryInterface    $projectRepository;
    private TemplateRepositoryInterface   $templateRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ProjectRepositoryInterface    $projectRepository
     * @param TemplateRepositoryInterface   $templateRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ProjectRepositoryInterface    $projectRepository,
        TemplateRepositoryInterface   $templateRepository
    )
    {
        $this->security           = $security;
        $this->validator          = $validator;
        $this->projectRepository  = $projectRepository;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Command handler.
     *
     * @param CreateTemplateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return Template
     */
    public function __invoke(CreateTemplateCommand $command): Template
    {
        /** @var null|\eTraxis\Entity\Project $project */
        $project = $this->projectRepository->find($command->project);

        if (!$project) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project)) {
            throw new AccessDeniedHttpException();
        }

        $template = new Template($project);

        $template->name        = $command->name;
        $template->prefix      = $command->prefix;
        $template->description = $command->description;
        $template->criticalAge = $command->critical;
        $template->frozenTime  = $command->frozen;
        $template->isLocked    = true;

        $errors = $this->validator->validate($template);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->templateRepository->persist($template);

        return $template;
    }
}
