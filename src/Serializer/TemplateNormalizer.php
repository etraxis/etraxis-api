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
use eTraxis\Entity\Template;
use eTraxis\Voter\IssueVoter;
use eTraxis\Voter\StateVoter;
use eTraxis\Voter\TemplateVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Template' entity.
 */
class TemplateNormalizer implements NormalizerInterface
{
    // HATEOAS links.
    public const UPDATE_TEMPLATE = 'update';
    public const DELETE_TEMPLATE = 'delete';
    public const LOCK_TEMPLATE   = 'lock';
    public const UNLOCK_TEMPLATE = 'unlock';
    public const GET_PERMISSIONS = 'get_permissions';
    public const SET_PERMISSIONS = 'set_permissions';
    public const CREATE_STATE    = 'create_state';
    public const CREATE_ISSUE    = 'create_issue';

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
        /** @var Template $object */
        $url = $this->router->generate('api_templates_get', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = [
            Template::JSON_ID          => $object->id,
            Template::JSON_PROJECT     => $this->projectNormalizer->normalize($object->project, $format, [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]),
            Template::JSON_NAME        => $object->name,
            Template::JSON_PREFIX      => $object->prefix,
            Template::JSON_DESCRIPTION => $object->description,
            Template::JSON_CRITICAL    => $object->criticalAge,
            Template::JSON_FROZEN      => $object->frozenTime,
            Template::JSON_LOCKED      => $object->isLocked,
            Hateoas::LINKS             => [
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
            self::UPDATE_TEMPLATE => [
                $this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $object),
                $this->router->generate('api_templates_update', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::DELETE_TEMPLATE => [
                $this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $object),
                $this->router->generate('api_templates_delete', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_DELETE,
            ],
            self::LOCK_TEMPLATE   => [
                $this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $object),
                $this->router->generate('api_templates_lock', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::UNLOCK_TEMPLATE => [
                $this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $object),
                $this->router->generate('api_templates_unlock', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::GET_PERMISSIONS => [
                $this->security->isGranted(TemplateVoter::GET_PERMISSIONS, $object),
                $this->router->generate('api_templates_get_permissions', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_GET,
            ],
            self::SET_PERMISSIONS => [
                $this->security->isGranted(TemplateVoter::SET_PERMISSIONS, $object),
                $this->router->generate('api_templates_set_permissions', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::CREATE_STATE    => [
                $this->security->isGranted(StateVoter::CREATE_STATE, $object),
                $this->router->generate('api_states_create', [], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::CREATE_ISSUE    => [
                $this->security->isGranted(IssueVoter::CREATE_ISSUE, $object),
                $this->router->generate('api_issues_create', [], UrlGeneratorInterface::ABSOLUTE_URL),
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
        return $format === Hateoas::FORMAT_JSON && $data instanceof Template;
    }
}
