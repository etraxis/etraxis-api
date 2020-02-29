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
use eTraxis\Entity\Template;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\MyController::getTemplates
 */
class GetTemplatesTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Template $taskC */
        /** @var Template $reqC */
        /** @var Template $reqD */
        [/* skipping */, /* skipping */, $taskC]       = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $reqC, $reqD] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer */
        $normalizer = self::$container->get('serializer');

        $expected = [
            $normalizer->normalize($taskC, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
            $normalizer->normalize($reqC, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
            $normalizer->normalize($reqD, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
        ];

        $uri = '/api/my/templates';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function testSuccessEmpty()
    {
        $this->loginAs('admin@example.com');

        $expected = [];

        $uri = '/api/my/templates';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($expected, json_decode($this->client->getResponse()->getContent(), true));
    }

    public function test401()
    {
        $uri = '/api/my/templates';

        $this->client->xmlHttpRequest(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
