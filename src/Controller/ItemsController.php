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

use eTraxis\Application\Command\ListItems as Command;
use eTraxis\Application\Hateoas;
use eTraxis\Entity\ListItem;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for '/items' resource.
 *
 * @Route("/api/items")
 * @Security("is_granted('ROLE_ADMIN')")
 *
 * @API\Tag(name="List Items")
 */
class ItemsController extends AbstractController
{
    /**
     * Returns specified list item.
     *
     * @Route("/{id}", name="api_items_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Item ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Application\Swagger\ListItemEx::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Item is not found.")
     *
     * @param ListItem $item
     *
     * @return JsonResponse
     */
    public function getItem(ListItem $item): JsonResponse
    {
        return $this->json($item, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]);
    }

    /**
     * Updates specified list item.
     *
     * @Route("/{id}", name="api_items_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Item ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateListItemCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Item is not found.")
     * @API\Response(response=409, description="Item with specified value or text already exists.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function updateItem(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\UpdateListItemCommand($request->request->all());

        $command->item = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified list item.
     *
     * @Route("/{id}", name="api_items_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Item ID.")
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
    public function deleteItem(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\DeleteListItemCommand([
            'item' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }
}
