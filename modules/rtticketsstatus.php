<?php

/*
 * LMS version 1.11.13 Dira
 *
 *  (C) NETER sp. z o.o.
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
 */

function GetTicketsSummary($userid=0, $status=0, $planed=0, $queue=0)
{
	global $DB, $AUTH;
	global $warning;
	global $critical;
	
	if ($status)
		{
		$sql='SELECT'
			.' s.state,'
			.($userid ? ' s.owner,' : '0 AS owner,')
			.' CASE s.state'
			.' WHEN 0 THEN "'.trans('new').'"'
			.' WHEN 1 THEN "'.trans('opened').'"'
			.' WHEN 2 THEN "'.trans('resolved').'"'
			.' WHEN 3 THEN "'.trans('dead').'"'
			.' WHEN 11 THEN "'.trans('external').'"'
			.' END AS name,' 
			.' COALESCE(s.suma,0) AS suma, COALESCE(w.warning,0) AS warning, COALESCE(c.critical,0) AS critical'
			.' FROM'
			.' ('
			.' SELECT'  
			.($userid ? ' owner,' : ' 0 AS owner,')
			.' `state`, COUNT(*) AS suma'
			.' FROM `rttickets`'
			.($userid ? ' WHERE `owner`='.$userid : '')
			.' GROUP BY'
			.($userid ? ' `owner`,' : '')
			.' `state`'
			.' ) s'

			.' LEFT JOIN' 
			.' ('
			.' SELECT'  
			.($userid ? ' `owner`,' : '')
			.' `state`, COUNT(*) AS warning'
			.' FROM `rttickets`'
			.' WHERE'
			.' ((resolvetime=0 AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))>'.$warning.' AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))<='.$critical.')'
			.' OR'
			.' (resolvetime<>0 AND DATEDIFF(resolvetime, FROM_UNIXTIME(`createtime`))>'.$warning.' AND DATEDIFF(resolvetime, FROM_UNIXTIME(`createtime`))<='.$critical.'))'
			.($userid ? ' AND `owner`='.$userid : '')
			.' GROUP BY'
			.($userid ? ' `owner`,' : '')
			.' `state`'
			.' ) w ON s.state = w.state'

			.' LEFT JOIN'
			.' ('
			.' SELECT'  
			.($userid ? ' `owner`,' : '')
			.' `state`, COUNT(*) AS critical'
			.' FROM `rttickets`'
			.' WHERE'
			.' ((resolvetime=0 AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))>'.$critical.')'
			.' OR'
			.' (resolvetime<>0 AND DATEDIFF(resolvetime, FROM_UNIXTIME(`createtime`))>'.$critical.'))'
			.($userid ? ' AND `owner`='.$userid : '')
			.' GROUP BY'
			.($userid ? ' `owner`,' : '')
			.' `state`'
			.' ) c ON s.state = c.state'
			.' UNION'
			.' SELECT'
			.' -1 AS state,'
			.($userid ? ' s.owner,' : '0 AS owner,')
			.' "niezakończone" AS name,' 
			.' COALESCE(s.suma,0) AS suma, COALESCE(w.warning,0) AS warning, COALESCE(c.critical,0) AS critical'
			.' FROM'
			.' ('
			.' SELECT'  
			.($userid ? ' owner,' : ' 0 AS owner,')
			.' COUNT(*) AS suma'
			.' FROM `rttickets`'
			.' WHERE state <> 2'
			.($userid ? ' AND `owner`='.$userid : '')
			.($userid ? ' GROUP BY `owner`' : '')
			.' ) s'

			.' LEFT JOIN' 
			.' ('
			.' SELECT'  
			.($userid ? ' `owner`,' : '')
			.' COUNT(*) AS warning'
			.' FROM `rttickets`'
			.' WHERE'
			.' ((resolvetime=0 AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))>'.$warning.' AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))<='.$critical.')'
			.' OR'
			.' (resolvetime<>0 AND DATEDIFF(resolvetime, FROM_UNIXTIME(`createtime`))>'.$warning.' AND DATEDIFF(resolvetime, FROM_UNIXTIME(`createtime`))<='.$critical.'))'
			.' AND state <> 2'
			.($userid ? ' AND `owner`='.$userid : '')
			.($userid ? ' GROUP BY `owner`' : '')
			.' ) w ON 1'

			.' LEFT JOIN'
			.' ('
			.' SELECT'  
			.($userid ? ' `owner`,' : '')
			.' COUNT(*) AS critical'
			.' FROM `rttickets`'
			.' WHERE'
			.' ((resolvetime=0 AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))>'.$critical.')'
			.' OR'
			.' (resolvetime<>0 AND DATEDIFF(resolvetime, FROM_UNIXTIME(`createtime`))>'.$critical.'))'
			.' AND state <> 2'
			.($userid ? ' AND `owner`='.$userid : '')
			.($userid ? ' GROUP BY `owner`' : '')
			.' ) c ON 1';
		
		
		$list = $DB->GetAll($sql);
/*		$list = array (
			array("name" => "nowy", 		"suma" => "10", "warning" => "5", "critical" => "1"),
			array("name" => "otwarty",		"suma" => "5", 	"warning" => "1", "critical" => "3"),
			array("name" => "rozwiazany", 	"suma" => "2", 	"warning" => "1", "critical" => "1"),
			array("name" => "martwy", 		"suma" => "1", 	"warning" => "0", "critical" => "1")
			);*/
		}
	else if ($planed)
		{
		if ($userid)
			{
			$sql = '
			SELECT 
			s.name, 
			COALESCE(s.suma,0) AS suma, COALESCE(w.suma,0) AS warning, COALESCE(c.suma,0) AS critical

			FROM 
				( 
				SELECT
				COUNT(*) AS suma, "zadania własne otwarte, bez terminu" AS name,
				'.$userid.' AS owner
				FROM `rttickets` t 
				LEFT JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events` ev LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE (ev.userid='.$userid.' OR ea.userid='.$userid.')
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state<>2 AND e.`ile` IS NULL AND t.owner='.$userid.'

				UNION
				SELECT
				COUNT(*) AS suma, 
				CONCAT("zadanie ",IF(`owner`='.$userid.',"własne","obce")," otwarte, ",IF(`closed`,"termin zamknięty","zaplanowane"),IF(`date`<UNIX_TIMESTAMP()," - po terminie","")) AS name,
				'.$userid.' AS owner
				FROM 
					(
					SELECT t.owner, ev.closed, ev.date
					FROM `rttickets` t 
					JOIN `events` ev ON t.`id`=ev.`rtticketid` 
					WHERE t.state<>2
					AND ev.userid='.$userid.'

					UNION
					SELECT t.owner, ev.closed, ev.date
					FROM `rttickets` t 
					JOIN `events` ev ON t.`id`=ev.`rtticketid` 
					LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE t.state<>2
					AND ev.userid<>'.$userid.'
					AND ea.userid='.$userid.'
					) t 
				GROUP BY `owner`='.$userid.', `closed`, `date`<UNIX_TIMESTAMP() 

				UNION
				SELECT
				COUNT(*) AS suma, IF(t.owner='.$userid.', "terminy z zakończonych zadań własnych","terminy z zakończonych zadań obcych") AS name,
				'.$userid.' AS owner
				FROM `rttickets` t 
				JOIN 
					(
					SELECT DISTINCT ev.rtticketid, COUNT(*) AS ile 
					FROM `events` ev LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE ev.closed=0
					AND (ev.userid='.$userid.' OR ea.userid='.$userid.')
					GROUP BY ev.rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state=2
				GROUP BY t.owner=14

				UNION 
				SELECT COUNT(*) AS suma, IF(e.`date`<UNIX_TIMESTAMP() ,"terminy bez zadań - po terminie","terminy bez zadań") AS name,
				'.$userid.' AS owner
				FROM `events` e LEFT JOIN `eventassignments` ea ON e.id=ea.eventid
				WHERE e.rtticketid <=0 AND e.closed=0
				AND (e.userid='.$userid.' OR ea.userid='.$userid.')
				GROUP BY e.`date`<UNIX_TIMESTAMP() 
				) s

			LEFT JOIN 
				( 
				SELECT
				COUNT(*) AS suma, "zadania własne otwarte, bez terminu" AS name,
				'.$userid.' AS owner
				FROM `rttickets` t 
				LEFT JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events` ev LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE (ev.userid='.$userid.' OR ea.userid='.$userid.')
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state<>2 AND e.`ile` IS NULL AND t.owner='.$userid.'
				AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))>'.$warning.'
				AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))<='.$critical.'

				UNION
				SELECT
				COUNT(*) AS suma, 
				CONCAT("zadanie ",IF(`owner`='.$userid.',"własne","obce")," otwarte, ",IF(`closed`,"termin zamknięty","zaplanowane"),IF(`date`<UNIX_TIMESTAMP()," - po terminie","")) AS name,
				'.$userid.' AS owner
				FROM 
					(
					SELECT t.owner, ev.closed, ev.date
					FROM `rttickets` t 
					JOIN `events` ev ON t.`id`=ev.`rtticketid` 
					WHERE t.state<>2
					AND ev.userid='.$userid.'
					AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))>'.$warning.'
					AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))<='.$critical.'

					UNION
					SELECT t.owner, ev.closed, ev.date
					FROM `rttickets` t 
					JOIN `events` ev ON t.`id`=ev.`rtticketid` 
					LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE t.state<>2
					AND ev.userid<>'.$userid.'
					AND ea.userid='.$userid.'
					AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))>'.$warning.'
					AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))<='.$critical.'
					) t 
				GROUP BY `owner`='.$userid.', `closed`, `date`<UNIX_TIMESTAMP() 

				UNION
				SELECT
				COUNT(*) AS suma, IF(t.owner='.$userid.', "terminy z zakończonych zadań własnych","terminy z zakończonych zadań obcych") AS name,
				'.$userid.' AS owner
				FROM `rttickets` t 
				JOIN 
					(
					SELECT DISTINCT ev.rtticketid, COUNT(*) AS ile 
					FROM `events` ev LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE ev.closed=0
					AND (ev.userid='.$userid.' OR ea.userid='.$userid.')
					AND DATEDIFF(NOW(), FROM_UNIXTIME(ev.`date`))>'.$warning.'
					AND DATEDIFF(NOW(), FROM_UNIXTIME(ev.`date`))<='.$critical.'
					GROUP BY ev.rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state=2
				GROUP BY t.owner=14

				UNION 
				SELECT COUNT(*) AS suma, IF(e.`date`<UNIX_TIMESTAMP() ,"terminy bez zadań - po terminie","terminy bez zadań") AS name,
				'.$userid.' AS owner
				FROM `events` e LEFT JOIN `eventassignments` ea ON e.id=ea.eventid
				WHERE e.rtticketid <= 0 AND e.closed=0
				AND (e.userid='.$userid.' OR ea.userid='.$userid.')
				AND DATEDIFF(NOW(), FROM_UNIXTIME(e.`date`))>'.$warning.'
				AND DATEDIFF(NOW(), FROM_UNIXTIME(e.`date`))<='.$critical.'
				GROUP BY e.`date`<UNIX_TIMESTAMP() 
				) w ON s.name = w.name 

			LEFT JOIN 
				( 
				SELECT
				COUNT(*) AS suma, "zadania własne otwarte, bez terminu" AS name,
				'.$userid.' AS owner
				FROM `rttickets` t 
				LEFT JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events` ev LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE (ev.userid='.$userid.' OR ea.userid='.$userid.')
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state<>2 AND e.`ile` IS NULL AND t.owner='.$userid.'
				AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))>'.$critical.'

				UNION
				SELECT
				COUNT(*) AS suma, 
				CONCAT("zadanie ",IF(`owner`='.$userid.',"własne","obce")," otwarte, ",IF(`closed`,"termin zamknięty","zaplanowane"),IF(`date`<UNIX_TIMESTAMP()," - po terminie","")) AS name,
				'.$userid.' AS owner
				FROM 
					(
					SELECT t.owner, ev.closed, ev.date
					FROM `rttickets` t 
					JOIN `events` ev ON t.`id`=ev.`rtticketid` 
					WHERE t.state<>2
					AND ev.userid='.$userid.'
					AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))>'.$critical.'

					UNION
					SELECT t.owner, ev.closed, ev.date
					FROM `rttickets` t 
					JOIN `events` ev ON t.`id`=ev.`rtticketid` 
					LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE t.state<>2
					AND ev.userid<>'.$userid.'
					AND ea.userid='.$userid.'
					AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))>'.$critical.'
					) t 
				GROUP BY `owner`='.$userid.', `closed`, `date`<UNIX_TIMESTAMP() 

				UNION
				SELECT
				COUNT(*) AS suma, IF(t.owner='.$userid.', "terminy z zakończonych zadań własnych","terminy z zakończonych zadań obcych") AS name,
				'.$userid.' AS owner
				FROM `rttickets` t 
				JOIN 
					(
					SELECT DISTINCT ev.rtticketid, COUNT(*) AS ile 
					FROM `events` ev LEFT JOIN `eventassignments` ea ON ev.id=ea.eventid
					WHERE ev.closed=0
					AND (ev.userid='.$userid.' OR ea.userid='.$userid.')
					AND DATEDIFF(NOW(), FROM_UNIXTIME(ev.`date`))>'.$critical.'
					GROUP BY ev.rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state=2
				GROUP BY t.owner=14

				UNION 
				SELECT COUNT(*) AS suma, IF(e.`date`<UNIX_TIMESTAMP() ,"terminy bez zadań - po terminie","terminy bez zadań") AS name,
				'.$userid.' AS owner
				FROM `events` e LEFT JOIN `eventassignments` ea ON e.id=ea.eventid
				WHERE e.rtticketid <= 0 AND e.closed=0
				AND (e.userid='.$userid.' OR ea.userid='.$userid.')
				AND DATEDIFF(NOW(), FROM_UNIXTIME(e.`date`))>'.$critical.'
				GROUP BY e.`date`<UNIX_TIMESTAMP() 
				) c ON s.name = c.name 

				';
			$list = $DB->GetAll($sql);

/*
		$list = array (
			array("name" => "zaplanowane",	"suma" => "10", "warning" => "5", "critical" => "1"),
			array("name" => "bez terminu",	"suma" => "5", 	"warning" => "1", "critical" => "3")
			);
*/
			}
		else
			{
			$sql = '
			SELECT 
			s.name, 
			COALESCE(s.suma,0) AS suma, COALESCE(w.suma,0) AS warning, COALESCE(c.suma,0) AS critical

			FROM 
				( 
				SELECT
				COUNT(*) AS suma, IF(ISNULL(e.`ile`),"zadania bez terminu","zadania zaplanowane") AS name
				FROM `rttickets` t 
				LEFT JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events`
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state<>2
				GROUP BY IF(ISNULL(e.`ile`),-1,1)

				UNION
				SELECT
				COUNT(*) AS suma, "terminy z zakończonych zadań" AS name
				FROM `rttickets` t 
				JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events`
					WHERE closed=0
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state=2

				UNION 
				SELECT COUNT(*) AS suma, IF(e.`date`<UNIX_TIMESTAMP() ,"terminy bez zadań - po terminie","terminy bez zadań") AS name
				FROM `events` e
				WHERE e.rtticketid <= 0 AND e.closed=0
				GROUP BY e.`date`<UNIX_TIMESTAMP() 
				) s

			LEFT JOIN 
				( 
				SELECT
				COUNT(*) AS suma, IF(ISNULL(e.`ile`),"zadania bez terminu","zadania zaplanowane") AS name
				FROM `rttickets` t 
				LEFT JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events`
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state<>2
				AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))>'.$warning.'
				AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))<='.$critical.'
				GROUP BY IF(ISNULL(e.`ile`),-1,1)

				UNION
				SELECT
				COUNT(*) AS suma, "terminy z zakończonych zadań" AS name
				FROM `rttickets` t 
				JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events`
					WHERE closed=0
					AND DATEDIFF(NOW(), FROM_UNIXTIME(`date`))>'.$warning.'
					AND DATEDIFF(NOW(), FROM_UNIXTIME(`date`))<='.$critical.'
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state=2

				UNION 
				SELECT COUNT(*) AS suma, IF(e.`date`<UNIX_TIMESTAMP() ,"terminy bez zadań - po terminie","terminy bez zadań") AS name
				FROM `events` e
				WHERE e.rtticketid <= 0 AND e.closed=0
				AND DATEDIFF(NOW(), FROM_UNIXTIME(e.`date`))>'.$warning.'
				AND DATEDIFF(NOW(), FROM_UNIXTIME(e.`date`))<='.$critical.'
				GROUP BY e.`date`<UNIX_TIMESTAMP() 
				) w ON s.name = w.name 

			LEFT JOIN 
				( 
				SELECT
				COUNT(*) AS suma, IF(ISNULL(e.`ile`),"zadania bez terminu","zadania zaplanowane") AS name
				FROM `rttickets` t 
				LEFT JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events`
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state<>2
				AND DATEDIFF(NOW(), FROM_UNIXTIME(t.`createtime`))>'.$critical.'
				GROUP BY IF(ISNULL(e.`ile`),-1,1)

				UNION
				SELECT
				COUNT(*) AS suma, "terminy z zakończonych zadań" AS name
				FROM `rttickets` t 
				JOIN 
					(
					SELECT DISTINCT rtticketid, COUNT(*) AS ile 
					FROM `events`
					WHERE closed=0
					AND DATEDIFF(NOW(), FROM_UNIXTIME(`date`))>'.$critical.'
					GROUP BY rtticketid
					) e ON t.`id`=e.`rtticketid`
				WHERE t.state=2

				UNION 
				SELECT COUNT(*) AS suma, IF(e.`date`<UNIX_TIMESTAMP() ,"terminy bez zadań - po terminie","terminy bez zadań") AS name
				FROM `events` e
				WHERE e.rtticketid <= 0 AND e.closed=0
				AND DATEDIFF(NOW(), FROM_UNIXTIME(e.`date`))>'.$critical.'
				GROUP BY e.`date`<UNIX_TIMESTAMP() 
				) c ON s.name = c.name 

				';
			$list = $DB->GetAll($sql);
			}
		
