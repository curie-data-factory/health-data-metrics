<?php 	

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

$_SESSION['page'] = "help";

include $_SERVER['DOCUMENT_ROOT'].'/header.php';

?>

<style>
	
body, html {width: 100%; height: 100%; margin: 0; padding: 0}

.second-row {position: absolute; top: 49px; left: 0; right: 0; bottom: 0; background-color: white }
.second-row iframe {display: block; width: 100%; height: 100%; border: none;}

</style>
<div class="second-row">
	<iframe src="./site/" height="99%" width="100%"></iframe>
</div>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/footer.php';

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}
?>