<?php

/*
 * LMS version 1.8.12 Tagan
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
 *  $Id: engine.php,v 1.2.2.2 2006/01/16 09:35:23 alec Exp $
 */

$cid = $document['customerid'];

$customerinfo = $LMS->GetCustomer($cid);
//$assignments = $LMS->GetCustomerAssignments($cid);
//$customernodes = $LMS->GetCustomerNodes($cid);
$balancelist = $LMS->GetCustomerInvoiceList($cid);
//$tariffs = $LMS->GetTariffs();

foreach($balancelist as $key => $row)
{
	$grandtotal['value']   += $row['pozostalo'];
	$grandtotal['odsetki'] += $row['odsetki'];
}
$grandtotal['all'] = round($grandtotal['value'] + $grandtotal['odsetki'],2);

/*

print('<pre>');
print_r($grandtotal);
print_r($balancelist);
print('</pre>');
die();


*/

$document['template'] = $DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($document['numberplanid']));
$document['nr']       = docnumber($document['number'], $document['template']);

$SMARTY->assign(
		array(
			'customerinfo' => $customerinfo,
			'document'     => $document,
			'engine'       => $engine,
			'balancelist'  => $balancelist,
			'grandtotal'   => $grandtotal
		     )
		);

$output = $SMARTY->fetch($_DOC_DIR.'/templates/'.$engine['name'].'/'.$engine['template']);

?>
