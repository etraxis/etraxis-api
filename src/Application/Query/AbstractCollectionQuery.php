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

namespace eTraxis\Application\Query;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract query for a collection of entities.
 *
 * @property int         $offset Zero-based index of the first entity to return.
 * @property int         $limit  Maximum number of entities to return.
 * @property null|string $search Optional search value.
 * @property array       $filter Array of property filters (keys are property names, values are filtering values).
 * @property array       $sort   Sorting specification (keys are property names, values are "asc" or "desc").
 */
abstract class AbstractCollectionQuery
{
    // Sorting directions.
    public const SORT_ASC  = 'ASC';
    public const SORT_DESC = 'DESC';

    // Restrictions.
    public const MAX_LIMIT = 100;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\GreaterThanOrEqual(value="0")
     */
    public int $offset;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Range(min="1", max="100")
     */
    public int $limit;

    /**
     * @var null|string
     */
    public ?string $search;

    /**
     * @var array
     *
     * @Assert\NotNull
     * @Assert\Type("array")
     */
    public array $filter;

    /**
     * @var array
     *
     * @Assert\NotNull
     * @Assert\Type("array")
     */
    public array $sort;

    /**
     * Retrieves and sanitizes 'offset', 'limit' and related headers from specified request.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $offset = (int) $request->get('offset', 0);
        $limit  = (int) $request->get('limit', self::MAX_LIMIT);

        $this->offset = max(0, $offset);
        $this->limit  = max(1, min($limit, self::MAX_LIMIT));

        $this->search = $request->headers->get('X-Search');
        $this->filter = json_decode($request->headers->get('X-Filter'), true) ?? [];
        $this->sort   = json_decode($request->headers->get('X-Sort'), true)   ?? [];

        if (is_string($this->search)) {
            $this->search = urldecode($this->search);
        }

        array_walk($this->filter, function (&$value) {
            if (is_string($value)) {
                $value = urldecode($value);
            }
        });
    }
}
