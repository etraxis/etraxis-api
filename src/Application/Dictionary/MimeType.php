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

namespace eTraxis\Application\Dictionary;

use Dictionary\StaticDictionary;

/**
 * MIME types.
 */
class MimeType extends StaticDictionary
{
    public const FALLBACK = 'application/octet-stream';

    protected static array $dictionary = [
        self::FALLBACK                                                       => 'unknown.png',

        // application
        'application/ecmascript'                                             => 'text-javascript.png',
        'application/font-sfnt'                                              => 'font.png',
        'application/font-tdpfr'                                             => 'font.png',
        'application/font-woff'                                              => 'font.png',
        'application/gzip'                                                   => 'archive-gzip.png',
        'application/java-archive'                                           => 'archive-jar.png',
        'application/javascript'                                             => 'text-javascript.png',
        'application/json'                                                   => 'text-javascript.png',
        'application/msword'                                                 => 'ms-word.png',
        'application/pdf'                                                    => 'pdf.png',
        'application/pgp-encrypted'                                          => 'pgp.png',
        'application/pgp-keys'                                               => 'pgp.png',
        'application/pgp-signature'                                          => 'pgp.png',
        'application/postscript'                                             => 'postscript.png',
        'application/rar'                                                    => 'archive-rar.png',
        'application/vnd\.adobe\.flash-movie'                                => 'flash.png',
        'application/vnd\.debian\.binary-package'                            => 'deb.png',
        'application/vnd\.font-fontforge-sfd'                                => 'font.png',
        'application/vnd\.microsoft\.portable-executable'                    => 'ms-executable.png',
        'application/vnd\.ms-excel(.*)'                                      => 'ms-excel.png',
        'application/vnd\.ms-fontobject'                                     => 'font.png',
        'application/vnd\.ms-powerpoint(.*)'                                 => 'ms-powerpoint.png',
        'application/vnd\.ms-word(.*)'                                       => 'ms-word.png',
        'application/vnd\.oasis\.opendocument\.formula(.*)'                  => 'oasis-formula.png',
        'application/vnd\.oasis\.opendocument\.graphics(.*)'                 => 'x-office-drawing.png',
        'application/vnd\.oasis\.opendocument\.presentation(.*)'             => 'x-office-presentation.png',
        'application/vnd\.oasis\.opendocument\.spreadsheet(.*)'              => 'x-office-spreadsheet.png',
        'application/vnd\.oasis\.opendocument\.text(.*)'                     => 'x-office-word.png',
        'application/vnd\.openxmlformats-officedocument\.drawing(.*)'        => 'x-office-drawing.png',
        'application/vnd\.openxmlformats-officedocument\.presentation(.*)'   => 'x-office-presentation.png',
        'application/vnd\.openxmlformats-officedocument\.spreadsheet(.*)'    => 'x-office-spreadsheet.png',
        'application/vnd\.openxmlformats-officedocument\.wordprocessing(.*)' => 'x-office-word.png',
        'application/vnd\.scribus'                                           => 'scribus.png',
        'application/x-7z-compressed'                                        => 'archive-7zip.png',
        'application/x-ace-compressed'                                       => 'archive-ace.png',
        'application/x-bittorrent'                                           => 'bittorrent.png',
        'application/x-csh'                                                  => 'text-script.png',
        'application/x-debian-package'                                       => 'deb.png',
        'application/x-executable'                                           => 'ms-executable.png',
        'application/x-font'                                                 => 'font.png',
        'application/x-font-pcf'                                             => 'font.png',
        'application/x-gtar'                                                 => 'archive.png',
        'application/x-gtar-compressed'                                      => 'archive.png',
        'application/xhtml+xml'                                              => 'text-xhtml.png',
        'application/x-httpd-eruby'                                          => 'text-ruby.png',
        'application/x-httpd-php'                                            => 'text-php.png',
        'application/x-httpd-php-source'                                     => 'text-php.png',
        'application/x-httpd-php3'                                           => 'text-php.png',
        'application/x-httpd-php3-preprocessed'                              => 'text-php.png',
        'application/x-httpd-php4'                                           => 'text-php.png',
        'application/x-httpd-php5'                                           => 'text-php.png',
        'application/x-iso9660-image'                                        => 'iso.png',
        'application/x-lzh'                                                  => 'archive.png',
        'application/x-lzh-compressed'                                       => 'archive.png',
        'application/x-lzip'                                                 => 'archive.png',
        'application/xml'                                                    => 'text-xml.png',
        'application/x-ms-application'                                       => 'ms-executable.png',
        'application/x-msdos-program'                                        => 'ms-executable.png',
        'application/x-msi'                                                  => 'ms-install.png',
        'application/x-rar-compressed'                                       => 'archive-rar.png',
        'application/x-redhat-package-manager'                               => 'rpm.png',
        'application/x-rss+xml'                                              => 'rss.png',
        'application/x-sh'                                                   => 'text-script.png',
        'application/x-shockwave-flash'                                      => 'flash.png',
        'application/x-sql'                                                  => 'text-sql.png',
        'application/x-tar'                                                  => 'archive.png',
        'application/x-tcl'                                                  => 'text-script.png',
        'application/x-trash'                                                => 'text-bak.png',
        'application/x-xcf'                                                  => 'image-xcf.png',
        'application/x-zip-compressed'                                       => 'archive-zip.png',
        'application/zip'                                                    => 'archive-zip.png',

        // audio
        'audio/mpeg3'                                                        => 'audio-mp3.png',
        'audio/mpeg4-generic'                                                => 'audio-mp3.png',
        'audio/mpeg'                                                         => 'audio-mp3.png',
        'audio/ogg'                                                          => 'audio-ogg.png',
        'audio/wav'                                                          => 'audio-wav.png',
        'audio/x-mpeg-3'                                                     => 'audio-mp3.png',
        'audio/x-mpequrl'                                                    => 'audio-playlist.png',
        'audio/x-ms-wma'                                                     => 'audio-wma.png',
        'audio/x-wav'                                                        => 'audio-wav.png',

        // image
        'image/bmp'                                                          => 'image-bmp.png',
        'image/gif'                                                          => 'image-gif.png',
        'image/jpeg'                                                         => 'image-jpeg.png',
        'image/png'                                                          => 'image-png.png',
        'image/tiff'                                                         => 'image-tiff.png',
        'image/vnd\.adobe\.photoshop'                                        => 'photoshop.png',
        'image/vnd\.microsoft\.icon'                                         => 'image-ico.png',
        'image/x-icon'                                                       => 'image-ico.png',
        'image/x-ms-bmp'                                                     => 'image-bmp.png',
        'image/x-photoshop'                                                  => 'photoshop.png',
        'image/x-tiff'                                                       => 'image-tiff.png',
        'image/x-windows-bmp'                                                => 'image-bmp.png',

        // text
        'text/calendar'                                                      => 'text-vcalendar.png',
        'text/css'                                                           => 'text-css.png',
        'text/csv'                                                           => 'x-office-spreadsheet.png',
        'text/ecmascript'                                                    => 'text-javascript.png',
        'text/html'                                                          => 'text-html.png',
        'text/javascript'                                                    => 'text-javascript.png',
        'text/pascal'                                                        => 'text-source.png',
        'text/plain'                                                         => 'text-plain.png',
        'text/richtext'                                                      => 'text-richtext.png',
        'text/rtf'                                                           => 'text-richtext.png',
        'text/vnd\.rn-realtext'                                              => 'text-richtext.png',
        'text/x-bibtex'                                                      => 'text-bibtex.png',
        'text/x-c'                                                           => 'text-c.png',
        'text/x-c++hdr'                                                      => 'text-hpp.png',
        'text/x-c++src'                                                      => 'text-cpp.png',
        'text/x-chdr'                                                        => 'text-h.png',
        'text/x-csh'                                                         => 'text-script.png',
        'text/x-csrc'                                                        => 'text-c.png',
        'text/x-h'                                                           => 'text-h.png',
        'text/x-haskell'                                                     => 'text-source.png',
        'text/x-java'                                                        => 'text-java.png',
        'text/x-java-source'                                                 => 'text-java.png',
        'text/x-makefile'                                                    => 'text-makefile.png',
        'text/x-pascal'                                                      => 'text-source.png',
        'text/x-perl'                                                        => 'text-source.png',
        'text/x-python'                                                      => 'text-python.png',
        'text/x-scala'                                                       => 'text-source.png',
        'text/x-script(.*)'                                                  => 'text-script.png',
        'text/x-sh'                                                          => 'text-script.png',
        'text/x-tcl'                                                         => 'text-script.png',
        'text/x-tex'                                                         => 'text-tex.png',
        'text/x-vcalendar'                                                   => 'text-vcalendar.png',
        'text/xml'                                                           => 'text-xml.png',

        // generic
        'audio/(.+)'                                                         => 'audio.png',
        'image/(.+)'                                                         => 'image.png',
        'message/(.+)'                                                       => 'message.png',
        'text/(.+)'                                                          => 'text-plain.png',
        'video/(.+)'                                                         => 'video.png',
    ];

    /**
     * {@inheritDoc}
     */
    public static function get($key)
    {
        foreach (static::$dictionary as $pattern => $value) {
            if (preg_match("!{$pattern}!isu", $key) === 1) {
                return $value;
            }
        }

        return static::$dictionary[static::FALLBACK];
    }

    /**
     * {@inheritDoc}
     */
    public static function has($key): bool
    {
        foreach (static::$dictionary as $pattern => $value) {
            if (preg_match("!{$pattern}!isu", $key) === 1) {
                return true;
            }
        }

        return false;
    }
}
