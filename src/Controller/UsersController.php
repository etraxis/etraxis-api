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

use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Application\Dictionary\Locale;
use eTraxis\Application\Dictionary\Timezone;
use eTraxis\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Users controller.
 *
 * @Route("/admin/users")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class UsersController extends AbstractController
{
    /**
     * 'Users' page.
     *
     * @Route("", name="admin_users", methods={"GET"})
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('users/index.html.twig');
    }

    /**
     * A user page.
     *
     * @Route("/{id}", name="admin_view_user", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param User $entity
     *
     * @return Response
     */
    public function view(User $entity): Response
    {
        return $this->render('users/view.html.twig', [
            'user'      => $entity,
            'providers' => AccountProvider::all(),
            'locales'   => Locale::all(),
            'timezones' => Timezone::all(),
        ]);
    }
}
