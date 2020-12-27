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

namespace eTraxis\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\CachedRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * @coversDefaultClass \eTraxis\Repository\CachedRepositoryTrait
 */
class CachedRepositoryTraitTest extends TransactionalTestCase
{
    private ServiceEntityRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $doctrine = $this->doctrine;

        $this->repository = new class($doctrine) extends ServiceEntityRepository implements CachedRepositoryInterface {
            use CachedRepositoryTrait;

            private int $calls = 0;

            public function __construct(ManagerRegistry $registry)
            {
                parent::__construct($registry, User::class);
            }

            public function getCache(): CacheInterface
            {
                return $this->cache;
            }

            public function getCalls(): int
            {
                return $this->calls;
            }

            public function find($id, $lockMode = null, $lockVersion = null)
            {
                return $this->findInCache($id, function ($id) {
                    $this->calls++;

                    return parent::find($id);
                });
            }

            public function delete($id): bool
            {
                return $this->deleteFromCache($id);
            }
        };
    }

    /**
     * @covers ::initCache
     * @covers ::warmup
     */
    public function testWarmup()
    {
        /** @var User $user1 */
        $user1 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var User $user2 */
        $user2 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        /** @var User $user3 */
        $user3 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'einstein@ldap.forumsys.com']);

        static::assertSame(2, $this->repository->warmup([
            self::UNKNOWN_ENTITY_ID,
            $user1->id,
            $user2->id,
        ]));

        static::assertSame(0, $this->repository->getCalls());

        $first = $this->repository->find($user1->id);
        static::assertSame($user1, $first);
        static::assertSame(0, $this->repository->getCalls());

        $second = $this->repository->find($user2->id);
        static::assertSame($user2, $second);
        static::assertSame(0, $this->repository->getCalls());

        $third = $this->repository->find($user3->id);
        static::assertSame($user3, $third);
        static::assertSame(1, $this->repository->getCalls());
    }

    /**
     * @covers ::findInCache
     * @covers ::initCache
     */
    public function testFindInCache()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        static::assertNull($this->repository->find(null));
        static::assertSame(0, $this->repository->getCalls());

        $first = $this->repository->find($user->id);
        static::assertSame($user, $first);
        static::assertSame(1, $this->repository->getCalls());

        $second = $this->repository->find($user->id);
        static::assertSame($user, $second);
        static::assertSame(1, $this->repository->getCalls());

        $asArray = $this->repository->find([$user->id]);
        static::assertSame($user, $asArray);
        static::assertSame(1, $this->repository->getCalls());
    }

    /**
     * @covers ::deleteFromCache
     * @covers ::initCache
     */
    public function testDeleteFromCache()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        static::assertSame(0, $this->repository->getCalls());

        $first = $this->repository->find($user->id);
        static::assertSame($user, $first);
        static::assertSame(1, $this->repository->getCalls());

        static::assertFalse($this->repository->delete(null));
        static::assertTrue($this->repository->delete($user->id));

        $second = $this->repository->find($user->id);
        static::assertSame($user, $second);
        static::assertSame(2, $this->repository->getCalls());
    }
}
