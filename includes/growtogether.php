<?php

/* 
     Copyright (C) 2007 Gilles Dubuc, Mukunda Modell.
 
     This file is part of Grow Together.

    Grow Together is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    Grow Together is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Grow Together.  If not, see <http://www.gnu.org/licenses/>.
*/

class growtogether {

    public static $apiurl="http://grow.darumazone.com/serve2.php";
    // Enter your growth ID below
    public static $growthid="46c03b0d343a789e073f1aba3a6fa6812925817b"; 

    /*
      generate a 1-line DIV version for including in your canvas page header or footer
        the $type parameter can be one of the following:
        - "full" shows the app icon, name and the full ad text
        - "short" shows just the app icon and name without the long ad text
      the returned html is given a class of growtogether_full or growtogether_short
      depending on the value of the type parameter.
      You can use CSS styles to change the layout or modify 
      the code below to suit your needs.
    */
    function getTextAds($type="full", $count=1) {
        $ads = self::getAdsJSON($count);

        $result = "<div class='growtogether_$type'><div class='ad $type'><b>More cool apps -></b></div>";
        if (isset($ads->result) && $ads->result == 0 && !empty($ads->apps)) {
            while (    $app = array_pop($ads->apps) ) {
                $result .= "<div class='ad $type'><a href='".$app->link
                        ."'><img src='".$app->icon."'/> ".$app->name."</a>";
                if ($type == "full")
                    $result .= " ". $app->text;
                $result .= "</div>";
            }
        }
        $result .= "</div>";
        return $result;
    }
    
    /* Generate a COOL APPS table with the specified number of rows, defaulting to 5 */
    function getCoolApps($count=5) {
        try {
            $ads = self::getAdsJSON($count);

            //for debugging output:
            //print_r($ads);
            
            $result = "<table class='coolapps'> <tr><th></th><th>App name</th>"
                    ."<th>Description</th></tr>";
            if (isset($ads->result) && $ads->result == 0 && !empty($ads->apps)) {
                foreach ($ads->apps as $app) {
                    $result .= "<tr><td><a href='".$app->link."'><img src='".$app->icon
                                ."'/></a></td>";
                    $result .= "<td><a href='".$app->link."'>".$app->name."</a></td>";
                    $result .= "<td>".$app->text."</td></tr>";
                }
            }
            $result .= "</table>";
            return $result;
        } catch(Exception $err) {
            error_log("growtogether::getCoolApps - Error: " . $err);
        }
    }
    
    /* this simply grabs the JSON and decodes it into an object using PHP's built-in 
       JSON support. Your PHP interpreter must be compiled with JSON support enabled.
    */
    public static function getAdsJSON($count=1) {
        $growthid = self::$growthid;
        $url = self::$apiurl . "?format=json&growthid=$growthid&quantity=$count";
        $json = file_get_contents($url);
        $obj = json_decode($json);
        return $obj->growth;
    }
    
    /* for backwards compatibility */
    public static function getAds($count=1) {
        return self::getCoolApps($count);
    }
    
    
    /* this is the old XML version of getCoolApps */
    public static function getAdsXML($count=1) {
        $growthid = self::$growthid;
        $url = self::$apiurl . "?format=xml&growthid=$growthid&quantity=$count";
        $xmlresult = file_get_contents($url);
        $xml = new SimpleXMLElement($xmlresult);
        
        $result = "<table class='coolapps'> <tr><th></th><th>App name</th>"
                ."<th>Description</th></tr>";
        if (isset($xml->result) && $xml->result == 0 && !empty($xml->app)) {
            foreach ($xml->app as $app) {
                $result .= "<tr><td><a href='".$app->link."'><img src='"
                        .$app->icon."'/></a></td>";
                $result .= "<td><a href='".$app->link."'>".$app->name."</a></td>";
                $result .= "<td>".$app->text."</td></tr>";
            }
        }
        $result .= "</table>";
        return $result;
    }
}

?>