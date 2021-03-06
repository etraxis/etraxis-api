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

use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Comment;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\CommentNormalizer
 */
class CommentNormalizerTest extends WebTestCase
{
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new CommentNormalizer();
    }

    /**
     * @covers ::normalize
     */
    public function testNormalize()
    {
        /** @var Comment $comment */
        [$comment] = $this->doctrine->getRepository(Comment::class)->findBy(['isPrivate' => true], ['id' => 'ASC']);

        /** @var \Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->loadUserByUsername('dorcas.ernser@example.com');

        $expected = [
            'id'        => $comment->id,
            'user'      => [
                'id'       => $user->id,
                'email'    => 'dorcas.ernser@example.com',
                'fullname' => 'Dorcas Ernser',
            ],
            'timestamp' => $comment->event->createdAt,
            'text'      => 'Ut ipsum explicabo iste sequi dignissimos. Et voluptatibus dolorum voluptas porro odio. Maiores debitis soluta deserunt tenetur totam consequatur nisi iusto. Occaecati itaque quae omnis sequi in dolor dolor. Modi eum sunt quidem impedit. Quisquam minus at occaecati quaerat sunt fugit. Sunt modi in enim repellat velit blanditiis iure. Omnis similique voluptatem voluptas qui esse ducimus ut. Optio id repellendus odio qui fugit qui. Provident reprehenderit in odio repudiandae corporis est.',
            'private'   => true,
        ];

        static::assertSame($expected, $this->normalizer->normalize($comment, 'json'));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $user    = new User();
        $issue   = new Issue($user);
        $event   = new Event(EventType::ISSUE_EDITED, $issue, $user);
        $comment = new Comment($event);

        static::assertTrue($this->normalizer->supportsNormalization($comment, 'json'));
        static::assertFalse($this->normalizer->supportsNormalization($comment, 'xml'));
        static::assertFalse($this->normalizer->supportsNormalization($event, 'json'));
    }
}
