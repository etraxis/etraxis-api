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
use eTraxis\Entity\State;
use eTraxis\Voter\FieldVoter;
use eTraxis\Voter\StateVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'State' entity.
 */
class StateNormalizer implements NormalizerInterface
{
    // HATEOAS links.
    public const UPDATE_STATE           = 'update';
    public const DELETE_STATE           = 'delete';
    public const SET_INITIAL            = 'set_initial';
    public const GET_TRANSITIONS        = 'get_transitions';
    public const SET_TRANSITIONS        = 'set_transitions';
    public const GET_RESPONSIBLE_GROUPS = 'get_responsible_groups';
    public const SET_RESPONSIBLE_GROUPS = 'set_responsible_groups';
    public const CREATE_FIELD           = 'create_field';

    private AuthorizationCheckerInterface $security;
    private RouterInterface               $router;
    private TemplateNormalizer            $templateNormalizer;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param RouterInterface               $router
     * @param TemplateNormalizer            $templateNormalizer
     */
    public function __construct(AuthorizationCheckerInterface $security, RouterInterface $router, TemplateNormalizer $templateNormalizer)
    {
        $this->security           = $security;
        $this->router             = $router;
        $this->templateNormalizer = $templateNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var State $object */
        $url = $this->router->generate('api_states_get', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = [
            State::JSON_ID          => $object->id,
            State::JSON_TEMPLATE    => $this->templateNormalizer->normalize($object->template, $format, [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
            State::JSON_NAME        => $object->name,
            State::JSON_TYPE        => $object->type,
            State::JSON_RESPONSIBLE => $object->responsible,
            State::JSON_NEXT        => $object->nextState === null ? null : $object->nextState->id,
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
            self::UPDATE_STATE           => [
                $this->security->isGranted(StateVoter::UPDATE_STATE, $object),
                $this->router->generate('api_states_update', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::DELETE_STATE           => [
                $this->security->isGranted(StateVoter::DELETE_STATE, $object),
                $this->router->generate('api_states_delete', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_DELETE,
            ],
            self::SET_INITIAL            => [
                $this->security->isGranted(StateVoter::SET_INITIAL, $object),
                $this->router->generate('api_states_initial', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::GET_TRANSITIONS        => [
                $this->security->isGranted(StateVoter::GET_TRANSITIONS, $object),
                $this->router->generate('api_states_get_transitions', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::SET_TRANSITIONS        => [
                $this->security->isGranted(StateVoter::SET_TRANSITIONS, $object),
                $this->router->generate('api_states_set_transitions', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::GET_RESPONSIBLE_GROUPS => [
                $this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $object),
                $this->router->generate('api_states_get_responsibles', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::SET_RESPONSIBLE_GROUPS => [
                $this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $object),
                $this->router->generate('api_states_set_responsibles', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::CREATE_FIELD           => [
                $this->security->isGranted(FieldVoter::CREATE_FIELD, $object),
                $this->router->generate('api_fields_create', [], UrlGeneratorInterface::ABSOLUTE_URL),
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
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof State;
    }
}
