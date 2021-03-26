<?php 

$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
$ldap_conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['AUTH']['AUTH_LDAP_CONF_PATH']),true);

if ($conf['AUTH']['AUTH_MODE'] != "table") {
	?>
	<div class="alert alert-primary" role="alert">
		The Authentication mode is set to <?php echo $conf['AUTH']['AUTH_MODE']; ?>. You can't manage credentials locally. Please change the AUTH_MODE to "table" in your config file or contact your LDAP administrator.
	</div>
	<?php
} else {

# On réécrit les valeurs si elles ont été modifiées.
if (isset($_POST['editAuthConfig'])) {

	$ldap_conf["mode"] = $_POST['authMode'];

	file_put_contents($_SERVER['DOCUMENT_ROOT'].$conf['AUTH']['AUTH_LDAP_CONF_PATH'],json_encode($ldap_conf,JSON_PRETTY_PRINT));
	
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
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-12">

		</div>
	</div>
	<div class="row">
		<div class="col-lg-12">
			<p>Authentication config file path : <span class="badge badge-primary"><?php echo($conf['AUTH']['AUTH_LDAP_CONF_PATH']); ?></span></p>
		</div>
		<div class="col-lg-4">
			<form class="needs-validation" method="post" action="?tab=authentication">
				<fieldset class="border p-3 mb-4">
					<legend class="w-auto">Mode : </legend>
					<div class="alert alert-primary" role="alert">
						Notice : Table mode is not yet supported
					</div>
					<div class="form-group">
						<label for="sqlRequestValue">Select an authentication mode : </label>
						<select class="form-control" name="authMode">
							<option>LDAP</option>
							<option>Table</option>
						</select>
					</div>
				</fieldset>
				<input type="submit" name="editAuthConfig" value="Save Changes" class="btn btn-primary col-sm-12 col-lg-12">
			</form>
		</div>
	</div>
</div>

<?php } ?>