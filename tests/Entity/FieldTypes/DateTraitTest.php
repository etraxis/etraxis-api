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
use eTraxis\Application\Seconds;
use eTraxis\Entity\Field;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\DateTrait
 */
class DateTraitTest extends WebTestCase
{
    use ReflectionTrait;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var Field
     */
    private $object;

    /**
     * @var DateInterface
     */
    private $facade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::DATE);
        $this->setProperty($this->object, 'id', 1);

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asDate
     */
    public function testValidationConstraints()
    {
        $this->object->name = 'Custom field';
        $this->facade
            ->setMinimumValue(0)
            ->setMaximumValue(7);

        $now = time();

        $errors = $this->validator->validate(date('Y-m-d', $now), $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(date('Y-m-d', $now + Seconds::ONE_DAY * 7), $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(date('Y-m-d', $now - Seconds::ONE_DAY), $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame(sprintf('\'Custom field\' should be in range from %s to %s.', date('n/j/y', $now), date('n/j/y', $now + Seconds::ONE_DAY * 7)), $errors->get(0)->getMessage());

        $errors = $this->validator->validate(date('Y-m-d', $now + Seconds::ONE_DAY * 8), $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame(sprintf('\'Custom field\' should be in range from %s to %s.', date('n/j/y', $now), date('n/j/y', $now + Seconds::ONE_DAY * 7)), $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-22-11', $this->facade->getValidationConstraints($this->translator));
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
     * @covers ::asDate
     */
    public function testMinimumValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(DateInterface::MIN_VALUE, DateInterface::MAX_VALUE);
        $min   = DateInterface::MIN_VALUE - 1;
        $max   = DateInterface::MAX_VALUE + 1;

        $this->facade->setMinimumValue($value);
        self::assertSame($value, $this->facade->getMinimumValue());
        self::assertSame($value, $this->getProperty($parameters, 'parameter1'));

        $this->facade->setMinimumValue($min);
        self::assertSame(DateInterface::MIN_VALUE, $this->facade->getMinimumValue());

        $this->facade->setMinimumValue($max);
        self::assertSame(DateInterface::MAX_VALUE, $this->facade->getMinimumValue());
    }

    /**
     * @covers ::asDate
     */
    public function testMaximumValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(DateInterface::MIN_VALUE, DateInterface::MAX_VALUE);
        $min   = DateInterface::MIN_VALUE - 1;
        $max   = DateInterface::MAX_VALUE + 1;

        $this->facade->setMaximumValue($value);
        self::assertSame($value, $this->facade->getMaximumValue());
        self::assertSame($value, $this->getProperty($parameters, 'parameter2'));

        $this->facade->setMaximumValue($min);
        self::assertSame(DateInterface::MIN_VALUE, $this->facade->getMaximumValue());

        $this->facade->setMaximumValue($max);
        self::assertSame(DateInterface::MAX_VALUE, $this->facade->getMaximumValue());
    }

    /**
     * @covers ::asDate
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(DateInterface::MIN_VALUE, DateInterface::MAX_VALUE);
        $min   = DateInterface::MIN_VALUE - 1;
        $max   = DateInterface::MAX_VALUE + 1;

        $this->facade->setDefaultValue($value);
        self::assertSame($value, $this->facade->getDefaultValue());
        self::assertSame($value, $this->getProperty($parameters, 'defaultValue'));

        $this->facade->setDefaultValue($min);
        self::assertSame(DateInterface::MIN_VALUE, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue($max);
        self::assertSame(DateInterface::MAX_VALUE, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue(null);
        self::assertNull($this->facade->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }
}
