<?php

$layout['pagetitle']='Przeniesienia numerów';

if(isset($_GET['id'])){

$id=$_GET['id'];
$linia=$voip->get_mov_data($id);
$operators=$voip->get_operators();

$SMARTY->assign('ops', $operators);
$SMARTY->assign('dane', $linia);
}


if(isset($_POST['idd'])){
$id=$_POST['idd'];
$stat=$_POST['sel'];
$odp=$voip->change_usr_mov_stat($id,$stat);
header('Location:?m=v_usr_movs');
}


if(isset($_POST['id_mov'])){
$id=$_POST['id_mov'];
$numery= str_replace('\'', '', $_POST['numery']);
$operr= $_POST['operr'];

$voip->change_usr_mov_data($id,$numery,$operr);
// unset($_POST['id_mov']);
header('Location:?m=v_usr_movs');
}


$n=$voip->get_movs();
// $all=$DB->GetAll('select * from user_mov');
// var_dump($all);
$SMARTY->assign('nums', $n);
$SMARTY->display('v_usr_movs.html');
?>
