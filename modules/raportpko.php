<?
$SMARTY->display('header.html');
echo '<H1>Załaduj plik</H1><form action="?m=raportpko" method="post" enctype="multipart/form-data">';
echo '<TABLE WIDTH="100%" CLASS="superlight" CELLPADDING="5"><TR><TD CLASS="FALL">';
echo '<input type="file" name="pko"> &nbsp; <input type="submit" value="wczytaj">';
echo '</TD></TR></TABLE></form><BR><BR>';
if(!empty($_FILES))
{
echo '<H1>Wykonane operacje</H1>';
echo '<TABLE WIDTH="100%" CLASS="superlight" CELLPADDING="5"><TR><TD CLASS="FALL">';
echo '<PRE>';
if($_FILES['pko']['error']!=UPLOAD_ERR_OK) echo 'Błąd wczytywania pliku !';
else
{
$f=file($_FILES['pko']['tmp_name']);
if(trim($f[1])!=':20:MT940') echo 'Nieprawidłowy format pliku';
else
{
	$out=array();
	for($i=0;$i<count($f);$i++)
	{
	$line=iconv('CP852','UTF-8//IGNORE',trim($f[$i]));
	
	if(preg_match('/^:([0-9FC]+):/',$line,$m))
	{
		switch($m[1])
		{
			case '61':
			if(!empty($out))
			{
				if($zap) zapis($out);
				$out=array();
			}
			$data=substr($line,10,4);
			$out['data']=mktime(0,0,0,substr($data,0,2),substr($data,2,2));
			preg_match('/[CD]{1}([0-9,]+)N/',$line,$kw);
			$out['kwota']=str_replace(',','.',$kw[1]);
			if($line[14]=='C') $zap=true; else $zap=false;
			break;	
		}
	
	}
	elseif(preg_match('/^~2/',$line))
		$out['opis'][]=substr($line,3);
	elseif(preg_match('/^~3[23]/',$line))
		$out['kto'][]=substr($line,3);
	elseif(preg_match('/^~63/',$line) and strlen($line)>5)
		$out['idk']=substr($line,-4);
	}
if(!empty($out) && $zap) zapis($out);
}
}
echo '</PRE>';
echo '</TD></TR></TABLE>';


}
$SMARTY->display('footer.html');

function zapis($c)
{
global $DB,$AUTH;
if(!$c['idk']) return;
foreach($c['opis'] as $key=>$val) if(ord($val)==194) unset($c['opis'][$key]);
echo 'Klient: '.(int)$c['idk'].' '.implode(' ',$c['kto']).' Kwota: '.$c['kwota'].' PLN Tytułem: '.implode('',$c['opis']);
echo "\n";
$DB->Execute('insert into cash (time,type,userid,value,customerid,comment) values (?,1,?,?,?,?)',array($c['data'],$AUTH->id,$c['kwota'],(int)$c['idk'],implode('',$c['opis'])));
//var_dump($c);//exit;
}
?>
