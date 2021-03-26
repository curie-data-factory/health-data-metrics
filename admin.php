<?php 	

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

include_once($_SERVER['DOCUMENT_ROOT'].'/connect_db.php');

$_SESSION['page'] = "admin";

include($_SERVER['DOCUMENT_ROOT'].'/header.php');

$showAuth = false;
$showApp = false;
$showDb = false;
$showHealth = false;
$showjobs = false;
$showLog = false;
$showMetPacks = false;
$showRulePacks = false;

if(isset($_GET['tab'])) {
	$tab = $_GET['tab'];
	switch ($tab) {
		case 'authentication':
			$showAuth = true;
			break;
		case 'application':
			$showApp = true;
			break;
		case 'databases':
			$showDb = true;
			break;
		case 'health':
			$showHealth = true;
			break;
		case 'jobs':
			$showjobs = true;
			break;
		case 'log':
			$showLog = true;
			break;
		case 'metricpacks':
			$showMetPacks = true;
			break;
		case 'rulepacks':
			$showRulePacks = true;
			break;
	}
} else {
	$showApp = true;
}

?>
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-2 bg-white pt-3 pb-3">
			<div class="position-fixed" style="width:15%;">
			<div class="nav nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical" style="display:block;">
			  <a class="nav-link <?php if($showApp){ echo('active'); } ?>" id="v-pills-application-tab" data-toggle="pill" href="#v-pills-application" role="tab" aria-controls="v-pills-application" aria-selected="true"><i class="fas fa-columns"></i> Application</a>
			  <a class="nav-link <?php if($showAuth){ echo('active'); } ?>" id="v-pills-authentication-tab" data-toggle="pill" href="#v-pills-authentication" role="tab" aria-controls="v-pills-authentication" aria-selected="false"><i class="fas fa-fingerprint"></i> Authentication</a>
			  <a class="nav-link <?php if($showDb){ echo('active'); } ?>" id="v-pills-databases-tab" data-toggle="pill" href="#v-pills-databases" role="tab" aria-controls="v-pills-databases" aria-selected="false"><i class="fas fa-database"></i> Databases</a>
			  <a class="nav-link <?php if($showHealth){ echo('active'); } ?>" id="v-pills-health-check-tab" data-toggle="pill" href="#v-pills-health-check" role="tab" aria-controls="v-pills-health-check" aria-selected="false"><i class="fas fa-notes-medical"></i> Health Check</a>
			  <a class="nav-link <?php if($showMetPacks){ echo('active'); } ?>" id="v-pills-metric-packs-tab" data-toggle="pill" href="#v-pills-metric-packs" role="tab" aria-controls="v-pills-metric-packs" aria-selected="false"><i class="fas fa-square-root-alt"></i> Metric Packs</a>
			  <a class="nav-link <?php if($showRulePacks){ echo('active'); } ?>" id="v-pills-rule-packs-tab" data-toggle="pill" href="#v-pills-rule-packs" role="tab" aria-controls="v-pills-rule-packs" aria-selected="false"><i class="far fa-check-circle"></i> Rule Packs</a>
			  <a class="nav-link <?php if($showjobs){ echo('active'); } ?>" id="v-pills-job-pipelines-tab" target="_blank" href="https://dev-airflow-data.curie.net/graph?dag_id=hdm-pipeline"><i class="fas fa-play"></i> Job Pipelines</a>
			  <h1 class="border-bottom border-gray pb-2 mb-0"></h1>
			  <a class="nav-link <?php if($showLog){ echo('active'); } ?>" id="v-pills-log-tab" data-toggle="pill" href="#v-pills-log" role="tab" aria-controls="v-pills-log" aria-selected="false"><i class="fas fa-clipboard-list"></i> Log History</a>
			</div>
			</div>
		</div>
		<div class="col-lg-10 pt-3">
			<div class="container-fluid">
				<div class="row p-3 bg-white rounded shadow-sm">
					<div class="tab-content m-4" id="v-pills-tabContent" style="width:100%;">
						<div class="tab-pane <?php if($showApp){ echo('show active'); } ?>" id="v-pills-application" role="tabpanel" aria-labelledby="v-pills-application-tab">
							<h1 class="pb-2 mb-2">Application</h1>
							<p class="border-bottom border-gray pb-2 mb-4">
								This Page contains all the configurations for the front-end to work properly.
							</p>
							<?php include $_SERVER['DOCUMENT_ROOT'].'/admin/application.php' ?>
						</div>
						<div class="tab-pane <?php if($showAuth){ echo('show active'); } ?>" id="v-pills-authentication" role="tabpanel" aria-labelledby="v-pills-authentication-tab">
							<h1 class="border-bottom border-gray pb-2 mb-2">Authentication</h1>
							<?php include $_SERVER['DOCUMENT_ROOT'].'/admin/authentication.php' ?>
						</div>
						<div class="tab-pane <?php if($showHealth){ echo('show active'); } ?>" id="v-pills-health-check" role="tabpanel" aria-labelledby="v-pills-health-check-tab">
							<h1 class="border-bottom border-gray pb-2 mb-2">Health Check</h1>
							<?php include $_SERVER['DOCUMENT_ROOT'].'/admin/health_check.php' ?>
						</div>
						<div class="tab-pane <?php if($showMetPacks){ echo('show active'); } ?>" id="v-pills-metric-packs" role="tabpanel" aria-labelledby="v-pills-health-metric-packs">
							<h1 class="border-bottom border-gray pb-2 mb-2">Metric Packs</h1>
							<?php include $_SERVER['DOCUMENT_ROOT'].'/admin/metric_packs.php' ?>
						</div>
						<div class="tab-pane <?php if($showRulePacks){ echo('show active'); } ?>" id="v-pills-rule-packs" role="tabpanel" aria-labelledby="v-pills-health-rule-packs">
							<h1 class="border-bottom border-gray pb-2 mb-2">Rule Packs</h1>
							<?php include $_SERVER['DOCUMENT_ROOT'].'/admin/rule_packs.php' ?>
						</div>
						<div class="tab-pane <?php if($showDb){ echo('show active'); } ?>" id="v-pills-databases" role="tabpanel" aria-labelledby="v-pills-databases-tab">
							<h1 class="border-bottom border-gray pb-2 mb-2">Databases</h1>
							<?php include $_SERVER['DOCUMENT_ROOT'].'/admin/databases.php' ?>
						</div>
						<div class="tab-pane <?php if($showLog){ echo('show active'); } ?>" id="v-pills-log" role="tabpanel" aria-labelledby="v-pills-log-tab">
							<h1 class="border-bottom border-gray pb-2 mb-2">Logs</h1>
							<?php include $_SERVER['DOCUMENT_ROOT'].'/admin/log_history.php' ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php

	include $_SERVER['DOCUMENT_ROOT'].'/footer.php';

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}
?>