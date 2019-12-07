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

namespace eTraxis\Repository\Contracts;

use Doctrine\Common\Collections\Selectable;
use LazySec\Repository\UserRepositoryInterface as LazySecUserRepositoryInterface;

/**
 * Interface to the 'User' entities repository.
 */
interface UserRepositoryInterface extends LazySecUserRepositoryInterface, Selectable
{
}
