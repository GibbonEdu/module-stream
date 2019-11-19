<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basic variables
$name = 'Stream';
$description = 'Stream is a photo sharing and social module, which allows users to share posts (including photos and videos) with other community members.';
$entryURL = 'stream.php';
$type = 'Additional';
$category = 'Other';
$version = '0.1.00';
$author = 'Sanda Kuipers, Harry Merrett & Ross Parker';
$url = 'https://gibbonedu.org';

//Module tables
$moduleTables[] = "CREATE TABLE `streamPost` (
  `streamPostID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,
  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
  `post` text,
  `tag` text,
  `streamCategoryIDList` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`streamPostID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `streamCategory` (
  `streamCategoryID` int(3) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `staffAccess` enum('Y','N') NOT NULL DEFAULT 'N',
  `studentAccess` enum('Y','N') NOT NULL DEFAULT 'N',
  `parentAccess` enum('Y','N') NOT NULL DEFAULT 'N',
  `otherAccess` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`streamCategoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

//Settings
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Stream', 'postLength', 'Post Length', 'Maximum number of characters in a post.', '280');";

//Action rows
$actionRows[] = [
    'name'                      => 'View Stream',
    'precedence'                => '0',
    'category'                  => 'Stream',
    'description'               => 'View the stream of shared posts.',
    'URLList'                   => 'stream.php',
    'entryURL'                  => 'stream.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'Manage Categories',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Create, edit and delete stream categories.',
    'URLList'                   => 'categories_manage.php,categories_manage_add.php,categories_manage_edit.php,categories_manage_delete.php',
    'entryURL'                  => 'categories_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Posts_all',
    'precedence'                => '1',
    'category'                  => 'Manage',
    'description'               => 'Allows a user to manage all posts within the system.',
    'URLList'                   => 'posts_manage.php,posts_manage_add.php,posts_manage_edit.php,posts_manage_delete.php',
    'entryURL'                  => 'posts_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Posts_my',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Allows a user to manage their own posts.',
    'URLList'                   => 'posts_manage.php,posts_manage_add.php,posts_manage_edit.php,posts_manage_delete.php',
    'entryURL'                  => 'posts_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'Stream Settings',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Allows a user to manage settings for the Stream Module.',
    'URLList'                   => 'stream_setting.php',
    'entryURL'                  => 'stream_setting.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];
