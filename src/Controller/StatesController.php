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

use eTraxis\Application\Command\States as Command;
use eTraxis\Application\Hateoas;
use eTraxis\Application\Query\States\GetStatesQuery;
use eTraxis\Entity\State;
use eTraxis\Entity\StateResponsibleGroup;
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
 * API controller for '/states' resource.
 *
 * @Route("/api/states")
 * @Security("is_granted('ROLE_ADMIN')")
 *
 * @API\Tag(name="States")
 */
class StatesController extends AbstractController
{
    /**
     * Returns list of states.
     *
     * @Route("", name="api_states_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first state to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of states to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="project",     type="integer"),
     *         @API\Property(property="template",    type="integer"),
     *         @API\Property(property="name",        type="string"),
     *         @API\Property(property="type",        type="string"),
     *         @API\Property(property="responsible", type="string")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="project",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="template",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="name",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="type",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="responsible", type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned state."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned state."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found states."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Application\Swagger\State::class)
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
    public function listStates(Request $request, QueryBusInterface $queryBus): JsonResponse
    {
        $query = new GetStatesQuery($request);

        $collection = $queryBus->execute($query);

        return $this->json($collection, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Creates new state.
     *
     * @Route("", name="api_states_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateStateCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     * @API\Response(response=409, description="State with specified name already exists.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function createState(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\CreateStateCommand($request->request->all());

        /** @var State $state */
        $state = $commandBus->handle($command);

        $url = $this->generateUrl('api_states_get', [
            'id' => $state->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified state.
     *
     * @Route("/{id}", name="api_states_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Application\Swagger\State::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     *
     * @param State $state
     *
     * @return JsonResponse
     */
    public function getState(State $state): JsonResponse
    {
        return $this->json($state, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]);
    }

    /**
     * Updates specified state.
     *
     * @Route("/{id}", name="api_states_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateStateCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     * @API\Response(response=409, description="State with specified name already exists.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function updateState(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\UpdateStateCommand($request->request->all());

        $command->state = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified state.
     *
     * @Route("/{id}", name="api_states_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
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
    public function deleteState(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\DeleteStateCommand([
            'state' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Sets specified state as initial.
     *
     * @Route("/{id}/initial", name="api_states_initial", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function setInitialState(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\SetInitialStateCommand([
            'state' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns available transitions from specified state.
     *
     * @Route("/{id}/transitions", name="api_states_get_transitions", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="roles", type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Application\Swagger\StateRoleTransition::class)
     *         )),
     *         @API\Property(property="groups", type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Application\Swagger\StateGroupTransition::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     *
     * @param State $state
     *
     * @return JsonResponse
     */
    public function getTransitions(State $state): JsonResponse
    {
        return $this->json([
            'roles'  => $state->roleTransitions,
            'groups' => $state->groupTransitions,
        ]);
    }

    /**
     * Sets transitions from specified state.
     *
     * @Route("/{id}/transitions", name="api_states_set_transitions", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     * @API\Parameter(name="",   in="body", @API\Schema(
     *     type="object",
     *     required={"state"},
     *     properties={
     *         @API\Property(property="state", type="integer", example="123", description="Destination state ID."),
     *         @API\Property(property="roles",  type="array", @API\Items(type="string", enum={"anyone", "author", "responsible"}, example="author", description="System role.")),
     *         @API\Property(property="groups", type="array", @API\Items(type="integer", example=123, description="Group ID."))
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function setTransitions(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $state  = $request->get('state');
        $roles  = $request->get('roles');
        $groups = $request->get('groups');

        if ($roles !== null) {

            $command = new Command\SetRolesTransitionCommand([
                'from'  => $id,
                'to'    => $state,
                'roles' => $roles,
            ]);

            $commandBus->handle($command);
        }

        if ($groups !== null) {

            $command = new Command\SetGroupsTransitionCommand([
                'from'   => $id,
                'to'     => $state,
                'groups' => $groups,
            ]);

            $commandBus->handle($command);
        }

        return $this->json(null);
    }

    /**
     * Returns responsible groups of specified state.
     *
     * @Route("/{id}/responsibles", name="api_states_get_responsibles", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(type="array", @API\Items(type="integer", example=123, description="Group ID.")))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     *
     * @param State $state
     *
     * @return JsonResponse
     */
    public function getResponsibles(State $state): JsonResponse
    {
        $groups = array_map(function (StateResponsibleGroup $group) {
            return $group->group->id;
        }, $state->responsibleGroups);

        return $this->json($groups);
    }

    /**
     * Sets responsible groups of specified state.
     *
     * @Route("/{id}/responsibles", name="api_states_set_responsibles", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     * @API\Parameter(name="",   in="body", @API\Schema(
     *     type="object",
     *     required={"groups"},
     *     properties={
     *         @API\Property(property="groups", type="array", @API\Items(type="integer", example=123, description="Group ID."))
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function setResponsibles(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\SetResponsibleGroupsCommand($request->request->all());

        $command->state = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }
}
