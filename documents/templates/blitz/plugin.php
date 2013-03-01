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
 *  $Id: plugin.php,v 1.5.2.2 2008/01/04 07:57:37 alec Exp $
 */

global $SMARTY;
global $DB;


/* short example of errors handling

if(isset($_POST['document']))
{
	$error['notes'] = 'Error';
	$result = 'Error';
	return;
}

*/

// Notice: $customer consist selected customer ID


$document['new_tar_sum'] = $document['new_tar_value'] + $document['new_tar_upust_value'];
$document['new_akt_sum'] = $document['new_akt_value'] + $document['new_akt_upust_value'];


$result = $SMARTY->fetch(DOC_DIR.'/templates/'.$engine['name'].'/plugin.html');

?>
