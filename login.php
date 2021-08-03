<?php

session_start();

require __DIR__ . '/vendor/autoload.php';

$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);

switch ($conf['AUTH']['AUTH_MODE']) {
	case 'ldap':

$ldap_conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['AUTH']['AUTH_LDAP_CONF_PATH']),true);

$service_ldap_authorization_domain = $ldap_conf['service_ldap_authorization_domain'];

$ad = new \Adldap\Adldap();

$config = $ldap_conf['config'][0];
$ad->addProvider($config);
        try {
            $provider = $ad->getDefaultProvider();
        } catch (\Adldap\AdldapException $e) {
        }
        $failed = false;
$failed_message = "";

if(isset($_POST['login']) AND isset($_POST['password'])) {
	$username = $_POST['login'];
	$password = $_POST['password'];

	try {
        try {
            if ($provider->auth()->attempt($username, $password, $bindAsUser = true)) {

                // Retrieving data
                $search = $provider->search();
                try {

                    $record = $search->findByOrFail('samaccountname', $username);

                    $mail = null;
                    if ($record->mail[0]) {
                        $mail = $record->mail[0];
                    } else {
                        $mail = $record->userprincipalname[0];
                    }

                    $_SESSION['user_ids'] = array('displayname' => $record->displayname[0],
                        'samaccountname' => $record->samaccountname[0],
                        'mail' => $mail,
                        'memberof' => $record->memberof);
                } catch (Adldap\Models\ModelNotFoundException $e) {
                    // Record wasn't found!
                }

                // On check les credentials du User :
                foreach ($_SESSION['user_ids']['memberof'] as $key => $value) {

                    // Si on arrive à matcher une des authorizations avec celle du user on valide la connexion
                    if (strpos($service_ldap_authorization_domain, $value) !== false) {
                        // Authentification succeeded
                        $_SESSION['connected'] = true;
                        header('location:/index.php');
                    }
                }

                // Si on a parcouru toutes les credentials du User et qu'on a rien matché, alors on renvoie un refus de credentials :
                $failed = true;
                $failed_message = "You don't have enough rights.";

            } else {
                // Failed.
                $failed = true;
                $failed_message = "Wrong Login or Password.";
            }
        } catch (\Adldap\Auth\BindException $e) {
        } catch (\Adldap\Auth\PasswordRequiredException $e) {
        } catch (\Adldap\Auth\UsernameRequiredException $e) {
        }
    } catch (Adldap\Auth\UsernameRequiredException $e) {
	    // The user didn't supply a username.
	} catch (Adldap\Auth\PasswordRequiredException $e) {
	    // The user didn't supply a password.
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Health Data Metrics</title>
	<link rel="icon" href="/img/favicon.ico"/>
	<link href="/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<link href="/css/login.css" rel="stylesheet">
	<script src="/js/jquery-3.3.1.slim.min.js"></script>
	<script src="/js/popper.min.js"></script>
	<script src="/js/bootstrap.min.js"></script>
<!------ Include the above in your HEAD tag ---------->
</head>
<body>
<div class="container py-5">
	<div class="row">
		<div class="col-md-12">
			<div class="col-md-12 text-center mt-4 mb-4">
				<h1 id="title">Health Data Metrics</h1><p><img src="/img/favicon.ico" style="width: 70px;" alt="icon"></p>
				<p style="max-width: 500px;margin: auto;background-color: #f7f7f7;padding: 20px;">Helps Monitor Data Quality.</p>
			</div>
			<div class="row">
				<div class="col-md-6 mx-auto mt-4">
					<!-- form card login -->
					<div class="card rounded-0" id="login-form">
						<div class="card-header">
							<h3 class="mb-0">LDAP Login</h3>
						</div>
						<div class="card-body">
							<?php if ($failed) {
								echo('<div class="alert alert-danger" role="alert">'.$failed_message.'</div>');
							} ?>
							<form class="form" role="form" method="POST">
								<div class="form-group">
                                    <label for="login">Login : </label><input type="text" class="form-control form-control-lg rounded-0" name="login" id="login" placeholder="login" required>
								</div>
								<div class="form-group">
                                    <label for="password">Password :</label><input type="password" class="form-control form-control-lg rounded-0" name="password" id="password" placeholder="password" required>
								</div>
								<div>
									<label class="custom-control custom-checkbox">
										<a href="javascript:void('forgot-form-link');" class="forgot-form-link">Forgot Password</a>
									</label>
								</div>
								<button type="submit" class="btn btn-orange btn-lg float-right" id="btnLogin">Login</button>
							</form>
						</div>
					</div>
					<!-- /form card login end-->
					
					<!-- form card forgot -->
					<div class="card rounded-0" id="forgot-form">
						<div class="card-header">
							<h3 class="mb-0">Reset Password</h3>
						</div>
						<div class="card-body">
							<form class="form" role="form" autocomplete="off" novalidate="" method="POST">
								<div class="form-group">
									<label>Contacts : </label>
									<p>To edit your password, please go to Windows and reset your password.</p><br>
									<p>If you need help, please contact your LDAP administrator.</p>
								</div>
								<div>
									<label class="custom-control custom-checkbox">
										<a href="javascript:void('login-form-link');" class="login-form-link">< Back to Login Page </a>
									</label>
								</div>
								<div class="btn btn-orange btn-lg float-right" id="btnLogin">Reset Password</div>
							</form>
						</div>
					</div>
					<!-- /form card forgot end -->
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container">
	<div class="row">
		<div class="col-12">
			<footer>
				<br/>
				<p>
					Data Factory / Direction des données / Institut Curie - <?php 
					echo(date('Y'));

			        # ouverture du json
					$json_version = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/version/version.json');
					$json_version_data = json_decode($json_version);

					echo(" - Version : ".$json_version_data->version);                
					?>
				</p>
			</footer>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		$("#register-form").hide();
		$("#forgot-form").hide();	
		$(".register-form-link").click(function(){
			$("#login-form").slideUp(0);
			$("#forgot-form").slideUp(0)	
			$("#register-form").fadeIn(300);	
		});

		$(".login-form-link").click(function(){
			$("#register-form").slideUp(0);
			$("#forgot-form").slideUp(0);	
			$("#login-form").fadeIn(300);	
		});

		$(".forgot-form-link").click(function(){
			$("#login-form").slideUp(0);	
			$("#forgot-form").fadeIn(300);	
		});
	});
</script>
</body>
</html>

<?php
		break;
	
	case 'none':
	    $_SESSION['connected'] = true;
		header('location:index.php');
		break;
} ?>