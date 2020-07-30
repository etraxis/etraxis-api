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

namespace eTraxis\Application\Dictionary;

use Dictionary\StaticDictionary;

/**
 * Event types.
 */
class EventType extends StaticDictionary
{
    public const ISSUE_CREATED      = 'issue.created';
    public const ISSUE_EDITED       = 'issue.edited';
    public const STATE_CHANGED      = 'state.changed';
    public const ISSUE_REOPENED     = 'issue.reopened';
    public const ISSUE_CLOSED       = 'issue.closed';
    public const ISSUE_ASSIGNED     = 'issue.assigned';
    public const ISSUE_SUSPENDED    = 'issue.suspended';
    public const ISSUE_RESUMED      = 'issue.resumed';
    public const PUBLIC_COMMENT     = 'comment.public';
    public const PRIVATE_COMMENT    = 'comment.private';
    public const FILE_ATTACHED      = 'file.attached';
    public const FILE_DELETED       = 'file.deleted';
    public const DEPENDENCY_ADDED   = 'dependency.added';
    public const DEPENDENCY_REMOVED = 'dependency.removed';

    protected static array $dictionary = [
        self::ISSUE_CREATED      => 'event.issue_created',
        self::ISSUE_EDITED       => 'event.issue_edited',
        self::STATE_CHANGED      => 'event.state_changed',
        self::ISSUE_REOPENED     => 'event.issue_reopened',
        self::ISSUE_CLOSED       => 'event.issue_closed',
        self::ISSUE_ASSIGNED     => 'event.issue_assigned',
        self::ISSUE_SUSPENDED    => 'event.issue_suspended',
        self::ISSUE_RESUMED      => 'event.issue_resumed',
        self::PUBLIC_COMMENT     => 'event.comment_added',
        self::PRIVATE_COMMENT    => 'event.comment_added',
        self::FILE_ATTACHED      => 'event.file_attached',
        self::FILE_DELETED       => 'event.file_deleted',
        self::DEPENDENCY_ADDED   => 'event.dependency_added',
        self::DEPENDENCY_REMOVED => 'event.dependency_removed',
    ];
}
