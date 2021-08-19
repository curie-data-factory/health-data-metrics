<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

$_SESSION['page'] = "explorer";

include_once($_SERVER['DOCUMENT_ROOT'].'/header.php');

# load constantes
$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
foreach ($conf['EXPLORER'] as $key => $value) {
	define($key,$value);
}

?>

<style>

body, html {width: 100%; height: 100%; margin: 0; padding: 0}
.second-row {position: absolute; top: 49px; left: 0; right: 0; bottom: 0; background-color: white }
.second-row iframe {display: block; width: 100%; height: 100%; border: none;}

</style>
<div class="second-row">

<?php 

# cas ou on a cliqué sur le lien de l'accueil sur une alerte et on veut voir le scope de l'alerte dans le tableau de bord
if (isset($_GET["filtered"])) {
	if($_GET["filtered"] == "true") {

		$database = "";
		if (!empty($_GET["database"])) {
			$database = $_GET["database"];
		}

		$version = "";
		if (!empty($_GET["version"])) {
			$version = $_GET["version"];
		}

		$table = "";
		if (!empty($_GET["table"])) {
			$table = $_GET["table"];
		}

		$column = "";
		if (!empty($_GET["column"])) {
			$column = $_GET["column"];
		}

		echo("<iframe src=\"".KIBANA_URL."/s/".KIBANA_NAMESPACE."/app/dashboards#/view/".KIBANA_EXPLORATOR_DASHBOARD."?embed=true&_g=(filters:!())&_a=(description:'Ce%20Dashboard%20permet%20d!'explorer%20les%20valeurs%20manquantes%20dans%20les%20bases%20de%20donn%C3%A9es.%0AIl%20permet%20%C3%A9galement%20d!'avoir%20une%20vue%20de%20l!'%C3%A9volution%20de%20la%20quantit%C3%A9%20de%20donn%C3%A9es%20dans%20les%20bases%20selon%20les%20filtres%20appliqu%C3%A9s.',filters:!(('$state':(store:appState),meta:(alias:!n,controlledBy:'1580223067055',disabled:!f,index:".KIBANA_EXPLORATOR_INDEX.",key:database.keyword,negate:!f,params:(query:".$database."),type:phrase),query:(match_phrase:(database.keyword:".$database."))),('$state':(store:appState),meta:(alias:!n,controlledBy:'1580223090761',disabled:!f,index:".KIBANA_EXPLORATOR_INDEX.",key:dbversion.keyword,negate:!f,params:(query:'".$version."'),type:phrase),query:(match_phrase:(dbversion.keyword:'".$version."'))),('$state':(store:appState),meta:(alias:!n,controlledBy:'1580224126576',disabled:!f,index:".KIBANA_EXPLORATOR_INDEX.",key:table.keyword,negate:!f,params:(query:".$table."),type:phrase),query:(match_phrase:(table.keyword:".$table."))),('$state':(store:appState),meta:(alias:!n,controlledBy:'1580224218009',disabled:!f,index:".KIBANA_EXPLORATOR_INDEX.",key:column.keyword,negate:!f,params:(query:".$column."),type:phrase),query:(match_phrase:(column.keyword:".$column.")))),fullScreenMode:!f,options:(hidePanelTitles:!f,useMargins:!f),query:(language:kuery,query:''),tags:!(),timeRestore:!t,title:'%5BMP%5D%5BBasic%5D%20Overview',viewMode:view)\" height=\"99%\" width=\"100%\"></iframe>");
	}
} else {
	echo('<iframe src="'.KIBANA_URL.'/s/'.KIBANA_NAMESPACE.'/app/kibana#/dashboards?embed=true&_g=(filters%3A!()%2CrefreshInterval%3A(pause%3A!t%2Cvalue%3A0)%2Ctime%3A(from%3Anow%2Fd%2Cto%3Anow%2Fd))" height="99%" width="100%"></iframe>');
}
?>
</div>
<?php 
include_once $_SERVER['DOCUMENT_ROOT'].'/footer.php';

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}

?>