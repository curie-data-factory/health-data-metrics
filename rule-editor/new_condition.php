<!-- MODEL -->
<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

$varType = "";	

if (!isset($_SESSION['form-step'])) {
	$_SESSION['form-step'] = 0;
	$_SESSION['alertClass'] = "Conditionnelle";
	$_SESSION['alertLevel'] = "Haut";
	$_SESSION['alertMessage'] = "";
    $scope = "";
    $_SESSION['alertScope'] = $scope;
}

if((isset($_POST['newConditionColumn']) || isset($_SESSION['form-step'])) && (isset($_SESSION['metrics']))) {
	switch ($_SESSION['metrics']['infered_data_type']) {
		case 'string':
            switch (@$_SESSION['metrics']['is_categorical']) {
                case 1:
                $varType = "Catégorique";
                break;
                default:
                $varType = "Texte Non Catégorique";
                break;
            }
		break;
		case 'date':
		    $varType = "Date";
		break;
		case 'floating':
		    $varType = "Numérique décimale";
		break;
		case 'integer':
		    $varType = "Numérique Entier";
		break;
		case 'empty':
		    $varType = "NULL";
		break;
	}
}

if (isset($_POST['conditionRequest'])) {
	$_SESSION['form-step'] = 1;
	$_SESSION['ruleType'] = "conditionnelle";
}

if(isset($_POST['sqlRequest'])) {
	$_SESSION['form-step'] = 1;
	$_SESSION['ruleType'] = "sql";
}

if (isset($_POST['nextSQLConstruct'])) {
	$_SESSION['form-step'] = 2;
}

if (isset($_POST['nextTrigger'])) {
	$_SESSION['form-step'] = 3;
}

if (isset($_POST['alertClass'])) {
	$_SESSION['alertClass'] = $_POST['alertClass'];
}

if (isset($_POST['alertLevel'])) {
	$_SESSION['alertLevel'] = $_POST['alertLevel'];
}

if (isset($_POST['sqlRequestValue'])) {
	$_SESSION['sqlRequestValue'] = $_POST['sqlRequestValue'];
}

if (isset($_POST['ruleName'])) {
	$_SESSION['ruleName'] = $_POST['ruleName'];
}

if (isset($_POST['alertMessage'])) {
	$_SESSION['alertMessage'] = $_POST['alertMessage'];
}

if (isset($_POST['conditionTrigger'])) {
	$_SESSION['conditionTrigger'] = $_POST['conditionTrigger'];
}

if (isset($_POST['nextConditionnalQuery'])) {
	if($_SESSION['conditionScope'] == "value") {
		$_SESSION['metric'] = $_POST['table']."/".$_POST['column'];
	} else{
		$_SESSION['metric'] = $_POST['metric'];
	}
	$_SESSION['condition'] = $_POST['condition'];
	$_SESSION['conditionValue'] = $_POST['conditionValue'];
	$_SESSION['form-step'] = 2;
}

?>
<!-- VUE -->
<form action="#" method="post" class="form-inline pb-1 mb-3"><input type="submit" name="cancelRule" value="Annuler" class="btn btn-danger">
	<div class="alert alert-primary p-1 ml-2 mb-0" role="alert">Périmètre de la règle : <?php echo $_SESSION['alertScope']; ?></div>
	<?php if(isset($_GET['column']) && (isset($_POST['newConditionColumn']) || isset($_SESSION['form-step'])))
	{
		echo ('<div class="alert alert-secondary p-1 ml-2 mb-0" role="alert">Type de variable : '.$varType.'</div>');
	} ?>
