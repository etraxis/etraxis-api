<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Serializer;

use eTraxis\Application\Hateoas;
use eTraxis\Entity\Group;
use eTraxis\Voter\GroupVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Group' entity.
 */
class GroupNormalizer implements NormalizerInterface
{
    // HATEOAS links.
    public const UPDATE_GROUP   = 'update';
    public const DELETE_GROUP   = 'delete';
    public const MEMBERS        = 'members';
    public const ADD_MEMBERS    = 'add_members';
    public const REMOVE_MEMBERS = 'remove_members';

    private AuthorizationCheckerInterface $security;
    private RouterInterface               $router;
    private ProjectNormalizer             $projectNormalizer;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param RouterInterface               $router
     * @param ProjectNormalizer             $projectNormalizer
     */
    public function __construct(AuthorizationCheckerInterface $security, RouterInterface $router, ProjectNormalizer $projectNormalizer)
    {
        $this->security          = $security;
        $this->router            = $router;
        $this->projectNormalizer = $projectNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var Group $object */
        $url = $this->router->generate('api_groups_get', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = [
            Group::JSON_ID          => $object->id,
            Group::JSON_PROJECT     => $object->isGlobal
                ? null
                : $this->projectNormalizer->normalize($object->project, $format, [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
            Group::JSON_NAME        => $object->name,
            Group::JSON_DESCRIPTION => $object->description,
            Group::JSON_GLOBAL      => $object->isGlobal,
            Hateoas::LINKS          => [
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
            self::UPDATE_GROUP   => [
                $this->security->isGranted(GroupVoter::UPDATE_GROUP, $object),
                $this->router->generate('api_groups_update', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::DELETE_GROUP   => [
                $this->security->isGranted(GroupVoter::DELETE_GROUP, $object),
                $this->router->generate('api_groups_delete', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_DELETE,
            ],
            self::MEMBERS        => [
                $this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $object),
                $this->router->generate('api_groups_members_get', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::ADD_MEMBERS    => [
                $this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $object),
                $this->router->generate('api_groups_members_set', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PATCH,
            ],
            self::REMOVE_MEMBERS => [
                $this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $object),
                $this->router->generate('api_groups_members_set', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
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
        return $format === Hateoas::FORMAT_JSON && $data instanceof Group;
    }
}
