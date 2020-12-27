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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Hateoas;
use eTraxis\Entity\Field;
use eTraxis\Voter\FieldVoter;
use eTraxis\Voter\ListItemVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Field' entity.
 */
class FieldNormalizer implements NormalizerInterface
{
    // HATEOAS links.
    public const UPDATE_FIELD    = 'update';
    public const DELETE_FIELD    = 'delete';
    public const SET_POSITION    = 'set_position';
    public const GET_PERMISSIONS = 'get_permissions';
    public const SET_PERMISSIONS = 'set_permissions';
    public const ITEMS           = 'items';
    public const CREATE_ITEM     = 'create_item';

    private AuthorizationCheckerInterface $security;
    private RouterInterface               $router;
    private EntityManagerInterface        $manager;
    private StateNormalizer               $stateNormalizer;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param RouterInterface               $router
     * @param EntityManagerInterface        $manager
     * @param StateNormalizer               $stateNormalizer
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        RouterInterface               $router,
        EntityManagerInterface        $manager,
        StateNormalizer               $stateNormalizer
    )
    {
        $this->security        = $security;
        $this->router          = $router;
        $this->manager         = $manager;
        $this->stateNormalizer = $stateNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var Field $object */
        $url = $this->router->generate('api_fields_get', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = [
            Field::JSON_ID          => $object->id,
            Field::JSON_STATE       => $this->stateNormalizer->normalize($object->state, $format, [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
            Field::JSON_NAME        => $object->name,
            Field::JSON_TYPE        => $object->type,
            Field::JSON_DESCRIPTION => $object->description,
            Field::JSON_POSITION    => $object->position,
            Field::JSON_REQUIRED    => $object->isRequired,
        ];

        $result += $object->getFacade($this->manager)->jsonSerialize();

        $result[Hateoas::LINKS] = [
            [
                Hateoas::LINK_RELATION => Hateoas::SELF,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_GET,
            ],
        ];

        $mode = $context[Hateoas::MODE] ?? Hateoas::MODE_ALL_LINKS;

        if ($mode === Hateoas::MODE_SELF_ONLY) {
            return $result;
        }

        $links = [
            self::UPDATE_FIELD    => [
                $this->security->isGranted(FieldVoter::UPDATE_FIELD, $object),
                $this->router->generate('api_fields_update', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::DELETE_FIELD    => [
                $this->security->isGranted(FieldVoter::REMOVE_FIELD, $object),
                $this->router->generate('api_fields_delete', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_DELETE,
            ],
            self::SET_POSITION    => [
                $this->security->isGranted(FieldVoter::UPDATE_FIELD, $object),
                $this->router->generate('api_fields_position', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::GET_PERMISSIONS => [
                $this->security->isGranted(FieldVoter::GET_PERMISSIONS, $object),
                $this->router->generate('api_fields_get_permissions', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::SET_PERMISSIONS => [
                $this->security->isGranted(FieldVoter::SET_PERMISSIONS, $object),
                $this->router->generate('api_fields_set_permissions', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::ITEMS           => [
                $object->type === FieldType::LIST,
                $this->router->generate('api_items_list', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::CREATE_ITEM     => [
                $this->security->isGranted(ListItemVoter::CREATE_ITEM, $object),
                $this->router->generate('api_items_create', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
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
     * {@inheritDoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof Field;
    }
}
