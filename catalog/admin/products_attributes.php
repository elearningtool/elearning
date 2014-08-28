<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
  $languages = tep_get_languages();

  $action = (($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  $option_page = (($HTTP_GET_VARS['option_page']) && is_numeric($HTTP_GET_VARS['option_page'])) ? $HTTP_GET_VARS['option_page'] : 1;
  $value_page = (($HTTP_GET_VARS['value_page']) && is_numeric($HTTP_GET_VARS['value_page'])) ? $HTTP_GET_VARS['value_page'] : 1;
  $attribute_page = (($HTTP_GET_VARS['attribute_page']) && is_numeric($HTTP_GET_VARS['attribute_page'])) ? $HTTP_GET_VARS['attribute_page'] : 1;

  $page_info = 'option_page=' . $option_page . '&value_page=' . $value_page . '&attribute_page=' . $attribute_page;

  if (tep_not_null($action)) {
    switch ($action) {
      case 'add_product_options':
        $products_options_id = tep_db_prepare_input($HTTP_POST_VARS['products_options_id']);
        $option_name_array = $HTTP_POST_VARS['option_name'];

        for ($i=0, $n=sizeof($languages); $i<$n; $i ++) {
          $option_name = tep_db_prepare_input($option_name_array[$languages[$i]['id']]);

          tep_db_query("insert into " . TABLE_PRODUCTS_OPTIONS . " (products_options_id, products_options_name, language_id) values ('" . (int)$products_options_id . "', '" . tep_db_input($option_name) . "', '" . (int)$languages[$i]['id'] . "')");
        }
        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
      case 'add_product_option_values':
        $value_name_array = $HTTP_POST_VARS['value_name'];
        $value_id = tep_db_prepare_input($HTTP_POST_VARS['value_id']);
        $option_id = tep_db_prepare_input($HTTP_POST_VARS['option_id']);

        for ($i=0, $n=sizeof($languages); $i<$n; $i ++) {
          $value_name = tep_db_prepare_input($value_name_array[$languages[$i]['id']]);

          tep_db_query("insert into " . TABLE_PRODUCTS_OPTIONS_VALUES . " (products_options_values_id, language_id, products_options_values_name) values ('" . (int)$value_id . "', '" . (int)$languages[$i]['id'] . "', '" . tep_db_input($value_name) . "')");
        }

        tep_db_query("insert into " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " (products_options_id, products_options_values_id) values ('" . (int)$option_id . "', '" . (int)$value_id . "')");

        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
      case 'add_product_attributes':
        $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
        $options_id = tep_db_prepare_input($HTTP_POST_VARS['options_id']);
        $values_id = tep_db_prepare_input($HTTP_POST_VARS['values_id']);
        $value_price = tep_db_prepare_input($HTTP_POST_VARS['value_price']);
        $price_prefix = tep_db_prepare_input($HTTP_POST_VARS['price_prefix']);

        tep_db_query("insert into " . TABLE_PRODUCTS_ATTRIBUTES . " values (null, '" . (int)$products_id . "', '" . (int)$options_id . "', '" . (int)$values_id . "', '" . (float)tep_db_input($value_price) . "', '" . tep_db_input($price_prefix) . "')");

        if (DOWNLOAD_ENABLED == 'true') {
          $products_attributes_id = tep_db_insert_id();

          $products_attributes_filename = tep_db_prepare_input($HTTP_POST_VARS['products_attributes_filename']);
          $products_attributes_maxdays = tep_db_prepare_input($HTTP_POST_VARS['products_attributes_maxdays']);
          $products_attributes_maxcount = tep_db_prepare_input($HTTP_POST_VARS['products_attributes_maxcount']);

          if (tep_not_null($products_attributes_filename)) {
            tep_db_query("insert into " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " values (" . (int)$products_attributes_id . ", '" . tep_db_input($products_attributes_filename) . "', '" . tep_db_input($products_attributes_maxdays) . "', '" . tep_db_input($products_attributes_maxcount) . "')");
          }
        }

        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
      case 'update_option_name':
        $option_name_array = $HTTP_POST_VARS['option_name'];
        $option_id = tep_db_prepare_input($HTTP_POST_VARS['option_id']);

        for ($i=0, $n=sizeof($languages); $i<$n; $i ++) {
          $option_name = tep_db_prepare_input($option_name_array[$languages[$i]['id']]);

          tep_db_query("update " . TABLE_PRODUCTS_OPTIONS . " set products_options_name = '" . tep_db_input($option_name) . "' where products_options_id = '" . (int)$option_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
        }

        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
      case 'update_value':
        $value_name_array = $HTTP_POST_VARS['value_name'];
        $value_id = tep_db_prepare_input($HTTP_POST_VARS['value_id']);
        $option_id = tep_db_prepare_input($HTTP_POST_VARS['option_id']);

        for ($i=0, $n=sizeof($languages); $i<$n; $i ++) {
          $value_name = tep_db_prepare_input($value_name_array[$languages[$i]['id']]);

          tep_db_query("update " . TABLE_PRODUCTS_OPTIONS_VALUES . " set products_options_values_name = '" . tep_db_input($value_name) . "' where products_options_values_id = '" . tep_db_input($value_id) . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
        }

        tep_db_query("update " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " set products_options_id = '" . (int)$option_id . "'  where products_options_values_id = '" . (int)$value_id . "'");

        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
      case 'update_product_attribute':
        $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
        $options_id = tep_db_prepare_input($HTTP_POST_VARS['options_id']);
        $values_id = tep_db_prepare_input($HTTP_POST_VARS['values_id']);
        $value_price = tep_db_prepare_input($HTTP_POST_VARS['value_price']);
        $price_prefix = tep_db_prepare_input($HTTP_POST_VARS['price_prefix']);
        $attribute_id = tep_db_prepare_input($HTTP_POST_VARS['attribute_id']);

        tep_db_query("update " . TABLE_PRODUCTS_ATTRIBUTES . " set products_id = '" . (int)$products_id . "', options_id = '" . (int)$options_id . "', options_values_id = '" . (int)$values_id . "', options_values_price = '" . (float)tep_db_input($value_price) . "', price_prefix = '" . tep_db_input($price_prefix) . "' where products_attributes_id = '" . (int)$attribute_id . "'");

        if (DOWNLOAD_ENABLED == 'true') {
          $products_attributes_filename = tep_db_prepare_input($HTTP_POST_VARS['products_attributes_filename']);
          $products_attributes_maxdays = tep_db_prepare_input($HTTP_POST_VARS['products_attributes_maxdays']);
          $products_attributes_maxcount = tep_db_prepare_input($HTTP_POST_VARS['products_attributes_maxcount']);

          if (tep_not_null($products_attributes_filename)) {
            tep_db_query("replace into " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " set products_attributes_id = '" . (int)$attribute_id . "', products_attributes_filename = '" . tep_db_input($products_attributes_filename) . "', products_attributes_maxdays = '" . tep_db_input($products_attributes_maxdays) . "', products_attributes_maxcount = '" . tep_db_input($products_attributes_maxcount) . "'");
          }
        }

        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
      case 'delete_option':
        $option_id = tep_db_prepare_input($HTTP_GET_VARS['option_id']);

        tep_db_query("delete from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id = '" . (int)$option_id . "'");

        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
      case 'delete_value':
        $value_id = tep_db_prepare_input($HTTP_GET_VARS['value_id']);

        tep_db_query("delete from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id = '" . (int)$value_id . "'");
        tep_db_query("delete from " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " where products_options_values_id = '" . (int)$value_id . "'");

        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
      case 'delete_attribute':
        $attribute_id = tep_db_prepare_input($HTTP_GET_VARS['attribute_id']);

        tep_db_query("delete from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_attributes_id = '" . (int)$attribute_id . "'");

// added for DOWNLOAD_ENABLED. Always try to remove attributes, even if downloads are no longer enabled
        tep_db_query("delete from " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " where products_attributes_id = '" . (int)$attribute_id . "'");

        tep_redirect(tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info));
        break;
    }
  }
  // product options values pull down menu array
  $select_options = tep_db_query("select products_options_id, products_options_name from " . TABLE_PRODUCTS_OPTIONS . " where language_id = '" . (int)$languages_id . "' order by products_options_name");
  while ($select_options_values = tep_db_fetch_array($select_options)) {
    $options_array[] = array('id' => $select_options_values['products_options_id'],
                             'text' => $select_options_values['products_options_name']);
  }
   // product name pulldown menu array for adding attributes to
  $select_products = tep_db_query("select p.products_id, pd.products_name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and pd.language_id = '" . $languages_id . "' order by pd.products_name");
  while($select_products_values = tep_db_fetch_array($select_products)) {
    $attributes_array[] = array('id' =>   $select_products_values['products_id'],
                                'text' => $select_products_values['products_name']); 
  }
  //product values array pulldown menu for attribute use
  $select_option_value = tep_db_query("select * from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where language_id ='" . $languages_id . "' order by products_options_values_name");
  while($select_values_values = tep_db_fetch_array($select_option_value)) {
    $values_array[] = array('id' => $select_values_values['products_options_values_id'],
                            'text' => $select_values_values['products_options_values_name']); 
  } 

  require(DIR_WS_INCLUDES . 'template_top.php');
?>

<!-- options and values//-->
          <div class="row">	
<!-- options //-->
            <div class="col-md-6">
              <div class="panel panel-default">
                <div class="panel-heading">                 
				  <?php echo HEADING_TITLE_OPT . "\n"; ?>
                  <div class="pull-right">
<?php
    $options = "select * from " . TABLE_PRODUCTS_OPTIONS . " where language_id = '" . (int)$languages_id . "' order by products_options_id";
    $options_split = new splitPageResults($option_page, MAX_ROW_LISTS_OPTIONS, $options, $options_query_numrows);
    echo $options_split->display_links($options_query_numrows, MAX_ROW_LISTS_OPTIONS, MAX_DISPLAY_PAGE_LINKS, $option_page, 'value_page=' . $value_page . '&attribute_page=' . $attribute_page, 'option_page');
?>
                  </div>
                </div><!--panel-heading -->
                <table class="table table-striped">
                  <thead>			  
                    <tr>
                      <th><?php echo TABLE_HEADING_ID; ?></th>
                      <th><?php echo TABLE_HEADING_OPT_NAME; ?></th>
                      <th class="text-right"><?php echo TABLE_HEADING_ACTION; ?></th>
                    </tr>
                  </thead>
                  <tbody>
<?php
    $next_id = 1;
    $rows = 0;
    $options = tep_db_query($options);
    while ($options_values = tep_db_fetch_array($options)) {
      $rows++;
?>
                    <tr>
                      <td><?php echo $options_values["products_options_id"]; ?></td>
                      <td><?php echo $options_values["products_options_name"]; ?></td>
                      <td class="text-right">
					    <div class="btn-toolbar" role="toolbar">
<?php
      echo '					      <div class="btn-group">' . tep_glyphicon_button(IMAGE_EDIT, 'pencil', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=update_option&option_id=' . $options_values['products_options_id'] . '&' . $page_info), null, 'warning') . '</div>' . "\n" .
           '					      <div class="btn-group">' . tep_glyphicon_button(IMAGE_DELETE, 'remove', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=delete_product_option&option_id=' . $options_values['products_options_id'] . '&' . $page_info), null, 'danger') . '</div>' . "\n";
?>
					    </div>
                      </td>
                    </tr>
<?php
      if ( ($HTTP_GET_VARS['option_id'] == $options_values['products_options_id']) && ($HTTP_GET_VARS['action'])) {
        switch ($action) {
		  case 'delete_product_option':
		    $options = tep_db_query("select products_options_id, products_options_name from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id = '" . (int)$HTTP_GET_VARS['option_id'] . "' and language_id = '" . (int)$languages_id . "'");
		    $options_values = tep_db_fetch_array($options);
            $contents .= '                          <div class="col-md-12">' . "\n";
			$contents .= '                            <h4>'. $options_values['products_options_name'] .'</h4>' . "\n";
			$products = tep_db_query("select p.products_id, pd.products_name, pov.products_options_values_name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov, " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and pov.language_id = '" . (int)$languages_id . "' and pd.language_id = '" . (int)$languages_id . "' and pa.products_id = p.products_id and pa.options_id='" . (int)$HTTP_GET_VARS['option_id'] . "' and pov.products_options_values_id = pa.options_values_id order by pd.products_name");
            if (tep_db_num_rows($products)) {	
			  $alertClass .= ' alert-message alert-message-danger';
			  $contents .= '                            <table class="table table-bordered" id="prdValDel">' . "\n";  
              $contents .= '                              <tr>' . "\n";
              $contents .= '                                <th>' . TABLE_HEADING_ID . '</th>' . "\n";
              $contents .= '                                <th>' . TABLE_HEADING_PRODUCT . '</th>' . "\n";
              $contents .= '                                <th>' . TABLE_HEADING_OPT_VALUE . '</th>' . "\n";
              $contents .= '                              </tr>' . "\n";
              $rows = 0;
              while ($products_values = tep_db_fetch_array($products)) {
                $rows++;
                $contents .= '                              <tr>' . "\n";
                $contents .= '                                <td>' . $products_values['products_id'] . '</td>' . "\n";
                $contents .= '                                <td>' . $products_values['products_name'] . '</td>' . "\n";
                $contents .= '                                <td>' . $products_values['products_options_values_name'] . '</td>' . "\n";
                $contents .= '                              </tr>' . "\n";
              }
              $contents .= '                            </table>' . "\n";
              $contents .= '                            <p>' . TEXT_WARNING_OF_DELETE . '</p>' . "\n";
              $contents .= '                            <span class="pull-right">' . tep_draw_bs_button(IMAGE_BACK, 'chevron-left', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info), null, null, 'btn-default text-danger') . '</span>' . "\n";
			} else {
              $alertClass .= ' alert-message alert-message-notice';
              $contents .= '                            <p>' . TEXT_OK_TO_DELETE . '</p>' . "\n";
              $contents .= '                            <span class="pull-right">' . tep_draw_bs_button(IMAGE_DELETE, 'ban-circle', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=delete_option&option_id=' . $HTTP_GET_VARS['option_id'] . '&' . $page_info), null, null, 'btn-danger') . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info), null, null, 'btn-default text-danger') . '</span>' . "\n";
            }
            $contents .= '                          </div>' . "\n";		
		  break;

		  case 'update_option':
		    $contents .= '                          ' . tep_draw_form('option', FILENAME_PRODUCTS_ATTRIBUTES, 'action=update_option_name&' . $page_info, 'post', 'class="form-inline"') . tep_draw_hidden_field('option_id', $options_values['products_options_id']) . "\n";
            $inputs = '';
            for ($i = 0, $n = sizeof($languages); $i < $n; $i ++) {
			  $option_name = tep_db_query("select products_options_name from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id = '" . $options_values['products_options_id'] . "' and language_id = '" . $languages[$i]['id'] . "'");
              $option_name = tep_db_fetch_array($option_name);
              $inputs .= '                              <div class="form-group">' . "\n" .
                         '                                <div class="input-group">' . "\n" .
                         '                                  <div class="input-group-addon">' . tep_image(tep_catalog_href_link(DIR_WS_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], '', 'SSL'), $languages[$i]['name'])  . '</div>' . "\n" .
                         '                                    ' . tep_draw_input_field('option_name[' . $languages[$i]['id'] . ']', $option_name['products_options_name']) . "\n" .
                         '                                  </div>' . "\n" .
                         '                                </div>' . "\n";  
            }
			$contents .= '                            <div class="col-md-1">' . "\n";
            $contents .= '                              '. $options_values['products_options_id'] . "\n";
			$contents .= '                            </div>' . "\n";
			$contents .= '                            <div class="col-md-7">' . "\n";
            $contents .=                              $inputs;
			$contents .= '                            </div>' . "\n";
			$contents .= '                            <div class="col-md-4 text-right">' . "\n";
            $contents .= '                              ' . tep_draw_bs_button(IMAGE_SAVE, 'ok') . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info)) . "\n";
            $contents .= '                            </div>' . "\n";
		    $contents .= '                          </form>' . "\n";
          break;
        }

        echo '                    <tr class="content-row">' . "\n" .
             '                      <td colspan="3">' . "\n" .
             '                        <div class="row' . $alertClass . '">' . "\n" .
                                        $contents . 
             '                        </div>' . "\n" .
             '                      </td>' . "\n" .
             '                    </tr>' . "\n";			  
      }
	  
      $max_options_id_query = tep_db_query("select max(products_options_id) + 1 as next_id from " . TABLE_PRODUCTS_OPTIONS);
      $max_options_id_values = tep_db_fetch_array($max_options_id_query);
      $next_id = $max_options_id_values['next_id'];
    }
?>
                  </tbody>
                </table>        
<?php
    if (!($HTTP_GET_VARS['action'])) {
?>
                <div class="panel-body content-row">               
<?php
      echo '                  ' . tep_draw_form('options', FILENAME_PRODUCTS_ATTRIBUTES, 'action=add_product_options&' . $page_info, 'post', 'class="form-inline row"'). tep_draw_hidden_field('products_options_id', $next_id) . "\n";
      $inputs = '';
      for ($i = 0, $n = sizeof($languages); $i < $n; $i ++) {
        $inputs .= '                      <div class="form-group">' . "\n" .
                   '                        <div class="input-group">' . "\n" .
                   '                          <div class="input-group-addon">' . tep_image(tep_catalog_href_link(DIR_WS_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], '', 'SSL'), $languages[$i]['name'])  . '</div>' . "\n" .
                   '                          ' . tep_draw_input_field('option_name[' . $languages[$i]['id'] . ']') . "\n" .
                   '                        </div>' . "\n" .
                   '                      </div>' . "\n";
      }
?>
                    <div class="col-md-1"><?php echo $next_id; ?></div>
                    <div class="col-md-7">
<?php echo $inputs; ?>
                    </div>
                    <div class="col-md-4 text-right"><?php echo tep_draw_bs_button(IMAGE_INSERT, 'plus'); ?></div>
                  </form>
                </div>
<?php
    }
?>
              </div><!-- panel -->
            </div><!-- col-md-6 -->
  <!-- eof options -->
  <!-- Values -->
            <div class="col-md-6">
              <div class="panel panel-default">
                <div class="panel-heading">                 
				  <?php echo HEADING_TITLE_VAL . "\n"; ?>
                  <div class="pull-right">                 
<?php
    $values = "select pov.products_options_values_id, pov.products_options_values_name, pov2po.products_options_id from " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov left join " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " pov2po on pov.products_options_values_id = pov2po.products_options_values_id where pov.language_id = '" . (int)$languages_id . "' order by pov.products_options_values_id";
    $values_split = new splitPageResults($value_page, MAX_ROW_LISTS_OPTIONS, $values, $values_query_numrows);
    echo $values_split->display_links($values_query_numrows, MAX_ROW_LISTS_OPTIONS, MAX_DISPLAY_PAGE_LINKS, $value_page, 'option_page=' . $option_page . '&attribute_page=' . $attribute_page, 'value_page');
?>
                  </div>
                </div><!-- panel-heading -->
                <table class="table table-striped">
                  <thead>			  
                    <tr>
                      <th><?php echo TABLE_HEADING_ID; ?></th>
                      <th><?php echo TABLE_HEADING_OPT_NAME; ?></th>
                      <th><?php echo TABLE_HEADING_OPT_VALUE; ?></th>
                      <th class="text-right"><?php echo TABLE_HEADING_ACTION; ?></th>
                    </tr>
                  </thead>
                  <tbody>           	
<?php
    $next_id = 1;
    $rows = 0;
    $values = tep_db_query($values);
    while ($values_values = tep_db_fetch_array($values)) {
      $options_name = tep_options_name($values_values['products_options_id']);
      $values_name = $values_values['products_options_values_name'];
      $rows++;
?>
                    <tr>
                      <td><?php echo $values_values["products_options_values_id"]; ?></td>
                      <td><?php echo $options_name; ?></td>
                      <td><?php echo $values_name; ?></td>
                      <td class="text-right">
<?php
      echo '                        <div class="btn-group">' . tep_glyphicon_button(IMAGE_EDIT, 'pencil', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=update_option_value&value_id=' . $values_values['products_options_values_id'] . '&' . $page_info), null, 'warning') . '</div>' . "\n" .
           '                        <div class="btn-group">' . tep_glyphicon_button(IMAGE_DELETE, 'remove', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=delete_option_value&value_id=' . $values_values['products_options_values_id'] . '&' . $page_info), null, 'danger') . '</div>' . "\n";
?>
                    </tr>                 
<?php                                      
      if (($HTTP_GET_VARS['value_id'] == $values_values['products_options_values_id']) && ($HTTP_GET_VARS['action'])) {       
        switch ($action) {	 
		  case 'delete_option_value':
		    $values = tep_db_query("select products_options_values_id, products_options_values_name from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id = '" . (int)$HTTP_GET_VARS['value_id'] . "' and language_id = '" . (int)$languages_id . "'");
            $values_values = tep_db_fetch_array($values);
			$contents .= '                          <div class="col-md-12">' . "\n";
			
			$products = tep_db_query("select p.products_id, pd.products_name, po.products_options_name, po.products_options_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS . " po, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and po.language_id = '" . (int)$languages_id . "' and pa.products_id = p.products_id and pa.options_values_id='" . (int)$HTTP_GET_VARS['value_id'] . "' and po.products_options_id = pa.options_id order by pd.products_name");
            if (tep_db_num_rows($products)) {
		      $alertClass .= ' alert-message alert-message-danger';
              $contents .= '                            <table class="table table-bordered">' . "\n";
              $contents .= '                              <thead>' . "\n";			  
              $contents .= '                                <tr>' . "\n";
              $contents .= '                                <th>' . TABLE_HEADING_ID . '</th>' . "\n";
              $contents .= '                                <th>' . TABLE_HEADING_PRODUCT . '</th>' . "\n";
              $contents .= '                                <th>' . TABLE_HEADING_OPT_NAME . '</th>' . "\n";
              $contents .= '                              </tr>' . "\n";
              $contents .= '                            </thead>' . "\n";
              $contents .= '                            <tbody>' . "\n";
              while ($products_values = tep_db_fetch_array($products)) {
                $rows++;
                $contents .= '                              <tr>' . "\n";
                $contents .= '                                <td>' . $products_values['products_id'] . '</td>' . "\n";
                $contents .= '                                <td>' . $products_values['products_name'] . '</td>' . "\n";
                $contents .= '                                <td>' . $products_values['products_options_name'] . '</td>' . "\n";
                $contents .= '                              </tr>' . "\n";
			  
              }
              $contents .= '                            </tbody>' . "\n";
              $contents .= '                          </table>' . "\n";
			  $contents .= '                          <p>' . TEXT_WARNING_OF_DELETE . '</p>' . "\n";
              $contents .= '                          <span class="pull-right">' . tep_draw_bs_button(IMAGE_BACK, 'chevron-left', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info), null, null, 'btn-default text-danger') . '</span>' . "\n";
			} else {
				
			  $alertClass .= ' alert-message alert-message-notice';
              $contents .= '                          <p>' . TEXT_OK_TO_DELETE . '</p>' . "\n";
              $contents .= '                          <span class="pull-right">' . tep_draw_bs_button(IMAGE_DELETE, 'trash', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=delete_value&value_id=' . $HTTP_GET_VARS['value_id'] . '&' . $page_info), null, null, 'btn-danger') . '<br>' . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info), null, null, 'btn-default text-danger') . '</span>' . "\n";
            }
			$contents .= '                        </div>' . "\n";
          break;
		  
		  case 'update_option_value':
		    $contents .= '                        ' . tep_draw_form('values', FILENAME_PRODUCTS_ATTRIBUTES, 'action=update_value&' . $page_info, 'post', 'class="form-inline"') . tep_draw_hidden_field('value_id', $values_values['products_options_values_id']) . "\n";
            $inputs = '';
            for ($i = 0, $n = sizeof($languages); $i < $n; $i ++) {
			  $value_name = tep_db_query("select products_options_values_name from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id = '" . (int)$values_values['products_options_values_id'] . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
              $value_name = tep_db_fetch_array($value_name);
              $inputs .= '                            <div class="form-group">' . "\n" .
                         '                              <div class="input-group">' . "\n" .
                         '                                <div class="input-group-addon">' . tep_image(tep_catalog_href_link(DIR_WS_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], '', 'SSL'), $languages[$i]['name'])  . '</div>' . "\n" .
                         '                                  ' . tep_draw_input_field('value_name[' . $languages[$i]['id'] . ']', $value_name['products_options_values_name']) . "\n" .
                         '                                </div>' . "\n" .
                         '                              </div>' . "\n";		  
            }
			$contents .= '                          <div class="col-md-1">' . "\n";
            $contents .= '                            '. $values_values['products_options_values_id'] . "\n";
			$contents .= '                          </div>' . "\n";
			$contents .= '                          <div class="col-md-7">' . "\n";
            $contents .= '                            ' . tep_draw_pull_down_menu('option_id', $options_array, $values_values['products_options_id']) . "\n";
            $contents .=                            $inputs;
			$contents .= '                          </div>' . "\n";
			$contents .= '                          <div class="col-md-4 text-right">' . "\n";
            $contents .= '                            ' . tep_draw_bs_button(IMAGE_SAVE, 'ok'). '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info)) . "\n";
            $contents .= '                          </div>' . "\n";
		    $contents .= '                        </form>' . "\n";
          break;
        }
		
		echo '                    <tr class="content-row">' . "\n" .
             '                      <td colspan="4">' . "\n" .
             '                        <div class="row' . $alertClass . '">' . "\n" .
                                        $contents . 
             '                        </div>' . "\n" .
             '                      </td>' . "\n" .
             '                    </tr>' . "\n";		  
		} // eof if

      $max_values_id_query = tep_db_query("select max(products_options_values_id) + 1 as next_id from " . TABLE_PRODUCTS_OPTIONS_VALUES);
      $max_values_id_values = tep_db_fetch_array($max_values_id_query);
      $next_id = $max_values_id_values['next_id'];
    }
?>
                  </tbody>
                </table>                     
<?php
    if (!($HTTP_GET_VARS['action'])) {
?>
                <div class="panel-body content-row">  
<?php 
	  echo '                  ' . tep_draw_form('values', FILENAME_PRODUCTS_ATTRIBUTES, 'action=add_product_option_values&' . $page_info, 'post', 'class="form-inline row"') . "\n";
      $inputs = '';
      for ($i = 0, $n = sizeof($languages); $i < $n; $i ++) {
        $inputs .= '				        <div class="form-group">' . "\n" .
                   '				          <div class="input-group">' . "\n" .
                   '                            <div class="input-group-addon">' . tep_image(tep_catalog_href_link(DIR_WS_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], '', 'SSL'), $languages[$i]['name'])  . '</div>' . "\n" .
                   '                            ' . tep_draw_input_field('value_name[' . $languages[$i]['id'] . ']') . "\n" .
                   '                          </div>' . "\n" .
                   '				        </div>' . "\n";	
      }
?>              
                      <div class="col-md-1"><?php echo $next_id; ?></div>
                      <div class="col-md-7">  
				        <?php echo tep_draw_pull_down_menu('option_id', $options_array) . "\n" .
				        $inputs; ?>
                      </div>
                      <div class="col-md-4 text-right"><?php echo tep_draw_bs_button(IMAGE_INSERT, 'plus'); ?></div>
                    </form>
                  </div>
<?php
    } 
?>
                </div><!-- panel-->
              </div><!--col-->
<!-- option value eof //-->
            </div><!--row end of options and values -->

<!-- products_attributes //--> 
          <div class="row">	
            <div class="col-md-12">
              <div class="panel panel-default">
                <div class="panel-heading"><?php echo HEADING_TITLE_ATRIB . "\n"; ?>
                  <div class="pull-right">     
<?php
  $attributes = "select pa.* from " . TABLE_PRODUCTS_ATTRIBUTES . " pa left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on pa.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' order by pd.products_name";
  $attributes_split = new splitPageResults($attribute_page, MAX_ROW_LISTS_OPTIONS, $attributes, $attributes_query_numrows);
  echo $attributes_split->display_links($attributes_query_numrows, MAX_ROW_LISTS_OPTIONS, MAX_DISPLAY_PAGE_LINKS, $attribute_page, 'option_page=' . $option_page . '&value_page=' . $value_page, 'attribute_page');
?>
                  </div>
                </div><!-- panel heading -->
<?php
  if ($action == 'update_attribute') {
    $form_action = 'update_product_attribute';
  } else {
    $form_action = 'add_product_attributes';
  }
  
  echo '                ' . tep_draw_form('attributes', FILENAME_PRODUCTS_ATTRIBUTES, 'action=' . $form_action . '&' . $page_info, 'post', 'class="form-inline"') . "\n"; ?>
                  <table id="product_attributes" class="table table-striped table-bordered">
                    <thead> 
                      <tr>
                        <th><?php echo TABLE_HEADING_ID; ?></th>
                        <th><?php echo TABLE_HEADING_PRODUCT; ?></th>
                        <th><?php echo TABLE_HEADING_OPT_NAME; ?></th>
                        <th><?php echo TABLE_HEADING_OPT_VALUE; ?></th>
                        <th><?php echo TABLE_HEADING_OPT_PRICE; ?></th>
                        <th><?php echo TABLE_HEADING_OPT_PRICE_PREFIX; ?></th>
                        <th class="text-right"><?php echo TABLE_HEADING_ACTION; ?></th>
                      </tr>         
                    </thead>
                    <tbody>
<?php
  $next_id = 1;
  $attributes = tep_db_query($attributes);
  while ($attributes_values = tep_db_fetch_array($attributes)) {
    $products_name_only = tep_get_products_name($attributes_values['products_id']);
    $options_name = tep_options_name($attributes_values['options_id']);
    $values_name = tep_values_name($attributes_values['options_values_id']);
    $rows++;

    if (($action == 'update_attribute') && ($_GET['attribute_id'] == $attributes_values['products_attributes_id'])) { 
?>
                      <tr class="content-row">
                        <td><?php echo $attributes_values['products_attributes_id'] . tep_draw_hidden_field('attribute_id', $attributes_values['products_attributes_id']); ?></td>
                        <td><?php echo tep_draw_pull_down_menu('products_id', $attributes_array, $attributes_values['products_id']); ?></td>
                        <td><?php echo tep_draw_pull_down_menu('options_id', $options_array, $attributes_values['options_id']); ?></td>
                        <td><?php echo tep_draw_pull_down_menu('values_id', $values_array, $attributes_values['options_values_id']); ?></td>
                        <td><?php echo tep_draw_input_field('value_price', $attributes_values['options_values_price'], 'size="4"'); ?></td>
                        <td><?php echo tep_draw_input_field('price_prefix', $attributes_values['price_prefix'], 'size="2"'); ?></td>
                        <td class="text-right"><?php echo tep_draw_bs_button(IMAGE_SAVE, 'ok') . '  ' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info)); ?>
                        </td>
                      </tr>
<?php
      if (DOWNLOAD_ENABLED == 'true') {
        $download_query_raw ="select products_attributes_filename, products_attributes_maxdays, products_attributes_maxcount from " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " where products_attributes_id='" . $attributes_values['products_attributes_id'] . "'";
        $download_query = tep_db_query($download_query_raw);
        if (tep_db_num_rows($download_query) > 0) {
          $download = tep_db_fetch_array($download_query);
          $products_attributes_filename = $download['products_attributes_filename'];
          $products_attributes_maxdays  = $download['products_attributes_maxdays'];
          $products_attributes_maxcount = $download['products_attributes_maxcount'];
        }
?>
                      <tr class="download-row">
                        <td colspan="7">
				          <button type="button" class="btn btn-default btn-block" data-toggle="collapse" data-target="#downloads"><?php echo tep_glyphicon('download-alt glyphicon-lg') . TABLE_HEADING_DOWNLOAD; ?></button>
				          <div id="downloads" class="collapse">
                          <br>
                            <div class="col-md-4">
                            <label class="sr-only" for="products_attributes_filename"><?php echo TABLE_TEXT_FILENAME; ?></label>
                              <div class="form-group">
                                <div class="input-group">
                                  <div class="input-group-addon"><?php echo tep_glyphicon('file'); ?></div>
								  <?php echo tep_draw_input_field('products_attributes_filename', $products_attributes_filename, 'placeholder="' . TABLE_TEXT_FILENAME . '"'); ?>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4">
                            <label class="sr-only" for="products_attributes_maxdays"><?php echo TABLE_TEXT_MAX_DAYS; ?></label>
                              <div class="form-group">
                                <div class="input-group">
                                  <div class="input-group-addon"><?php echo tep_glyphicon('calendar'); ?></div>
								  <?php echo tep_draw_input_field('products_attributes_maxdays', $products_attributes_maxdays, 'placeholder="' . TABLE_TEXT_MAX_DAYS . '"'); ?>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4">
                            <label class="sr-only" for="products_attributes_maxcount"><?php echo TABLE_TEXT_MAX_COUNT; ?></label>
                              <div class="form-group">
                                <div class="input-group">
                                  <div class="input-group-addon"><?php echo tep_glyphicon('download'); ?></div>
								  <?php echo tep_draw_input_field('products_attributes_maxcount', $products_attributes_maxcount, 'placeholder="' . TABLE_TEXT_MAX_COUNT . '"'); ?>
                                </div>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
<?php
      }
    } elseif (($action == 'delete_product_attribute') && ($HTTP_GET_VARS['attribute_id'] == $attributes_values['products_attributes_id'])) {
?>
                      <tr class="alert-message alert-message-danger">
                        <td><strong><?php echo $attributes_values["products_attributes_id"]; ?></strong></td>
                        <td><strong><?php echo $products_name_only; ?></strong></td>
                        <td><strong><?php echo $options_name; ?></strong></td>
                        <td><strong><?php echo $values_name; ?></strong></td>
                        <td><strong><?php echo $attributes_values["options_values_price"]; ?></strong></td>
                        <td><strong><?php echo $attributes_values["price_prefix"]; ?></strong></td>
                        <td><?php echo tep_draw_bs_button(IMAGE_DELETE, 'ban-circle', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=delete_attribute&attribute_id=' . $HTTP_GET_VARS['attribute_id'] . '&' . $page_info), null, null, 'btn-danger') . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, $page_info), null, null, 'btn-default text-danger'); ?></td>
                      </tr>
<?php
    } else {
?>
                      <tr>
                        <td><?php echo $attributes_values["products_attributes_id"]; ?></td>
                        <td><?php echo $products_name_only; ?></td>
                        <td><?php echo $options_name; ?></td>
                        <td><?php echo $values_name; ?></td>
                        <td><?php echo $attributes_values["options_values_price"]; ?></td>
                        <td><?php echo $attributes_values["price_prefix"]; ?></td>
                        <td class="text-right">
					      <div class="btn-toolbar" role="toolbar">
<?php
      echo '					      <div class="btn-group">' . tep_glyphicon_button(IMAGE_EDIT, 'pencil', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=update_attribute&attribute_id=' . $attributes_values['products_attributes_id'] . '&' . $page_info), null, 'warning') . '</div>' . "\n" .
           '					      <div class="btn-group">' . tep_glyphicon_button(IMAGE_DELETE, 'remove', tep_href_link(FILENAME_PRODUCTS_ATTRIBUTES, 'action=delete_product_attribute&attribute_id=' . $attributes_values['products_attributes_id'] . '&' . $page_info), null, 'danger') . '</div>' . "\n";
?>
					      </div>
                        </td>
                      </tr>
<?php
    }
	
    $max_attributes_id_query = tep_db_query("select max(products_attributes_id) + 1 as next_id from " . TABLE_PRODUCTS_ATTRIBUTES);
    $max_attributes_id_values = tep_db_fetch_array($max_attributes_id_query);
    $next_id = $max_attributes_id_values['next_id'];
  }
  
  if (!($HTTP_GET_VARS['action'])) {
?>
                      <tr>
                        <td><?php echo $next_id; ?></td>
                        <td><?php echo tep_draw_pull_down_menu('products_id', $attributes_array); ?></td>
                        <td><?php echo tep_draw_pull_down_menu('options_id', $options_array); ?></td>
                        <td><?php echo tep_draw_pull_down_menu('values_id', $values_array); ?></td>
						<td><?php echo tep_draw_input_field('value_price', null, 'size="4"'); ?></td>
                        <td><?php echo tep_draw_input_field('price_prefix', '+', 'size="2"'); ?></td>
                        <td class="text-right"><?php echo tep_draw_bs_button(IMAGE_INSERT, 'plus'); ?></td>
                      </tr>
<?php
    if (DOWNLOAD_ENABLED == 'true') {
      $products_attributes_maxdays  = DOWNLOAD_MAX_DAYS;
      $products_attributes_maxcount = DOWNLOAD_MAX_COUNT;
?>
                        <tr class="download-row">
                          <td colspan="7">
                            <button type="button" class="btn btn-default btn-block" data-toggle="collapse" data-target="#downloads"><?php echo tep_glyphicon('download-alt glyphicon-lg') . TABLE_HEADING_DOWNLOAD; ?></button>
				            <div id="downloads" class="collapse">
                            <br>
                              <div class="col-md-4">
                                <label class="sr-only" for="products_attributes_filename"><?php echo TABLE_TEXT_FILENAME; ?></label>
                                <div class="form-group">
                                  <div class="input-group">
                                    <div class="input-group-addon"><?php echo tep_glyphicon('file'); ?></div>
									<?php echo tep_draw_input_field('products_attributes_filename', $products_attributes_filename, 'placeholder="' . TABLE_TEXT_FILENAME . '"'); ?>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <label class="sr-only" for="products_attributes_maxdays"><?php echo TABLE_TEXT_MAX_DAYS; ?></label>
                                <div class="form-group">
                                  <div class="input-group">
                                    <div class="input-group-addon"><?php echo tep_glyphicon('calendar'); ?></div>
									<?php echo tep_draw_input_field('products_attributes_maxdays', $products_attributes_maxdays, 'placeholder="' . TABLE_TEXT_MAX_DAYS . '"'); ?>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <label class="sr-only" for="products_attributes_maxcount"><?php echo TABLE_TEXT_MAX_COUNT; ?></label>
                                <div class="form-group">
                                  <div class="input-group">
                                    <div class="input-group-addon"><?php echo tep_glyphicon('download'); ?></div>
								    <?php echo tep_draw_input_field('products_attributes_maxcount', $products_attributes_maxcount, 'placeholder="' . TABLE_TEXT_MAX_COUNT . '"'); ?>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>
                        </tr>            
<?php
      } // end of DOWNLOAD_ENABLED section
  }
?>
                    </tbody>    
                  </table>
                </form>
              </div><!-- panel -->
            </div>
          </div>
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>