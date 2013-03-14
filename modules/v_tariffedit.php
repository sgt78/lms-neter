<?php
//error_reporting(E_ALL);
//$SMARTY->debugging=true;
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
 *  $Id: tariffedit.php,v 1.36 2006/01/16 09:31:58 alec Exp $
 */

$tariff = $_POST['tariff'];

if(isset($tariff))
{
	foreach($tariff as $key => $value)
		if(!is_array($value)) $tariff[$key] = trim($value);

	$tariff['amount'] = str_replace(',','.',$tariff['amount']);
	

	if($tariff['name'] == '')
		$error['name'] = 'Nazwa jest wymagana';
	elseif($LMS->GetTariffIDByName($tariff['name']) && $tariff['name'] != $LMS->GetTariffName($_GET['id']))
		$error['name'] = 'Abonament o tej nazwie już istnieje';

	if($tariff['amount'] == '')
		$error['amount'] = trans('Value required!');
	elseif(!(ereg('^[-]?[0-9.,]+$', $tariff['amount'])))
		$error['amount'] = trans('Incorrect value!');
	
	
	if(!$tariff['tax'])
		$tariff['tax'] = 22;

	$tariff['id'] = $_GET['id'];

	if(!$error)
	{
		$voip->TariffUpdate($tariff);
		$SESSION->redirect('?m=v_tariffinfo&id='.$tariff['id']);
	}

}else
	$tariff = $voip->GetTariff($_GET['id']);
	
$layout['pagetitle'] = 'Edycja abonamentu '.$tariff['name'];
$SMARTY->assign('tariff',$tariff);
//$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('error',$error);
$SMARTY->display('v_tariffedit.html');
?>
