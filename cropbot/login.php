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
$output = "";

$cSession  = $_COOKIE['session'];
$cUsername = $_COOKIE['username'];
$action = htmlspecialchars($_GET['action']);

if($action != "wrongbrowser")
{
  browercheck();
}

if(!$action AND !$cSession)
{
  redirect("login.php?action=login");
}
else if(!$action AND $cSession)
{
  redirect("cropbot.php");
}

//Datenbank abfragen & verbinden
include("/home/luxo/public_html/contributions/logindata.php"); //DB-Logindaten
$dblink = mysql_connect($databankname, $userloginname, $databasepw) or die(mysql_error());
mysql_select_db("u_luxo", $dblink);


if($cSession AND $cUsername)
{
  //Daten abfragen
  $resu1 = mysql_query( "SELECT * FROM user WHERE name='".mysql_real_escape_string($cUsername)."'", $dblink) or die(mysql_error());
  
  $a_row2 = mysql_fetch_row($resu1);
  
  $dbname      = $a_row2["0"];
  $dbpassword  = $a_row2["1"];
  $dbsessionid = $a_row2["2"];
  $confirmed   = $a_row2["3"];
  $commonsname = $a_row2["4"];
  
  if($cSession == $dbsessionid)
  {
    $loggedin = true;

  }
  else
  {
    $loggedin = false;
  }
}
else
{
  $loggedin = false;
}




