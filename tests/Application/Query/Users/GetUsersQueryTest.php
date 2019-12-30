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

namespace eTraxis\Application\Query\Users;

use eTraxis\Application\Dictionary\AccountProvider;
use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Users\Handler\GetUsersHandler
 */
class GetUsersQueryTest extends WebTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);

        $repository = $this->doctrine->getRepository(User::class);

        $expected = array_map(function (User $user) {
            return $user->fullname;
        }, $repository->findAll());

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset()
    {
        $expected = [
            'Nikko Hills',
            'Ted Berge',
            'Tony Buckridge',
            'Tracy Marquardt',
            'Vida Parker',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 30;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(30, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            'Albert Einstein',
            'Alyson Schinner',
            'Anissa Marvin',
            'Ansel Koepp',
            'Artem Rodygin',
            'Bell Kemmer',
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Carson Legros',
            'Carter Batz',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch()
    {
        $expected = [
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Dangelo Hill',
            'Derrick Tillman',
            'Dorcas Ernser',
            'Emmanuelle Bartell',
            'Hunter Stroman',
            'Jarrell Kiehn',
            'Joe Gutmann',
            'Juanita Goodwin',
            'Leland Doyle',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;
        $query->search = 'mAn';

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(10, $collection->to);
        self::assertSame(11, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByEmail()
    {
        $expected = [
            'Berenice O\'Connell',
            'Lucas O\'Connell',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_EMAIL => 'oCoNNel',
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByFullname()
    {
        $expected = [
            'Berenice O\'Connell',
            'Lucas O\'Connell',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_FULLNAME => 'o\'cONneL',
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescription()
    {
        $expected = [
            'Bell Kemmer',
            'Carter Batz',
            'Jarrell Kiehn',
            'Kailyn Bahringer',
            'Kyla Schultz',
            'Nikko Hills',
            'Tony Buckridge',
            'Tracy Marquardt',
            'Vida Parker',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_DESCRIPTION => 'sUPpOrT',
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByAdmin()
    {
        $expected = [
            'eTraxis Admin',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_ADMIN => User::ROLE_ADMIN,
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDisabled()
    {
        $expected = [
            'Ted Berge',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_DISABLED => true,
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByLockedOn()
    {
        $expected = [
            'Joe Gutmann',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_LOCKED => true,
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByLockedOff()
    {
        $expected = [
            'Albert Einstein',
            'Alyson Schinner',
            'Anissa Marvin',
            'Ansel Koepp',
            'Artem Rodygin',
            'Bell Kemmer',
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Carson Legros',
            'Carter Batz',
            'Christy McDermott',
            'Dangelo Hill',
            'Denis Murazik',
            'Dennis Quigley',
            'Derrick Tillman',
            'Dorcas Ernser',
            'Emmanuelle Bartell',
            'eTraxis Admin',
            'Francesca Dooley',
            'Hunter Stroman',
            'Jarrell Kiehn',
            'Jeramy Mueller',
            //'Joe Gutmann',    <- this one is locked
            'Juanita Goodwin',
            'Kailyn Bahringer',
            'Kyla Schultz',
            'Leland Doyle',
            'Lola Abshire',
            'Lucas O\'Connell',
            'Millie Bogisich',
            'Nikko Hills',
            'Ted Berge',
            'Tony Buckridge',
            'Tracy Marquardt',
            'Vida Parker',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_LOCKED => false,
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(33, $collection->to);
        self::assertSame(34, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProvider()
    {
        $expected = [
            'Albert Einstein',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_PROVIDER => AccountProvider::LDAP,
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testCombinedFilter()
    {
        $expected = [
            'Bell Kemmer',
            'Berenice O\'Connell',
            'Dorcas Ernser',
            'Jeramy Mueller',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetUsersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_EMAIL       => 'eR',
            User::JSON_FULLNAME    => '',
            User::JSON_DESCRIPTION => 'a+',
        ];

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByEmail()
    {
        $expected = [
            ['eTraxis Admin',       'Built-in administrator'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Anissa Marvin',       'Developer B'],
            ['Artem Rodygin',       null],
            ['Alyson Schinner',     'Client B'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Bell Kemmer',         'Support Engineer A+B'],
            ['Carolyn Hill',        'Manager B+C'],
            ['Carter Batz',         'Support Engineer A+C'],
            ['Christy McDermott',   'Developer A'],
            ['Carson Legros',       'Client A+B'],
            ['Dangelo Hill',        'Manager A'],
            ['Denis Murazik',       'Client C'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Derrick Tillman',     'Client B+C'],
            ['Albert Einstein',     null],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Hunter Stroman',      'Client A'],
            ['Juanita Goodwin',     'Manager C'],
            ['Joe Gutmann',         'Locked account'],
            ['Jarrell Kiehn',       'Support Engineer A, Developer B, Manager C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Kailyn Bahringer',    'Support Engineer B+C'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = 25;

        $query->sort = [
            User::JSON_EMAIL => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return [$user->fullname, $user->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByFullname()
    {
        $expected = [
            ['Albert Einstein',     null],
            ['Alyson Schinner',     'Client B'],
            ['Anissa Marvin',       'Developer B'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Artem Rodygin',       null],
            ['Bell Kemmer',         'Support Engineer A+B'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Carolyn Hill',        'Manager B+C'],
            ['Carson Legros',       'Client A+B'],
            ['Carter Batz',         'Support Engineer A+C'],
            ['Christy McDermott',   'Developer A'],
            ['Dangelo Hill',        'Manager A'],
            ['Denis Murazik',       'Client C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Derrick Tillman',     'Client B+C'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['eTraxis Admin',       'Built-in administrator'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Hunter Stroman',      'Client A'],
            ['Jarrell Kiehn',       'Support Engineer A, Developer B, Manager C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Joe Gutmann',         'Locked account'],
            ['Juanita Goodwin',     'Manager C'],
            ['Kailyn Bahringer',    'Support Engineer B+C'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = 25;

        $query->sort = [
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return [$user->fullname, $user->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByDescription()
    {
        $expected = [
            ['Artem Rodygin',       null],
            ['Albert Einstein',     null],
            ['eTraxis Admin',       'Built-in administrator'],
            ['Hunter Stroman',      'Client A'],
            ['Carson Legros',       'Client A+B'],
            ['Lucas O\'Connell',    'Client A+B+C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Alyson Schinner',     'Client B'],
            ['Derrick Tillman',     'Client B+C'],
            ['Denis Murazik',       'Client C'],
            ['Christy McDermott',   'Developer A'],
            ['Lola Abshire',        'Developer A+B'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Anissa Marvin',       'Developer B'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Millie Bogisich',     'Developer C'],
            ['Ted Berge',           'Disabled account'],
            ['Joe Gutmann',         'Locked account'],
            ['Dangelo Hill',        'Manager A'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Leland Doyle',        'Manager A+B+C+D'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Carolyn Hill',        'Manager B+C'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = 25;

        $query->sort = [
            User::JSON_DESCRIPTION => GetUsersQuery::SORT_ASC,
            User::JSON_FULLNAME    => GetUsersQuery::SORT_DESC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return [$user->fullname, $user->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByAdmin()
    {
        $expected = [
            ['eTraxis Admin',       'Built-in administrator'],
            ['Albert Einstein',     null],
            ['Alyson Schinner',     'Client B'],
            ['Anissa Marvin',       'Developer B'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Artem Rodygin',       null],
            ['Bell Kemmer',         'Support Engineer A+B'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Carolyn Hill',        'Manager B+C'],
            ['Carson Legros',       'Client A+B'],
            ['Carter Batz',         'Support Engineer A+C'],
            ['Christy McDermott',   'Developer A'],
            ['Dangelo Hill',        'Manager A'],
            ['Denis Murazik',       'Client C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Derrick Tillman',     'Client B+C'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Hunter Stroman',      'Client A'],
            ['Jarrell Kiehn',       'Support Engineer A, Developer B, Manager C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Joe Gutmann',         'Locked account'],
            ['Juanita Goodwin',     'Manager C'],
            ['Kailyn Bahringer',    'Support Engineer B+C'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = 25;

        $query->sort = [
            User::JSON_ADMIN    => GetUsersQuery::SORT_ASC,
            User::JSON_FULLNAME => GetUsersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return [$user->fullname, $user->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProvider()
    {
        $expected = [
            ['Albert Einstein',     null],
            ['Vida Parker',         'Support Engineer B'],
            ['Tracy Marquardt',     'Support Engineer A+B+C'],
            ['Tony Buckridge',      'Support Engineer C'],
            ['Ted Berge',           'Disabled account'],
            ['Nikko Hills',         'Support Engineer A+B, Developer C'],
            ['Millie Bogisich',     'Developer C'],
            ['Lucas O\'Connell',    'Client A+B+C'],
            ['Lola Abshire',        'Developer A+B'],
            ['Leland Doyle',        'Manager A+B+C+D'],
            ['Kyla Schultz',        'Support Engineer A'],
            ['Kailyn Bahringer',    'Support Engineer B+C'],
            ['Juanita Goodwin',     'Manager C'],
            ['Joe Gutmann',         'Locked account'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Jarrell Kiehn',       'Support Engineer A, Developer B, Manager C'],
            ['Hunter Stroman',      'Client A'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['eTraxis Admin',       'Built-in administrator'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Derrick Tillman',     'Client B+C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Denis Murazik',       'Client C'],
            ['Dangelo Hill',        'Manager A'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetUsersQuery(new Request());

        $query->offset = 0;
        $query->limit  = 25;

        $query->sort = [
            User::JSON_PROVIDER => GetUsersQuery::SORT_DESC,
            User::JSON_FULLNAME => GetUsersQuery::SORT_DESC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return [$user->fullname, $user->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        $query = new GetUsersQuery(new Request());

        $this->queryBus->execute($query);
    }
}
