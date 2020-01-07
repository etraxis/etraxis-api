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
use eTraxis\Entity\File;
use eTraxis\Entity\User;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'File' entity.
 */
class FileNormalizer implements NormalizerInterface
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
        /** @var File $object */
        $url = $this->router->generate('api_files_download', [
            'id' => $object->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = [
            File::JSON_ID        => $object->id,
            File::JSON_USER      => [
                User::JSON_ID       => $object->event->user->id,
                User::JSON_EMAIL    => $object->event->user->email,
                User::JSON_FULLNAME => $object->event->user->fullname,
            ],
            File::JSON_TIMESTAMP => $object->event->createdAt,
            File::JSON_NAME      => $object->name,
            File::JSON_SIZE      => $object->size,
            File::JSON_TYPE      => $object->type,
            Hateoas::LINKS       => [
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

        if ($this->security->isGranted(IssueVoter::DELETE_FILE, $object->issue) && !$object->isRemoved) {

            $url = $this->router->generate('api_files_delete', [
                'id' => $object->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $result[Hateoas::LINKS][] = [
                Hateoas::LINK_RELATION => IssueVoter::DELETE_FILE,
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
        return $format === Hateoas::FORMAT_JSON && $data instanceof File;
    }
}
