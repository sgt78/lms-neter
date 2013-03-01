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
 *  $Id: smsing.php,v 1.56.2.3 2008/01/04 07:58:11 alec Exp $
 */

function AddSms($createdate, $senddate, $phone, $message, $customerid, $userid, $senderid)
{
	global $DB, $LMS;	

	$customerid = ($customerid != '' ? $customerid : 0);
	$userid     = ($userid     != '' ? $userid     : 0);
	$senderid   = ($senderid   != '' ? $senderid   : 0);


	// dodajemy ze statusem 0 czyli do wysłania	
	$query =    ("insert into sms_outgoing (createdate,
						senddate,
						userid,
						customerid,
						phone,
						senderid,
						status,
						message)
					values ($createdate,
						$senddate,
						$userid,
						$customerid,
						$phone,
						$senderid,
						0,
						'$message')"
			);

	$DB->Execute($query);
}

function check_phone( $phone )
{
	$length = strlen( $phone );

	if ( $length < 6 )
		return FALSE;

	$phone_charset = '1234567890+';
	$i = 0;
	while ( $i < $length )
	{
		$char = $phone[$i++];
		if ( stristr( $phone_charset, $char ) === false )
			return FALSE;
	}

	return TRUE;
}


function GetEmails($group, $network=NULL, $customergroup=NULL)
{
	global $DB, $LMS;
	
	if($group == 4)
	{
		$deleted = 1;
		$network = NULL;
		$customergroup = NULL;
	}
	else
		$deleted = 0;
	
	$disabled = ($group == 5) ? 1 : 0;
	$indebted = ($group == 6) ? 1 : 0;
	$notindebted = ($group == 7) ? 1 : 0;
	
	if($group>3) $group = 0;
	
	if($network) 
		$net = $LMS->GetNetworkParams($network);
	
	if($emails = $DB->GetAll('SELECT c.id AS id, email, '.$DB->Concat('c.lastname', "' '", 'c.name').' AS customername, pin, '
		.'COALESCE(SUM(value), 0.00) AS balance '
		.'FROM customersview c LEFT JOIN cash ON (c.id = cash.customerid) '
		.($network ? 'LEFT JOIN nodes ON (c.id = ownerid) ' : '')
		.($customergroup ? 'LEFT JOIN customerassignments ON (c.id = customerassignments.customerid) ' : '')
		.' WHERE deleted = '.$deleted
		.' AND email != \'\''
		.($group!=0 ? ' AND status = '.$group : '')
		.($network ? ' AND (ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].')' : '')
		.($customergroup ? ' AND customergroupid='.$customergroup : '')
		.' GROUP BY email, c.lastname, c.name, c.id, pin ORDER BY customername'))
	{
		if($disabled)
			$access = $DB->GetAllByKey('SELECT ownerid AS id FROM nodes GROUP BY ownerid HAVING (SUM(access) != COUNT(access))','id'); 
		
		$email2 = array();
		
		foreach($emails as $idx => $row)
		{
			if($disabled && $access[$row['id']])
				$emails2[] = $row;
			elseif($indebted)
			{
				if($row['balance'] < 0)
					$emails2[] = $row;
			}
			elseif($notindebted)
			{
				if($row['balance'] >= 0)
					$emails2[] = $row;
			}
		}
	
		if($disabled || $indebted || $notindebted)
			$emails = $emails2;
	}

	return $emails;
}

$layout['pagetitle'] = trans('SMS Send');


if(isset($_GET['phone']))
{

	$phone = preg_replace('/ /', '', $_GET['phone']);
	$phone_len = strlen($phone);

	if ((strlen($phone)) <> 9)
		$phone = '';
	$smsing['phone']      = $phone;
	$smsing['customerid'] = $_GET['customerid'];
	$smsing['userid']     = $_GET['userid'];
}


if(isset($_POST['smsing']))
{

	$smsing = $_POST['smsing'];

	if($smsing['group'] < 0 || $smsing['group'] > 7)
		$error['group'] = trans('Incorrect customers group!');

	if($smsing['phone']=='')
		$error['phone'] = trans('Sender phone is required!');
	elseif(!check_phone($smsing['phone']))
		$error['sender'] = trans('Specified phone is not correct!');

	if($smsing['body']=='')
		$error['body'] = trans('Message body is required!');

	if(!$error)
	{
		
		
		
		$phones = array($smsing['phone']);
			
		if(sizeof($phones))
		{
			$DateNow = $DB->Now();
			foreach($phones as $value)
			{

			    AddSms($DateNow, $DateNow, $value,$smsing['body'],$smsing['customerid'],$smsing['userid'], $AUTH->id);
			}
		}
		

		header('Location: ?m=smsoutgoing');
		die;
	}
}

// dodać obsługę $error[]
$SMARTY->assign('smsing', $smsing);
//$SMARTY->assign('phone',$phone);
$SMARTY->assign('error', $error);
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('userinfo', $LMS->GetUserInfo($AUTH->id));
$SMARTY->display('smssend.html');

?>
