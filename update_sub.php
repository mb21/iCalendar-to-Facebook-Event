<?php
mb_internal_encoding('UTF-8');

/////////////////////////////////
// CONFIGURATION
/////////////////////////////////
require_once 'config.php';
require_once 'facebook/facebook.php';

$facebook = new Facebook($appapikey, $appsecret);
$user_id = $facebook->require_login();

//Connect to Database
$con = mysql_connect($host,$db_user,$db_password);
if (!$con)
	die('Could not connect: '. mysql_error());
mysql_query("SET NAMES 'utf8';", $con);
mysql_query("SET CHARACTER SET 'utf8';", $con);
mysql_select_db($database_name,$con);

function __autoload($class_name) {
    require_once $class_name . '.php';
}




//$_POST = array("adv_update" => "update","rsvp" => "attending","privacy" => "open","uploadedfile" => "","page_id" => "","adv_subcategory" => 1,"adv_category" => 1,"adv_sub_name" => "","sub_id" => "","subcategory" => "29","category" => "8","url" => "http://ics-to-fbevent.project21.ch/basic.ics","sub_name" => "nay");
//$user_id = "713344833";



$_POST['sub_name'] = '';
$sub_name = $_POST['adv_sub_name'];
$category = $_POST['adv_category'];
$subcategory = $_POST['adv_subcategory'];
$sub_id = $_POST['sub_id'];

try{
        //update db
        mysql_query("UPDATE subscriptions SET sub_name='$sub_name', category='$category',
                subcategory='$subcategory' WHERE sub_id='$sub_id' AND user_id='$user_id'") or trigger_error(mysql_error());

        if ($_POST['adv_update'] == "update"){
            //update events on fb
            $result = mysql_query("SELECT url, page_id FROM subscriptions where sub_id='$sub_id' AND user_id='$user_id'") or trigger_error(mysql_error());
            $row = mysql_fetch_row($result);

            $sub_data = array("sub_id" => $sub_id, "url" => $row[0], "user_id" => $user_id, "category" => $category, "subcategory" => $subcategory, "page_id" => $row[1]);
            $calendar  = new Calendar($sub_data);
            $numb_events = $calendar->update(TRUE); //force update of calendar
        }

        //response
        $_POST['msg'] = "<div class='clean-ok'>Subscription " . $sub_name . " updated.</div>";
        echo json_encode($_POST);

}
catch(Exception $e){
        echo "{'msg':'<div class=\'clean-error\'>" . $e->getMessage() ."</div>'}";
}

mysql_close($con);

?>