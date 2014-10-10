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
include("logincheck.php");
session_start();
/* echo $_SESSION['uploader'];
 echo  $_SESSION['width'];
 echo  $_SESSION['height'];
 echo  $_SESSION['internname'];
 echo  $_SESSION['thumbname'];
 echo  $_SESSION['thumbwidth'];
 echo  $_SESSION['thumbheight'];*/

if(!$_SESSION['uploader'] OR !$_SESSION['internname'])
{
  redirect("cropbot.php");
  //die("no image received");
}

if($_SESSION['thumbname'] != false)
{
 $picturl = "cache/".htmlspecialchars($_SESSION['thumbname']);
 $w = htmlspecialchars($_SESSION['thumbwidth']);
 $h = htmlspecialchars($_SESSION['thumbheight']);
}
else
{
 $picturl = "cache/".htmlspecialchars($_SESSION['internname'].".jpg");
 $w = htmlspecialchars($_SESSION['width']);
 $h = htmlspecialchars($_SESSION['height']);
}

 $Tw = htmlspecialchars($_SESSION['width']);
 $Th = htmlspecialchars($_SESSION['height']);
 
 //Image:Beispiel.jpg --> Image:Beispiel (cropped).jpg
$posX = strrpos($_SESSION['image'], ".");
$imgX = substr($_SESSION['image'], 0, $posX);
$extX = substr($_SESSION['image'],$posX);
$defnewname = $imgX." (cropped)".$extX;
 
 
//Reupload Block for Bundesarchiv images
$blockreupload = 0;
$blockreuploadreason = "Reupload allowed";

if(stristr($_SESSION['image'],"Bundesarchiv") != false)
{
  //$blockreupload = 1;
  //$blockreuploadreason = "Overwrite of images from the German Federal Archive ist not alowed. Please upload under a different name.";
}


 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="author" content="Luxo">
  <title>Cropbot</title>
  <link rel="stylesheet" href="/~luxo/cropbot/cropper/cropper.css" type="text/css" media="screen" />
<script type="text/javascript" src="cropper/lib/prototype.js" language="javascript"></script>
<script type="text/javascript" src="cropper/lib/scriptaculous.js" language="javascript"></script>
<script type="text/javascript" src="cropper/cropper.js" language="javascript"></script>

  <script type="text/javascript">
  var firstX  = 0;
  var firstY  = 0;
  var secondX = 0;
  var secondY = 0;
  var H = 0;
  var W = 0;
  var pictH = <?php echo $h; ?>;
  var pictW = <?php echo $w; ?>;
  var picturl = "<?php echo $picturl; ?>";
  var internname = "<?php echo $_SESSION['internname']; ?>";
  var oldname = "<?php echo htmlspecialchars($_SESSION['image']); ?>";
  var truesizeH = <?php echo $Th; ?>;
  var truesizeW = <?php echo $Tw; ?>;
  var defsummary = "cropped";
  var defnewname = "<?php echo htmlspecialchars($defnewname); ?>";
  var blockreupload = <?php echo $blockreupload; ?>;
  var blockreuploadreason = "<?php echo $blockreuploadreason; ?>";
  





var CropImageManager = {

		curCrop: null,

		init: function() {
			this.attachCropper();
		},
		
		//erstellen, wenn nicht bereits da
		attachCropper: function() {
			if( this.curCrop == null ) 
      {
        this.curCrop = new Cropper.Img( 'cimg', { onEndCrop: onEndCrop } );
      }
			else
      {
        this.curCrop.reset();
      }
		},
		
		//ratio
		attachCropperRatio: function(ratioX,ratioY) {
		  if( this.curCrop == null ) {
        this.curCrop = new Cropper.Img( 'cimg', { ratioDim: { x: ratioX, y: ratioY }, onEndCrop: onEndCrop } );
      }
			else { this.curCrop.reset(); }
		},
		
		//entfernen
		removeCropper: function() {
			if( this.curCrop != null ) {
				this.curCrop.remove();
				this.curCrop = null;
			}
		},
		
		//zurücksetzen
		resetCropper: function() {
			this.attachCropper();
		}
}



//Event.observe( window, 'load', function() { CropImageManager.init(); } ); //alternativ in onstartit();

	

function onstartit()
{
  //$("cimg").src = picturl;
  $('fsummary').value = defsummary;
  CropImageManager.init();
}
window.onload = onstartit;


