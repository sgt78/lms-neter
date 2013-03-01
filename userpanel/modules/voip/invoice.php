<?php

/*
 *  LMS Userpanel version 1.0rc1-Kai
 *
 *  (C) Copyright 2004-2006 Userpanel Developers
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
 *  $Id: invoice.php,v 1.12.2.1 2006/01/16 09:49:58 lexx Exp $
 */

global $LMS,$SESSION,$CONFIG,$_CONFIG,$SMARTY,$invoice,$voip;
echo $voip->Invoice($_GET['id']);
exit;

$invoice = $LMS->GetInvoiceContent($_GET['id']);

if($invoice['customerid'] != $SESSION->id)
{
	die;
}

$ntempl = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
$layout['pagetitle'] = trans('Invoice No. $a', $ntempl);
$type = chkconfig($CONFIG['userpanel']['invoice_duplicate']) ? trans('DUPLICATE') : trans('ORIGINAL');
$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
$invoice['last'] = TRUE;

if(version_compare('1.6.99',$LMS->_version) == 1)
{
	include('invoice16.php');
	die;
}

if (strtolower($CONFIG['invoices']['type']) == 'pdf')
{
    include('invoice_pdf.php');
    die;
}

header('Content-Type: '.$CONFIG['invoices']['content_type']);
if($CONFIG['invoices']['attachment_name'] != '')
	header('Content-Disposition: attachment; filename='.$CONFIG['invoices']['attachment_name']);

$SMARTY->assign('css', file($CONFIG['directories']['sys_dir'].'/img/style_print.css')); 

// use LMS templates directory
$SMARTY->template_dir = !isset($CONFIG['directories']['smarty_tremplates_dir']) ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['direcories']['smarty_templates_dir'];

$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('type', $type);
$SMARTY->display('invoiceheader.html');
if($invoice['invoice'])
	$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
else
	$SMARTY->display($CONFIG['invoices']['template_file']);
$SMARTY->display('clearfooter.html');

?>
