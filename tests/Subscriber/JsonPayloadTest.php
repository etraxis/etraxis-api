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

namespace eTraxis\Subscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \eTraxis\Subscriber\JsonPayload
 */
class JsonPayloadTest extends TestCase
{
    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents()
    {
        $expected = [
            'kernel.controller',
        ];

        self::assertSame($expected, array_keys(JsonPayload::getSubscribedEvents()));
    }

    /**
     * @covers ::onJson
     */
    public function testFormPostRequest()
    {
        $parameters = [
            'month'     => 'July',
            'dayOfWeek' => 'Thursday',
        ];

        $headers = [
            'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $content = '{ "firstName": "Anna",  "lastName": "Rodygina" }';

        $request = new Request([], $parameters, [], [], [], $headers, $content);
        $request->setMethod(Request::METHOD_POST);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ControllerEvent(
            $kernel,
            function () {},
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $subscriber = new JsonPayload();
        $subscriber->onJson($event);

        $request = $event->getRequest();

        $expected = $parameters;

        self::assertSame($expected, $request->request->all());
    }

    /**
     * @covers ::onJson
     */
    public function testJsonPostRequest()
    {
        $parameters = [
            'month'     => 'July',
            'dayOfWeek' => 'Thursday',
        ];

        $headers = [
            'HTTP_Content-Type' => 'application/json',
        ];

        $content = '{ "firstName": "Anna",  "lastName": "Rodygina" }';

        $request = new Request([], $parameters, [], [], [], $headers, $content);
        $request->setMethod(Request::METHOD_POST);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ControllerEvent(
            $kernel,
            function () {},
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $subscriber = new JsonPayload();
        $subscriber->onJson($event);

        $request = $event->getRequest();

        $expected = [
            'firstName' => 'Anna',
            'lastName'  => 'Rodygina',
        ];

        self::assertSame($expected, $request->request->all());
    }

    /**
     * @covers ::onJson
     */
    public function testJsonGetRequest()
    {
        $parameters = [
            'month'     => 'July',
            'dayOfWeek' => 'Thursday',
        ];

        $headers = [
            'HTTP_Content-Type' => 'application/json',
        ];

        $content = '{ "firstName": "Anna",  "lastName": "Rodygina" }';

        $request = new Request([], $parameters, [], [], [], $headers, $content);
        $request->setMethod(Request::METHOD_GET);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ControllerEvent(
            $kernel,
            function () {},
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $subscriber = new JsonPayload();
        $subscriber->onJson($event);

        $request = $event->getRequest();

        $expected = $parameters;

        self::assertSame($expected, $request->request->all());
    }
}
