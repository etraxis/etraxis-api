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
    public const FALLBACK = 'en';

    protected static $dictionary = [
        'en' => 'English',
        'ru' => 'Русский',
    ];
}
