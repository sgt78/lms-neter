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
 *  $Id: invoiceedit.php,v 1.23.2.2 2008/01/04 07:58:10 alec Exp $
 */

$taxeslist = $LMS->GetTaxes();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if(isset($_GET['id']) && $action=='edit')
{
    $invoice = $LMS->GetInvoiceContent($_GET['id']);

    $SESSION->remove('invoicecontents');
    $SESSION->remove('invoicecustomer');

    $i = 0;
    foreach ($invoice['content'] as $item) {
		$i++;
        $nitem['tariffid']		= $item['tariffid'];
		$nitem['name']			= $item['description'];
		$nitem['prodid']		= $item['prodid'];
        $nitem['count']			= str_replace(',','.',$item['count']);
		$nitem['discount']	= str_replace(',' ,'.', $item['pdiscount']);
		$nitem['pdiscount']	= str_replace(',' ,'.', $item['pdiscount']);
		$nitem['vdiscount']	= str_replace(',' ,'.', $item['vdiscount']);
		$nitem['jm']			= str_replace(',','.',$item['content']);
        $nitem['valuenetto']	= str_replace(',','.',$item['basevalue']);
        $nitem['valuebrutto']	= str_replace(',','.',$item['value']);
		$nitem['s_valuenetto']	= str_replace(',','.',$item['totalbase']);
        $nitem['s_valuebrutto']	= str_replace(',','.',$item['total']);
		$nitem['tax']			= $taxeslist[$item['taxid']]['label'];
		$nitem['taxid']			= $item['taxid'];
		$nitem['posuid']		= $i;
		$SESSION->restore('invoicecontents', $invoicecontents);
		$invoicecontents[] = $nitem;
		$SESSION->save('invoicecontents', $invoicecontents);
    }
    $SESSION->save('invoicecustomer', $LMS->GetCustomer($invoice['customerid']));
    $invoice['oldcdate'] = $invoice['cdate'];
    $SESSION->save('invoice', $invoice);
    $SESSION->save('invoiceid', $invoice['id']);
}

$SESSION->restore('invoicecontents', $contents);
$SESSION->restore('invoicecustomer', $customer);
$SESSION->restore('invoice', $invoice);
$SESSION->restore('invoiceediterror', $error);
$itemdata = r_trim($_POST);

$layout['pagetitle'] = trans('Invoice Edit: $a', $invoice['extnumber']);

if(isset($_GET['customerid']) && $_GET['customerid'] != '' && $LMS->CustomerExists($_GET['customerid']))
	$action = 'setcustomer';