/*
		$list = array (
			array("name" => "zaplanowane",	"suma" => "10", "warning" => "5", "critical" => "1"),
			array("name" => "bez terminu",	"suma" => "5", 	"warning" => "1", "critical" => "3")
			);
*/
		}
	else if ($queue)
		{
		$sql = 	'SELECT'
			.' s.queueid, q.name,'
			.($userid ? ' s.owner,' : ' 0 AS owner,')
			.' COALESCE(s.suma,0) AS suma, COALESCE(w.warning,0) AS warning, COALESCE(c.critical,0) AS critical'
			.' FROM'
			.' ('
			.' SELECT'  
			.($userid ? ' owner,' : ' 0 AS owner,')
			.' `queueid`, COUNT(*) AS suma'
			.' FROM `rttickets`'
			.' WHERE resolvetime=0'
			.($userid ? ' AND `owner`='.$userid : '')
			.' GROUP BY'
			.($userid ? ' `owner`,' : '')
			.' `queueid`'
			.' ) s'

			.' LEFT JOIN' 
			.' ('
			.' SELECT'  
			.($userid ? ' `owner`,' : '')
			.' `queueid`, COUNT(*) AS warning'
			.' FROM `rttickets`'
			.' WHERE'
			.' resolvetime=0 AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))>'.$warning.' AND DATEDIFF(resolvetime, FROM_UNIXTIME(`createtime`))<='.$critical
			.($userid ? ' AND `owner`='.$userid : '')
			.' GROUP BY'
			.($userid ? ' `owner`,' : '')
			.' `queueid`'
			.' ) w ON s.queueid = w.queueid'

			.' LEFT JOIN'
			.' ('
			.' SELECT'  
			.($userid ? ' `owner`,' : '')
			.' `queueid`, COUNT(*) AS critical'
			.' FROM `rttickets`'
			.' WHERE'
			.' resolvetime=0 AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))>'.$critical
			.($userid ? ' AND `owner`='.$userid : '')
			.' GROUP BY'
			.($userid ? ' `owner`,' : '')
			.' `queueid`'
			.' ) c ON s.queueid = c.queueid'
			.' LEFT JOIN `rtqueues` q ON s.queueid = q.id'
			.' UNION'
			.' SELECT'
			.' 0, "w sumie" AS name,'
			.($userid ? ' s.owner,' : ' 0 AS owner,')
			.' COALESCE(s.suma,0) AS suma, COALESCE(w.warning,0) AS warning, COALESCE(c.critical,0) AS critical'
			.' FROM'
			.' ('
			.' SELECT'  
			.($userid ? ' owner,' : ' 0 AS owner,')
			.' COUNT(*) AS suma'
			.' FROM `rttickets`'
			.' WHERE resolvetime=0'
			.($userid ? ' AND `owner`='.$userid : '')
			.($userid ? ' GROUP BY `owner`' : '')
			.' ) s'

			.' LEFT JOIN' 
			.' ('
			.' SELECT'  
			.($userid ? ' `owner`,' : '')
			.' COUNT(*) AS warning'
			.' FROM `rttickets`'
			.' WHERE'
			.' resolvetime=0 AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))>'.$warning.' AND DATEDIFF(resolvetime, FROM_UNIXTIME(`createtime`))<='.$critical
			.($userid ? ' AND `owner`='.$userid : '')
			.($userid ? ' GROUP BY`owner`' : '')
			.' ) w ON 1'

			.' LEFT JOIN'
			.' ('
			.' SELECT'  
			.($userid ? ' `owner`,' : '')
			.' COUNT(*) AS critical'
			.' FROM `rttickets`'
			.' WHERE'
			.' resolvetime=0 AND DATEDIFF(NOW(), FROM_UNIXTIME(`createtime`))>'.$critical
			.($userid ? ' AND `owner`='.$userid : '')
			.($userid ? ' GROUP BY`owner`' : '')
			.' ) c ON 1';

		$list = $DB->GetAll($sql);
/*		$list = array (
			array("name" => "kolejka 1",	"suma" => "10", "warning" => "5", "critical" => "1"),
			array("name" => "kolejka 2",	"suma" => "5", 	"warning" => "1", "critical" => "3"),
			array("name" => "kolejka 3", 	"suma" => "2", 	"warning" => "1", "critical" => "1"),
			array("name" => "kolejka 4",	"suma" => "1", 	"warning" => "0", "critical" => "1")
			);*/
		}
		
	
	return $list;
}


 
//kopia z eventlist.php
function GetEventList($year=NULL, $month=NULL, $day=NULL, $forward=0, $customerid=0, $userid=0)
{
	global $DB, $AUTH;

	if(!$year) $year = date('Y',time());
	if(!$month) $month = date('n',time());
	if(!$day) $day = date('j',time());
	
	$startdate = mktime(0,0,0, $month, $day, $year);
	$enddate = mktime(0,0,0, $month, $day+$forward, $year);
	$list2 = array();
	
	$list = $DB->GetAll(
	        'SELECT events.id AS id, title, description, date, begintime, endtime, customerid, closed, rtticketid, '
		.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername 
		 FROM events LEFT JOIN customers ON (customerid = customers.id)
		 WHERE date >= ? AND date < ? AND (private = 0 OR (private = 1 AND userid = ?)) '
		.($customerid ? 'AND customerid = '.$customerid : '')
		.' ORDER BY date, begintime',
		 array($startdate, $enddate, $AUTH->id));
	
	if($list)
		foreach($list as $idx => $row)
		{
			$list[$idx]['userlist'] = $DB->GetAll('SELECT userid AS id, users.name
								    FROM eventassignments, users
								    WHERE userid = users.id AND eventid = ? ',
								    array($row['id']));

			if($userid && sizeof($list[$idx]['userlist']))
				foreach($list[$idx]['userlist'] as $user)
					if($user['id'] == $userid)
					{
						$list2[] = $list[$idx];
						break;
					}
		}
	if($userid)
		return $list2;	
	else	
		return $list;
}

