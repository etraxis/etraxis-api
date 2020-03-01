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

use eTraxis\Application\Hateoas;
use eTraxis\Entity\Group;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\UserNormalizer
 */
class UserNormalizerTest extends WebTestCase
{
    /**
     * @var UserNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $security */
        $security = self::$container->get('security.authorization_checker');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = self::$container->get('router');

        $this->normalizer = new UserNormalizer($security, $router);
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSelfOnly()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->loadUserByUsername('artem@example.com');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $user->id,
            'email'       => 'artem@example.com',
            'fullname'    => 'Artem Rodygin',
            'description' => null,
            'admin'       => false,
            'disabled'    => false,
            'locked'      => false,
            'provider'    => 'etraxis',
            'locale'      => 'en_US',
            'theme'       => 'azure',
            'timezone'    => 'UTC',
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/users/%s', $baseUrl, $user->id),
                    'type' => 'GET',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($user, 'json', [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeAllLinks()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->loadUserByUsername('artem@example.com');

        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router  = self::$container->get('router');
        $baseUrl = rtrim($router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $expected = [
            'id'          => $user->id,
            'email'       => 'artem@example.com',
            'fullname'    => 'Artem Rodygin',
            'description' => null,
            'admin'       => false,
            'disabled'    => false,
            'locked'      => false,
            'provider'    => 'etraxis',
            'locale'      => 'en_US',
            'theme'       => 'azure',
            'timezone'    => 'UTC',
            'links'       => [
                [
                    'rel'  => 'self',
                    'href' => sprintf('%s/api/users/%s', $baseUrl, $user->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'update',
                    'href' => sprintf('%s/api/users/%s', $baseUrl, $user->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'delete',
                    'href' => sprintf('%s/api/users/%s', $baseUrl, $user->id),
                    'type' => 'DELETE',
                ],
                [
                    'rel'  => 'disable',
                    'href' => sprintf('%s/api/users/%s/disable', $baseUrl, $user->id),
                    'type' => 'POST',
                ],
                [
                    'rel'  => 'set_password',
                    'href' => sprintf('%s/api/users/%s/password', $baseUrl, $user->id),
                    'type' => 'PUT',
                ],
                [
                    'rel'  => 'groups',
                    'href' => sprintf('%s/api/users/%s/groups', $baseUrl, $user->id),
                    'type' => 'GET',
                ],
                [
                    'rel'  => 'add_groups',
                    'href' => sprintf('%s/api/users/%s/groups', $baseUrl, $user->id),
                    'type' => 'PATCH',
                ],
                [
                    'rel'  => 'remove_groups',
                    'href' => sprintf('%s/api/users/%s/groups', $baseUrl, $user->id),
                    'type' => 'PATCH',
                ],
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($user, 'json', [Hateoas::MODE => Hateoas::MODE_ALL_LINKS]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $user  = new User();
        $group = new Group();

        self::assertTrue($this->normalizer->supportsNormalization($user, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($user, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization($group, 'json'));
    }
}