</form>
<div class="col-lg-12 bg-white rounded shadow-sm p-3">
	<form class="needs-validation" method="post" action="#">
		<fieldset class="border p-3 mb-1">
			<legend  class="w-auto">1. Definition de la règle qui déclenche l'alerte : </legend>
			<div class="form-group">
				<label for="alertMessage">Nom de la règle :</label>
                <label for="ruleName"></label><textarea required class="form-control" id="ruleName" name="ruleName" rows="1"><?php echo @$_SESSION['ruleName']; ?></textarea>
				<div class="invalid-tooltip">
					Please provide a valid zip.
				</div>
			</div>
			<div class="form-group">
				<input type="submit" name="sqlRequest" value="Règle par Requête SQL" class="btn btn-primary">
				<input type="submit" name="conditionRequest" value="Regle Conditionnelle" class="btn btn-primary">
			</div>
		</fieldset>
	</form>
	<?php if($_SESSION['form-step'] >= 1) {

		if ($_SESSION['ruleType'] == "conditionnelle") {
			?>
			<form class="needs-validation" method="post" action="#">
				<fieldset class="border p-3 mb-1">
					<legend  class="w-auto">2. Construction : </legend>
					<div class="form-group">
						<?php @include 'conditionnelle.php'; ?>
					</div>
					<input type="submit" name="nextConditionnalQuery" value="Enregistrer la requête conditionnelle" class="btn btn-primary col-sm-12 col-lg-4"
					<?php if(!isset($_SESSION['conditionScope'])) { echo "disabled";} ?>>
				</fieldset>
			</form>
			<?php
		} elseif ($_SESSION['ruleType'] == "sql") {
		?>
		<form class="needs-validation" method="post" action="#">
			<fieldset class="border p-3 mb-1">
				<legend  class="w-auto">2. Construction : </legend>
				<div class="form-group">
					<label for="sqlRequestValue">Votre requête SQL : </label>
					<textarea required class="form-control" id="sqlRequestValue" name="sqlRequestValue" rows="5"><?php echo @$_SESSION['sqlRequestValue']; ?></textarea>
				</div>
				<input type="submit" name="nextSQLConstruct" value="Enregistrer la requête SQL" class="btn btn-primary col-sm-12 col-lg-4">
			</fieldset>
		</form>
	<?php }	
	} 

	if($_SESSION['form-step'] >= 2) {
	?>
	<form class="needs-validation" method="post" action="#">
		<fieldset class="border p-3 mb-1">
			<legend  class="w-auto">3. Condition de déclenchement de l'alerte : </legend>
			<?php 
				if($_SESSION['ruleType'] == "sql") { ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="conditionTrigger" id="inlineRadio1" value="returnNone" <?php if (@!isset($_SESSION['conditionTrigger'])) {	echo ('checked');} if (@$_SESSION['conditionTrigger'] == "returnNone") {
							echo ('checked');} ?>>
						<label class="form-check-label" for="inlineRadio1">Si la requête ne renvoie rien</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="conditionTrigger" id="inlineRadio2" value="returnNotNull" <?php if (@$_SESSION['conditionTrigger'] == "returnNotNull") {
							echo ('checked');} ?>>
						<label class="form-check-label" for="inlineRadio2">Si la requête renvoie quelque chose</label>
					</div><br/>
				<?php } 
				elseif ($_SESSION['ruleType'] == "conditionnelle") {
					?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="conditionTrigger" id="inlineRadio1" value="returnTrue" <?php if (@!isset($_SESSION['conditionTrigger'])) {	echo ('checked');} if (@$_SESSION['conditionTrigger'] == "returnTrue") {
							echo ('checked');} ?>>
						<label class="form-check-label" for="inlineRadio1">Si la condition est vrai</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="conditionTrigger" id="inlineRadio2" value="returnFalse" <?php if (@$_SESSION['conditionTrigger'] == "returnFalse") {
							echo ('checked');} ?>>
						<label class="form-check-label" for="inlineRadio2">Si la condition est fausse</label>
					</div><br/>
					<?php
				}
			?>
			<input type="submit" name="nextTrigger" value="Suivant" class="btn btn-primary col-sm-12 col-lg-4 mt-4">
		</fieldset>
	</form>
	<?php
	}

	if($_SESSION['form-step'] >= 3) { ?>
		<form class="needs-validation" method="post" action="#">
			<fieldset class="border p-3 mb-1">
				<legend  class="w-auto">4. Type d'alerte : </legend>
				<div class="form-group row">
					<label for="alertClass" class="col-sm-2 col-form-label">Classe d'alerte: </label>
					<div class="col-sm-4">
                        <label>
                            <select class="form-control" name="alertClass" onchange="this.form.submit()">
                                <option <?php if($_SESSION['alertClass'] == "SCHEMA"){ echo "selected";} ?>>SCHEMA</option>
                                <option <?php if($_SESSION['alertClass'] == "METRICCOMPARE"){ echo "selected";} ?>>METRICCOMPARE</option>
                                <option <?php if($_SESSION['alertClass'] == "METRIQUE"){ echo "selected";} ?>>METRIQUE</option>
                                <option <?php if($_SESSION['alertClass'] == "DATA"){ echo "selected";} ?>>DATA</option>
                            </select>
                        </label>
                    </div>
					<label for="alertLevel" class="col-sm-2 col-form-label">Niveau d'alerte: </label>
					<div class="col-sm-4">
                        <label>
                            <select class="form-control" name="alertLevel" onchange="this.form.submit()">
                                <option <?php if($_SESSION['alertLevel'] == "Haut"){ echo "selected";} ?>>Haut</option>
                                <option <?php if($_SESSION['alertLevel'] == "Warning"){ echo "selected";} ?>>Warning</option>
                                <option <?php if($_SESSION['alertLevel'] == "Info"){ echo "selected";} ?>>Info</option>
                            </select>
                        </label>
                    </div>
				</div>
				<div class="form-group">
					<label for="alertMessage">Message d'alerte</label>
					<textarea required class="form-control" id="alertMessage" name="alertMessage" rows="1"><?php echo $_SESSION['alertMessage']; ?></textarea>
					<div class="invalid-tooltip">
						Please provide a valid zip.
					</div>
				</div>
				<div class="form-group pt-3">
					<input type="submit" name="saveRule" class="btn btn-primary col-sm-12 col-lg-4">
				</div>
			</fieldset>
			<input type="hidden" name="table" value="<?php echo(@$_GET['table']) ?>">
			<input type="hidden" name="column" value="<?php echo(@$_GET['column']) ?>">
		</form>
	<?php } ?>
</div>

<?php 

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
} 
?>