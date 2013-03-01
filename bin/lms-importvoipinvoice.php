#!/usr/bin/php
<?
$CONFIG_FILE = '/etc/lms/lms.ini';
if(is_readable('../lms.ini'))
	$CONFIG_FILE = '../lms.ini';
function lms_parse_ini_file($filename, $process_sections = false) 
{
	$ini_array = array();
	$section = '';
	$lines = file($filename);
	foreach($lines as $line) 
	{
		$line = trim($line);
		
		if($line == '' || $line[0] == ';' || $line[0] == '#') 
			continue;
		
		list($sec_name) = sscanf($line, "[%[^]]");
		
		if( $sec_name )
			$section = trim($sec_name);
		else 
		{
			list($property, $value) = sscanf($line, "%[^=] = '%[^']'");
			if ( !$property || !$value ) 
			{
				list($property, $value) = sscanf($line, "%[^=] = \"%[^\"]\"");
				if ( !$property || !$value ) 
				{
					list($property, $value) = sscanf($line, "%[^=] = %[^;#]");
					if( !$property || !$value ) 
						continue;
					else
						$value = trim($value, "\"'");
				}
			}
		
			$property = trim($property);
			$value = trim($value);
			
			if($process_sections) 
				$ini_array[$section][$property] = $value;
			else 
				$ini_array[$property] = $value;
		}
	}
	
	return $ini_array;
}

$CONFIG = array();

foreach(lms_parse_ini_file($CONFIG_FILE, true) as $key => $val)
	$CONFIG[$key] = $val;
$p=explode('/',getcwd());
unset($p[count($p)-1]);
$path=implode('/',$p);
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? $path : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$_LIB_DIR = $CONFIG['directories']['lib_dir'];
//require_once($_LIB_DIR.'/config.php');

require_once($_LIB_DIR.'/LMSDB.php');
require_once($_LIB_DIR.'/LMSVOIP.class.php');
require_once($_LIB_DIR.'/LMS.class.php');
require_once($_LIB_DIR.'/language.php');
require_once($_LIB_DIR.'/definitions.php');
$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $CONFIG);
if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
foreach($cfg as $row)
	$CONFIG[$row['section']][$row['var']] = $row['value'];

$voip=new LMSVOIP(&$DB,$CONFIG['phpui']);
setlocale(LC_NUMERIC, 'C');
$voip->ImportInvoice($argv[1]);
?>
