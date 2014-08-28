<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/
?>
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo TITLE; ?></title>
<base href="<?php echo ($request_type == 'SSL') ? HTTPS_SERVER . DIR_WS_HTTPS_ADMIN : HTTP_SERVER . DIR_WS_ADMIN; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo tep_catalog_href_link('ext/bootstrap/css/bootstrap.min.css', '', 'SSL'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo tep_catalog_href_link('ext/bootstrap-select/bootstrap-select.min.css', '', 'SSL'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo tep_catalog_href_link('ext/jquery/ui/redmond/jquery-ui-1.10.4.min.css', '', 'SSL'); ?>">
<link rel="stylesheet" type="text/css" href="ext/stylesheet.css">
<!--[if lt IE 9]>
  <script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/js/html5shiv.js', '', 'SSL'); ?>/ext/html5shiv.js"></script>
  <script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/js/respond.min.js', '', 'SSL'); ?>"></script>
  <script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/js/excanvas.min.js', '', 'SSL'); ?>"></script>
<![endif]-->
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/jquery/jquery-1.11.1.min.js', '', 'SSL'); ?>"></script>
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/bootstrap/js/bootstrap.min.js', '', 'SSL'); ?>"></script>
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/bootstrap-select/bootstrap-select.min.js', '', 'SSL'); ?>"></script>

<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/jquery/ui/jquery-ui-1.10.4.min.js', '', 'SSL'); ?>"></script>

<?php
  if (tep_not_null(JQUERY_DATEPICKER_I18N_CODE)) {
?>
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/jquery/ui/i18n/jquery.ui.datepicker-' . JQUERY_DATEPICKER_I18N_CODE . '.js', '', 'SSL'); ?>"></script>
<script type="text/javascript">
$.datepicker.setDefaults($.datepicker.regional['<?php echo JQUERY_DATEPICKER_I18N_CODE; ?>']);
</script>
<?php
  } 
  if (strpos($_SERVER['PHP_SELF'],'index.php')) 
  {
?>
<!--[if IE]><script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/flot/excanvas.min.js', '', 'SSL'); ?>"></script><![endif]-->
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/flot/jquery.flot.min.js', '', 'SSL'); ?>"></script>
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/flot/jquery.flot.time.min.js', '', 'SSL'); ?>"></script>
<?php 
  }
?>
<script type="text/javascript" src="includes/general.js"></script>
</head>
<body>
  <div id="bodyWrapper" class="page-container">
  
<?php
  require(DIR_WS_INCLUDES . 'header.php');
?>
    
    <div id="main" class="container-fluid">
      <div class="row row-offcanvas">
      
<?php
  if (tep_session_is_registered('admin')) {
    include(DIR_WS_INCLUDES . 'column_left.php');
  } else {
?>
<style>
#bodyContent {
background: none;
border:none;
box-shadow:none;
-webkit-box-shadow: none;
}
</style>
<?php
  }
?>

        <div id="bodyContent" class="col-xs-12 <?php echo (tep_session_is_registered('admin') ? 'col-sm-9 col-md-10 equal' : 'col-sm-6 col-md-6 col-md-offset-3 col-sm-offset-3'); ?> content-canvas">
        		
<?php
  if ($messageStack->size > 0) {
    echo $messageStack->output();
  }
?>