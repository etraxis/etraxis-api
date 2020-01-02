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

use eTraxis\Application\Command\Users as Command;
use eTraxis\Entity\User;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * API controller for '/my' resource.
 *
 * @Route("/api/my")
 * @Security("is_granted('ROLE_USER')")
 *
 * @API\Tag(name="My Account")
 */
class MyController extends AbstractController
{
    /**
     * Returns profile of the current user.
     *
     * @Route("/profile", name="api_profile_get", methods={"GET"})
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Application\Swagger\Profile::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            User::JSON_ID       => $user->id,
            User::JSON_EMAIL    => $user->email,
            User::JSON_FULLNAME => $user->fullname,
            User::JSON_PROVIDER => $user->account->provider,
            User::JSON_LOCALE   => $user->locale,
            User::JSON_THEME    => $user->theme,
            User::JSON_TIMEZONE => $user->timezone,
        ]);
    }

    /**
     * Updates profile of the current user.
     *
     * @Route("/profile", name="api_profile_update", methods={"PATCH"})
     *
     * @API\Parameter(name="", in="body", @API\Schema(
     *     type="object",
     *     required={},
     *     properties={
     *         @API\Property(property="email",    type="string", maxLength=254, description="Email address (RFC 5322). Ignored for external accounts."),
     *         @API\Property(property="fullname", type="string", maxLength=50, description="Full name. Ignored for external accounts."),
     *         @API\Property(property="locale",   type="string", example="en_NZ", description="Locale (ISO 639-1 / ISO 3166-1)."),
     *         @API\Property(property="theme",    type="string", enum={"azure", "emerald", "humanity", "mars"}, example="azure", description="Theme."),
     *         @API\Property(property="timezone", type="string", example="Pacific/Auckland", description="Timezone (IANA database value).")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=409, description="Account with specified email already exists.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function updateProfile(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $profile = new Command\UpdateProfileCommand([
            'email'    => $request->request->get('email', $user->email),
            'fullname' => $request->request->get('fullname', $user->fullname),
        ]);

        $settings = new Command\UpdateSettingsCommand([
            'locale'   => $request->request->get('locale', $user->locale),
            'theme'    => $request->request->get('theme', $user->theme),
            'timezone' => $request->request->get('timezone', $user->timezone),
        ]);

        if (!$user->isAccountExternal()) {
            $commandBus->handle($profile);
        }

        $commandBus->handle($settings);

        return $this->json(null);
    }

    /**
     * Sets new password for the current user.
     *
     * @Route("/password", name="api_password_set", methods={"PUT"})
     *
     * @API\Parameter(name="", in="body", @API\Schema(
     *     type="object",
     *     required={"current", "new"},
     *     properties={
     *         @API\Property(property="current", type="string", maxLength=4096, description="Current password."),
     *         @API\Property(property="new",     type="string", maxLength=4096, description="New password.")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="Wrong current password, or The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Password cannot be set for external accounts.")
     *
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $encoder
     * @param TranslatorInterface          $translator
     * @param CommandBusInterface          $commandBus
     *
     * @return JsonResponse
     */
    public function setPassword(Request $request, UserPasswordEncoderInterface $encoder, TranslatorInterface $translator, CommandBusInterface $commandBus): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isAccountExternal()) {
            throw new AccessDeniedHttpException('Password cannot be set for external accounts.');
        }

        if (!$encoder->isPasswordValid($user, $request->request->get('current'))) {
            throw new BadRequestHttpException($translator->trans('Bad credentials.'));
        }

        $command = new Command\SetPasswordCommand([
            'user'     => $user->id,
            'password' => $request->request->get('new'),
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }
}
