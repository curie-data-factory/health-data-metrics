<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

include_once($_SERVER['DOCUMENT_ROOT'].'/connect_db.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/core.php');

# load constantes
$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
foreach ($conf['EXPLORER'] as $key => $value) {
	define($key,$value);
}

if (isset($_POST['editRule'])) {
	$sql = 'SELECT * FROM `rule_basic` WHERE  `id_rule`=:id_rule LIMIT 100;';
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
			$_SESSION['sqlRequestValue'] = $res['rule_content'];
			break;
		case 'conditionnelle':
			$dataParsed = json_decode($res['rule_content']);
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
	$sql = 'DELETE FROM `rule_basic` WHERE  `id_rule`=:id_rule;';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute(array('id_rule' => $_POST['id_rule']));
	$res = $sth->fetchAll(PDO::FETCH_ASSOC);
}

// récupération des noms des bases de données :
if(isset($_GET['newFilter'])){

	$sql = 'SELECT DISTINCT `database` FROM `metric_basic`';
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

	$sql = 'SELECT DISTINCT `table` FROM `metric_basic` WHERE `database` = :database';
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

	$sql = 'SELECT DISTINCT `column` FROM `metric_basic` WHERE `table` = :table AND `database` = :database';
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
	FROM `metric_basic` 
	WHERE `database` = :database 
		AND `table` = :table 
		AND `column` = :column 
		AND `date` = (SELECT DISTINCT MAX(`date`) AS date 
							FROM `metric_basic`
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

$url_kibana = KIBANA_URL."/s/".KIBANA_NAMESPACE."/app/kibana#";

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
		$ruleContent = $_SESSION['sqlRequestValue'];
		break;
		case 'conditionnelle':
		$ruleContent = json_encode(array("metric" => $_SESSION['metric'],
							"condition" => $_SESSION['condition'],
							"conditionValue" => $_SESSION['conditionValue'],
							"conditionTrigger" => $_SESSION['conditionTrigger']));
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
		$sql = 'UPDATE `rule_basic` SET `rule_name`=:rule_name, `rule_type`=:rule_type, `alert_level`=:alert_level, `alert_class`=:alert_class, `alert_message`=:alert_message, `alert_scope`=:alert_scope, `condition_trigger`=:condition_trigger,`condition_scope`=:condition_scope, `database`=:database, `table`=:table, `column`=:column, `rule_content`=:rule_content WHERE `id_rule`=:id_rule;';
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
		$sql = 'INSERT INTO `rule_basic` (`rule_name`, `rule_type`, `alert_level`,`alert_class`, `alert_message`, `alert_scope`,`condition_trigger`,`condition_scope`,`database`,`table`,`column`, `rule_content`) VALUES (:rule_name, :rule_type, :alert_level,:alert_class, :alert_message, :alert_scope, :condition_trigger, :condition_scope,  :database, :table, :column, :rule_content);';
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
			$sql = 'SELECT * FROM `rule_basic` WHERE `database`=:database AND `table`=:table AND `column`=:column ORDER BY `id_rule` DESC LIMIT 100;';
			$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute(array('database'=>$_GET['database'],
								'table'=>$_GET['table'],
								'column'=> $_GET['column']));
        } else {
			$sql = 'SELECT * FROM `rule_basic` WHERE `database`=:database AND `table`=:table ORDER BY `id_rule` DESC LIMIT 100;';
			$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute(array('database'=>$_GET['database'],
								'table'=>$_GET['table']));
        }
    } else {
		$sql = 'SELECT * FROM `rule_basic` WHERE `database`=:database ORDER BY `id_rule` DESC LIMIT 100;';
		$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute(array('database'=>$_GET['database']));
    }
} else {
	$sql = 'SELECT * FROM `rule_basic` ORDER BY `id_rule` DESC LIMIT 100;';
	$sth = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute();
}
    $rules = $sth->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_GET['newFilter'])) {
	$filterPaneSize = 5;
	$rulePaneSize = 7;	
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
						<button type="submit" name="newFilter" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrage</button>
						<?php if(isset($_GET['newFilter']))
						{ 
							echo('<button type="submit" name="cancel" class="mr-1 btn btn-danger">Supprimer les filtres</button>');
						} ?>
					</form>
					<form method="post" action="#">
						<?php if(isset($_GET['newFilter']))
						{ 
							if ($_SESSION['kibana'] == "True") { $kibana = "info";} else { $kibana = "light";}
							echo('<button type="submit" name="kibana" class="btn btn-'.$kibana.'">Kibana dashboard</button>');
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
							<label for="staticEmail" class="col-sm-2 col-form-label">Database : </label>
							<div class="col-sm-10">
                                    <select  class="form-control" name='database' onchange='this.form.submit()'>
                                        <option>Select a Database</option>
                                        <?php
                                        if(isset($_GET['newFilter'])){
                                            foreach ($databases as $key => $value) {
                                                $var = $value["database"];
                                                if($value["database"] == $_GET['database']){
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
							<label for="staticEmail" class="col-sm-2 col-form-label">Table : </label>
							<div class="col-sm-10">
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
								<label for="staticEmail" class="col-sm-2 col-form-label">Colonne : </label>
								<div class="col-sm-10">
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
							if(!isset($_GET['column']) && ($_SESSION['kibana'] == "True")) { ?>

								<!-- Dashboard Kibana de description d'une table -->
								<iframe src="<?php echo $url_kibana; ?>/dashboard/9875af60-6807-11e9-a3e3-29a6fcac61c5?embed=true&_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:now%2FM,to:now%2FM))&_a=(description:'',filters:!(('$state':(store:appState),meta:(alias:!n,disabled:!f,index:'7d24a990-6047-11e9-98fb-b7b29faba70d',key:table,negate:!f,params:!(<?php echo $_GET['table'] ?>),type:phrases,value:<?php echo $_GET['table'] ?>),query:(bool:(minimum_should_match:1,should:!((match_phrase:(table:<?php echo $_GET['table'] ?>))))))),fullScreenMode:!f,options:(hidePanelTitles:!f,useMargins:!t),panels:!((embeddableConfig:(vis:(colors:('15-04-2019:+Max+naValues':%23BF1B00,'15-04-2019:+Max+valueCounts':%237EB26D),legendOpen:!f)),gridData:(h:19,i:'4',w:48,x:0,y:0),id:'146d23a0-5f75-11e9-98fb-b7b29faba70d',panelIndex:'4',type:visualization,version:'7.0.0')),query:(language:kuery,query:''),timeRestore:!f,title:quick_dash_table,viewMode:view)" height="600" width="100%"></iframe>
								<?php echo('<iframe  src='.KIBANA_URL."/s/".KIBANA_NAMESPACE."/app/kibana#/dashboard/".KIBANA_HOME_DASHBOARD."?embed=true&_g=(refreshInterval%3A(pause%3A!t%2Cvalue%3A0)%2Ctime%3A(from%3Anow%2FM%2Cto%3Anow%2FM))".' height="2200px" width="100%"></iframe>') ?>

								<?php 
							}
						}

						if(isset($_GET['column']) && !empty(array_filter($res3))){
							?>
							<?php if (($_SESSION['kibana'] == "True") && @$_SESSION['metrics']['is_categorical']){ ?>

								<!-- Dashboard Kibana de description d'une Colonne de table -->
								<iframe src="<?php echo $url_kibana; ?>/dashboard/75f6c5b0-6806-11e9-a3e3-29a6fcac61c5?embed=true&_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:now%2FM,to:now%2FM))&_a=(description:'',filters:!(('$state':(store:appState),meta:(alias:!n,disabled:!f,index:'028a0e10-6046-11e9-98fb-b7b29faba70d',key:table,negate:!f,params:(query:<?php echo $_GET['table'] ?>),type:phrase,value:<?php echo $_GET['table'] ?>),query:(match:(table:(query:<?php echo $_GET['table'] ?>,type:phrase)))),('$state':(store:appState),meta:(alias:!n,disabled:!f,index:'028a0e10-6046-11e9-98fb-b7b29faba70d',key:column,negate:!f,params:(query:<?php echo $_GET['column'] ?>),type:phrase,value:<?php echo $_GET['column'] ?>),query:(match:(column:(query:<?php echo $_GET['column'] ?>,type:phrase))))),fullScreenMode:!f,options:(hidePanelTitles:!f,useMargins:!t),panels:!((embeddableConfig:(),gridData:(h:19,i:'1',w:48,x:0,y:0),id:'65517340-6806-11e9-a3e3-29a6fcac61c5',panelIndex:'1',type:visualization,version:'7.0.0')),query:(language:kuery,query:''),timeRestore:!f,title:quick_dash_colonnes,viewMode:view)" height="600" width="100%"></iframe>
							<?php } ?>

							<div class="form-group row">
								<nav class="col-sm-12 col-lg-12">
									<h3>Métriques : </h3>
									<?php
									foreach (array_filter($res3[0]) as $key => $value) {
										echo('<ul class="pagination pagination-sm">
											<li class="page-item active col-sm-4">
											<span class="page-link">'.$key.' : </span>
											</li>
											<li class="page-item col-sm-8"><a class="page-link" href="#">'.$value.'</a></li>
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
							<form action="#" method="post">
								<div class="form-group row">
									<nav class="col-sm-12">
										<span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Veuillez sélectionner une table et une colonne">
											<button type="submit" name="newConditionBase" class="btn btn-primary">Nouvelle Règle de Base</button>
											<button type="submit" <?php if(!isset($_GET['table'])) { echo "disabled";} ?>  name="newConditionTable" class="btn btn-primary">Nouvelle Règle de Table</button>
											<button type="submit" <?php if(!isset($_GET['column'])) { echo "disabled";} ?> name="newConditionColumn" class="btn btn-primary">Nouvelle Règle de colonne</button>
										</span>
									</nav>
								</div>
								<input type="hidden" name="newFilter">
							</form>
							<div class="col-12 p-3 bg-white rounded shadow-sm">
								<?php 

								if(isset($_GET['column'])) {
									echo('<h4 class="border-bottom border-gray pb-2 mb-0">Règles de la table :  <span class="badge badge-secondary"><b>'.$_GET["table"].'</b></span> et de la colonne : <span class="badge badge-secondary"><b>'.$_GET["column"].'</b></span></h4>');
								} else if ((isset($_GET['table']) && ($_GET['table'] != "Select a Table")) && !isset($_GET['column'])) {
									echo('<h4 class="border-bottom border-gray pb-2 mb-0">Règles de la table :  <span class="badge badge-secondary"><b>'.$_GET["table"].'</b></span></h4>');
								} else {
									echo('<h4 class="border-bottom border-gray pb-2 mb-0">Toutes les règles : </h4>');
								}

								if (empty($rules)) { ?>
									<div class="mt-3 mb-0">
								        <h3 style="text-align: center;">Aucun résultats</h3>
								      </div>
								<?php }

								foreach ($rules as $key => $value) {
									?>
									<form method="post" action="?database=<?php echo $value['database']."&table=".$value['table']."&column=".$value['column']."&newFilter=#"; ?>">
										<div class="media text-muted">
											<div class="col-lg-4 pl-0">
												<strong class="p-1 colg-lg-9 d-block text-gray-dark"><?php echo $value['rule_name']; ?></strong>
												<?php 
												switch ($value['alert_level']) {
													case 'Haut':
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
                                                                    <textarea disabled class="form-control"><?php echo $value['rule_content']; ?></textarea>
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