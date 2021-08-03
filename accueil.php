<?php 	

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

$_SESSION['page'] = "home";

include_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/connect_db.php');

?>
<div class="container">
	<div class="row mt-4">
		<div class="col-lg-4">
			<div class="card p-3 bg-white rounded shadow-sm">
				<h3 class="border-bottom border-gray">Home Page</h3>
				<p>Cette page vous permet de voir en un coup d'oeil les fonctionnalités de Health Data Metrics et de pouvoir y accéder.</p>
			</div>
		</div>
		<div class="col-lg-4">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title"><i class="fas fa-search"></i> Explorer</h5>
					<p class="card-text">Explorez le contenu de vos bases de données à l'aide des métriques calculés sur elles.</p>
					<a href="/explorer/wrapper.php" class="btn btn-primary">> Explorer</a>
				</div>
				<div class="card-body">
					<h5 class="card-title"><i class="fas fa-pencil-alt"></i> Rule Editor</a></h5>
					<p class="card-text">Éditez des règles pour pouvoir être alerté en cas de données de mauvaises qualités.</p>
					<a href="/rule-editor/rule.php" class="btn btn-primary">> Rule Editor</a>
				</div>
				<div class="card-body">
					<h5 class="card-title"><i class="fas fa-exclamation-circle"></i> Alerts</a></h5>
					<p class="card-text">Consultez les alertes sur vos entrepôts de données.</p>
					<a href="/alert/alert.php" class="btn btn-primary">> Alerts</a>
				</div>
				<div class="card-body">
					<h5 class="card-title"><i class="fas fa-wave-square"></i> DB Compare</a></h5>
					<p class="card-text">Comparez deux bases de données ou deux version d'une base de donnée, à travers les métriques qui y sont calculés.</p>
					<a href="/db-compare/compare.php" class="btn btn-primary">> DB Compare</a>
				</div>
			</div>
		</div>
		<div class="col-lg-4">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title"><i class="fas fa-cogs"></i> Admin</a></h5>
					<p class="card-text">Administrez, monitorez, configurez l'application à l'aide de son panneau d'administration.</p>
					<a href="/admin.php" class="btn btn-primary">> Admin</a>
				</div>
				<div class="card-body">
					<h5 class="card-title"><i class="fas fa-question"></i> Help</a></h5>
					<p class="card-text">Besoin d'aide ? Retrouvez toutes les explications sur l'utilisation de l'application.</p>
					<a href="/help.php" class="btn btn-primary">> Help</a>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
	include_once('footer.php');
} else {
	include('login.php');
} 

?>