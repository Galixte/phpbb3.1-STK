<?php
/**
*
* @package Support Toolkit - Duplicate Permission Remover
* @version $Id$
* @copyright (c) 2009 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

class remove_orphaned_permissions
{
	function display_options()
	{
		return 'REMOVE_ORPHANED_PERMISSIONS';
	}

	function run_tool()
	{
		global $db, $user, $cache;
		$orphaned_permissions = $orphaned_users_permissions = array();

		// Find orphaned_permissions from groups
		$sql = 'SELECT DISTINCT auth_option_id
			FROM ' . ACL_GROUPS_TABLE . '
			ORDER BY auth_option_id ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$auth_option_id = $row['auth_option_id'];
			$sql = 'SELECT auth_option_id
				FROM ' . ACL_OPTIONS_TABLE . '
				WHERE auth_option_id = ' . $auth_option_id;
			$res = $db->sql_query($sql);
			$auth = $db->sql_fetchrow($res);
			$db->sql_freeresult($res);
			if(empty($auth))
			{
				$orphaned_permissions[] = $row['auth_option_id'];
			}
		}
		$db->sql_freeresult($result);

		// Find orphaned_permissions from users
		$sql = 'SELECT DISTINCT auth_option_id
			FROM ' . ACL_USERS_TABLE . '
			ORDER BY auth_option_id ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$auth_option_id = $row['auth_option_id'];
			$sql = 'SELECT auth_option_id
				FROM ' . ACL_OPTIONS_TABLE . '
				WHERE auth_option_id = ' . $auth_option_id;
			$res = $db->sql_query($sql);
			$auth = $db->sql_fetchrow($res);
			$db->sql_freeresult($res);

			if(empty($auth))
			{
				$orphaned_users_permissions[] = $row['auth_option_id'];
			}
		}
		$db->sql_freeresult($result);

		if(sizeof($orphaned_permissions) || sizeof($orphaned_users_permissions))
		{
			// Delete groups permissions
			if(sizeof($orphaned_permissions))
			{
				$sql = 'DELETE FROM ' . ACL_GROUPS_TABLE . '
					WHERE ' . $db->sql_in_set('auth_option_id', $orphaned_permissions, false);
				$db->sql_query($sql);
			}

			// Delete users permissions
			if(sizeof($orphaned_users_permissions))
			{
				$sql = 'DELETE FROM ' . ACL_USERS_TABLE . '
					WHERE ' . $db->sql_in_set('auth_option_id', $orphaned_users_permissions, false);
				$db->sql_query($sql);
			}

			$cache->purge();
			$message = $user->lang['ORPHANED_PERMISSIONS_DELETED'];
		}
		else
		{
			$message = $user->lang['ORPHANED_PERMISSIONS_NOT_FIND'];
		}

		meta_refresh(3, append_sid("" . STK_ROOT_PATH . "index." . PHP_EXT . "", 'c=support'));
		trigger_error($message);
	}
}
