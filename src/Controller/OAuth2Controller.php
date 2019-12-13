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

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * OAuth2 controller.
 *
 * @Route("/oauth")
 */
class OAuth2Controller extends AbstractController
{
    /**
     * OAuth2 callback URL for Google.
     *
     * @Route("/google", name="oauth_google")
     *
     * @param ClientRegistry $clientRegistry
     *
     * @return Response
     */
    public function google(ClientRegistry $clientRegistry): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $clientRegistry->getClient('google')->redirect([], []);
    }

    /**
     * OAuth2 callback URL for GitHub.
     *
     * @Route("/github", name="oauth_github")
     *
     * @param ClientRegistry $clientRegistry
     *
     * @return Response
     */
    public function github(ClientRegistry $clientRegistry): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $clientRegistry->getClient('github')->redirect(['user:email'], []);
    }

    /**
     * OAuth2 callback URL for Bitbucket.
     *
     * @Route("/bitbucket", name="oauth_bitbucket")
     *
     * @param ClientRegistry $clientRegistry
     *
     * @return Response
     */
    public function bitbucket(ClientRegistry $clientRegistry): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $clientRegistry->getClient('bitbucket')->redirect([], []);
    }
}
