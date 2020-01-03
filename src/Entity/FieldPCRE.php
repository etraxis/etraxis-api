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
use Webinarium\PropertyTrait;

/**
 * Field embedded "PCRE" options.
 *
 * @ORM\Embeddable
 *
 * @property null|string $check   Perl-compatible regular expression which values of the field must conform to.
 * @property null|string $search  Perl-compatible regular expression to modify values of the field before display them (search for).
 * @property null|string $replace Perl-compatible regular expression to modify values of the field before display them (replace with).
 */
class FieldPCRE implements \JsonSerializable
{
    use PropertyTrait;

    // Constraints.
    public const MAX_PCRE = 500;

    // JSON properties.
    public const JSON_CHECK   = 'check';
    public const JSON_SEARCH  = 'search';
    public const JSON_REPLACE = 'replace';

    /**
     * @var string
     *
     * @ORM\Column(name="check", type="string", length=500, nullable=true)
     */
    protected $check;

    /**
     * @var string
     *
     * @ORM\Column(name="search", type="string", length=500, nullable=true)
     */
    protected $search;

    /**
     * @var string
     *
     * @ORM\Column(name="replace", type="string", length=500, nullable=true)
     */
    protected $replace;

    /**
     * Checks whether specified value conforms to current PCRE configuration.
     *
     * @param null|string $value
     *
     * @return bool
     */
    public function validate(?string $value): bool
    {
        return preg_match("/{$this->check}/isu", $value) === 1;
    }

    /**
     * Updates specified value in accordance with current PCRE configuration.
     *
     * @param null|string $value
     *
     * @return null|string
     */
    public function transform(?string $value): ?string
    {
        if (mb_strlen($this->search) === 0 || mb_strlen($this->replace) === 0) {
            return $value;
        }

        return preg_replace("/{$this->search}/isu", $this->replace, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_CHECK   => $this->check,
            self::JSON_SEARCH  => $this->search,
            self::JSON_REPLACE => $this->replace,
        ];
    }
}
