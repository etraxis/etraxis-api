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
 * System roles.
 */
class SystemRole extends StaticDictionary
{
    public const ANYONE      = 'anyone';        // any authenticated user
    public const AUTHOR      = 'author';        // creator of the issue
    public const RESPONSIBLE = 'responsible';   // user assigned to the issue

    protected static array $dictionary = [
        self::ANYONE      => 'role.any',
        self::AUTHOR      => 'role.author',
        self::RESPONSIBLE => 'role.responsible',
    ];
}
