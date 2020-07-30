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
 * Field permissions.
 */
class FieldPermission extends StaticDictionary
{
    public const READ_ONLY  = 'R';
    public const READ_WRITE = 'RW';

    protected static array $dictionary = [
        self::READ_ONLY  => 'field.permission.read_only',
        self::READ_WRITE => 'field.permission.read_write',
    ];
}
