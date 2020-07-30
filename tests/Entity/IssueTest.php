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

use eTraxis\Application\Dictionary\StateType;
use eTraxis\Application\Seconds;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\Issue
 */
class IssueTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);

        $createdAt = $this->getProperty($issue, 'createdAt');
        $changedAt = $this->getProperty($issue, 'changedAt');

        self::assertSame($user, $issue->author);
        self::assertNull($issue->origin);
        self::assertLessThanOrEqual(2, time() - $createdAt);
        self::assertSame($createdAt, $changedAt);

        $clone = new Issue($user, $issue);

        self::assertSame($issue, $clone->origin);
    }

    /**
     * @covers ::touch
     */
    public function testTouch()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $issue = new Issue(new User());
        $this->setProperty($issue, 'changedAt', 0);

        $changedAt = $this->getProperty($issue, 'changedAt');
        self::assertGreaterThan(2, time() - $changedAt);

        $issue->touch();

        $changedAt = $this->getProperty($issue, 'changedAt');
        self::assertLessThanOrEqual(2, time() - $changedAt);
    }

    /**
     * @covers ::getters
     */
    public function testFullId()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);
        $this->setProperty($template, 'prefix', 'bug');

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $issue = new Issue(new User());

        $issue->state = $state;

        $this->setProperty($issue, 'id', 4);
        self::assertSame('bug-004', $issue->fullId);

        $this->setProperty($issue, 'id', 1234);
        self::assertSame('bug-1234', $issue->fullId);
    }

    /**
     * @covers ::getters
     */
    public function testProject()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $issue = new Issue(new User());

        $issue->state = $state;

        self::assertSame($project, $issue->project);
    }

    /**
     * @covers ::getters
     */
    public function testTemplate()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $issue = new Issue(new User());

        $issue->state = $state;

        self::assertSame($template, $issue->template);
    }

    /**
     * @covers ::setters
     */
    public function testState()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 3);

        $final = new State($template, StateType::FINAL);
        $this->setProperty($final, 'id', 4);

        $issue = new Issue(new User());

        $issue->state = $initial;
        self::assertSame($initial, $issue->state);

        $issue->state = $final;
        self::assertSame($final, $issue->state);
    }

    /**
     * @covers ::setters
     */
    public function testStateException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown state: bar');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $template2 = new Template($project);
        $this->setProperty($template2, 'id', 3);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 4);
        $this->setProperty($state, 'name', 'foo');

        $state2 = new State($template2, StateType::FINAL);
        $this->setProperty($state2, 'id', 5);
        $this->setProperty($state2, 'name', 'bar');

        $issue = new Issue(new User());

        $issue->state = $state;
        $issue->state = $state2;
    }

    /**
     * @covers ::getters
     */
    public function testAge()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $issue = new Issue(new User());

        $issue->state = $state;

        $this->setProperty($issue, 'createdAt', time() - 86401);
        self::assertSame(2, $issue->age);
    }

    /**
     * @covers ::getters
     */
    public function testIsCloned()
    {
        $issue = new Issue(new User());
        $clone = new Issue(new User(), $issue);

        self::assertFalse($issue->isCloned);
        self::assertTrue($clone->isCloned);
    }

    /**
     * @covers ::getters
     */
    public function testIsCritical()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);
        $template->criticalAge = 1;

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 3);

        $final = new State($template, StateType::FINAL);
        $this->setProperty($final, 'id', 4);

        $issue = new Issue(new User());

        $issue->state = $initial;
        self::assertFalse($issue->isCritical);

        $this->setProperty($issue, 'createdAt', time() - 86401);
        self::assertTrue($issue->isCritical);

        $issue->state = $final;
        self::assertFalse($issue->isCritical);
    }

    /**
     * @covers ::getters
     */
    public function testIsFrozen()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 3);

        $final = new State($template, StateType::FINAL);
        $this->setProperty($final, 'id', 4);

        $issue = new Issue(new User());

        $issue->state = $final;
        $this->setProperty($issue, 'closedAt', time() - 86401);

        $template->frozenTime = null;
        self::assertFalse($issue->isFrozen);

        $template->frozenTime = 1;
        self::assertTrue($issue->isFrozen);

        $issue->state = $initial;
        self::assertFalse($issue->isFrozen);
    }

    /**
     * @covers ::getters
     */
    public function testIsClosed()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 3);

        $final = new State($template, StateType::FINAL);
        $this->setProperty($final, 'id', 4);

        $issue = new Issue(new User());
        self::assertFalse($issue->isClosed);

        $issue->state = $initial;
        self::assertFalse($issue->isClosed);

        $issue->state = $final;
        self::assertTrue($issue->isClosed);

        $issue->state = $initial;
        self::assertFalse($issue->isClosed);
    }

    /**
     * @covers ::getters
     * @covers ::resume
     * @covers ::suspend
     */
    public function testIsSuspended()
    {
        $issue = new Issue(new User());
        self::assertFalse($issue->isSuspended);

        $issue->suspend(time() + Seconds::ONE_DAY);
        self::assertTrue($issue->isSuspended);

        $issue->resume();
        self::assertFalse($issue->isSuspended);

        $issue->suspend(time());
        self::assertFalse($issue->isSuspended);
    }

    /**
     * @covers ::getters
     */
    public function testEvents()
    {
        $issue = new Issue(new User());
        self::assertSame([], $issue->events);

        /** @var \Doctrine\Common\Collections\Collection $events */
        $events = $this->getProperty($issue, 'eventsCollection');
        $events->add('Event A');
        $events->add('Event B');

        self::assertSame(['Event A', 'Event B'], $issue->events);
    }

    /**
     * @covers ::getters
     */
    public function testValues()
    {
        $issue = new Issue(new User());
        self::assertSame([], $issue->values);

        /** @var \Doctrine\Common\Collections\Collection $values */
        $values = $this->getProperty($issue, 'valuesCollection');
        $values->add('Value A');
        $values->add('Value B');

        self::assertSame(['Value A', 'Value B'], $issue->values);
    }

    /**
     * @covers ::getters
     */
    public function testDependencies()
    {
        $issue = new Issue(new User());
        $this->setProperty($issue, 'id', 1);
        self::assertSame([], $issue->values);

        $issue1 = new Issue(new User());
        $this->setProperty($issue1, 'id', 2);

        $issue2 = new Issue(new User());
        $this->setProperty($issue2, 'id', 3);

        $dependency1 = new Dependency($issue, $issue1);
        $dependency2 = new Dependency($issue, $issue2);

        /** @var \Doctrine\Common\Collections\Collection $values */
        $values = $this->getProperty($issue, 'dependenciesCollection');
        $values->add($dependency1);
        $values->add($dependency2);

        self::assertSame([$issue1, $issue2], $issue->dependencies);
    }
}
