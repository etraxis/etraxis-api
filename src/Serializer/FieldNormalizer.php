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

use Doctrine\ORM\EntityManagerInterface;
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
    private $security;
    private $router;
    private $manager;
    private $stateNormalizer;

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
     * {@inheritdoc}
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

        if ($this->security->isGranted(FieldVoter::UPDATE_FIELD, $object)) {

            $url = $this->router->generate('api_fields_update', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => FieldVoter::UPDATE_FIELD,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(FieldVoter::REMOVE_FIELD, $object)) {

            $url = $this->router->generate('api_fields_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => FieldVoter::DELETE_FIELD,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_DELETE,
            ];
        }

        if ($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $object)) {

            $url = $this->router->generate('api_fields_get_permissions', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => FieldVoter::MANAGE_PERMISSIONS,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_GET,
            ];
        }

        if ($this->security->isGranted(ListItemVoter::CREATE_ITEM, $object)) {

            $url = $this->router->generate('api_items_create', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => ListItemVoter::CREATE_ITEM,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof Field;
    }
}
