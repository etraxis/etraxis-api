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

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Application\Dictionary\MimeType
 */
class MimeTypeTest extends TestCase
{
    /**
     * @covers ::get
     */
    public function testGet()
    {
        $expected = [
            'application/msword'                                                      => 'ms-word.png',
            'application/octet-stream'                                                => 'unknown.png',
            'application/pdf'                                                         => 'pdf.png',
            'application/vnd.ms-excel'                                                => 'ms-excel.png',
            'application/vnd.ms-excel.sheet.macroEnabled.12'                          => 'ms-excel.png',
            'application/vnd.oasis.opendocument.spreadsheet'                          => 'x-office-spreadsheet.png',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'x-office-spreadsheet.png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'x-office-word.png',
            'application/x-rar-compressed'                                            => 'archive-rar.png',
            'application/x-zip-compressed'                                            => 'archive-zip.png',
            'image/bmp'                                                               => 'image-bmp.png',
            'image/gif'                                                               => 'image-gif.png',
            'image/jpeg'                                                              => 'image-jpeg.png',
            'image/png'                                                               => 'image-png.png',
            'text/html'                                                               => 'text-html.png',
            'text/plain'                                                              => 'text-plain.png',
            'text/x-script.ksh'                                                       => 'text-script.png',
            'text/xml'                                                                => 'text-xml.png',
            'video/mp4'                                                               => 'video.png',
            'video/x-ms-wmv'                                                          => 'video.png',
            'application/etraxis'                                                     => 'unknown.png',
            'audio/etraxis'                                                           => 'audio.png',
            'image/etraxis'                                                           => 'image.png',
            'message/etraxis'                                                         => 'message.png',
            'text/etraxis'                                                            => 'text-plain.png',
            'video/etraxis'                                                           => 'video.png',
        ];

        foreach ($expected as $mime => $file) {
            static::assertSame($file, MimeType::get($mime));
        }
    }

    /**
     * @covers ::has
     */
    public function testHas()
    {
        $expected = [
            'application/msword'                                                      => true,
            'application/octet-stream'                                                => true,
            'application/pdf'                                                         => true,
            'application/vnd.ms-excel'                                                => true,
            'application/vnd.ms-excel.sheet.macroEnabled.12'                          => true,
            'application/vnd.oasis.opendocument.spreadsheet'                          => true,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => true,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
            'application/x-rar-compressed'                                            => true,
            'application/x-zip-compressed'                                            => true,
            'audio/'                                                                  => false,
            'image/'                                                                  => false,
            'image/bmp'                                                               => true,
            'image/gif'                                                               => true,
            'image/jpeg'                                                              => true,
            'image/png'                                                               => true,
            'radio/'                                                                  => false,
            'text/html'                                                               => true,
            'text/plain'                                                              => true,
            'text/x-script.ksh'                                                       => true,
            'text/xml'                                                                => true,
            'video/mp4'                                                               => true,
            'video/x-ms-wmv'                                                          => true,
            'application/etraxis'                                                     => false,
            'audio/etraxis'                                                           => true,
            'image/etraxis'                                                           => true,
            'message/'                                                                => false,
            'message/etraxis'                                                         => true,
            'text/'                                                                   => false,
            'text/etraxis'                                                            => true,
            'video/'                                                                  => false,
            'video/etraxis'                                                           => true,
        ];

        foreach ($expected as $mime => $has) {
            static::assertSame($has, MimeType::has($mime));
        }
    }
}
