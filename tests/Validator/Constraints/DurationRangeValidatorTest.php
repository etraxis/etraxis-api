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

/**
 * @coversDefaultClass \eTraxis\Validator\Constraints\DurationRangeValidator
 */
class DurationRangeValidatorTest extends WebTestCase
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->client->getContainer()->get('validator');
    }

    /**
     * @covers \eTraxis\Validator\Constraints\DurationRange::__construct
     */
    public function testMissingOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Either option "min" or "max" must be given for constraint "eTraxis\\Validator\\Constraints\\DurationRange".');

        $constraint = new DurationRange();

        $this->validator->validate('0:00', [$constraint]);
    }

    /**
     * @covers \eTraxis\Validator\Constraints\DurationRange::__construct
     */
    public function testInvalidMinOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "min" option given for constraint "eTraxis\\Validator\\Constraints\\DurationRange" is invalid.');

        $constraint = new DurationRange([
            'min' => '0:60',
        ]);

        $this->validator->validate('0:00', [$constraint]);
    }

    /**
     * @covers \eTraxis\Validator\Constraints\DurationRange::__construct
     */
    public function testInvalidMaxOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "max" option given for constraint "eTraxis\\Validator\\Constraints\\DurationRange" is invalid.');

        $constraint = new DurationRange([
            'max' => '0:60',
        ]);

        $this->validator->validate('0:00', [$constraint]);
    }

    /**
     * @covers ::str2int
     * @covers ::validate
     * @covers \eTraxis\Validator\Constraints\DurationRange::__construct
     */
    public function testBothOptions()
    {
        $constraint = new DurationRange([
            'min' => '1:00',
            'max' => '10:00',
        ]);

        $errors = $this->validator->validate('0:59', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 1:00 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('10:01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 10:00 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('1:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0:60', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::str2int
     * @covers ::validate
     */
    public function testMinOptionOnly()
    {
        $constraint = new DurationRange([
            'min' => '1:00',
        ]);

        $errors = $this->validator->validate('0:59', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 1:00 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('1:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0:60', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::str2int
     * @covers ::validate
     */
    public function testMaxOptionOnly()
    {
        $constraint = new DurationRange([
            'max' => '10:00',
        ]);

        $errors = $this->validator->validate('10:01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 10:00 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0:60', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::str2int
     * @covers ::validate
     */
    public function testCustomMinMessage()
    {
        $constraint = new DurationRange([
            'min'        => '1:00',
            'minMessage' => 'The value must be >= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('0:00', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value must be >= 1:00.', $errors->get(0)->getMessage());
    }

    /**
     * @covers ::str2int
     * @covers ::validate
     */
    public function testCustomMaxMessage()
    {
        $constraint = new DurationRange([
            'max'        => '10:00',
            'maxMessage' => 'The value must be <= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('11:00', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value must be <= 10:00.', $errors->get(0)->getMessage());
    }

    /**
     * @covers ::validate
     */
    public function testCustomInvalidMessage()
    {
        $constraint = new DurationRange([
            'min'            => '1:00',
            'max'            => '10:00',
            'invalidMessage' => 'The value is invalid.',
        ]);

        $errors = $this->validator->validate('0:60', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value is invalid.', $errors->get(0)->getMessage());
    }
}
