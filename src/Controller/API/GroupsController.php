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

namespace eTraxis\Controller\API;

use eTraxis\Application\Command\Groups as Command;
use eTraxis\Application\Hateoas;
use eTraxis\Application\Query\Groups\GetGroupsQuery;
use eTraxis\Entity\Group;
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
 * API controller for '/groups' resource.
 *
 * @Route("/api/groups")
 * @Security("is_granted('ROLE_ADMIN')")
 *
 * @API\Tag(name="Groups")
 */
class GroupsController extends AbstractController
{
    /**
     * Returns list of groups.
     *
     * **X-Filter model:**
     * <pre>
     * {
     *   "project": 0,
     *   "name": "string",
     *   "description": "string",
     *   "global": true
     * }
     * </pre>
     *
     * **X-Sort model:**
     * <pre>
     * {
     *   "id": "ASC",
     *   "project": "ASC",
     *   "name": "ASC",
     *   "description": "ASC",
     *   "global": "ASC"
     * }
     * </pre>
     *
     * @Route("", name="api_groups_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query",  type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first group to return.")
     * @API\Parameter(name="limit",    in="query",  type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of groups to return.")
     * @API\Parameter(name="X-Search", in="header", type="string",  required=false, description="Optional search value.")
     * @API\Parameter(name="X-Filter", in="header", type="string",  required=false, description="Optional filters (JSON-encoded).")
     * @API\Parameter(name="X-Sort",   in="header", type="string",  required=false, description="Optional sorting (JSON-encoded).")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned group."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned group."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found groups."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Application\Swagger\Group::class)
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
    public function listGroups(Request $request, QueryBusInterface $queryBus): JsonResponse
    {
        $query = new GetGroupsQuery($request);

        $collection = $queryBus->execute($query);

        return $this->json($collection, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Creates new group.
     *
     * @Route("", name="api_groups_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateGroupCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Project is not found.")
     * @API\Response(response=409, description="Group with specified name already exists.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function createGroup(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\CreateGroupCommand($request->request->all());

        /** @var Group $group */
        $group = $commandBus->handle($command);

        $url = $this->generateUrl('api_groups_get', [
            'id' => $group->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified group.
     *
     * @Route("/{id}", name="api_groups_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Group ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Application\Swagger\Group::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Group is not found.")
     *
     * @param Group $group
     *
     * @return JsonResponse
     */
    public function getGroup(Group $group): JsonResponse
    {
        return $this->json($group, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]);
    }

    /**
     * Updates specified group.
     *
     * @Route("/{id}", name="api_groups_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Group ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateGroupCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Group is not found.")
     * @API\Response(response=409, description="Group with specified name already exists.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function updateGroup(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\UpdateGroupCommand($request->request->all());

        $command->group = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified group.
     *
     * @Route("/{id}", name="api_groups_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Group ID.")
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
    public function deleteGroup(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\DeleteGroupCommand([
            'group' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns members for the specified group.
     *
     * @Route("/{id}/members", name="api_groups_members_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Group ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Application\Swagger\User::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Group is not found.")
     *
     * @param Group $group
     *
     * @return JsonResponse
     */
    public function getMembers(Group $group): JsonResponse
    {
        return $this->json($group->members);
    }

    /**
     * Sets members for the specified group.
     *
     * @Route("/{id}/members", name="api_groups_members_set", methods={"PATCH"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Group ID.")
     * @API\Parameter(name="",   in="body", @API\Schema(
     *     @API\Property(property="add", type="array", example={123, 456}, description="List of user IDs to add.",
     *         @API\Items(type="integer")
     *     ),
     *     @API\Property(property="remove", type="array", example={123, 456}, description="List of user IDs to remove.",
     *         @API\Items(type="integer")
     *     )
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Group is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function setMembers(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $add    = $request->request->get('add');
        $remove = $request->request->get('remove');

        $add    = is_array($add) ? $add : [];
        $remove = is_array($remove) ? $remove : [];

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->getDoctrine()->getManager();
        $manager->beginTransaction();

        $command = new Command\AddMembersCommand([
            'group' => $id,
            'users' => array_diff($add, $remove),
        ]);

        if (count($command->users)) {
            $commandBus->handle($command);
        }

        $command = new Command\RemoveMembersCommand([
            'group' => $id,
            'users' => array_diff($remove, $add),
        ]);

        if (count($command->users)) {
            $commandBus->handle($command);
        }

        $manager->commit();

        return $this->json(null);
    }
}
