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
 *  $Id: paymentlist.php,v 1.13.2.2 2008/01/04 07:58:16 alec Exp $
 */

$layout['pagetitle'] = trans('Payments List');

$paymentlist = $LMS->GetPaymentList();

foreach($paymentlist as $key => $value)
{
	$sum_count = $sum_count + $value['pay_count'];
	$sum_value = $sum_value + $value['pay_value'];
}

$listdata['total']     = $paymentlist['total'];
$listdata['sum_count'] = $sum_count;
$listdata['sum_value'] = $sum_value;

unset($paymentlist['total']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('paymentlist',$paymentlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->display('nm_koszty_stale.html');

?>