function ratio(ratioX,ratioY)
{
  //window.alert("sorry, this feature is still in developing.")
  CropImageManager.removeCropper();
  if(ratioX == 0) {
  CropImageManager.attachCropper(); 
  } else {
  CropImageManager.attachCropperRatio(ratioX,ratioY);
  }
}


function onEndCrop( coords, dimensions ) {
	firstX = coords.x1;
	firstY = coords.y1;
	secondX = coords.x2;
	secondY = coords.y2;
	W = dimensions.width;
	H = dimensions.height;
	//window.alert("WxH:"+W+"x"+H+"  XxY:"+firstX+"x"+firstY);
	updateanz(W, H);

}


function updateanz(W, H)
{
var truew = Math.round(truesizeW / pictW * W);
var trueh = Math.round(truesizeH / pictH * H);
  $("size").firstChild.data = truew+"x"+trueh;
}




function checkall() //Eingaben prüfen
{
  var iserror = false;
  
  $('blockloader').show();
  
  $('errselect').hide();
  $('errsummar').hide();
  $('errnewfil').hide();
  
  //Bereich ausgewählt?
  if(H == 0 || W == 0 )
  {
    iserror = true;
    $('errselect').show();
  }
  
  //Summary da?
  if($('fsummary').value == "") 
  {
    iserror = true;
    $('errsummar').show();
  }
  
  //Neuer Dateiname vorhanden?
  if($('foverwrite').checked == false && $('fnewname').value == "File:")
  {
    iserror = true;
    $('errnewfil').show();
  }
  
  //Überladen von Datei erlaubt?
  if($('foverwrite').checked == true && blockreupload == 1)
  {
    iserror = true;
    $('errnewfil').show();
    window.alert(blockreuploadreason);
  }
  
  if($('newnamecheck').value == "false" && $('foverwrite').checked == false)
  {
    iserror = true;
    $('erralexis').show();
  }
  
  if(iserror == false)
  {
    //alles ok. schneide bild via ajax, rückgabewert: URL des geschnittenen Bild
    Effect.SlideUp('menubar', { scaleX: true, scaleY: false, duration: 3.0, scaleContent:false });
    Effect.SlideUp('image', { scaleX: true, scaleY: false, duration: 3.0, scaleContent:false });
    ajaxcrop();
  }
  else
  {
    $('mb3').show();
    $('blockloader').hide();
  }
}


function defaultname(idcheck)
{
  if($(idcheck).checked == false)
  {
    $('fnewname').value = defnewname;
    checkimg(defnewname);
  }
}

function hideshow(idcheck,iddoit,iddoit2)
{
  if($(idcheck).checked == true)
  {
    $(iddoit).hide();
    $(iddoit2).show();
  }
  else
  {
    $(iddoit).show();
    $(iddoit2).hide();
  }
}


function ajaxcrop()
{
//Funktion übergibt daten via ajax, bild wird geschnitten, rückgabewert: url
// jpegtran -crop 156x353+68+73 img.jpg > img2.jpg

//hochrechnen auf wirkliche grösse
var truew = Math.round(truesizeW / pictW * W);
var trueh = Math.round(truesizeH / pictH * H);

var trueX = Math.round(truesizeH / pictH * firstX);
var trueY = Math.round(truesizeH / pictH * firstY);

if($("cropsyst").checked == false)
{
  var cropmode = "IM";//use imagemagick
}
else
{
  var cropmode = "JT"; //use jpegtran
}
  var url = 'crop.php?H='+trueh+'&W='+truew+'&X='+trueX+'&Y='+trueY+"&cropmode="+cropmode;

  
  new Ajax.Request(url, {
    method: 'get',
    onSuccess: function(transport) {
      var returnurl = transport.responseText;
      if(returnurl == "ERROR")
      {
        window.alert("Whoops there has a internal error occurred. please restart."); 
      }
      else
      {
        $('blockloader').hide();
        //window.alert(returnurl);
        Effect.SlideDown('newfile', { duration: 3.0 });
        $('newfileok').show();
        $('newpicture').src = returnurl;
      }
    }
  });
}


