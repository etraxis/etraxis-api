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

namespace eTraxis\Repository;

use eTraxis\Entity\File;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\FileRepository
 */
class FileRepositoryTest extends WebTestCase
{
    /**
     * @var Contracts\FileRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(File::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(FileRepository::class, $this->repository);
    }

    /**
     * @covers ::getFullPath
     */
    public function testFullPath()
    {
        /** @var File $file */
        [$file] = $this->repository->findAll();

        $expected = getcwd() . \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . $file->uuid;

        self::assertSame($expected, $this->repository->getFullPath($file));
    }
}
