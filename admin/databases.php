<?php

$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
$dataConfDb = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);

$dbCore = $dataConfDb['hdm-core-database'];
$dbSQLMetrics = $dataConfDb['hdm-sql-database'];
$dbNOSQLMetrics = $dataConfDb['hdm-nosql-database'];

# Ajout des données dans le fichier de configuration.
if (isset($_POST['addDatabase'])
	AND isset($_POST['type'])
	AND isset($_POST['host'])
	AND isset($_POST['port'])
	AND isset($_POST['user'])
	AND isset($_POST['password'])
	AND isset($_POST['database'])
	AND isset($_POST['ssl'])) {

	if(($_POST['host'] != "")
		AND ($_POST['type'] != "")
		AND ($_POST['port'] != "")
		AND ($_POST['user'] != "")
		AND ($_POST['password'] != "")
		AND ($_POST['database'] != "")
		AND ($_POST['ssl'] != "")) {

		$tempArr = array("user" => $_POST['user'],
			"password" => $_POST['password'],
			"type" => $_POST['type'],
			"host" => $_POST['host'],
			"port" => $_POST['port'],
			"database" => $_POST['database'],
			"ssl" => $_POST['ssl']);
		array_push($dataConfDb['hdm-scanned-database'],$tempArr);
		file_put_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH'], json_encode($dataConfDb,JSON_PRETTY_PRINT));

		# on synchronise avec la table aussi
		$dataConfDb = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);
		$dbTarget = $dataConfDb['hdm-scanned-database'];

		SyncTable($conn,$dbTarget);
		?>
		<div class="alert alert-success alert-dismissible fade show" role="alert">
		  Ajout Effectué.
		  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
		    <span aria-hidden="true">&times;</span>
		  </button>
		</div>
		<?php
	} else {
		?>
		<div class="alert alert-danger" role="alert">
			Erreur, formulaire incomplet ou problème dans les données.
		</div>
		<?php
	}
} elseif (isset($_POST['dropDatabase'])) {
	var_dump($_POST);
}


$dataConfDb = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);
$dbTarget = $dataConfDb['hdm-scanned-database'];

# Synchronisation conf/table
if(isset($_POST['runSyncFileToDb'])){
	SyncTable($conn,$dbTarget);
	?>
	<div class="alert alert-success alert-dismissible fade show" role="alert">
	  Synchronisation faite.
	  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
	    <span aria-hidden="true">&times;</span>
	  </button>
	</div>
	<?php
}

if (isset($_POST['runCreateDb'])) {
	?>
	<div class="alert alert-success" role="alert">
	<?php
		include $_SERVER['DOCUMENT_ROOT']."/admin/create_db.php";
	 ?>
	 	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
	    <span aria-hidden="true">&times;</span>
	  </button>
	</div>
	<?php
}

