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
 * Timezones.
 */
class Timezone extends StaticDictionary
{
    public const FALLBACK = 'UTC';

    /**
     * {@inheritdoc}
     */
    protected static function dictionary()
    {
        $timezones = timezone_identifiers_list();

        return $timezones !== false ? array_combine($timezones, $timezones) : [];
    }
}
