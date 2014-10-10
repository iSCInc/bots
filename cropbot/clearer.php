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
//Aufräumen des caches

//cache-speicher auflisten
clearer();

function clearer()
{
    $delete = array();
    $curl = "/home/luxo/public_html/cropbot/cache/";
    $dh = opendir($curl);
    while(!is_bool($file = readdir($dh)))
    {
      if(substr($file, -4) == ".jpg")
      {
      
        //zeitstempel ermitteln
        //1216997254
        $ts = substr($file,0,10);
        $nowts = time();
        
        $difftime = $nowts - $ts;
        
        
        if($difftime > 3600)
        {
          $delete[] = $file;
        
          if(is_file($curl."/".$ts."-2.jpg"))
          {
            $delete[] = $ts."-2.jpg";
          }
          if(is_file($curl."/".$ts."-3.jpg"))
          {
            $delete[] = $ts."-3.jpg";
          }
          if(is_file($curl."/".$ts."-4.jpg"))
          {
            $delete[] = $ts."-4.jpg";
          }
        }
        
      }
    }

    foreach($delete as $delfile)
    {
      if(is_file($curl.$delfile))
      {
        unlink($curl.$delfile);
      }
    }
    
    closedir($dh);
    

}

?>
