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
 *  $Id: LMS.class.php,v 1.868.2.17 2008/03/16 07:51:05 alec Exp $
 */

// LMS Class - contains internal LMS database functions used
// to fetch data like customer names, searching for mac's by ID, etc..

class RADIUS
{
	var $DBRADIUS;			// database object
	var $DB;
	var $AUTH;			// object from Session.class.php (session management)
	var $CONFIG;			// table including lms.ini options
	var $_version = '1.10.4 Pyrus';	// class version
	var $_revision = '$Revision: 1.868.2.17 $';

	function RADIUS(&$DB, &$DBRADIUS, &$AUTH, &$CONFIG) // class variables setting
	{
		$this->DB = &$DBRADIUS;
		$this->DBLMS = &$DB;
		$this->AUTH = &$AUTH;
		$this->CONFIG = &$CONFIG;

		$this->_revision = eregi_replace('^.Revision: ([0-9.]+).*','\1', $this->_revision);
		$this->_version = $this->_version.' ('.$this->_revision.')';
		
	}

	function _postinit()
	{
		return TRUE;
	}

	/*
	 *  Logging
	 *	0 - disabled
	 *	1 - system log in and modules calls without access privileges
	 *	2 - as above, addition and deletion
	 *	3 - as above, and changes
	 *	4 - as above, and all modules calls (paranoid)
	 */
/*
	function Log($loglevel=0, $message=NULL)
	{
		if( $loglevel <= $this->CONFIG['phpui']['loglevel'] && $message )
		{
			$this->DB->Execute('INSERT INTO syslog (time, userid, level, message)
					    VALUES (?NOW?, ?, ?, ?)', array($this->AUTH->id, $loglevel, $message));
		}
	}
*/
	/*
	 *  Customers
	 */

