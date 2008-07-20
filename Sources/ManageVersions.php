<?php
/**********************************************************************************
* ManageVersions.php                                                              *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.1 Alpha                         *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007 by:          Niko Pahajoki (http://www.madjoki.com)              *
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

if (!defined('SMF'))
	die('Hacking attempt...');

function ManageVersions()
{
	global $context, $sourcedir, $scripturl, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectTools('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = &$txt['manage_versions'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = &$txt['manage_versions_description'];

	$context['page_title'] = &$txt['manage_versions'];

	$subActions = array(
		'list' => array('ManageVersionsList'),
		'new' => array('EditVersion'),
		'edit' => array('EditVersion'),
		'edit2' => array('EditVersion2'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ManageVersions');

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

function ManageVersionsList()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT p.id_project, p.name, p.description
		FROM {db_prefix}projects AS p
		ORDER BY p.name');

	$context['projects'] = array();
	$projects = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['projects'][$row['id_project']] = array(
			'id' => $row['id_project'],
			'href' => $scripturl . '?action=admin;area=manageprojects;sa=project;project=' . $row['id_project'],
			'name' => $row['name'],
			'description' => $row['description'],
			'versions' => array(),
			'categories' => array(),
		);

		$projects[] = $row['id_project'];
	}
	$smcFunc['db_free_result']($request);

	// Current project
	if (!isset($_REQUEST['project']) || !in_array((int) $_REQUEST['project'], $projects))
		$id_project = $projects[0];
	else
		$id_project = (int) $_REQUEST['project'];

	$projectsHtml = '';

	foreach ($context['projects'] as $project)
	{
		$projectsHtml .= '
		<option value="' . $project['id'] . '"' . ($project['id'] == $id_project ? ' selected="selected"' : '') . '>' . $project['name']. '</option>';
	}

	$listOptions = array(
		'id' => 'versions_list',
		'base_href' => $scripturl . '?action=admin;area=manageversions',
		'get_items' => array(
			'function' => 'list_getVersions',
			'params' => array(
				$id_project,
			),
		),
		'columns' => array(
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
					'style' => 'width: 4%;',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="versions[]" value="%1$d" class="check" />',
						'params' => array(
							'id' => false,
						),
					),
					'style' => 'text-align: center;',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['header_version'],
				),
				'data' => array(
					'function' => create_function('$list_item', '
						return str_repeat(\'&nbsp;\', $list_item[\'level\'] * 5) . $list_item[\'link\'];
					'),
				),
				'sort' => array(
					'default' => 'ver.version_name',
					'reverse' => 'ver.version_name DESC',
				),
			),
			'actions' => array(
				'header' => array(
					'value' => '<a href="' .  $scripturl . '?action=admin;area=manageversions;sa=new;project=' . $id_project . '">' . $txt['new_version_group'] . '</a>',
					'style' => 'width: 16%; text-align: right;',
				),
				'data' => array(
					'function' => create_function('$list_item', '
						global $txt, $scripturl;
						return (empty($list_item[\'level\']) ? \'<a href="\' .  $scripturl . \'?action=admin;area=manageversions;sa=new;project=' . $id_project . ';parent=\' . $list_item[\'id\'] . \'">\' . $txt[\'new_version\'] . \'</a>\' : \'\');
					'),
					'style' => 'text-align: right;',
				),
				'sort' => array(
					'default' => 'ver.version_name',
					'reverse' => 'ver.version_name DESC',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=manageversions',
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
				'sc' => $context['session_id'],
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'top_of_list',
				'value' => '
					<select name="project">' . $projectsHtml . '</select>
					<input type="submit" name="go" value="' . $txt['go'] . '" />',
				'class' => 'catbg',
				'align' => 'right',
			),
		),
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	// Template
	$context['sub_template'] = 'versions_list';
}

function EditVersion()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$_REQUEST['version'] = isset($_REQUEST['version']) ? (int) $_REQUEST['version'] : 0;
	$_REQUEST['project'] = isset($_REQUEST['project']) ? (int) $_REQUEST['project'] : 0;

	if ($_REQUEST['sa'] == 'new')
	{
		if (!$context['project'] = loadProject((int) $_REQUEST['project']))
			fatal_lang_error('project_not_found', false);

		list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

		$context['version'] = array(
			'is_new' => true,
			'id' => 0,
			'project' => $context['project']['id'],
			'name' => '',
			'description' => '',
			'parent' => !empty($_REQUEST['parent']) && isset($context['versions_id'][$_REQUEST['parent']]) ? $_REQUEST['parent'] : 0,
			'status' => 0,
			'release_date' => array('day' => 0, 'month' => 0, 'year' => 0),
		);

		$request = $smcFunc['db_query']('', '
			SELECT id_project, id_group, group_name
			FROM {db_prefix}project_groups
			WHERE id_project = {int:project} OR id_project = 0',
			array(
				'project' => $context['project']['id'],
			)
		);

		$context['project_groups'] = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['project_groups'][$row['id_group']] = array(
				'id' => $row['id_group'],
				'name' => $row['group_name'],
				'global' => $row['id_project'] == 0,
				'selected' => false,
			);
		}
		$smcFunc['db_free_result']($request);
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT
				v.id_version, v.id_project, v.id_parent, v.version_name,
				v.status, v.project_groups, v.description, v.release_date
			FROM {db_prefix}project_versions AS v
			WHERE id_version = {int:version}',
			array(
				'version' => $_REQUEST['version']
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
			fatal_lang_error('version_not_found', false);

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		$project_groups = explode(',', $row['project_groups']);

		if (!$context['project'] = loadProject((int) $row['id_project']))
			fatal_lang_error('project_not_found', false);

		list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

		$context['version'] = array(
			'id' => $row['id_version'],
			'project' => $row['id_project'],
			'name' => htmlspecialchars($row['version_name']),
			'description' => htmlspecialchars($row['description']),
			'parent' => isset($context['versions_id'][$row['id_parent']]) ? $row['id_parent'] : 0,
			'status' => $row['status'],
			'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array('day' => 0, 'month' => 0, 'year' => 0),
		);

		$request = $smcFunc['db_query']('', '
			SELECT id_group, group_name, id_project
			FROM {db_prefix}project_groups
			WHERE id_project = {int:project} OR id_project = 0',
			array(
				'project' => $context['project']['id'],
			)
		);

		$context['project_groups'] = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['project_groups'][$row['id_group']] = array(
				'id' => $row['id_group'],
				'name' => $row['group_name'],
				'selected' => in_array($row['id_group'], $project_groups),
				'global' => $row['id_project'] == 0,
			);
		}
		$smcFunc['db_free_result']($request);
	}

	// Template
	$context['sub_template'] = 'edit_version';
}

function EditVersion2()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	checkSession();

	$_POST['project'] = (int) $_POST['project'];
	$_POST['version'] = (int) $_POST['version'];

	if (isset($_POST['edit']) || isset($_POST['add']))
	{
		$versionOptions = array();

		$versionOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['version_name']);
		$versionOptions['description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['desc']);

		if (!empty($_POST['parent']))
		{
			$versionOptions['parent'] = $_POST['parent'];

			$versionOptions['release_date'] = serialize(array(
				'day' => !empty($_POST['release_date'][0]) ? $_POST['release_date'][0] : 0,
				'month' => !empty($_POST['release_date'][1]) ? $_POST['release_date'][1] : 0,
				'year' => !empty($_POST['release_date'][2]) ? $_POST['release_date'][2] : 0
			));

			$versionOptions['status'] = (int) $_POST['status'];

			if ($versionOptions['status'] < 0 || $versionOptions['status'] > 6)
				$versionOptions['status'] = 0;
		}

		$versionOptions['project_groups'] = $_POST['groups'];


		if (isset($_POST['add']))
			createVersion($_POST['project'], $versionOptions);
		else
			updateVersion($_POST['version'], $versionOptions);
	}
	elseif (isset($_POST['delete']))
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_versions
			WHERE id_version = {int:version}',
			array(
				'version' => $_POST['version']
			)
		);
	}

	redirectexit('action=admin;area=manageversions;project=' . $_POST['project']);
}

function list_getVersions($start, $items_per_page, $sort, $project)
{
	global $smcFunc, $scripturl;

	$request = $smcFunc['db_query']('', '
		SELECT ver.id_version, ver.version_name, ver.id_parent
		FROM {db_prefix}project_versions AS ver
		WHERE ver.id_project = {int:project}
		ORDER BY ver.id_parent, ver.version_name',
		array(
			'project' => $project
		)
	);

	$versionsTemp = array();
	$children = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (empty($row['id_parent']))
		{
			$versionsTemp[] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'link' => '<a href="' . $scripturl . '?action=admin;area=manageversions;sa=edit;version= ' . $row['id_version'] . '">' . $row['version_name'] . '</a>',
				'level' => 0,
			);
		}
		else
		{
			if (!isset($children[$row['id_parent']]))
				$children[$row['id_parent']] = array();

			$children[$row['id_parent']][] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'link' => '<a href="' . $scripturl . '?action=admin;area=manageversions;sa=edit;version= ' . $row['id_version'] . '">' . $row['version_name'] . '</a>',
				'level' => 1,
			);
		}
	}

	$smcFunc['db_free_result']($request);

	$versions = array();

	foreach ($versionsTemp as $ver)
	{
		$versions[] = $ver;

		if (isset($children[$ver['id']]))
			$versions = array_merge($versions, $children[$ver['id']]);
	}

	return $versions;
}

?>