if($action == "login")
{
  //Login-Formular
  
  $output .= '<h1>Login</h1>
  Don&#39;t have a account? <a target="_blank" href="http://toolserver.org/~magnus/tusc.php?language=commons&project=wikimedia">create account</a><br>
  <small><a href="/~luxo/gwatch/" target="_blank">gWatch</a> and TUSC accounts are compatible</small><br>
  <br>
  <form method="post" action="?action=clogin" name="login">
  Username<br>
  <input size="30" name="username"><br>
  <br>
  Password<br>
  <input size="30" name="password" type="password"><br>
  <br>
  <input value="Login" type="submit"></form><br>
  <br>
  <br>
  Learn more about the features of Cropbot <a href="http://commons.wikimedia.org/wiki/User:Cropbot">here</a>!';
  
}
else if($action == "clogin")
{
  //login verarbeiten
  $output .= "<h1>Login</h1>";
  if(!$_POST['username'] OR !$_POST['password'])
  {
    $output .= "Error. Please go <a href='?action=login'>back</a> and complete all forms.<br>";
  }
  else
  {
    //Daten empfangen, checken
    $pUser = strtolower($_POST['username']);
    $pPass = md5($_POST['password']);
    
    $resu1 = mysql_query( "SELECT * FROM user WHERE name='".mysql_real_escape_string($pUser)."'", $dblink) or die(mysql_error());
    
    $a_row2 = mysql_fetch_row($resu1);
    
    $dbname      = $a_row2["0"];
    $dbpassword  = $a_row2["1"];
    $dbsessionid = $a_row2["2"];
    $confirmed   = $a_row2["3"];
    $commonsname = $a_row2["4"];
    
    if($dbpassword != $pPass)
    {

      //TUSC abfragen
      //POST request!
      $username = str_replace("_"," ",$_POST['username']);
      $tuscurl = 'http://toolserver.org/~magnus/tusc.php';
      $postdata = 'check=1&botmode=1&user='.urlencode($username).'&language=commons&project=wikimedia&password='.urlencode($_POST['password']);
      $answer = do_post_request($tuscurl, $postdata);
      if(trim($answer) == "1") //valid
      {
      
        //Prüfen, ob Benutzername bereits existiert
        $resu1 = mysql_query( "SELECT * FROM user WHERE name='".mysql_real_escape_string(strtolower($pUser))."'", $dblink) or die(mysql_error());
        $anzerg = mysql_num_rows($resu1);
    
        $newuseris = true;
        if($anzerg > 0)
        {
          $newuseris = false;
          $output .= "A user with the name ".htmlspecialchars(strtolower($username))." already exist!<br>Please note that this tool use the same login like <a href='/~luxo/gwatch/'>gWatch</a>. If you have a account there, you can use the login datas from there for <a href='?action=login'>login</a>. If you don't have a account there, notify User:Luxo on his <a href='http://commons.wikimedia.org/wiki/User_talk:Luxo'>talkpage</a>.";
        }
        
        if($newuseris == true)
        {
          //User in DB eintragen
          $newsession = createsessionid();
          mysql_query("INSERT INTO user SET name='".mysql_real_escape_string(strtolower($_POST['username']))."',password='".mysql_real_escape_string(md5($_POST['password']))."',sessionid='".mysql_real_escape_string($newsession)."',confirmed='1',commonsname='".mysql_real_escape_string($_POST['username'])."';") or die(mysql_error());
          //Cookies
          setcookie("username",htmlspecialchars(strtolower($_POST['username'])), time() + 4320000, "/~luxo");
          setcookie("session",htmlspecialchars($newsession), time() + 4320000, "/~luxo");
          
          $output .= 'Wellcome, '.htmlspecialchars($_POST['username']).'!<br>
                        <br>
                        Now you can use the interface of Cropbot.<br>
                        <div style="margin-left: 40px;">→ <a href="cropbot.php">Cropbot</a><br>
                        </div>
                        <br>
                        This login is also valid for gWatch.<br>
                        <div style="margin-left: 40px;">→ <a href="/~luxo/gwatch/">gWatch</a></div>';
        }
      }
      else if(trim($answer) == "0") //not valid
      {
        $output .= "username or password not correct.<br> It seems that you don't have a TUSC account. You can create one <a href='http://toolserver.org/~magnus/tusc.php?language=commons&project=wikimedia&user=".htmlspecialchars($_POST['username'])."'>here</a>.<br><a href='?action=login'>back to login</a>";
      }
      else
      {
        $output .= "<h2>Internal Error.</h2>Wrong answer from the TUSC system, please notify User:Luxo on his <a href='http://commons.wikimedia.org/wiki/User_talk:Luxo'>talkpage</a>. <br><small>Error: ".htmlspecialchars(trim($answer))." - ".gettype($answer)."</small>";      
      }
    }
    else
    {
      //Alles ok, user bereits registriert, neue session generieren
      $newsession = createsessionid();
      mysql_query( "UPDATE user SET sessionid='".mysql_real_escape_string($newsession)."' WHERE name='".mysql_real_escape_string($dbname)."';", $dblink) or die(mysql_error());
      
      //Cookies
      setcookie("username",htmlspecialchars($dbname), time() + 4320000, "/~luxo");
      setcookie("session",htmlspecialchars($newsession), time() + 4320000, "/~luxo");
      
      $output .= "<b>login successful!</b><br>> <a href='cropbot.php'>Next</a><br><br>";
    }
  }
}
else if($action == "register")
{
  //Register-Formular
  //
  if($loggedin == true)
  {
    if($confirmed != 0)
    {
      redirect("cropbot.php"); //bereits confirmed
    }
    
    $c = ' disabled="disabled" readonly="readonly"'; 
    $h = "<br><br><b style='color:red'>You are already logged in, but your account is not confirmed. Please follow the steps below.</b>";
    
  }
  else
  {
    $c = "";
    $h = "";
  }
  
  $confirmstring = createsessionid();
  $output .= '<form method="post" action="?action=cregister"
   name="login">
    <input type="hidden" value="'.$confirmstring.'" name="cs">
    <br>'.$h.'
    Wikimedia:Commons Username<br>
    User:<input size="30" name="username"><br>
    <br>
    <hr>For using Cropbot you must confirm that you are this user above.<br>
    <input name="tusc" value="true" type="radio" checked="checked" id="tusccheck" onclick="hideshow(\'tusccheck\',\'normalconfirm\',\'tuscconfirm\')"> Use <a href="/~magnus/tusc.php" target="_blank">TUSC</a> for confirmation<br>
    <input name="tusc" value="false" type="radio" onclick="hideshow(\'tusccheck\',\'normalconfirm\',\'tuscconfirm\')"> Use normal confirmation<br>
    <br>
  <span id="normalconfirm" style="display:none">
     <span style="font-weight: bold;">Please do the following:</span><br>
  1. go to your user talk page (don&#39;t close this window, otherwise the checksum change!)<br>
  2. make a edit and paste the following in the summary: &nbsp;<span
   style="font-weight: bold;">'.md5($confirmstring).'<br>
    </span>3. save it<br>
  4. controll that you see the edit in the history. If not, you made a
    <a
   href="http://en.wikipedia.org/wiki/Wikipedia:Null_edit#Null_edit"
   target="_blank">null edit</a>. Edit one more and
  delete e.g. a space, then it should work.<br>
  5. klick ok below.<br></span>
    <span id="tuscconfirm" style="display:block">
    You must have a TUSC account. If you dont have one, you can create one <a href="/~magnus/tusc.php" target="_blank">here</a>.<br>
    TUSC password: <input type="password" name="tuscpassword"><br>
    </span>
  <input value="Ok" type="submit"></form>';
  
}
else if($action == "cregister")
{
  //registrierung verarbeiten & account überprügen
  //zuerst passwort überprüfen
  if($loggedin == false)
  {
    $pwio = true;
    if(!$_POST['password'] OR !$_POST['password2'])
    {
      $output .= "Please complete all forms.<br><a href='javascript:history.back()'>back</a><br>";
      $pwio = false;
    }
    if($_POST['password']  != $_POST['password2'])
    {
      $output .= "Passwords not identical!<br><a href='javascript:history.back()'>back</a><br>";
      $pwio = false;
    }
    if(strlen($_POST['password']) < 5)
    {
      $output .= "Password to short!<br><a href='javascript:history.back()'>back</a><br>";
      $pwio = false;
    }
  }
  else
  {
    $pwio = true;
  }
  if($pwio == true)
  {
    //passwort i.o., nun name überprüfen
    if($_POST['tusc'] != "true")
    {
      //normales system
      $username = str_replace("_"," ",$_POST['username']);
      $url = "http://commons.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User%20talk:".urlencode($username)."&rvprop=timestamp|user|comment&rvlimit=50&format=php";
      
      $blankarray = unserialize(file_get_contents($url));
      $confirmed = false;
      foreach($blankarray['query']['pages'] as $y)
      {
        foreach($y['revisions'] as $c)
        {
          if(strstr($c['comment'],md5($_POST['cs'])))
          {
            if($c['user'] == $username)
            {
              
              $confirmed = true;
            }
          }
        }
      }
    }
    else
    {
      //tusc system
      //POST request!
      $username = str_replace("_"," ",$_POST['username']);
      $tuscurl = 'http://toolserver.org/~magnus/tusc.php';
      $postdata = 'check=1&botmode=1&user='.urlencode($username).'&language=commons&project=wikimedia&password='.urlencode($_POST['tuscpassword']);
      $answer = do_post_request($tuscurl, $postdata);
      
            
      if($answer == "1")
      {
        $confirmed = true;
      }
      else if($answer == "0")
      {
        $confirmed = false;
      }
      else
      {
        $confirmed = false;
      }
    }
    
    if($confirmed == false)
    {
      if($_POST['tusc'] != "true")
      {
        $output .= "revision with this checksum not found. <br><a href='javascript:history.back()'>back</a>";
      }
      else
      {
        $output .= "This TUSC user was not found. <br><a href='javascript:history.back()'>back</a>";
      }
    }
    else
    {
      //confirmed OK
      if($loggedin == true)
      {
        //update
        mysql_query( "UPDATE user SET confirmed='1', commonsname='".mysql_real_escape_string($username)."' WHERE name='".mysql_real_escape_string($dbname)."';", $dblink) or die(mysql_error());
        $output .= "Thanks, ".htmlspecialchars($username).", your account is now confirmed.<br><a href='cropbot.php'>next</a>";
      }
      else
      {
        //Prüfen, ob Benutzername bereits existiert
        $resu1 = mysql_query( "SELECT * FROM user WHERE name='".mysql_real_escape_string(strtolower($username))."'", $dblink) or die(mysql_error());
        $anzerg = mysql_num_rows($resu1);
    
        $newuseris = true;
        if($anzerg > 0)
        {
        $newuseris = false;
        $output .= "A user with the name ".htmlspecialchars(strtolower($username))." already exist!<br>Please note that this tool use the same login like <a href='/~luxo/gwatch/'>gWatch</a>. If you have a account there, you can use the login datas from there for <a href='?action=login'>login</a>. If you don't have a account there, notify me on my <a href='http://commons.wikimedia.org/wiki/User_talk:Luxo'>talkpage</a>.";
        }
      
        if($newuseris == true)
        {
          //neu eintragen
          $newsession = createsessionid();
          mysql_query("INSERT INTO user SET name='".mysql_real_escape_string(strtolower($username))."',password='".mysql_real_escape_string(md5($_POST['password']))."',sessionid='".mysql_real_escape_string($newsession)."',confirmed='1',commonsname='".mysql_real_escape_string($username)."';") or die(mysql_error());
          
          setcookie("username",htmlspecialchars(strtolower($username)), time() + 4320000, "/~luxo");
          setcookie("session",htmlspecialchars($newsession), time() + 4320000, "/~luxo");
          
          $output .= 'Wellcome, '.htmlspecialchars($username).'!<br>
                      <br>
                      Now you can use the interface of Cropbot.<br>
                      <div style="margin-left: 40px;">→ <a href="cropbot.php">Cropbot</a><br>
                      </div>
                      <br>
                      This login is also valid for gWatch.<br>
                      <div style="margin-left: 40px;">→ <a href="/~luxo/gwatch/">gWatch</a></div>';
        }
      }
    }
  }
}
else if($action == "logout")
{
  //logout
      $newsession = createsessionid();
      mysql_query( "UPDATE user SET sessionid='".mysql_real_escape_string($newsession)."' WHERE name='".mysql_real_escape_string($dbname)."';", $dblink) or die(mysql_error());
      
      //Cookies
      setcookie("username",htmlspecialchars($dbname), time() -10, "/~luxo");
      setcookie("session",htmlspecialchars('111'), time() -10, "/~luxo");
      
      $output .= "<b>logout successful!</b><br>> <a href='?action=login'>Login</a><br><br>";
}

else if($action == "wrongbrowser")
{
  $output .= "Sorry, Cropbot does only work with<br>
<ul>
  <li>Firefox</li>
  <li>Netscape Navigator</li>
  <li>Safari</li>
  <li>Opera</li>
</ul>
Internet Explorer is not supported at the moment.";
}


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="author" content="Luxo">
  <title>Cropbot</title>
  <script type="text/javascript">
function hideshow(idcheck,iddoit1,iddoit2)
{

var element1 = document.getElementById(idcheck);
var element2 = document.getElementById(iddoit1);
var element3 = document.getElementById(iddoit2);

  if(element1.checked == true)
  {
    element2.style.display = "none";
    element3.style.display = "block";
  }
  else
  {
    element2.style.display = "block";
    element3.style.display = "none";
  }
}
  </script>
  </head>
  <body>
  <div style="position:absolute;left:0px;top:20px;width:90%;bottom:20px;border: 1px solid green;background-color:#b3ffb3;-moz-border-radius: 0px 10px 10px 0px;padding:10px;">
  <?php
  echo $output;
  ?>
</div>
  </body>
</html>
<?php


function redirect($path)
{
  $url = "http://".$_SERVER["SERVER_NAME"]."/~luxo/cropbot/".$path;
  header("Location: $url");
  die();
}

function createsessionid() //Create random session-id
{
$rand1 = rand(1000,9999);
$rand2 = rand(1000,9999);

return(md5($rand2.$rand1));
}

function browercheck()
{
  //Browsercheck
  $wrongbrowser = false;
  if(strstr($_SERVER["HTTP_USER_AGENT"],"Opera"))
  {
    //$wrongbrowser = true;
  }
  
  if(strstr($_SERVER["HTTP_USER_AGENT"],"MSIE"))
  {
    $wrongbrowser = true;
  }
  
  if($wrongbrowser == true)
  {
      redirect("login.php?action=wrongbrowser");
  }
  
}



function do_post_request($url, $fields_string)
  {
     //open connection  
     $ch = curl_init();  
      
     //set the url, number of POST vars, POST data  
     curl_setopt($ch,CURLOPT_URL,$url);  
     curl_setopt($ch,CURLOPT_POST,6);  
     curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
     curl_setopt($ch, CURLOPT_REFERER  , "http://toolserver.org/~luxo/cropbot/login.php");
     
     //curl_setopt($ch, CURLOPT_HEADER  ,1);
     //execute post  
     //$result = file_get_contents("http://toolserver.org/~magnus/tusc.php?check=1&botmode=1&user=Luxo&language=commons&project=wikimedia&password=ddydc");
     $result = curl_exec($ch); 

     //close connection  
     curl_close($ch);  
     
     return substr($result, -1); //wegen BOM
     
     //file_put_contents("test.txt",$result);
  }

?>
