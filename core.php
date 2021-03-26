<?php 

# Cete fonction synchronise la table dblist avec le fichier de conf de base de données
function SyncTable($conn,$dbTarget)	{

	foreach ($dbTarget as $db) {
		$query = $conn->prepare('INSERT INTO `hdm_core_dblist` ( `db_name`, `db_host`, `db_port`, `db_user`, `db_is_ssl`) VALUES (:dbname, :dbhost, :dbport, :dbuser, :dbisssl);');

		if ($db['ssl'] == NULL) {
			$db['ssl'] = "false";
		}
		
	    if (!$query->execute(array(':dbname' => $db['database'],
						  ':dbhost' => $db['host'],
						  ':dbport' => $db['port'],
						  ':dbuser' => $db['user'],
						  ':dbisssl' => $db['ssl']))) {
			print_r($query->errorInfo());
		}

		#dropping duplicates :
		$query = $conn->prepare('DELETE
								FROM hdm_core_dblist
								WHERE id NOT IN
								    (SELECT id
								     FROM
								       (SELECT MIN(id) AS id
								        FROM hdm_core_dblist
								        GROUP BY `db_name`,
								                 `db_host`,
								                 `db_port`,
								                 `db_user`,
								                 `db_is_ssl` HAVING COUNT(*) >= 1) AS c);');
	    if (!$query->execute()) {
			print_r($query->errorInfo());
		}
	}
}

# Fonction qui permet de requêter une page
function getSSLPage($url) {

	$ch = curl_init($url);

	//Set to simple authentification
	curl_setopt($ch, CURLOPT_USERPWD, NEXUS_USER . ":" . NEXUS_PASSWORD);

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
}

# Fonction qui récupère le contenus sur Nexus
function getNexusContent($query="") {

	# on récupère l'url du répo à requêter
	if(!isset($_SESSION['nexusUrl'])) {
	    $_SESSION['nexusUrl'] = NEXUS_API_URL;
	}
	# on récupère le répo à requêter
	if (!isset($_SESSION['nexusRepository'])) {
	    $_SESSION['nexusRepository'] = NEXUS_DEFAULT_REPOSITORY;
	}

	// Affichage par défaut des datas
	$default = True;

	# On redémare la liste 
	if(isset($_POST['resetList'])) {
	    unset($_SESSION['continuationToken']);
	    $default = False;
	}

	# requête pour récup les metric packs du répo NEXUS : HARDCODED : hdm.metricpacks
	$url = $_SESSION['nexusUrl']."search?q=".$query."&repository=".$_SESSION['nexusRepository'];
	unset($_SESSION['continuationToken']);

	# On GET le content
	$contents =  getSSLPage($url);

	# Si il y a du contenu
	if($contents !== false){

	    # On décode le json
	    $data = json_decode($contents,true);
	}

	return $data;
}

# Fonction qui récupère la liste des bases de données à scanner
function getDbList($conn) {
	# Getting MySQL Metrics database version
	$sql = 'SELECT * FROM hdm_core_dblist';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute();
	$res = $sth->fetchAll(PDO::FETCH_ASSOC);

	return $res;
}

# Fonction qui récupère la liste de correspondance entre les bases et les metricpacks
function getDbMpCorrList($conn) {
	# Getting MySQL Metrics database version
	$sql = 'SELECT * FROM hdm_core_table_corr_db_mp';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute();
	$res = $sth->fetchAll(PDO::FETCH_ASSOC);

	return $res;
}

# Fonction qui récupère la liste de correspondance entre les bases et les metricpacks
function getDbRpCorrList($conn) {
	# Getting MySQL Rule database version
	$sql = 'SELECT * FROM hdm_core_table_corr_db_rp';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute();
	$res = $sth->fetchAll(PDO::FETCH_ASSOC);

	return $res;
}

// alert table Fonction php qui prend en entrée une ligne de donnée SQL et affiche en une ligne de tableau HTML en tenant compte des afficheur de filtre de niveau d'alerte
function writeRow($row)
{
	switch ($row['alert_scope']) {
		case 'all':
			$linkRule =  "/rule-editor/rule.php?database=".$row['database']."&newFilter=#";
			break;
		case 'database':
			$linkRule =  "/rule-editor/rule.php?database=".$row['database']."&newFilter=#";
			break;
		case 'table':
			$linkRule =  "/rule-editor/rule.php?database=".$row['database']."&table=".$row['table']."&newFilter=#";
			break;
		case 'column':
			$linkRule =  "/rule-editor/rule.php?database=".$row['database']."&table=".$row['table']."&column=".$row['column']."&newFilter=#";
			break;
	}
	$display = FALSE;
	if(($_SESSION['alert-display-high'] == "True") && ($row['alert_level'] == "Haut")){
		echo("<tr class=\"table-danger row\">");
		$display = TRUE;
	} elseif(($_SESSION['alert-display-warn'] == "True") && ($row['alert_level'] == "Warning")){
		echo("<tr class=\"table-warning row\">");
		$display = TRUE;
	} elseif(($_SESSION['alert-display-info'] == "True") && ($row['alert_level'] == "Info")){
		echo("<tr class=\"table-info row\">");
		$display = TRUE;
	}

	if(($_SESSION['filter-display-METRICCOMPARE'] != "True") && ($row['alert_class'] == "METRICCOMPARE")){
		$display = FALSE;
	} elseif(($_SESSION['filter-display-SCHEMA'] != "True") && ($row['alert_class'] == "SCHEMA")){
		$display = FALSE;
	} elseif(($_SESSION['filter-display-METRIQUE'] != "True") && ($row['alert_class'] == "METRIQUE")){
		$display = FALSE;
	} elseif(($_SESSION['filter-display-DATA'] != "True") && ($row['alert_class'] == "DATA")){
		$display = FALSE;
	}

	switch ($row['alert_class']) {
		case 'METRICCOMPARE':
			$messageClass = '<span class="badge badge-secondary">METRIQUECOMPARE</span>';
			break;
		case 'SCHEMA':
			$messageClass = '<span class="badge badge-success">SCHEMA</span>';
			break;
		case 'METRIQUE':
			$messageClass = '<span class="badge badge-primary">METRIQUE</span>';
			break;
		case 'DATA':
			$messageClass = '<span class="badge badge-dark">DATA</span>';
			break;
	}

	switch ($row['alert_level']) {
		case 'Haut':
			$messageClass .= '<span class="badge badge-danger">High</span>';
			break;
		case 'Warning':
			$messageClass .= '<span class="badge badge-warning">Warning</span>';
			break;
		case 'Info':
			$messageClass .= '<span class="badge badge-info">Info</span>';
			break;
	}

	#creation du lien parametre pour le explorer kibana : 
	$linkKibana = "/explorer/wrapper.php?filtered=true&database=".$row['database']."&version=".$row['dbversion']."&table=".$row['table']."&column=".$row['column'];

	if($display){
		
		echo "<td class=\"col-lg-1 col-sm-1\">".  $messageClass . "</td>\n";
		echo "<td class=\"col-lg-2 col-sm-2\">".  $row['database'] . "</td>\n";
		echo "<td class=\"col-lg-1 col-sm-1\">".  $row['dbversion'] . "</td>\n";
		echo "<td class=\"col-lg-2 col-sm-2\">".  $row['table'] . "</td>\n";
		echo "<td class=\"col-lg-3 col-sm-3\">".  $row['column'] . "</td>\n";
		echo "<td class=\"col-lg-2 col-sm-3\">".  $row['alert_message'] . "</td>\n";
		echo "<td class=\"col-lg-1 col-sm-1\">
		<a href=\"".$linkKibana."\" role=\"button\" class=\"btn btn-light\" style=\"padding:0px;\"><i class=\"fas fa-search\"></i></a>
		<a href=\"".$linkRule."\" role=\"button\" class=\"btn btn-light\" style=\"padding:0px;\"><i class=\"fas fa-pencil-alt\"></i></i></a> 
		</td>\n";
		echo('</tr>');
	}
}

// alert table function
function printBadge($array,$selector,$scope){
	if(!empty($array)) {
		foreach ($array as $key => $value) {
			if ($value[$scope] == $selector) {
				$i = 0;
				foreach ($value as $indice => $count) {
					if ($indice == "alert_level") {
						echo('<span style="float: right;" class="badge badge-');
						switch($count) {
							case 'Haut':
							echo "danger";
							break;
							case 'Warning':
							echo "warning";
							break;
							case 'Info':
							echo "info";
							break;
						}
						echo('">'.$value['count'].'</span>');
					}
					$i++;
				}
			}
		}
	}
}

// alert table function
function printHeader($value='',$display=False)
{
	$valueDisplay = "";
	if ($display) {
		$valueDisplay = "active show";
	}

	$tableHeader = '<div class="tab-pane fade '.$valueDisplay.'" id="v-pills-'.$value.'" role="tabpanel" aria-labelledby="v-pills-'.$value.'-tab">
	<h4 id="'.$value.'">'.$value.'</h4>
	<table class="table">
	<thead><tr class="row">
	<th class="col-lg-1"><a href="?orderBy=alertClass">Badges</a></th>
	<th class="col-lg-2"><a href="?orderBy=database">database</a></th>
	<th class="col-lg-1"><a href="?orderBy=version">version</a></th>
	<th class="col-lg-2"><a href="?orderBy=table">table</a></th>
	<th class="col-lg-3"><a href="?orderBy=column">column</a></th>
	<th class="col-lg-2"><a href="?orderBy=alert_message">message</a></th>
	<th class="col-lg-1"><a href="?orderBy=rule">Link</a></th>
	</tr></thead>
	<tbody>';

	echo($tableHeader);
}

// fonction qui permet de détruire toutes les variables de session des formulaires d'ajout des regles
function unsetFormRule()
{
	unset($_SESSION['form-step']);
	unset($_SESSION['ruleType']);
	unset($_SESSION['ruleName']);
	unset($_SESSION['sqlRequestValue']);
	unset($_SESSION['sqlRequest']);
	unset($_SESSION['alertScope']);
	unset($_SESSION['alertMessage']);
	unset($_SESSION['alertLevel']);
	unset($_SESSION['alertClass']);
	unset($_SESSION['conditionTrigger']);
	unset($_SESSION['metric']);
	unset($_SESSION['condition']);
	unset($_SESSION['conditionValue']);
	unset($_SESSION['conditionScope']);
}

