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
 * Issue dependencies.
 *
 * @ORM\Table(name="dependencies")
 * @ORM\Entity(repositoryClass="eTraxis\Repository\DependencyRepository")
 *
 * @property-read Issue $issue      Dependant issue.
 * @property-read Issue $dependency Dependency issue.
 */
class Dependency
{
    use PropertyTrait;

    /**
     * @var Issue
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Issue", inversedBy="dependenciesCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="issue_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $issue;

    /**
     * @var Issue
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Issue")
     * @ORM\JoinColumn(name="dependency_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $dependency;

    /**
     * Creates new dependency.
     *
     * @param Issue $issue
     * @param Issue $dependency
     */
    public function __construct(Issue $issue, Issue $dependency)
    {
        $this->issue      = $issue;
        $this->dependency = $dependency;
    }
}
