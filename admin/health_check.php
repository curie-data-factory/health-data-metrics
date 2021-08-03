<a href="?tab=health" class="btn btn-primary">Refresh</a>
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-8 p-3">
			<h3>Version check :</h3>
			<?php 

			# check applications versions
			$apacheversion = apache_get_version();
			$phpversion = phpversion();

			?>
			<div class="alert mb-0 p-0" role="alert">
				Version Apache : <span class="badge badge-primary"><?php echo($apacheversion); ?></span> recommended : 2.4.38 or more<br>
				Version PHP : <span class="badge badge-primary"><?php echo($phpversion); ?></span> recommended : 7.4.4 or more<br>
			</div>

			<?php

			# Getting MySQL Metrics database version
			$sql = 'SELECT VERSION()';
			$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute();
			$res = $sth->fetchAll(PDO::FETCH_ASSOC);

			$mysqlversion = $res[0]["VERSION()"];
 			
 			?>
			<div class="alert mb-0 p-0" role="alert">
				Version MySQL : <span class="badge badge-primary"><?php echo($mysqlversion); ?></span> recommended : 8.0.18 or more
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-8 p-3">
			<h3>Basic Database check :</h3>
			<?php 
			# check table created
			$tableChecks = ["hdm_core_dblist","hdm_pack_metric_conf","hdm_pack_rule_conf","hdm_core_table_corr_db_mp","hdm_core_table_corr_db_rp"];
			foreach ($tableChecks as $table) {
				$sql = "SELECT * FROM ".$table." LIMIT 1";
				$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
				$sth->execute();
				$res = $sth->fetchAll(PDO::FETCH_ASSOC);
				$testTable = false;
				if (count($res) >= 1) {
					$testTable = true;
					?><div class="alert alert-success mb-0 p-2" role="alert">Table [<?php echo($table); ?>] exist and is not empty.</div><?php
				} else {
					?><div class="alert alert-danger mb-0 p-2" role="alert">Table [<?php echo($table); ?>] doesn't exist or is empty. <b>Recommended to go to [Databases] > [Launch db hdm script creator]</b></div><?php
				}
			}
			?>
		</div>
	</div>
</div>

