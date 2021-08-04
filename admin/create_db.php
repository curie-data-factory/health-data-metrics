<?php 

/* LOAD CONF */ 
$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
$json = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);
$ldap_conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $conf['AUTH']['AUTH_LDAP_CONF_PATH']), true);

if (isset($_SESSION['connected'])
    AND in_array($ldap_conf['admin_ldap_authorization_domain'],$_SESSION['user_ids']['memberof'])) {

/* CONNEXION AU SERVEUR DE BASE DE DONNEES */
$dsn = 'mysql:host='.$json["hdm-core-database"]['host'].':'.$json["hdm-core-database"]['port'];
$user = $json["hdm-core-database"]['user'];
$password = $json["hdm-core-database"]['password'];

/* CREATION DE LA BASE DE DONNEE IF NOT EXISTS */
$conn = new PDO($dsn, $user, $password);
$query = "";
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

/* CONNEXION A LA BASE DE DONNEE */
$dsn = 'mysql:dbname='.$json["hdm-core-database"]["database"].';host='.$json["hdm-core-database"]['host'].':'.$json["hdm-core-database"]['port'];
$user = $json["hdm-core-database"]['user'];
$password = $json["hdm-core-database"]['password'];

try {
    $conn = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
   echo $e->getMessage();
}

/* CREATION DES TABLES TECHNIQUES */
try {
	$sql = file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CREATE_SCRIPT_PATH'],true);
	try{
		$conn->query($sql);
	} catch(PDOException $e) {
    	echo $e->getMessage();
	}

} catch (PDOException $e) {
	?>
	<div class="alert alert-danger mb-0 p-2" role="alert">
		<?php echo 'Connexion échouée : ' . $e->getMessage(); ?>
    </div>
    <?php
}

/* RESULT */
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if ($res == array()) {
	echo "Database HDM Script Complete";
}

} else {
    include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}
