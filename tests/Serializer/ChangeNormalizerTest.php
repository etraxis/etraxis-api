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
use eTraxis\Entity\Change;
use eTraxis\Entity\DecimalValue;
use eTraxis\Entity\Event;
use eTraxis\Entity\Field;
use eTraxis\Entity\Issue;
use eTraxis\Entity\ListItem;
use eTraxis\Entity\StringValue;
use eTraxis\Entity\TextValue;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @coversDefaultClass \eTraxis\Serializer\ChangeNormalizer
 */
class ChangeNormalizerTest extends WebTestCase
{
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \eTraxis\Repository\Contracts\DecimalValueRepositoryInterface $decimalRepository */
        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        /** @var \eTraxis\Repository\Contracts\StringValueRepositoryInterface $stringRepository */
        /** @var \eTraxis\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);
        $listRepository    = $this->doctrine->getRepository(ListItem::class);
        $stringRepository  = $this->doctrine->getRepository(StringValue::class);
        $textRepository    = $this->doctrine->getRepository(TextValue::class);

        $this->normalizer = new ChangeNormalizer(
            $decimalRepository,
            $listRepository,
            $stringRepository,
            $textRepository
        );
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSubject()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Event $event */
        [$event] = $this->doctrine->getRepository(Event::class)->findBy([
            'type'  => EventType::ISSUE_EDITED,
            'issue' => $issue,
        ], [
            'createdAt' => 'ASC',
        ]);

        /** @var Change $change */
        [$change] = $this->doctrine->getRepository(Change::class)->findBy(['event' => $event], ['id' => 'ASC']);

        $expected = [
            'user'      => [
                'id'       => $event->user->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $event->createdAt,
            'field'     => null,
            'old_value' => 'Task 1',
            'new_value' => 'Development task 1',
        ];

        self::assertSame($expected, $this->normalizer->normalize($change, 'json'));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeField()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Event $event */
        [$event] = $this->doctrine->getRepository(Event::class)->findBy([
            'type'  => EventType::ISSUE_EDITED,
            'issue' => $issue,
        ], [
            'createdAt' => 'ASC',
        ]);

        /** @var Change $change */
        [/* skipping */, $change] = $this->doctrine->getRepository(Change::class)->findBy(['event' => $event], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $valueNormal */
        $valueNormal = $this->doctrine->getRepository(ListItem::class)->findOneBy([
            'field' => $field,
            'value' => 2,
        ]);

        /** @var ListItem $valueLow */
        $valueLow = $this->doctrine->getRepository(ListItem::class)->findOneBy([
            'field' => $field,
            'value' => 3,
        ]);

        $expected = [
            'user'      => [
                'id'       => $event->user->id,
                'email'    => 'ldoyle@example.com',
                'fullname' => 'Leland Doyle',
            ],
            'timestamp' => $event->createdAt,
            'field'     => [
                'id'          => $field->id,
                'name'        => 'Priority',
                'type'        => 'list',
                'description' => null,
                'position'    => 1,
                'required'    => true,
            ],
            'old_value' => [
                'id'    => $valueLow->id,
                'value' => 3,
                'text'  => 'low',
            ],
            'new_value' => [
                'id'    => $valueNormal->id,
                'value' => 2,
                'text'  => 'normal',
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($change, 'json'));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization()
    {
        $user   = new User();
        $issue  = new Issue($user);
        $event  = new Event(EventType::ISSUE_EDITED, $issue, $user);
        $change = new Change($event, null, null, null);

        self::assertTrue($this->normalizer->supportsNormalization($change, 'json'));
        self::assertFalse($this->normalizer->supportsNormalization($change, 'xml'));
        self::assertFalse($this->normalizer->supportsNormalization($event, 'json'));
    }
}
