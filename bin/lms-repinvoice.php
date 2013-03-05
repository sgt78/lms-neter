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


$voip->rategroups=$voip->makerategroups();

$date=$argv[1];
if(!$date)
$date=date('Y/m/d');
$tmp=explode('/',$date);	
if($tmp[1] > 1) die('Uruchamiaj tylko w styczniu.');
$day=$tmp[2];
$cdate1=mktime(0,0,0,$tmp[1],$tmp[2],$tmp[0]);
$cdate2=mktime(23,59,59,$tmp[1],$tmp[2],$tmp[0]);

$alltaxes=$LMS->GetTaxes();
foreach($alltaxes as $val) 
	if($val['value']==22)
	{
		$taxid22=$val['id'];
	}
	elseif($val['value']==23)
	{
		$taxid23=$val['id'];
	}
	
$tax22=1.22;
$tax23=1.23;

$customers = $voip->_ImportInvoice_customers($day);
if(is_array($customers)) foreach($customers as $val)
{
$doc=$DB->GetRow('select id,itemid from documents left join invoicecontents on id=docid where cdate>=? and cdate<=? and customerid=? and description like \'Us%ugi telekomunikacyjne\'',array($cdate1, $cdate2, $val['lmsid']));

if(!$doc) continue; //nie ma co poprawiac

$ab = $voip->_ImportInvoice_ab($val['id']);
$now=mktime(1,0,0,$tmp[1],$day,$tmp[0]);
$last=strtotime('-1 month',$now);
$from=date('Y-m-d H:i:s',$last);
$to=str_replace('/','-',$date).' 01:00:00';

$netto23 = $ab['amount']; //abonament zawsze 23

if($to != '2011-01-01 01:00:00') //wszystkie polaczenia po starej stawce?
{
	$netto22=$voip->_ImportInvoice_imp($val['id'],$from, '2011-01-01 01:00:00');
	$netto23+=$voip->_ImportInvoice_imp($val['id'], '2011-01-01 01:00:00', $to);
}
else
	$netto22=$voip->_ImportInvoice_imp($val['id'],$from, $to);


$addserv=$voip->billaddserv($val['id']); //dodatkowe uslugi to abonamenty - zawsze 23
$netto23+=$addserv['sum'];

if($netto22 == 0 and $netto23 == 0) continue;

//trudna czesc - usuwanie
$DB->Execute('delete from invoicecontents where docid=? and itemid=?',array($doc['id'],$doc['itemid']));
$DB->Execute('delete from cash where docid=? and itemid=?',array($doc['id'],$doc['itemid']));

//dodajemy ponownie - z podzialem na 22 i 23 %
$DB->Execute('INSERT INTO invoicecontents (docid, value, taxid, prodid, content, count, description, tariffid, itemid, discount) VALUES (?,?,?,?,?,?,?,?,?,?)', array($doc['id'], round($tax22*$netto22,2), $taxid22,'','szt', 1, 'Usługi telekomunikacyjne 22%',0,$doc['itemid'],0));
$DB->Execute('INSERT INTO invoicecontents (docid, value, taxid, prodid, content, count, description, tariffid, itemid, discount) VALUES (?,?,?,?,?,?,?,?,?,?)', array($doc['id'], round($tax23*$netto23,2), $taxid23,'','szt', 1, 'Usługi telekomunikacyjne 23%',0,$doc['itemid']+1,0));

$DB->Execute('INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) VALUES (?,?,?,?,?,?,?)', array($now,round($tax22*$netto22,2)*-1,$taxid22,$val['lmsid'],'Usługi telekomunikacyjne 22%',$doc['id'],$doc['itemid']));
$DB->Execute('INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) VALUES (?,?,?,?,?,?,?)', array($now,round($tax23*$netto23,2)*-1,$taxid23,$val['lmsid'],'Usługi telekomunikacyjne 23%',$doc['id'],$doc['itemid']+1));

echo 'Klient '.$val['id']." poprawiony.\n";

}

?>