switch($action)
{
	case 'additem':
		$itemdata = r_trim($_POST);

		unset($error);

		$itemdata['discount'] = str_replace(',', '.', $itemdata['discount']);
		$itemdata['pdiscount'] = 0;
		$itemdata['vdiscount'] = 0;
		if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $itemdata['discount'])) {
			$itemdata['pdiscount'] = ($itemdata['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($itemdata['discount']) : 0);
			$itemdata['vdiscount'] = ($itemdata['discount_type'] == DISCOUNT_AMOUNT ? floatval($itemdata['discount']) : 0);
		}
		if ($itemdata['pdiscount'] < 0 || $itemdata['pdiscount'] > 99.9 || $itemdata['vdiscount'] < 0)
			$error['discount'] = trans('Wrong discount value!');

		if ($error)
			break;
		
		foreach(array('count', 'discount', 'pdiscount', 'vdiscount', 'valuenetto', 'valuebrutto') as $key)
			$itemdata[$key] = round((float) str_replace(',', '.', $itemdata[$key]), 2);

		if ($itemdata['count'] > 0 && $itemdata['name'] != '')
		{
			$taxvalue = $taxeslist[$itemdata['taxid']]['value'];
			if ($itemdata['valuenetto'] != 0)
			{
				$itemdata['valuenetto'] = f_round(($itemdata['valuenetto'] - $itemdata['valuenetto'] * f_round($itemdata['pdiscount']) / 100) - $itemdata['vdiscount']);
				$itemdata['valuebrutto'] = round($itemdata['valuenetto'] * ($taxvalue / 100 + 1), 2);
			}
			elseif ($itemdata['valuebrutto'] != 0)
			{
				$itemdata['valuebrutto'] = f_round(($itemdata['valuebrutto'] - $itemdata['valuebrutto'] * $itemdata['pdiscount'] / 100) - $itemdata['vdiscount']);
				$itemdata['valuenetto'] = round($itemdata['valuebrutto'] / ($taxvalue / 100 + 1), 2);
			}
			
			// str_replace here is needed because of bug in some PHP versions (4.3.10)
			$itemdata['s_valuebrutto'] 	= str_replace(',','.',$itemdata['valuebrutto'] * $itemdata['count']);
			$itemdata['s_valuenetto'] 	= str_replace(',','.',$itemdata['valuenetto'] * $itemdata['count']);
//			$itemdata['s_valuenetto'] = str_replace(',', '.', $itemdata['s_valuebrutto'] / ($taxvalue / 100 + 1));
			$itemdata['valuenetto'] 	= str_replace(',','.',$itemdata['valuenetto']);
			$itemdata['valuebrutto'] 	= str_replace(',','.',$itemdata['valuebrutto']);
			$itemdata['count'] 			= str_replace(',','.',$itemdata['count']);
			$itemdata['discount'] 		= str_replace(',', '.', $itemdata['discount']);
			$itemdata['pdiscount'] 		= str_replace(',', '.', $itemdata['pdiscount']);
			$itemdata['vdiscount'] 		= str_replace(',', '.', $itemdata['vdiscount']);
			$itemdata['tax'] 			= $taxeslist[$itemdata['taxid']]['label'];
			$itemdata['posuid'] 		= (string) getmicrotime();
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
		
		$olddate = $invoice['oldcdate'];
		
		unset($invoice); 
		unset($customer);
		unset($error);
		
		if($invoice = $_POST['invoice'])
			foreach($invoice as $key => $val)
				$invoice[$key] = $val;
		
		$invoice['paytime'] = sprintf('%d', $invoice['paytime']);
		$invoice['oldcdate'] = $olddate;
		
		if($invoice['paytime'] < 0)
			$invoice['paytime'] = 14;

		if($invoice['cdate']) // && !$invoice['cdatewarning'])
		{
			list($year, $month, $day) = split('/',$invoice['cdate']);
			if(checkdate($month, $day, $year))
			{
				$invoice['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
			}
			else
				$error['cdate'] = trans('Incorrect date format!');
		}
		
		$invoice['customerid'] = $_POST['customerid'];
		
		if(!$error)
			if($LMS->CustomerExists(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customerid'])))
				$customer = $LMS->GetCustomer(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customerid']));
	break;

	case 'save':

		if($contents && $customer)
		{
			$SESSION->restore('invoiceid', $invoice['id']);
			$invoice['type'] = DOC_DELIV_INVOICE;
			$LMS->DelivererInvoiceUpdate(array('customer' => $customer, 'contents' => $contents, 'invoice' => $invoice));
			$SESSION->redirect('?m=nm_fv_kosztowa_info&id='.$invoice['id']);
		}
	break;

	case 'invoicedel':
	    $LMS->InvoiceDelete($_GET['id']);
	    $SESSION->redirect('?m=nm_fv_kosztowe_lista');
}

if($invoice['paytype'] == '')
	$invoice['paytype'] = trans('CASH');

$SESSION->save('invoice', $invoice);
$SESSION->save('invoicecontents', $contents);
$SESSION->save('invoicecustomer', $customer);
$SESSION->save('invoiceediterror', $error);

if($action != '')
{
	// redirect, ¿eby refreshem nie spierdoliæ faktury
	$SESSION->redirect('?m=nm_fv_kosztowa_edytuj');
}

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
        $SMARTY->assign('customers', $LMS->GetDostawcyNames());
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->display('nm_fv_kosztowa_edytuj.html');

?>
