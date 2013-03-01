<?php

/*
 * LMS version 1.11.13 Dira
 *
 *  (C) Copyright 2001-2011 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id: netdevmap.php,v 1.66 2011/01/18 08:12:23 alec Exp $
 */


$layout['pagetitle'] = trans('Network Map Google');

$mini = isset($_GET['mini']) ? true : false;
$nodelist = array();
$devicelinks = array();

if(!$mini && ($nodes = $DB->GetAll('SELECT id, linktype, netdev 
			FROM nodes 
			WHERE ownerid > 0 AND netdev > 0 
			ORDER BY name ASC')))
{
	foreach($nodes as $idx => $node)
	{
		$nodelist[$node['netdev']][] = $node;
		unset($nodes[$idx]);
	}
}

if($links = $DB->GetAll('SELECT src, dst FROM netlinks'))
{
	foreach($links as $idx => $link)
	{
		$devicelinks[$link['src']][$link['dst']] = $link['dst'];
		$devicelinks[$link['dst']][$link['src']] = $link['src'];
		unset($links[$idx]);
	}
}


$deviceslist = $DB->GetAll('SELECT id, name FROM netdevices ORDER BY name ASC');

$SMARTY->assign('mini', $mini);
$SMARTY->assign('nodelist', $nodelist);
$SMARTY->assign('devicelinks', $devicelinks);
$SMARTY->assign('deviceslist', $deviceslist);
$SMARTY->assign('GoogleApiKey', isset($CONFIG['phpui']['googleapikey']) ? $CONFIG['phpui']['googleapikey'] : '');
$SMARTY->assign('emptydb', sizeof($deviceslist) ? FALSE : TRUE);
$SMARTY->display('netdevmapgoogle.html');
	
?>
