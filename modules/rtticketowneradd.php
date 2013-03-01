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
 *  $Id: rtmessageadd.php,v 1.74 2011/04/05 13:56:19 chilek Exp $
 */

function OwnerAdd($t)
{
	global $DB, $LMS, $CONFIG, $AUTH;
	
	$zmienne_sql=array(
				$t['ticketid'],
				$t['subject_old'],
				$t['subject_new'],
				$t['owner_old'],
				$t['owner_new'],
				$t['queueid_old'],
				$t['queueid_new'],
				$t['body'],
				time(),
				$AUTH->id
				);

	$DB->Execute('INSERT INTO rtticketshistory 
		(ticketid, subject_old, subject, owner_old, owner, queueid_old, queueid, body, createtime, creatorid) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $zmienne_sql);

	$zmienne_sql=array(
				$t['subject_new'],
				$t['owner_new'],
				$t['queueid_new'],
				$t['ticketid']
				);
	$DB->Execute('UPDATE rttickets SET 
		subject= ?, owner= ?, queueid= ? 
		WHERE id= ?', $zmienne_sql);
	
}

if(isset($_POST['ticketowner']))
{
	$ticketowner = $_POST['ticketowner'];
	
	if($ticketowner['subject_new'] == '')
		$error['subject'] = trans('Message subject not specified!');
	
	if($ticketowner['body'] == '')
		$error['body'] = trans('Message body not specified!');
		
	if(($ticketowner['owner_old'] == $ticketowner['owner_new'])&&($ticketowner['queueid_old'] == $ticketowner['queueid_new']))
		{
		$error['owner_new'] = trans('Nie można przekazać zadania do siebie!');
		$error['queue'] = trans('Nie można przekazać zadania do siebie!');
		}

	if(!$error)
		{
		$queue = $LMS->GetQueueByTicketId($ticketowner['ticketid']);
		$user = $LMS->GetUserInfo($AUTH->id);
		
		OwnerAdd($ticketowner);

/*        // Users notification
		if(isset($message['notify']) && ($user['email'] || $queue['email']))
		{
			$mailfname = '';
			
			if(!empty($CONFIG['phpui']['helpdesk_sender_name']))
			{
				$mailfname = $CONFIG['phpui']['helpdesk_sender_name'];
				
				if($mailfname == 'queue')
					$mailfname = $queue['name'];
				elseif($mailfname == 'user')
					$mailfname = $user['name'];
				
				$mailfname = '"'.$mailfname.'"';
			}

			$mailfrom = $user['email'] ? $user['email'] : $queue['email'];

	        $headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = sprintf("[RT#%06d] %s", $message['ticketid'], $DB->GetOne('SELECT subject FROM rttickets WHERE id = ?', array($message['ticketid'])));
			$headers['Reply-To'] = $headers['From'];

            $sms_body = $headers['Subject']."\n".$message['body'];
			$body = $message['body']."\n\nhttp"
				.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
				.$_SERVER['HTTP_HOST']
				.substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
				.'?m=rtticketview&id='.$message['ticketid'];

			if(chkconfig($CONFIG['phpui']['helpdesk_customerinfo'])
				&& ($cid = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($message['ticketid']))))
			{
				$info = $DB->GetRow('SELECT '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
						email, address, zip, city, (SELECT phone FROM customercontacts 
							WHERE customerid = customers.id ORDER BY id LIMIT 1) AS phone
						FROM customers WHERE id = ?', array($cid));

				$body .= "\n\n-- \n";
				$body .= trans('Customer:').' '.$info['customername']."\n";
				$body .= trans('ID:').' '.sprintf('%04d', $cid)."\n";
				$body .= trans('Address:').' '.$info['address'].', '.$info['zip'].' '.$info['city']."\n";
				$body .= trans('Phone:').' '.$info['phone']."\n";
				$body .= trans('E-mail:').' '.$info['email'];

                $sms_body .= "\n";
                $sms_body .= trans('Customer:').' '.$info['customername'];
                $sms_body .= ' '.sprintf('(%04d)', $ticket['customerid']).'. ';
                $sms_body .= $info['address'].', '.$info['zip'].' '.$info['city'];
                if ($info['phone'])
                    $sms_body .= '. '.trans('Phone:').' '.$info['phone'];
			}

            // send email
			if($recipients = $DB->GetCol('SELECT DISTINCT email
			        FROM users, rtrights 
					WHERE users.id=userid AND queueid = ? AND email != \'\' 
						AND (rtrights.rights & 8) = 8 AND users.id != ?
						AND deleted = 0 AND (ntype & ?) = ?',
					array($queue['id'], $AUTH->id, MSG_MAIL, MSG_MAIL))
			) {
				foreach($recipients as $email) {
					$headers['To'] = '<'.$email.'>';

					$LMS->SendMail($email, $headers, $body);
				}
			}

            // send sms
			if(!empty($CONFIG['sms']['service']) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
			        FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND users.id != ?
						AND deleted = 0 AND (ntype & ?) = ?',
					array($queue['id'], $AUTH->id, MSG_SMS, MSG_SMS)))
			) {
				foreach($recipients as $phone) {
					$LMS->SendSMS($phone, $sms_body);
				}
			}
		}
*/
	$SESSION->redirect('?m=rtticketview&id='.$ticketowner['ticketid']);
	}
}
else
{
	if($_GET['ticketid'])
	{
		$queue = $LMS->GetQueueByTicketId($_GET['ticketid']);
		
		$ticket = $DB->GetRow('SELECT t.id, t.queueid, q.name AS queuename, t.subject, t.owner, c.name AS ownername, 
		    t.createtime
		    FROM rttickets t
		    LEFT JOIN users c ON (t.owner = c.id)
		    LEFT JOIN rtqueues q ON (t.queueid = q.id)
		    WHERE t.id = ?', array($_GET['ticketid']));
	}

	$user = $LMS->GetUserInfo($AUTH->id);
	
	$ticketowner['ticketid'] = $_GET['ticketid'];
	$ticketowner['subject_old'] = $ticket['subject'];
	$ticketowner['subject_new'] = $ticket['subject'];
	$ticketowner['owner_old'] = $ticket['owner'];
	$ticketowner['ownername_old'] = $ticket['ownername'];
	$ticketowner['owner_new'] = $ticket['owner'];
	$ticketowner['ownername_new'] = $ticket['ownername'];
	$ticketowner['queueid_old'] = $ticket['queueid'];
	$ticketowner['queuename_old'] = $ticket['queuename'];
	$ticketowner['queueid_new'] = $ticket['queueid'];
	$ticketowner['queuename_new'] = $ticket['queuename'];
}

$layout['pagetitle'] = trans('Przekazywanie zadania: $a',sprintf("%06d",$ticketowner['ticketid']));

//$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('ticketowner', $ticketowner);
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);

$SMARTY->display('rtticketowneradd.html');

?>
