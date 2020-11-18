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
use eTraxis\Entity\Project;
use eTraxis\Voter\ProjectVoter;
use eTraxis\Voter\TemplateVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Project' entity.
 */
class ProjectNormalizer implements NormalizerInterface
{
    // HATEOAS links.
    public const UPDATE_PROJECT  = 'update';
    public const DELETE_PROJECT  = 'delete';
    public const SUSPEND_PROJECT = 'suspend';
    public const RESUME_PROJECT  = 'resume';
    public const CREATE_TEMPLATE = 'create_template';

    private AuthorizationCheckerInterface $security;
    private RouterInterface               $router;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param RouterInterface               $router
     */
    public function __construct(AuthorizationCheckerInterface $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router   = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var Project $object */
        $url = $this->router->generate('api_projects_get', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = [
            Project::JSON_ID          => $object->id,
            Project::JSON_NAME        => $object->name,
            Project::JSON_DESCRIPTION => $object->description,
            Project::JSON_CREATED     => $object->createdAt,
            Project::JSON_SUSPENDED   => $object->isSuspended,
            Hateoas::LINKS            => [
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
            self::UPDATE_PROJECT  => [
                $this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $object),
                $this->router->generate('api_projects_update', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_PUT,
            ],
            self::DELETE_PROJECT  => [
                $this->security->isGranted(ProjectVoter::DELETE_PROJECT, $object),
                $this->router->generate('api_projects_delete', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_DELETE,
            ],
            self::SUSPEND_PROJECT => [
                $this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $object),
                $this->router->generate('api_projects_suspend', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::RESUME_PROJECT  => [
                $this->security->isGranted(ProjectVoter::RESUME_PROJECT, $object),
                $this->router->generate('api_projects_resume', ['id' => $object->id], UrlGeneratorInterface::ABSOLUTE_URL),
                Request::METHOD_POST,
            ],
            self::CREATE_TEMPLATE => [
                $this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $object),
                $this->router->generate('api_templates_create', [], UrlGeneratorInterface::ABSOLUTE_URL),
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
        return $format === Hateoas::FORMAT_JSON && $data instanceof Project;
    }
}
