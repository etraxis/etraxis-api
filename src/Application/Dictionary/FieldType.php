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

    protected static $dictionary = [
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
}
