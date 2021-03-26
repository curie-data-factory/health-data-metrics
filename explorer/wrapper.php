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

<style type="text/css">
.md-header {
	background-color: hsl(209, 59%, 30%);
}

body, html {width: 100%; height: 100%; margin: 0; padding: 0}
.first-row {position: absolute;top: 0; left: 0; right: 0; height: 49px; background-color: white;}
.second-row {position: absolute; top: 49px; left: 0; right: 0; bottom: 0; background-color: white }
.second-row iframe {display: block; width: 100%; height: 100%; border: none;}

</style>
<div class="second-row">

<?php 

# cas ou on a cliqué sur le lien de l'accueil sur une alerte et on veut voir le scope de l'alerte dans le dashboard
if (isset($_GET["filtered"])) {
	if($_GET["filtered"] == "true") {

		$database = "";
		if (!empty($_GET["database"])) {
			$database = $_GET["database"];
		}

		$version = "";
		if (!empty($_GET["dbversion"])) {
			$version = $_GET["dbversion"];
		}

		$table = "";
		if (!empty($_GET["table"])) {
			$table = $_GET["table"];
		}

		$column = "";
		if (!empty($_GET["column"])) {
			$column = $_GET["column"];
		}

		echo("<iframe src=\"".KIBANA_URL."/s/".KIBANA_NAMESPACE."/app/kibana#/dashboard/".KIBANA_EXPLORATOR_DASHBOARD."?embed=true&_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:now-7d,to:now))&_a=(description:'',filters:!(('\$state':(store:appState),meta:(alias:!n,controlledBy:'1580223067055',disabled:!f,index:'".KIBANA_EXPLORATOR_INDEX."',key:database.keyword,negate:!f,params:(query:".$database."),type:phrase,value:".$database."),query:(match:(database.keyword:(query:".$database.",type:phrase)))),('\$state':(store:appState),meta:(alias:!n,controlledBy:'1580223090761',disabled:!f,index:'".KIBANA_EXPLORATOR_INDEX."',key:version.keyword,negate:!f,params:(query:'".$version."'),type:phrase,value:'".$version."'),query:(match:(version.keyword:(query:'".$version."',type:phrase)))),('\$state':(store:appState),meta:(alias:!n,controlledBy:'1580224126576',disabled:!f,index:'".KIBANA_EXPLORATOR_INDEX."',key:table.keyword,negate:!f,params:(query:".$table."),type:phrase,value:".$table."),query:(match:(table.keyword:(query:".$table.",type:phrase)))),('\$state':(store:appState),meta:(alias:!n,controlledBy:'1580224218009',disabled:!f,index:'".KIBANA_EXPLORATOR_INDEX."',key:column.keyword,negate:!f,params:(query:".$column."),type:phrase,value:".$column."),query:(match:(column.keyword:(query:".$column.",type:phrase))))),fullScreenMode:!f,options:(hidePanelTitles:!f,useMargins:!f),panels:!((embeddableConfig:(),gridData:(h:33,i:'213a9506-66fc-4e7a-990b-b7d48bc4c204',w:18,x:0,y:0),id:'4f715230-41ee-11ea-b8ad-5ffe5099689d',panelIndex:'213a9506-66fc-4e7a-990b-b7d48bc4c204',type:visualization,version:'7.7.0'),(embeddableConfig:(),gridData:(h:24,i:a57ac482-1ba5-4bf8-86a6-586fb17b51fd,w:13,x:35,y:0),id:'7ae5b100-41de-11ea-b8ad-5ffe5099689d',panelIndex:a57ac482-1ba5-4bf8-86a6-586fb17b51fd,type:visualization,version:'7.7.0'),(embeddableConfig:(),gridData:(h:7,i:'79897a81-9160-4756-ba2e-b0c5a876f21d',w:13,x:35,y:24),id:dfdd7740-41e9-11ea-b8ad-5ffe5099689d,panelIndex:'79897a81-9160-4756-ba2e-b0c5a876f21d',type:visualization,version:'7.7.0'),(embeddableConfig:(),gridData:(h:7,i:'19b01aa3-5cd6-48f8-b1f4-0e6a19b911cc',w:13,x:35,y:31),id:'1bd1fa00-41ea-11ea-b8ad-5ffe5099689d',panelIndex:'19b01aa3-5cd6-48f8-b1f4-0e6a19b911cc',type:visualization,version:'7.7.0')),query:(language:kuery,query:''),timeRestore:!f,title:hdm-dashboard,viewMode:view)\" height=\"99%\" width=\"100%\"></iframe>");
	}
} else {
	echo('<iframe src="'.KIBANA_URL.'s/'.KIBANA_NAMESPACE.'/app/kibana#/dashboards?embed=true&_g=(filters%3A!()%2CrefreshInterval%3A(pause%3A!t%2Cvalue%3A0)%2Ctime%3A(from%3Anow%2Fd%2Cto%3Anow%2Fd))" height="99%" width="100%"></iframe>');
}
?>
</div>
<?php 
include_once $_SERVER['DOCUMENT_ROOT'].'/footer.php';

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}

?>