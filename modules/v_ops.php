<?php
$layout['pagetitle']='Zarządzanie operatorami';

if(isset($_SESSION['kom'])){
$SMARTY->assign('kom', $_SESSION['kom']);
unset($_SESSION['kom']);
}

if(isset($_GET['id'])){
$linia=$DB->GetRow('select * from  operators WHERE id=?', array($_GET['id']));


$SMARTY->assign('dane', $linia);
}

if(isset($_GET['del'])){
$DB->Execute('delete from operators WHERE id=?', array($_GET['del']));

}


if(isset($_POST['id_op'])  && !empty($_POST['nazwa']) && !empty($_POST['adres'])&& !empty($_POST['kod']) && !empty($_POST['poczta'])){
$nazwa= str_replace('\'', '', $_POST['nazwa']);
$adres= str_replace('\'', '', $_POST['adres']);
$kod= str_replace('\'', '', $_POST['kod']);
$poczta= str_replace('\'', '', $_POST['poczta']);
$linia=$DB->Execute('UPDATE operators SET nazwa=?, adres=?, kod=?, poczta=?  WHERE id=?', array($nazwa, $adres, $kod, $poczta, $_POST['id_op']));

}

if(isset($_POST['id_op_add']) && !empty($_POST['nazwa']) && !empty($_POST['adres'])&& !empty($_POST['kod']) && !empty($_POST['poczta']) ){
$nazwa= str_replace('\'', '', $_POST['nazwa']);
$adres= str_replace('\'', '', $_POST['adres']);
$kod= str_replace('\'', '', $_POST['kod']);
$poczta= str_replace('\'', '', $_POST['poczta']);
$odp=$DB->Execute('INSERT INTO operators (nazwa, adres, kod, poczta) values(?,?,?,?)', array($nazwa, $adres, $kod, $poczta));
if($odp) $_SESSION['kom']='<span style="color:red; font-size:10pt; font-weight:bold">Dodano operatora</span>';
$SMARTY->assign('kom', $_SESSION['kom']);
header('Location: ?m=v_ops');
}


$n=$DB->GetAll('select * from operators ORDER BY nazwa');
// $all=$DB->GetAll('select * from user_mov');
// var_dump($all);
$SMARTY->assign('ops', $n);
$SMARTY->display('v_ops.html');
?>
