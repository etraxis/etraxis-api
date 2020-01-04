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

        if ($this->security->isGranted(UserVoter::UPDATE_USER, $object)) {

            $url = $this->router->generate('api_users_update', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => UserVoter::UPDATE_USER,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(UserVoter::DELETE_USER, $object)) {

            $url = $this->router->generate('api_users_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => UserVoter::DELETE_USER,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_DELETE,
            ];
        }

        if ($this->security->isGranted(UserVoter::SET_PASSWORD, $object)) {

            $url = $this->router->generate('api_users_password', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => UserVoter::SET_PASSWORD,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(UserVoter::DISABLE_USER, $object)) {

            $url = $this->router->generate('api_users_disable', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => UserVoter::DISABLE_USER,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(UserVoter::ENABLE_USER, $object)) {

            $url = $this->router->generate('api_users_enable', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => UserVoter::ENABLE_USER,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(UserVoter::UNLOCK_USER, $object)) {

            $url = $this->router->generate('api_users_unlock', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => UserVoter::UNLOCK_USER,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(UserVoter::MANAGE_MEMBERSHIP, $object)) {

            $url = $this->router->generate('api_users_groups_set', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => UserVoter::MANAGE_MEMBERSHIP,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PATCH,
            ];
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
