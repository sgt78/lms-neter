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
 *  $Id: paymentinfo.php,v 1.15.2.2 2008/01/04 07:58:16 alec Exp $
 */

function GetPaymentsByCustomer($customerid,$pay_at,$pay_period)
{
    global $DB, $SESSION;

    $customer = $DB->GetAll("SELECT customers.id as cus_id,
			 					    customers.name as cus_name,
								    customers.lastname as cus_lastname,
								    customers.ten as cus_nip
                               FROM customers
                              WHERE customers.id = ?",array($customerid));

    $payments = $DB->GetAll("SELECT pay_id,
                                    pay_name,
                                    pay_value,
                                    pay_description,
                                    taxes.id as tax_id,
								    taxes.value as tax_value,
                                    taxes.label as tax_label,
                                    nkk_nr,
                                    nkk_opis,
                                    documents.cdate as dok_cdate
							  FROM payments 
				    		  LEFT JOIN taxes on taxes.id = pay_tax_id
                              LEFT JOIN nm_konta_ksiegowe on nkk_id = pay_nkk_id
                              LEFT JOIN documents on documents.id = pay_last_dok_id
							 WHERE pay_cus_id = ? AND pay_at = ? AND pay_period = ?
							 ORDER BY pay_value ASC",array($customerid,$pay_at,$pay_period));
							 
	$saldo = $DB->GetOne("SELECT sum(value)
							FROM cash
						   WHERE customerid = ?",array($customerid));

    if(!$customer || !$payments)
        $SESSION->redirect('?m=nm_koszty_stale');

    foreach($customer as $key => $node)
        $customer = $node;

    foreach($payments as $key => $node)
    {
        $totalnetto = $totalnetto + $node['pay_value'];
        $totalvat   = $totalvat   + $node['pay_value'] * $node['tax_value']/100;
	}

    $retval['customer']    = $customer;
    $retval['payments']    = $payments;
    $retval['saldo']       = $saldo;
    $retval['totalnetto']  = $totalnetto;
    $retval['totalvat']    = $totalvat;
    $retval['totalbrutto'] = $totalnetto + $totalvat;

    return $retval;

}

if(isset($_POST['action']))
{
	switch($_POST['action'])
	{
		case 'paymentadd':
		// 1. Tworzymy fakturę
		$forwho = $_POST['paymentadd'];
		
		$payments = GetPaymentsByCustomer($forwho['customerid'],$forwho['at'],$forwho['period']);
		
		$invoice['invoice']['number'] 			= 'autonaliczenie';
		$invoice['invoice']['type']   			= DOC_DELIV_INVOICE;
		$invoice['invoice']['cdate']  			= time();
		$invoice['invoice']['paytime']			= 14;
		$invoice['invoice']['paytype'] 			=  2;

		$invoice['customer']['id']				= $payments['customer']['cus_id'];
		$invoice['customer']['customername']	= $payments['customer']['cus_lastname'].' '.$payments['customer']['cus_name'];
		$invoice['customer']['address']			= '';
		$invoice['customer']['ten'] 			= $payments['customer']['cus_nip'];
		$invoice['customer']['ssn'] 			= '';
		$invoice['customer']['zip'] 			= '';
		$invoice['customer']['city']			= '';
		
		// 2. Tworzymy pozycje
		foreach($payments['payments'] as $key => $row)
		{
			$content[$key]['valuebrutto'] = f_round($row['pay_value'] + $row['pay_value'] * $row['tax_value'] /100,2);
			$content[$key]['taxid']       = $row['tax_id'];
			$content[$key]['jm']          = 'szt.';
			$content[$key]['count']       = 1;
			$content[$key]['name']        = $row['pay_name'];
			$content[$key]['tariffid']    = 0;

		}
		$invoice['contents'] = $content;
		
		// 3. Dodajemy to wszystko
		$iid = $LMS->AddDelivererInvoice($invoice);

		// 4. Aktualizujemy dok_id w payments
		foreach($payments['payments'] as $key => $row)
		{
			$DB->Execute("UPDATE payments set pay_last_dok_id = ? where pay_id = ?",array($iid,$row['pay_id']));
		}
//		$SESSION->redirect('?m=nm_koszty_stale');
		break;
	}
}



$payments_id['customer'] = $_GET['cus_id'];
$payments_id['at']       = $_GET['pay_at'];
$payments_id['period']   = $_GET['pay_period'];


$listdata = GetPaymentsByCustomer($_GET['cus_id'],$_GET['pay_at'],$_GET['pay_period']);

$layout['pagetitle'] = 'Informacje o płatnoścach stałych';

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('payments_id',$payments_id);
$SMARTY->assign('listdata',$listdata);

$SMARTY->display('nm_koszty_stale_info.html');

?>
