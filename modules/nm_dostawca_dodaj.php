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
 *  $Id: paymentadd.php,v 1.16.2.2 2008/01/04 07:58:15 alec Exp $
 */

$dostawca = isset($_POST['dostawca']) ? $_POST['dostawca'] : NULL;

if($dostawca)
{
	foreach($dostawca as $key => $value)
		$dostawca[$key] = trim($value);

	
	if ($dostawca['ndo_customer_id']=='')
		$error['ndo_customer_id'] = "Numer powiązanego klienta nie może być pusty";
		
	if ($dostawca['ndo_konto']=='')
		$error['ndo_konto'] = "Numer konta syntetycznego nie może byc pusty";
		
	if ($dostawca['ndo_domyslne_kkosztowe']=='')
		$error['ndo_domyslne_kkosztowe'] = "Numer konta analitycznego nie może być pusty";
		
	if ($dostawca['ndo_bank']=='')
		$error['ndo_bank'] = "Wpisz nazwę banku";
		
	if ($dostawca['ndo_nr_konta']=='')
		$error['ndo_nr_konta'] = "Wpisz numer konta kontrahenta";
	
	if(!$error)
	{
		$LMS->DostawcaAdd($dostawca);
		$SESSION->redirect('?m=nm_dostawcy_lista');
			
		unset($kontoksiegowe);
	}
}

print_r($dostawca);

$layout['pagetitle'] = 'Nowy Dostawca';

$kontaglowne = $LMS->GetKontaKsiegoweIDNr();

$SMARTY->assign('kontaglowne',$kontaglowne);

$SMARTY->assign('dostawcy', $LMS->GetDostawcyNames());

$SMARTY->assign('error', $error);
$SMARTY->assign('kontoksiegowe', $kontoksiegowe);
$SMARTY->display('nm_dostawca_dodaj.html');

?>
