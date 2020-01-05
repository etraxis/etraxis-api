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

        if ($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $object)) {

            $url = $this->router->generate('api_templates_update', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => TemplateVoter::UPDATE_TEMPLATE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $object)) {

            $url = $this->router->generate('api_templates_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => TemplateVoter::DELETE_TEMPLATE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_DELETE,
            ];
        }

        if ($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $object)) {

            $url = $this->router->generate('api_templates_lock', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => TemplateVoter::LOCK_TEMPLATE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $object)) {

            $url = $this->router->generate('api_templates_unlock', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => TemplateVoter::UNLOCK_TEMPLATE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(TemplateVoter::MANAGE_PERMISSIONS, $object)) {

            $url = $this->router->generate('api_templates_set_permissions', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => TemplateVoter::MANAGE_PERMISSIONS,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(StateVoter::CREATE_STATE, $object)) {

            $url = $this->router->generate('api_states_create', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => StateVoter::CREATE_STATE,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(IssueVoter::CREATE_ISSUE, $object)) {

            $url = $this->router->generate('api_issues_create', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::CREATE_ISSUE,
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
        return $format === Hateoas::FORMAT_JSON && $data instanceof Template;
    }
}
