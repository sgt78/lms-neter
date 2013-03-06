<?php
global $LMS,$SMARTY,$SESSION,$DB,$voip;
if(isset($_SESSION['kom'])){
$SMARTY->assign('kom', $_SESSION['kom']);
unset($_SESSION['kom']);
}
if($_GET['genpdf']==1){
 $linia=$voip->get_pdf1_data($_GET['id'], $SESSION->id);
 
$SMARTY->assign('d', $linia);
 $SMARTY->display('module:doc1.html');
exit;
}

if($_GET['genpdf']==2){
 $linia=$voip->get_pdf2_data($_GET['id'], $SESSION->id);
 
$SMARTY->assign('d', $linia);
$SMARTY->display('module:doc2.html');
exit;
}

if($_GET['genpdf']==3){
 $linia=$voip->get_pdf3_data($_GET['id'], $SESSION->id);
 
$SMARTY->assign('d', $linia);
$SMARTY->display('module:doc3.html');
exit;
}

if($_GET['genpdf']==3){
 $linia=$voip->get_pdf3_data($_GET['id'], $SESSION->id);
 
$SMARTY->assign('d', $linia);
$SMARTY->display('module:doc3.html');
exit;
}

if(isset($_POST['mov']) && !empty($_POST['mov']['numery'])&& !empty($_POST['mov']['nr_ewid'])){
$id=$SESSION->id;

$numery= str_replace('\'', '', $_POST['mov']['numery']);
$nr_ewid= $_POST['mov']['nr_ewid'];
$oper= $_POST['mov']['operator'];

$odp=$voip->user_mov_add($id, $numery, $nr_ewid, $oper, 0, date('Y-m-d'));

if($odp) $_SESSION['kom']='<span style="color:red; font-size:10pt; font-weight:bold">Zgłoszenie zostało przyjęte</span>';
header('Location: ?m=docs');
}

$docs=$voip->get_my_movs($SESSION->id);


$operators=$voip->get_operators();
// $all=$DB->GetAll('select * from user_mov');
// var_dump($all);
$SMARTY->assign('ops', $operators);
$SMARTY->assign('docs', $docs);
$SMARTY->display('module:docs.html');

?>
