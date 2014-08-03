<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'save':
        $configuration_value = tep_db_prepare_input($HTTP_POST_VARS['configuration_value']);
        $cID = tep_db_prepare_input($HTTP_GET_VARS['cID']);

        tep_db_query("update " . TABLE_CONFIGURATION . " set configuration_value = '" . tep_db_input($configuration_value) . "', last_modified = now() where configuration_id = '" . (int)$cID . "'");

        tep_redirect(tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cID));
      break;
    }
  }

  $gID = (isset($HTTP_GET_VARS['gID'])) ? $HTTP_GET_VARS['gID'] : 1;

  $cfg_group_query = tep_db_query("select configuration_group_title from " . TABLE_CONFIGURATION_GROUP . " where configuration_group_id = '" . (int)$gID . "'");
  $cfg_group = tep_db_fetch_array($cfg_group_query);

  require(DIR_WS_INCLUDES . 'template_top.php');
?>

          <div class="page-header">
            <h1><?php echo $cfg_group['configuration_group_title']; ?></h1>
          </div>
  
          <div class="panel panel-default">        
  
            <table class="table table-hover table-condensed table-striped">
              <thead>
                <tr class="heading-row">
                  <th class="col-md-6"><?php echo TABLE_HEADING_CONFIGURATION_TITLE; ?></th>
                  <th class="col-md-4"><?php echo TABLE_HEADING_CONFIGURATION_VALUE; ?></th>
                  <th class="col-md-2 text-right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</th>
                </tr>
              </thead>
              <tbody>
<?php
  $configuration_query = tep_db_query("select configuration_id, configuration_title, configuration_value, use_function from " . TABLE_CONFIGURATION . " where configuration_group_id = '" . (int)$gID . "' order by sort_order");
  while ($configuration = tep_db_fetch_array($configuration_query)) {
    if (tep_not_null($configuration['use_function'])) {
      $use_function = $configuration['use_function'];
      if (preg_match('/->/', $use_function)) {
        $class_method = explode('->', $use_function);
        if (!is_object(${$class_method[0]})) {
          include(DIR_WS_CLASSES . $class_method[0] . '.php');
          ${$class_method[0]} = new $class_method[0]();
        }
        $cfgValue = tep_call_function($class_method[1], $configuration['configuration_value'], ${$class_method[0]});
      } else {
        $cfgValue = tep_call_function($use_function, $configuration['configuration_value']);
      }
    } else {
      $cfgValue = $configuration['configuration_value'];
    }
   

    if ((!isset($HTTP_GET_VARS['cID']) || (isset($HTTP_GET_VARS['cID']) && ($HTTP_GET_VARS['cID'] == $configuration['configuration_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
      $cfg_extra_query = tep_db_query("select configuration_key, configuration_description, date_added, last_modified, use_function, set_function from " . TABLE_CONFIGURATION . " where configuration_id = '" . (int)$configuration['configuration_id'] . "'");
      $cfg_extra = tep_db_fetch_array($cfg_extra_query);

      $cInfo_array = array_merge($configuration, $cfg_extra);
      $cInfo = new objectInfo($cInfo_array);
    }

    if ( (isset($cInfo) && is_object($cInfo)) && ($configuration['configuration_id'] == $cInfo->configuration_id) ) {
      echo '                <tr class="active" onclick="document.location.href=\'' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cInfo->configuration_id . '&action=edit') .'\'">' . "\n";
    } else {
      echo '                <tr onclick="document.location.href=\'' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $configuration['configuration_id']) . '\'">' . "\n";
    }
?>
                  <td class="col-md-6"><?php echo $configuration['configuration_title']; ?></td>
                  <td class="col-md-4"><?php echo htmlspecialchars($cfgValue); ?></td>
                  <td class="col-md-2 text-right">
                    <div class="btn-toolbar pull-right" role="toolbar" style="margin: 0;">
                      <div class="btn-group btn-group-xs">
<?php
            echo tep_glyphicon_button(IMAGE_ICON_INFO, 'info-sign', tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $configuration['configuration_id'] . '&action=info'), null, 'info', null, null, null, false) . '</div><div class="btn-group btn-group-xs">' . 
			  tep_glyphicon_button(IMAGE_EDIT, 'pencil', tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $configuration['configuration_id'] . '&action=edit'), null, 'warning', null, null, null, false); ?>
                      </div>
                    </div>
                  </td>
                </tr>
<?php
    if ( (isset($cInfo) && is_object($cInfo)) && ($configuration['configuration_id'] == $cInfo->configuration_id) && (isset($HTTP_GET_VARS['action'])) ) {

      switch ($action) {
        case 'edit': 
      
          if ($cInfo->set_function) {
            eval('$value_field = ' . $cInfo->set_function . '"' . htmlspecialchars($cInfo->configuration_value) . '");');
          } else {
            $value_field = tep_draw_input_field('configuration_value', $cInfo->configuration_value);
          }
	  
          $contents = '';
          $contents .= '                      ' . tep_draw_form('configuration', FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cInfo->configuration_id . '&action=save') . "\n";
          $contents .= '                        <div class="col-xs-8 col-sm-8 col-md-8">' . "\n";
          $contents .= '                          <p>' . $cInfo->configuration_description . '</p>' . "\n";
          $contents .= '                          ' . $value_field . "\n";
          $contents .= '                        </div>' . "\n";
          $contents .= '                        <div class="col-xs-4 col-sm-4 col-md-4 text-right">' . "\n";
          $contents .= '                          <div class="btn-group-vertical">' . tep_draw_bs_button(IMAGE_SAVE, 'ok', null) . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cInfo->configuration_id)) . '</div>' . "\n";
          $contents .= '                        </div>' . "\n";
          $contents .= '                      </form>' . "\n";
        break;
      
        default:
          if (isset($cInfo) && is_object($cInfo)) {
            $contents = '';
            $contents .= '                      <div class="col-xs-12 col-sm-8 col-md-8">' . "\n";      
            $contents .= '                        <p>' . $cInfo->configuration_description . '</p>' . "\n"; 
            $contents .= '                      </div>' . "\n";
            $contents .= '                      <div class="col-xs-12 col-sm-4 col-md-4 text-right">' . "\n";		
            $contents .= '                        <span class="label label-info">' . TEXT_INFO_DATE_ADDED . ' ' . tep_date_short($cInfo->date_added) . '</span>' . "\n";
		
            if (tep_not_null($cInfo->last_modified)) $contents .= '                        <span class="label label-info">' . TEXT_INFO_LAST_MODIFIED . ' ' . tep_date_short($cInfo->last_modified) . '</span>' . "\n";
		
            $contents .= '                      </div>' . "\n";
          }
        break;
      }	
	
      echo '                <tr class="content-row">' . "\n" .
           '                  <td colspan="3">' . "\n" .
		   '                    <div class="row">' . "\n" .
                                  $contents . 
           '                    </div>' . "\n" .
           '                  </td>' . "\n" .
           '                </tr>' . "\n";
    }
	
  }
?>

              </tbody>
            </table>
          </div>
  
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>