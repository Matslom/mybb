<?php
/**
 * MyBB 1.0
 * Copyright � 2005 MyBulletinBoard Group, All Rights Reserved
 *
 * Website: http://www.mybboard.com
 * License: http://www.mybboard.com/eula.html
 *
 * $Id$
 */

$templatelist = "memberlist,memberlist_row";
$templatelist .= ",postbit_www,postbit_email";
require "./global.php";

// Load global language phrases
$lang->load("memberlist");

if($mybb->settings['enablememberlist'] == "no")
{
	error($lang->memberlist_disabled);
}

$plugins->run_hooks("memberlist_start");

addnav($lang->nav_memberlist);

if($mybb->usergroup['canviewmemberlist'] == "no")
{
	nopermission();
}

if($mybb->input['by'] != "regdate" && $mybb->input['by'] != "postnum" && $mybb->input['by'] != "username")
{
	if($mybb->input['usersearch'])
	{
		$mybb->input['by'] = "username";
	}
	else
	{
		$mybb->input['by'] = $mybb->settings['default_memberlist_sortby'];
	}
}

if($mybb->input['order'] != "DESC" && $mybb->input['order'] != "ASC")
{
	// top posters first
	if($mybb->input['by'] == "postnum")
	{
		$mybb->input['order'] = "DESC";
	}
	else
	{
		$mybb->input['order']= $mybb->settings['default_memberlist_order'];
	}
}

if($mybb->input['usersearch'])
{
	$query = $db->query("SELECT COUNT(*) AS users FROM ".TABLE_PREFIX."users WHERE username LIKE '%".$db->escape_string($mybb->input['usersearch'])."%'");
	$linkaddon = "&usersearch=".$mybb->input['usersearch'];
}
else
{
	$query = $db->query("SELECT COUNT(*) AS users FROM ".TABLE_PREFIX."users");
	$linkaddon = '';
}

$num = $db->fetch_field($query, "users")
$page = intval($mybb->input['page']);
if($page)
{
	$start = ($page - 1) * $mybb->settings['membersperpage'];
}
else
{
	$start = 0;
	$page = 1;
}
$multipage = multipage($num, $mybb->settings['membersperpage'], $page, "memberlist.php?by=".$mybb->input['by']."&order=".$mybb->input['order'].$linkaddon);

if($mybb->input['by'] == "postnum")
{
	$postnumsel = " selected=\"selected\"";
}
elseif($mybb->input['by'] == "username")
{
	$usernamesel = " selected=\"selected\"";
}
else
{
	$regdatesel = " selected=\"selected\"";
}
if($mybb->input['order'] == "DESC")
{
	$descsel = " selected=\"selected\"";
}
else
{
	$ascsel = " selected=\"selected\"";
}

if($mybb->input['usersearch'])
{
	$query = $db->query("SELECT u.*, f.* FROM ".TABLE_PREFIX."users u LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid) WHERE u.username LIKE '%".$db->escape_string($mybb->input['usersearch'])."%' ORDER BY u.".$mybb->input['by']." ".$mybb->input['order']." LIMIT $start, ".$mybb->settings['membersperpage']);
}
else
{
	$query = $db->query("SELECT u.*, f.* FROM ".TABLE_PREFIX."users u LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid) ORDER BY u.".$mybb->input['by']." ".$mybb->input['order']." LIMIT $start, ".$mybb->settings['membersperpage']);
}
$member = '';
while($users = $db->fetch_array($query))
{
	$plugins->run_hooks("memberlist_user");

	// Make variables for postbit templates
	$post = &$users;

	if($users['website'] == '' || $users['website'] == "http://")
	{
		$usersite = '';
	}
	else
	{
		eval("\$usersite = \"".$templates->get("postbit_www")."\";");
	}
	$users['location'] = $users['fid1'];
	$users['location'] = htmlspecialchars_uni(stripslashes($users['location']));
	if($users['hideemail'] == "yes")
	{
		$useremail = '';
	}
	else
	{
		eval("\$useremail = \"".$templates->get("postbit_email")."\";");
	}
	$users['regdate'] = mydate($mybb->settings['dateformat'], $users['regdate']);
	$users['username'] = formatname($users['username'], $users['usergroup'], $users['displaygroup']);
	eval("\$member .= \"".$templates->get("memberlist_row")."\";");
}

// just in case there's no matching search results, or no registered members
if(!$member)
{
	$member = "<tr>\n<td colspan=\"6\" align=\"center\" class=\"trow1\">$lang->error_no_members</td>\n</tr>";
}
$usersearch = htmlspecialchars_uni($mybb->input['usersearch']);

$plugins->run_hooks("memberlist_end");

eval("\$memberlist = \"".$templates->get("memberlist")."\";");
outputpage($memberlist);
?>