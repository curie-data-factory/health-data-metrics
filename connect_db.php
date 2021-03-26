<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

	/* loading json */ 
	$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
	$json = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);

	/* Connexion à une base MySQL avec l'invocation de pilote */
	$dsn = 'mysql:dbname='.$json["hdm-core-database"]["database"].';host='.$json["hdm-core-database"]['host'].':'.$json["hdm-core-database"]['port'];
	$user = $json["hdm-core-database"]['user'];
	$password = $json["hdm-core-database"]['password'];

	try {
	    $conn = new PDO($dsn, $user, $password);
	} catch (PDOException $e) {
		?>
		<div class="alert alert-danger mb-0 p-2" role="alert">
			<?php echo 'Connexion échouée : ' . $e->getMessage() . ' |  You should consider running the create db script : '; ?>
			<a href="/admin/create_db.php">Run the create db script</a>
	    </div>
	    <?php
	}

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}

?>
