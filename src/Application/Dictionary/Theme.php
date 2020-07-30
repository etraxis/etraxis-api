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
 * UI themes.
 */
class Theme extends StaticDictionary
{
    public const FALLBACK = 'azure';

    protected static array $dictionary = [
        'azure'     => 'Azure',
        'emerald'   => 'Emerald',
        'mars'      => 'Mars',
        'nord'      => 'Nord',
        'solarized' => 'Solarized',
    ];
}
