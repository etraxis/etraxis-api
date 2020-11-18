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

namespace eTraxis\Controller;

use eTraxis\Application\Command\Users\ForgetPasswordCommand;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Forgot password controller.
 *
 * @Route("/forgot")
 */
class ForgotPasswordController extends AbstractController
{
    /**
     * 'Forgot password' page.
     *
     * @Route(name="forgot_password", methods={"GET"})
     *
     * @return Response
     */
    public function index(): Response
    {
        if (!$this->isGranted('IS_ANONYMOUS')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('security/forgot/index.html.twig');
    }

    /**
     * Generates a reset token for forgotten password.
     *
     * @Route(methods={"POST"})
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function forgotPassword(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        if ($this->isGranted('IS_ANONYMOUS')) {

            $command = new ForgetPasswordCommand($request->request->all());

            $commandBus->handle($command);
        }

        return $this->json(null);
    }
}
