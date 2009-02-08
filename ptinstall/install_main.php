<?php
/**********************************************************************************
* install_main.php                                                                *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.2                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007-2009 by:     Niko Pahajoki (http://www.madjoki.com)              *
* Support, News, Updates at:  http://www.madjoki.com                              *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

global $txt, $smcFunc, $db_prefix;
global $project_version, $addSettings, $permissions, $tables;

if (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please run ptinstall/index.php instead');

$tbl = array_keys($tables);

// Add prefixes to array
foreach ($tbl as $id => $table)
	$tbl[$id] = $db_prefix . $table;

db_extend('packages');
$tbl = array_intersect($tbl, $smcFunc['db_list_tables']());

// Step 1: Do tables
doTables($tbl, $tables);

// Step 2: Do Settings
doSettings($addSettings);

// Step 3: Do Permissions
doPermission($permissions);

// Step 4: Install default groups if needed
$request = $smcFunc['db_query']('', '
	SELECT COUNT(*)
	FROM {db_prefix}project_profiles');

list ($count) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);
if ($count == 0)
{
	$smcFunc['db_insert']('insert',
		'{db_prefix}project_profiles',
		array(
			'id_profile' => 'int', 'profile_name' => 'string',
		),
		array(
			1, 'Default',
		),
		array()
	);
	$smcFunc['db_insert']('insert',
		'{db_prefix}project_permissions',
		array(
			'id_profile' => 'int',
			'id_group' => 'int',
			'permission' => 'string',
		),
		array(
			// Guest
			array(1, -1, 'issue_view'),
			// Regular members
			array(1, 0, 'issue_view'),
			array(1, 0, 'issue_report'),
			array(1, 0, 'issue_comment'),
			array(1, 0, 'issue_update_own'),
			array(1, 0, 'issue_attach'),
			array(1, 0, 'edit_comment_own'),
			// Global Moderators
			array(1, 2, 'issue_view'),
			array(1, 2, 'issue_report'),
			array(1, 2, 'issue_comment'),
			array(1, 2, 'issue_update_own'),
			array(1, 2, 'issue_update_any'),
			array(1, 2, 'issue_attach'),
			array(1, 2, 'issue_moderate'),
			array(1, 2, 'edit_comment_own'),
			array(1, 2, 'edit_comment_any'),
			array(1, 2, 'delete_comment_own'),
			array(1, 2, 'delete_comment_any'),
		),
		array()
	);
}

// Step 5: Update project_maxEventID
$request = $smcFunc['db_query']('', '
	SELECT MAX(id_event)
	FROM {db_prefix}project_timeline');

list ($maxEventID) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);

updateSettings(array('project_maxEventID' => $maxEventID));

?>