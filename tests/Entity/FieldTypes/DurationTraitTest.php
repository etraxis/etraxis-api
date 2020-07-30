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

namespace eTraxis\Entity\FieldTypes;

use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\Field;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\DurationTrait
 */
class DurationTraitTest extends WebTestCase
{
    use ReflectionTrait;

    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $object;
    private DurationInterface   $facade;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::DURATION);
        $this->setProperty($this->object, 'id', 1);
        $this->object->isRequired = false;

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asDuration
     */
    public function testJsonSerialize()
    {
        $expected = [
            'minimum' => '0:00',
            'maximum' => '999999:59',
            'default' => null,
        ];

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asDuration
     */
    public function testValidationConstraints()
    {
        $this->object->name = 'Custom field';
        $this->facade
            ->setMinimumValue('0:00')
            ->setMaximumValue('24:00');

        $errors = $this->validator->validate('0:00', $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('24:00', $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('24:01', $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0:00 to 24:00.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0:60', $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::asDuration
     */
    public function testMinimumValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $duration = 866;
        $value    = '14:26';
        $min      = '0:00';
        $max      = '999999:59';

        $this->facade->setMinimumValue($value);
        self::assertSame($value, $this->facade->getMinimumValue());
        self::assertSame($duration, $this->getProperty($parameters, 'parameter1'));

        $this->facade->setMinimumValue($min);
        self::assertSame($min, $this->facade->getMinimumValue());

        $this->facade->setMinimumValue($max);
        self::assertSame($max, $this->facade->getMinimumValue());
    }

    /**
     * @covers ::asDuration
     */
    public function testMaximumValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $duration = 866;
        $value    = '14:26';
        $min      = '0:00';
        $max      = '999999:59';

        $this->facade->setMaximumValue($value);
        self::assertSame($value, $this->facade->getMaximumValue());
        self::assertSame($duration, $this->getProperty($parameters, 'parameter2'));

        $this->facade->setMaximumValue($min);
        self::assertSame($min, $this->facade->getMaximumValue());

        $this->facade->setMaximumValue($max);
        self::assertSame($max, $this->facade->getMaximumValue());
    }

    /**
     * @covers ::asDuration
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $duration = 866;
        $value    = '14:26';
        $min      = '0:00';
        $max      = '999999:59';

        $this->facade->setDefaultValue($value);
        self::assertSame($value, $this->facade->getDefaultValue());
        self::assertSame($duration, $this->getProperty($parameters, 'defaultValue'));

        $this->facade->setDefaultValue($min);
        self::assertSame($min, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue($max);
        self::assertSame($max, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue(null);
        self::assertNull($this->facade->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }

    /**
     * @covers ::asDuration
     */
    public function testToNumber()
    {
        self::assertNull($this->facade->toNumber(null));
        self::assertNull($this->facade->toNumber('0:99'));
        self::assertSame(866, $this->facade->toNumber('14:26'));
    }

    /**
     * @covers ::asDuration
     */
    public function testToString()
    {
        self::assertNull($this->facade->toString(null));
        self::assertSame('0:00', $this->facade->toString(DurationInterface::MIN_VALUE - 1));
        self::assertSame('999999:59', $this->facade->toString(DurationInterface::MAX_VALUE + 1));
        self::assertSame('14:26', $this->facade->toString(866));
    }
}
