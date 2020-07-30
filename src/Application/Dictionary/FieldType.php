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

namespace eTraxis\Application\Dictionary;

use Dictionary\StaticDictionary;
use eTraxis\Application\Command\Fields as Command;

/**
 * Field types.
 */
class FieldType extends StaticDictionary
{
    public const CHECKBOX = 'checkbox';
    public const DATE     = 'date';
    public const DECIMAL  = 'decimal';
    public const DURATION = 'duration';
    public const ISSUE    = 'issue';
    public const LIST     = 'list';
    public const NUMBER   = 'number';
    public const STRING   = 'string';
    public const TEXT     = 'text';

    protected static array $dictionary = [
        self::CHECKBOX => 'field.type.checkbox',
        self::DATE     => 'field.type.date',
        self::DECIMAL  => 'field.type.decimal',
        self::DURATION => 'field.type.duration',
        self::ISSUE    => 'field.type.issue',
        self::LIST     => 'field.type.list',
        self::NUMBER   => 'field.type.number',
        self::STRING   => 'field.type.string',
        self::TEXT     => 'field.type.text',
    ];

    /**
     * Returns class name of CreateField command that corresponds to specified field type.
     *
     * @param null|string $type
     *
     * @return null|string
     */
    public static function getCreateCommand(?string $type): ?string
    {
        $commands = [
            self::CHECKBOX => Command\CreateCheckboxFieldCommand::class,
            self::DATE     => Command\CreateDateFieldCommand::class,
            self::DECIMAL  => Command\CreateDecimalFieldCommand::class,
            self::DURATION => Command\CreateDurationFieldCommand::class,
            self::ISSUE    => Command\CreateIssueFieldCommand::class,
            self::LIST     => Command\CreateListFieldCommand::class,
            self::NUMBER   => Command\CreateNumberFieldCommand::class,
            self::STRING   => Command\CreateStringFieldCommand::class,
            self::TEXT     => Command\CreateTextFieldCommand::class,
        ];

        return $commands[$type] ?? null;
    }

    /**
     * Returns class name of UpdateField command that corresponds to specified field type.
     *
     * @param null|string $type
     *
     * @return null|string
     */
    public static function getUpdateCommand(?string $type): ?string
    {
        $commands = [
            self::CHECKBOX => Command\UpdateCheckboxFieldCommand::class,
            self::DATE     => Command\UpdateDateFieldCommand::class,
            self::DECIMAL  => Command\UpdateDecimalFieldCommand::class,
            self::DURATION => Command\UpdateDurationFieldCommand::class,
            self::ISSUE    => Command\UpdateIssueFieldCommand::class,
            self::LIST     => Command\UpdateListFieldCommand::class,
            self::NUMBER   => Command\UpdateNumberFieldCommand::class,
            self::STRING   => Command\UpdateStringFieldCommand::class,
            self::TEXT     => Command\UpdateTextFieldCommand::class,
        ];

        return $commands[$type] ?? null;
    }
}
