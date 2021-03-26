<?php 

/* loading json */ 
$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
$json = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);	

/* Connexion à une base MySQL avec l'invocation de pilote */

$dsn = 'mysql:host='.$json["hdm-core-database"]['host'].':'.$json["hdm-core-database"]['port'];
$user = $json["hdm-core-database"]['user'];
$password = $json["hdm-core-database"]['password'];

/* CREATION DE LA BASE DE DONNEE IF NOT EXISTS */
$conn = new PDO($dsn, $user, $password);
try {
    $query = $conn->prepare("CREATE DATABASE IF NOT EXISTS `".$json['hdm-core-database']['database']."` /*!40100 */;", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$query->execute();
} catch (PDOException $e) {
	?>
	<div class="alert alert-danger mb-0 p-2" role="alert">
		<?php echo 'Connexion échouée : ' . $e->getMessage(); ?>
    </div>
    <?php
}

/* Connexion normal */
include_once($_SERVER['DOCUMENT_ROOT'].'/connect_db.php');

try {
	/* CREATION DES TABLES TECHNIQUES */
	$sql = file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CREATE_SCRIPT_PATH'],true);
	$query = $conn->prepare($sql);
	$query->execute();
} catch (PDOException $e) {
	?>
	<div class="alert alert-danger mb-0 p-2" role="alert">
		<?php echo 'Connexion échouée : ' . $e->getMessage(); ?>
    </div>
    <?php
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
if ($res == array()) {
	echo "Database HDM Script Complete";
}
