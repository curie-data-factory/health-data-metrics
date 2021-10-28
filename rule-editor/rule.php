<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

include_once($_SERVER['DOCUMENT_ROOT'].'/connect_db.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/core.php');

# Rule pack rule filtering
if (!isset($_SESSION['rulePack'])) {
    $rule_pack = "rule_basic";
} else {
    $rule_pack = $_GET['rulePack'] ?? $_SESSION['rulePack'];
}
$_SESSION['rulePack'] = $rule_pack;

// Pack Name :
$pack_name = explode("_",$_SESSION['rulePack'])[1];

$sql_rule_pack = "SELECT * FROM `information_schema`.`TABLES` WHERE TABLE_NAME LIKE 'rule%'LIMIT 100;";
$sth = $conn->prepare($sql_rule_pack, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
$sth->execute(array('id_rule' => $_POST['id_rule']));
$rule_pack_table_list = $sth->fetchAll(PDO::FETCH_ASSOC);
#########

if (isset($_POST['editRule'])) {

    $sql = 'SELECT * FROM `'.$_SESSION['rulePack'].'` WHERE `id_rule`=:id_rule LIMIT 100;';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute(array('id_rule' => $_POST['id_rule']));
	$res = $sth->fetchAll(PDO::FETCH_ASSOC)[0];

	$_SESSION['form-step'] = 3;
	$_SESSION['alertClass'] = $res['alert_class'];
	$_SESSION['alertLevel'] = $res['alert_level'];
	$_SESSION['alertMessage'] = $res['alert_message'];
	$_SESSION['alertScope'] = $res['alert_scope'];
	$_SESSION['conditionScope'] = $res['condition_scope'];
	$_SESSION['conditionTrigger'] = $res['condition_trigger'];
	$_SESSION['ruleType'] = $res['rule_type'];
	$_SESSION['ruleName'] = $res['rule_name'];

	switch ($_SESSION['ruleType']) {
		case 'sql':
			$_SESSION['sqlRequestValue'] = base64_encode($res['rule_content']);
			break;
		case 'conditionnelle':

            if ( base64_encode(base64_decode($res['rule_content'], true)) === $res['rule_content'] ) {
                $dataParsed = json_decode(base64_decode($res['rule_content'],true));
            } else {
                $dataParsed = json_decode($res['rule_content']);
            }

			$_SESSION['metric'] = $dataParsed->{'metric'};
			$_SESSION['condition'] = $dataParsed->{'condition'};
			$_SESSION['conditionValue'] = $dataParsed->{'conditionValue'};
			$_SESSION['conditionTrigger'] = $dataParsed->{'conditionTrigger'};
			break;
	}

	$_SESSION['ruleEdit'] = True;
	$_SESSION['id_rule'] = $res['id_rule'];
}

// Suppression d'une règle :
if (@isset($_POST['dropRule'.$_POST['id_rule']])) {
	$sql = 'DELETE FROM `'.$_SESSION['rulePack'].'` WHERE  `id_rule`=:id_rule;';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute(array('id_rule' => $_POST['id_rule']));
	$res = $sth->fetchAll(PDO::FETCH_ASSOC);
}

// récupération des noms des bases de données :
if(isset($_GET['newFilter'])){

	$sql = 'SELECT DISTINCT `db_name` FROM `hdm_core_dblist`';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute();
	$databases = $sth->fetchAll(PDO::FETCH_ASSOC);
}

// récupération des noms des tables :
if(isset($_GET['database']) || isset($_POST['database'])){

	if (isset($_GET['database'])) {
		$database = $_GET['database'];

        $_SESSION['alertScope'] = "database";
	} elseif (isset($_POST['database'])) {
		$database = $_POST['database'];
	}

	$sql = 'SELECT DISTINCT `table` FROM `metric_'.$pack_name.'` WHERE `date` = (SELECT MAX(DATE) AS date FROM `metric_'.$pack_name.'`) AND `database` = :database';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute(array('database' => $database));
	$tables = $sth->fetchAll(PDO::FETCH_ASSOC);
}

// récupération des noms des colonnes :
if(isset($_GET['table']) || isset($_POST['table'])){

	if (isset($_GET['database'])) {
		$database = $_GET['database'];
	} elseif (isset($_POST['database'])) {
		$database = $_POST['database'];
	}

	if (isset($_GET['table'])) {
		if(($_GET['table'] == "Select a Table") | ($_GET['table'] == "")) {$table = "";}
		else {
			$table = $_GET['table'];
			$_SESSION['alertScope'] = "table";
		}
	} elseif (isset($_POST['table'])) {
		$table = $_POST['table'];
	}

	$sql = 'SELECT DISTINCT `column` FROM `metric_'.$pack_name.'` WHERE `date` = (SELECT MAX(DATE) AS date FROM `metric_'.$pack_name.'`) AND `database` = :database AND `table` = :table';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute(array('database' => $database, 'table' => $table));
	$colonnes = $sth->fetchAll(PDO::FETCH_ASSOC);
}

// récupération des métriques de la colonne de la table sélectionnée :
if(isset($_GET['column']) || isset($_POST['column'])){

	if (isset($_GET['database'])) {
		$database = $_GET['database'];
	} elseif (isset($_POST['database'])) {
		$database = $_POST['database'];
	}

	if (isset($_GET['table'])) {
		if($_GET['table'] == "Select a Table") {$table = "";}
		else {$table = $_GET['table'];}
	} elseif (isset($_POST['table'])) {
		$table = $_POST['table'];
	}

	if (isset($_GET['column'])) {
		if(($_GET['column'] == "Select a Column") | ($_GET['column'] == "")) {$column = "";}
		else {
			$column = $_GET['column'];
			$_SESSION['alertScope'] = "column";
		}
	} elseif (isset($_POST['column'])) {
		$column = $_POST['column'];
	}

	$sql = 'SELECT DISTINCT * 
	FROM `metric_'.$pack_name.'` 
	WHERE `database` = :database 
		AND `table` = :table 
		AND `column` = :column 
		AND `date` = (SELECT DISTINCT MAX(`date`) AS date 
							FROM `metric_'.$pack_name.'`
							WHERE `database` = :database 
							AND `table` = :table 
							AND `column` = :column)
';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute(array('database' => $database,
		'table' => $table,
		'column' => $column));
	$res3 = $sth->fetchAll(PDO::FETCH_ASSOC);
	@$_SESSION['metrics'] = array_filter($res3[0]);

}

