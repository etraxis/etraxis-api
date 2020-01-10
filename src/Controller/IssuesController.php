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

use eTraxis\Application\Command\Issues as Command;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Hateoas;
use eTraxis\Application\Query\Issues as Query;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;
use eTraxis\MessageBus\Contracts\CommandBusInterface;
use eTraxis\MessageBus\Contracts\QueryBusInterface;
use eTraxis\Repository\Contracts\DecimalValueRepositoryInterface;
use eTraxis\Repository\Contracts\FileRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\LastReadRepositoryInterface;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Repository\Contracts\StringValueRepositoryInterface;
use eTraxis\Repository\Contracts\TextValueRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * API controller for '/issues' resource.
 *
 * @Route("/api/issues")
 * @Security("is_granted('ROLE_USER')")
 *
 * @API\Tag(name="Issues")
 */
class IssuesController extends AbstractController
{
    /**
     * Returns list of issues.
     *
     * **X-Filter model:**
     * <pre>
     * {
     *   "id": "string",
     *   "subject": "string",
     *   "author": 0,
     *   "author_name": "string",
     *   "project": 0,
     *   "project_name": "string",
     *   "template": 0,
     *   "template_name": "string",
     *   "state": 0,
     *   "state_name": "string",
     *   "responsible": 0,
     *   "responsible_name": "string",
     *   "is_cloned": true,
     *   "age": 0,
     *   "is_critical": true,
     *   "is_suspended": true,
     *   "is_closed": true
     * }
     * </pre>
     *
     * **X-Sort model:**
     * <pre>
     * {
     *   "id": "ASC",
     *   "subject": "ASC",
     *   "created_at": "ASC",
     *   "changed_at": "ASC",
     *   "closed_at": "ASC",
     *   "author": "ASC",
     *   "project": "ASC",
     *   "template": "ASC",
     *   "state": "ASC",
     *   "responsible": "ASC",
     *   "age": "ASC"
     * }
     * </pre>
     *
     * @Route("", name="api_issues_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query",  type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first issue to return.")
     * @API\Parameter(name="limit",    in="query",  type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of issues to return.")
     * @API\Parameter(name="X-Search", in="header", type="string",  required=false, description="Optional search value.")
     * @API\Parameter(name="X-Filter", in="header", type="string",  required=false, description="Optional filters (JSON-encoded).")
     * @API\Parameter(name="X-Sort",   in="header", type="string",  required=false, description="Optional sorting (JSON-encoded).")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned issue."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned issue."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found issues."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Application\Swagger\Issue::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request                     $request
     * @param QueryBusInterface           $queryBus
     * @param LastReadRepositoryInterface $lastReadRepository
     *
     * @return JsonResponse
     */
    public function listIssues(Request $request, QueryBusInterface $queryBus, LastReadRepositoryInterface $lastReadRepository): JsonResponse
    {
        $query = new Query\GetIssuesQuery($request);

        /** @var \eTraxis\Application\Query\Collection $collection */
        $collection = $queryBus->execute($query);

        /** @var \eTraxis\Entity\LastRead[] $lastReads */
        $ids = array_map(function (Issue $issue) {
            return $issue->id;
        }, $collection->data);

        $lastReadRepository->warmup($ids);

        return $this->json($collection, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Creates new issue.
     *
     * @Route("", name="api_issues_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateIssueCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function createIssue(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\CreateIssueCommand($request->request->all());

        /** @var Issue $issue */
        $issue = $commandBus->handle($command);

        $url = $this->generateUrl('api_issues_get', [
            'id' => $issue->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified issue.
     *
     * @Route("/{id}", name="api_issues_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Application\Swagger\Issue::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Issue                       $issue
     * @param LastReadRepositoryInterface $lastReadRepository
     *
     * @return JsonResponse
     */
    public function getIssue(Issue $issue, LastReadRepositoryInterface $lastReadRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        $lastReadRepository->markAsRead($issue);

        return $this->json($issue, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]);
    }

    /**
     * Clones specified issue.
     *
     * @Route("/{id}", name="api_issues_clone", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\CloneIssueCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function cloneIssue(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\CloneIssueCommand($request->request->all());

        $command->issue = $id;

        /** @var Issue $issue */
        $issue = $commandBus->handle($command);

        $url = $this->generateUrl('api_issues_get', [
            'id' => $issue->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Updates specified issue.
     *
     * @Route("/{id}", name="api_issues_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateIssueCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function updateIssue(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\UpdateIssueCommand($request->request->all());

        $command->issue = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified issue.
     *
     * @Route("/{id}", name="api_issues_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
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
    public function deleteIssue(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\DeleteIssueCommand([
            'issue' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Changes state of specified issue.
     *
     * @Route("/{id}/state/{state}", name="api_issues_state", methods={"POST"}, requirements={"id": "\d+", "state": "\d+"})
     *
     * @API\Parameter(name="id",    in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="state", in="path", type="integer", required=true, description="State ID.")
     * @API\Parameter(name="",      in="body", @Model(type=Command\ChangeStateCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue or state is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param int                 $state
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function changeState(Request $request, int $id, int $state, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\ChangeStateCommand($request->request->all());

        $command->issue = $id;
        $command->state = $state;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Reassigns specified issue.
     *
     * @Route("/{id}/assign/{user}", name="api_issues_assign", methods={"POST"}, requirements={"id": "\d+", "user": "\d+"})
     *
     * @API\Parameter(name="id",   in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="user", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue or user is not found.")
     *
     * @param int                 $id
     * @param int                 $user
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function assignIssue(int $id, int $user, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\ReassignIssueCommand([
            'issue'       => $id,
            'responsible' => $user,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Suspends specified issue.
     *
     * @Route("/{id}/suspend", name="api_issues_suspend", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\SuspendIssueCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function suspendIssue(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\SuspendIssueCommand($request->request->all());

        $command->issue = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Resumes specified issue.
     *
     * @Route("/{id}/resume", name="api_issues_resume", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function resumeIssue(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\ResumeIssueCommand([
            'issue' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Marks specified issue as read.
     *
     * @Route("/{id}/read", name="api_issues_read", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\MarkAsReadCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function readIssue(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\MarkAsReadCommand([
            'issues' => [$id],
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Marks specified issue as unread.
     *
     * @Route("/{id}/unread", name="api_issues_unread", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\MarkAsUnreadCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function unreadIssue(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\MarkAsUnreadCommand([
            'issues' => [$id],
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of issue events.
     *
     * @Route("/{id}/events", name="api_issues_events", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Application\Swagger\Event::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param int                      $id
     * @param QueryBusInterface        $queryBus
     * @param StateRepositoryInterface $stateRepository
     * @param UserRepositoryInterface  $userRepository
     * @param FileRepositoryInterface  $fileRepository
     * @param IssueRepositoryInterface $issueRepository
     *
     * @return JsonResponse
     */
    public function listEvents(
        int                      $id,
        QueryBusInterface        $queryBus,
        StateRepositoryInterface $stateRepository,
        UserRepositoryInterface  $userRepository,
        FileRepositoryInterface  $fileRepository,
        IssueRepositoryInterface $issueRepository
    ): JsonResponse
    {
        $query = new Query\GetEventsQuery([
            'issue' => $id,
        ]);

        /** @var Event[] $events */
        $events = $queryBus->execute($query);

        // Find all events with FK as a parameter.
        $stateEvents = array_filter($events, function (Event $event) {
            return in_array($event->type, [
                EventType::ISSUE_CREATED,
                EventType::STATE_CHANGED,
                EventType::ISSUE_REOPENED,
                EventType::ISSUE_CLOSED,
            ], true);
        });

        $userEvents = array_filter($events, function (Event $event) {
            return in_array($event->type, [
                EventType::ISSUE_ASSIGNED,
            ], true);
        });

        $fileEvents = array_filter($events, function (Event $event) {
            return in_array($event->type, [
                EventType::FILE_ATTACHED,
                EventType::FILE_DELETED,
            ], true);
        });

        $issueEvents = array_filter($events, function (Event $event) {
            return in_array($event->type, [
                EventType::DEPENDENCY_ADDED,
                EventType::DEPENDENCY_REMOVED,
            ], true);
        });

        // Get IDs for all required repositories.
        $stateIds = array_map(function (Event $event) {
            return $event->id;
        }, $stateEvents);

        $userIds = array_map(function (Event $event) {
            return $event->id;
        }, $userEvents);

        $fileIds = array_map(function (Event $event) {
            return $event->id;
        }, $fileEvents);

        $issueIds = array_map(function (Event $event) {
            return $event->id;
        }, $issueEvents);

        // Warmup repositories cache.
        $stateRepository->warmup(array_unique($stateIds));
        $userRepository->warmup(array_unique($userIds));
        $fileRepository->warmup(array_unique($fileIds));
        $issueRepository->warmup(array_unique($issueIds));

        return $this->json($events, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Returns list of issue changes.
     *
     * @Route("/{id}/changes", name="api_issues_changes", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Application\Swagger\Change::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param int                             $id
     * @param QueryBusInterface               $queryBus
     * @param DecimalValueRepositoryInterface $decimalRepository
     * @param ListItemRepositoryInterface     $listRepository
     * @param StringValueRepositoryInterface  $stringRepository
     * @param TextValueRepositoryInterface    $textRepository
     *
     * @return JsonResponse
     */
    public function listChanges(
        int                             $id,
        QueryBusInterface               $queryBus,
        DecimalValueRepositoryInterface $decimalRepository,
        ListItemRepositoryInterface     $listRepository,
        StringValueRepositoryInterface  $stringRepository,
        TextValueRepositoryInterface    $textRepository
    ): JsonResponse
    {
        $query = new Query\GetChangesQuery([
            'issue' => $id,
        ]);

        /** @var \eTraxis\Entity\Change[] $changes */
        $changes = $queryBus->execute($query);

        // Warmup values cache.
        $ids = [
            FieldType::DECIMAL => [],
            FieldType::LIST    => [],
            FieldType::STRING  => [],
            FieldType::TEXT    => [],
        ];

        foreach ($changes as $change) {

            $type = $change->field === null
                ? FieldType::STRING
                : $change->field->type;

            $ids[$type][] = $change->oldValue;
            $ids[$type][] = $change->newValue;
        }

        $decimalRepository->warmup(array_unique($ids[FieldType::DECIMAL]));
        $listRepository->warmup(array_unique($ids[FieldType::LIST]));
        $stringRepository->warmup(array_unique($ids[FieldType::STRING]));
        $textRepository->warmup(array_unique($ids[FieldType::TEXT]));

        return $this->json($changes, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Returns list of issue watchers.
     *
     * **X-Filter model:**
     * <pre>
     * {
     *   "email": "string",
     *   "fullname": "string"
     * }
     * </pre>
     *
     * **X-Sort model:**
     * <pre>
     * {
     *   "email": "ASC",
     *   "fullname": "ASC"
     * }
     * </pre>
     *
     * @Route("/{id}/watchers", name="api_issues_watchers", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id",       in="path",   type="integer", required=true,  description="Issue ID.")
     * @API\Parameter(name="offset",   in="query",  type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first watcher to return.")
     * @API\Parameter(name="limit",    in="query",  type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of watchers to return.")
     * @API\Parameter(name="X-Search", in="header", type="string",  required=false, description="Optional search value.")
     * @API\Parameter(name="X-Filter", in="header", type="string",  required=false, description="Optional filters (JSON-encoded).")
     * @API\Parameter(name="X-Sort",   in="header", type="string",  required=false, description="Optional sorting (JSON-encoded).")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned watcher."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned watcher."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found watchers."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Application\Swagger\UserInfo::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request           $request
     * @param int               $id
     * @param QueryBusInterface $queryBus
     *
     * @return JsonResponse
     */
    public function listWatchers(Request $request, int $id, QueryBusInterface $queryBus): JsonResponse
    {
        $query = new Query\GetWatchersQuery($request);

        $query->issue = $id;

        $collection = $queryBus->execute($query);

        return $this->json($collection, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Starts watching for specified issue.
     *
     * @Route("/{id}/watch", name="api_issues_watch", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\WatchIssuesCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function watchIssue(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\WatchIssuesCommand([
            'issues' => [$id],
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Stops watching for specified issue.
     *
     * @Route("/{id}/unwatch", name="api_issues_unwatch", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\UnwatchIssuesCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function unwatchIssue(int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\UnwatchIssuesCommand([
            'issues' => [$id],
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of issue comments.
     *
     * @Route("/{id}/comments", name="api_issues_comments", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Application\Swagger\Comment::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param int               $id
     * @param QueryBusInterface $queryBus
     *
     * @return JsonResponse
     */
    public function listComments(int $id, QueryBusInterface $queryBus): JsonResponse
    {
        $query = new Query\GetCommentsQuery([
            'issue' => $id,
        ]);

        $comments = $queryBus->execute($query);

        return $this->json($comments, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Creates new comment.
     *
     * @Route("/{id}/comments", name="api_issues_comments_create", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\AddCommentCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function createComment(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\AddCommentCommand($request->request->all());

        $command->issue = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of issue files.
     *
     * @Route("/{id}/files", name="api_files_list", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Application\Swagger\File::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param int               $id
     * @param QueryBusInterface $queryBus
     *
     * @return JsonResponse
     */
    public function listFiles(int $id, QueryBusInterface $queryBus): JsonResponse
    {
        $query = new Query\GetFilesQuery([
            'issue' => $id,
        ]);

        $files = $queryBus->execute($query);

        return $this->json($files, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Attaches new file.
     *
     * @Route("/{id}/files", name="api_files_create", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id",         in="path",     type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="attachment", in="formData", type="file",    required=true, description="Uploaded file.")
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function createFile(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $attachment */
        $attachment = $request->files->get('attachment');

        $command = new Command\AttachFileCommand([
            'issue' => $id,
            'file'  => $attachment,
        ]);

        /** @var \eTraxis\Entity\File $file */
        $file = $commandBus->handle($command);

        $url = $this->generateUrl('api_files_download', [
            'id' => $file->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns list of issue dependencies.
     *
     * **X-Filter model:**
     * <pre>
     * {
     *   "id": "string",
     *   "subject": "string",
     *   "author": 0,
     *   "author_name": "string",
     *   "project": 0,
     *   "project_name": "string",
     *   "template": 0,
     *   "template_name": "string",
     *   "state": 0,
     *   "state_name": "string",
     *   "responsible": 0,
     *   "responsible_name": "string",
     *   "is_cloned": true,
     *   "age": 0,
     *   "is_critical": true,
     *   "is_suspended": true,
     *   "is_closed": true
     * }
     * </pre>
     *
     * **X-Sort model:**
     * <pre>
     * {
     *   "id": "ASC",
     *   "subject": "ASC",
     *   "created_at": "ASC",
     *   "changed_at": "ASC",
     *   "closed_at": "ASC",
     *   "author": "ASC",
     *   "project": "ASC",
     *   "template": "ASC",
     *   "state": "ASC",
     *   "responsible": "ASC",
     *   "age": "ASC"
     * }
     * </pre>
     *
     * @Route("/{id}/dependencies", name="api_issues_dependencies_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id",       in="path",   type="integer", required=true,  description="Issue ID.")
     * @API\Parameter(name="offset",   in="query",  type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first issue to return.")
     * @API\Parameter(name="limit",    in="query",  type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of issues to return.")
     * @API\Parameter(name="X-Search", in="header", type="string",  required=false, description="Optional search value.")
     * @API\Parameter(name="X-Filter", in="header", type="string",  required=false, description="Optional filters (JSON-encoded).")
     * @API\Parameter(name="X-Sort",   in="header", type="string",  required=false, description="Optional sorting (JSON-encoded).")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned issue."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned issue."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found issues."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Application\Swagger\Issue::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request                     $request
     * @param int                         $id
     * @param QueryBusInterface           $queryBus
     * @param LastReadRepositoryInterface $lastReadRepository
     *
     * @return JsonResponse
     */
    public function getDependencies(Request $request, int $id, QueryBusInterface $queryBus, LastReadRepositoryInterface $lastReadRepository): JsonResponse
    {
        $query = new Query\GetDependenciesQuery($request);

        $query->issue = $id;

        /** @var \eTraxis\Application\Query\Collection $collection */
        $collection = $queryBus->execute($query);

        /** @var \eTraxis\Entity\LastRead[] $lastReads */
        $ids = array_map(function (Issue $issue) {
            return $issue->id;
        }, $collection->data);

        $lastReadRepository->warmup($ids);

        return $this->json($collection, JsonResponse::HTTP_OK, [], [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
    }

    /**
     * Updates list of issue dependencies.
     *
     * @Route("/{id}/dependencies", name="api_issues_dependencies_set", methods={"PATCH"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @API\Schema(
     *     @API\Property(property="add", type="array", example={123, 456}, description="List of issue IDs to add.",
     *         @API\Items(type="integer")
     *     ),
     *     @API\Property(property="remove", type="array", example={123, 456}, description="List of issue IDs to remove.",
     *         @API\Items(type="integer")
     *     )
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request             $request
     * @param int                 $id
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function setDependencies(Request $request, int $id, CommandBusInterface $commandBus): JsonResponse
    {
        $add    = $request->request->get('add');
        $remove = $request->request->get('remove');

        $add    = is_array($add) ? $add : [];
        $remove = is_array($remove) ? $remove : [];

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->getDoctrine()->getManager();
        $manager->beginTransaction();

        $command = new Command\AddDependenciesCommand([
            'issue'        => $id,
            'dependencies' => array_diff($add, $remove),
        ]);

        if (count($command->dependencies)) {
            $commandBus->handle($command);
        }

        $command = new Command\RemoveDependenciesCommand([
            'issue'        => $id,
            'dependencies' => array_diff($remove, $add),
        ]);

        if (count($command->dependencies)) {
            $commandBus->handle($command);
        }

        $manager->commit();

        return $this->json(null);
    }

    /**
     * Marks multiple issues as read.
     *
     * @Route("/read", name="api_issues_read_multiple", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\MarkAsReadCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function readMultipleIssues(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\MarkAsReadCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Marks multiple issues as unread.
     *
     * @Route("/unread", name="api_issues_unread_multiple", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\MarkAsUnreadCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function unreadMultipleIssues(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\MarkAsUnreadCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Starts watching for multiple issues.
     *
     * @Route("/watch", name="api_issues_watch_multiple", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\WatchIssuesCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function watchMultipleIssues(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\WatchIssuesCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Stops watching for multiple issues.
     *
     * @Route("/unwatch", name="api_issues_unwatch_multiple", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\UnwatchIssuesCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request             $request
     * @param CommandBusInterface $commandBus
     *
     * @return JsonResponse
     */
    public function unwatchMultipleIssues(Request $request, CommandBusInterface $commandBus): JsonResponse
    {
        $command = new Command\UnwatchIssuesCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }
}
