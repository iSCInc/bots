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

ini_set('memory_limit', '50M'); //Speicher auf 50 MBytes hochsetzen
include("logincheck.php");
include("edit.php");
session_start();
//    var url = 'upload.php?localimg=x&summary=x&overwrite=x&newname=x


$localimg  = $_GET['localimg'];
$summary   = trim($_GET['summary']);
$overwrite = $_GET['overwrite'];
$oldname   = trim($_GET['oldname']);
$newname   = trim($_GET['newname']);
$removetem = $_GET['removetemp'];

sleep(5);
if($overwrite == "true")
{
  $overwrite = true;
  $newname = $oldname;
  
}
else
{
  $overwrite = false;
  
}

if($localimg && $summary && $oldname)
{
  //hochladen
  
  //Bildbeschreibung generieren
  if($overwrite == true)
  {
    //einfache Bildbeschreibung
    $desc = "upload cropped version, operated by [[User:$commonsname]]. Summary: $summary";
  }
  else
  {
    //Bildbeschreibung vom Original kopieren
    $originaldesc = file_get_contents("http://commons.wikimedia.org/w/index.php?title=".urlencode($oldname)."&action=raw");
    
    //paar sachen anhängen
    $catpos = strpos($originaldesc, "[[Category:");
    if($catpos)
    {
      $pre = substr($originaldesc,0,$catpos);
      $after = substr($originaldesc,$catpos);
    }
    else //falls keine kats vorhanden sind
    {
      $pre = $originaldesc;
      $after = "";
    }
    $oldname = substr($oldname,5);
    $desc = $pre."\n{{Extracted from|".$oldname."}}\n{{RetouchedPicture|".str_replace("|","{{!}}",$summary)."|editor=".$commonsname."|orig=".$oldname."}}\n".$after;
    $desc = "<!-- Uploaded with Cropbot operated by [[User:".$commonsname."]]  -->\n".$desc;
  }
  //Bildbeschreibung generiert, hochladen
  $newname = substr($newname,5);
  wikiupload("commons.wikimedia.org",$localimg,$newname,"",$desc);
  
  if($overwrite == false)
  {
    //in DB von Bilderbot eintragen
    include("/home/luxo/public_html/contributions/logindata.php"); //DB-Logindaten
    $dblink = mysql_connect($databankname, $userloginname, $databasepw) or die(mysql_error());
    mysql_select_db("u_luxo", $dblink);
    mysql_query( "INSERT INTO derivativefx SET file='Image:".mysql_real_escape_string($oldname)."', derivative='".mysql_real_escape_string($newname)."', status='open', time='".mysql_real_escape_string(time())."', donetime='-'", $dblink) or die("Error");
    mysql_close($dblink);
  }
  
  if($removetem == "true" AND $overwrite == true)
  {
    //Template entfernen
    $origurl = "http://commons.wikimedia.org/w/index.php?title="."Image:".urlencode($newname)."&action=raw";
    $originaldesc = file_get_contents($origurl);
    $templates = array("{{Remove border}}", "{{RemoveBorder}}","{{Removeborder}}","{{Crop}}",
    "{{remove border}}", "{{removeBorder}}","{{removeborder}}","{{crop}}");
    $ret = array("","","","","","","",""); //mit nichts ersetzen
    
    $newdesc = str_replace($templates, $ret, $originaldesc, $counter);
    

    
    if($counter > 0)
    {
      //Template gefunden, nun entfernen
      wikiedit("commons.wikimedia.org","Image:".$newname,$newdesc,"Bot: remove template {{Remove border}}, image cropped by [[User:$commonsname|]]","1");
      
    }
    

    
     
  }
  
  
  echo"http://commons.wikimedia.org/w/index.php?action=purge&title=File:".urlencode($newname);
}
else
{
  echo "ERROR";
}