function upload()
{
  $('blockloader').show();
  $('newfile').hide();
  $('newfileok').hide();
    
    if($('foverwrite').checked == true)
    {
      var tover = "true";
    }
    else
    {
      var tover = "false";   
    }
    
    if($('removetemp').checked == true)
    {
      var removetemp = "true";
    }
    else
    {
      var removetemp = "false";   
    }
    
    var url = 'upload.php?localimg='+encodeURIComponent(internname+"-3.jpg")+'&summary='+encodeURIComponent($('fsummary').value)+'&overwrite='+encodeURIComponent(tover)+'&newname='+encodeURIComponent($('fnewname').value)+'&oldname='+oldname+'&removetemp='+encodeURIComponent(removetemp);
    
    new Ajax.Request(url, {
    method: 'get',
    onSuccess: function(transport) {
      var returnurl = transport.responseText;
      if(returnurl == "ERROR")
      {
        window.alert("Whoops there has a internal error occurred. please restart."); 
      }
      else
      {
        $('blockloader').hide();
        window.alert("image cropped. Thanks for using Cropbot.");
        document.location.href=returnurl; 
        //redirect to image
      }
    }
  });
}


function stopupload()
{
  $('blockloader').show();
  Effect.SlideUp('newfile', { duration: 3.0 });
  $('newfileok').hide();
  location.reload();
}

var imgcache = "";

function checkimg(image)
{
  var checkercI = image.substr(0,6);
  if(checkercI.toLowerCase() == "image:")
  {
    $("fnewname").value = "File:" + image.substr(6);
  }

  if(image.match(/(.*)\.(png|gif|jpg|jpeg|xcf|pdf|mid|sxw|sxi|sxc|sxd|ogg|svg|djvu)/gi))
  {
    if(imgcache != image)
    {
        $('blockloader').show();
        var checkerc = image.substr(0,5);
        if(checkerc.toLowerCase() != "file:")
        {
          var inam = "File:"+image;
          image = inam;
          $("fnewname").value = inam;
        }
        var myAjax = new Ajax.Request(
        "checkexist.php?image="+image,
        { method: 'get', onComplete: abbr }
        );
        imgcache = image;
    }
  }
}

  function abbr(originalRequest)
  {
    $('blockloader').hide();
    if(originalRequest.responseText == "FALSE")
    {

      window.alert("Image name already exist. Please choose a different name.");
      $('newnamecheck').value = "false";
      new Effect.Highlight('fnewname', { startcolor: '#cc0000', endcolor: '#ffffff' });

    }
    else
    {
      $('newnamecheck').value = "true";
      new Effect.Highlight('fnewname', { startcolor: '#33cc00', endcolor: '#ffffff' });
    }
  }
  
  
var smallview = true;  
function resizeimage()
{
  if(smallview == true)
  {
    $('newpicture').src = "cache/"+internname+"-3.jpg";
    smallview = false;
    window.status = "You see the original size";
  }
  else
  {
    $('newpicture').src = "cache/"+internname+"-4.jpg";
    smallview = true;
    window.status = "You see a scaled version";
  }

}
  
</script>
    </head>