?>
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<p>Database connexion config file path : <span class="badge badge-primary"><?php echo($conf['DB']['DB_CONF_PATH']); ?></span></p>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-4">
			<form method="post" action="?tab=databases">
				<input type="submit" name="runCreateDb" value="Launch db hdm script creator" class="btn btn-primary">
			</form><br/>
			<fieldset class="border p-3 mb-4">
				<legend class="w-auto">HDM Core database :
					<a href="#" data-toggle="tooltip" title="Notice : The Core database is the database containing sensitive & essential configurations & data."><i class="fas fa-question-circle"></i></a></legend>
					<p>Database: <b><?php echo($dbCore['database']) ?></b><br>
						Type: <b><?php echo($dbCore['type']) ?></b><br>
						Host: <b><?php echo($dbCore['host']) ?></b><br>
						Port: <b><?php echo($dbCore['port']) ?></b><br>
						User: <b><?php echo($dbCore['user']) ?></b><br>
						SSL: <b><?php echo($dbCore['ssl']) ?></b></p>
			</fieldset>
			<fieldset class="border p-3 mb-4">
				<legend class="w-auto">HDM Metric NO-SQL database :
					<a href="#" data-toggle="tooltip" title="Notice : The NO-SQL Metric database is the database containing metrics & dashboards & analytics views."><i class="fas fa-question-circle"></i></a></legend>
					<p>Database: <b><?php // echo($dbNOSQLMetrics['database']) ?></b><br>
						Type: <b><?php echo($dbNOSQLMetrics['type']) ?></b><br>
						Host: <b><?php echo($dbNOSQLMetrics['host']) ?></b><br>
						Port: <b><?php echo($dbNOSQLMetrics['port']) ?></b><br>
						User: <b><?php echo($dbNOSQLMetrics['user']) ?></b><br>
						SSL: <b><?php echo($dbNOSQLMetrics['ssl']) ?></b></p>
			</fieldset>
			<fieldset class="border p-3 mb-4">
				<legend class="w-auto">HDM Metric SQL database :
					<a href="#" data-toggle="tooltip" title="Notice : The SQL Metric database is the database containing metrics/rules/alerts & more data. By default it is the same as the core database but it can be decoupled, for security and/or volumetric reasons."><i class="fas fa-question-circle"></i></a></legend>
					<p>Database: <b><?php echo($dbSQLMetrics['database']) ?></b><br>
						Type: <b><?php echo($dbSQLMetrics['type']) ?></b><br>
						Host: <b><?php echo($dbSQLMetrics['host']) ?></b><br>
						Port: <b><?php echo($dbSQLMetrics['port']) ?></b><br>
						User: <b><?php echo($dbSQLMetrics['user']) ?></b><br>
						SSL: <b><?php echo($dbSQLMetrics['ssl']) ?></b></p>
			</fieldset>
			<a href="<?php echo($_SERVER['DOCUMENT_ROOT'].'/admin/create') ?>"></a>
		</div>
		<div class="col-lg-8">
			<legend class="w-auto">Scanned databases :
				<a href="#" data-toggle="tooltip" title="Notice : Scanned databases are the databases that can be scanned from the different Metric-Packs. If you want to edit specific Metric-Packs configurations, go to the [Metric Packs] Admin tab."><i class="fas fa-question-circle"></i></a></legend>
			<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#adddatabase">
				<i class="fas fa-plus"></i> Add database
			</button>
			<form method="post" action="?tab=databases" style="display: inline;">
				<input type="submit" name="runSyncFileToDb" value="Sync db-config File to HDM's Table [Database List]" class="btn btn-primary">
			</form>
			<table class="table table-striped mt-2">
				<thead class="thead-dark">
					<tr>
						<th scope="col">Database</th>
						<th scope="col">Type</th>
						<th scope="col">Host</th>
						<th scope="col">Port</th>
						<th scope="col">User</th>
						<th scope="col">SSL</th>
						<th scope="col">Edit</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($dbTarget as $db) {
						?>
						<tr>
							<td scope="row"><?php echo($db['database']) ?></td>
							<td><?php echo($db['type']) ?></td>
							<td><?php echo($db['host']) ?></td>
							<td><?php echo($db['port']) ?></td>
							<td><?php echo($db['user']) ?></td>
							<td><?php echo($db['ssl']) ?></td>
							<td>
								<form metdod="post" action="?tab=databases" style="display: inline-block;">
									<button type="submit" class="btn btn-primary">
										<i class="fas fa-pen"></i>
									</button>
									<input type="hidden" name="dbType" value="<?php echo($db['type']) ?>">
									<input type="hidden" name="dbHost" value="<?php echo($db['host']) ?>">
									<input type="hidden" name="dbPort" value="<?php echo($db['port']) ?>">
									<input type="hidden" name="dbUser" value="<?php echo($db['user']) ?>">
									<input type="hidden" name="dbSsl" value="<?php echo($db['ssl']) ?>">
									<input type="hidden" name="editDatabase" value="True">
								</form>
								<form metdod="post" action="?tab=databases" style="display: inline-block;">
									<button type="submit" class="btn btn-danger">
										<i class="fas fa-trash-alt"></i>
									</button>
									<input type="hidden" name="dbType" value="<?php echo($db['type']) ?>">
									<input type="hidden" name="dbHost" value="<?php echo($db['host']) ?>">
									<input type="hidden" name="dbPort" value="<?php echo($db['port']) ?>">
									<input type="hidden" name="dbUser" value="<?php echo($db['user']) ?>">
									<input type="hidden" name="dbSsl" value="<?php echo($db['ssl']) ?>">
									<input type="hidden" name="dropDatabase" value="True">
								</form>
							</td>
						</tr>
						<?php

						// ajout du modal de modification

						?>
						<form method="post" action="?tab=databases">
							<div class="modal" id="editDatabase" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title" id="exampleModalLabel">Edit Database</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
										<div class="modal-body">
											<div class="form-group row">
												<label for="type" class="col-lg-4 col-form-label"><b>Type</b></label>
												<div class="col-lg-8">
													<input type="text" required class="form-control" id="type" name="type" placeholder="mysql">
												</div>
											</div>
											<div class="form-group row">
												<label for="host" class="col-lg-4 col-form-label"><b>Host</b></label>
												<div class="col-lg-8">
													<input type="text" required class="form-control" id="host" name="host" placeholder="mysql.host.ip">
												</div>
											</div>
											<div class="form-group row">
												<label for="port" class="col-lg-4 col-form-label"><b>Port</b></label>
												<div class="col-lg-8">
													<input type="text" required class="form-control" id="port" name="port" placeholder="3306">
												</div>
											</div>
											<div class="form-group row">
												<label for="user" class="col-lg-4 col-form-label"><b>User</b></label>
												<div class="col-lg-8">
													<input type="text" required class="form-control" id="user" name="user" placeholder="hdm_user">
												</div>
											</div>
											<div class="form-group row">
												<label for="password" class="col-lg-4 col-form-label"><b>Password</b></label>
												<div class="col-lg-8">
													<input type="password" required class="form-control" id="password" name="password" placeholder="******">
												</div>
											</div>
											<div class="form-group row">
												<label for="database" class="col-lg-4 col-form-label"><b>Database</b></label>
												<div class="col-lg-8">
													<input type="text" required class="form-control" id="database" name="database" placeholder="database name">
												</div>
											</div>
											<div class="form-group row">
												<label for="ssl" class="col-lg-4 col-form-label"><b>SSL</b></label>
												<div class="col-lg-8">
													<input type="text" required class="form-control" id="ssl" name="ssl" placeholder="true/false">
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
											<input type="submit" class="btn btn-primary" name="addDatabase" value="Add">
										</div>
									</div>
								</div>
							</div>
						</form>
					<?php
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Modal -->
<form method="post" action="?tab=databases">
	<div class="modal" id="adddatabase" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Add Database</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group row">
						<label for="type" class="col-lg-4 col-form-label"><b>Type</b></label>
						<div class="col-lg-8">
							<input type="text" required class="form-control" id="type" name="type" placeholder="mysql">
						</div>
					</div>
					<div class="form-group row">
						<label for="host" class="col-lg-4 col-form-label"><b>Host</b></label>
						<div class="col-lg-8">
							<input type="text" required class="form-control" id="host" name="host" placeholder="mysql.host.ip">
						</div>
					</div>
					<div class="form-group row">
						<label for="port" class="col-lg-4 col-form-label"><b>Port</b></label>
						<div class="col-lg-8">
							<input type="text" required class="form-control" id="port" name="port" placeholder="3306">
						</div>
					</div>
					<div class="form-group row">
						<label for="user" class="col-lg-4 col-form-label"><b>User</b></label>
						<div class="col-lg-8">
							<input type="text" required class="form-control" id="user" name="user" placeholder="hdm_user">
						</div>
					</div>
					<div class="form-group row">
						<label for="password" class="col-lg-4 col-form-label"><b>Password</b></label>
						<div class="col-lg-8">
							<input type="password" required class="form-control" id="password" name="password" placeholder="******">
						</div>
					</div>
					<div class="form-group row">
						<label for="database" class="col-lg-4 col-form-label"><b>Database</b></label>
						<div class="col-lg-8">
							<input type="text" required class="form-control" id="database" name="database" placeholder="database name">
						</div>
					</div>
					<div class="form-group row">
						<label for="ssl" class="col-lg-4 col-form-label"><b>SSL</b></label>
						<div class="col-lg-8">
							<input type="text" required class="form-control" id="ssl" name="ssl" placeholder="true/false">
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<input type="submit" class="btn btn-primary" name="addDatabase" value="Add">
				</div>
			</div>
		</div>
	</div>
</form>

<script type="text/javascript">
	$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>
