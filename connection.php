<?php
$host='localhost';
$username='root';
$password='';
$db='bankapp';



$connection=new mysqli($host,$username,$password,$db);
if($connection->connect_errno){
    echo "Not Connected".$connection->connect_error;
}
else{
    echo "Connected";
}