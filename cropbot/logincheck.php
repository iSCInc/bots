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
$commonsname = logincheck();

function logincheck()
{
  browercheck();
  
  $cSession  = $_COOKIE['session'];
  $cUsername = $_COOKIE['username'];
  
  //Datenbank abfragen & verbinden
  include("/home/luxo/public_html/contributions/logindata.php"); //DB-Logindaten
  $dblink = @mysql_connect($databankname, $userloginname, $databasepw) or die("<img src='http://upload.wikimedia.org/wikipedia/commons/thumb/d/dd/Achtung.svg/50px-Achtung.svg.png'> Can't connect the database. ".mysql_error());
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
      //login ok
      
      if($confirmed != 1)
      {
        redirect("login.php?action=register");
      }
    }
    else
    {
      redirect("login.php?action=login");
    }
  }
  else
  {
    redirect("login.php?action=login");
  }
  mysql_close($dblink);
  

  
  
  return $commonsname;
}

function redirect($path)
{
  $url = "http://".$_SERVER["SERVER_NAME"]."/~luxo/cropbot/".$path;
  header("Location: $url");
  die();
}

function browercheck()
{
  //Browsercheck
  $wrongbrowser = false;
  /*if(strstr($_SERVER["HTTP_USER_AGENT"],"Opera"))
  {
    //$wrongbrowser = true;
  }*/
  
  if(strstr($_SERVER["HTTP_USER_AGENT"],"MSIE"))
  {
    $wrongbrowser = true;
  }
  
  if($wrongbrowser == true)
  {
      redirect("login.php?action=wrongbrowser");
  }
  
}
?>
