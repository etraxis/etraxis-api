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

namespace eTraxis\Controller\IssuesController;

use eTraxis\Entity\Issue;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \eTraxis\Controller\IssuesController::getIssue
 */
class GetIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4']);

        /** @var State $resolved */
        [/* skipping */, /* skipping */, $resolved] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved']);

        /** @var User $author */
        $author = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dtillman@example.com']);

        /** @var User $responsible */
        $responsible = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'cbatz@example.com']);

        /** @var User $kbahringer */
        $kbahringer = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'kbahringer@example.com']);

        /** @var User $tbuckridge */
        $tbuckridge = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tbuckridge@example.com']);

        /** @var User $tmarquardt */
        $tmarquardt = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'           => $issue->id,
            'subject'      => 'Support request 4',
            'created_at'   => $issue->createdAt,
            'changed_at'   => $issue->changedAt,
            'closed_at'    => null,
            'author'       => [
                'id'       => $author->id,
                'email'    => 'dtillman@example.com',
                'fullname' => 'Derrick Tillman',
            ],
            'state'        => [
                'id'          => $issue->state->id,
                'template'    => [
                    'id'          => $issue->state->template->id,
                    'project'     => [
                        'id'          => $issue->state->template->project->id,
                        'name'        => 'Excepturi',
                        'description' => 'Project C',
                        'created'     => $issue->state->template->project->createdAt,
                        'suspended'   => false,
                        'links'       => [
                            [
                                'rel'  => 'self',
                                'href' => sprintf('%s/api/projects/%s', $baseUrl, $issue->state->template->project->id),
                                'type' => 'GET',
                            ],
                        ],
                    ],
                    'name'        => 'Support',
                    'prefix'      => 'req',
                    'description' => 'Support Request C',
                    'critical'    => 3,
                    'frozen'      => 7,
                    'locked'      => false,
                    'links'       => [
                        [
                            'rel'  => 'self',
                            'href' => sprintf('%s/api/templates/%s', $baseUrl, $issue->state->template->id),
                            'type' => 'GET',
                        ],
                    ],
                ],
                'name'        => 'Opened',
                'type'        => 'intermediate',
                'responsible' => 'assign',
                'next'        => null,
                'links'       => [
                    [
                        'rel'  => 'self',
                        'href' => sprintf('%s/api/states/%s', $baseUrl, $issue->state->id),
                        'type' => 'GET',
                    ],
                ],
            ],
            'responsible'  => [
                'id'       => $responsible->id,
                'email'    => 'cbatz@example.com',
                'fullname' => 'Carter Batz',
            ],
            'is_cloned'    => false,
            'origin'       => null,
            'age'          => $issue->age,
            'is_critical'  => true,
            'is_suspended' => false,
            'resumes_at'   => null,
            'is_closed'    => false,
            'is_frozen'    => false,
            'read_at'      => time(),
            'links'        => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/issues/%s', $baseUrl, $issue->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'issue.update',
                    'href' => sprintf('%s/api/issues/%s', $baseUrl, $issue->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'issue.delete',
                    'href' => sprintf('%s/api/issues/%s', $baseUrl, $issue->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'    => 'state.change',
                    'href'   => sprintf('%s/api/issues/%s/state/{state}', $baseUrl, $issue->id),
                    'type'   => 'POST',
                    'states' => [
                        [
                            'id'          => $resolved->id,
                            'name'        => $resolved->name,
                            'type'        => $resolved->type,
                            'responsible' => $resolved->responsible,
                        ],
                    ],
                ],
                [
                    'rel'   => 'issue.reassign',
                    'href'  => sprintf('%s/api/issues/%s/assign/{user}', $baseUrl, $issue->id),
                    'type'  => 'POST',
                    'users' => [
                        [
                            'id'       => $kbahringer->id,
                            'email'    => 'kbahringer@example.com',
                            'fullname' => 'Kailyn Bahringer',
                        ],
                        [
                            'id'       => $tbuckridge->id,
                            'email'    => 'tbuckridge@example.com',
                            'fullname' => 'Tony Buckridge',
                        ],
                        [
                            'id'       => $tmarquardt->id,
                            'email'    => 'tmarquardt@example.com',
                            'fullname' => 'Tracy Marquardt',
                        ],
                    ],
                ],
                [
                    'rel'  => 'issue.suspend',
                    'href' => sprintf('%s/api/issues/%s/suspend', $baseUrl, $issue->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'comment.public.add',
                    'href' => sprintf('%s/api/issues/%s/comments', $baseUrl, $issue->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'comment.private.add',
                    'href' => sprintf('%s/api/issues/%s/comments', $baseUrl, $issue->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'comment.private.read',
                    'href' => sprintf('%s/api/issues/%s/comments', $baseUrl, $issue->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'dependency.add',
                    'href' => sprintf('%s/api/issues/%s/dependencies', $baseUrl, $issue->id),
                    'type' => 'PATCH',
                ],
                [
                    'rel'  => 'dependency.remove',
                    'href' => sprintf('%s/api/issues/%s/dependencies', $baseUrl, $issue->id),
                    'type' => 'PATCH',
                ],
            ],
        ];

        $uri = sprintf('/api/issues/%s', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $actual = json_decode($this->client->getResponse()->getContent(), true);
        self::assertLessThanOrEqual(2, $actual['read_at'] - $expected['read_at']);

        $expected['read_at'] = $actual['read_at'];
        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6']);

        $uri = sprintf('/api/issues/%s', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6']);

        $uri = sprintf('/api/issues/%s', $issue->id);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('nhills@example.com');

        $uri = sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID);

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
