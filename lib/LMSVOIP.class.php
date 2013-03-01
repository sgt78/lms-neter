<?php
class LMSVOIP
{
var $db;
var $temp;
var $lmsdb;
var $config;
var $csql;
var $cnames;
var $admins;
var $mondir='/var/spool/asterisk/monitor/';
var $prepaid_ass;
var $fax_outgoingdir='/var/spool/asterisk/fax/outgoing/';
var $fax_incomingdir='/var/spool/asterisk/fax/incoming/';
var $fax_statusdir='/var/spool/asterisk/outgoing_done/';
var $gs='/usr/bin/gs';
var $rategroups;
var $mailboxdir='/var/spool/asterisk/voicemail/default/';
var $dialplan_file='/var/spool/asterisk/virtualpbx/dialplan.conf';
var $dialplan=array();
var $connected=true;
var $incvoipdir='/var/spool/asterisk/incvoip/';
var $ivrdir='/var/spool/asterisk/ivr/';

function __call($name,$args)
{
if(!$this->connected) return $this->ret($args[0]);
$hex=sprintf('%04s',strlen($name));
foreach($args as $key=>$val)
{
//$val=str_replace('"','&quot',$val);
$val=str_replace("\r\n",'%newl%',$val);
$val=str_replace("\n",'%newl2%',$val);
$args[$key]=$val;
}
//var_dump($args);
$str=$name.addslashes(serialize($args));

$hex.=$str;
$hex.=md5($this->config['pg_pass'])."\n";
//echo '<pre>';
//var_dump($hex);
//echo '</pre>';
$fp=fsockopen($this->config['pg_host'],32002,$errno,$errstr,2);
if($fp)
{
stream_set_timeout($fp,15);
fwrite($fp,$hex);

while(!feof($fp))
{
$linia.=fgets($fp,128);
$meta = stream_get_meta_data($fp);
if($meta['timed_out']) return $this->ret($args[0]);
}
fclose($fp);
if(!$linia) return $this->ret($args[0]);
}
else return $this->ret($args[0]);
$linia=trim($linia);

$output=unserialize(stripslashes($linia));
if(is_array($output)) foreach($output as $key=>$val)
{
//$val=str_replace('"','&quot',$val);
$val=str_replace('%newl%',"\r\n",$val);
$val=str_replace('%newl2%',"\n",$val);
$output[$key]=$val;
}
return $output;

}

function ret($arg)
{
$this->connected=false;
if(is_array($arg) && !empty($arg)) return $arg;
else return null;
}

function LMSVOIP($lmsdb,$config)
{
//$this->db=$db;
$this->lmsdb=$lmsdb;
if(is_array($config) && count($config)>0) $this->toiso(&$config);
$this->config=$config;
$this->csql=array();
$this->csql[1]='rates.id=10882';
$this->csql[2]='(rates.id=10880 or rates.id between 10891 and 11273 or rates.id between 11389 and 12645)';
$this->csql[3]='rates.id between 11299 and 11384';
$this->csql[4]='rates.id>15192';
$this->cnames=array();
$this->cnames[1]='Sieć CONNECT';
$this->cnames[2]='Numery strefowe';
$this->cnames[3]='Numery komórkowe';
$this->admins=array(60,10781,10830,11146,12699,12893);
$this->prepaid_ass=array('4468862'=>array('wych'=>1,'przych'=>6,'cena'=>25),
	'4468925'=>array('wych'=>3,'przych'=>6,'cena'=>50),
	'4468988'=>array('wych'=>6,'przych'=>6,'cena'=>75),
	'4469050'=>array('wych'=>12,'przych'=>12,'cena'=>100),
	'1'=>array('wych'=>0,'przych'=>0,'cena'=>5));
}

function toutf8($d)
{
	if(is_array($d))
	foreach($d as $key=>$val)
	{
	$d[$key]=iconv('ISO-8859-2','UTF-8//IGNORE',$val);
	}
	else $d=iconv('ISO-8859-2','UTF-8//IGNORE',$d);
	return $d;
}

function toiso($d)
{
	if(is_array($d))
	foreach($d as $key=>$val)
	{
		$d[$key]=iconv('UTF-8','ISO-8859-2//IGNORE',$val);
	}
	else $d=iconv('UTF-8','ISO-8859-2//IGNORE',$d);
	return $d;
}