if(!isset($_GET['a']))
	$SESSION->restore('ela', $a);
else
	$a = $_GET['a'];

$SESSION->save('ela', $a);

if(!isset($_GET['u']))
	$SESSION->restore('elu', $u);
else 
	$u = $_GET['u'];
$SESSION->save('elu', $u);

if(isset($_GET['month']) && isset($_GET['year']))
{
	$day = isset($_GET['day']) ? $_GET['day'] : 1;
	$month = $_GET['month'];
	$year = $_GET['year'];
}
else
{
	if($edate = $SESSION->get('edate'))
		list($year, $month, $day) = explode('/', $SESSION->get('edate'));
}

$day = (isset($day) ? $day : date('j',time()));
$month = (isset($month) ? sprintf('%d',$month) : date('n',time()));
$year = (isset($year) ? $year : date('Y',time()));
//Neter sgt
//$forward = $CONFIG['phpui']['timetable_days_forward'];
$forward = 7;
$warning = 7;
$critical = 14;

if(isset($_POST['search']))
	$search = $_POST['search'];
elseif(isset($_GET['s']))
	$SESSION->restore('rtsearch', $search);

if(isset($search))
{
    	if(isset($search['owner'])) $a = $search['owner'];
	$SESSION->save('ela', $a); 
} else {
    	$search['owner'] = $AUTH->id;
        $a = $AUTH->id;
	$SESSION->save('ela', $a); 
}
//Neter end

