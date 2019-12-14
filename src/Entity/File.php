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
use eTraxis\Application\Dictionary\MimeType;
use Ramsey\Uuid\Uuid;
use Webinarium\PropertyTrait;

/**
 * Attached file.
 *
 * @ORM\Table(
 *     name="files",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"event_id"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\FileRepository")
 *
 * @property-read int    $id        Unique ID.
 * @property-read Issue  $issue     Issue of the file.
 * @property-read Event  $event     Event which the file has been attached by.
 * @property-read string $name      File name.
 * @property-read int    $size      File size.
 * @property-read string $type      MIME type (see the "MimeType" dictionary).
 * @property-read string $uuid      Unique UUID for storage.
 * @property-read bool   $isRemoved Whether the file is removed (soft-deleted).
 */
class File
{
    use PropertyTrait;

    // Constraints.
    public const MAX_NAME = 100;
    public const MAX_TYPE = 255;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(name="event_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $event;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=100)
     */
    protected $name;

    /**
     * @var int
     *
     * @ORM\Column(name="filesize", type="integer")
     */
    protected $size;

    /**
     * @var string
     *
     * @ORM\Column(name="mimetype", type="string", length=255)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="uuid", type="string", length=32)
     */
    protected $uuid;

    /**
     * @var int Unix Epoch timestamp when the file has been removed (NULL while file is present).
     *
     * @ORM\Column(name="removed_at", type="integer", nullable=true)
     */
    protected $removedAt;

    /**
     * Creates file.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param Event  $event
     * @param string $name
     * @param int    $size
     * @param string $type
     */
    public function __construct(Event $event, string $name, int $size, string $type)
    {
        $this->event = $event;
        $this->name  = $name;
        $this->size  = $size;

        $this->type = MimeType::has($type) ? $type : MimeType::FALLBACK;

        $this->uuid = Uuid::uuid4()->getHex();
    }

    /**
     * Marks file as removed (soft-deleted).
     */
    public function remove(): void
    {
        if ($this->removedAt === null) {
            $this->removedAt = time();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'issue' => function (): Issue {
                return $this->event->issue;
            },

            'isRemoved' => function (): bool {
                return $this->removedAt !== null;
            },
        ];
    }
}
