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
 *  $Id: customerbalanceok.php,v 1.11.2.2 2008/01/04 07:58:05 alec Exp $
 */

$docid = $_GET['id'];

$dokument = $DB->GetAll('select sum(value) as saldo, customerid 
               			 FROM cash 
                			WHERE docid = '. $docid.'
                     			GROUP BY customerid');


if($dokument[0]['saldo'] > 0)
{
    $dokument[0]['saldo'] = $dokument[0]['saldo']*-1;
    $dokument[0]['saldo'] = str_replace(',','.',$dokument[0]['saldo']);

	$DB->Execute('INSERT INTO cash (time, type, userid, value, customerid, docid, comment)
					   VALUES (?NOW?, 1, ?, ?, ?, ?, ?)',
					   array($AUTH->id,
					         $dokument[0]['saldo'],
					         $dokument[0]['customerid'],
					         $docid,
					         'Wpłata na rzecz faktury'
					        ));

	$DB->Execute('UPDATE documents set closed = 1 where id = '.$docid);
}

if($DB->errors)
    print_r($DB->errors);

header('Location: ?'.$SESSION->get('backto'));

?>
