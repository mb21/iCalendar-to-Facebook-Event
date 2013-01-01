<?php

if( !isset($_GET['subId']) ) {
	header("Location: " . 'index.php');
	exit;
}

$subId = $_GET['subId'];

$sub = $database->getSubscription($subId, $fbUserId);

$calUrl = $sub->calUrl;

include 'headerInclude.php';

function printLog($log) {

	echo '<table class="logTable">';
	echo'	<thead><tr>';
	echo'		<th class="thLogId">Log Id</th>';
	echo'		<th class="thTime">Time</th>';
	echo'		<th>Message</th>';
	echo '	</tr></thead>';
	echo '<tbody>';
	while ($row = $log->fetch()) {
		echo '<tr>';
		echo '	<td>' . $row->logId . '</td>';
		echo '	<td>' . date("r", $row->timestamp) . '</td>';
		echo '	<td>' . $row->message . '</td>';
		echo '</tr>';
	}
	echo '	</tbody>';
	echo '</table>';
}
?>

<h2>Error Log for Subscription <i><?php echo $sub->subName ?></i></h2>

<p>The calendar URL is <a href="<?php echo $calUrl ?>" target="_blank"><?php echo $calUrl ?></a>.</p>
<p>Subscription ID: <?php echo $subId ?></p>

<h3>Errors</h3>
<?php
printLog($database->selectErrorLog($subId));
?>


<h3>Infos</h3>
<?php
printLog($database->selectInfoLog($subId));

include 'footerInclude.php';
?>
