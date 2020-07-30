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
 * Supported account providers.
 */
class AccountProvider extends StaticDictionary
{
    public const FALLBACK = self::ETRAXIS;

    public const ETRAXIS   = 'etraxis';
    public const LDAP      = 'ldap';
    public const GOOGLE    = 'google';
    public const GITHUB    = 'github';
    public const BITBUCKET = 'bitbucket';

    protected static array $dictionary = [
        self::ETRAXIS   => 'eTraxis',
        self::LDAP      => 'LDAP',
        self::GOOGLE    => 'Google',
        self::GITHUB    => 'GitHub',
        self::BITBUCKET => 'Bitbucket',
    ];
}
