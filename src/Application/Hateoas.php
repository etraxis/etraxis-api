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

namespace eTraxis\Application;

/**
 * Assorted HATEOAS constants.
 */
class Hateoas
{
    // Serialization modes.
    public const MODE           = 'hateoas_mode';
    public const MODE_SELF_ONLY = 'self_only';
    public const MODE_RECURSIVE = 'recursive';

    // Supported formats.
    public const FORMAT_JSON = 'json';

    // JSON properties.
    public const LINKS         = 'links';
    public const SELF          = 'self';
    public const LINK_RELATION = 'rel';
    public const LINK_HREF     = 'href';
    public const LINK_TYPE     = 'type';
}
