<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

	$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'metric_".$pack_name."'";
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute();
	$res = $sth->fetchAll(PDO::FETCH_ASSOC);

	$metricsList = array();
	$conditionsList = array("0"=>">",
							"1"=>">=",
							"2"=>"<",
							"3"=>"<=",
							"4"=>"==",
							"5"=>"!=");

	$conditionsListCat = array("0"=>">",
								"1"=>">=",
								"2"=>"<",
								"3"=>"<=",
								"4"=>"=",
								"5"=>"!=");

	foreach ($res as $key => $value) {
		array_push($metricsList, $value['COLUMN_NAME']);
	}

	// Fonction qui permet de gérer les sélecteurs de façon automatique
	function generateSelector($name,$data)
	{
		if (isset($_POST[$name])) {
			$_SESSION[$name] = $_POST[$name];
		}

		echo('<select class="form-control" name="'.$name.'" onchange="this.form.submit()">');
		foreach ($data as $value) {
			$selected = "";
			if ($_SESSION[$name] == $value) {
				$selected = " selected";
			}
			echo('<option '.$selected.'>'.$value.'</option>');
		}
		echo('</select>');
	}

	$attributes = array();

	foreach ($_SESSION['metrics'] as $key => $value) {
		array_push($attributes, $key);
	}

	if ($_POST['metric']) {
		$metric = $_POST['metric'];
	} else {
		$metric = $_SESSION['metric'];
	}
	if ($_POST['condition']) {
		$condition = $_POST['condition'];
	} else {
		$condition = $_SESSION['condition'];
	}
	if ($_POST['conditionValue']) {
		$conditionValue = $_POST['conditionValue'];
	} else {
		$conditionValue = $_SESSION['conditionValue'];
	}

	if ($_POST['conditionOnValues']) {
		$_SESSION['conditionScope'] = "value";
	} elseif ($_POST['conditionOnMetrics']) {
		$_SESSION['conditionScope'] = "metrics";
	}
?>
<div class="mb-2">
	<input type="submit" class="btn btn-primary" value="On metrics" name="conditionOnMetrics">
	<input type="submit" class="btn btn-primary" value="On data" name="conditionOnValues">
</div>
<div class="alert alert-info" role="alert">
	<?php echo('IF '.$metric.' '.$condition.' '.$conditionValue); ?>
</div>
<?php if ($_SESSION['conditionScope'] == "value") { ?>

<h4>Conditions on data : </h4>
<div class="row">
	<div class="col-lg-3"><label>
            <input type="text" class="form-control" name="metric" value="<?php echo($_GET['table']."/".$_GET['column']); ?>" disabled>
        </label>
    </div>
	<div class="col-lg-2"><?php generateSelector("condition",$conditionsListCat); ?></div>
	<input type="hidden" name="table" value="<?php echo($_GET['table']); ?>">
	<input type="hidden" name="column" value="<?php echo($_GET['column']); ?>">
	<div class="col-lg-7"><label>
            <input type="text" class="form-control" name="conditionValue" value="<?php echo $conditionValue ?>">
        </label></div>
</div>
<?php } else if ($_SESSION['conditionScope'] == "metrics") {  ?>

<h4>Conditions on metrics : </h4>
<div class="row">
	<div class="col-lg-3"><?php generateSelector("metric",$metricsList); ?></div>
	<div class="col-lg-2"><?php generateSelector("condition",$conditionsList); ?></div>
	<div class="col-lg-7"><label>
            <input type="text" class="form-control" name="conditionValue" value="<?php echo $conditionValue ?>">
        </label></div>
</div>
<?php } 

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}
?>