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
use eTraxis\Application\Hateoas;
use eTraxis\Application\Query\Users\GetUsersQuery;
use eTraxis\Entity\User;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use eTraxis\MessageBus\Contracts\QueryBusInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * API controller for '/users' resource.
 *
 * @Route("/api/users")
 * @Security("is_granted('ROLE_ADMIN')")
 *
 * @API\Tag(name="Users")
 */
class UsersController extends AbstractController
{
    /**
     * Returns list of users.
     *
     * **X-Filter model:**
     * <pre>
     * {
     *   "email": "string",
     *   "fullname": "string",
     *   "description": "string",
     *   "admin": true,
     *   "disabled": true,
     *   "locked": true,
     *   "provider": "string"
     * }
     * </pre>
     *
     * **X-Sort model:**
     * <pre>
     * {
     *   "id": "ASC",
     *   "email": "ASC",
     *   "fullname": "ASC",
     *   "description": "ASC",
     *   "admin": "ASC",
     *   "provider": "ASC"
     * }
     * </pre>
     *
     * @Route("", name="api_users_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query",  type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first user to return.")
     * @API\Parameter(name="limit",    in="query",  type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of users to return.")
     * @API\Parameter(name="X-Search", in="header", type="string",  required=false, description="Optional search value.")
     * @API\Parameter(name="X-Filter", in="header", type="string",  required=false, description="Optional filters (JSON-encoded).")
     * @API\Parameter(name="X-Sort",   in="header", type="string",  required=false, description="Optional sorting (JSON-encoded).")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned user."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned user."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found users."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Application\Swagger\User::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param Request           $request
     * @param QueryBusInterface $queryBus
     *
     * @return JsonResponse
     */
    public function listUsers(Request $request, QueryBusInterface $queryBus): JsonResponse
    {
        $query = new GetUsersQuery($request);

        $collection = $queryBus->execute($query);

        return $this->json($collection, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Creates new user.
     *
     * @Route("", name="api_users_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateUserCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=409, description="Account with specified email already exists.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function createUser(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\CreateUserCommand($request->request->all());

        /** @var User $user */
        $user = $commandBus->handle($command);

        $url = $this->generateUrl('api_users_get', [
            'id' => $user->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified user.
     *
     * @Route("/{id}", name="api_users_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Application\Swagger\User::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function retrieveUser(User $user): JsonResponse
    {
        return $this->json($user, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]);
    }

    /**
     * Updates specified user.
     *
     * @Route("/{id}", name="api_users_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateUserCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     * @API\Response(response=409, description="Account with specified email already exists.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function updateUser(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\UpdateUserCommand($request->request->all());

        $command->user = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified user.
     *
     * @Route("/{id}", name="api_users_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function deleteUser(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\DeleteUserCommand([
            'user' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Disables specified user.
     *
     * @Route("/{id}/disable", name="api_users_disable", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function disableUser(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\DisableUsersCommand([
            'users' => [$id],
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Enables specified user.
     *
     * @Route("/{id}/enable", name="api_users_enable", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function enableUser(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\EnableUsersCommand([
            'users' => [$id],
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Unlocks specified user.
     *
     * @Route("/{id}/unlock", name="api_users_unlock", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function unlockUser(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\UnlockUserCommand([
            'user' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Sets password for the specified user.
     *
     * @Route("/{id}/password", name="api_users_password", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\SetPasswordCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function setPassword(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\SetPasswordCommand($request->request->all());

        $command->user = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns groups for the specified user.
     *
     * @Route("/{id}/groups", name="api_users_groups_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Application\Swagger\Group::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function getGroups(User $user): JsonResponse
    {
        return $this->json($user->groups);
    }

    /**
     * Sets groups for the specified user.
     *
     * @Route("/{id}/groups", name="api_users_groups_set", methods={"PATCH"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     * @API\Parameter(name="",   in="body", @API\Schema(
     *     @API\Property(property="add", type="array", example={123, 456}, description="List of group IDs to add.",
     *         @API\Items(type="integer")
     *     ),
     *     @API\Property(property="remove", type="array", example={123, 456}, description="List of group IDs to remove.",
     *         @API\Items(type="integer")
     *     )
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function setGroups(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $add    = $request->request->get('add');
        $remove = $request->request->get('remove');

        $add    = is_array($add) ? $add : [];
        $remove = is_array($remove) ? $remove : [];

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->getDoctrine()->getManager();
        $manager->beginTransaction();

        $command = new Command\AddGroupsCommand([
            'user'   => $id,
            'groups' => array_diff($add, $remove),
        ]);

        if (count($command->groups)) {
            $commandBus->handle($command);
        }

        $command = new Command\RemoveGroupsCommand([
            'user'   => $id,
            'groups' => array_diff($remove, $add),
        ]);

        if (count($command->groups)) {
            $commandBus->handle($command);
        }

        $manager->commit();

        return $this->json(null);
    }

    /**
     * Disables multiple users.
     *
     * @Route("/disable", name="api_users_disable_multiple", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\DisableUsersCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function disableMultipleUsers(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\DisableUsersCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Enables multiple users.
     *
     * @Route("/enable", name="api_users_enable_multiple", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\EnableUsersCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function enableMultipleUsers(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\EnableUsersCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }
}
