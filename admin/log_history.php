<?php

/* LOAD CONF */
$conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/conf/appli/conf-appli.json"), true);
$ldap_conf = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $conf['AUTH']['AUTH_LDAP_CONF_PATH']), true);

if (isset($_SESSION['connected'])
AND in_array($ldap_conf['admin_ldap_authorization_domain'],$_SESSION['user_ids']['memberof'])) {
?>

<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">
    <a class="nav-item nav-link" id="nav-apachelogs-tab" data-toggle="tab" href="#nav-apachelogs" role="tab" aria-controls="nav-apachelogs" aria-selected="true">Apache Logs</a>
    <a class="nav-item nav-link" id="nav-apacheerrors-tab" data-toggle="tab" href="#nav-apacheerrors" role="tab" aria-controls="nav-apacheerrors" aria-selected="false">Apache Errors</a>
  </div>
</nav>

<div class="tab-content" id="nav-tabContent">
  <div class="tab-pane fade show active" id="nav-apachelogs" role="tabpanel" aria-labelledby="nav-apachelogs-tab">
      <label for="apachelogs"></label><textarea id="apachelogs" class="log" disabled><?php
  		echo file_get_contents($_SERVER['DOCUMENT_ROOT']."/logs/access.log"); ?>
  	</textarea>
  </div>
  <div class="tab-pane fade" id="nav-apacheerrors" role="tabpanel" aria-labelledby="nav-apacheerrors-tab">
      <label for="apacheerrors"></label><textarea id="apacheerrors" class="log" disabled><?php
  		echo file_get_contents($_SERVER['DOCUMENT_ROOT']."/logs/error.log"); ?>  	
  	</textarea>
  </div>
</div>
<?php } else {
    include $_SERVER['DOCUMENT_ROOT'].'/login.php';
}
