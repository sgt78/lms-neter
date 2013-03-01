<?php

/*
 * LMS version 1.9.1 Jumar
 *
 *  (C) Copyright 2001-2006 LMS Developers
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
 *  $Id: invoicenew.php,v 1.48 2006/02/05 16:48:04 alec Exp $
 */

$layout['pagetitle'] = trans('New Invoice');

$customers = $voip->GetCustomerNames();
$tariffs = $voip->Get_Tariffs();
//$taxeslist = $LMS->GetTaxes();
//$numberplanlist = $LMS->GetNumberPlans(DOC_INVOICE);
$SESSION->restore('invoicecontents', $contents);
$SESSION->restore('invoicecustomer', $customer);
$SESSION->restore('invoice', $invoice);
$SESSION->restore('invoicenewerror', $error);
$itemdata = r_trim($_POST);

switch($_GET['action'])
{
	case 'init':

    		unset($invoice);
    		unset($contents);
    		unset($customer);
    		unset($error);

		// get default invoice's numberplanid and next number
		//$invoice['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans WHERE doctype=? AND isdefault=1', array(DOC_INVOICE));
		$invoice['cdate'] = date('Y/m/d');
		$invoice['number']='OB-FV '.$voip->GetNextInvoiceNumber().'/'.date('m').'/'.date('Y');
		//$invoice['paytime'] = 14;
		if($_GET['customerid'] != '' && $voip->CustomerExists($_GET['customerid']))
			$customer = $LMS->GetCustomer($_GET['customerid']);
	break;

	case 'additem':
		$itemdata = r_trim($_POST);
		foreach(array('count', 'discount', 'valuenetto', 'valuebrutto') as $key)
			$itemdata[$key] = f_round($itemdata[$key]);
		
		if($itemdata['count'] > 0 && $itemdata['name'] != '')
		{
			$taxvalue = 22;
			if($itemdata['valuenetto'] != 0)
			{
				$itemdata['valuenetto'] = f_round($itemdata['valuenetto'] - $itemdata['valuenetto'] * f_round($itemdata['discount'])/100);
				$itemdata['valuebrutto'] = round($itemdata['valuenetto'] * ($taxvalue / 100 + 1),2);
			}
			elseif($itemdata['valuebrutto'] != 0)
			{
				$itemdata['valuebrutto'] = f_round($itemdata['valuebrutto'] - $itemdata['valuebrutto'] * f_round($itemdata['discount'])/100);
				$itemdata['valuenetto'] = round($itemdata['valuebrutto'] / ($taxvalue + 100) * 100, 2);
			}
			
			// str_replace->f_round here is needed because of bug in some PHP versions
			$itemdata['s_valuenetto'] = f_round($itemdata['valuenetto'] * $itemdata['count']);
			$itemdata['s_valuebrutto'] = f_round($itemdata['valuebrutto'] * $itemdata['count']);
			$itemdata['valuenetto'] = f_round($itemdata['valuenetto']);
			$itemdata['valuebrutto'] = f_round($itemdata['valuebrutto']);
			$itemdata['count'] = f_round($itemdata['count']);
			$itemdata['discount'] = f_round($itemdata['discount']);
			$itemdata['tax'] = $taxeslist[$itemdata['taxid']]['label'];
			$itemdata['posuid'] = (string) getmicrotime();
			$contents[] = $itemdata;
		}
	break;

	case 'deletepos':
		if(sizeof($contents))
			foreach($contents as $idx => $row)
				if($row['posuid'] == $_GET['posuid']) 
					unset($contents[$idx]);
	break;

	case 'setcustomer':

		unset($invoice); 
		unset($customer);
		unset($error);
		
		if($invoice = $_POST['invoice'])
			foreach($invoice as $key => $val)
				$invoice[$key] = $val;
		
		//$invoice['paytime'] = sprintf('%d', $invoice['paytime']);
		
		//if($invoice['paytime'] < 0)
		//	$invoice['paytime'] = 14;

		$invoice['customerid'] = $_POST['customerid'];
		
		if($invoice['cdate'])
		{
			list($year, $month, $day) = split('/',$invoice['cdate']);
			if(checkdate($month, $day, $year)) 
			{
			//	$invoice['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
			}
			else
			{
				$error['cdate'] = trans('Incorrect date format!');
				$invoice['cdate'] = date('Y/m/d');
				break;
			}
		}
		else $invoice['cdate'] = date('Y/m/d');
/*		if($invoice['cdate'] && !$invoice['cdatewarning'])
		{
			$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents WHERE type = 1');
			if($invoice['cdate'] < $maxdate)
			{
				$error['cdate'] = trans('Last date of invoice settlement is $a. If sure, you want to write invoice with date of $b, then click "Submit" again.',date('Y/m/d H:i', $maxdate), date('Y/m/d H:i', $invoice['cdate']));
				$invoice['cdatewarning'] = 1;
			}
		}
*/
		if($invoice['number'])
		{
			//if(!eregi('^[0-9]+$', $invoice['number']))
			//	$error['number'] = trans('Invoice number must be integer!');
			if($voip->DocumentExists($invoice['number']))
				$error['number'] = trans('Invoice number $a already exists!', $invoice['number']);
		}
		
		if(!$error)
			if($voip->CustomerExists(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer'])))
				$customer = $LMS->GetCustomer(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer']));

	break;

	case 'save':

		if($contents && $customer)
		{
			if(!$invoice['number'])
				$invoice['number']='OB-FV '.$voip->GetNextInvoiceNumber().'/'.date('m').'/'.date('Y');
			else
			{
				if($voip->DocumentExists($invoice['number']))
					$error['number'] = trans('Invoice number $a already exists!', $invoice['number']);
				
				if($error)
					$invoice['number']='OB-FV '.$voip->GetNextInvoiceNumber().'/'.date('m').'/'.date('Y');
			}
				
			$iid = $voip->AddInvoice(array('customer' => $customer, 'contents' => $contents, 'invoice' => $invoice));
		
			$SESSION->remove('invoicecontents');
			$SESSION->remove('invoicecustomer');
			$SESSION->remove('invoice');
			$SESSION->remove('invoicenewerror');
			$SESSION->redirect('?m=v_invoice&id='.$iid);
		}
	break;
}

if($invoice['paytype'] == '')
	$invoice['paytype'] = trans('CASH');

$SESSION->save('invoice', $invoice);
$SESSION->save('invoicecontents', $contents);
$SESSION->save('invoicecustomer', $customer);
$SESSION->save('invoicenewerror', $error);

if($_GET['action'] != '')
{
	// redirect, �eby refreshem nie spierdoli� faktury
	$SESSION->redirect('?m=v_invoicenew');
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('customers', $customers);
//$SMARTY->assign('taxeslist', $taxeslist);
//$SMARTY->assign('numberplanlist', $numberplanlist);
$SMARTY->display('v_invoicenew.html');

?>
