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

$kontoksiegowe = isset($_POST['kontoksiegowe']) ? $_POST['kontoksiegowe'] : NULL;

if($kontoksiegowe)
{
	foreach($kontoksiegowe as $key => $value)
		$kontoksiegowe[$key] = trim($value);

	if($kontoksiegowe['nkk_nr']=='' && $kontoksiegowe['nkk_parent_id']=='' && $kontoksiegowe['nkk_opis']=='')
	{
		$SESSION->redirect('?m=nm_konta_ksiegowe');
	}
	
	if ($kontoksiegowe['nkk_nr']=='')
		$error['nnk_nr'] = "Numer konta nie może być pusty";
		
	if ($kontoksiegowe['nkk_parent_id']=='')
		$error['nkk_parent_id'] = "Numer nadrzędnego konta nie może być pusty";
		
	if ($kontoksiegowe['nkk_opis']=='')
		$error['nkk_opis'] = "Ustaw jakiś opis tego konta";
	
	if(!$error)
	{
		if(isset($kontoksiegowe['reuse']))
		{
			$LMS->KontoKsiegoweAdd($kontoksiegowe);
		} else {
			$LMS->KontoKsiegoweAdd($kontoksiegowe);
			$SESSION->redirect('?m=nm_konta_ksiegowe');
		}
			
		unset($kontoksiegowe);
		$kontoksiegowe['reuse'] = '1';
	}
}

print_r($kontoksiegowe);

$layout['pagetitle'] = 'Nowe Konto Księgowe';

$kontaglowne = $LMS->GetKontaKsiegoweIDNr();

$SMARTY->assign('kontaglowne',$kontaglowne);


$SMARTY->assign('error', $error);
$SMARTY->assign('kontoksiegowe', $kontoksiegowe);
$SMARTY->display('nm_konta_ksiegowe_dodaj.html');

?>
