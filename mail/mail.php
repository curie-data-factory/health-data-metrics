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

var_dump($_POST);

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
            <p>Subscription mail : <span class="badge badge-primary"><?php echo($_SESSION['user_ids']['mail']); ?></span></p>
                <div class="row">
                    <div class="col-lg-12">
                        <fieldset class="border p-3 mb-4">
                            <legend class="w-auto">Reports :
                            <a href="#" data-toggle="tooltip" title="Notice : Reports comes from Metric Packs, they are aggregated vue of computed metrics. They are sent periodically"><i class="fas fa-question-circle"></i></a></legend>
                            <div class="alert alert-primary" role="alert">
                                Notice :
                                <br> Reports comes from Metric Packs, they are aggregated vue of computed metrics. They are sent periodically.
                                <br> Note that not all metric pack generate reports. Refer to pack documentation to know more, also, schedule can be manage on a per pack basis, but can be also global, be aware of that.
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
                                            <th scope="col">Port</th>
                                            <th scope="col">User</th>
                                            <th scope="col">SSL</th>
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
                                                <td><?php echo($db['db_port']) ?></td>
                                                <td><?php echo($db['db_user']) ?></td>
                                                <td><?php echo($db['db_is_ssl']) ?></td>
                                                <?php
                                                foreach ($dataReMapMP as $key => $value) {

                                                    $checked = false;
                                                    // Si la clé est déjà présente en base on check la box
                                                    foreach ($hdmMPCorrList as $CorrDbKey) {
                                                        if(($CorrDbKey['db_key'] == $dbkey)
                                                            && ($CorrDbKey['mp_key'] == $value[array_key_first($value)]['name'])){
                                                            $checked = true;
                                                        }
                                                    }

                                                    ?>
                                                    <td>
                                                        <form method="POST" action="mail.php" style="display: inline-block; vertical-align: middle;">
                                                            <input type="checkbox" name="checkbox"  class="double" <?php if($checked) { echo ""; } else { echo "disabled"; } ?> onChange="this.form.submit()">
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
                                            <th scope="col">Port</th>
                                            <th scope="col">User</th>
                                            <th scope="col">SSL</th>
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
                                                <td scope="col"><?php echo($db['db_name']) ?></td>
                                                <td scope="col"><?php echo($db['db_type']) ?></td>
                                                <td scope="col"><?php echo($db['db_host']) ?></td>
                                                <td scope="col"><?php echo($db['db_port']) ?></td>
                                                <td scope="col"><?php echo($db['db_user']) ?></td>
                                                <td scope="col"><?php echo($db['db_is_ssl']) ?></td>
                                                <?php
                                                foreach ($dataReMapRP as $key => $value) {

                                                    $checked = false;
                                                    // Si la clé est déjà présente en base on check la box
                                                    foreach ($hdmRPCorrList as $CorrDbKey) {
                                                        if(($CorrDbKey['db_key'] == $dbkey)
                                                            && ($CorrDbKey['rp_key'] == $value[array_key_first($value)]['name'])){
                                                            $checked = true;
                                                        }
                                                    }

                                                    ?>
                                                    <td>
                                                        <form method="POST" action="mail.php" style="display: inline-block; vertical-align: middle;">
                                                            <input type="checkbox" name="checkbox"  class="double" <?php if($checked) { echo ""; } else { echo "disabled"; } ?> onChange="this.form.submit()">
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