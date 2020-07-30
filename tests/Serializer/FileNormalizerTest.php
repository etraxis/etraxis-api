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

namespace eTraxis\Serializer;

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Hateoas;
use eTraxis\Entity\Event;
use eTraxis\Entity\File;
use eTraxis\Entity\Issue;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\FileNormalizer
 */
class FileNormalizerTest extends WebTestCase
{
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $security */
        $security = self::$container->get('security.authorization_checker');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = self::$container->get('router');

        $this->normalizer = new FileNormalizer($security, $router);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSelfOnly()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'        => $file->id,
            'user'      => [
                'id'       => $file->event->user->id,
                'email'    => 'jkiehn@example.com',
                'fullname' => 'Jarrell Kiehn',
            ],
            'timestamp' => $file->event->createdAt,
            'name'      => 'Inventore.pdf',
            'size'      => 175971,
            'type'      => 'application/pdf',
            'links'     => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/files/%s', $baseUrl, $file->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($file, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinksAttachedFile()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'        => $file->id,
            'user'      => [
                'id'       => $file->event->user->id,
                'email'    => 'jkiehn@example.com',
                'fullname' => 'Jarrell Kiehn',
            ],
            'timestamp' => $file->event->createdAt,
            'name'      => 'Inventore.pdf',
            'size'      => 175971,
            'type'      => 'application/pdf',
            'links'     => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/files/%s', $baseUrl, $file->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'delete',
                    'href' => sprintf('%s/api/files/%s', $baseUrl, $file->id),
                    'type' => 'DELETE',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($file, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinksRemovedFile()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Possimus sapiente.pdf'], ['id' => 'ASC']);

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'        => $file->id,
            'user'      => [
                'id'       => $file->event->user->id,
                'email'    => 'jkiehn@example.com',
                'fullname' => 'Jarrell Kiehn',
            ],
            'timestamp' => $file->event->createdAt,
            'name'      => 'Possimus sapiente.pdf',
            'size'      => 10753,
            'type'      => 'application/pdf',
            'links'     => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/files/%s', $baseUrl, $file->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($file, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $user  = new User();
        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);
        $file  = new File($event, 'filename.ext', 0, 'plain/text');

        self::assertTrue($this->normalizer->supportsNormalization($file, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($file, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization($event, 'json'));
    }
}
