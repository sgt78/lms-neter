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

if (!check_conf('privileges.reports'))
	access_denied();

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'stats': /******************************************/

		$days  = !empty($_GET['days']) ? intval($_GET['days']) : intval($_POST['days']);
		$times = !empty($_GET['times']) ? intval($_GET['times']) : intval($_POST['times']);
		$queue = !empty($_GET['queue']) ? intval($_GET['queue']) : intval($_POST['queue']);
		$categories = !empty($_GET['categories']) ? $_GET['categories'] : $_POST['categories'];
		
		if($queue)
			$where[] = 'queueid = '.$queue;
		if($days)
			$where[] = 'rttickets.createtime > '.mktime(0, 0, 0, date('n'), date('j')-$days);
		$catids = (is_array($categories) ? array_keys($categories) : NULL);
		if (!empty($catids))
			$where[] = 'tc.categoryid IN ('.implode(',', $catids).')';
		else
			$where[] = 'tc.categoryid IS NULL';
	
    		if($list = $DB->GetAll('SELECT COUNT(*) AS total, customerid, '
				    .$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername
		               	    FROM rttickets
		               	    LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
				    LEFT JOIN customers ON (customerid = customers.id)
				    WHERE customerid != 0'
				    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
				    .' GROUP BY customerid, customers.lastname, customers.name'
				    .($times ? ' HAVING COUNT(*) > '.$times : '')
				    .' ORDER BY total DESC'))
		{
    			$customer = $DB->GetAllByKey('SELECT COUNT(*) AS total, customerid
		               	    FROM rttickets 
		               	    LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
				    WHERE cause = 1'
				    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
				    .' GROUP BY customerid', 'customerid');
    			$company = $DB->GetAllByKey('SELECT COUNT(*) AS total, customerid
		               	    FROM rttickets 
		               	    LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
				    WHERE cause = 2'
				    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
				    .' GROUP BY customerid', 'customerid');
			
			foreach($list as $idx => $row)
			{
				$list[$idx]['customer'] = isset($customer[$row['customerid']]) ? $customer[$row['customerid']]['total'] : 0;
				$list[$idx]['company'] = isset($company[$row['customerid']]) ? $company[$row['customerid']]['total'] : 0;
				$list[$idx]['other'] = $list[$idx]['total'] - $list[$idx]['customer'] - $list[$idx]['company'];
			}
		}

		$layout['pagetitle'] = trans('Requests Stats');

		$SMARTY->assign('list', $list);
		$SMARTY->display('rtprintstats.html');
	break;

	case 'ticketslist': /******************************************/

		$days 	  = !empty($_GET['days']) ? intval($_GET['days']) : intval($_POST['days']);
		$customer = !empty($_GET['customer']) ? intval($_GET['customer']) : intval($_POST['customer']);
		$queue 	  = !empty($_GET['queue']) ? intval($_GET['queue']) : intval($_POST['queue']);
		$status   = isset($_GET['status']) ? $_GET['status'] : $_POST['status'];
		$subject  = !empty($_GET['subject']) ? $_GET['subject'] : $_POST['subject'];
		$extended = !empty($_GET['extended']) ? true : !empty($_POST['extended']) ? true : false;
		$categories = !empty($_GET['categories']) ? $_GET['categories'] : $_POST['categories'];

		if($queue)
			$where[] = 'queueid = '.$queue;
		if($customer)
			$where[] = 'customerid = '.$customer;
		if($days)
			$where[] = 'rttickets.createtime < '.mktime(0, 0, 0, date('n'), date('j')-$days);
		if($subject)
			$where[] = 'rttickets.subject ?LIKE? '.$DB->Escape("%$subject%");
		$catids = (is_array($categories) ? array_keys($categories) : NULL);
		if (!empty($catids))
			$where[] = 'tc.categoryid IN ('.implode(',', $catids).')';
		else
			$where[] = 'tc.categoryid IS NULL';

		if($status != '')
		{
			if($status == -1)
				$where[] = 'rttickets.state != '.RT_RESOLVED;
			else
    				$where[] = 'rttickets.state = '.intval($status);
		}

    		$list = $DB->GetAll('SELECT rttickets.id, createtime, customerid, subject, requestor, '
			.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername '
			.(!empty($_POST['contacts']) || !empty($_GET['contacts'])
				? ', address, (SELECT phone
				FROM customercontacts
				WHERE customerid = customers.id LIMIT 1) AS phone ' : '')
		        .'FROM rttickets
			LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
			LEFT JOIN customers ON (customerid = customers.id)
			WHERE state != '.RT_RESOLVED
			.(isset($where) ? ' AND '.implode(' AND ', $where) : '')
			.' ORDER BY createtime');

		if ($list && $extended)
		{
			$tickets = implode(',', array_keys($list));
			if ($content = $DB->GetAll('(SELECT body, ticketid, createtime, 0 AS note
				FROM rtmessages
				WHERE ticketid in ('.$tickets.'))
				UNION
				(SELECT body, ticketid, createtime, 1 AS note
				FROM rtnotes
				WHERE ticketid in ('.$tickets.'))
			        ORDER BY createtime'))
			{
				foreach ($content as $idx => $row)
				{
					$list[$row['ticketid']]['content'][] = array(
						'body' => trim($row['body']),
						'note' => $row['note'],
					);
					unset($content[$idx]);
				}
			}
		}

		$layout['pagetitle'] = trans('List of Requests');

		$SMARTY->assign('list', $list);
		$SMARTY->display($extended ? 'rtprinttickets-ext.html' : 'rtprinttickets.html');
	break;

	case 'userstats': /******************************************/
		if($_POST['datefrom'])
		{
			list($year, $month, $day) = explode('/', $_POST['datefrom']);
        	$datefrom = mktime(0,0,0, $month, $day, $year);
		    $where[] = 'rttickets.createtime > '.$datefrom;
		    $pagetitle[] = ' od :'.$_POST['datefrom'];
		}        
		if($_POST['dateto'])
		{
			list($year, $month, $day) = explode('/', $_POST['dateto']);
        	$dateto = mktime(0,0,0, $month, $day+1, $year);
		    $where[] = 'rttickets.createtime < '.$dateto;
		    $pagetitle[] = ' od :'.$_POST['dateto'];
		}        
		if($_POST['days'])
		{
			$days    = $_POST['days']*24*60*60;
			$nowtime = mktime(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
			$where[] = "(if( rttickets.resolvetime=0, $nowtime , rttickets.resolvetime ) - rttickets.createtime > ".$days.")";
			//$where[] = 'rttickets.createtime < '.mktime(0, 0, 0, date('n'), date('j')-$_POST['days']);
		    $pagetitle[] = ' uptime dni :'.$_POST['days'];		
		}

		$layout['pagetitle'] = 'Zestawienie zgłoszeń serwisowych'.(isset($pagetitle) ? implode('', $pagetitle) : '');


		$query = "SELECT COUNT(CASE state WHEN 0 THEN 1 END) AS new,
       					 COUNT(CASE state WHEN 1 THEN 1 END) AS opened,
						 COUNT(CASE state WHEN 2 THEN 1 END) AS resolved,
						 COUNT(CASE state WHEN 3 THEN 1 END) AS dead,
						 owner, 
						 name
					FROM rttickets join users on users.id = rttickets.owner 
				    WHERE 1=1"
				    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')."
				   GROUP BY owner
				  HAVING (opened > 0 OR new > 0 OR resolved > 0)";
		$list = $DB->GetAll($query);

		//print_r($where);

		$SMARTY->assign('list', $list);
		$SMARTY->display('rtprintuserstats.html');
	break;

	default:
		$categories = $LMS->GetCategoryListByUser($AUTH->id);

		$layout['pagetitle'] = trans('Reports');
		
		if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
		{
			$SMARTY->assign('customers', $LMS->GetCustomerNames());
		}

        $SMARTY->assign('userlist', $LMS->GetUserNames());		
		$SMARTY->assign('queues', $LMS->GetQueueList());
		$SMARTY->assign('categories', $categories);
		$SMARTY->display('rtprintindex.html');
	break;
}

?>
