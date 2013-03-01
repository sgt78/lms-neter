<?php

/*
 * LMS version 1.10.4 Pyrus
 *
 *  (C) Copyright 2001-2008 LMS Developers
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
 *  $Id: rtqueuelist.php,v 1.12.2.2 2008/01/04 07:58:18 alec Exp $
 */

function EventScoreList()
{
	global $DB;
	$list = $DB->GetAll("SELECT evs_id, evs_name, evs_score FROM eventscore WHERE evs_deleted = 0");
	return $list;
}

switch($_GET['action'])
{
	case 'add':
		break;
	case 'edit':
		if($_GET['id'] > 0)
		$score = $DB->GetAll("SELECT evs_id,evs_name,evs_score FROM eventscore WHERE evs_id=?",array($_GET['id']));
		foreach($score as $key => $row)
			$score = $row;
		$SMARTY->assign('sc',$score);
		break;
	case 'save';
		$sc = $_POST['sc'];
		if($sc['evs_id'] > 0)
		{
			// update
			$DB->Execute("UPDATE eventscore set evs_name=?,evs_score=? WHERE evs_id=?",array($sc['evs_name'],$sc['evs_score'],$sc['evs_id']));
		} else {
			//insert
			$DB->Execute("INSERT into eventscore (evs_name,evs_score) VALUES (?,?)",array($sc['evs_name'],$sc['evs_score']));
		}
	case 'delete':
		if(isset($_GET['id']))
			$DB->Execute("UPDATE eventscore set evs_deleted=1 where evs_id=?",array($_GET['id']));
		break;
		
}	


$layout['pagetitle'] = 'Punktacja za wykonane zadania';

$scores = EventScoreList();

//$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('scores', $scores);
$SMARTY->display('eventscore.html');
?>
