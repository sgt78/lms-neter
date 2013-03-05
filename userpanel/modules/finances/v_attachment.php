<?
global $_LIB_DIR,$layout,$DB,$voip,$pdf,$SESSION;
setlocale(LC_ALL,'C');
$docid=$_GET['docid'];
$uid=$DB->GetOne('select customerid from documents where id=?',array($docid));
//var_dump($uid);
if($uid!=$SESSION->id) die;
$res=$DB->GetAll('select name,value from billing_details where documents_id=?',array($docid));
$data=array();
$i=1;$suma=0;$poz=0;
foreach($res as $val)
{
$el=array();
$el['L.p.']=$i++;
$el['Nazwa us³ugi']=iconv('UTF-8','ISO-8859-2//TRANSLIT',$val['name']);
$el['Liczba jedn.']='1.00';
//$el['Cena jedn.']=$val['value'];
//$el['Stawka VAT']='22,00';
$el['Warto¶æ netto']=$val['value'];
$data[]=$el;
$suma+=$val['value'];$poz++;
}
$el=array();
$el['Nazwa us³ugi']='Razem';
$el['Warto¶æ netto']=number_format($suma,2,'.','');
$el['Liczba jedn.']=number_format($poz,2,'.','');
$data[]=array();
$data[]=$el;

require_once(LIB_DIR.'/pdf.php');

$pdf =& init_pdf('A4', 'portrait', trans('Invoices'));
$res=$DB->GetRow('select number,cdate from documents where id=?',array($docid));
$numer=$res['number'].date('/m/Y/',$res['cdate']).'LMS';
//$id=$pdf->getFirstPageId();
$pdf->ezText("Za³±cznik do faktury VAT\nNr $numer\n",30,array('left'=>130));
$pdf->ezTable($data);
if($_GET['is_sure']==1)
{
$data=at_details($docid);
if(count($data)>0)
{
$pdf->ezNewPage();
$pdf->ezTable($data);
}
}
$pdf->ezStream();
close_pdf($pdf);

function at_details($id)
{
global $DB,$voip;
$res=$DB->GetRow('select cdate,customerid from documents where id=?',array($id));
$data=$voip->GetBilling($res['cdate'],$res['customerid']);
$out=array();$lp=1;
if(is_array($data)) foreach($data as $val)
{
$el=array();
$el['L.p.']=$lp++;
$el['Data']=$val['calldate'];
$el['Z numeru']=$val['src'];
$el['Na numer']=$val['dst'];
$el['Czas']=$val['seconds'];
$el['Koszt']=$val['tmp_cost'];
$el['Opï¿½ata']=$val['cost'];
$out[]=$el;
}
return $out;
}

?>
