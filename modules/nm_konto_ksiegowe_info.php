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

if(!$LMS->KontoKsiegoweExists($_GET['nkk_id']))
{
	$SESSION->redirect('?m=nm_konta_ksiegowe');
}

$kontoksiegowe = $LMS->GetKontoKsiegowe($_GET['nkk_id']);

# tu będziemy pobierać jakieś ciekawe informacje o kosztach na tym koncie

$layout['pagetitle'] = 'Informacje o Koncie Księgowym: ('.$kontoksiegowe['nkk_nr'].') '.$kontoksiegowe['nkk_opis'];

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('kontoksiegowe',$kontoksiegowe);
$SMARTY->display('nm_konto_ksiegowe_info.html');

?>
