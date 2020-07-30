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
 * Locales.
 */
class Locale extends StaticDictionary
{
    public const FALLBACK = 'en_US';

    protected static array $dictionary = [
        'en_AU' => 'English (Australia)',
        'en_CA' => 'English (Canada)',
        'en_GB' => 'English (Great Britain)',
        'en_NZ' => 'English (New Zealand)',
        'en_US' => 'English (United States)',
        'ru'    => 'Русский',
    ];
}
