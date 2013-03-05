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
 *  $Id: info.php,v 1.8 2008/01/04 07:53:24 alec Exp $
 */

$engine = array(
	'name' => 'informacja-2012', 	// template directory
	'engine' => 'informacja-2012', 	// engine.php directory
				// you can use other engine
	'template' => 'template.html', 		// template file (in 'name' dir)
	'title' => 'informacja-2012', // description for UI
	'content_type' => 'text/html', 		// output file type
	'output' => 'informacja.html', 		// output file name
	'plugin' => 'plugin',			// form plugin (in 'name' dir)
	'post-action' => 'post-action',		// action file executed after document addition (in transaction)
)

?>
