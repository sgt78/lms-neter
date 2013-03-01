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
 *  $Id: invoice.php,v 1.66.2.3 2008/01/04 07:58:10 alec Exp $
 */


	if(isset($_GET['action']))
	{

        switch($_GET['action'])
        {
            case 'paymentadd' :
                $payment = $_POST['payment'];
                
				$saldo = $DB->GetOne('SELECT SUM(cash.value) 
				                       FROM cash 
				                      WHERE cash.docid = ?',array($payment['docid']));
				                      

				if($saldo-$payment['value'] < 0)
					$error['value'] = 'Za duża wartość, zrobisz nadpłatę';

				if($payment['date'])
				{				
					list($year, $month, $day) = split('/',$payment['date']);
					$payment['cdate'] = mktime(0,0,0,$month,$day,$year);	
				} else {
					$payment['cdate'] = time();
				}
						
				if(!$error)
				{

					$payment['value'] = str_replace(',','.',$payment['value']);
					
					$LMS->AddBalance(array(
						'time' 			=> $payment['cdate'],
						'value' 		=> $payment['value']*-1,
						'type' 			=> 1,
						'customerid' 	=> $payment['customerid'],
						'comment' 		=> $payment['comment'],
						'docid' 		=> $payment['docid'],
						'itemid'		=> 1
					));
					
					if($saldo-$payment['value'] == 0)
						{
							$DB->Execute('UPDATE documents set closed = 1 where id = '.$payment['docid']);
						}
				}

				$SESSION->redirect('?m=nm_fv_kosztowa_info&id='.$payment['docid']);
            break;
            case 'paymentdel':
            	if(!$_POST['cash'])
            		break; # po prostu opuszczamy case i wchodzimy na info
            		
            	$DB->Execute("DELETE FROM cash where id = ?",array($_POST['cash']['id']));
            	//od-rozliczamy dokument
            	$DB->Execute("UPDATE documents SET closed=0 WHERE id=?",array($_POST['cash']['dokid']));
            		
            break;
            case 'paymentasign':
            	if(!$_POST['cash'])
            		break; # po prostu opuszczamy case i wchodzimy na info

				$cash = $_POST['cash'];
				
				// POBIERAMY JESZCZE RAZ DANE DLA PEWNOŚCI
				
				$cashrow  = $DB->GetAll("SELECT id,time,type,value,customerid,comment from cash where id =?",array($cash['id']));
				$docsaldo = $DB->GetOne("SELECT sum(value) from cash where docid =?",array($cash['doc_id']));
			
				foreach($cashrow as $key => $value)
					$cashrow = $value;
			
				if(($cashrow['value']*-1) == $docsaldo)
				{
					// nadpłata jest równa fakturze - trzeba tylko przypisać dokid
					$DB->Execute("UPDATE cash set docid = ? where id = ?",array($cash['doc_id'],$cash['id']));
					// rozliczamy fakturę
					$DB->Execute("UPDATE documents set closed=1 where id =?",array($cash['doc_id']));
				} 
				elseif(($cashrow['value']*-1) < $docsaldo)
				{
					// nadpłata jest mniejsza niż faktura - trzeba tylko przypisać dokid
					$DB->Execute("UPDATE cash set docid = ? where id = ?",array($cash['doc_id'],$cash['id']));
				}
				else
				{
					// nadpłata jest większa niż ta faktura, trzeba ją rozbić
					$LMS->AddBalance(array(
						'time' 			=> $cashrow['time'],
						'value' 		=> $cashrow['value']+$docsaldo,
						'type' 			=> 1,
						'customerid' 	=> $cashrow['customerid'],
						'comment' 		=> $cashrow['comment'],
						'docid' 		=> 0,
						'itemid'		=> 1
					));
					$DB->Execute("UPDATE cash set value =?, docid =? where id =?",
									array($docsaldo*-1,$cash['doc_id'],$cash['id']));
					// rozliczamy fakturę
					$DB->Execute("UPDATE documents set closed=1 where id =?",array($cash['doc_id']));
				}

            break;
        }
	}


	if(isset($_GET['id']) || $cash['doc_id'])
		$invoiceid = isset($_GET['id']) ? $_GET['id'] : $cash['doc_id'];
	else
		$SESSION->redirect('?m=nm_fv_kosztowe_lista');

	$invoice = $LMS->GetInvoiceContent($invoiceid);
	$pozostalo = $invoice['total'];

	$wplaty  = $DB->GetAll('SELECT cash.id, time, users.name as username, value, comment 
							  FROM cash 
						 	  LEFT JOIN users on userid=users.id
						 	 WHERE type = 1 and docid=?' ,array($invoiceid));
	 
	if(isset($wplaty))
	{
		foreach($wplaty as $idx => $row)
		{
			$wplaty[$idx][value] = $row['value']*-1;
			$pozostalo = $pozostalo + $row['value'];
		}
	} else {
	// wplaty z KW na fakture kosztowa
		$wplaty = $DB->GetAll('SELECT 0, cdate as time, users.name as username, value, description as comment
								 FROM receiptcontents
								 LEFT JOIN documents on receiptcontents.docid = documents.id
								 LEFT JOIN users on documents.userid=users.id
								WHERE receiptcontents.reference=?',array($invoiceid));

		if(isset($wplaty))
		{
			foreach($wplaty as $idx => $row)
			{
				$wplaty[$idx][value] = $row['value']*-1;
				$pozostalo = $pozostalo + $row['value'];
			}
		} 
	}
	
	if($nadplaty = $DB->GetAll("SELECT cash.id, time, users.name as username, value, comment
								   FROM cash
								   LEFT JOIN users on userid = users.id
								  WHERE type = 1 
								    AND docid = 0 
								    AND customerid =?",array($invoice['customerid'])))
	{
		
	}


	$layout['pagetitle'] = trans('Faktura kosztowa');

$SMARTY->assign('invoice',$invoice);
$SMARTY->assign('wplaty',$wplaty);
$SMARTY->assign('nadplaty',$nadplaty);
$SMARTY->assign('pozostalo',$pozostalo);
$SMARTY->display('nm_fv_kosztowa_info.html');

?>
