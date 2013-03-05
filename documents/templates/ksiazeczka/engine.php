<?php

/*
 * LMS version 1.11.4 Telchak
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
 *  $Id: engine.php,v 1.9 2008/01/04 07:53:24 alec Exp $
 */

$cid = $document['customerid'];
$noticecost = 0;		//Koszt wezwania - doliczany jeœli zmienna $noticecost jest ró¿na od 0 
if($noticecost != 0)
    {
	$DB->Execute('INSERT INTO cash (time, type, value, customerid, comment) VALUES (?NOW?, 0, ?, ?, "Koszt wezwania")', array(-$noticecost, $cid));
    }
$customerinfo = $LMS->GetCustomer($cid);
$assignments = $LMS->GetCustomerAssignments($cid);
$customernodes = $LMS->GetCustomerNodes($cid);
$tariffs = $LMS->GetTariffs();
$customerdocuments = $DB->GetAll('SELECT  number, cdate, paytime, value, description FROM documents, invoicecontents WHERE customerid = ? AND type=1 AND closed=0 AND id=docid', array($cid)); // Faktury nierozliczone

unset($customernodes['total']);

if($customernodes)
	foreach($customernodes as $idx => $row)
	{
		$customernodes[$idx]['net'] = $DB->GetRow('SELECT *, inet_ntoa(address) AS ip FROM networks WHERE address = (inet_aton(mask) & ?)', array($row['ipaddr']));
	}

if($customeraccounts = $DB->GetAll('SELECT passwd.*, domains.name AS domain
				FROM passwd LEFT JOIN domains ON (domainid = domains.id)
				WHERE passwd.ownerid = ? ORDER BY login', array($cid)))
	foreach($customeraccounts as $idx => $account)
	{
		$customeraccounts[$idx]['aliases'] = $DB->GetCol('SELECT login FROM aliases WHERE accountid=?', array($account['id']));
		/*// create random password
		$pass = '';
		for ($i = 0; $i < 8; $i++)
		       $pass .= substr('0123456789abcdefghijklmnopqrstuvwxyz', rand(0,36), 1);
		$customeraccounts[$idx]['password'] = $pass;
		*/
	}

$document['template'] = $DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($document['numberplanid']));
$document['nr'] = docnumber($document['number'], $document['template']);

$SMARTY->assign(
		array(
			'customernodes' => $customernodes,
			'assignments' => $assignments,
			'customerinfo' => $customerinfo,
			'tariffs' => $tariffs,
			'customerdocuments' => $customerdocuments,
			'customeraccounts' => $customeraccounts,
			'document' => $document,
			'engine' => $engine,
			'noticecost' => $noticecost,
		     )
		);

$output = $SMARTY->fetch(DOC_DIR.'/templates/'.$engine['name'].'/'.$engine['template']);

?>
