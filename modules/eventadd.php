<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 *  $Id$
 */

function GetScoreList()
{
	global $DB;
	
	$scorelist = $DB->GetAll("SELECT evs_id, evs_name from eventscore WHERE evs_deleted = 0 ORDER by evs_name");
	return($scorelist);
}

if(isset($_POST['event']))
{
	$event = $_POST['event'];
	
	if(!($event['title'] || $event['description'] || $event['date']))
	{
		$SESSION->redirect('?m=eventlist');
	}
	
	if($event['title'] == '')
    		$error['title'] = trans('Event title is required!');
	
	if($event['date'] == '')
		$error['date'] = trans('You have to specify event day!');
	else
	{
		list($year,$month, $day) = explode('/',$event['date']);
		if(!checkdate($month,$day,$year))
			$error['date'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	if(!$error)
	{
		$date = mktime(0, 0, 0, $month, $day, $year);
		$event['status'] = isset($event['status']) ? 1 : 0;
		$event['ticketid'] = $event['ticketid'] ? $event['ticketid'] : 0;
		$score             = $DB->GetOne("SELECT evs_score FROM eventscore WHERE evs_id=?",array($event['category']));
		$event['score']    = $score ? $score : 0;

		if (isset($event['customerid']))
			$event['custid'] = $event['customerid'];
		if ($event['custid'] == '')
			$event['custid'] = 0;

		$DB->Execute('INSERT INTO events (title,description, date, begintime, endtime, userid, private, customerid, rtticketid, evn_evs_id, evn_evs_score) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array($event['title'], 
					$event['description'], 
					$date, 
					$event['begintime'], 
					$event['endtime'], 
					$AUTH->id, 
					$event['status'], 
					$event['custid'],
					$event['ticketid'],
					$event['category'],
					$event['score']));
		
		if(!empty($event['userlist']))
		{
			$id = $DB->GetOne('SELECT id FROM events WHERE title=? AND date=? AND begintime=? AND endtime=? AND userid=?',
				array($event['title'], $date, $event['begintime'], $event['endtime'], $AUTH->id));

			foreach($event['userlist'] as $userid)
				$DB->Execute('INSERT INTO eventassignments (eventid, userid) 
					VALUES (?, ?)', array($id, $userid));
		}

		// todo wysłanie sms przerobić
		if($event['userlist'] and $event['notifysms'])
		{
			$begintime_arr  = explode(',',($event['begintime']/100));
			$endtime_arr    = explode(',',($event['endtime']/100));
			$begintime      = sprintf('%02d',$begintime_arr[0]).':'.sprintf('%02d',$begintime_arr[1]);
			$endtime        = sprintf('%02d',$endtime_arr[0]).':'.sprintf('%02d',$endtime_arr[1]);

			if ($event['begintime'] == $event['endtime'])
			{
				$time_mess = ' o godzinie: '.$begintime;
			} else {
				$time_mess = ' w godzinach: '.$begintime.'-'.$endtime;
			}
			
			if ($event['customerid'])
			{
				$customer_info = $LMS->GetCustomer($event['customerid']);
				$phones = '';
				foreach($customer_info['contacts'] as $customer_contact)
					$phones = $phones.' '.ereg_replace(" ","",$customer_contact['phone']);
				
				$phones = trim($phones);
				
				$messagesms = 'Nowe zadanie: '.date('Y.m.d',$date).$time_mess.' o tresci: '.$event['title'].' adres: '.$customer_info['address'].' '.$customer_info['city'].' telefon '.$phones;
			} else {
				$messagesms = 'Nowe zadanie: '.date('Y.m.d',$date).$time_mess.' o tresci: '.$event['title'];
			}
				
			$createdate = $DB->GetOne('SELECT UNIX_TIMESTAMP()');
			if(!$event['whennotify'])
			{
				$senddate = $createdate;

			} else {
				$hour     = $begintime_arr[0]-$event['whennotify'];
				$minute   = $begintime_arr[1];
				$senddate = mktime($hour,$minute,0,$month,$day,$year);
			}

			$DB->Execute('INSERT INTO messages (type, cdate, subject, body, userid, sender)
				VALUES (?, ?, ?, ?, ?, ?)', array(
					MSG_SMS,
					$createdate,
					'Powiadomienie o zadaniu :'.$event['title'],
					$messagesms,
					$AUTH->id,
					'',
				));
				
			$msgid = $DB->GetLastInsertID('messages');

			foreach($event['userlist'] as $userid)
			{

				$userphone = $LMS->GetUserPhone($userid);
				$DB->Execute('INSERT INTO messageitems (messageid, customerid, userid, destination, lastdate, status)
					VALUES (?, ?, ?, ?, ?, ?)', array(
						$msgid,
						isset($event['customerid']) ? $event['customerid'] : 0,
						$userid,
						$userphone,
						$senddate,
						MSG_NEW,
					));
				$itemid = $DB->GetLastInsertID('messageitems');
					
				if($createdate = $senddate)	
				{
					$smsresult = $LMS->SendSMS($userphone, $messagesms, $msgid);

					if (!is_int($smsresult) || $smsresult == MSG_SENT)
					{
						$DB->Execute('UPDATE messageitems SET status = ?, error = ?
						               WHERE id = ?',
							array(
								is_int($smsresult) ? $smsresult : MSG_ERROR,
								is_int($smsresult) ? null : $smsresult,
								$itemid
							));
					}
				}
			}
				
		}
		
		if($event['ticketid']>0) $SESSION->redirect('?m=rtticketview&id='.$event['ticketid']);

		if(!isset($event['reuse']))
		{
			$SESSION->redirect('?'.$SESSION->get('backto'));
			//$SESSION->redirect('?m=eventlist');
		}
		
		unset($event['title']);
		unset($event['description']);
	}
}

$event['date'] = isset($event['date']) ? $event['date'] : $SESSION->get('edate');
if(empty($event['customerid']) && !empty($_GET['customerid']))
	$event['customerid'] = intval($_GET['customerid']);

if(isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year']))
{
	$event['date'] = sprintf('%04d/%02d/%02d', $_GET['year'], $_GET['month'], $_GET['day']);
}

$SESSION->restore('ticketinfo',$ticketinfo);
if(isset($ticketinfo))
{
	$layout['pagetitle'] = 'Nowe zdarzenie dla zgłoszenia: '.sprintf("%04d",$ticketinfo['tic_id']);
	$event['title']      = $ticketinfo['subject'];
	$event['ticketid']   = $ticketinfo['tic_id'];
	$event['customerid'] = $ticketinfo['cus_id'];
	$SESSION->remove('ticketinfo');
} else {
	$layout['pagetitle'] = trans('New Event');
}	
	
//$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$userlist = $LMS->GetUserNames();
$scorelist = GetScoreList();

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
	$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}

$SMARTY->assign('scorelist',$scorelist);
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('userlistsize', sizeof($userlist));
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);
$SMARTY->assign('backto', '?'.$SESSION->get('backto'));
$SMARTY->assign('hours', 
		array(0,30,100,130,200,230,300,330,400,430,500,530,
		600,630,700,730,800,830,900,930,1000,1030,1100,1130,
		1200,1230,1300,1330,1400,1430,1500,1530,1600,1630,1700,1730,
		1800,1830,1900,1930,2000,2030,2100,2130,2200,2230,2300,2330
		));
$SMARTY->display('eventadd.html');

?>
