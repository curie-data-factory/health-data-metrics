<?php

$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
$dataConfDb = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);
$ldap_conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $conf['AUTH']['AUTH_LDAP_CONF_PATH']), true);

if (isset($_SESSION['connected'])
AND in_array($ldap_conf['admin_ldap_authorization_domain'],$_SESSION['user_ids']['memberof'])) {

# On passe en variable d'env la conf
define('NEXUS_URL',$conf['PACK']['NEXUS_URL']);
define('NEXUS_API_URL',$conf['PACK']['NEXUS_API_URL']);
define('NEXUS_DEFAULT_REPOSITORY',$conf['PACK']['NEXUS_PACKS_ROOT_REPOSITORY']);
define('NEXUS_USER', $dataConfDb['hdm-nexus-creds']['user']);
define('NEXUS_PASSWORD', $dataConfDb['hdm-nexus-creds']['password']);

$hdmMetricPacks = getNexusContent("hdm.metricpacks");
$hdmDbList = getDbList($conn);
$hdmCorrList = getDbMpCorrList($conn);

// validation du formulaire de config des metricpack && modification dans la base
if (isset($_POST['mpconfigsubmit'])) {
	
	$query = $conn->prepare('INSERT INTO `hdm_pack_metric_conf` ( `id_config`, `pack_name`, `pack_version`,`pack_config` ) VALUES (:idconfig, :metricpack, :version, :configcontent) ON DUPLICATE KEY UPDATE pack_config = :configcontent;');

	// s'il s'agit d'une configuration spécifique à un mp + base on ajoute dans la table de correspondance
	if($_POST['dbkey'] != "" && $_POST['mpkey'] != ""){
	    if (!$query->execute(array(':idconfig' => $_POST["mpkey"].':'.$_POST["dbkey"],
	    						   ':metricpack' => $_POST["mpkey"],
						  		   ':version' => NULL,
						  		   ':configcontent' => base64_encode($_POST['mpconfigcontent'])))) {
			print_r($query->errorInfo());
		}

	// Sinon c'est une configuration de metric pack générale : 
	} else if($_POST['metricpack'] != "" && $_POST['version'] != "") {
	    if (!$query->execute(array(':idconfig' => $_POST["metricpack"].':'.$_POST["version"],
	    						   ':metricpack' => $_POST["metricpack"],
						  		   ':version' => $_POST["version"],
						  		   ':configcontent' => base64_encode($_POST['mpconfigcontent'])))) {
			print_r($query->errorInfo());
		}
	}

	?>
	<div class="alert alert-success alert-dismissible fade show" role="alert">
	  Configuration sauvegardé !
	  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
	    <span aria-hidden="true">&times;</span>
	  </button>
	</div>
	<?php
} elseif (isset($_POST['mpconfig'])) {
	// On va récupérer la conf dans la base : 

	$query = $conn->prepare('SELECT * FROM `hdm_pack_metric_conf` WHERE `id_config` = :idconfig;');

	// s'il s'agit d'une configuration spécifique à un mp + base
	if(isset($_POST['dbkey']) && isset($_POST['mpkey'])){

	    if (!$query->execute(array(':idconfig' => $_POST["mpkey"].':'.$_POST["dbkey"]))) {
			print_r($query->errorInfo());
		}

	// Sinon c'est une configuration de metric pack générale : 
	} else if(isset($_POST['metricpack']) && isset($_POST['version'])) {

	    if (!$query->execute(array(':idconfig' => $_POST["metricpack"].':'.$_POST["version"]))) {
			print_r($query->errorInfo());
		}

	}

	// on récupère la config, on vérifie qu'elle n'est pas nulle.
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	if (empty($res)) {
		$mpconfigraw = "";
	} else {
		$mpconfigraw = base64_decode($res[0]['pack_config']);
	}
}

// On modifie les entrées dans la table de correspondance MetricPack/Databases
if(isset($_POST["mpkey"]) 
	AND ($_POST["mpkey"] != "")
	AND isset($_POST["dbkey"]) 
	AND ($_POST["dbkey"] != "")
	AND !isset($_POST["mpconfig"])
	AND !isset($_POST["mpconfigsubmit"])){

	// Si on a check la box alors qu'elle est déjà check, cela signifie que l'on veut décocher la case (supprimer la ligne de la table)
	$checked = false;

	// Si la clé est déjà présente en base on uncheck la box
	foreach ($hdmCorrList as $CorrDbKey) {
		if(($CorrDbKey['db_key'] == $_POST["dbkey"])
			&& ($CorrDbKey['mp_key'] == $_POST["mpkey"])){
			$checked = true;
		}
	}

	if ($checked) {
		$query = $conn->prepare('DELETE FROM `hdm_core_table_corr_db_mp` 
			WHERE `mp_key` = :mpkey 
			AND `db_key` = :dbkey;');
    } else {
		$query = $conn->prepare('INSERT INTO `hdm_core_table_corr_db_mp` ( `mp_key`, `db_key` ) VALUES (:mpkey, :dbkey);');

    }
    if (!$query->execute(array(':mpkey' => $_POST["mpkey"],
                      ':dbkey' => $_POST["dbkey"]))) {
        print_r($query->errorInfo());
    }

    #dropping duplicates :
	$query = $conn->prepare('DELETE
							FROM hdm_core_table_corr_db_mp
							WHERE id NOT IN
							    (SELECT id
							     FROM
							       (SELECT MIN(id) AS id
							        FROM hdm_core_table_corr_db_mp
							        GROUP BY `mp_key`,
							                 `db_key` HAVING COUNT(*) >= 1) AS c);');
    if (!$query->execute()) {
		print_r($query->errorInfo());
	}
}

$hdmMetricPacks = getNexusContent("hdm.metricpacks");
$hdmDbList = getDbList($conn);
$hdmCorrList = getDbMpCorrList($conn);

// on va modifier la structure des données pour pouvoir agréger les affichages
$dataReMap = array();
if($hdmMetricPacks['items'] != NULL){
	foreach ($hdmMetricPacks['items'] as $value) {
	    $dataReMap[$value['name']][$value['version']] = $value;
	}
}

?>

<div class="container-fuild">
	<!-- 
	################################################################################
	Liste des Metrics Packs : 
	 -->
	 <div class="row">
	 	<div class="col-lg-6 m-0 alert alert-primary" role="alert">
			Notice : Liste des Metric Packs disponibles avec leurs versions.
		</div>
	 </div>
 	<div class="row p-2 m-2" style="background-color: #f9f9f9;">
 		<?php 
 		foreach ($dataReMap as $key => $value) {
 			?>
 			<div class="card">
 				<div class="card-body">
 					<!-- On affiche l'entête des ressources à partir du nom et du groupe -->
 					<h6 class="card-title"><?php echo($value[array_key_first($value)]['name']); ?></h6>
 					<h6 class="card-subtitle mb-2 text-muted"><?php echo($value[array_key_first($value)]['group']); ?></h6>

 					<ul style="padding-inline-start: 20px;">
 						<?php 
 						foreach ($value as $itemData) {
 							if($itemData["version"]) {
 								?><li>Version : <?php echo $itemData["version"]; ?>

 								<form method="post" action="?tab=metricpacks">
									<button type="submit" class="btn btn-primary">
										<i class="fas fa-wrench"></i>
									</button >
									<input type="hidden" name="metricpack" value="<?php echo($value[array_key_first($value)]['name']); ?>">
									<input type="hidden" name="version" value="<?php echo $itemData["version"]; ?>">
 									<input type="hidden" name="mpconfig" value="True">
								</form>

 								</li>
 								<?php
 							} 
 						}
 						?>
 					</ul>
 				</div>
 			</div>
 			<?php
 		}
 		?>
 	</div>

	<!-- 
	################################################################################
	Matrice de correspondance Metric Pack / Databases : 
	 -->
 	<div class="row">
	 	<div class="col-lg-8 mb-2 alert alert-primary" role="alert">
			Notice : Sélectionnez le(s) MP qui doivent s'éxecuter avec quelle base de donnée.
		</div>
 		<div class="col-12">
 			<table class="table table-bordered">
 				<thead>
 					<tr>
 						<th scope="col">Database</th>
 						<th scope="col">Type</th>
 						<th scope="col">Host</th>
 						<th scope="col">Port</th>
 						<th scope="col">User</th>
 						<th scope="col">SSL</th>
 						<?php
 						foreach ($dataReMap as $key => $value) {
 							if (!isset($tempvalue)
 								OR ($value != $tempvalue)) {
								echo("<th scope=\"col\">".$value[array_key_first($value)]['name']."</th>");
								$tempvalue = $value;
 							}
 						}
 						?>
 					</tr>
 				</thead>
 				<tbody>
 					<?php 
 					foreach ($hdmDbList as $db) {

						$dbkey = $db['db_name'].":".$db['db_type'].":".$db['db_host'].":".$db['db_port'].":".$db['db_user'].":".$db['db_is_ssl'];
	 					 ?>
		 					<tr>
		 						<td><?php echo($db['db_name']) ?></td>
		 						<td><?php echo($db['db_type']) ?></td>
		 						<td><?php echo($db['db_host']) ?></td>
		 						<td><?php echo($db['db_port']) ?></td>
		 						<td><?php echo($db['db_user']) ?></td>
		 						<td><?php echo($db['db_is_ssl']) ?></td>
		 						<?php
		 						foreach ($dataReMap as $key => $value) {

									$checked = false;
									// Si la clé est déjà présente en base on check la box
									foreach ($hdmCorrList as $CorrDbKey) {
										if(($CorrDbKey['db_key'] == $dbkey) 
											&& ($CorrDbKey['mp_key'] == $value[array_key_first($value)]['name'])){
											$checked = true;
										}
									}
	 								
 									?>
 									<td>
										<form method="POST" action="<?php echo($_SERVER['DOCUMENT_ROOT']) ?>/admin.php?tab=metricpacks" style="display: inline-block; vertical-align: middle;">
                                            <input type="checkbox" name="checkbox"  class="double" <?php if($checked) { echo "checked"; } ?> onChange="this.form.submit()">
                                            <input type="hidden" name="dbkey" value="<?php echo($dbkey) ?>">
		 									<input type="hidden" name="mpkey" value="<?php echo($value[array_key_first($value)]['name']) ?>">
										</form>
		 								<form method="post" action="?tab=metricpacks" style="display: inline-block; vertical-align: middle;">
											<button type="submit" class="btn btn-primary" style="top: -8px;margin-left: 5px;">
												<i class="fas fa-wrench"></i>
											</button >
		 									<input type="hidden" name="dbkey" value="<?php echo($dbkey) ?>">
		 									<input type="hidden" name="mpkey" value="<?php echo($value[array_key_first($value)]['name']) ?>">
		 									<input type="hidden" name="mpconfig" value="True">
										</form>
 									</td>
 									<?php
		 						}
		 						?>
		 					</tr>
						<?php 
					} ?>
 				</tbody>
 			</table>
 		</div>
 	</div>
</div>

<?php 

//////////////////////////////////////////////////////////
// Modal de modification de la configuration des metricpacks
// C'est un modal générique qui récupère les variables en post pour récupérer la conf en base, la décoder et l'afficher dans une textarea dans le modal.
// Si cette configuration est modifiée elle sera récupérée en post à l'envoit du formulaire, puis le contenu sera encodé en base64 pour être inséré en base avec la clé du metric pack correspondant.

if(isset($_POST['mpconfig'])) { ?>

	<div class="container">
		<div class="modal fade" id="mpconfigmodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel">Metric Pack Configuration</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="form-group">
						<form method="POST" action="<?php echo $_SERVER['DOCUMENT_ROOT'] ?>/admin.php?tab=metricpacks">
							<div class="modal-body">
								<span class="badge badge-success"><?php if(isset($_POST['metricpack'])) { echo($_POST['metricpack']); } ?></span>
								<span class="badge badge-success"><?php if(isset($_POST['mpkey'])) { echo($_POST['mpkey']); } ?></span>
								<span class="badge badge-success"><?php if(isset($_POST['dbkey'])) { echo($_POST['dbkey']); } ?></span>
								<span class="badge badge-success"><?php if(isset($_POST['version'])) { echo($_POST['version']); } ?></span>
								<br>
								<?php 
								if(empty($mpconfigraw)) {
									echo('<div class="mb-2 alert alert-primary" role="alert">
										Notice : Il n\'y a aucune configuration.
										</div>');
								}
								?>
								<label for="mpconfigtextarea">Configuration :</label>
								<textarea class="form-control" name="mpconfigcontent" id="mpconfigtextarea" rows="15"><?php if(!empty($mpconfigraw)) { print_r($mpconfigraw); } ?></textarea>
								<input type="hidden" name="dbkey" value="<?php if(isset($_POST['dbkey'])) { echo($_POST['dbkey']); } ?>">
								<input type="hidden" name="mpkey" value="<?php if(isset($_POST['mpkey'])) { echo($_POST['mpkey']); } ?>">
								<input type="hidden" name="metricpack" value="<?php if(isset($_POST['metricpack'])) { echo($_POST['metricpack']); } ?>">
								<input type="hidden" name="version" value="<?php if(isset($_POST['version'])) { echo($_POST['version']); } ?>">
								<input type="hidden" name="mpconfigsubmit" value="True">
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
								<button type="submit" class="btn btn-primary">Save changes</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			$(window).on('load',function(){
				$('#mpconfigmodal').modal('show');
			});
		</script>
	</div>
<?php }
} else {
    include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}