// ############### EDIT WIKIPEDIA - FUNCTION ###############
function wikiupload($project,$filename_local,$filename_wiki,$license,$desc)
{
  GLOBAL $cookies;
  include("logindata.php");
  
  /*
  ****************Upload with api************************
  1.) check login, or login
  2.) get token with prop=info (in)
  3.) Upload
  
  */
  if(!$cookies["commonswikiUserName"] OR !$cookies["commonswikiUserID"])
  {


    wikilogin($username,$password,$project,$useragent);

  } 
  
  if($cookies) {
 
  } else { 
 
  }
  
  //Angemeldet, Cookies formatieren**************
 
  foreach ($cookies as $key=>$value) 
  {
    $cookie .= trim($value).";";
  }
  $cookie = substr($cookie,0,-1);
  
  //get token
  $token = gettoken($project,$cookie);
  if($token)
  {

  } else {
 
    die();
  }
  
  //Upload
  wiki_upload_file ($filename_local,$filename_wiki,$license,$desc,$project,$cookie,$token);

  
}
  
  
  
  
  
  
  
  function gettoken($project,$cookie) {
    $url = "http://".$project."/w/api.php";
    $post = "action=query&prop=info&intoken=edit&titles=Foo&format=php";
                  
    $useragent = "Luxobot/1.1 (toolserver; php) luxo@ts.wikimedia.org";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $ry = curl_exec($ch);  
    $data = unserialize($ry);
    //print_r($ry);
    return $data['query']["pages"]["-1"]["edittoken"];
  
  }
  
  function wiki_upload_file ($filename_local,$filename_wiki,$license,$desc,$wiki,$cookies,$token)
{  
    $file1 = "";//Löschen wegen Speicherplatz
$file1 = file_get_contents("/home/luxo/public_html/cropbot/cache/".$filename_local) or suicide("Fehler - Datei nicht gefunden! ($filename_local)");

    
    $data_l = array(
    "action" => "upload",
    "file.file" => $file1,
    "filename" => $filename_wiki,
    "comment" => str_replace("\\'","'",$desc),
    "token" => $token,
    "ignorewarnings" => "1");
    $file1 = "";//Löschen wegen Speicherplatz
    wiki_PostToHostFD($wiki, "/w/api.php", $data_l, $wiki, $cookies);    
    
    $data_l = array();//Das auch löschen wegen Speicherplatz
    
}
function wiki_PostToHostFD ($host, $path, $data_l, $wiki, $cookies) //this function was developed by [[:de:User:APPER]] (Christian Thiele) http://toolserver.org/~apper/code.php?file=wetter/upload.inc.php
{
logfile("verbinde zu $host ...");
    $useragent = "Luxobot/1.2 (toolserver; php) luxo@ts.wikimedia.org";
    $dc = 0;
    $bo="-----------------------------305242850528394";
    $filename=$data_l['filename'];
    $fp = fsockopen($host, 80, $errno, $errstr, 30);
    if (!$fp) { echo "$errstr ($errno)<br />\n"; exit; }
    
    fputs($fp, "POST $path HTTP/1.0\r\n");
    fputs($fp, "Host: $host\r\n");
    fputs($fp, "Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, image/png, */*\r\n");
    fputs($fp, "Accept-Charset: iso-8859-1,*,utf-8\r\n"); 
    fputs($fp, "Cookie: ".$cookies."\r\n");
    fputs($fp, "User-Agent: ".$useragent."\r\n");
    fputs($fp, "Content-type: multipart/form-data; boundary=$bo\r\n");
    
    foreach($data_l as $key=>$val) 
    {
        // Hack for attachment
        if ($key == "file.file")
        {
            $ds =sprintf("--%s\r\nContent-Disposition: attachment; name=\"file\"; filename=\"%s\"\r\nContent-type: image/png\r\nContent-Transfer-Encoding: binary\r\n\r\n%s\r\n", $bo, $filename, $val);
        }
        else
        {
            $ds =sprintf("--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n", $bo, $key, $val);
        }      
        $dc += strlen($ds);
    }
    $dc += strlen($bo)+3;
    fputs($fp, "Content-length: $dc \n");
    fputs($fp, "\n");
    
    foreach($data_l as $key=>$val) 
    {
        if ($key == "file.file")
        {
            $ds =sprintf("--%s\r\nContent-Disposition: attachment; name=\"file\"; filename=\"%s\"\r\nContent-type: image/png\r\nContent-Transfer-Encoding: binary\r\n\r\n%s\r\n", $bo, $filename, $val);
            $data_1["file.file"] = "";//löschen
        }
        else
        {
            $ds =sprintf("--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n", $bo, $key, $val);
        }      
        fputs($fp, $ds );
    }
    $ds = "--".$bo."--\n";
    fputs($fp, $ds);
    
    $res = "";
    while(!feof($fp)) 
    {
        $res .= fread($fp, 1);
    }
    fclose($fp);
    file_put_contents("/home/luxo/rotbot/cache/log.txt",$res);
    return $res;
    $data_l = array();
}


?>
