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
    private $security;
    private $router;
    private $projectNormalizer;

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

        if ($this->security->isGranted(GroupVoter::UPDATE_GROUP, $object)) {

            $url = $this->router->generate('api_groups_update', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => GroupVoter::UPDATE_GROUP,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(GroupVoter::DELETE_GROUP, $object)) {

            $url = $this->router->generate('api_groups_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => GroupVoter::DELETE_GROUP,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_DELETE,
            ];
        }

        if ($this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $object)) {

            $url = $this->router->generate('api_groups_members_get', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => GroupVoter::MANAGE_MEMBERSHIP,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_GET,
            ];
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
