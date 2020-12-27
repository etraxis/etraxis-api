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

namespace eTraxis\Validator\Constraints;

use eTraxis\WebTestCase;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @coversDefaultClass \eTraxis\Validator\Constraints\DecimalRangeValidator
 */
class DecimalRangeValidatorTest extends WebTestCase
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
     * @covers \eTraxis\Validator\Constraints\DecimalRange::__construct
     */
    public function testMissingOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Either option "min" or "max" must be given for constraint "eTraxis\\Validator\\Constraints\\DecimalRange".');

        $constraint = new DecimalRange();

        $this->validator->validate('0', [$constraint]);
    }

    /**
     * @covers \eTraxis\Validator\Constraints\DecimalRange::__construct
     */
    public function testInvalidMinOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "min" option given for constraint "eTraxis\\Validator\\Constraints\\DecimalRange" is invalid.');

        $constraint = new DecimalRange([
            'min' => 'test',
        ]);

        $this->validator->validate('0', [$constraint]);
    }

    /**
     * @covers \eTraxis\Validator\Constraints\DecimalRange::__construct
     */
    public function testInvalidMaxOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "max" option given for constraint "eTraxis\\Validator\\Constraints\\DecimalRange" is invalid.');

        $constraint = new DecimalRange([
            'max' => 'test',
        ]);

        $this->validator->validate('0', [$constraint]);
    }

    /**
     * @covers ::validate
     * @covers \eTraxis\Validator\Constraints\DecimalRange::__construct
     */
    public function testBothOptions()
    {
        $constraint = new DecimalRange([
            'min' => '-10',
            'max' => '+10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('-10.01', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('11', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('10.01', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        static::assertCount(0, $errors);
    }

    /**
     * @covers ::validate
     */
    public function testMinOptionOnly()
    {
        $constraint = new DecimalRange([
            'min' => '-10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('-10.01', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('11', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('10.01', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('0', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        static::assertCount(0, $errors);
    }

    /**
     * @covers ::validate
     */
    public function testMaxOptionOnly()
    {
        $constraint = new DecimalRange([
            'max' => '+10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.01', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('11', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('10.01', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        static::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        static::assertCount(0, $errors);
    }

    /**
     * @covers ::validate
     */
    public function testCustomMinMessage()
    {
        $constraint = new DecimalRange([
            'min'        => '1',
            'minMessage' => 'The value must be >= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('0', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('The value must be >= 1.', $errors->get(0)->getMessage());
    }

    /**
     * @covers ::validate
     */
    public function testCustomMaxMessage()
    {
        $constraint = new DecimalRange([
            'max'        => '10',
            'maxMessage' => 'The value must be <= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('11', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('The value must be <= 10.', $errors->get(0)->getMessage());
    }

    /**
     * @covers ::validate
     */
    public function testCustomInvalidMessage()
    {
        $constraint = new DecimalRange([
            'min'            => '1',
            'max'            => '10',
            'invalidMessage' => 'The value is invalid.',
        ]);

        $errors = $this->validator->validate('test', [$constraint]);
        static::assertNotCount(0, $errors);
        static::assertSame('The value is invalid.', $errors->get(0)->getMessage());
    }
}
