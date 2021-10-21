<?php 	

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

$_SESSION['page'] = "alert";

include_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/connect_db.php');

# load constantes
$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
foreach ($conf['EXPLORER'] as $key => $value) {
	define($key,$value);
}

$_SESSION['kibanaUrl'] = KIBANA_URL;

///////////////////////////////// ALERT CLASS

if(!isset($_SESSION['filter-display-METRICCOMPARE'])){
	$_SESSION['filter-display-METRICCOMPARE'] = "True";
}
if(isset($_SESSION['filter-display-METRICCOMPARE']) && @$_POST['filter-display-METRICCOMPARE']){
	if($_POST['filter-display-METRICCOMPARE'] == "True"){
		$_SESSION['filter-display-METRICCOMPARE'] = "False";
	} else {
		$_SESSION['filter-display-METRICCOMPARE'] = "True";
	}
}

if(!isset($_SESSION['filter-display-METRIQUE'])){
	$_SESSION['filter-display-METRIQUE'] = "True";
}
if(isset($_SESSION['filter-display-METRIQUE']) && @$_POST['filter-display-METRIQUE']){
	if($_POST['filter-display-METRIQUE'] == "True"){
		$_SESSION['filter-display-METRIQUE'] = "False";
	} else {
		$_SESSION['filter-display-METRIQUE'] = "True";
	}
}

if(!isset($_SESSION['filter-display-SCHEMA'])){
	$_SESSION['filter-display-SCHEMA'] = "True";
}

if(isset($_SESSION['filter-display-SCHEMA']) && @$_POST['filter-display-SCHEMA']){
	if($_POST['filter-display-SCHEMA'] == "True"){
		$_SESSION['filter-display-SCHEMA'] = "False";
	} else {
		$_SESSION['filter-display-SCHEMA'] = "True";
	}
}

if(!isset($_SESSION['filter-display-DATA'])){
	$_SESSION['filter-display-DATA'] = "True";
}

if(isset($_SESSION['filter-display-DATA']) && @$_POST['filter-display-DATA']){
	if($_POST['filter-display-DATA'] == "True"){
		$_SESSION['filter-display-DATA'] = "False";
	} else {
		$_SESSION['filter-display-DATA'] = "True";
	}
}

if(isset($_POST['databaseName'])){
	$_SESSION['databaseName'] = $_POST['databaseName'];
}
if(isset($_POST['tableName'])){
	$_SESSION['tableName'] = $_POST['tableName'];
}

if(!isset($_SESSION['databaseName'])){
	$_SESSION['databaseName'] ="";
}

if(!isset($_SESSION['tableName'])){
	$_SESSION['tableName'] ="";
}

///////////////////////////////// ALERT LEVEL

if(!isset($_SESSION['alert-display-high'])){
	$_SESSION['alert-display-high'] = "True";
}
if(isset($_SESSION['alert-display-high']) && @$_POST['alert-display-high']){
	if($_POST['alert-display-high'] == "True"){
		$_SESSION['alert-display-high'] = "False";
	} else {
		$_SESSION['alert-display-high'] = "True";
	}
}

if(!isset($_SESSION['alert-display-warn'])){
	$_SESSION['alert-display-warn'] = "True";
}
if(isset($_SESSION['alert-display-warn']) && @$_POST['alert-display-warn']){
	if($_POST['alert-display-warn'] == "True"){
		$_SESSION['alert-display-warn'] = "False";
	} else {
		$_SESSION['alert-display-warn'] = "True";
	}
}

if(!isset($_SESSION['alert-display-info'])){
	$_SESSION['alert-display-info'] = "True";
}
if(isset($_SESSION['alert-display-info']) && @$_POST['alert-display-info']){
	if($_POST['alert-display-info'] == "True"){
		$_SESSION['alert-display-info'] = "False";
	} else {
		$_SESSION['alert-display-info'] = "True";
	}
}

if(!isset($_SESSION['seeAll'])){
	$_SESSION['seeAll'] = "False";
}
if(isset($_SESSION['seeAll']) && @$_POST['seeAll']){
	if($_POST['seeAll'] == "True"){
		$_SESSION['seeAll'] = "False";
	} else {
		$_SESSION['seeAll'] = "True";
	}
}

