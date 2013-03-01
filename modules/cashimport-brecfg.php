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
 *  $Id: cashimportcfg.php,v 1.8.2.2 2008/01/04 07:58:02 alec Exp $
 */

#$pattern = "/^([^ ]+)\t([^ ]+)[\s\t]+([^ ]+)\t([^ ]+)\t(.*)/";
#$pattern = "/([^\|]+)\|([^\|]+)\|([^\|]+)\|([^\|]+)\|([^\|]+)\|([^;]+);.*?ID IPH: XX([^;]+);([^;]+);([^;]+);([^;]+);.*?TNR:([^|]+)/";
/* Neter sgt
 
  $pattern = "/([^\|]+)\|([^\|]+)\|([^\|]+)\|([^\|]+)\|([^\|]+)\|([^;]+);.*?ID IPH: XX([^;]+);([^;]+);.*?od: ([^;]+);(.*?tyt.: ([^;]+);|).*?TNR:([^|]+)/";
$pid 				= 7;		// customer ID position in expression
								// if zero - we try to search ID by regexp,
								// invoice number or customer name and forename in entire row
$pname 				= 2;		// name position 
$plastname 			= 9; 		// forename position 

$pdate 				= 2;		// date position
$pvalue 			= 4;		// value position
$pcomment 			= 11;		// operation comment position
$ptnr				= 12;		// identyfikator operacji

$date_regexp 		= '/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/'; // date format (dd.mm.yyyy)
$pday 				= 1;
$pmonth 			= 2;
$pyear 				= 3;*/
//$invoice_regexp 	= '/.* (\d*)\/NETER\/([0-9]{4}).*/'; 	// format of invoice number
/*								// default %N/LMS/%Y
$pinvoice_number 	= 1; 		// position of invoice number in $invoice_regexp
$pinvoice_year   	= 2;   		// year position in $invoice_regexp

$encoding 			= 'ISO-8859-2';  // imported data encoding (for conversion)

$modvalue 			= FALSE;	// if operation's value is 1000.00, then
								// enabled $modvalue will modify it to 10.00
Neter end*/
$patterns[] = array(
    'id' => 2,	// import source identifier (from 'cashsources' table)
    'pattern' => "/([^\|]+)\|([^\|]+)\|([^\|]+)\|([^\|]+)\|([^\|]+)\|([^;]+);.*?ID IPH: XX([^;]+);([^;]+);.*?od: ([^;]+);(.*?tyt.: ([^;]+);|).*?TNR:([^|]+)/",
    'pid' => 7,		// customer ID position in expression
			// if zero - we try to search ID by regexp,
			// invoice number or customer name and forename in entire line
    'pname' => 2,	// name position 
    'plastname' => 9,	// forename position 
    'pvalue' => 4,	// value position
    'pcomment' => 11,	// operation comment position
    'pdate' => 2,  	// date position

    'date_regexp' => '/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/', // date format (dd.mm.yyyy)
    'pday' => 1,
    'pmonth' => 2,
    'pyear' => 3,

    //'pid_regexp' => '/.*ID[:\-\/]([0-9]{0,4}).*/i', 	// if 'pid' is not specified
    							// try to find it by regexp

    'invoice_regexp' => '/.* (\d*)\/NETER\/([0-9]{4}).*/',// format of invoice number
							// default %N/LMS/%Y
    'pinvoice_number' => 1,	// position of invoice number in $invoice_regexp
    'pinvoice_year' => 2,	// year position in $invoice_regexp
    'pinvoice_month' => 0,	// month position in $invoice_regexp

    'encoding' => 'ISO-8859-2',	// imported data encoding (for conversion)

    'modvalue' => 0,		// if not zero do value = value * modvalue
    'use_line_hash' => FALSE,	// create md5 hash for whole import line instead of
				// time, value, customer name and comment
    'line_idx_hash' => FALSE,	// include line number into hash data
    'ptnr_hash' => TRUE,	// Neter sgt
    'ptnr_date_hash' => FALSE,	// Neter sgt
	'ptnr' => 12,		// identyfikator operacji
);

?>
