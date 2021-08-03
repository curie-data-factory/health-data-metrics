<?php 

$confFilePath = "/conf/appli/conf-appli.json";
$confFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$confFilePath), true);

# On réécrit les valeurs si elles ont été modifiées.
if (isset($_POST['editApplicationConfiguration'])) {
	foreach ($confFile as $key => $value) {
		foreach ($value as $subkey => $subvalue) {
			$confFile[$key][$subkey] = $_POST[$subkey];
		}
	}
	file_put_contents($_SERVER['DOCUMENT_ROOT'].$confFilePath,json_encode($confFile,JSON_PRETTY_PRINT));
	?>
	<div class="alert alert-success alert-dismissible fade show" role="alert">
	  Configuration sauvegardé !
	  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
	    <span aria-hidden="true">&times;</span>
	  </button>
	</div>
	<?php
}

?>
<form class="needs-validation" method="post" action="?tab=application">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<p>Application configuration file path : <span class="badge badge-primary"><?php echo($_SERVER['DOCUMENT_ROOT'].$confFilePath); ?></span></p>
			</div>
		</div>
		<input type="submit" name="editApplicationConfiguration" value="Save Changes" class="mb-2 btn btn-primary col-sm-12 col-lg-4">
		<div class="row">
	<?php 
		foreach ($confFile as $key => $value) {
			?>
			<div class="col-lg-6 col-md-12">
			<fieldset class="border p-3 mb-4">
				<legend class="w-auto"><?php echo $key ?> : </legend>
					<?php 
						// Zone d'information, adds information to specific configurations.
						switch ($key) {
							case 'EXPLORER':
							echo('
							<div class="alert alert-primary" role="alert">
								Notice : 
								<br> - KIBANA_URL : Url to Explorer UI (ex : https://kibana-url:5601).
								<br> - KIBANA_NAMESPACE : Kibana Namespace containing dashboards & data.
								<br> - KIBANA_HOME_DASHBOARD : Kibana dashboard for MP-basic field descriptions
								<br> - KIBANA_EXPLORATOR_DASHBOARD : Kibana dashboard for MP-basic Data overviews
								<br> - KIBANA_EXPLORATOR_INDEX : kibana index.
							</div>
								');
								break;
							case 'DB':
							echo('
							<div class="alert alert-primary" role="alert">
								Notice : 
								<br> - DB_CONF_PATH : Path to Database credentials.
								<br> - DB_CREATE_SCRIPT_PATH : Path to the core database Initialization script.
							</div>
								');
								break;
							case 'PACK':
							echo('
							<div class="alert alert-primary" role="alert">
								Notice : 
								<br> - PACK_METRIC_CONF_PATH : Path to metric pack configuration file.
								<br> - PACK_RULE_CONF_PATH : Path to rule pack configuration file.
							</div>
								');
								break;
							case 'AUTH':
							echo('
							<div class="alert alert-primary" role="alert">
								Notice : 
								<br> - AUTH_MODE : (table,ldap,none) : Local is not yet supported
								<br> - AUTH_LDAP_CONF_PATH : Path to json LDAP configuration file
							</div>
								');
								break;
							case 'PIPELINE':
							echo('
							<div class="alert alert-primary" role="alert">
								Notice : 
								<br> - PIPELINE_RUNNING_MODE : (local,docker-runner,airflow) : Docker runner & Airflow not yet supported
								<br> - AIRFLOW_URL : If PIPELINE_RUNNING_MODE==airflow : Airflow Endpoint URL
							</div>
								');
								break;
						}

						foreach ($value as $subkey => $subvalue) {
							switch ($subkey) {
								case 'NEXUS_PASSWORD':
								echo('
						<div class="form-group row">
							<label for="'.$subkey.'" class="col-lg-12 col-form-label"><b>'.$subkey.'</b></label>
							<div class="col-lg-12">
								<input type="password" class="form-control" id="'.$subkey.'"  name="'.$subkey.'" value="'.$subvalue.'">
							</div>
						</div>
									');
									break;
								default:
																echo('
						<div class="form-group row">
							<label for="'.$subkey.'" class="col-lg-12 col-form-label"><b>'.$subkey.'</b></label>
							<div class="col-lg-12">
								<input type="text" class="form-control" id="'.$subkey.'"  name="'.$subkey.'" value="'.$subvalue.'">
							</div>
						</div>
									');
									break;
							}
					} ?>
			</fieldset>
			</div>
			<?php
		}
	 ?>
		</div>	
	</div>
</form>
