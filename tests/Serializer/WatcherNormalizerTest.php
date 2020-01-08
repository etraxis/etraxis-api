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

use eTraxis\Entity\Issue;
use eTraxis\Entity\User;
use eTraxis\Entity\Watcher;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Serializer\WatcherNormalizer
 */
class WatcherNormalizerTest extends WebTestCase
{
    /**
     * @var WatcherNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new WatcherNormalizer();
    }

    /**
     * @covers ::normalize
     */
    public function testNormalize()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByUsername('fdooley@example.com');

        /** @var Watcher $watcher */
        [$watcher] = $this->doctrine->getRepository(Watcher::class)->findBy([
            'user' => $user,
        ]);

        $expected = [
            'id'       => $watcher->user->id,
            'email'    => 'fdooley@example.com',
            'fullname' => 'Francesca Dooley',
        ];

        self::assertSame($expected, $this->normalizer->normalize($watcher, 'json'));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $user    = new User();
        $issue   = new Issue($user);
        $watcher = new Watcher($issue, $user);

        self::assertTrue($this->normalizer->supportsNormalization($watcher, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($watcher, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization($issue, 'json'));
    }
}