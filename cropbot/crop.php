<?php
/*
Copyright Luxo 2008

This file is part of Cropbot.

    Cropbot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Cropbot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Cropbot.  If not, see <http://www.gnu.org/licenses/>.
    
    */
ini_set('user_agent', ' Cropbot by Luxo on the Toolserver / PHP');
include("logincheck.php");
session_start();

/* echo $_SESSION['uploader'];
 echo  $_SESSION['width'];
 echo  $_SESSION['height'];
 echo  $_SESSION['internname'];
 echo  $_SESSION['thumbname'];
 echo  $_SESSION['thumbwidth'];
 echo  $_SESSION['thumbheight'];*/
 
 $intnr = $_SESSION['internname'];

//skript muss neuen dateinamen zurückgeben
$error = false;

$W = $_GET['W'];
$H = $_GET['H'];

$X = $_GET['X'];
$Y = $_GET['Y'];
$cropmode = $_GET['cropmode'];


//überprüfen
if($W < 0 OR $W > 100000)
  $error = true;

if($H < 0 OR $H > 100000)
  $error = true;

if($X < 0 OR $X > 100000)
  $error = true;

if($Y < 0 OR $Y > 100000)
  $error = true;


// jpegtran -crop 156x353+68+73 img.jpg > img2.jpg
//WxH+X+Y 

if($error == false)
{
  sleep(5);
  $savepath = "/home/luxo/public_html/cropbot/cache/";

  $origimg = $savepath.$intnr.".jpg";
  $newimg = $savepath.$intnr."-3.jpg";
  
  
  if($cropmode == "IM")
  {
    exec("convert $origimg -crop ".$W."x".$H."+".$X."+".$Y."  +repage  $newimg",$return); //Imagemagick
  }
  else
  {
    exec("jpegtran -crop ".$W."x".$H."+".$X."+".$Y." $origimg > $newimg",$return); //JPEGTRAN
  }
  
  //convert rose: -crop 40x30+40+30  +repage  repage_br.gif

  
  
  if($W > 700)
  {
    //thumb erstellen 700px
        passthru("convert $newimg -resize 700 ".$savepath.$intnr."-4.jpg",$return);
        chmod ($savepath.$intnr."-4.jpg", 0644);
        echo"cache/".$intnr."-4.jpg";
        
  }
  else
  {
    echo"cache/".$intnr."-3.jpg";
  }
  
  chmod ($newimg, 0644);
  

}
else
{
  echo"ERROR";
}


?>