// Gestion en session des affichages kibana :
if(isset($_POST['kibana'])) {
	if ($_SESSION['kibana'] == "True") {
		$_SESSION['kibana'] = "False";
	} else {
		$_SESSION['kibana'] = "True";
	}
} else {
	if(!isset($_SESSION['kibana'])) {
		$_SESSION['kibana'] = "False";
	}
}

if(isset($_POST['cancelRule'])){
	unsetFormRule();
}

if(isset($_POST['saveRule'])){

	// Récupération des variables à enregistrer :
	$ruleName = $_SESSION['ruleName'];
	$ruleType = $_SESSION['ruleType'];
	$alertLevel = $_SESSION['alertLevel'];
	$alertClass = $_SESSION['alertClass'];

    if (!empty($_SESSION['conditionTrigger'])) {
        $conditionTrigger = $_SESSION['conditionTrigger'];
    }

    $alertMessage = $_POST['alertMessage'] ?? $_SESSION['alertMessage'];

	$alertScope = $_SESSION['alertScope'];
	$database = "";
	$table = "";
	$column = "";

	switch ($ruleType) {
		case 'sql':
		    $ruleContent = base64_encode($_SESSION['sqlRequestValue']);
		break;
		case 'conditionnelle':
		    $ruleContent = base64_encode(json_encode(array("metric" => $_SESSION['metric'],
							"condition" => $_SESSION['condition'],
                            "conditionValue" => $_SESSION['conditionValue'],
							"conditionTrigger" => $_SESSION['conditionTrigger'])));
		break;
	}

	switch ($alertScope) {
		case 'database':
		$database = $_GET['database'];
		break;
		case 'table':
		$database = $_GET['database'];
		if($_GET['table'] == "Select a Table") {$table = "";}
		else {$table = $_GET['table'];}
		break;
		case 'column':
		$database = $_GET['database'];
		if($_GET['table'] == "Select a Table") {$table = "";}
		else {$table = $_GET['table'];}
		$column = $_GET['column'];
		break;
	}

	$conditionScope = $_SESSION['conditionScope'];

	if (isset($_SESSION['ruleEdit'])) {
		$sql = 'UPDATE `'.$_SESSION['rulePack'].'` SET `rule_name`=:rule_name, `rule_type`=:rule_type, `alert_level`=:alert_level, `alert_class`=:alert_class, `alert_message`=:alert_message, `alert_scope`=:alert_scope, `condition_trigger`=:condition_trigger,`condition_scope`=:condition_scope, `database`=:database, `table`=:table, `column`=:column, `rule_content`=:rule_content WHERE `id_rule`=:id_rule;';
		$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute(array('rule_name' => $ruleName,
			'rule_type' => $ruleType,
			'alert_level' => $alertLevel,
			'alert_message' => $alertMessage,
			'alert_scope' => $alertScope,
			'condition_trigger' => $conditionTrigger,
			'condition_scope' => $conditionScope,
			'alert_class' => $alertClass,
			'database' => $database,
			'table' => $table,
			'column' => $column,
			'rule_content' => $ruleContent,
			'id_rule' => $_SESSION['id_rule']));
		$res9 = $sth->fetchAll(PDO::FETCH_ASSOC);



		unset($_SESSION['id_rule']);
		unset($_SESSION['ruleEdit']);

	} else {

		// ajout de la regle dans la base
		$sql = 'INSERT INTO `'.$_SESSION['rulePack'].'` (`rule_name`, `rule_type`, `alert_level`,`alert_class`, `alert_message`, `alert_scope`,`condition_trigger`,`condition_scope`,`database`,`table`,`column`, `rule_content`) VALUES (:rule_name, :rule_type, :alert_level,:alert_class, :alert_message, :alert_scope, :condition_trigger, :condition_scope,  :database, :table, :column, :rule_content);';
		$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute(array('rule_name' => $ruleName,
			'rule_type' => $ruleType,
			'alert_level' => $alertLevel,
			'alert_class' => $alertClass,
			'alert_message' => $alertMessage,
			'alert_scope' => $alertScope,
			'condition_trigger' => $conditionTrigger,
			'condition_scope' => $conditionScope,
			'database' => $database,
			'table' => $table,
			'column' => $column,
			'rule_content' => $ruleContent));
		$res9 = $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	unsetFormRule();
}

// récupération des règles :
if (isset($_GET['database'])) {
	if (isset($_GET['table']) && ($_GET['table'] != "Select a Table")) {
		if (isset($_GET['column']) && ($_GET['column'] != "Select a Column")) {
			$sql = 'SELECT * FROM `'.$_SESSION['rulePack'].'` WHERE `database`=:database AND `table`=:table AND `column`=:column ORDER BY `id_rule` DESC LIMIT 100;';
			$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute(array('database'=>$_GET['database'],
								'table'=>$_GET['table'],
								'column'=> $_GET['column']));
        } else {
			$sql = 'SELECT * FROM `'.$_SESSION['rulePack'].'` WHERE `database`=:database AND `table`=:table ORDER BY `id_rule` DESC LIMIT 100;';
			$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute(array('database'=>$_GET['database'],
								'table'=>$_GET['table']));
        }
    } else {
		$sql = 'SELECT * FROM `'.$_SESSION['rulePack'].'` WHERE `database`=:database ORDER BY `id_rule` DESC LIMIT 100;';
		$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute(array('database'=>$_GET['database']));
    }
} else {
	$sql = 'SELECT * FROM `'.$_SESSION['rulePack'].'` ORDER BY `id_rule` DESC LIMIT 100;';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute();
}
    $rules = $sth->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['newFilter'])) {
	$filterPaneSize = 4;
	$rulePaneSize = 8;
} else {
	$filterPaneSize = 1;
	$rulePaneSize = 11;
}

