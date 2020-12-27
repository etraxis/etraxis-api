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

namespace eTraxis\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * eTraxis legacy password encoder.
 *
 * As of 3.6.8 passwords were stored as base64-encoded binary SHA1 hashes.
 * For backward compatibility we let user authenticate if his password is stored in a legacy way.
 */
class Sha1PasswordEncoder extends BasePasswordEncoder implements PasswordEncoderInterface
{
    /**
     * {@inheritDoc}
     */
    public function encodePassword(string $raw, ?string $salt = null)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        return base64_encode(sha1($raw, true));
    }

    /**
     * {@inheritDoc}
     */
    public function isPasswordValid(string $encoded, string $raw, ?string $salt = null)
    {
        return !$this->isPasswordTooLong($raw) && $this->comparePasswords($encoded, $this->encodePassword($raw, $salt));
    }

    /**
     * {@inheritDoc}
     */
    public function needsRehash(string $encoded): bool
    {
        return true;
    }
}
