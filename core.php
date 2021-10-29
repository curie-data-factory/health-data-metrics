<?php 

# Cette fonction synchronise la table dblist avec le fichier de conf de base de données
function SyncTable($conn,$dbTarget)	{

	foreach ($dbTarget as $db) {
		$query = $conn->prepare('INSERT INTO `hdm_core_dblist` ( `db_name`, `db_type`, `db_host`, `db_port`, `db_user`, `db_is_ssl`) VALUES (:dbname, :dbtype, :dbhost, :dbport, :dbuser, :dbisssl);');

		if ($db['ssl'] == NULL) {
			$db['ssl'] = "false";
		}
		
	    if (!$query->execute(array(':dbname' => $db['database'],
	    				  ':dbtype' => $db['type'],
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
								                 `db_type`,
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

# Fonction qui récupère le contenu sur Nexus
function getNexusContent($query="") {

	# on récupère l'url du répo à requêter
	if(!isset($_SESSION['nexusUrl'])) {
	    $_SESSION['nexusUrl'] = NEXUS_API_URL;
	}
	# on récupère le répo à requêter
	if (!isset($_SESSION['nexusRepository'])) {
	    $_SESSION['nexusRepository'] = NEXUS_DEFAULT_REPOSITORY;
	}

	// Affichage par défaut des data

    # On redémarre la liste
	if(isset($_POST['resetList'])) {
	    unset($_SESSION['continuationToken']);
    }

	# requête pour récup les metric packs du répo NEXUS : HARDCODED : hdm.metricpacks
	$url = $_SESSION['nexusUrl']."search?q=".$query."&repository=".$_SESSION['nexusRepository'];
	unset($_SESSION['continuationToken']);

	# On GET le content
	$contents =  getSSLPage($url);

	# Si il y a du contenu
    $data = "{}";
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
    return $sth->fetchAll(PDO::FETCH_ASSOC);
}

# Fonction qui récupère la liste de correspondance entre les bases et les metricpacks
function getDbMpCorrList($conn) {
    # Getting MySQL Metrics database version
    $sql = 'SELECT * FROM hdm_core_table_corr_db_mp';
    $sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    $sth->execute();
    return $sth->fetchAll(PDO::FETCH_ASSOC);
}

# Fonction qui récupère la liste de correspondance entre les bases et les metricpacks
function getMailList($conn,$mail) {
    # Getting MySQL Metrics database version
    $sql = 'SELECT * FROM hdm_core_mail_list WHERE mail = :mail';
    $sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    $sth->execute(array(':mail' => $mail));
    return $sth->fetchAll(PDO::FETCH_ASSOC);
}

# Fonction qui récupère la liste de correspondance entre les bases et les metricpacks
function getDbRpCorrList($conn) {
	# Getting MySQL Rule database version
	$sql = 'SELECT * FROM hdm_core_table_corr_db_rp';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute();
    return $sth->fetchAll(PDO::FETCH_ASSOC);
}

// alert table Fonction php qui prend en entrée une ligne de donnée SQL et affiche en une ligne de tableau HTML en tenant compte des afficheurs de filtre de niveau d'alerte
function writeRow($row): string
{
    $row_print = "";
    $linkRule = "";
	switch ($row['alert_scope']) {
        case 'database':
        case 'all':
			$linkRule =  "/rule-editor/rule.php?rulePack=rule_".$row['rule_pack']."&database=".$row['database']."&newFilter=#";
			break;
        case 'table':
			$linkRule =  "/rule-editor/rule.php?rulePack=rule_".$row['rule_pack']."&database=".$row['database']."&table=".$row['table']."&newFilter=#";
			break;
		case 'column':
			$linkRule =  "/rule-editor/rule.php?rulePack=rule_".$row['rule_pack']."&database=".$row['database']."&table=".$row['table']."&column=".$row['column']."&newFilter=#";
			break;
	}
	$display = FALSE;
	if(($_SESSION['alert-display-high'] == "True") && ($row['alert_level'] == "Haut")){
        $row_print .= "<tr class=\"table-danger row\">";
		$display = TRUE;
	} elseif(($_SESSION['alert-display-warn'] == "True") && ($row['alert_level'] == "Warning")){
        $row_print .= "<tr class=\"table-warning row\">";
		$display = TRUE;
	} elseif(($_SESSION['alert-display-info'] == "True") && ($row['alert_level'] == "Info")){
        $row_print .= "<tr class=\"table-info row\">";
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

	$messageClass = "";

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

	# creation du lien paramètre pour l'explorer kibana :
	$linkKibana = "/explorer/wrapper.php?filtered=true&database=".$row['database']."&version=".$row['dbversion']."&table=".$row['table']."&column=".$row['column'];

	if($display){
		
		$row_print .= "<td class=\"col-lg-1 col-sm-1\">".  $messageClass . "</td>\n";
		$row_print .= "<td class=\"col-lg-2 col-sm-2\">".  $row['database'] . "</td>\n";
		$row_print .= "<td class=\"col-lg-1 col-sm-1\">".  $row['dbversion'] . "</td>\n";
		$row_print .= "<td class=\"col-lg-2 col-sm-2\">".  $row['table'] . "</td>\n";
		$row_print .= "<td class=\"col-lg-3 col-sm-3\">".  $row['column'] . "</td>\n";
		$row_print .= "<td class=\"col-lg-2 col-sm-3\">".  $row['alert_message'] . "</td>\n";
		$row_print .= "<td class=\"col-lg-1 col-sm-1\">
		<a href=\"".$linkKibana."\" role=\"button\" class=\"btn btn-light\" style=\"padding:0;\"><i class=\"fas fa-search\"></i></a>
		<a href=\"".$linkRule."\" role=\"button\" class=\"btn btn-light\" style=\"padding:0;\"><i class=\"fas fa-pencil-alt\"></i></i></a> 
		</td>\n";
        $row_print .= '</tr>';
	}

	return $row_print;
}

// alert table function
function printBadge($array,$selector,$scope){
	if(!empty($array)) {
		foreach ($array as $value) {
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
function printHeader($value='',$display=False): string
{
	$valueDisplay = "";
	if ($display) {
		$valueDisplay = "active show";
	}

    return '<div class="tab-pane fade '.$valueDisplay.'" id="v-pills-'.$value.'" role="tabpanel" aria-labelledby="v-pills-'.$value.'-tab">
    <a href="?#'.$value.'"><h4 id="'.$value.'">'.$value.'</h4></a>
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
}

// fonction qui permet de détruire toutes les variables de session des formulaires d'ajout des règles
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
	unset($_SESSION['metrics']);
}

# Fonction qui permet de requêter sans arguments
function simple_query_db($conn,$query_string) {
    try {
        # Préparation de la requête pour insertion des données dans la table de tokens
        if ($conn != null) {
            $query = $conn->prepare($query_string);
            # Execution de la requête
            if(!$query->execute()) {
                print_r($query->errorInfo());
            } else {
                return $query->fetchAll();
            }
        } else {
            return "";
        }
    } catch (PDOException $e) {
        echo 'Connexion échouée : '. $e->getMessage();
    }

    return null;
}

# This function sanitize a string to slug
function sanitize($title): string
{
    $title = strip_tags($title);
    // Preserve escaped octets.
    $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
    // Remove percent signs that are not part of an octet.
    $title = str_replace('%', '', $title);
    // Restore octets.
    $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

    $title = strtolower($title);
    $title = preg_replace('/&.+?;/', '', $title); // kill entities
    $title = str_replace('.', '-', $title);
    $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
    $title = preg_replace('/\s+/', '-', $title);
    $title = preg_replace('|-+|', '-', $title);
    $title = trim($title, '-');

    return $title;
}