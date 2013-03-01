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
 *  $Id: paymentedit.php,v 1.18.2.2 2008/01/04 07:58:16 alec Exp $
 */

if(!$LMS->KontoKsiegoweExists($_GET['nkk_id']))
{
	$SESSION->redirect('?m=nm_konta_ksiegowe');
}

if(isset($_POST['kontoksiegowe']))
{
	$kontoksiegowe = $_POST['kontoksiegowe'];
	$kontoksiegowe['nkk_id'] = $_GET['nkk_id'];
	
	foreach($kontoksiegowe as $key => $value)
		$payment[$key] = trim($value);

	if($kontoksiegowe['nkk_nr'] == '')
		$error['nkk_nr'] = 'Numer konta musi być uzupełniony';

	if($kontoksiegowe['nkk_opis'] == '')
		$error['nkk_opis'] = 'Opis musi być ustawiony';
		
	if($kontoksiegowe['nkk_parent_id'] == '')
		$error['nkk_parent_id'] = 'Parent id pusty, dziwne';

#	if($LMS->GetpaymentIDByName($payment['name']) && $payment['name'] != $LMS->GetPaymentName($_GET['id']))
#		$error['name'] = trans('Specified name is in use!');	


	if(!$error)
	{
		$LMS->KontoKsiegoweUpdate($kontoksiegowe);
		$SESSION->redirect('?m=nm_konto_ksiegowe_info&nkk_id='.$kontoksiegowe['nkk_id']);
	}

} 
else 
{
	$kontoksiegowe = $LMS->GetKontoKsiegowe($_GET['nkk_id']);
}

	
$layout['pagetitle'] = 'Edycja Konta: '.$kontoksiegowe['nkk_nr'].' - '.$kontoksiegowe['nkk_opis'];

$SMARTY->assign('kontaglowne', $LMS->GetKontaKsiegoweIDNr());
$SMARTY->assign('kontoksiegowe', $kontoksiegowe);
$SMARTY->assign('error', $error);
$SMARTY->display('nm_konta_ksiegowe_edytuj.html');

?>
