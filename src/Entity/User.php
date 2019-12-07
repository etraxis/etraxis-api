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

namespace eTraxis\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * User.
 *
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="eTraxis\Repository\UserRepository")
 * @Assert\UniqueEntity(fields={"email"}, message="user.conflict.email")
 *
 * @property-read int         $id          Unique ID.
 * @property      string      $email       Email address.
 * @property      string      $fullname    Full name.
 * @property      null|string $description Optional description of the user.
 */
class User
{
    use PropertyTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=254, unique=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="fullname", type="string", length=50)
     */
    protected $fullname;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected $description;
}