	function GetLastStatus($login)
	{
		$radius_session = $this->DB->GetAll("SELECT unix_timestamp(AcctStartTime) as AcctStartTime,
													unix_timestamp(AcctStopTime) as AcctStopTime
											   FROM radacct
											  WHERE UserName = ?
											  ORDER BY AcctStartTime DESC
											  LIMIT 1",array($login));
		if($radius_session)
			foreach($radius_session as $key => $session)
			{
				$retval['AcctStartTime'] = $session['AcctStartTime'];
				$retval['AcctStopTime']  = $session['AcctStopTime'];
			}
		
		
		return($retval);
	}
	
	function GetSessions($from=0,$to=0,$login=0,$groupby=0)
	{
		
		$tlogin = $login ? " AND UserName = '$login'" : '';
		$tfrom  = $from  ? $from  : 1;
		$tto    = $to>$from ? 0 : $to;

		if($to>0)
		{
			$wFromTime = "(AcctStopTime > DATE_SUB( NOW() , INTERVAL $tfrom DAY ))";
			$wToTime   = " AND (AcctStopTime < DATE_SUB( NOW() , INTERVAL $tto DAY ))";
		} else {
			$wFromTime = "(AcctStopTime > DATE_SUB( NOW() , INTERVAL $tfrom DAY ) OR AcctStopTime is NULL)";
			$wToTime   = "";
		}
		
		if($groupby <> '')
		{
			$OrderGroup = "GROUP BY $groupby";
			$ssum1      = "sum(";
			$ssum2	    = ")";
		} else {
			$OrderGroup = "ORDER BY AcctStartTime";
			$ssum1      = "";
			$ssum2	    = "";
		}

				
		$sessions = $this->DB->GetAll("SELECT AcctUniqueid as sessid,
									          AcctStartTime,
									          AcctStopTime,
											  $ssum1(case AcctStopTime when NULL then 
												(timestampdiff(SECOND,AcctStartTime,now()))
											   else
												(timestampdiff(SECOND,AcctStartTime,AcctStopTime))
											   end)$ssum2 as uptime,
									          UserName as name,
									          $ssum1 AcctInputOctets $ssum2 as upload,
									          $ssum1 AcctOutputOctets $ssum2 as download,
									          CallingStationId as mac,
									          FramedIPAddress as ip,
									          NASIPAddress as nasip
									     FROM radacct
									    WHERE $wFromTime
											  $wToTime
										      $tlogin
										$OrderGroup");
		foreach($sessions as $key => $node)
		{
			$sessions[$key]['uptime']   = $this->intToUptime($node['uptime']);
			$sessions[$key]['upload']   = $this->intToGB($node['upload']);
			$sessions[$key]['download'] = $this->intToGB($node['download']);
		}
		
		return $sessions;
	}
									     

	function GetLastSession($login)
	{
		$status = $this->DB->GetRow("SELECT AcctUniqueid as sessid, 
											UserName as username,
											NASIPAddress as nasip,
											/*(case AcctStopTime when 0 then AcctStartTime else AcctStopTime end) as fromdate,
											(case AcctStopTime when 0 then 'online' else 'offline' end) as state,
											(case AcctStopTime when 0 then 
												sec_to_time(timestampdiff(SECOND,AcctStartTime,now()))
											else
												sec_to_time(timestampdiff(SECOND,AcctStopTime,now()))
											end) as uptime,*/
											if(AcctStopTime, AcctStopTime, AcctStartTime) as fromdate,
											if(AcctStopTime, 'offline', 'online') as state,
											if(AcctStopTime, 
											    sec_to_time(timestampdiff(SECOND,AcctStopTime,now())), 
											    sec_to_time(timestampdiff(SECOND,AcctStartTime,now()))
											    ) as uptime,
											sec_to_time(AcctSessionTime) as sessiontime,
											AcctInputOctets as upload,
											AcctOutputOctets as download,
											CallingStationId as mac,
											FramedIPAddress as ip 
									   FROM radacct
									  WHERE UserName = ?
									  ORDER BY AcctStartTime DESC
									  LIMIT 1",array($login));

		$status['upload'] = $this->intToGB($status['upload']);
		$status['download'] = $this->intToGB($status['download']);

		return $status;

	}

	function IntToGB($octets)
	{
		$retval = round($octets/1024,2);
		if( $retval < 1024 ) return( $retval.' kB' );
		$retval = round($octets/1024/1024,2);
		if( $retval < 1024 ) return( $retval.' MB' );
		$retval = round($octets/1024/1024/1024,2);
		if( $retval < 1024 )	return( $retval.' GB');
		$retval = round($octets/1024/1024/1024/1024,2);
			return( $retval.' TB');
	}
	
	function IntToUptime($stamp)
	{
		$default_timezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$retval = date("z\d H:i:s", $stamp);
		date_default_timezone_set($default_timezone);

		return $retval;
	}

	function GetOnlineList($nasip = 0)
	{
		$query = "SELECT UserName,
						 AcctStartTime,
						 unix_timestamp(now()) - unix_timestamp(AcctStartTime) as uptime,
						 FramedIPAddress,
						 NASIPAddress,
						 CallingStationId,
						 AcctInputOctets,
						 AcctOutputOctets 
					FROM radacct 
				   WHERE AcctStopTime is NULL";
				   
		$retval = $this->DB->GetAll($query);
		
		foreach($retval as $key => $node)
		{
			$retval[$key]['uptime']           = $this->IntToUptime($node['uptime']);
			$retval[$key]['AcctInputOctets']  = $this->intToGB($node['AcctInputOctets']);
			$retval[$key]['AcctOutputOctets'] = $this->intToGB($node['AcctOutputOctets']);
		}
		
		return $retval;
		
	}
	
	function GetRadiusStatistics()
	{
		$wherearray = array('','','');
		$query = "select count(case reply when 'Access-Reject' then 1 end) as reject, 
						 count(case reply when 'Access-Accept' then 1 end) as accept 
				    FROM radpostauth";
	}

	function GetFailedLoginsList($from = 0)
	{
		$tfrom = ($from == 0) ? 1 : $from;
		
		$query = "SELECT count( * ) as count , user, date, station
					FROM radpostauth
				   WHERE reply = 'Access-Reject'
					 AND (date > now( ) - INTERVAL $tfrom HOUR)
				   GROUP BY user";

		$list = $this->DB->GetAll($query);
		
		return $list;
	}

	function GetFlappingLoginsList($from = 0)
	{
		$tfrom = ($from == 0) ? 1 : $from;
		$maxattemps = $tfrom * 2;
		
		$query = "SELECT count( * ) as count,
						 sum(AcctSessionTime) as session,
						 sum(AcctInputOctets) as upload,
						 sum(AcctOutputOctets) as download,
						 CallingStationId as mac,
						 AcctTerminateCause as cause,
						 FramedIPAddress as ip,
						 UserName as name,
						 NASIPAddress as nasip
					FROM radacct
				   WHERE (AcctStartTime > now( ) - INTERVAL ? HOUR)
				   GROUP BY CallingStationId,AcctTerminateCause,FramedIPAddress,UserName,NASIPAddress
				   HAVING count(*) > ?";

		$list = $this->DB->GetAll($query,array($tfrom,$maxattemps));
		
		foreach($list as $key => $node)
		{
			$list[$key]['session']  = $this->IntToUptime($node['session']);
			$list[$key]['upload']   = $this->IntToGB($node['upload']);
			$list[$key]['download'] = $this->IntToGB($node['download']);
		}
		
		return $list;
	}



	/*
	 *  Users (Useristrators)
	 */
	 
	 function GetUserOctets($user,$days)
	 {
		 $retval = $this->DB->GetRow("
			SELECT SUM( AcctOutputOctets ) AS download, SUM( AcctInputOctets ) AS upload
			  FROM radacct
			 WHERE UserName = ?
			   AND (DATE_SUB( NOW() , INTERVAL ? DAY ) < AcctStopTime
					OR AcctStopTime is NULL)",array($user,$days));
		

		$retval['download'] = $this->IntToGB($retval['download']);
		$retval['upload']   = $this->IntToGB($retval['upload']);

		return $retval;
		
	 }	

	
}

?>
