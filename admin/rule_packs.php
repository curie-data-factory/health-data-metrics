<?php 

$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
$dataConfDb = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);

$hdmRulePacks = getNexusContent("hdm.rulepacks");
$hdmDbList = getDbList($conn);
$hdmCorrList = getDbRpCorrList($conn);

// validation du formulaire de config des rulepack && modification dans la base
if (isset($_POST['rpconfigsubmit'])) {
	
	$query = $conn->prepare('INSERT INTO `hdm_pack_rule_conf` ( `id_config`, `pack_name`, `pack_version`,`pack_config` ) VALUES (:idconfig, :rulepack, :version, :configcontent) ON DUPLICATE KEY UPDATE pack_config = :configcontent;');

	// si il s'agit d'une configuration spécifique à un rp + base on ajoute dans la table de corespondance
	if($_POST['dbkey'] != "" && $_POST['rpkey'] != ""){
	    if (!$query->execute(array(':idconfig' => $_POST["rpkey"].':'.$_POST["dbkey"],
	    						   ':rulepack' => $_POST["rpkey"],
						  		   ':version' => NULL,
						  		   ':configcontent' => base64_encode($_POST['rpconfigcontent'])))) {
			print_r($query->errorInfo());
		}

	// Sinon c'est une configuration de metric pack générale : 
	} else if($_POST['rulepack'] != "" && $_POST['version'] != "") {
	    if (!$query->execute(array(':idconfig' => $_POST["rulepack"].':'.$_POST["version"],
	    						   ':rulepack' => $_POST["rulepack"],
						  		   ':version' => $_POST["version"],
						  		   ':configcontent' => base64_encode($_POST['rpconfigcontent'])))) {
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
} elseif (isset($_POST['rpconfig'])) {
	// On va récupérer la conf dans la base : 

	$query = $conn->prepare('SELECT * FROM `hdm_pack_rule_conf` WHERE `id_config` = :idconfig;');

	// si il s'agit d'une configuration spécifique à un rp + base
	if(isset($_POST['dbkey']) && isset($_POST['rpkey'])){

	    if (!$query->execute(array(':idconfig' => $_POST["rpkey"].':'.$_POST["dbkey"]))) {
			print_r($query->errorInfo());
		}

	// Sinon c'est une configuration de metric pack générale : 
	} else if(isset($_POST['rulepack']) && isset($_POST['version'])) {

	    if (!$query->execute(array(':idconfig' => $_POST["rulepack"].':'.$_POST["version"]))) {
			print_r($query->errorInfo());
		}

	}

	// on récupère la config, on vérifie qu'elle n'est pas nulle.
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	if (empty($res)) {
		$rpconfigraw = "";
	} else {
		$rpconfigraw = base64_decode($res[0]['pack_config']);
	}
}

// On modifie les entrées dans la table de correspondance rulepack/Databases
if(isset($_POST["rpkey"]) 
	AND ($_POST["rpkey"] != "")
	AND isset($_POST["dbkey"]) 
	AND ($_POST["dbkey"] != "")
	AND !isset($_POST["rpconfig"])
	AND !isset($_POST["rpconfigsubmit"])){

	// Si on a check la box alors qu'elle est déjà check, cela signifie que l'on veut décocher la case ( supprimer la ligne de la table )
	$checked = false;

	// Si la clé est déjà présente en base on uncheck la box
	foreach ($hdmCorrList as $CorrDbKey) {
		if(($CorrDbKey['db_key'] == $_POST["dbkey"])
			&& ($CorrDbKey['rp_key'] == $_POST["rpkey"])){
			$checked = true;
		}
	}

	if ($checked) {
		$query = $conn->prepare('DELETE FROM `hdm_core_table_corr_db_rp` 
			WHERE `rp_key` = :rpkey 
			AND `db_key` = :dbkey;');
	    if (!$query->execute(array(':rpkey' => $_POST["rpkey"],
						  ':dbkey' => $_POST["dbkey"]))) {
			print_r($query->errorInfo());
		}
	} else {	
		$query = $conn->prepare('INSERT INTO `hdm_core_table_corr_db_rp` ( `rp_key`, `db_key` ) VALUES (:rpkey, :dbkey);');

	    if (!$query->execute(array(':rpkey' => $_POST["rpkey"],
						  ':dbkey' => $_POST["dbkey"]))) {
			print_r($query->errorInfo());
		}
	}


	#dropping duplicates :
	$query = $conn->prepare('DELETE
							FROM hdm_core_table_corr_db_rp
							WHERE id NOT IN
							    (SELECT id
							     FROM
							       (SELECT MIN(id) AS id
							        FROM hdm_core_table_corr_db_rp
							        GROUP BY `rp_key`,
							                 `db_key` HAVING COUNT(*) >= 1) AS c);');
    if (!$query->execute()) {
		print_r($query->errorInfo());
	}
}

$hdmRulePacks = getNexusContent("hdm.rulepacks");
$hdmDbList = getDbList($conn);
$hdmCorrList = getDbrpCorrList($conn);

// on va modifer la structure des données pour pouvoir agréger les affichages
$dataReMap = array();
if($hdmRulePacks['items'] != NULL){
	foreach ($hdmRulePacks['items'] as $value) {
	    $dataReMap[$value['name']][$value['version']] = $value;
	}
}

?>

<div class="container-fuild">
	<!-- 
	################################################################################
	Liste des Rule Packs : 
	 -->
	 <div class="row">
	 	<div class="col-lg-6 m-0 alert alert-primary" role="alert">
			Notice : Liste des Rule Packs disponibles avec leurs versions.
		</div>
	 </div>
 	<div class="row p-2 m-2" style="background-color: #f9f9f9;">
 		<?php 
 		foreach ($dataReMap as $key => $value) {
 			?>
 			<div class="card">
 				<div class="card-body">
 					<!-- On affice l'entête des ressources à partir du nom et du groupe -->
 					<h6 class="card-title"><?php echo($value[array_key_first($value)]['name']); ?></h6>
 					<h6 class="card-subtitle mb-2 text-muted"><?php echo($value[array_key_first($value)]['group']); ?></h6>

 					<ul style="padding-inline-start: 20px;">
 						<?php 
 						foreach ($value as $itemData) {
 							if($itemData["version"]) {
 								?><li>Version : <?php echo $itemData["version"]; ?>

 								<form method="post" action="?tab=rulepacks">
									<button type="submit" class="btn btn-primary">
										<i class="fas fa-wrench"></i>
									</button >
									<input type="hidden" name="rulepack" value="<?php echo($value[array_key_first($value)]['name']); ?>">
									<input type="hidden" name="version" value="<?php echo $itemData["version"]; ?>">
 									<input type="hidden" name="rpconfig" value="True">
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
			Notice : Selectionnez le(s) rp qui doivent s'executer avec quelle base de donnée.
		</div>
 		<div class="col-12">
 			<table class="table table-bordered">
 				<thead>
 					<tr>
 						<th scope="col">Database</th>
 						<th scope="col">Host</th>
 						<th scope="col">Port</th>
 						<th scope="col">User</th>
 						<th scope="col">SSL</th>
 						<?php
 						foreach ($dataReMap as $key => $value) {
 							if (!isset($terpvalue)
 								OR ($value != $terpvalue)) {
								echo("<th scope=\"col\">".$value[array_key_first($value)]['name']."</th>");
								$terpvalue = $value;
 							}
 						}
 						?>
 					</tr>
 				</thead>
 				<tbody>
 					<?php 
 					foreach ($hdmDbList as $db) {

						$dbkey = $db['db_name'].":".$db['db_host'].":".$db['db_port'].":".$db['db_user'].":".$db['db_is_ssl'];
	 					 ?>
		 					<tr>
		 						<td scope="col"><?php echo($db['db_name']) ?></td>
		 						<td scope="col"><?php echo($db['db_host']) ?></td>
		 						<td scope="col"><?php echo($db['db_port']) ?></td>
		 						<td scope="col"><?php echo($db['db_user']) ?></td>
		 						<td scope="col"><?php echo($db['db_is_ssl']) ?></td>
		 						<?php
		 						foreach ($dataReMap as $key => $value) {

									$checked = false;
									// Si la clé est déjà présente en base on check la box
									foreach ($hdmCorrList as $CorrDbKey) {
										if(($CorrDbKey['db_key'] == $dbkey) 
											&& ($CorrDbKey['rp_key'] == $value[array_key_first($value)]['name'])){
											$checked = true;
										}
									}
	 								
 									?>
 									<td>
										<form method="POST" action="admin.php?tab=rulepacks" style="display: inline-block; vertical-align: middle;">
	 										<input type="checkbox" name="checkbox"  class="double" <?php if($checked) { echo "checked"; } ?> onChange="this.form.submit()">
		 									<input type="hidden" name="dbkey" value="<?php echo($dbkey) ?>">
		 									<input type="hidden" name="rpkey" value="<?php echo($value[array_key_first($value)]['name']) ?>">
										</form>
		 								<form method="post" action="?tab=rulepacks" style="display: inline-block; vertical-align: middle;">
											<button type="submit" class="btn btn-primary" style="top: -8px;margin-left: 5px;">
												<i class="fas fa-wrench"></i>
											</button >
		 									<input type="hidden" name="dbkey" value="<?php echo($dbkey) ?>">
		 									<input type="hidden" name="rpkey" value="<?php echo($value[array_key_first($value)]['name']) ?>">
		 									<input type="hidden" name="rpconfig" value="True">
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
// Modal de modification de la configuration des rulepacks
// C'est un modal générique qui récupère les variables en post pour récupérer la conf en base, la décoder et l'afficher dans une textarea dans le modal.
// Si cette configuration est modifié elle sera récupérée en post à l'envoit du formulaire, puis le contenus sera encodé en base64 pour être inséré en base avec la clé du metric pack correspondant.

if(isset($_POST['rpconfig'])) { ?>

	<div class="container">
		<div class="modal fade" id="rpconfigmodal" tabindex="-1" role="dialog" aria-labelledby="exarpleModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="exarpleModalLabel">Metric Pack Configuration</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="form-group">
						<form method="POST" action="admin.php?tab=rulepacks">
							<div class="modal-body">
								<span class="badge badge-success"><?php if(isset($_POST['rulepack'])) { echo($_POST['rulepack']); } ?></span>
								<span class="badge badge-success"><?php if(isset($_POST['rpkey'])) { echo($_POST['rpkey']); } ?></span>
								<span class="badge badge-success"><?php if(isset($_POST['dbkey'])) { echo($_POST['dbkey']); } ?></span>
								<span class="badge badge-success"><?php if(isset($_POST['version'])) { echo($_POST['version']); } ?></span>
								<br>
								<?php 
								if(empty($rpconfigraw)) {
									echo('<div class="mb-2 alert alert-primary" role="alert">
										Notice : Il n\'y a aucune configuration.
										</div>');
								}
								?>
								<label for="rpconfigtextarea">Configuration :</label>
								<textarea class="form-control" name="rpconfigcontent" id="rpconfigtextarea" rows="15"><?php if(!empty($rpconfigraw)) { print_r($rpconfigraw); } ?></textarea>
								<input type="hidden" name="dbkey" value="<?php if(isset($_POST['dbkey'])) { echo($_POST['dbkey']); } ?>">
								<input type="hidden" name="rpkey" value="<?php if(isset($_POST['rpkey'])) { echo($_POST['rpkey']); } ?>">
								<input type="hidden" name="rulepack" value="<?php if(isset($_POST['rulepack'])) { echo($_POST['rulepack']); } ?>">
								<input type="hidden" name="version" value="<?php if(isset($_POST['version'])) { echo($_POST['version']); } ?>">
								<input type="hidden" name="rpconfigsubmit" value="True">
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
				$('#rpconfigmodal').modal('show');
			});
		</script>
	</div>
<?php }  ?>