if(!isset($_SESSION['split-display-database'])){
	$_SESSION['split-display-database'] = "True";
	$_SESSION['split-display-table'] = "False";
	$_SESSION['split-display-column'] = "False";
	$_SESSION['split-display-scope'] = "database";
}

if(isset($_SESSION['split-display-database']) && @$_POST['split-display-database']){
	if($_POST['split-display-database'] == "True"){
		$_SESSION['split-display-database'] = "False";
	} else {
		$_SESSION['split-display-scope'] = "database";
		$_SESSION['split-display-database'] = "True";
		$_SESSION['split-display-table'] = "False";
		$_SESSION['split-display-column'] = "False";
	}
}

if(isset($_SESSION['split-display-table']) && @$_POST['split-display-table']){
	if($_POST['split-display-table'] == "True"){
		$_SESSION['split-display-table'] = "False";
	} else {
		$_SESSION['split-display-table'] = "True";
		$_SESSION['split-display-scope'] = "table";
		$_SESSION['split-display-database'] = "False";
		$_SESSION['split-display-column'] = "False";
	}
}

if(isset($_SESSION['split-display-column']) && @$_POST['split-display-column']){
	if($_POST['split-display-column'] == "True"){
		$_SESSION['split-display-column'] = "False";
	} else {
		$_SESSION['split-display-column'] = "True";
		$_SESSION['split-display-scope'] = "column";
		$_SESSION['split-display-table'] = "False";
		$_SESSION['split-display-database'] = "False";
	}
}

// Gestion en session des affichages kibana2 : 
if(isset($_POST['kibana2'])) {
	if ($_SESSION['kibana2'] == "True") {
		$_SESSION['kibana2'] = "False";
	} else {
		$_SESSION['kibana2'] = "True";
	}
} else {
	if(!isset($_SESSION['kibana2'])) {
		$_SESSION['kibana2'] = "False";
	}
}

