<?php
if($_GET['type']=='p')
{
$res=$voip->list_pow($_GET['id']);
if($res) foreach($res as $key=>$val)
echo "obj.options[obj.options.length] = new Option('$val','$key');";
}
elseif($_GET['type']=='m')
{
$res=$voip->list_mia($_GET['id']);
if($res) foreach($res as $key=>$val)
echo "obj.options[obj.options.length] = new Option('$val','$key');";
}
?>
