<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Application\Query\Issues;

use eTraxis\Application\Query\AbstractCollectionQuery;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Returns a collection of issue dependencies.
 *
 * @property int $issue Issue ID.
 */
class GetDependenciesQuery extends AbstractCollectionQuery
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public ?int $issue;
}
