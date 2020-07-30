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

namespace eTraxis\Validator\Constraints;

use eTraxis\WebTestCase;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @coversDefaultClass \eTraxis\Validator\Constraints\DateRangeValidator
 */
class DateRangeValidatorTest extends WebTestCase
{
    private ValidatorInterface $validator;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->client->getContainer()->get('validator');
    }

    /**
     * @covers \eTraxis\Validator\Constraints\DateRange::__construct
     */
    public function testMissingOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Either option "min" or "max" must be given for constraint "eTraxis\\Validator\\Constraints\\DateRange".');

        $constraint = new DateRange();

        $this->validator->validate('2015-12-29', [$constraint]);
    }

    /**
     * @covers \eTraxis\Validator\Constraints\DateRange::__construct
     */
    public function testInvalidMinOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "min" option given for constraint "eTraxis\\Validator\\Constraints\\DateRange" is invalid.');

        $constraint = new DateRange([
            'min' => '2015-22-11',
        ]);

        $this->validator->validate('2015-12-29', [$constraint]);
    }

    /**
     * @covers \eTraxis\Validator\Constraints\DateRange::__construct
     */
    public function testInvalidMaxOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "max" option given for constraint "eTraxis\\Validator\\Constraints\\DateRange" is invalid.');

        $constraint = new DateRange([
            'max' => '2015-22-11',
        ]);

        $this->validator->validate('2015-12-29', [$constraint]);
    }

    /**
     * @covers ::validate
     * @covers \eTraxis\Validator\Constraints\DateRange::__construct
     */
    public function testBothOptions()
    {
        $constraint = new DateRange([
            'min' => '2015-11-22',
            'max' => '2016-02-15',
        ]);

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2015-11-22 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2016-02-15 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-22', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-15', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::validate
     */
    public function testMinOptionOnly()
    {
        $constraint = new DateRange([
            'min' => '2015-11-22',
        ]);

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2015-11-22 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-22', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::validate
     */
    public function testMaxOptionOnly()
    {
        $constraint = new DateRange([
            'max' => '2016-02-15',
        ]);

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2016-02-15 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-15', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::validate
     */
    public function testCustomMinMessage()
    {
        $constraint = new DateRange([
            'min'        => '2015-11-22',
            'minMessage' => 'The value must be >= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value must be >= 2015-11-22.', $errors->get(0)->getMessage());
    }

    /**
     * @covers ::validate
     */
    public function testCustomMaxMessage()
    {
        $constraint = new DateRange([
            'max'        => '2016-02-15',
            'maxMessage' => 'The value must be <= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value must be <= 2016-02-15.', $errors->get(0)->getMessage());
    }

    /**
     * @covers ::validate
     */
    public function testCustomInvalidMessage()
    {
        $constraint = new DateRange([
            'min'            => '2015-11-22',
            'max'            => '2016-02-15',
            'invalidMessage' => 'The value is invalid.',
        ]);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value is invalid.', $errors->get(0)->getMessage());
    }
}
