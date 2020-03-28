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

namespace eTraxis\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * eTraxis legacy password encoder.
 *
 * Up to version 3.6.7 passwords were stored as MD5 hashes.
 * For backward compatibility we let user authenticate if his password is stored in a legacy way.
 */
class Md5PasswordEncoder extends BasePasswordEncoder implements PasswordEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encodePassword(string $raw, ?string $salt = null)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        return md5($raw);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid(string $encoded, string $raw, ?string $salt = null)
    {
        return !$this->isPasswordTooLong($raw) && $this->comparePasswords($encoded, $this->encodePassword($raw, $salt));
    }

    /**
     * {@inheritdoc}
     */
    public function needsRehash(string $encoded): bool
    {
        return true;
    }
}