$_SESSION['page'] = "rule";

include $_SERVER['DOCUMENT_ROOT'].'/header.php';

?>
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-<?php echo($filterPaneSize) ?> p-4 pb-0" style="padding-bottom: 0!important;">
			<div class="row">
				<div  class="col-lg-12 form-group form-inline">
					<form method="get" action="#">
						<button type="submit" name="newFilter" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
						<?php if(isset($_GET['newFilter']))
						{
							echo('<button type="submit" name="cancel" class="mr-1 btn btn-danger">Delete filters</button>');
						} ?>
					</form>
				</div>
				<?php
				// affichage du formulaire d'ajout de règles
				if(isset($_GET['newFilter'])){
					?>
				<div class="col-lg-12 bg-white rounded shadow-sm p-3 mb-3">
					<form  method="get" action="#">

						<div class="form-group row">
							<label for="staticEmail" class="col-sm-3 col-form-label">Database : </label>
							<div class="col-sm-9">
                                <select  class="form-control" name='database' onchange='this.form.submit()'>
                                    <?php

                                    if(isset($_GET['newFilter'])){

                                        if(isset($_GET['database'])){
                                            echo("<option selected >".$_GET['database']."</option>");
                                        } else {
                                            echo("<option selected >Select a Database</option>");
                                        }

                                        foreach ($databases as $key => $value) {
                                            $var = $value["db_name"];
                                            if($value["db_name"] == $_GET['database']){
                                                $select = "selected";
                                            } else {
                                                $select = "";
                                            }
                                            echo("<option ".$select.">".$var."</option>");
                                        }
                                    }?>
                                </select>
                            </div>
						</div>

						<?php
						if(isset($_GET['database']) || isset($_POST['database'])) {

						 ?>

						<div class="form-group row">
							<label for="staticEmail" class="col-sm-3 col-form-label">Table : </label>
							<div class="col-sm-9">
                                    <select  class="form-control" name='table' onchange='this.form.submit()'>
                                        <option>Select a Table</option>
                                        <?php
                                        if(isset($_GET['newFilter'])){
                                            foreach ($tables as $key => $value) {
                                                $var = $value["table"];
                                                if($value["table"] == $_GET['table']){
                                                    $select = "selected";
                                                } else {
                                                    $select = "";
                                                }
                                                echo("<option ".$select.">".$var."</option>");
                                            }
                                        }?>
                                    </select>
                            </div>
						</div>

						<?php
						}

						if((isset($_GET['table']) && ($_GET['table'] != "Select a Table")) || isset($_POST['table'])) {
							?>
							<div class="form-group row">
								<label for="staticEmail" class="col-sm-3 col-form-label">Column : </label>
								<div class="col-sm-9">
                                        <select  class="form-control" name='column' onchange='this.form.submit()'>
                                            <option>Select a Column</option>
                                            <?php
                                            foreach ($colonnes as $key => $value) {
                                                $var = $value["column"];
                                                if($value["column"] == $_GET['column']){
                                                    $select = "selected";
                                                } else {
                                                    $select = "";
                                                }
                                                echo("<option ".$select.">".$var."</option>");
                                            }
                                            ?>
                                        </select>
                                </div>
							</div><?php
						}

						if(isset($_GET['column']) && !empty(array_filter($res3))){
							?>
							<div class="form-group row">
								<nav class="col-sm-12 col-lg-12">
									<h3>Metrics : </h3>
									<?php
									foreach (array_filter($res3[0]) as $key => $value) {
										echo('<ul class="pagination pagination-sm">
											<li class="page-item active col-sm-6">
											<span class="page-link">'.$key.' : </span>
											</li>
											<li class="page-item col-sm-6">'.$value.'</li>
											</ul>');
										}
									?>
								</nav>
							</div>
							<?php
						}

						?>
						<input type="hidden" name="newFilter">
						<noscript><input type="submit" value="Submit"></noscript>
					</form>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="col-lg-<?php echo($rulePaneSize) ?> p-4 pb-0 ">
			<div class="row">
			<?php

			// VOLET DROIT EDITION DES RÈGLES
				if(isset($_POST['newConditionColumn']) || isset($_SESSION['form-step'])) {
					$scope = "column";
					include 'new_condition.php';
				} elseif(isset($_POST['newConditionTable']) || isset($_SESSION['form-step'])) {
					$scope = "table";
					include 'new_condition.php';
				} elseif(isset($_POST['newConditionBase']) || isset($_SESSION['form-step'])) {
					$scope = "database";
					include 'new_condition.php';
				}
				else {
					?>

					<div class="col-lg-12">
                        <div class="row">
                            <div class="form-inline form-group">
                                <form action="#" method="get">
                                    <select id="rulePack" class="form-control mr-2" style="vertical-align: middle; width:auto; display:inline-block;" name='rulePack' onchange='this.form.submit()'>
                                        <?php

                                        foreach ($rule_pack_table_list as $table) {
                                            $var = $table['TABLE_NAME'];
                                            if($var == $_SESSION['rulePack']){
                                                $select = "selected";
                                            } else {
                                                $select = "";
                                            }
                                            echo("<option ".$select.">".$var."</option>");
                                        }

                                        ?>
                                    </select>
                                    <?php
                                    if(isset($_GET['newFilter'])) {
                                        echo '<input type="hidden" name="newFilter">';
                                    }
                                    if(isset($_GET['database'])) {
                                        echo '<input type="hidden" name="database" value="'.$_GET['database'].'">';
                                    }
                                    if(isset($_GET['table'])) {
                                        echo '<input type="hidden" name="table" value="'.$_GET['table'].'">';
                                    }
                                    if(isset($_GET['column'])) {
                                        echo '<input type="hidden" name="column" value="'.$_GET['column'].'">';
                                    }
                                    ?>
                                </form>
                                <form action="#" method="post">
                                        <button type="submit" <?php if(!isset($_GET['database'])) { echo "disabled";} ?> name="newConditionBase" class="btn btn-primary">New Database Rule</button>
                                        <button type="submit" <?php if(!isset($_GET['table']) || ($_GET['table'] == 'Select a Table')) { echo "disabled";} ?>  name="newConditionTable" class="btn btn-primary">New Table Rule</button>
                                        <button type="submit" <?php if(!isset($_GET['column']) || ($_GET['column'] == 'Select a Column')) { echo "disabled";} ?> name="newConditionColumn" class="btn btn-primary">New Column Rule</button>
                                        <input type="hidden" name="newFilter">
                                </form>
                            </div>
                            <div class="col-12 p-3 bg-white rounded shadow-sm">
								<?php

								if(isset($_GET['column'])) {
									echo('<h4 class="border-bottom border-gray pb-2 mb-0">Table\'s Rule :  <span class="badge badge-secondary"><b>'.$_GET["table"].'</b></span> and column : <span class="badge badge-secondary"><b>'.$_GET["column"].'</b></span></h4>');
								} else if ((isset($_GET['table']) && ($_GET['table'] != "Select a Table")) && !isset($_GET['column'])) {
									echo('<h4 class="border-bottom border-gray pb-2 mb-0">Table\'s Rule :  <span class="badge badge-secondary"><b>'.$_GET["table"].'</b></span></h4>');
								} else {
									echo('<h4 class="border-bottom border-gray pb-2 mb-0">All Rules : </h4>');
								}

								if (empty($rules)) { ?>
									<div class="mt-3 mb-0">
								        <h3 style="text-align: center;">No Results</h3>
								      </div>
								<?php }

								foreach ($rules as $key => $value) {
									?>
									<form method="post" action="<?php echo "?database=".$value['database']."&table=".$value['table']."&column=".$value['column']."&newFilter=#"; ?>">
										<div class="media text-muted">
											<div class="col-lg-4 pl-0">
												<strong class="p-1 colg-lg-9 d-block text-gray-dark"><?php echo $value['rule_name']; ?></strong>
												<?php
												switch ($value['alert_level']) {
													case 'High':
													echo('<span class="col-lg-5 badge badge-danger p-2 mr-2"><b> High Priority </b></span>');
													break;
													case 'Warning':
													echo('<span class="col-lg-5 badge badge-warning p-2 mr-2"><b> Warning </b></span>');
													break;
													case 'Info':
													echo('<span class="col-lg-5 badge badge-info p-2 mr-2"><b> Info </b></span>');
													break;
												} ?>
												<nav aria-label="breadcrumb" class="pl-0">
													<ol class="breadcrumb m-0">
														<li class="breadcrumb-item"><a href='?database=<?php echo $value['database']."&newFilter=#"; ?>'>BDD : <?php echo $value['database']; ?></a></li>
														<?php if(!empty($value['table'])) {?>
															<li class="breadcrumb-item"><a href='?database=<?php echo $value['database']."&table=".$value['table']."&newFilter=#"; ?>'>Table : <?php echo $value['table']; ?></a></li>
														<?php }
														if(!empty($value['column'])) {?>
															<li class="breadcrumb-item"><a href='?database=<?php echo $value['database']."&table=".$value['table']."&column=".$value['column']."&newFilter=#"; ?>'>Col : <?php echo $value['column']; ?></a></li>
														<?php } ?>
													</ol>
												</nav>
											</div>
											<div class="col-lg-7">
												<table class="table table-hover table-sm mt-3">
													<tbody class="container">
														<tr class="row">
															<th class="col-lg-2" scope="row">Message : </th>
															<td class="col-lg-10"><?php echo $value['alert_message']; ?></td>
														</tr>
														<tr class="row">
															<th class="col-lg-2"  scope="row">Rule content : </th>
															<td class="col-lg-10">
                                                                    <textarea disabled class="form-control"><?php
                                                                        if ( base64_encode(base64_decode($value['rule_content'], true)) === $value['rule_content']){
                                                                            echo base64_decode($value['rule_content'], true);
                                                                        } else {
                                                                            echo $value['rule_content'];
                                                                        }
                                                                        ?></textarea>
                                                                </td>
														</tr>
													</tbody>
												</table>
											</div>
											<div class="btn-group col-lg-1 pt-5" role="group" aria-label="Basic example">
												<button type="submit" name="editRule" class="btn btn-primary"><i class="fas fa-edit"></i></button>
												<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#exampleModal<?php echo $value['id_rule']; ?>"><i class="fas fa-trash-alt"></i></button>
											</div>
											<div class="modal fade" id="exampleModal<?php echo $value['id_rule']; ?>" tabindex="-1" role="dialog" aria-labelledby="modaldeletelabel<?php echo $value['id_rule']; ?>" aria-hidden="true">
												<div class="modal-dialog" role="document">
													<div class="modal-content">
														<div class="modal-header">
															<h5 class="modal-title" id="modaldeletelabel">Supprimer la règle : <?php echo $value['id_rule'].' , '.$value['rule_name']; ?> </h5>
															<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																<span aria-hidden="true">&times;</span>
															</button>
														</div>
														<div class="modal-body">
															Êtes-vous sur de bien vouloir supprimer cette règle ?
														</div>
														<div class="modal-footer">
															<button type="button" class="btn btn-secondary" data-dismiss="modal">Non</button>
															<button type="submit" name="dropRule<?php echo $value['id_rule']; ?>" class="btn btn-danger">Oui</button>
															<input type="hidden" name="id_rule" value="<?php echo $value['id_rule']; ?>">
														</div>
													</div>
												</div>
											</div>
										</div>
									</form>
								<?php } ?>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
<?php
@include $_SERVER['DOCUMENT_ROOT'].'/footer.php';

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}
?>