?>
<main role="main">
<form method="post" action="#" >
	<div class="container-fluid">
		<div class="row">
		
			<div class="col-lg-12">
				<div class="row query mb-3 mt-1 bg-white rounded shadow-sm p-2">
				
						<div class="col-lg-2">
						<div class="row">
						<?php 
						if ($_SESSION['kibana2'] == "True") { 
							$kibana2 = "info";
						} else { 
							$kibana2 = "light";
						}
						echo('<button type="submit" name="kibana2" class="mr-4 btn btn-'.$kibana2.'">Kibana dashboard</button>');
						?>
						</div>
						</div>
						<div class="col-lg-3">
						<div class="row">
						<h5 class="mt-2  mr-2" >Alert Level : </h5>
						<button type="submit" id="databaseNameButton" value="<?php echo($_SESSION['databaseName']);?>" name="databaseName"  style="display: none"></button>
						<button type="submit" id="tableNameButton" value="<?php echo($_SESSION['tableName']); ?>" name="tableName"  style="display: none"></button>
						
						<button type="submit" value="<?php echo($_SESSION['alert-display-high']) ?>" name="alert-display-high" class='btn btn-<?php if($_SESSION["alert-display-high"] == "True"){ echo("danger") ;} else {echo("light") ;} ?> '>High</button>
						<button type="submit"  value="<?php echo($_SESSION['alert-display-warn']) ?>" name="alert-display-warn" class='btn btn-<?php if($_SESSION["alert-display-warn"] == "True"){ echo("warning") ;} else {echo("light") ;} ?> '>Warning</button>
						<button type="submit" value="<?php echo($_SESSION['alert-display-info']) ?>" name="alert-display-info" class='mr-4 btn btn-<?php if($_SESSION["alert-display-info"] == "True"){ echo("info") ;} else {echo("light") ;} ?> '>Info</button>
						</div>
						</div>
						<div class="col-lg-7">
							<div class="row">
						<h5 class="mt-2  mr-2" > Alert Class : </h5>
						<button type="submit" value="<?php echo($_SESSION['filter-display-METRICCOMPARE']) ?>" name="filter-display-METRICCOMPARE" class='btn btn-<?php if($_SESSION["filter-display-METRICCOMPARE"] == "True"){ echo("secondary") ;} else {echo("light") ;} ?> '>METRICCOMPARE</button>
						<button type="submit" value="<?php echo($_SESSION['filter-display-SCHEMA']) ?>" name="filter-display-SCHEMA" class='btn btn-<?php if($_SESSION["filter-display-SCHEMA"] == "True"){ echo("success") ;} else {echo("light") ;} ?> '>SCHEMA</button>
						<button type="submit" value="<?php echo($_SESSION['filter-display-DATA']) ?>" name="filter-display-DATA" class='btn btn-<?php if($_SESSION["filter-display-DATA"] == "True"){ echo("dark") ;} else {echo("light") ;} ?> '>DATA</button>
						<button type="submit" value="<?php echo($_SESSION['filter-display-METRIQUE']); ?>" <?php if($_SESSION["filter-display-METRIQUE"] == "True"){ echo('style="background-color: #007bff; border-color: #007bff; "');
						} ?> name="filter-display-METRIQUE" class=' mr-4 btn btn-<?php if($_SESSION["filter-display-METRIQUE"] == "True"){ echo("primary") ;} else {echo("light") ;} ?>'>METRIQUE</button>
						<button type="submit" value="<?php echo($_SESSION['seeAll']) ?>" name="seeAll" class='btn btn-<?php if($_SESSION["seeAll"] == "True"){ echo("primary") ;} else {echo("light") ;} ?>'>See All</button>
						</div>
						</div>
				</div>
			</div>
			<?php 

			$sql = "SELECT DISTINCT `hdm_alerts`.database 
			FROM `hdm_alerts` 
			WHERE `date` = (SELECT MAX(`date`) AS maxdate FROM `hdm_alerts`)
			ORDER BY `hdm_alerts`.database";
			
			// Requête pour récupérer les alertes
			$sql = "";
			// requête pour récupérer le compte des alertes par niveau d'alerte
			$sql1 ="";
			if($_SESSION['split-display-database'] == "True" ){
				$sql1 = "SELECT hdm_alerts.database, hdm_alerts.alert_level, COUNT(*) AS count
			FROM hdm_alerts 
			WHERE `date` = (SELECT MAX(`date`) AS maxdate FROM hdm_alerts)
			GROUP BY hdm_alerts.database, hdm_alerts.alert_level
			ORDER BY hdm_alerts.database;";
			
			
			$sql = "SELECT DISTINCT `hdm_alerts`.database 
			FROM `hdm_alerts` 
			WHERE `date` = (SELECT MAX(`date`) AS maxdate FROM `hdm_alerts`)
			ORDER BY `hdm_alerts`.database;";
			
				
			}
			else if($_SESSION['split-display-table'] == "True" ){

				if (@$_SESSION['seeAll'] == "False") {
											$sql1 = "SELECT hdm_alerts.table, hdm_alerts.alert_level, COUNT(*) AS count
			FROM hdm_alerts 
			WHERE `date` = (SELECT MAX(`date`) AS maxdate FROM hdm_alerts)
			and hdm_alerts.database='".$_SESSION['databaseName']."'
			GROUP BY hdm_alerts.table, hdm_alerts.alert_level
			ORDER BY hdm_alerts.table;";
			
			$sql = "SELECT DISTINCT `hdm_alerts`.table 
			FROM `hdm_alerts` 
			WHERE `date` = (SELECT MAX(`date`) AS maxdate FROM `hdm_alerts`)
			and hdm_alerts.database='".$_SESSION['databaseName']."'
			";
				}else{

				$sql1 = "SELECT hdm_alerts.table, hdm_alerts.alert_level, COUNT(*) AS count
			FROM hdm_alerts 
			WHERE `date` = (SELECT MAX(`date`) AS maxdate FROM hdm_alerts)
			
			GROUP BY hdm_alerts.table, hdm_alerts.alert_level
			ORDER BY hdm_alerts.table;";
			
			$sql = "SELECT DISTINCT `hdm_alerts`.table 
			FROM `hdm_alerts` 
			WHERE `date` = (SELECT MAX(`date`) AS maxdate FROM `hdm_alerts`)
			";
				}
			}

			$sth1 = $conn->prepare($sql1, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth1->execute();
			$resNum = $sth1->fetchAll(PDO::FETCH_ASSOC);

			$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute();
			$res = $sth->fetchAll(PDO::FETCH_NUM);
			
			
			if($_SESSION['split-display-database'] == "True" && (!isset($_POST['databaseName']) && !isset($_SESSION['databaseName']) ) ){
				$_POST['databaseName']=$res[0][0];
				$_SESSION['databaseName']=$res[0][0];
			}
			if($_SESSION['split-display-table'] == "True" && (!isset($_POST['tableName']) && !isset($_SESSION['tableName']) ) ){
				$_POST['tableName']=$res[0][0];
				$_SESSION['tableName']=$res[0][0];
			}
			
			if ($_SESSION['kibana2'] == "True") {
				?>
				<div class="col-lg-4">
					<?php echo('<iframe  src='.KIBANA_URL."/s/".KIBANA_NAMESPACE."/app/kibana#/dashboard/".KIBANA_HOME_DASHBOARD."?embed=true&_g=(refreshInterval%3A(pause%3A!t%2Cvalue%3A0)%2Ctime%3A(from%3Anow%2FM%2Cto%3Anow%2FM))".' height="2200px" width="100%"></iframe>') ?>
				</div>
				
				<div class="col-lg-8">
				
					<div class="row">
					
						<div class="col-lg-3">
							
							<div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical" style="max-height: 80vh;display: block;overflow: inherit;">
																	<nav aria-label="breadcrumb" >
									  <ol class="breadcrumb p-2" >
										<li class="breadcrumb-item"><a href="#">
											<button id="dbclickbutton" type="submit" value="<?php echo($_SESSION['split-display-database']) ?>" name="split-display-database" class='btn btn-<?php if($_SESSION["split-display-database"] == "True"){ echo("primary") ;} else {echo("light") ;} ?>'>Database</button>
										</a></li>
										<li class="breadcrumb-item"><a href="#">
											<button id="tableclickbutton"  type="submit" value="<?php echo($_SESSION['split-display-table']) ?>" name="split-display-table" class='btn btn-<?php if($_SESSION["split-display-table"] == "True"){ echo("primary") ;} else {echo("light") ;} ?>'>Table</button>
										</a></li>
									  </ol>

										<ol class="breadcrumb p-2" >
											<li class="breadcrumb-item" onclick="$('#dbclickbutton').click();"><a href="#">
											<?php echo($_SESSION['databaseName']) ?>
											</a></li>
											<li class="breadcrumb-item" ><a >
											<?php if($_SESSION['split-display-table'] == "True"){ echo($_SESSION['tableName']);} ?>
											</a></li>
										</ol>
									</nav>
								<?php
									$i = 0;
									foreach ($res as $row) {
										if($row[0] == ""){
											$row[0] = "None";
										}

										if($_SESSION['databaseName'] == $row[0] || $_SESSION['tableName'] == $row[0]){
											echo('<a class="nav-link active" id="v-pills-'.$row[0].'-tab"  role="tab" 	aria-controls="v-pills-'.$row[0].'">'.$row[0]);
										} else {
											echo('<a class="nav-link" id="v-pills-'.$row[0].'-tab"  role="tab" aria-controls="v-pills-'.$row[0].'">'.$row[0]);
										}

										printBadge($resNum,$row[0],$_SESSION['split-display-scope']);
										echo('</a>');
										$i++;
									}
								?>
							</div>
						</div>
						<div class="col-lg-9">
							<div class="tab-content" id="v-pills-tabContent">
				<?php } 
				else { ?>
						<div class="col-lg-12">
							<div class="row">
								<div class="col-lg-3">
									<div class="affix nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical" style="max-height: 80vh;display: block;overflow: inherit;">
									<nav aria-label="breadcrumb" >
									  <ol class="breadcrumb p-2" >
										<li class="breadcrumb-item"><a href="#">
											<button id="dbclickbutton" type="submit" value="<?php echo($_SESSION['split-display-database']) ?>" name="split-display-database" class='btn btn-<?php if($_SESSION["split-display-database"] == "True"){ echo("primary") ;} else {echo("light") ;} ?>'>Database</button>
										</a></li>
										<li class="breadcrumb-item"><a href="#">
											<button id="tableclickbutton"  type="submit" value="<?php echo($_SESSION['split-display-table']) ?>" name="split-display-table" class='btn btn-<?php if($_SESSION["split-display-table"] == "True"){ echo("primary") ;} else {echo("light") ;} ?>'>Table</button>
										</a></li>
									  </ol>

										<ol class="breadcrumb p-2" >
											<li class="breadcrumb-item" onclick="$('#dbclickbutton').click();"><a href="#">
											<?php if (@$_SESSION['seeAll'] == "False") {echo($_SESSION['databaseName']);}else{echo("all database");} ?>
											</a></li>
											<li class="breadcrumb-item" ><a >
											<?php if($_SESSION['split-display-table'] == "True"){ echo($_SESSION['tableName']);} ?>
											</a></li>
										</ol>
									</nav>
										<?php
											$i = 0;
											
											foreach ($res as $row) {
												if($row[0] == ""){
													$row[0] = "None";
												}

												if($_SESSION['databaseName'] == $row[0] || $_SESSION['tableName'] == $row[0]){
													echo('<a class="nav-link active" id="v-pills-'.$row[0].'-tab"  role="tab" 	aria-controls="v-pills-'.$row[0].'">'.$row[0]);
												} else {
													echo('<a class="nav-link" id="v-pills-'.$row[0].'-tab"  role="tab" aria-controls="v-pills-'.$row[0].'">'.$row[0]);
												}

												printBadge($resNum,$row[0],$_SESSION['split-display-scope']);
												echo('</a>');
												$i++;
											}
										?>
									</div>
								</div>
								<div class="col-lg-9">
									<div class="tab-content" id="v-pills-tabContent">
									<?php } 
										$orderbywarning="ORDER BY FIELD(`hdm_alerts`.alert_level, 'Haut', 'Warning', 'Info')";
										$orderbytable="ORDER BY FIELD( `hdm_alerts`.TABLE,'".$_SESSION['tableName']."',`hdm_alerts`.TABLE),`hdm_alerts`.TABLE,FIELD(`hdm_alerts`.alert_level, 'Haut', 'Warning', 'Info') ; ";
										
										$alldb="and `hdm_alerts`.database='".$_SESSION['databaseName']."' ";
										if (@$_SESSION['seeAll'] == "True") {
												$seeAll = True;
										}
										
										$sql = "SELECT `rule_basic`.*, `hdm_alerts`.* 
												FROM `hdm_alerts` 
												LEFT OUTER JOIN rule_basic 
												ON rule_basic.id_rule = hdm_alerts.`rule_id`
												WHERE `hdm_alerts`.date = (SELECT MAX(`date`) AS maxdate FROM hdm_alerts)
												 ";
										
										if (@$_SESSION['seeAll'] == "False") {
												$sql=$sql.$alldb;
										}
										//echo($sql);
										if($_SESSION['split-display-database'] == "True") {
											$sql=$sql.$orderbywarning;
										}
										else if($_SESSION['split-display-table'] == "True") {
											$sql=$sql.$orderbytable;
										}

										$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
										$sth->execute();
										$res = $sth->fetchAll(PDO::FETCH_ASSOC);
										$tableFooter = '</tbody></table></div>';

										if(!empty($res)) {

											$seeAll = False;
											if (@$_SESSION['seeAll'] == "True") {
												$seeAll = True;
											}
										
											$i = 0;

											foreach ($res as $row) {

												if($row[$_SESSION['split-display-scope']] == ""){
													$row[$_SESSION['split-display-scope']] = "None";
												}

												if(!isset($currentDatabase)) {
													$currentDatabase = $row[$_SESSION['split-display-scope']];
													echo(printHeader($row[$_SESSION['split-display-scope']],True));
													
												} else if ($row[$_SESSION['split-display-scope']] != $currentDatabase) {
													echo($tableFooter);
													$currentDatabase = $row[$_SESSION['split-display-scope']];
													echo(printHeader($row[$_SESSION['split-display-scope']],True));
												} 

												echo(writeRow($row));
												$i = $i + 1;
											}
										}
									?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</main>
<script type="text/javascript">
$( document ).ready(function() {
    $( ".nav-link" ).click(function() {

        let displayscope = "<?php    echo $_SESSION['split-display-scope']; ?>";

        if(displayscope==="database"){
            let databasename = this.id;
            databasename=databasename.replace("v-pills-","").replace("-tab","");
            $( "#databaseNameButton").val(databasename);
            $( "#databaseNameButton").click();
		}
		else if(displayscope==="table"){
            let tablename = this.id;
            tablename=tablename.replace("v-pills-","").replace("-tab","");
			$( "#tableNameButton").val(tablename);
			$( "#tableNameButton").click();
		}
	});
});
</script>
<?php
	// include_once($_SERVER['DOCUMENT_ROOT'].'/footer.php');
} else {
	include($_SERVER['DOCUMENT_ROOT'].'/login.php');
} ?>