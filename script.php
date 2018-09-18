<?php
/**
 * Created by PhpStorm.
 * User: kolya
 * Date: 9/17/2018
 * Time: 12:43 PM
 */

# run once in 30 days via crontab(?)

header('Content-Type: text/html; charset=utf-8');
$host = "localhost";
$user = "root";
$password = "";
$database = "mysql";

$mailto = "kolyabogdanenko@gmail.com";

$sqlrefresh = "SELECT id, date_of_expiry, teh_info, status, title FROM mysql.certificates_and_diplomas";
$sqlnotify = "SELECT id, date_of_expiry, status FROM mysql.certificates_and_diplomas";

$email = "";
$errors = "";

$user_ids = [];

$link = mysqli_connect($host, $user, $password, $database)
    or die("Error " . mysqli_error($link));
    $link->set_charset("utf8");
    $result = mysqli_query($link, $sqlrefresh);

// queries

// refresh dates
//TIPPS
// $timestamp=1333699439;
// gmdate("Y-m-d\TH:i:s\Z", $timestamp);
//
//$link = mysqli_connect($this->host, $this->user, $this->password, $this->database)
//        or die("Ошибка " . mysqli_error($link));
//        mysqli_query($link, "set names utf8");
//
//
//
    date_default_timezone_set('UTC');

    while($row = $result->fetch_row()) {
        try {
            $id = $row[0];                          // gets value in order of query
            $dateexpiery = $row[1];
            $user_id = $row[2];
            $status = $row[3];
            $certname = $row[4];

            $sqluser = "SELECT last_name, first_name, middle_name, tel FROM " . $database .".usersnames WHERE id=" . $user_id.";";

            $userresult = mysqli_query($link, $sqluser) or die("Error " . mysqli_error($link));
            $userdata = $userresult->fetch_row();
            $last_name = $userdata[0];
            $first_name = $userdata[1];
            $middle_name = $userdata[2];
            $tel = $userdata[3];

            if ($dateexpiery == null) {    // ignore null-result (+)
                continue;
            }

            $days = 0;

            $unix_now = strtotime('now');       // time now                           // returns UNIX; tested - OK
            $unix_end = strtotime($dateexpiery);      // last day of certificates duration // tested - OK

            if ($unix_end>$unix_now) {
                $days = intval(($unix_end - $unix_now) / 86400); // seconds between dates / seconds in one day = number of days left

                $updatecerts = "UPDATE " . $database . ".certificates_and_diplomas SET 
                days=" . $days . ", 
                last_name='" . $last_name . "', 
                first_name='" . $first_name . "', 
                middle_name='" . $middle_name . "', 
                tel='" . $tel . "' 
                WHERE id=" . $id . ";";
            }
            else{
                $updatecerts = "UPDATE ".$database.".certificates_and_diplomas SET  
                last_name='".$last_name."', 
                first_name='".$first_name."', 
                middle_name='".$middle_name."', 
                tel='".$tel."' 
                WHERE id=".$id.";";
            }

            mysqli_query($link, $updatecerts) or die("Error " . mysqli_error($link));

            if ($status == 0){
                $email .= "\nCertificate ".$certname." of user ".$first_name." ".$middle_name." ".$last_name." ".$tel." is left only ". $days ." days.\n";
            }

        } catch (Exception $exc) {
            $errors .= "Error: " . $exc->getMessage() . " occured when certificate with ID: " . $id . " was processed.\n";
        }
    }

    echo $email;

// send email
//mail($mailto,"Test",$email . "\nErrors:\n" . $errors);
