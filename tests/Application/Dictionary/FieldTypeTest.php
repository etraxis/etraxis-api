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

namespace eTraxis\Application\Dictionary;

use eTraxis\Application\Command\Fields\CreateCheckboxFieldCommand;
use eTraxis\Application\Command\Fields\CreateDateFieldCommand;
use eTraxis\Application\Command\Fields\CreateDecimalFieldCommand;
use eTraxis\Application\Command\Fields\CreateDurationFieldCommand;
use eTraxis\Application\Command\Fields\CreateIssueFieldCommand;
use eTraxis\Application\Command\Fields\CreateListFieldCommand;
use eTraxis\Application\Command\Fields\CreateNumberFieldCommand;
use eTraxis\Application\Command\Fields\CreateStringFieldCommand;
use eTraxis\Application\Command\Fields\CreateTextFieldCommand;
use eTraxis\Application\Command\Fields\UpdateCheckboxFieldCommand;
use eTraxis\Application\Command\Fields\UpdateDateFieldCommand;
use eTraxis\Application\Command\Fields\UpdateDecimalFieldCommand;
use eTraxis\Application\Command\Fields\UpdateDurationFieldCommand;
use eTraxis\Application\Command\Fields\UpdateIssueFieldCommand;
use eTraxis\Application\Command\Fields\UpdateListFieldCommand;
use eTraxis\Application\Command\Fields\UpdateNumberFieldCommand;
use eTraxis\Application\Command\Fields\UpdateStringFieldCommand;
use eTraxis\Application\Command\Fields\UpdateTextFieldCommand;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Application\Dictionary\FieldType
 */
class FieldTypeTest extends TestCase
{
    /**
     * @covers ::getCreateCommand
     */
    public function testGetCreateCommand()
    {
        static::assertSame(CreateCheckboxFieldCommand::class, FieldType::getCreateCommand(FieldType::CHECKBOX));
        static::assertSame(CreateDateFieldCommand::class, FieldType::getCreateCommand(FieldType::DATE));
        static::assertSame(CreateDecimalFieldCommand::class, FieldType::getCreateCommand(FieldType::DECIMAL));
        static::assertSame(CreateDurationFieldCommand::class, FieldType::getCreateCommand(FieldType::DURATION));
        static::assertSame(CreateIssueFieldCommand::class, FieldType::getCreateCommand(FieldType::ISSUE));
        static::assertSame(CreateListFieldCommand::class, FieldType::getCreateCommand(FieldType::LIST));
        static::assertSame(CreateNumberFieldCommand::class, FieldType::getCreateCommand(FieldType::NUMBER));
        static::assertSame(CreateStringFieldCommand::class, FieldType::getCreateCommand(FieldType::STRING));
        static::assertSame(CreateTextFieldCommand::class, FieldType::getCreateCommand(FieldType::TEXT));
    }

    /**
     * @covers ::getUpdateCommand
     */
    public function testGetUpdateCommand()
    {
        static::assertSame(UpdateCheckboxFieldCommand::class, FieldType::getUpdateCommand(FieldType::CHECKBOX));
        static::assertSame(UpdateDateFieldCommand::class, FieldType::getUpdateCommand(FieldType::DATE));
        static::assertSame(UpdateDecimalFieldCommand::class, FieldType::getUpdateCommand(FieldType::DECIMAL));
        static::assertSame(UpdateDurationFieldCommand::class, FieldType::getUpdateCommand(FieldType::DURATION));
        static::assertSame(UpdateIssueFieldCommand::class, FieldType::getUpdateCommand(FieldType::ISSUE));
        static::assertSame(UpdateListFieldCommand::class, FieldType::getUpdateCommand(FieldType::LIST));
        static::assertSame(UpdateNumberFieldCommand::class, FieldType::getUpdateCommand(FieldType::NUMBER));
        static::assertSame(UpdateStringFieldCommand::class, FieldType::getUpdateCommand(FieldType::STRING));
        static::assertSame(UpdateTextFieldCommand::class, FieldType::getUpdateCommand(FieldType::TEXT));
    }
}
