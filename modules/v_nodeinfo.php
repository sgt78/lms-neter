<?php

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
 *  $Id: nodeinfo.php,v 1.55 2006/01/16 09:31:57 alec Exp $
 */

if(!eregi('^[0-9]+$',$_GET['id']))
{
	$SESSION->redirect('?m=v_nodelist');
}

if(!$voip->NodeExists($_GET['id']))
	if(isset($_GET['ownerid']))
	{
		$SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
	}
	else
	{
		$SESSION->redirect('?m=v_nodelist');
	}


$nodeid = $_GET['id'];
$ownerid = $voip->GetNodeOwner($nodeid);
$tariffs = $LMS->GetTariffs();
$customerinfo = $LMS->GetCustomer($ownerid);
$nodeinfo=$voip->GetNode($nodeid);
$nodeinfo['ownerid']=$ownerid;
$nodeinfo['id']=$nodeinfo['id_ast_sip'];
$nodeinfo['createdby'] = $LMS->GetUserName($nodeinfo['creatorid']);
if($nodeinfo['modifierid']) $nodeinfo['modifiedby'] = $LMS->GetUserName($nodeinfo['modifierid']);
$balancelist = $LMS->GetCustomerBalanceList($ownerid);
$assignments = $LMS->GetCustomerAssignments($ownerid);
$documents = $LMS->GetDocuments($ownerid, 10);
$customergroups = $LMS->CustomergroupGetForCustomer($ownerid);
$othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($ownerid);
$taxeslist = $LMS->GetTaxes();

$sub=$voip->get_id_subscriptions();
$tar=$voip->get_id_tariffs();
$nodeinfo['id_subscriptions']=$sub[$nodeinfo['id_subscriptions']];
$nodeinfo['id_tariffs']=$tar[$nodeinfo['id_tariffs']];
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto').'&ownerid='.$ownerid);

$layout['pagetitle'] = 'Informacje o koncie '.$nodeinfo['name'];

($voip->CustomerExists($ownerid) ? $v=true : $v=false);
$SMARTY->assign('isvoip',$v);
if($v)
{
	$customerinfo=$voip->GetCustomer($customerinfo,$ownerid);
	$v_balancelist = $voip->GetCustomerBalance($ownerid);
	//$customersip = $voip->GetCustomerNodes($ownerid);
	//$customersip['ownerid']=$ownerid;
	$SMARTY->assign('customersip',$customersip);
	$SMARTY->assign('v_balancelist',$v_balancelist);
}

$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('customerinfo',$customerinfo);
$SMARTY->assign('nodedata',$nodeinfo);
$SMARTY->assign('assignments',$assignments);
$SMARTY->assign('customergroups',$customergroups);
$SMARTY->assign('othercustomergroups',$othercustomergroups);
$SMARTY->assign('documents', $documents);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('tariffs',$tariffs);
$SMARTY->assign('customernodes',$LMS->GetCustomerNodes($ownerid));
$SMARTY->display('v_nodeinfo.html');

?>
