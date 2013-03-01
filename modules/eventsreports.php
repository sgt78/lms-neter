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
 *  $Id: customerprint.php,v 1.9.2.2 2008/01/04 07:58:06 alec Exp $
 */

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'userwork':
	
		$user = $_POST['user'];
		
		if($_POST['from'])
		{
			list($year, $month, $day) = split('/',$_POST['from']);
			$from = mktime(0,0,0,$month,$day,$year);
		} else {
			$from = mktime( 0,0,0,date("n"),1,date("Y") );
			$_POST['from'] = date('d/m/Y',$from);
		}
		if($_POST['to'])
		{
			list($year, $month, $day) = split('/',$_POST['to']);
			$to = mktime(0,0,0,$month,$day+1,$year);
		} else {
			$to = mktime( 0,0,0,date("n"),date("j"),date("Y") );
			$_POST['to'] = date('d/m/Y',$to);
		}
		
		
		$work = $DB->GetAll("SELECT id,
								   title,
								   evn_evs_id,
								   evn_evs_score,
								   closed,
								   eventassignments.userid
							  FROM events LEFT JOIN eventassignments ON events.id = eventassignments.eventid
							 WHERE events.date > ?
							   AND events.date < ?
							   ",array($from,$to));
		$tmp = array();
		$tmp = $work;

		foreach($work as $key => $row)
		{
			if($row['userid'] == $user)
			{				
				foreach($tmp as $tmpkey => $tmprow) if($tmprow['id'] == $row['id']) $row['count']++;
				$output[] = $row;
			} 
		}
		unset($tmp);
		unset($work);
		
		$rep1[0]['name'] = 'Ilość zadań ogółem';
		$rep1[1]['name'] = 'Ilość punktów ogółem';
		$rep1[2]['name'] = 'Ilość zadań bez punktów';
		$rep1[3]['name'] = 'Ilość zadań nierozliczonych';
		$rep1[4]['name'] = 'Ilość punktów z zadań nierozliczonych';
		$rep1[5]['name'] = 'Ilość zadań rozliczonych';
		$rep1[6]['name'] = 'Ilość punktów z zadań rozliczonych';
				
		foreach($output as $key => $value)
		{
			// ########### Report 1 : statystyka w zadanym okresie //
			$rep1[0]['value']++;
			$rep1[1]['value'] = $rep1[1]['value'] + ($value['evn_evs_score']/$value['count']);
			switch($value['evn_evs_id'])
			{
				case 0:
					$rep1[2]['value']++;
					break;
				default:
					switch($value['closed'])
					{
						case 0:
							$rep1[3]['value']++;
							$rep1[4]['value'] = $rep1[4]['value'] + ($value['evn_evs_score']/$value['count']);
							break;
						case 1:
							$rep1[5]['value']++;
							$rep1[6]['value'] = $rep1[6]['value'] + ($value['evn_evs_score']/$value['count']);
							break;	
					}
					break;
			}
			// ########### Report 2 : statystyka według score //
			$rep2[$value['evn_evs_id']]['ilosc']++;
			$rep2[$value['evn_evs_id']]['punkty'] = $rep2[$value['evn_evs_id']]['punkty'] + ($value['evn_evs_score']/$value['count']);
			$rep2[$value['evn_evs_id']]['zal']    = ($value['closed'] == 1) ? $rep2[$value['evn_evs_id']]['zal'] + ($value['evn_evs_score']/$value['count']) : $rep2[$value['evn_evs_id']]['zal'];
			$rep2[$value['evn_evs_id']]['niezal'] = ($value['closed'] == 0) ? $rep2[$value['evn_evs_id']]['niezal'] + ($value['evn_evs_score']/$value['count']) : $rep2[$value['evn_evs_id']]['niezal'];
			
		}

		$score = $DB->GetAll("SELECT evs_id, evs_name FROM eventscore");
		
		foreach($score as $key => $value)
		{
			$score[$key]['ilosc']  = $rep2[$value['evs_id']]['ilosc'];
			$score[$key]['punkty'] = $rep2[$value['evs_id']]['punkty'];
			$score[$key]['zal']    = $rep2[$value['evs_id']]['zal'];
			$score[$key]['niezal'] = $rep2[$value['evs_id']]['niezal'];
		}
		
		foreach($score as $key => $value)
		{
			$suma['ilosc']  = $suma['ilosc'] + $value['ilosc'];
			$suma['punkty'] = $suma['punkty'] + $value['punkty'];
			$suma['zal']    = $suma['zal'] + $value['zal'];
			$suma['niezal'] = $suma['niezal'] + $value['niezal'];
		}

		$layout['pagetitle'] = 'Karta pracownika '.$LMS->GetUserName($user).' za okres: '.$_POST['from'].' '.$_POST['to'];
		
		$SMARTY->assign('rep1',$rep1);
		$SMARTY->assign('rep2',$score);
		$SMARTY->assign('suma',$suma);
		
		$SMARTY->display('printeventreport.html');
	break;

	
	default: /*******************************************************/
	
		$layout['pagetitle'] = trans('Reports');
    			    
		for($i=$yearstart; $i<$yearend+1; $i++)
			$statyears[] = $i;
		for($i=1; $i<13; $i++)
			$months[$i] = strftime('%B', mktime(0,0,0,$i,1));

		$users = $LMS->GetUserNames();
		$SMARTY->assign('users', $users);

		$SMARTY->assign('currmonth', date('n'));
		$SMARTY->assign('curryear', date('Y'));
		$SMARTY->assign('statyears', $statyears);
		$SMARTY->assign('months', $months);
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
		$SMARTY->assign('printmenu', 'customer');
		$SMARTY->display('eventsreports.html');
	break;
}

?>