<body style="font-family:Arial;"><!-- width:650px;height:500px; -->
  <div style="padding:10px;position:absolute;left:0px;top:0px;border: 1px solid green;background-color:#b3ffb3;-moz-border-radius: 0px 10px 10px 0px;" id="image">
        <img src="<?php echo $picturl; ?>" name="picture" id="cimg">
     </div>
  <br>
  <br>
  <!--http://commons.wikimedia.org/w/index.php?title=Image:Jurgis_Kairys_Su-26_G%C3%B3raszka_2008_1.JPG-->
  <!--<a href="javascript:alertit();" style="position:absolute;top:100px">alert</a> onmousemove="zeichneeck(this)" -->
  <div style="position:absolute;right:0px;top:0px;width:300px;" id="menubar">
    <div id="mb1" style="width:300px;right:0px;border:1px solid red;background-color:#ffcccc;-moz-border-radius: 10px 0px 0px 10px;padding:10px">
      <b>Cropbot</b>
      <br> trim your images
      <hr>
      <b>selected size:</b>
      <span id="size">
        0x0
      </span>
      <br><br>
      <b>ratio:</b><br>
      <select name="ratio">
        <option selected="selected" value="a000" OnClick="ratio('0','0');">free</option>
        <?php
        $ratios = array(array("1","1"),
                        array("2","1"),
                        array("3","2"),
                        array("4","3"),
                        array("5","3"),
                        array("5","4"),
                        array("7","5"),
                        array("9","7"),
                        array("12","10"),
                        array("16","9"),
                        array("16","10")
                        );
        foreach($ratios as $keyx => $ratio)
        {
          echo "<option value=\"a$keyx\" OnClick=\"ratio('".$ratio[0]."','".$ratio[1]."');\">".$ratio[0].":".$ratio[1]." (".round(($ratio[0] / $ratio[1]), 2).")</option>\n";
          if($ratio[0] != $ratio[1])
          {
            //umkehren
            echo "<option value=\"b$keyx\" OnClick=\"ratio('".$ratio[1]."','".$ratio[0]."');\">".$ratio[1].":".$ratio[0]." (".round(($ratio[1] / $ratio[0]), 2).")</option>\n";
          }
        }
        ?>
      </select><br>     
      <!--<input type="radio" name="ratio" value="fr" checked="checked" OnClick="ratio('0','0');"> free<br>
      <input type="radio" name="ratio" value="fourtothree" OnClick="ratio('4','3');"> 4:3 &nbsp;&nbsp;&nbsp;
      <input type="radio" name="ratio" value="threetofour" OnClick="ratio('3','4');"> 3:4<br>
      <input type="radio" name="ratio" value="sixthentonine" OnClick="ratio('16','9');"> 16:9&nbsp;&nbsp;
      <input type="radio" name="ratio" value="ninetosixthen" OnClick="ratio('9','16');"> 9:16<br>-->
      <br>
    </div>
    <br>
    <div id="mb2" style="width:300px;right:0px;border:1px solid blue;background-color:#b3b3ff;-moz-border-radius: 10px 0px 0px 10px;padding:10px">
      Edit summary:
      <br>
      <input type="text" id="fsummary" size="40" value="">
      <br>
      <br>
      <input onClick="hideshow(this.id,'fnewfile','remtemp');defaultname(this.id)" id="foverwrite" type="checkbox" value="true" checked="checked">
      overwrite actual file
      <br>
      
      <div style="margin-left: 10px;display:none;" id="fnewfile"><br>
        New file name:
        <br>
        <input tpye="text" id="fnewname" size="40" value="File:" onKeyUp="checkimg(this.value);" onChange="checkimg(this.value);">
        <br><input type="hidden" value="false" id="newnamecheck">
        <small>The original description will be taken for the new file. A note to this cropped version will be set into the original file.</small>
        <br><br>
      </div>
      <span id="remtemp"><input type="checkbox" id="removetemp" value="true" checked="checked"> <small>Remove <i>Template:Remove border</i> (if found)</small><br><br></span>
      <input type="radio" name="cropsys" value="exactly" checked="checked"> Crop exactly<br>
      <input type="radio" name="cropsys" value="lossless" id="cropsyst"> Crop lossless<br>
      <input value="Ok - crop this file" type="button" onclick="checkall();">
    </div>
    <br>
    <div id="mb3" style="width:300px;height:100px;display:none;right:0px;border:1px solid orange;background-color:#f7c29b;-moz-border-radius: 10px 0px 0px 10px;padding:10px"> Error:
      <ol>
        <li style="display:none;" id="errselect">
          Please select a part to crop</li>
        <li style="display:none;" id="errsummar">
          Please add a summary</li>
        <li style="display:none;" id="errnewfil">
          Please add a new file name</li>
        <li style="display:none;" id="erralexis">
          new file name already exist</li>
      </ol>
    </div>
  </div>
  <div id="newfile" style="overflow:scroll;max-height:90%;max-width:710px;display:none;position:absolute;bottom:0px;border: 1px solid green;background-color:#b3ffb3;left:120px;-moz-border-radius: 10px 10px 0px 0px;padding:10px">

    <img src="load.gif" id="newpicture" style="cursor:url(lupe.png);" onClick="">

  </div>
  
  <div id="newfileok" style="display:none;position:absolute;left:0px;top:0px;width:90px;height:140px;border:1px solid red;background-color:#ffcccc;-moz-border-radius: 0px 10px 10px 0px;padding:10px;">
  Upload?<br>
  <br>
  <input value="YES" type="button" onclick="upload();"><br>
  <input value="NO" type="button" onclick="stopupload();"><br>
  </div>
  <!-- Blockloader muss zu unterst sein! -->
  <div id="blockloader" style="display:none;position:absolute;top:0px;left:0px;height:100%;width:100%;background-image:url(grey.png);text-align: center;vertical-align:middle;">
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <img src="load.gif"><h1>Please wait...</h1> DON'T close this window!
  </div>
</body>
</html>
