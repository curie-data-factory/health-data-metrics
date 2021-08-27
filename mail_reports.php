<?php

###########################
# ARMAND LEOPOLD
# 19/08/2021
# This page show sended mails of alerts.
###########################
include_once("core.php");
include_once("connect_db.php");

$_SESSION['split-display-database'] = "True";
$_SESSION['split-display-table'] = "False";
$_SESSION['split-display-column'] = "False";
$_SESSION['split-display-scope'] = "database";
$_SESSION['alert-display-high'] = "True";
$_SESSION['alert-display-warn'] = "True";
$_SESSION['alert-display-info'] = "True";
$_SESSION['filter-display-METRICCOMPARE'] = "True";
$_SESSION['filter-display-METRIQUE'] = "True";
$_SESSION['filter-display-DATA'] = "True";
$_SESSION['filter-display-SCHEMA'] = "True";

###########################
# Récupération de la liste des abonnements mails :

$reports_subs = simple_query_db($conn,"SELECT DISTINCT mail FROM hdm_core_mail_list WHERE `type` = 'reports'");
$alerts_subs = simple_query_db($conn,"SELECT DISTINCT mail FROM hdm_core_mail_list WHERE `type` = 'alerts'");

###########################
# Envoi des alertes :

$alert_db_list_for_sub = simple_query_db($conn,"SELECT * FROM hdm_core_mail_list  WHERE `type` = 'alerts' AND `mail` = '".$_SESSION['user_ids']['mail']."'");
# Construction de la requête pour récupérer les alertes
showAlertMessage($_SESSION['user_ids']['mail'],$alert_db_list_for_sub,$conn);

function showAlertMessage($infos,$db_list,$conn) {

    // message
    $message = '<html lang="en">
    <head>
        <title>[HDM] Alert Report</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </head>
    <body>
    <style>
    .table td, .table th {
        padding: 0.2rem;
    }
      html,body {
      height: 100%;
      font-family: "Gill Sans", sans-serif;
      color: #1f4e79;
    }
    
    a {
        color: #1F4E79;
        text-decoration: none;
        background-color: transparent;
    }
    
    img {
        display: flex;
    }
    
    body {
      display: flex;
      align-items: center;
      padding-bottom: 40px;
      background-color: #f5f5f5;
    }
    
    .form-signin {
      width: 100%;
      max-width: 1400px;
      padding: 25px;
      margin: auto;
      background-color: #ffffff;
      box-shadow: #e9e9e9 2px 2px 9px 1px;
      border-radius: 5px;
    }
    
    </style>
    <main class="form-signin">
      <div class="container-fluid">
      <div class="row">
          <div class="col-lg-12">
              <img alt="logo" src="https://raw.githubusercontent.com/curie-data-factory/health-data-metrics/master/img/logo-hdm.png" width="105" height="67" style="float:right;">
              <h3 style="color:#111c;">HDM / Alert Report : '.date("Y-m-d").' / '.$infos.'</h3>
              <hr>
          </div>
      </div>
      <div class="row">
      <div class="col-lg-12">
      ';

    foreach($db_list as $db_key) {

        $db_ids = explode(":",$db_key['db_key']);
        $query = "SELECT * FROM `hdm_alerts` WHERE `database` = '".$db_ids[0]."' AND `date` = '".date("Y-m-d")."'";
        $alert_data = simple_query_db($conn,$query);

        if(sizeof($alert_data) > 0){
            $message .= printHeader($alert_data[0]['database'],true);
            foreach ($alert_data as $row){
                $message .= writeRow($row);
            }
            $message .= '</tbody></table></div>';
        }
    }

    $message .= '</div></div></div></main></body></html>';

    // Pour envoyer un mail HTML, l'en-tête Content-type doit être défini
    $headers[] = "From: HDM <no-reply-hdm@".$_SERVER['SERVER_NAME'].">";
    $headers[] = "Reply-To: no-reply-hdm@".$_SERVER['SERVER_NAME'];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';

    echo $message;
}
