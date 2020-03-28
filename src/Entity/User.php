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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Application\Dictionary\Locale;
use eTraxis\Application\Dictionary\Theme;
use eTraxis\Application\Dictionary\Timezone;
use LazySec\Entity\DisableAccountTrait;
use LazySec\Entity\LockAccountTrait;
use LazySec\Entity\UserTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Webinarium\PropertyTrait;

/**
 * User.
 *
 * @ORM\Table(
 *     name="users",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"account_provider", "account_uid"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\UserRepository")
 * @Assert\UniqueEntity(fields={"email"}, message="user.conflict.email")
 *
 * @property-read int         $id          Unique ID.
 * @property      string      $email       Email address.
 * @property      null|string $password    Password.
 * @property      string      $fullname    Full name.
 * @property      null|string $description Optional description of the user.
 * @property      bool        $isAdmin     Whether the user has administrator privileges.
 * @property      AccountInfo $account     User account.
 * @property      string      $locale      User locale (see the "Locale" dictionary).
 * @property      string      $theme       User theme (see the "Theme" dictionary).
 * @property      string      $timezone    User timezone (see the "Timezone" dictionary).
 * @property-read Group[]     $groups      List of groups the user is member of.
 */
class User implements UserInterface
{
    use PropertyTrait;
    use UserTrait;
    use DisableAccountTrait;
    use LockAccountTrait;

    // Roles.
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_USER  = 'ROLE_USER';

    // Constraints.
    public const MAX_EMAIL       = 254;
    public const MAX_FULLNAME    = 50;
    public const MAX_DESCRIPTION = 100;

    // JSON properties.
    public const JSON_ID          = 'id';
    public const JSON_EMAIL       = 'email';
    public const JSON_FULLNAME    = 'fullname';
    public const JSON_DESCRIPTION = 'description';
    public const JSON_ADMIN       = 'admin';
    public const JSON_DISABLED    = 'disabled';
    public const JSON_LOCKED      = 'locked';
    public const JSON_PROVIDER    = 'provider';
    public const JSON_LOCALE      = 'locale';
    public const JSON_THEME       = 'theme';
    public const JSON_TIMEZONE    = 'timezone';

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
     * @ORM\Column(name="password", type="string", nullable=true)
     */
    protected $password;

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

    /**
     * @var string User's role (see "User::ROLE_..." constants).
     *
     * @ORM\Column(name="role", type="string", length=20)
     */
    protected $role;

    /**
     * @var AccountInfo
     *
     * @ORM\Embedded(class="AccountInfo")
     */
    protected $account;

    /**
     * @var array User's settings.
     *
     * @ORM\Column(name="settings", type="json", nullable=true)
     */
    protected $settings;

    /**
     * @var ArrayCollection|Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", mappedBy="membersCollection")
     * @ORM\OrderBy({"name": "ASC", "project": "ASC"})
     */
    protected $groupsCollection;

    /**
     * Creates new user.
     */
    public function __construct()
    {
        $this->role             = self::ROLE_USER;
        $this->account          = new AccountInfo();
        $this->groupsCollection = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return [$this->role];
    }

    /**
     * Checks whether the account is loaded from a 3rd party provider.
     *
     * @return bool
     */
    public function isAccountExternal(): bool
    {
        return $this->account->provider !== AccountProvider::ETRAXIS;
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'isAdmin' => function (): bool {
                return $this->role === self::ROLE_ADMIN;
            },

            'locale' => function (): string {
                return $this->settings['locale'] ?? Locale::FALLBACK;
            },

            'theme' => function (): string {
                return $this->settings['theme'] ?? Theme::FALLBACK;
            },

            'timezone' => function (): string {
                return $this->settings['timezone'] ?? Timezone::FALLBACK;
            },

            'groups' => function (): array {
                return $this->groupsCollection->getValues();
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setters(): array
    {
        return [

            'isAdmin' => function (bool $value): void {
                $this->role = $value ? self::ROLE_ADMIN : self::ROLE_USER;
            },

            'locale' => function (string $value): void {
                if (Locale::has($value)) {
                    $this->settings['locale'] = $value;
                }
            },

            'theme' => function (string $value): void {
                if (Theme::has($value)) {
                    $this->settings['theme'] = $value;
                }
            },

            'timezone' => function (string $value): void {
                if (Timezone::has($value)) {
                    $this->settings['timezone'] = $value;
                }
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccountBeLocked(): bool
    {
        return !$this->isAccountExternal();
    }
}
