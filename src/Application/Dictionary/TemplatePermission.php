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

use Dictionary\StaticDictionary;

/**
 * Template permissions.
 */
class TemplatePermission extends StaticDictionary
{
    public const VIEW_ISSUES         = 'issue.view';
    public const CREATE_ISSUES       = 'issue.create';
    public const EDIT_ISSUES         = 'issue.edit';
    public const REASSIGN_ISSUES     = 'issue.reassign';
    public const SUSPEND_ISSUES      = 'issue.suspend';
    public const RESUME_ISSUES       = 'issue.resume';
    public const ADD_COMMENTS        = 'comment.add';
    public const PRIVATE_COMMENTS    = 'comment.private';
    public const ATTACH_FILES        = 'file.attach';
    public const DELETE_FILES        = 'file.delete';
    public const ADD_DEPENDENCIES    = 'dependency.add';
    public const REMOVE_DEPENDENCIES = 'dependency.remove';
    public const SEND_REMINDERS      = 'reminder.send';
    public const DELETE_ISSUES       = 'issue.delete';

    protected static array $dictionary = [
        self::VIEW_ISSUES         => 'template.permission.view_issues',
        self::CREATE_ISSUES       => 'template.permission.create_issues',
        self::EDIT_ISSUES         => 'template.permission.edit_issues',
        self::REASSIGN_ISSUES     => 'template.permission.reassign_issues',
        self::SUSPEND_ISSUES      => 'template.permission.suspend_issues',
        self::RESUME_ISSUES       => 'template.permission.resume_issues',
        self::ADD_COMMENTS        => 'template.permission.add_comments',
        self::PRIVATE_COMMENTS    => 'template.permission.private_comments',
        self::ATTACH_FILES        => 'template.permission.attach_files',
        self::DELETE_FILES        => 'template.permission.delete_files',
        self::ADD_DEPENDENCIES    => 'template.permission.add_dependencies',
        self::REMOVE_DEPENDENCIES => 'template.permission.remove_dependencies',
        self::SEND_REMINDERS      => 'template.permission.send_reminders',
        self::DELETE_ISSUES       => 'template.permission.delete_issues',
    ];
}
