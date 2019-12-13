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

namespace eTraxis\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Login controller.
 */
class LoginController extends AbstractController
{
    /**
     * Login page.
     *
     * @Route("/login", name="login")
     *
     * @param AuthenticationUtils $utils
     *
     * @return Response
     */
    public function index(AuthenticationUtils $utils): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('security/login/index.html.twig', [
            'error'        => $utils->getLastAuthenticationError(),
            'username'     => $utils->getLastUsername(),
            'hasGoogle'    => (bool) $this->getParameter('oauth.google.client.id'),
            'hasGitHub'    => (bool) $this->getParameter('oauth.github.client.id'),
            'hasBitbucket' => (bool) $this->getParameter('oauth.bitbucket.client.id'),
        ]);
    }
}
