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

use eTraxis\Application\Command\Users\ResetPasswordCommand;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Reset password controller.
 *
 * @Route("/reset/{token}")
 */
class ResetPasswordController extends AbstractController
{
    /**
     * 'Reset password' page.
     *
     * @Route(name="reset_password", methods={"GET"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function index(string $token): Response
    {
        if (!$this->isGranted('IS_ANONYMOUS')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('security/reset/index.html.twig', [
            'token' => $token,
        ]);
    }

    /**
     * Resets a forgotten password by specified token.
     *
     * @Route(methods={"POST"})
     *
     * @param Request             $request
     * @param string              $token
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function resetPassword(Request $request, string $token, CommandBusInterface $commandBus): JsonResponse
    {
        if ($this->isGranted('IS_ANONYMOUS')) {

            $command = new ResetPasswordCommand($request->request->all());

            $command->token = $token;

            try {
                $commandBus->handle($command);
            }
            catch (NotFoundHttpException $exception) {
                return $this->json(null);
            }
        }

        return $this->json(null);
    }
}
