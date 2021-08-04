<?php 	

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {

$_SESSION['page'] = "mail";

include $_SERVER['DOCUMENT_ROOT'].'/header.php';
include_once($_SERVER['DOCUMENT_ROOT'].'/connect_db.php');

$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
$dataConfDb = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$conf['DB']['DB_CONF_PATH']),true);

$hdmMailList = getMailList($conn,$_SESSION['user_ids']['mail']);

// On modifie les entrées dans la table de correspondance MetricPack/Databases
if((isset($_POST["mpkey"]) OR isset($_POST["rpkey"]))
    AND (($_POST["mpkey"] != "") OR ($_POST["rpkey"] != ""))
    AND isset($_POST["dbkey"])
    AND ($_POST["dbkey"] != "")){

    # On récupère la clé
    $posted_key = "";
    $mail_type = "";
    if ($_POST["mpkey"] != "") {
        $posted_key = $_POST["mpkey"];
        $mail_type = "reports";
    } else if($_POST["rpkey"] != "") {
        $posted_key = $_POST["rpkey"];
        $mail_type = "alerts";
    }

    // Si on a check la box alors qu'elle est déjà check, cela signifie que l'on veut décocher la case (supprimer la ligne de la table)
    $checked = false;

    // Si la clé est déjà présente en base on uncheck la box
    foreach ($hdmMailList as $CorrDbKey) {
        if(($CorrDbKey['db_key'] == $_POST["dbkey"])
            && $CorrDbKey['type'] == $mail_type
            && ($CorrDbKey['key'] == $posted_key)){
            $checked = true;
        }
    }

    if ($checked) {
        $query = $conn->prepare('DELETE FROM `hdm_core_mail_list` 
        WHERE `key` = :key 
        AND `db_key` = :dbkey
        AND `type` = :type
        AND `mail` = :mail;');
    } else {
        $query = $conn->prepare('INSERT INTO `hdm_core_mail_list` ( `key`, `db_key`, `type`, `mail` ) VALUES (:key, :dbkey, :type, :mail);');
    }
    if (!$query->execute(array(':key' => $posted_key,
        ':dbkey' => $_POST["dbkey"],
        ':type' => $mail_type,
        ':mail' => $_SESSION['user_ids']['mail']))) {
        print_r($query->errorInfo());
    }

    #dropping duplicates :
    $query = $conn->prepare('DELETE
                        FROM hdm_core_mail_list
                        WHERE id NOT IN
                            (SELECT id
                             FROM
                               (SELECT MIN(id) AS id
                                FROM hdm_core_mail_list
                                GROUP BY `key`,
                                         `type`,
                                         `mail`,
                                         `db_key` HAVING COUNT(*) >= 1) AS c);');
    if (!$query->execute()) {
        print_r($query->errorInfo());
    }
}

# On passe en variable d'env la conf
define('NEXUS_URL',$conf['PACK']['NEXUS_URL']);
define('NEXUS_API_URL',$conf['PACK']['NEXUS_API_URL']);
define('NEXUS_DEFAULT_REPOSITORY',$conf['PACK']['NEXUS_PACKS_ROOT_REPOSITORY']);
define('NEXUS_USER', $dataConfDb['hdm-nexus-creds']['user']);
define('NEXUS_PASSWORD', $dataConfDb['hdm-nexus-creds']['password']);

$hdmMetricPacks = getNexusContent("hdm.metricpacks");
$hdmRulePacks = getNexusContent("hdm.rulepacks");
$hdmDbList = getDbList($conn);
$hdmMPCorrList = getDbMpCorrList($conn);
$hdmRPCorrList = getDbRpCorrList($conn);
$hdmMailList = getMailList($conn,$_SESSION['user_ids']['mail']);

$dataReMapMP = array();
if($hdmMetricPacks['items'] != NULL){
    foreach ($hdmMetricPacks['items'] as $value) {
        $dataReMapMP[$value['name']][$value['version']] = $value;
    }
}

$dataReMapRP = array();
if($hdmRulePacks['items'] != NULL){
    foreach ($hdmRulePacks['items'] as $value) {
        $dataReMapRP[$value['name']][$value['version']] = $value;
    }
}

?>

<div class="container">
    <div class="row p-3 bg-white rounded shadow-sm mt-3">
        <div class="col-lg-12">
            <h1>Mail Subscriptions</h1>
            <p class="border-bottom border-gray pb-2 mb-4">
                Manage your subscriptions.
            </p>
            <p>Subscription email : <span class="badge badge-primary"><?php echo($_SESSION['user_ids']['mail']); ?></span></p>
                <div class="row">
                    <div class="col-lg-12">
                        <fieldset class="border p-3 mb-4">
                            <legend class="w-auto">Reports :
                            <a href="#" data-toggle="tooltip" title="Notice : Reports comes from Metric Packs, they are aggregated view of computed metrics. They are sent periodically"><i class="fas fa-question-circle"></i></a></legend>
                            <div class="alert alert-primary" role="alert">
                                Notice :
                                <br> Reports comes from Metric Packs, they are aggregated view of computed metrics. They are sent periodically.
                                <br> Note that not all metric pack generate reports. Refer to pack documentation to know more, also, schedule can be managed on a per pack basis, but can be also global, be aware of that.
                                <br> You can only subscribe to databases that are currently scanned by metric packs.
                            </div>
                            <!--
                            ################################################################################
                            Matrice de correspondance Metric Pack / Databases :
                             -->
                            <div class="row">
                                <div class="col-12">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th scope="col">Database</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Host</th>
                                            <?php
                                            foreach ($dataReMapMP as $key => $value) {
                                                if (!isset($tempvalue)
                                                    OR ($value != $tempvalue)) {
                                                    echo("<th scope=\"col\">".$value[array_key_first($value)]['name']."</th>");
                                                    $tempvalue = $value;
                                                }
                                            }
                                            ?>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        foreach ($hdmDbList as $db) {

                                            $dbkey = $db['db_name'].":".$db['db_type'].":".$db['db_host'].":".$db['db_port'].":".$db['db_user'].":".$db['db_is_ssl'];
                                            ?>
                                            <tr>
                                                <td><?php echo($db['db_name']) ?></td>
                                                <td><?php echo($db['db_type']) ?></td>
                                                <td><?php echo($db['db_host']) ?></td>
                                                <?php
                                                foreach ($dataReMapMP as $key => $value) {

                                                    $scanned = false;
                                                    $checked = false;
                                                    // Si la clé est déjà présente en base on rend la box "checkable"
                                                    foreach ($hdmMPCorrList as $CorrDbKey) {
                                                        if(($CorrDbKey['db_key'] == $dbkey)
                                                            && ($CorrDbKey['mp_key'] == $value[array_key_first($value)]['name'])){
                                                            $scanned = true;
                                                        }
                                                    }

                                                    // Si la clé est déjà présente en base on check la box
                                                    foreach ($hdmMailList as $CorrDbKey) {
                                                        if(($CorrDbKey['db_key'] == $dbkey)
                                                            && $CorrDbKey['type'] == "reports"
                                                            && ($CorrDbKey['key'] == $value[array_key_first($value)]['name'])){
                                                            $checked = true;
                                                        }
                                                    }

                                                    ?>
                                                    <td>
                                                        <form method="POST" action="mail.php" style="display: inline-block; vertical-align: middle;">
                                                            <input type="checkbox" name="checkbox"  class="double" <?php if($scanned & $checked) { echo "checked"; } else if($scanned) { echo ""; } else { echo "disabled"; } ?> onChange="this.form.submit()">
                                                            <input type="hidden" name="dbkey" value="<?php echo($dbkey) ?>">
                                                            <input type="hidden" name="mpkey" value="<?php echo($value[array_key_first($value)]['name']) ?>">
                                                        </form>
                                                        <form method="post" action="mail.php" style="display: inline-block; vertical-align: middle;">
                                                            <input type="hidden" name="dbkey" value="<?php echo($dbkey) ?>">
                                                            <input type="hidden" name="mpkey" value="<?php echo($value[array_key_first($value)]['name']) ?>">
                                                            <input type="hidden" name="mpconfig" value="True">
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                                ?>
                                            </tr>
                                            <?php
                                        } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-lg-12">
                        <fieldset class="border p-3 mb-4">
                            <legend class="w-auto">Alerts :
                            <a href="#" data-toggle="tooltip" title="Notice : Receive alerts coming from only selected data-sources."><i class="fas fa-question-circle"></i></a></legend>
                            <div class="alert alert-primary" role="alert">
                                Notice :
                                <br>Receive alerts coming from only selected data-sources.
                            </div>

                            <!--
                            ################################################################################
                            Matrice de correspondance Rule Pack / Databases :
                             -->
                            <div class="row">
                                <div class="col-12">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th scope="col">Database</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Host</th>
                                            <?php
                                            foreach ($dataReMapRP as $key => $value) {
                                                if (!isset($terpvalue)
                                                    OR ($value != $terpvalue)) {
                                                    echo("<th scope=\"col\">".$value[array_key_first($value)]['name']."</th>");
                                                    $terpvalue = $value;
                                                }
                                            }
                                            ?>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        foreach ($hdmDbList as $db) {

                                            $dbkey = $db['db_name'].":".$db['db_type'].":".$db['db_host'].":".$db['db_port'].":".$db['db_user'].":".$db['db_is_ssl'];
                                            ?>
                                            <tr>
                                                <td><?php echo($db['db_name']) ?></td>
                                                <td><?php echo($db['db_type']) ?></td>
                                                <td><?php echo($db['db_host']) ?></td>
                                                <?php
                                                foreach ($dataReMapRP as $key => $value) {

                                                    $scanned = false;
                                                    $checked = false;
                                                    // Si la clé est déjà présente en base on check la box
                                                    foreach ($hdmRPCorrList as $CorrDbKey) {
                                                        if(($CorrDbKey['db_key'] == $dbkey)
                                                            && ($CorrDbKey['rp_key'] == $value[array_key_first($value)]['name'])){
                                                            $scanned = true;
                                                        }
                                                    }

                                                    // Si la clé est déjà présente en base on check la box
                                                    foreach ($hdmMailList as $CorrDbKey) {
                                                        if(($CorrDbKey['db_key'] == $dbkey)
                                                            && $CorrDbKey['type'] == "alerts"
                                                            && ($CorrDbKey['key'] == $value[array_key_first($value)]['name'])){
                                                            $checked = true;
                                                        }
                                                    }

                                                    ?>
                                                    <td>
                                                        <form method="POST" action="mail.php" style="display: inline-block; vertical-align: middle;">
                                                            <input type="checkbox" name="checkbox"  class="double" <?php if($scanned & $checked) { echo "checked"; } else if($scanned) { echo ""; } else { echo "disabled"; } ?> onChange="this.form.submit()">
                                                            <input type="hidden" name="dbkey" value="<?php echo($dbkey) ?>">
                                                            <input type="hidden" name="rpkey" value="<?php echo($value[array_key_first($value)]['name']) ?>">
                                                        </form>
                                                        <form method="post" action="mail.php" style="display: inline-block; vertical-align: middle;">
                                                            <input type="hidden" name="dbkey" value="<?php echo($dbkey) ?>">
                                                            <input type="hidden" name="rpkey" value="<?php echo($value[array_key_first($value)]['name']) ?>">
                                                            <input type="hidden" name="rpconfig" value="True">
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                                ?>
                                            </tr>
                                            <?php
                                        } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/footer.php';

} else {
	include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}
?>