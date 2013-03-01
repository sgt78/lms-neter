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
 *  $Id: accountlist.php,v 1.28.2.2 2008/01/04 07:58:01 alec Exp $
 */

function GetSmsIncomingList($order='date,asc', $customerid=NULL, $userid=NULL, $senderid=NULL)
{
    global $DB;
    
    list($order,$direction) = sscanf($order, '%[^,],%s');
    
    ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';
    
    switch($order)
    {
	case 'date':
		$sqlord = " ORDER BY SMS_OUTGOING.DATE $direction";
	break;
    }
    
    $list = $DB->GetAll('SELECT sms_incoming.id as id, 
				sms_incoming.createdate as createdate, 
				sms_incoming.senddate as senddate, 
    				sms_incoming.senderid as senderid,
    				sms_incoming.sender_phone as sender_phone,
				sms_incoming.category as category,
				sms_incoming.status as status,
				sms_incoming.message 
    			   from sms_incoming;'
			.($sqlord != '' ? $sqlord : '')
    							
    					);
    					
    					
    $list['total'] = sizeof($list);
    $list['order'] = $order;
    
    return $list;
}



if ($SESSION->is_set('alp') && !isset($_GET['page']))
	$SESSION->restore('alp', $_GET['page']);
	    
$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = 50;

/*
 * (!isset($LMS->CONFIG['phpui']['smslist_pagelimit']) ? $listdata['total'] : $LMS->CONFIG['phpui']['smslist_pagelimit']);
 */

$start = ($page - 1) * $pagelimit;

$SESSION->save('alp', $page);


$layout['pagetitle'] = trans('SMS Outgoing List');

$smslist = GetSmsIncomingList($o, $u, $t, $k);

 
$listdata['total'] = $smslist['total'];
$listdata['order'] = $smslist['order'];
$listdata['direction'] = $smslist['direction'];
$listdata['type'] = $smslist['type'];
$listdata['kind'] = $smslist['kind'];
$listdata['customer'] = $smslist['customer'];

unset($smslist['total']);
unset($smslist['order']);
unset($smslist['type']);
unset($smslist['kind']);
unset($smslist['customer']);
unset($smslist['direction']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('smslist',$smslist);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('customerlist',$LMS->GetCustomerNames());
$SMARTY->display('smsoutgoing.html');

?>
