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

    // JSON properties.
    public const JSON_ID        = 'id';
    public const JSON_USER      = 'user';
    public const JSON_TIMESTAMP = 'timestamp';
    public const JSON_NAME      = 'name';
    public const JSON_SIZE      = 'size';
    public const JSON_TYPE      = 'type';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(name="event_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected Event $event;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=100)
     */
    protected string $name;

    /**
     * @var int
     *
     * @ORM\Column(name="filesize", type="integer")
     */
    protected int $size;

    /**
     * @var string
     *
     * @ORM\Column(name="mimetype", type="string", length=255)
     */
    protected string $type;

    /**
     * @var string
     *
     * @ORM\Column(name="uuid", type="string", length=32)
     */
    protected string $uuid;

    /**
     * @var null|int Unix Epoch timestamp when the file has been removed (NULL while file is present).
     *
     * @ORM\Column(name="removed_at", type="integer", nullable=true)
     */
    protected ?int $removedAt = null;

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

        $this->uuid = Uuid::uuid4()->getHex()->toString();
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
            'issue'     => fn (): Issue => $this->event->issue,
            'isRemoved' => fn (): bool => $this->removedAt !== null,
        ];
    }
}
