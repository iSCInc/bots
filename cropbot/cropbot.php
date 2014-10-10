<?php
/*
Copyright Luxo 2009

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
   ini_set('display_errors', 1);
   error_reporting(E_ALL & ~E_NOTICE);
ini_set('user_agent', ' Cropbot by Luxo on the Toolserver / PHP');
session_start();
include("logincheck.php");
include("clearer.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="author" content="Luxo">
  <title>Cropbot</title>
    <script src="lib/prototype.js" type="text/javascript"></script>
    <script language="javascript" type="text/javascript">
    function gotourl(url)
    {
      var W = screen.width;
      var H = screen.height;
      var adresse = 'http://toolserver.org/~luxo/cropbot/'+url
      //
      //MeinFenster = window.open(adresse, "editwindow", "width="+W+",height="+H+",toolbar=no,status=no,scrollbars=yes,menubar=no,location=no");
      //if(MeinFenster)
      //{
        //MeinFenster.focus();
        window.location.href = adresse;
      //}
     
    }
    
    var doit = "nein";
    function screenres()
    {
      var W = screen.width;
      var H = screen.height;
      

      if(window.outerWidth < W)
      {
        //Fenster positionieren
        self.moveTo(0,0);
        
        //Fenster maximieren
        window.outerHeight = H - 22;
        window.outerWidth = W;
      }
      
      //warnen falls auflösung zu klein ist
      if (W < 1024 && H < 768 && doit == "Ja")
      {
        $('screenW').firstChild.data = W;
        $('screenH').firstChild.data = H;
        $('screensmall').show();
      }
      else
      {
        if(doit == "Ja")
        {
          $('screensmall').hide();
        }
      }
    }
    </script>

  </head>
  <body>
    <div style="position:absolute;left:0px;top:20px;width:90%;border: 1px solid green;background-color:#b3ffb3;-moz-border-radius: 0px 10px 10px 0px;padding:20px;">
<?php
//Startfile der Oberfläche.
$img = $_GET['img'];

if($img)
{
  
  
  //URL etc aus API holen
  
  if(substr(strtolower($img),0,5) != "file:")
   { $img = "File:".$img; }  //name vervollständigen falls nötig
  
  $apiret = unserialize(file_get_contents("http://commons.wikimedia.org/w/api.php?action=query&titles=".urlencode($img)."&prop=imageinfo&iiprop=timestamp|user|comment|url|size|sha1|mime|metadata|archivename&format=php"));
  
  foreach($apiret['query']['pages'] as $x)
  {
    $pageid = $x['pageid'];
    $img    = $x['title'];
    $user   = $x['imageinfo'][0]['user'];
    $width  = $x['imageinfo'][0]['width'];
    $height = $x['imageinfo'][0]['height'];
    $url    = $x['imageinfo'][0]['url'];
    $mime   = $x['imageinfo'][0]['mime'];
    $intimg = time();
  }

  if(!$pageid)
  {
    die("image not found...");
  }

  
  if($mime != "image/jpeg")
  {
    die("sorry, I crop only jpeg's...");
  }
  
 
  
  //Bild speichern
  $savepath = "/home/luxo/public_html/cropbot/cache/";
  $imagename = $savepath.$intimg.".jpg";
  
  $file = file_get_contents($url) or die("Kann $img nicht downloaden!");
  
  $fp = fopen($imagename, "wb+");
  fwrite($fp, $file);
  fclose($fp); 
  //Datei gespeichert
  
  chmod ($imagename, 0644);
  
  //Breite sollte 700px nicht überschreiten für 1024x768
  if($width > 700)
  {
    //thumb mit w700 erstellen
    passthru("convert $imagename -resize 700 ".$savepath.$intimg."-2.jpg",$return);
    $thumbname = $intimg."-2.jpg";
    $xx = getimagesize($savepath.$intimg."-2.jpg");
    $thumbwidth  = $xx['0'];
    $thumbheight = $xx['1'];
    chmod ($savepath.$intimg."-2.jpg", 0644) or die("Internal error x150 - ".$intimg);
  }
  else
  {
    $thumbname = false; 
    $thumbwidth = false;
    $thumbheight = false;
  }

  $_SESSION['image'] = $img;
  $_SESSION['uploader'] = $user;
  $_SESSION['width'] = $width;
  $_SESSION['height'] = $height;
  $_SESSION['internname'] = $intimg;
  $_SESSION['thumbname'] = $thumbname;
  $_SESSION['thumbwidth'] = $thumbwidth;
  $_SESSION['thumbheight'] = $thumbheight;
  
  ?>
  Saved. Please wait or click <a href="javascript:gotourl('cropper.php');">next</a>
  
  <script language="javascript" type="text/javascript">
  gotourl('cropper.php');
  </script>
  <br>
  <!--If you enable Popups for <i>toolserver.org</i> you mustn't click next above.-->
  <?php
}
else
{
?>
<h1>Cropbot</h1>
<form method="get" action="cropbot.php" name="image">
File:<input size="40" name="img">
<input value="Crop" type="submit">
</form>
<br>JavaScript and cookies must been enabled.<br>
<div id="screensmall" style="display:hidden">
<h3>Warning</h3>
Your screen resolution is probably too low. You have <span id="screenW">800</span>x<span id="screenH">600</span>, recommended is 1024x768 or higher.
</div>
  <script language="javascript" type="text/javascript">
  doit = "Ja";
  </script>
<?php
}



?>
  </div>
      <div style="text-align: center;position:absolute;right:0px;bottom:20px;width:90%;border: 1px solid red;background-color:#fda2a2;-moz-border-radius: 10px 0px 0px 10px;padding:20px;">
     You are logged in as <?php echo htmlspecialchars($commonsname); ?>. <a href="login.php?action=logout">Logout</a><br>
     by <a href="http://commons.wikimedia.org/wiki/User:Luxo">Luxo</a> | <a href="http://commons.wikimedia.org/wiki/User_talk:Luxo">talk</a> | <a href="http://meta.wikimedia.org/wiki/User:Luxo/Licenses#Cropbot">license</a>
      </div>
      <script type="text/javascript">
      screenres();
      </script>
  </body>
</html>
