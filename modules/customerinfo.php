<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 *  $Id$
 */

($voip->CustomerExists($_GET['id']) ? $v=true : $v=false);
$SMARTY->assign('isvoip',$v);

$customerid = intval($_GET['id']);


include(MODULES_DIR.'/infocenter.inc.php');
include(MODULES_DIR.'/customer.inc.php');

if($v)
{
        $customerinfo = $voip->GetCustomer($customerinfo,$_GET['id']);
	if($setl=$CONFIG['phpui']['voip_set_remb'])
	{
		if(date('Y-m-d', strtotime('+'.$setl.' month'))>=$customerinfo['voipkoniecum'])
			$SMARTY->assign('setl',$setl);
	}
	$customerinfo['voipbalance'] = $voip->GetCustomerVoipBalance($_GET['id']);
        $v_balancelist = $voip->GetCustomerBalance($_GET['id']);
        $customersip = $voip->GetCustomerNodes($_GET['id']);
	$customersip['ownerid']=$_GET['id'];
	$SMARTY->assign('customersip',$customersip);
	$SMARTY->assign('cdr',$voip->GetLastUserCdr($_GET['id']));
	$SMARTY->assign('v_balancelist',$v_balancelist);
	if($customerinfo['woj'] && $customerinfo['pow'] && $customerinfo['mia'])
	{
		$tmp=$voip->list_woj();
		$geoloc=$tmp[$customerinfo['woj']];
		$tmp=$voip->list_pow($customerinfo['woj']);
		$geoloc.=' - &gt; '.$tmp[$customerinfo['pow']];
		$tmp=$voip->list_mia($customerinfo['pow']);
		$geoloc.=' - &gt; '.$tmp[$customerinfo['mia']];
		$tmp=null;
	}
	else $geoloc='<B>BRAK !! KONIECZNIE UZUPEŁNIJ !!</B>';
	$SMARTY->assign('geoloc',$geoloc);
	$SMARTY->assign('id_tariffs',$voip->get_id_tariffs());
	$SMARTY->assign('id_subscriptions',$voip->get_id_subscriptions());
}

if($customerinfo['cutoffstop'] > mktime(0,0,0))
        $customerinfo['cutoffstopnum'] = floor(($customerinfo['cutoffstop'] - mktime(23,59,59))/86400);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Customer Info: $a',$customerinfo['customername']);



if($_GET['genpdf']==1){
 $linia=$voip->get_pdf1_data($_GET['idn'], $customerid);
 
$SMARTY->assign('d', $linia);
 $SMARTY->display('doc1.html');
exit;
}

if($_GET['genpdf']==2){
 $linia=$voip->get_pdf2_data($_GET['idn'], $customerid);
 
$SMARTY->assign('d', $linia);
$SMARTY->display('doc2.html');
exit;
}
if($_GET['genpdf']==3){
 $linia=$voip->get_pdf3_data($_GET['idn'], $customerid);
 
$SMARTY->assign('d', $linia);
$SMARTY->display('doc3.html');
exit;
}

if(isset($_POST['mov']) && !empty($_POST['mov']['numery'])&& !empty($_POST['mov']['nr_ewid'])){
$id=$customerid;

$numery= str_replace('\'', '', $_POST['mov']['numery']);
$nr_ewid= $_POST['mov']['nr_ewid'];
$oper= $_POST['mov']['operator'];

$odp=$voip->user_mov_add($id, $numery, $nr_ewid, $oper, 0, date('Y-m-d'));

if($odp) $_SESSION['kom']='<span style="color:red; font-size:10pt; font-weight:bold">Zgłoszenie zostało przyjęte</span>';
header('Location: ?m=customerinfo&id='.$customerid);
}



$docs=$voip->get_my_movs($customerid);


$operators=$voip->get_operators();
// $all=$DB->GetAll('select * from user_mov');
// var_dump($all);
$SMARTY->assign('ops', $operators);
$SMARTY->assign('docs', $docs);


$SMARTY->display('customerinfo.html');

?>
