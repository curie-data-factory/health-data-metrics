<?php 
	include_once($_SERVER['DOCUMENT_ROOT'].'/core.php');
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Health Data Metrics</title>
	<link rel="icon" href="/img/favicon.ico"/>

	<link rel="stylesheet" href="/css/bootstrap.min.css">
	<link rel="stylesheet" href="/css/all.min.css">
	<link rel="stylesheet" href="/css/custom.css">

	<script type="text/javascript" src="/js/jquery-3.3.1.slim.min.js"></script>
	<script type="text/javascript" src="/js/popper.min.js"></script>
	<script type="text/javascript" src="/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="/js/all.min.js"></script>
</head>

<body class="bg-light">
	<nav  class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top first-row" style="font-size: 1rem;padding: 5px 13px 4px 13px;"><img src="/img/favicon.ico"  width="30" height="30" class="d-inline-block align-top" alt="icon"/> <h3 class="m-0 mr-2 ml-2 navbar-brand">Health Data Metrics</h3>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarsExampleDefault">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item ml-auto">
					<a class="navbar-brand">
						<span class="badge badge-secondary">
							<?php echo($_SESSION['user_ids']['displayname']) ?>
						</span>
					</a>
				</li>
			</ul>
			<ul class="navbar-nav mr-auto">
				<li class="nav-item">
					<a class='nav-link <?php if($_SESSION["page"] == "home"){echo("active");} ?>' href="/index.php"><i class="fas fa-home"></i> Home</a>
				</li>
				<li class="nav-item">
					<a class='nav-link <?php if($_SESSION["page"] == "explorer"){echo("active");} ?>' href="/explorer/wrapper.php"><i class="fas fa-search"></i> Explorer</a>
				</li>
				<li class="nav-item">
					<a class='nav-link <?php if($_SESSION["page"] == "rule"){echo("active");} ?>' href="/rule-editor/rule.php"><i class="fas fa-pencil-alt"></i> Rule Editor</a>
				</li>
				<li class="nav-item">
					<a class='nav-link <?php if($_SESSION["page"] == "alert"){echo("active");} ?>' href="/alert/alert.php"><i class="fas fa-exclamation-circle"></i> Alerts</a>
				</li>
				<li class="nav-item">
					<a class='nav-link <?php if($_SESSION["page"] == "mail"){echo("active");} ?>' href="/mail/mail.php"><i class="far fa-envelope"></i> Mail</a>
				</li>
			</ul>
			<ul class="navbar-nav ml-1 mr-2">
                <?php
                    $conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);

                    switch ($conf['AUTH']['AUTH_MODE']) {
                        case 'ldap':
                            $ldap_conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $conf['AUTH']['AUTH_LDAP_CONF_PATH']), true);

                            if (in_array($ldap_conf['admin_ldap_authorization_domain'],$_SESSION['user_ids']['memberof'])){
                                ?>
                                <li class="nav-item" >
                                    <a class='nav-link <?php if($_SESSION["page"] == "admin"){echo("active");} ?>' href = "/admin.php" ><i class="fas fa-cogs" ></i > Admin</a >
                                </li >
                                <?php
                            }
                        break;
                        case 'none':
                        	?>
                                <li class="nav-item" >
                                    <a class='nav-link <?php if($_SESSION["page"] == "admin"){echo("active");} ?>' href = "/admin.php" ><i class="fas fa-cogs" ></i > Admin</a >
                                </li >
                                <?php
                        break;
                    }
                ?>
                <li class="nav-item">
                <a class='nav-link <?php if($_SESSION["page"] == "help"){echo("active");} ?>' href="/help.php"><i class="fas fa-question"></i> Help</a>
				</li>
			</ul>
			<form class="form-inline my-2 my-lg-0">
				<a class="btn btn-danger" href="/logout.php">Déconnexion</a>
			</form>
		</div>
	</nav>