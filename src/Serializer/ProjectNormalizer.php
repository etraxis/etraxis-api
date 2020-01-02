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
use eTraxis\Entity\Project;
use eTraxis\Voter\ProjectVoter;
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
    private $security;
    private $router;

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

        if ($this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $object)) {

            $url = $this->router->generate('api_projects_update', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => ProjectVoter::UPDATE_PROJECT,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_PUT,
            ];
        }

        if ($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $object)) {

            $url = $this->router->generate('api_projects_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => ProjectVoter::DELETE_PROJECT,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_DELETE,
            ];
        }

        if ($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $object)) {

            $url = $this->router->generate('api_projects_suspend', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => ProjectVoter::SUSPEND_PROJECT,
                Hateoas::LINK_HREF     => $url,
                Hateoas::LINK_TYPE     => Request::METHOD_POST,
            ];
        }

        if ($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $object)) {

            $url = $this->router->generate('api_projects_resume', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => ProjectVoter::RESUME_PROJECT,
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
        return $format === Hateoas::FORMAT_JSON && $data instanceof Project;
    }
}
