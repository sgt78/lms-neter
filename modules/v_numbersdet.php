<?php
$rate=$voip->getratebyid($_GET['id']);
$layout['pagetitle']='Wzorce numerów - strefa numeracyjna '.$rate[0]['desc'];

$SMARTY->assign('rate',$rate[0]);
$SMARTY->assign('n',$voip->getnumbersfromrate($_GET['id']));
$SMARTY->display('v_numbersdet.html');
?>
