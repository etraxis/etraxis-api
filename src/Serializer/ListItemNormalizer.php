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
    // HATEOAS links.
    public const UPDATE_ITEM = 'update';
    public const DELETE_ITEM = 'delete';

    private AuthorizationCheckerInterface $security;
    private RouterInterface               $router;
    private FieldNormalizer               $fieldNormalizer;

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

        $links = [
            self::UPDATE_ITEM   => [
                $this->security->isGranted(ListItemVoter::UPDATE_ITEM, $object),
                $this->router->generate('api_items_update', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::DELETE_ITEM   => [
                $this->security->isGranted(ListItemVoter::DELETE_ITEM, $object),
                $this->router->generate('api_items_delete', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_DELETE,
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
        return $format === Hateoas::FORMAT_JSON && $data instanceof ListItem;
    }
}
