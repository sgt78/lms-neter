<?php

/* v2.1.1 - Przetestowane dok³adnie na TEST i dzia³ajace
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
 *  $Id: engine.php,v 1.9 2008/01/04 07:53:24 alec Exp $
 */

$cid = $document['customerid'];
$customerinfo = $LMS->GetCustomer($cid);

$sal=($customerinfo['balance']*-1);// bilans u¿ytkownika

$pos=-1;
$idheck=9999999999;//maksymalna mo¿liwa data
#odjêcie fv z terminem p³atnosci w przysz³osci
$future=$DB->GetOne("SELECT  sum(value) as value FROM documents LEFT JOIN invoicecontents ON id=docid WHERE cdate<'$idheck' AND customerid = ? AND type=1 AND closed=0 AND (documents.cdate+(documents.paytime*86400))>UNIX_TIMESTAMP() ORDER BY cdate DESC", array($cid));
#odjêcie sumy odsetek
$wezwanie=$DB->GetOne("SELECT sum(value) FROM documents doc LEFT JOIN debitnotecontents deb ON doc.id=deb.docid WHERE type=5 AND closed=0 AND customerid=?", array($cid));

$sal=$sal-$future-$wezwanie;
$customerinfo['balance']=$sal;
$odsetki=0;

while ($sal>0)
{
	$pos=$pos+1;
//Pobieranie wedle daty kolejnyfv do wykorzystania bilansu
	$customerdocuments[$pos]= $DB->GetRow("SELECT  number, cdate, paytime, ( select sum(value) FROM invoicecontents WHERE docid=id) as value, description, ((UNIX_TIMESTAMP()-cdate)/86400)-paytime as days FROM documents LEFT JOIN invoicecontents ON id=docid WHERE cdate<'$idheck' AND customerid = ? AND type=1 AND closed=0 AND (documents.cdate+(documents.paytime*86400))<UNIX_TIMESTAMP() ORDER BY cdate DESC", array($cid));
	$idheck=$customerdocuments[$pos]['cdate'];
	$sa=$sal;
	$sal=$sal-$customerdocuments[$pos]['value'];//pomniejszenie salda o wyszukan± FV
	$days=explode(".",$customerdocuments[$pos]['days']);
	if ($sal>=0)
	{
		$customerdocuments[$pos]['reszta']=$customerdocuments[$pos]['value'];//zapisanie reszty je¿eli potrzeba kolejnej faktóry
		$customerdocuments[$pos]['odsetki']=($customerdocuments[$pos]['reszta']*$days[0]*(11/100))/365;
	}
	if ($sal<0) 
	{
		$customerdocuments[$pos]['reszta']=$sa;//zapisanie reszty do ostatniej FV
		$customerdocuments[$pos]['odsetki']=($customerdocuments[$pos]['reszta']*$days[0]*(11/100))/365;
	}
		#zlicza odsetki
		$odsetki=$odsetki+$customerdocuments[$pos]['odsetki'];
	if ($pos==20){print "Niemoge pokryæ zaleg³o¶ci otwartymi fakturami";die();}
}

$item['value']=str_replace(",",".",$odsetki);
$note['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans
				WHERE doctype = ? AND isdefault = 1', array(DOC_DNOTE));
	
$note['number'] = $LMS->GetNewDocumentNumber(DOC_DNOTE, $note['numberplanid'], $note['cdate']);
$cdate = time();
$item['description']=$CONFIG['notes']['descript'];
$itemid=1;
$note['paytime']= $CONFIG['notes']['paytime'];


	$DB->Execute('INSERT INTO documents (number, numberplanid, type,
		                        cdate, userid, customerid, name, address, paytime,
		                        ten, ssn, zip, city, countryid, divisionid)
		                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
		                        array($note['number'],
		                                !empty($note['numberplanid']) ? $note['numberplanid'] : 0,
		                                DOC_DNOTE,
		                                $cdate,
		                                $AUTH->id,
		                                $customerinfo['id'],
				                $customerinfo['customername'],
				                $customerinfo['address'],
						$note['paytime'],
				                $customerinfo['ten'],
				                $customerinfo['ssn'],
				                $customerinfo['zip'],
				                $customerinfo['city'],
				                $customerinfo['countryid'],
				                $customerinfo['divisionid'],
					));

	$nid = $DB->GetOne('SELECT id FROM documents ORDER BY id DESC LIMIT 1');	
	$DB->Execute('INSERT INTO debitnotecontents (docid, itemid, value, description)
					VALUES (?, ?, ?, ?)', array($nid, $itemid, $item['value'], $item['description']));



		$LMS->AddBalance(array(
				     'time' => $cdate,
				     'value' => $item['value']*-1,
				     'taxid' => 0,
				     'customerid' => $customerinfo['id'],
				     'comment' => $item['description'],
				     'docid' => $nid,
				     'itemid'=> $itemid
				));
#sumuje odsetki
$customerinfo['balance']=$customerinfo['balance']+$odsetki;
#### Do zapisu s³ownego kwot
$KWOTA = trim($customerinfo['balance']);
$KWOTA_NR = str_replace(',','.',$KWOTA);  // na wszelki wypadek
$KWOTA_GR = sprintf('%02d',round(($KWOTA_NR - floor($KWOTA_NR))*100));
$SHORT_TO_WORDS = chkconfig($CONFIG['phpui']['to_words_short_version']);

if($SHORT_TO_WORDS)
{
	$KWOTA_ZL = to_words(floor($KWOTA_NR), 0, '', 1);
	$KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100';
}
else
{
	$KWOTA_ZL = to_words(floor($KWOTA_NR));
	$KWOTA_GR = to_words($KWOTA_GR);
	$KWOTA_X = trans('$0 dollars $1 cents', $KWOTA_ZL, $KWOTA_GR);
}
$customerinfo['word']=$KWOTA_X;

$document['template'] = $DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($document['numberplanid']));
$document['nr'] = docnumber($document['number'], $document['template']);
$SMARTY->assign(
		array(
			'customerinfo' => $customerinfo,
			'customerdocuments' => $customerdocuments,
			'document' => $document,
			'engine' => $engine,
		     )
		);

$output = $SMARTY->fetch($_DOC_DIR.'/templates/'.$engine['name'].'/'.$engine['template']);

?>
