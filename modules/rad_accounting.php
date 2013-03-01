<?php

/*
 * LMS version 1.11.13 Dira
 *
 *  (C) Copyright 2001-2011 LMS Developers
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
 *  $Id: nodelist.php,v 1.51 2011/01/18 08:12:24 alec Exp $
 */

$layout['pagetitle'] = 'Sesje radius';

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($_GET['from']))
	$SESSION->restore('nfrom', $from);
else
	$from = $_GET['from'];
$SESSION->save('nfrom', $from);

if(!isset($_GET['to']))
	$SESSION->restore('nto', $to);
else
	$to = $_GET['to'];
$SESSION->save('nto', $to);

if(!isset($_GET['name']))
	$SESSION->restore('nname', $name);
else
	$name = $_GET['name'];
$SESSION->save('nname', $name);

if(!isset($_GET['groupby']))
	$SESSION->restore('ngroupby', $groupby);
else
	$groupby = $_GET['groupby'];
$SESSION->save('ngroupby', $groupby);

if(!isset($_GET['ng']))
	$SESSION->restore('nlng', $ng);
else
	$ng = $_GET['ng'];
$SESSION->save('nlng', $ng);

$nodelist = $RADIUS->GetSessions($from,$to,$name,$groupby);

//($o, NULL, NULL, $n, $s, $g, $ng);
$listdata['total'] = sizeof($nodelist);
$listdata['order'] = $nodelist['order'];
$listdata['direction'] = $nodelist['direction'];
//$listdata['totalon'] = $nodelist['totalon'];
//$listdata['totaloff'] = $nodelist['totaloff'];
$listdata['from'] = $from;
$listdata['to'] = $to;
$listdata['name'] = $name;
$listdata['groupby'] = $groupby;

unset($nodelist['total']);
unset($nodelist['order']);
unset($nodelist['direction']);
unset($nodelist['totalon']);
unset($nodelist['totaloff']);

if ($SESSION->is_set('nlp') && !isset($_GET['page']))
	$SESSION->restore('nlp', $_GET['page']);
	
$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = (!isset($CONFIG['phpui']['nodelist_pagelimit']) ? $listdata['total'] : $CONFIG['phpui']['nodelist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('nlp', $page);

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('listdata',$listdata);

$SMARTY->display('rad_accounting.html');

?>
