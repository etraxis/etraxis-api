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

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Application\Hateoas;
use eTraxis\Entity\Event;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\FileRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for a 'Event' entity.
 */
class EventNormalizer implements NormalizerInterface
{
    private IssueNormalizer          $issueNormalizer;
    private FileNormalizer           $fileNormalizer;
    private StateRepositoryInterface $stateRepository;
    private UserRepositoryInterface  $userRepository;
    private FileRepositoryInterface  $fileRepository;
    private IssueRepositoryInterface $issueRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param IssueNormalizer          $issueNormalizer
     * @param FileNormalizer           $fileNormalizer
     * @param StateRepositoryInterface $stateRepository
     * @param UserRepositoryInterface  $userRepository
     * @param FileRepositoryInterface  $fileRepository
     * @param IssueRepositoryInterface $issueRepository
     */
    public function __construct(
        IssueNormalizer          $issueNormalizer,
        FileNormalizer           $fileNormalizer,
        StateRepositoryInterface $stateRepository,
        UserRepositoryInterface  $userRepository,
        FileRepositoryInterface  $fileRepository,
        IssueRepositoryInterface $issueRepository
    )
    {
        $this->issueNormalizer = $issueNormalizer;
        $this->fileNormalizer  = $fileNormalizer;
        $this->stateRepository = $stateRepository;
        $this->userRepository  = $userRepository;
        $this->fileRepository  = $fileRepository;
        $this->issueRepository = $issueRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        /** @var Event $object */
        $result = [
            Event::JSON_TYPE      => $object->type,
            Event::JSON_USER      => [
                User::JSON_ID       => $object->user->id,
                User::JSON_EMAIL    => $object->user->email,
                User::JSON_FULLNAME => $object->user->fullname,
            ],
            Event::JSON_TIMESTAMP => $object->createdAt,
        ];

        switch ($object->type) {

            case EventType::ISSUE_CREATED:
            case EventType::STATE_CHANGED:
            case EventType::ISSUE_REOPENED:
            case EventType::ISSUE_CLOSED:

                /** @var \eTraxis\Entity\State $state */
                $state = $this->stateRepository->find($object->parameter);

                $result[Event::JSON_STATE] = [
                    'id'          => $state->id,
                    'name'        => $state->name,
                    'type'        => $state->type,
                    'responsible' => $state->responsible,
                ];

                break;

            case EventType::ISSUE_ASSIGNED:

                /** @var User $user */
                $user = $this->userRepository->find($object->parameter);

                $result[Event::JSON_ASSIGNEE] = [
                    'id'       => $user->id,
                    'email'    => $user->email,
                    'fullname' => $user->fullname,
                ];

                break;

            case EventType::FILE_ATTACHED:
            case EventType::FILE_DELETED:

                /** @var \eTraxis\Entity\File $file */
                $file = $this->fileRepository->find($object->parameter);

                $result[Event::JSON_FILE] = $this->fileNormalizer->normalize($file, $format, [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);

                break;

            case EventType::DEPENDENCY_ADDED:
            case EventType::DEPENDENCY_REMOVED:

                /** @var \eTraxis\Entity\Issue $issue */
                $issue = $this->issueRepository->find($object->parameter);

                $result[Event::JSON_ISSUE] = $this->issueNormalizer->normalize($issue, $format, [Hateoas::MODE => Hateoas::MODE_SELF_ONLY]);

                break;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null)
    {
        return $format === Hateoas::FORMAT_JSON && $data instanceof Event;
    }
}
