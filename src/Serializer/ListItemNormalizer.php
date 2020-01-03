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
use eTraxis\Entity\ListItem;
use eTraxis\Voter\ListItemVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'ListItem' entity.
 */
class ListItemNormalizer implements NormalizerInterface
{
    private $security;
    private $router;
    private $fieldNormalizer;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param RouterInterface               $router
     * @param FieldNormalizer               $fieldNormalizer
     */
    public function __construct(AuthorizationCheckerInterface $security, RouterInterface $router, FieldNormalizer $fieldNormalizer)
    {
        $this->security        = $security;
        $this->router          = $router;
        $this->fieldNormalizer = $fieldNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var ListItem $object */
        $url = $this->router->generate('api_items_get', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $mode = $context[Hateoas::MODE] ?? Hateoas::MODE_ALL_LINKS;

        $result = $object->jsonSerialize();

        if ($mode === Hateoas::MODE_ALL_LINKS) {
            $result[ListItem::JSON_FIELD] = $this->fieldNormalizer->normalize($object->field, $format, [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);
        }

        $result[Hateoas::LINKS] = [
            [
                Hateoas::LINK_RELATION => Hateoas::SELF,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_GET,
            ],
        ];

        if ($mode === Hateoas::MODE_SELF_ONLY) {
            return $result;
        }

        if ($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $object)) {

            $url = $this->router->generate('api_items_update', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => ListItemVoter::UPDATE_ITEM,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(ListItemVoter::DELETE_ITEM, $object)) {

            $url = $this->router->generate('api_items_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => ListItemVoter::DELETE_ITEM,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_DELETE,
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof ListItem;
    }
}
