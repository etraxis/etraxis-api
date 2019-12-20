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

namespace eTraxis\Application\Command\States\Handler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\States\CreateStateCommand;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\Voter\StateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateStateHandler
{
    private $security;
    private $validator;
    private $templateRepository;
    private $stateRepository;
    private $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TemplateRepositoryInterface   $templateRepository
     * @param StateRepositoryInterface      $stateRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TemplateRepositoryInterface   $templateRepository,
        StateRepositoryInterface      $stateRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->security           = $security;
        $this->validator          = $validator;
        $this->templateRepository = $templateRepository;
        $this->stateRepository    = $stateRepository;
        $this->manager            = $manager;
    }

    /**
     * Command handler.
     *
     * @param CreateStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return State
     */
    public function __invoke(CreateStateCommand $command): State
    {
        /** @var null|\eTraxis\Entity\Template $template */
        $template = $this->templateRepository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::CREATE_STATE, $template)) {
            throw new AccessDeniedHttpException();
        }

        $state = new State($template, $command->type);

        $state->name        = $command->name;
        $state->responsible = $command->responsible;

        if ($command->next) {

            /** @var null|State $nextState */
            $nextState = $this->stateRepository->find($command->next);

            if (!$nextState || $nextState->template !== $template) {
                throw new NotFoundHttpException('Unknown next state.');
            }

            $state->nextState = $nextState;
        }

        $errors = $this->validator->validate($state);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        // Only one initial state is allowed per template.
        if ($command->type === StateType::INITIAL) {

            $query = $this->manager->createQuery('
                UPDATE eTraxis:State state
                SET state.type = :interim
                WHERE state.template = :template AND state.type = :initial
            ');

            $query->execute([
                'template' => $template,
                'initial'  => StateType::INITIAL,
                'interim'  => StateType::INTERMEDIATE,
            ]);
        }

        $this->stateRepository->persist($state);

        return $state;
    }
}
