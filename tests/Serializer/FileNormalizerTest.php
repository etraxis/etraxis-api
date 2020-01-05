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
use eTraxis\Entity\Event;
use eTraxis\Entity\File;
use eTraxis\Entity\Issue;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Serializer\FileNormalizer
 */
class FileNormalizerTest extends WebTestCase
{
    /**
     * @var FileNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new FileNormalizer();
    }

    /**
     * @covers ::normalize
     */
    public function testNormalize()
    {
        /** @var File $file */
        [$file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $expected = [
            'id'        => $file->id,
            'user'      => [
                'id'       => $file->event->user->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $file->event->createdAt,
            'name'      => 'Inventore.pdf',
            'size'      => 175971,
            'type'      => 'application/pdf',
        ];

        self::assertSame($expected, $this->normalizer->normalize($file, 'json'));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $user  = new User();
        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);
        $file  = new File($event, 'filename.ext', 0, 'plain/text');

        self::assertTrue($this->normalizer->supportsNormalization($file, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($file, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass(), 'json'));
    }
}
