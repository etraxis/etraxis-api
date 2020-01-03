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
    private $security;
    private $router;
    private $templateNormalizer;

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

        if ($this->security->isGranted(StateVoter::UPDATE_STATE, $object)) {

            $url = $this->router->generate('api_states_update', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => StateVoter::UPDATE_STATE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(StateVoter::DELETE_STATE, $object)) {

            $url = $this->router->generate('api_states_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => StateVoter::DELETE_STATE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_DELETE,
            ];
        }

        if ($this->security->isGranted(StateVoter::SET_INITIAL, $object)) {

            $url = $this->router->generate('api_states_initial', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => StateVoter::SET_INITIAL,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $object)) {

            $url = $this->router->generate('api_states_get_transitions', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => StateVoter::MANAGE_TRANSITIONS,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_GET,
            ];
        }

        if ($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $object)) {

            $url = $this->router->generate('api_states_get_responsibles', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => StateVoter::MANAGE_RESPONSIBLE_GROUPS,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_GET,
            ];
        }

        if ($this->security->isGranted(FieldVoter::CREATE_FIELD, $object)) {

            $url = $this->router->generate('api_fields_create', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => FieldVoter::CREATE_FIELD,
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
        return $format === Hateoas::FORMAT_JSON && $data instanceof State;
    }
}