	function GetNetworkList()
	{
		if($networks = $this->lmsdb->GetAll('SELECT id, name, start, count as size FROM v_netlist ORDER BY name'))
		{
			$size = 0; $assigned = 0;

			foreach($networks as $idx => $row)
			{
				//$row['prefix'] = mask2prefix($row['mask']);
				//$row['size'] = pow(2,(32 - $row['prefix']));
				//$row['broadcast'] = getbraddr($row['address'],$row['mask']);
				//$row['broadcastlong'] = ip_long($row['broadcast']);
				$row['assigned'] = $this->_GetNetworkList($row['start'], $row['start'] + $row['size']);
				$row['end']=sprintf('%010s',(int)$row['start'] + $row['size'] - 1);
            			$networks[$idx] = $row;
				$size += $row['size'];
				$assigned += $row['assigned'];
				//$online += $row['online'];
			}
			$networks['size'] = $size;
			$networks['assigned'] = $assigned;
		}
		return $networks;
	}

function NetworkExists($id)
{
return $this->lmsdb->GetOne('select count(id) from v_netlist where id=?',array($id));
}

function GetNetworkRecord($id)
{
$network = $this->lmsdb->GetRow('SELECT id, name, start, count as size FROM v_netlist where id=?',array($id));
//$size = 0; $assigned = 0;

$network['assigned'] = $this->_GetNetworkRecord($network);
$network['end']=sprintf('%010s',(int)$network['start'] + $network['size'] - 1);
$network['page']=1;
$network['pages']=1;
$nodes=$this->_getnodes((int)$network['start'],(int)$network['end']);
//var_dump($nodes);
for($i=0;$i<$network['size'];$i++)
{
$j=sprintf('%010d',(int)$network['start']+$i);
$network['nodes']['id'][$i]=($nodes[$j] ? $nodes[$j]['id'] : 0 );
$network['nodes']['name'][$i]= ($nodes[$j] ? $nodes[$j]['name'] : 0 );
$network['nodes']['address'][$i]=$j;

}
$network['rows'] = ceil(sizeof($network['nodes']['address']) / 4);
$network['pageassigned']=$network['assigned'];
$network['free']=$network['size']-$network['assigned'];
return $network;
}

function NetworkUpdate($d)
{
$count=(int)$d['end']-(int)$d['start']+1;
$this->lmsdb->Execute('update v_netlist set name=?,start=?,count=? where id=?',array($d['name'],$d['start'],$count,$d['id']));

//var_dump($d);exit;
}

function NetworkAdd($d)
{
$this->lmsdb->Execute('insert into v_netlist (name,start,count) values (?,?,?)',array($d['name'],$d['start'],$d['count']));
return $this->lmsdb->GetOne('select last_insert_id() from v_netlist');
}

function NetworkDelete($id)
{
$this->lmsdb->Execute('delete from v_netlist where id=?',array($id));
}

function GetNetworks()
{
if($netlist = $this->lmsdb->GetAll('SELECT id, name, start AS address, count as prefix FROM v_netlist ORDER BY name'))
return $netlist;
}

function GetCustomerVoipBalance($id)
{
return $this->lmsdb->GetOne('select sum(value) from voip_finances where user=?',array($id));
}

function get_billing_details($tslist)
{
if(is_array($tslist['id'])) foreach($tslist['id'] as $key=>$val)
{
if($this->lmsdb->GetOne('select count(id) from billing_details where documents_id=?',array($tslist['docid'][$key]))>0)
$tslist['details'][$key]=1;
else $tslist['details'][$key]=0;
}
}

function get_billing_details2($tslist)
{
if(is_array($tslist)) foreach($tslist as $key=>$val) if(is_array($val))
{
if($this->lmsdb->GetOne('select count(id) from billing_details where documents_id=?',array($val['id']))>0)
$tslist[$key]['details']=1;
else $tslist[$key]['details']=0;
}
return $tslist;
}

function update_user($d)
{
$u=$this->lmsdb->GetRow('select lastname, name, email, address, zip, city, ten,pin from customers where id=?',array($d['id']));
foreach($u as $key=>$val)
	$u[$key]=iconv('UTF-8','ISO-8859-2//IGNORE',$val);
$u['password']=md5($u['pin']);
$this->_update_user($d,$u);
}

function uiGetCustomerBalance($id)
{
$res=$this->lmsdb->GetAll('select date,value,`desc` as comment from voip_finances where user=? order by id',array($id));
$after=0;
if(is_array($res)) foreach($res as $key=>$val)
{
$after+=$val['value'];
$val['after']=$after;
$res[$key]=$val;
}
return $res;
}

function preparepayment($user)
{
        $rand=md5(uniqid(rand(), true));
        $this->lmsdb->Execute('insert into platnosci (user,date,sid) values (?,unix_timestamp(),?)',array($user,$rand));
        $this->lmsdb->Execute('delete from platnosci where date < unix_timestamp()-84600');
        return $rand;
}

function fax_outbox($u,$limit=0)
{
$res=$this->lmsdb->GetAll('select * from v_fax where user=? order by id desc',array($u));
$user=$this->GetAstId($u);
$status='';
if(is_array($res)) foreach($res as $key=>$val)
{
	$statusfile=$this->fax_statusdir.$user.'_'.$val['uniqueid'];
	if(is_file($statusfile))
	{
		$fp=fopen($statusfile,'r');
		while(!feof($fp))
		{
			$line=fgets($fp);
			if(substr($line,0,7)=='Status:') $status=substr($line,8);
		}
		fclose($fp);
	}
	if(!$status)
		if($val['data']+600 < time()) $status='Błąd';
			else $status='Przekazany do wysłania';
	$res[$key]['status']=$status;
	if(file_exists($this->fax_outgoingdir.$user.'/'.$val['uniqueid'].'.tif'))
		$res[$key]['allowprint']=true;
}
return $res;
}

function ui_deletefout($d,$user)
{
$uid=$this->GetAstId($user);
foreach($d as $val)
{
	$uniq=$this->lmsdb->GetOne('select uniqueid from v_fax where id=? and user=?',array($val,$user));
	if($uniq)
	{
		$fname=$this->fax_outgoingdir.$uid.'/'.$uniq.'.tif';
		$this->lmsdb->Execute('delete from v_fax where id=?',array($val));
		@unlink($fname);
	}
}
}

function ui_faxsa($id,$user)
{
$out=$this->lmsdb->GetRow('select nr_from,nr_to,uniqueid,filename from v_fax where id=? and user=?',array($id,$user));
if(!$out) return;
$out['id_ast_sip']=$this->_ui_faxsa($out['nr_from']);
return $out;
}

function preparetofax($f,$nrfrom,$nrto,$user=0)
{
$subdir=$this->GetAstId($user);
do
	$filename=substr(md5(uniqid(rand(), true)),-10,8);
while(file_exists($this->fax_outgoingdir.$subdir.'/'.$filename.'.tif'));
$fname=$nrfrom.'-'.$nrto.'-'.$filename.'.tif';
exec($this->gs.' -q -dNOPAUSE -dBATCH -r204x98 -dSAFER -sDEVICE=tiffg3 -sOutputFile='.$this->fax_outgoingdir.$fname.' -f '.$f['tmp_name']);
$this->lmsdb->Execute('insert into v_fax (nr_from,nr_to,data,user,uniqueid,filename) values (?,?,unix_timestamp(),?,?,?)',array($nrfrom,$nrto,$user,$filename,$f['name']));
}

function preparetofax_again($f,$nrfrom,$nrto,$user=0)
{
$subdir=$this->GetAstId($user);
if(!file_exists($this->fax_outgoingdir.$subdir.'/'.$f.'.tif')) return false;
do
	$filename=substr(md5(uniqid(rand(), true)),-10,8);
while(file_exists($this->fax_outgoingdir.$subdir.'/'.$filename.'.tif'));
$fname=$nrfrom.'-'.$nrto.'-'.$filename.'.tif';
copy($this->fax_outgoingdir.$subdir.'/'.$f.'.tif',$this->fax_outgoingdir.$fname);
$forig=$this->lmsdb->GetOne('select filename from v_fax where uniqueid=?',array($f));
$this->lmsdb->Execute('insert into v_fax (nr_from,nr_to,data,user,uniqueid,filename) values (?,?,unix_timestamp(),?,?,?)',array($nrfrom,$nrto,$user,$filename,$forig));
return true;
}

function GetTaxId()
{
return $this->lmsdb->GetOne('select id from taxes where value=?',array(22));
}

function InvH($user,$od)
{
global $LMS;

$alltaxes=$LMS->GetTaxes();
$this->UpdateTax($alltaxes);
foreach($alltaxes as $val) if($val['label']=='VOIP')
{
	$tax=$val['value'];
	$taxid1=$val['id'];
	break;
}
if($taxid1) foreach($alltaxes as $val) if($val['value']==$tax and $val['id']!=$taxid1)
{
	$taxid=$val['id'];
	break;
}

if(!$tax)
{
	if($tmp[0]>=2011) $tax=23;
	else $tax=22;
}
if(!$taxid) $taxid=1;

$tax=$tax/100+1;

$us=$this->GetAstId($user);
$ab = $this->_ImportInvoice_ab($us);
$diff=date('t')-$od+1;
$netto=number_format(($ab['amount']/date('t'))*$diff,2,'.','');
$now=time();
$numberplan=$this->lmsdb->GetOne('SELECT id FROM numberplans WHERE doctype = 1 AND isdefault = 1');
if(!$numberplan) $numberplan=0;
$number=$LMS->GetNewDocumentNumber(DOC_INVOICE, $numberplan, $now);
$urow=$this->lmsdb->GetRow('SELECT lastname, name, address, city, zip, ssn, ten FROM customers WHERE id=?', array($user));

$this->lmsdb->Execute('INSERT INTO documents (number, numberplanid, type, customerid, name, address, zip, city, ten, ssn, cdate, paytime, paytype, divisionid) VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2, 1)', array($number, $numberplan, $user, $urow['lastname'].' '.$urow['name'], $urow['address'], $urow['zip'], $urow['city'], $urow['ten'], $urow['ssn'], $now, 7));
$docid=$this->lmsdb->GetOne('SELECT id FROM documents WHERE number=? AND cdate=? AND type = 1 AND customerid=?', array($number, $now, $user));
$itemid=1;

$this->lmsdb->Execute('INSERT INTO invoicecontents (docid, value, taxid, prodid, content, count, description, tariffid, itemid, discount) VALUES (?,?,?,?,?,?,?,?,?,?)', array($docid, number_format($tax*$netto,2,'.',''), $taxid,'','szt', 1, 'Usługi telekomunikacyjne',0,$itemid,0));

$this->lmsdb->Execute('INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) VALUES (?,?,?,?,?,?,?)', array($now,number_format(-$tax*$netto,2,'.',''),$taxid,$user,'Usługi telekomunikacyjne',$docid,$itemid));
$this->_ImportInvoice_updatefreesec(round(($ab['free']/date('t'))*$diff)*60,$us);
}


function ImportInvoice($date)
{
global $LMS;
$this->rategroups=$this->makerategroups();

if(!$date)
$date=date('Y/m/d');
$tmp=explode('/',$date);	
$day=$tmp[2];

$alltaxes=$LMS->GetTaxes();
$this->UpdateTax($alltaxes);
foreach($alltaxes as $val) if($val['label']=='VOIP')
{
	$tax=$val['value'];
	$taxid1=$val['id'];
	break;
}
if($taxid1) foreach($alltaxes as $val) if($val['value']==$tax and $val['id']!=$taxid1)
{
	$taxid=$val['id'];
	break;
}

if(!$tax)
{
	if($tmp[0]>=2011) $tax=23;
	else $tax=22;
}
if(!$taxid) $taxid=1;

$tax=$tax/100+1;

$customers = $this->_ImportInvoice_customers($day);
if(is_array($customers)) foreach($customers as $val)
{
$ab = $this->_ImportInvoice_ab($val['id']);
$now=mktime(1,0,0,$tmp[1],$day,$tmp[0]);
$last=strtotime('-1 month',$now);
$from=date('Y-m-d H:i:s',$last);
$to=str_replace('/','-',$date).' 01:00:00';

$imp = $this->_ImportInvoice_imp($val['id'],$from, $to);
$netto=$imp+$ab['amount'];

$this->_ImportInvoice_updatefreesec($ab['free']*60,$val['id']);

$addserv=$this->billaddserv($val['id']);
$netto+=$addserv['sum'];

if($netto == 0) continue;

//sprawdz, czy taryfa jest dual
$docid=$this->lmsdb->GetOne('select id from documents left join invoicecontents on id=docid where DAY(FROM_UNIXTIME(cdate))=? and MONTH(FROM_UNIXTIME(cdate))=? and YEAR(FROM_UNIXTIME(cdate))=? and customerid=?',array($day,$tmp[1],$tmp[0],$val['lmsid']));
if($docid)
{
$itemid=$this->lmsdb->GetOne('select max(itemid) from invoicecontents where docid=?',array($docid));
$itemid++;
}
else
{
$numberplan=$this->lmsdb->GetOne('SELECT id FROM numberplans WHERE doctype = 1 AND isdefault = 1');
//$numberplan=3;
//var_dump($numberplan);exit;
if(!$numberplan) $numberplan=0;
$number=$LMS->GetNewDocumentNumber(DOC_INVOICE, $numberplan, $now);
$urow=$this->lmsdb->GetRow('SELECT lastname, name, address, city, zip, ssn, ten FROM customers WHERE id=?', array($val['lmsid']));

$this->lmsdb->Execute('INSERT INTO documents (number, numberplanid, type, customerid, name, address, zip, city, ten, ssn, cdate, paytime, paytype, divisionid) VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2, 1)', array($number, $numberplan, $val['lmsid'], $urow['lastname'].' '.$urow['name'], $urow['address'], $urow['zip'], $urow['city'], $urow['ten'], $urow['ssn'], $now, 7));
$docid=$this->lmsdb->GetOne('SELECT id FROM documents WHERE number=? AND cdate=? AND type = 1 AND customerid=?', array($number, $now, $val['lmsid']));
$itemid=1;
}
$this->lmsdb->Execute('INSERT INTO invoicecontents (docid, value, taxid, prodid, content, count, description, tariffid, itemid, discount) VALUES (?,?,?,?,?,?,?,?,?,?)', array($docid, round($tax*$netto,2), $taxid,'','szt', 1, 'Usługi telekomunikacyjne',0,$itemid,0));

$this->lmsdb->Execute('INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) VALUES (?,?,?,?,?,?,?)', array($now,round($tax*$netto,2)*-1,$taxid,$val['lmsid'],'Usługi telekomunikacyjne',$docid,$itemid));

echo "CID: {$val['lmsid']} VAL: ".round($tax*$netto,2)." DESC: Usługi telekomunikacyjne\n";

//billing_details

$cachedrates=array();
$konta=$this->_ImportInvoice_konta($val['id']);
foreach($konta as $konto)
{
$ab=$this->_ImportInvoice_abbd($konto['id_subscriptions']);
if($ab['amount']>0)
$this->lmsdb->Execute('insert into billing_details (documents_id, name, value) values (?,?,?)', array($docid, $ab['name'],$ab['amount']));

$pol=$this->_ImportInvoice_pol($val['id'],$from,$to,$konto['accountcode']);
$price=array();
if(is_array($pol)) foreach($pol as $po)
{
	if($cachedrates[$po['id_rates']])
		$rategr=$cachedrates[$po['id_rates']];
	else
	{
		$rategr=$this->_ImportInvoice_rategr($po['dst'],$po['id_rates']);
		$cachedrates[$po['id_rates']]=$rategr;
	}
	$price[$rategr]+=$po['cost'];
}
foreach($price as $rtg=>$cost) if($cost>0)
{
$name=$this->rategroups[$rtg].' - konto '.$konto['accountcode'];
$this->lmsdb->Execute('insert into billing_details (documents_id, name, value) values (?,?,?)', array($docid, $name,$cost));
//echo "$name -> $cost\n";
}
}

if(is_array($addserv['data'])) foreach($addserv['data'] as $adds)
{
	$name=$adds['dname'].' - '.$adds['name'];
	$this->lmsdb->Execute('insert into billing_details (documents_id, name, value) values (?,?,?)', array($docid, $name,$adds['price']));
}

}

$users=$this->GetCustomerNames();
foreach($users as $us)
{
	$this->UpdateCustomerBalance($us['id'],-$LMS->GetCustomerBalance($us['id']));
}
if(isset($LMS->CONFIG['phpui']['voip_timeswitch']) and $LMS->CONFIG['phpui']['voip_timeswitch'] == 1) $this->EnableTimeAccounts($date);
}

function export_user($lmsid,$type='postpaid')
{
$u=$this->lmsdb->GetRow('select lastname, name, email, address, zip, city, ten,pin from customers where id=?',array($lmsid));
foreach($u as $key=>$val)
	$u[$key]=iconv('UTF-8','ISO-8859-2//IGNORE',$val);
$u['password']=md5($u['pin']);
$this->_export_user($lmsid, $type, $u);
$this->lmsdb->Execute('insert into v_exportedusers values (?)',array($lmsid));
}

function CustomerExists($id)
{
if($this->lmsdb->GetOne('select count(*) from v_exportedusers where lmsid=?',array($id)) > 0) return true; else return false;
}

function GetState()
{
        $api=new floAPI($this->config['voip_as_login'], $this->config['voip_as_pass'], $this->config['voip_as_host']);
        $out=array();
        $out['clients']=$api->request('COMMAND', array('COMMAND' => 'sip show peers'));
        $out['channels']=$api->request('COMMAND', array('COMMAND' => 'core show channels'));
        $api->close();
return $out;
}

function reload_dialplan()
{
        $api=new floAPI($this->config['voip_as_login'], $this->config['voip_as_pass'], $this->config['voip_as_host']);
        $api->request('COMMAND', array('COMMAND' => 'dialplan reload'));
        $api->close();
}

function DeleteCustomer($lmsid)
{
$this->_DeleteCustomer($lmsid);
$this->lmsdb->Execute('delete from v_exportedusers where lmsid=?',array($lmsid));
}

function faxprint($u,$id,$type)
{
$user=$this->GetAstId($u);
switch($type)
{
        case 'incoming':
        $file=$this->_faxprint($u,$id,$type);
        break;

        case 'outgoing':
        $uniqid=$this->lmsdb->GetOne('select uniqueid from v_fax where user=? and id=?',array($u,$id));
        if(!$uniqid) return null;
        $file=$this->fax_outgoingdir.$user.'/'.$uniqid.'.tif';
        break;

        default:
        return null;
}
if(file_exists($file)) return $file;
return null;
}

function GetUserToSettings($id,$field)
{
return $this->lmsdb->GetRow('select lastname,name,'.$field.' as login, pin from customers where id=?',array($id));
}

function change_usr_mov_stat($id,$stat)
{
// echo $id;
// echo $stat;
// exit;;
return $this->lmsdb->Execute('UPDATE user_mov SET status=? WHERE id=?', array($stat, $id));
}


function change_usr_mov_data($id_mov,$numery,$operr)
{

return $this->lmsdb->Execute('UPDATE user_mov SET numery=?, oper=? WHERE id=?', array($numery, $operr, $id_mov));
}


function get_mov_data($id)
{
return $this->lmsdb->GetRow('select um.*, c.lastname from user_mov um, customers c WHERE c.id=um.id_user AND um.id=?', array($id));
}

function get_operators()
{

return $this->lmsdb->GetAll('select id, nazwa from operators');
}

function get_movs()
{
 // $test=$this->lmsdb->Execute('delete from user_mov');
 // var_dump($test);
 // exit;
	return $this->lmsdb->GetAll('select um.*, c.lastname, c.id as cusid, o.nazwa from user_mov um, operators o, customers c WHERE o.id=um.oper AND c.id=um.id_user ORDER BY status ASC, data DESC');
}
function get_my_movs($id)
{
$t= $this->lmsdb->GetAll('select user_mov.id, numery, status, o.nazwa from user_mov, operators o where o.id=user_mov.oper AND user_mov.id_user=?', array($id));

return $t;
}

function user_mov_add($id, $numery, $nr_ewid, $oper, $status, $data)
{
	return $this->lmsdb->Execute('insert into user_mov (id_user, numery, nr_ewid, oper, status, data) values(?,?,?,?,?,?)', array($id, $numery, $nr_ewid, $oper, $status, $data));
}


function get_pdf1_data($id, $uid)
{
	return $this->lmsdb->GetRow('select um.*, c.* from user_mov um, customers c WHERE c.id=um.id_user AND um.id=? AND um.id_user=?', array($id, $uid));
}

function get_pdf2_data($id, $uid)
{
	return $this->lmsdb->GetRow('select um.*, c.*, o.* from user_mov um, customers c, operators o WHERE o.id=um.oper AND c.id=um.id_user AND um.id=?  AND um.id_user=?', array($id, $uid));
}
function get_pdf3_data($id, $uid)
{
	return $this->lmsdb->GetRow('select um.*, c.*, o.* from user_mov um, customers c, operators o WHERE o.id=um.oper AND c.id=um.id_user AND um.id=?  AND um.id_user=?', array($id, $uid));
}


function get_mov_stat(){
$res[0]= $this->lmsdb->GetAll('select count(status) as s from user_mov where status=0 group by status');
$res[1]= $this->lmsdb->GetAll('select count(status)  as s from user_mov where status=1 group by status');
$res[2]= $this->lmsdb->GetAll('select count(status) as s  from user_mov where status=2 group by status');
return $res;
}

function GetTariff($id)
{
$tariff=$this->_GetTariff($id);
foreach((array)$tariff['idlms'] as $val)
{
	$temp = $this->lmsdb->GetRow('SELECT id,  '.$this->lmsdb->Concat('upper(lastname)',"' '",'name').' AS customername FROM customers WHERE id = ? AND deleted = 0', array($val['lmsid']));
	$temp['customername'].=' ('.$val['name'].')';
	$tariff['customers'][]=$temp;
}
return $tariff;
}

function GetCustomersWithT($id)
{
$cust=$this->_GetCustomersWithT($id);
foreach((array)$cust['idlms'] as $val)
{
	$temp = $this->lmsdb->GetRow('SELECT id,  '.$this->lmsdb->Concat('upper(lastname)',"' '",'name').' AS customername FROM customers WHERE id = ? AND deleted = 0', array($val['lmsid']));
	$temp['customername'].=' ('.$val['name'].')';
	$cust['customers'][]=$temp;
}
return $cust;
}

function ivr_uploadfile($file,$user)
{
$us=$this->GetAstId($user);
if(!is_dir($this->ivrdir.$us)) mkdir($this->ivrdir.$us);
$roz=substr($file['name'],-3);
do
$filename=substr(md5(uniqid(time())),8,8).'.'.$roz;
while(file_exists($this->ivrdir.$us.'/'.$filename));
//move_uploaded_file($file['tmp_name'],$this->ivrdir.$us.'/'.$filename);
exec('/usr/bin/sox '.$file['tmp_name'].' -r 8000 -c 1 -s '.$this->ivrdir.$us.'/'.$filename);
return $filename;
}

function ivr_deletefile($file,$user)
{
$us=$this->GetAstId($user);
if(file_exists($this->ivrdir.$us.'/'.$file)) unlink($this->ivrdir.$us.'/'.$file);
}

}
?>