$eventlist = GetEventList($year, $month, $day, $forward, $u, $a);
$SESSION->restore('elu', $listdata['customerid']);
$SESSION->restore('ela', $listdata['userid']);

// create calendars
for($i=0; $i<$forward; $i++)
{
	$dt = mktime(0, 0, 0, $month, $day+$i, $year);
	$daylist[$i] = $dt;
}

$date = mktime(0, 0, 0, $month, $day, $year);
$daysnum = date('t', $date);
for($i=1; $i<$daysnum+1; $i++)
{
	$date = mktime(0, 0, 0, $month, $i, $year);
	$days['day'][] = date('j',$date);
	$days['dow'][] = date('w',$date);
	$days['sel'][] = ($i == $day);
}
 
$SESSION->restore('ticketinfo',$ticketinfo);

if(isset($ticketinfo))
{
	$layout['pagetitle'] = 'Wybierz termin dla zgłoszenia: ('.sprintf("%04d",$ticketinfo['tic_id']).') - '.$ticketinfo['subject'];
} else {
	$layout['pagetitle'] = trans('Timetable');
}	

$SESSION->save('edate', sprintf('%04d/%02d/%02d', $year, $month, $day));
$SMARTY->assign('period', $DB->GetRow('SELECT MAX(date) AS fromdate, MIN(date) AS todate FROM events'));
$SMARTY->assign('eventlist',$eventlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('days',$days);
$SMARTY->assign('day',$day);
$SMARTY->assign('daylist',$daylist);
$SMARTY->assign('month',$month);
$SMARTY->assign('year',$year);
$SMARTY->assign('date',$date);
$SMARTY->assign('forward',$forward);
$SMARTY->assign('customerlist',$LMS->GetCustomerNames());
//eventlist.php END

$layout['pagetitle'] = trans('Statystyki zgłoszeń serwisowych');
$SESSION->save('backto', $_SERVER['QUERY_STRING']);
if (isset($_GET['mobile']))
	{
	$mobile=true;	
	}
else
	{
	$mobile=false;
	}

$statuslist = GetTicketsSummary($a, 1, 0, 0);
$planedlist = GetTicketsSummary($a, 0, 1, 0);
$queuedlist = GetTicketsSummary($a, 0, 0, 1);
$SMARTY->assign('statuslist', $statuslist);
$SMARTY->assign('planedlist', $planedlist);
$SMARTY->assign('queuedlist', $queuedlist);
$SMARTY->assign('warning', $warning*60*60*24);
$SMARTY->assign('critical', $critical*60*60*24);

$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('search', $search);
$SMARTY->assign('mobile', $mobile);
//$SMARTY->assign('error', $error);
$SMARTY->display('rtticketsstatus.html');

?>
