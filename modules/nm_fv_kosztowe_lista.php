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
 *  $Id: invoicelist.php,v 1.42.2.4 2008/01/04 07:58:10 alec Exp $
 */

function GetInvoicesList($search=NULL, $cat=NULL, $closed=NULL, $order,$date_from,$date_to)
{
	global $DB;
	
	if($order=='')
		$order='id,asc';
	
	list($order,$direction) = sscanf($order, '%[^,],%s');
	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
	
	switch($order)
	{
		case 'id':
			$sqlord = ' ORDER BY d.id';
		break;
		case 'cdate':
			$sqlord = ' ORDER BY d.cdate';
		break;
		case 'pdate':
			$sqlord = ' ORDER BY pdate';
		break;
		case 'number':
			$sqlord = ' ORDER BY number';
		break;
		case 'value':
			$sqlord = ' ORDER BY value';
		break;
		case 'count':
			$sqlord = ' ORDER BY count';
		break;
		case 'name':
			$sqlord = ' ORDER BY name';
		break;
	}
	
	$where = ' AND cdate >= '.$date_from.' AND cdate <= '.$date_to;
	
	if($search!='' && $cat)
        {
	        switch($cat)
		{
			case 'extnumber':
				$where = ' AND extnumber = '.intval($search);
			break;
			case 'cdate':
				$where = ' AND cdate >= '.intval($search).' AND cdate < '.(intval($search)+86400);
			break;
			case 'month':
				$last = mktime(23,59,59, date('n', $search) + 1, 0, date('Y', $search));
				$where = ' AND cdate >= '.intval($search).' AND cdate <= '.$last;
			break;
			case 'ten':
			        $where = ' AND ten = \''.$DB->Escape($search).'\'';
			break;
			case 'customerid':
				$where = ' AND customerid = '.intval($search);
			break;
			case 'name':
				$where = ' AND UPPER(name) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').')';
			break;
			case 'address':
				$where = ' AND UPPER(address) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').')';
			break;
			case 'all':
				$where = ' ';
			break;
		}
	}
        
	if($closed=='on')
		$where .= ' AND closed = 0';

	if($result = $DB->GetAll('SELECT d.id AS id, extnumber as number, cdate, cdate+84000*paytime as pdate, d.type as type,
			d.customerid as customerid, name, address, zip, city, closed, 
			CASE reference WHEN 0 THEN
			    SUM(a.value*a.count) 
			ELSE
			    SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count)
			END AS value, 
			COUNT(a.docid) AS count,
			(SELECT SUM(cash.value) from cash where cash.docid = d.id) AS saldo
	    		FROM documents d
			LEFT JOIN invoicecontents a ON (a.docid = d.id)
			LEFT JOIN invoicecontents b ON (d.reference = b.docid AND a.itemid = b.itemid)
			
			WHERE (d.type = '.DOC_DELIV_CNOTE.(($cat != 'cnotes') ? ' OR d.type = '.DOC_DELIV_INVOICE : '').')'
			.$where
			.' AND NOT EXISTS (
			        SELECT 1 FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)'
			.' GROUP BY d.id, number, cdate, customerid, 
			name, address, zip, city, closed, type, reference '
	    		.$sqlord.' '.$direction))
	{
		
		// pobranie danych do salda faktury z KW (z pozycji)
		foreach($result as $idx => $row)
		{
			if($row['closed'] == 1 && $row['saldo'] == $row['value'])
			{
				$saldo_receipt = $DB->GetOne('SELECT SUM(value) from receiptcontents WHERE reference=?',array($row['id']));
				$result[$idx]['saldo'] = $row['saldo']+$saldo_receipt;
			}
		}
		
		$result1 = array();
		if($search!='' && $cat=='value')
		{
			$val = f_round($search);

			foreach($result as $idx => $row)
			{
				if($row['value']==$val)
					$result1[] = $result[$idx];
			}

			$result = $result1;
			unset($result1);
		}

	}

	$result['order'] = $order;
	$result['direction'] = $direction;

	return $result;
}

$layout['pagetitle'] = 'Lista faktur kosztowych';
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SESSION->restore('ilm', $marks);
if(isset($_POST['marks']))
	foreach($_POST['marks'] as $id => $mark)
		$marks[$id] = $mark;
$SESSION->save('ilm', $marks);

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('ils', $s);

$SESSION->restore('year',$year);
$SESSION->restore('month',$month);
//Neter sgt
if(!isset($s))
	{
	$year=date("Y", time());
	$month=date("m", time());
	$s = $year.'/'.$month;
	}
//Neter end
$SESSION->save('ils', $s);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('ilo', $o);
$SESSION->save('ilo', $o);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('ilc', $c);
//Neter sgt
if(!isset($c))
	$c = 'month';
//Neter end
$SESSION->save('ilc', $c);

$SESSION->restore('exclosed',$exclosed);
if(isset($_POST['excludeclosed'])) {
	$exclosed = $_POST['excludeclosed'];
}
$SESSION->save('exclosed',$exclosed);

$listdata['excludeclosed'] = $exclosed;

if($c == 'cdate' && $s && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $s))
{
	list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, $month, $day, $year);
}
elseif($c == 'month' && $s && preg_match('/^[0-9]{4}\/[0-9]{2}$/', $s))
{
	list($year, $month) = explode('/', $s);
        $s = mktime(0,0,0, $month, 1, $year);
}
elseif($c == 'year' && $s && preg_match('/^[0-9]{4}$/', $s))
{
	$year=$s;
        $s = mktime(0,0,0, 1, 1, $year);
		$month = 0;
}

if(!isset($month))
	$month = date('n',time());

$SESSION->save('year',$year);
$SESSION->save('month',$month);

if($month==0)
{
	$date_from = mktime(0,0,0,1,1,$year);
	$date_to   = mktime(0,0,0,12,31,$year);
} else {
	$date_from = mktime(0,0,0,$month,1,$year);
	$date_to   = mktime(0,0,0,$month+1,0,$year);
}

$invoicelist = GetInvoicesList($s, $c, $exclosed, $o,$date_from,$date_to);

$SESSION->restore('ilc', $listdata['cat']);
$SESSION->restore('ils', $listdata['search']);
$SESSION->restore('ilg', $listdata['group']);
$SESSION->restore('ilge', $listdata['groupexclude']);
$listdata['order'] = $invoicelist['order'];
$listdata['direction'] = $invoicelist['direction'];
$page = $invoicelist['page'];

unset($invoicelist['page']);
unset($invoicelist['order']);
unset($invoicelist['direction']);


foreach($invoicelist as $key => $value)
{
	$sum_value = $sum_value + $value['value'];
	$sum_saldo = $sum_saldo + $value['saldo'];
}

$listdata['total'] = sizeof($invoicelist);
$listdata['sumvalue'] = $sum_value;
$listdata['sumsaldo'] = $sum_saldo;

$pagelimit = $CONFIG['phpui']['invoicelist_pagelimit'];
$page = !isset($_GET['page']) ? ceil($listdata['total']/$pagelimit) : intval($_GET['page']);
$start = ($page - 1) * $pagelimit;

$SMARTY->assign('nowtime',time());
$SMARTY->assign('yearlist',$yearlist);
$SMARTY->assign('year',$year);
$SMARTY->assign('month',$month);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',($page - 1) * $pagelimit);
$SMARTY->assign('page',$page);
$SMARTY->assign('marks',$marks);
$SMARTY->assign('invoicelist',$invoicelist);
$SMARTY->display('nm_fv_kosztowe_lista.html');

?>
