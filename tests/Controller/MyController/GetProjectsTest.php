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

namespace eTraxis\Controller\MyController;

use eTraxis\Application\Hateoas;
use eTraxis\Entity\Project;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\MyController::getProjects
 */
class GetProjectsTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Project $projectC */
        /** @var Project $projectD */
        $projectC = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Excepturi']);
        $projectD = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Presto']);

        /** @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer */
        $normalizer = self::$container->get('serializer');

        $expected = [
            $normalizer->normalize($projectC, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
            $normalizer->normalize($projectD, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
        ];

        $uri = '/api/my/projects';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function testSuccessEmpty()
    {
        $this->loginAs('admin@example.com');

        $expected = [];

        $uri = '/api/my/projects';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        $uri = '/api/my/projects';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
