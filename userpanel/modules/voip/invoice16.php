<?php

/*
 *  LMS Userpanel version 1.0rc1-Kai
 *
 *  (C) Copyright 2004-2006 Userpanel Developers
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
 *  $Id: invoice16.php,v 1.3.2.1 2006/01/16 09:49:58 lexx Exp $
 */
		      
if(strtolower($CONFIG['invoices']['type']) == 'pdf')
{
    include('invoice_pdf16.php');
    die;
}

header('Content-Type: '.$CONFIG['invoices']['content_type']);
if($CONFIG['invoices']['attachment_name'] != '')
	header('Content-Disposition: attachment; filename='.$CONFIG['invoices']['attachment_name']);

// use LMS templates directory
$SMARTY->template_dir = !isset($CONFIG['directories']['smarty_tremplates_dir']) ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['direcories']['smarty_templates_dir'];

$SMARTY->assign('invoice',$invoice);
$SMARTY->assign('type', $type);
$SMARTY->display('clearheader.html');
$SMARTY->display($CONFIG['invoices']['template_file']);
$SMARTY->display('clearfooter.html');

?>
