<?php
/*include 'lib/config.php';
include LMS_LIB.'LMSDB.php';
$db=DBinit('mysql',DBHOST,DBUSER,DBPASS,DBNAME);
$db->iconv='ISO-8859-2';
addinv(225,100);
*/

function addinv($customerid,$value)
{
global $db;
//$tariff=$db->GetRow("select name,value from tariffs where id=?", array($tariffid));
$customer=GetCustomer($customerid);
/*$dzien=$this->DB->GetOne("select at from assignments where tariffid=? and customerid=?",array($tariffid,$customerid));

$d=date("j");
$miesiac=date("n");
$rok=date("y");
if($dzien>$d) $miesiac--;
if($miesiac==0)
{
$rok--;
$miesiac=1;
}
$rok+=2000;
$ctime=mktime(1,0,0,$miesiac,$dzien,$rok);
$typ=$this->DB->GetOne('select typ from customers where id=?',array($customerid));
if($typ=='B') $planid=3; else $planid=2;*/
$planid=3;
$ctime=time();
$invoice['number']=GetNewDocumentNumber(1, $planid, time());

$invoice['paytype']='Wpłata na konto bankowe';
$invoice['paytime']=14;
$invoice['cdate']=$ctime;
$invoice['type']=1;
$invoice['numberplanid']=$planid;
$item['valuebrutto']=$value;
$item['pkwiu']='';
$item['jm']='szt';
$item['count']=1;
$item['name']='Zasilenie konta prepaid';
$item['tariffid']=0;
$item['taxid']=1;
$item['prodid']=0;
$out['customer']=$customer;
$out['invoice']=$invoice;
$out['contents'][0]=$item;
AddInvoice($out);
}

function GetCustomer($id)
{
global $db;
if($result = $db->GetRow('SELECT id, '.$db->Concat('UPPER(lastname)',"' '",'name').' AS customername, lastname, name, status, email, im, phone1, phone2, phone3, address, zip, ten, ssn, city, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, pin, IF(cutoff=\'0\',\'nie\',\'tak\')  as cutofftext,cutoff FROM lms.customers WHERE id=?', array($id)))
                {
                        //$result['createdby'] = $this->GetUserName($result['creatorid']);
                        //$result['modifiedby'] = $this->GetUserName($result['modid']);
                        //$result['creationdateh'] = date('Y/m/d, H:i',$result['creationdate']);
                        //$result['moddateh'] = date('Y/m/d, H:i',$result['moddate']);
                        //$result['balance'] = $this->GetCustomerBalance($result['id']);
                        //$result['tariffsvalue'] = $this->GetCustomerTariffsValue($result['id']);
                        return $result;
                }else
//var_dump($this->DB->errors);
return FALSE;
}

function GetNewDocumentNumber($doctype=NULL, $planid=NULL, $cdate=NULL)
        {
global $db;
                if($planid)
                        $period = $db->GetOne('SELECT period FROM lms.numberplans WHERE id=?', array($planid));
                else
                        $planid = 0;

                $period = $period ? $period : YEARLY;
                $cdate = $cdate ? $cdate : time();

                $start = mktime(0, 0, 0, date('n',$cdate), 1, date('Y',$cdate));
                $end = mktime(0, 0, 0, date('n',$cdate)+1, 1, date('Y',$cdate));
                
                $number = $db->GetOne('
                                SELECT MAX(number)
                                FROM lms.documents
                                WHERE cdate >= ? AND cdate < ? AND type = ? AND numberplanid = ?',
                                array($start, $end, $doctype, $planid));
                return $number ? ++$number : 1;
        }

	function AddInvoice($invoice)
	{
global $db,$fid;
		$cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : time();
		$number = $invoice['invoice']['number'];
		$type = $invoice['invoice']['type'];

		$db->Execute('INSERT INTO lms.documents (number, numberplanid, type, cdate, paytime, paytype, userid, customerid, name, address, ten, ssn, zip, city)
				    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				    array($number, 
					    $invoice['invoice']['numberplanid'] ? $invoice['invoice']['numberplanid'] : 1, 
					    $type, 
					    $cdate, 
					    $invoice['invoice']['paytime'], 
					    $invoice['invoice']['paytype'], 
					    0,
					    $invoice['customer']['id'], 
					    $invoice['customer']['customername'], 
					    $invoice['customer']['address'], 
					    $invoice['customer']['ten'], 
					    $invoice['customer']['ssn'], 
					    $invoice['customer']['zip'], 
					    $invoice['customer']['city']
					));
		$iid = $db->GetOne('SELECT id FROM lms.documents WHERE number = ? AND cdate = ? AND type = ?', array($number,$cdate,$type));
$db->Execute('update finances set invoiceid=? where id=?',array($iid,$fid));
		$itemid=0;
		foreach($invoice['contents'] as $idx => $item)
		{
			$itemid++;
			$item['valuebrutto'] = str_replace(',','.',$item['valuebrutto']);
			$item['count'] = str_replace(',','.',$item['count']);
			$item['discount'] = str_replace(',','.',$item['discount']);

			$db->Execute('INSERT INTO lms.invoicecontents (docid, itemid, value, taxid, prodid, content, count, discount, description, tariffid) 
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array(
					$iid,
					$itemid,
					$item['valuebrutto'],
					$item['taxid'],
					$item['prodid'],
					$item['jm'],
					$item['count'],
					$item['discount'],
					$item['name'],
					0));

			AddBalance(array('value' => $item['valuebrutto']*$item['count']*-1, 'taxid' => $item['taxid'], 'customerid' => $invoice['customer']['id'], 'comment' => $item['name'], 'docid' => $iid, 'itemid'=>$itemid, 'time'=>$cdate));
			AddBalance(array('value' => $item['valuebrutto']*$item['count'], 'taxid' => $item['taxid'], 'customerid' => $invoice['customer']['id'], 'comment' => 'wpłata na konto', 'docid' => 0, 'itemid'=>0, 'time'=>$cdate));
		}
		return $iid;
	}

	function AddBalance($addbalance)
	{
//		$this->SetTS('cash');
global $db;
		$addbalance['value'] = str_replace(',','.',round($addbalance['value'],2));

		return $db->Execute('INSERT INTO lms.cash (time, userid, value, type, taxid, customerid, comment, docid, itemid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array($addbalance['time'] ? $addbalance['time'] : time(),
					    $addbalance['userid'] ? $addbalance['userid'] : 0,
					    $addbalance['value'],
					    $addbalance['type'] ? $addbalance['type'] : 0,
					    $addbalance['taxid'] ? $addbalance['taxid'] : 0,
					    $addbalance['customerid'],
					    $addbalance['comment'],
					    $addbalance['docid'] ? $addbalance['docid'] : 0,
					    $addbalance['itemid'] ? $addbalance['itemid'] : 0
					    ));
	}

?>
