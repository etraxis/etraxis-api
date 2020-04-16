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
use eTraxis\Entity\User;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'User' entity.
 */
class UserNormalizer implements NormalizerInterface
{
    // HATEOAS links.
    public const UPDATE_USER   = 'update';
    public const DELETE_USER   = 'delete';
    public const DISABLE_USER  = 'disable';
    public const ENABLE_USER   = 'enable';
    public const UNLOCK_USER   = 'unlock';
    public const SET_PASSWORD  = 'set_password';
    public const GROUPS        = 'groups';
    public const ADD_GROUPS    = 'add_groups';
    public const REMOVE_GROUPS = 'remove_groups';

    private $security;
    private $router;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param RouterInterface               $router
     */
    public function __construct(AuthorizationCheckerInterface $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router   = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var User $object */
        $url = $this->router->generate('api_users_get', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = [
            User::JSON_ID          => $object->id,
            User::JSON_EMAIL       => $object->email,
            User::JSON_FULLNAME    => $object->fullname,
            User::JSON_DESCRIPTION => $object->description,
            User::JSON_ADMIN       => $object->isAdmin,
            User::JSON_DISABLED    => !$object->isEnabled(),
            User::JSON_LOCKED      => !$object->isAccountNonLocked(),
            User::JSON_PROVIDER    => $object->account->provider,
            User::JSON_LOCALE      => $object->locale,
            User::JSON_THEME       => $object->theme,
            User::JSON_LIGHT_MODE  => $object->isLightMode,
            User::JSON_TIMEZONE    => $object->timezone,
            Hateoas::LINKS         => [
                [
                    Hateoas::LINK_RELATION => Hateoas::SELF,
                    Hateoas::LINK_HREF     => $url,
                    Hateoas::LINK_TYPE     => Request::METHOD_GET,
                ],
            ],
        ];

        $mode = $context[Hateoas::MODE] ?? Hateoas::MODE_ALL_LINKS;

        if ($mode === Hateoas::MODE_SELF_ONLY) {
            return $result;
        }

        $links = [
            self::UPDATE_USER   => [
                $this->security->isGranted(UserVoter::UPDATE_USER, $object),
                $this->router->generate('api_users_update', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::DELETE_USER   => [
                $this->security->isGranted(UserVoter::DELETE_USER, $object),
                $this->router->generate('api_users_delete', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_DELETE,
            ],
            self::DISABLE_USER  => [
                $this->security->isGranted(UserVoter::DISABLE_USER, $object),
                $this->router->generate('api_users_disable', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::ENABLE_USER   => [
                $this->security->isGranted(UserVoter::ENABLE_USER, $object),
                $this->router->generate('api_users_enable', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::UNLOCK_USER   => [
                $this->security->isGranted(UserVoter::UNLOCK_USER, $object),
                $this->router->generate('api_users_unlock', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::SET_PASSWORD  => [
                $this->security->isGranted(UserVoter::SET_PASSWORD, $object),
                $this->router->generate('api_users_password', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::GROUPS        => [
                $this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $object),
                $this->router->generate('api_users_groups_get', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::ADD_GROUPS    => [
                $this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $object),
                $this->router->generate('api_users_groups_set', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PATCH,
            ],
            self::REMOVE_GROUPS => [
                $this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $object),
                $this->router->generate('api_users_groups_set', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PATCH,
            ],
        ];

        foreach ($links as $relation => $link) {
            if ($link[0]) {
                $result[Hateoas::LINKS][] = [
                    Hateoas::LINK_RELATION => $relation,
                    Hateoas::LINK_HREF     => $link[1],
                    Hateoas::LINK_TYPE     => $link[2],
                ];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof User;
    }
}
