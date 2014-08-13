<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'setflag':
        if ( ($HTTP_GET_VARS['flag'] == '0') || ($HTTP_GET_VARS['flag'] == '1') ) {
          if (isset($HTTP_GET_VARS['pID'])) {
            tep_set_product_status($HTTP_GET_VARS['pID'], $HTTP_GET_VARS['flag']);
          }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('categories');
            tep_reset_cache_block('also_purchased');
          }
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $HTTP_GET_VARS['cPath'] . '&pID=' . $HTTP_GET_VARS['pID']));
        break;
      case 'insert_category':
      case 'update_category':
        if (isset($HTTP_POST_VARS['categories_id'])) $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
        $sort_order = tep_db_prepare_input($HTTP_POST_VARS['sort_order']);

        $sql_data_array = array('sort_order' => (int)$sort_order);

        if ($action == 'insert_category') {
          $insert_sql_data = array('parent_id' => $current_category_id,
                                   'date_added' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          tep_db_perform(TABLE_CATEGORIES, $sql_data_array);

          $categories_id = tep_db_insert_id();
        } elseif ($action == 'update_category') {
          $update_sql_data = array('last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform(TABLE_CATEGORIES, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "'");
        }

        $languages = tep_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $categories_name_array = $HTTP_POST_VARS['categories_name'];

          $language_id = $languages[$i]['id'];

          $sql_data_array = array('categories_name' => tep_db_prepare_input($categories_name_array[$language_id]));

          if ($action == 'insert_category') {
            $insert_sql_data = array('categories_id' => $categories_id,
                                     'language_id' => $languages[$i]['id']);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array);
          } elseif ($action == 'update_category') {
            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
          }
        }

        $categories_image = new upload('categories_image');
        $categories_image->set_destination(DIR_FS_CATALOG_IMAGES);

        if ($categories_image->parse() && $categories_image->save()) {
          tep_db_query("update " . TABLE_CATEGORIES . " set categories_image = '" . tep_db_input($categories_image->filename) . "' where categories_id = '" . (int)$categories_id . "'");
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $categories_id));
        break;
      case 'delete_category_confirm':
        if (isset($HTTP_POST_VARS['categories_id'])) {
          $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);

          $categories = tep_get_category_tree($categories_id, '', '0', '', true);
          $products = array();
          $products_delete = array();

          for ($i=0, $n=sizeof($categories); $i<$n; $i++) {
            $product_ids_query = tep_db_query("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id = '" . (int)$categories[$i]['id'] . "'");

            while ($product_ids = tep_db_fetch_array($product_ids_query)) {
              $products[$product_ids['products_id']]['categories'][] = $categories[$i]['id'];
            }
          }

          reset($products);
          while (list($key, $value) = each($products)) {
            $category_ids = '';

            for ($i=0, $n=sizeof($value['categories']); $i<$n; $i++) {
              $category_ids .= "'" . (int)$value['categories'][$i] . "', ";
            }
            $category_ids = substr($category_ids, 0, -2);

            $check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$key . "' and categories_id not in (" . $category_ids . ")");
            $check = tep_db_fetch_array($check_query);
            if ($check['total'] < '1') {
              $products_delete[$key] = $key;
            }
          }

// removing categories can be a lengthy process
          tep_set_time_limit(0);
          for ($i=0, $n=sizeof($categories); $i<$n; $i++) {
            tep_remove_category($categories[$i]['id']);
          }

          reset($products_delete);
          while (list($key) = each($products_delete)) {
            tep_remove_product($key);
          }
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath));
        break;
      case 'delete_product_confirm':
        if (isset($HTTP_POST_VARS['products_id']) && isset($HTTP_POST_VARS['product_categories']) && is_array($HTTP_POST_VARS['product_categories'])) {
          $product_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
          $product_categories = $HTTP_POST_VARS['product_categories'];

          for ($i=0, $n=sizeof($product_categories); $i<$n; $i++) {
            tep_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "' and categories_id = '" . (int)$product_categories[$i] . "'");
          }

          $product_categories_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "'");
          $product_categories = tep_db_fetch_array($product_categories_query);

          if ($product_categories['total'] == '0') {
            tep_remove_product($product_id);
          }
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath));
        break;
      case 'move_category_confirm':
        if (isset($HTTP_POST_VARS['categories_id']) && ($HTTP_POST_VARS['categories_id'] != $HTTP_POST_VARS['move_to_category_id'])) {
          $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
          $new_parent_id = tep_db_prepare_input($HTTP_POST_VARS['move_to_category_id']);

          $path = explode('_', tep_get_generated_category_path_ids($new_parent_id));

          if (in_array($categories_id, $path)) {
            $messageStack->add_session(ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT, 'error');

            tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $categories_id));
          } else {
            tep_db_query("update " . TABLE_CATEGORIES . " set parent_id = '" . (int)$new_parent_id . "', last_modified = now() where categories_id = '" . (int)$categories_id . "'");

            if (USE_CACHE == 'true') {
              tep_reset_cache_block('categories');
              tep_reset_cache_block('also_purchased');
            }

            tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&cID=' . $categories_id));
          }
        }

        break;
      case 'move_product_confirm':
        $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
        $new_parent_id = tep_db_prepare_input($HTTP_POST_VARS['move_to_category_id']);

        $duplicate_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$new_parent_id . "'");
        $duplicate_check = tep_db_fetch_array($duplicate_check_query);
        if ($duplicate_check['total'] < 1) tep_db_query("update " . TABLE_PRODUCTS_TO_CATEGORIES . " set categories_id = '" . (int)$new_parent_id . "' where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$current_category_id . "'");

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&pID=' . $products_id));
        break;
      case 'insert_product':
      case 'update_product':
        if (isset($HTTP_GET_VARS['pID'])) $products_id = tep_db_prepare_input($HTTP_GET_VARS['pID']);
        $products_date_available = tep_db_prepare_input($HTTP_POST_VARS['products_date_available']);

        $products_date_available = (date('Y-m-d') < $products_date_available) ? $products_date_available : 'null';

        $sql_data_array = array('products_quantity' => (int)tep_db_prepare_input($HTTP_POST_VARS['products_quantity']),
                                'products_model' => tep_db_prepare_input($HTTP_POST_VARS['products_model']),
                                'products_price' => tep_db_prepare_input($HTTP_POST_VARS['products_price']),
                                'products_date_available' => $products_date_available,
                                'products_weight' => (float)tep_db_prepare_input($HTTP_POST_VARS['products_weight']),
                                'products_status' => tep_db_prepare_input($HTTP_POST_VARS['products_status']),
                                'products_tax_class_id' => tep_db_prepare_input($HTTP_POST_VARS['products_tax_class_id']),
                                'manufacturers_id' => (int)tep_db_prepare_input($HTTP_POST_VARS['manufacturers_id']));

        $products_image = new upload('products_image');
        $products_image->set_destination(DIR_FS_CATALOG_IMAGES);
        if ($products_image->parse() && $products_image->save()) {
          $sql_data_array['products_image'] = tep_db_prepare_input($products_image->filename);
        }

        if ($action == 'insert_product') {
          $insert_sql_data = array('products_date_added' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
          $products_id = tep_db_insert_id();

          tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$current_category_id . "')");
        } elseif ($action == 'update_product') {
          $update_sql_data = array('products_last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "'");
        }

        $languages = tep_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $language_id = $languages[$i]['id'];

          $sql_data_array = array('products_name' => tep_db_prepare_input($HTTP_POST_VARS['products_name'][$language_id]),
                                  'products_description' => tep_db_prepare_input($HTTP_POST_VARS['products_description'][$language_id]),
                                  'products_url' => tep_db_prepare_input($HTTP_POST_VARS['products_url'][$language_id]));

          if ($action == 'insert_product') {
            $insert_sql_data = array('products_id' => $products_id,
                                     'language_id' => $language_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
          } elseif ($action == 'update_product') {
            tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and language_id = '" . (int)$language_id . "'");
          }
        }

        $pi_sort_order = 0;
        $piArray = array(0);

        foreach ($HTTP_POST_FILES as $key => $value) {
// Update existing large product images
          if (preg_match('/^products_image_large_([0-9]+)$/', $key, $matches)) {
            $pi_sort_order++;

            $sql_data_array = array('htmlcontent' => tep_db_prepare_input($HTTP_POST_VARS['products_image_htmlcontent_' . $matches[1]]),
                                    'sort_order' => $pi_sort_order);

            $t = new upload($key);
            $t->set_destination(DIR_FS_CATALOG_IMAGES);
            if ($t->parse() && $t->save()) {
              $sql_data_array['image'] = tep_db_prepare_input($t->filename);
            }

            tep_db_perform(TABLE_PRODUCTS_IMAGES, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and id = '" . (int)$matches[1] . "'");

            $piArray[] = (int)$matches[1];
          } elseif (preg_match('/^products_image_large_new_([0-9]+)$/', $key, $matches)) {
// Insert new large product images
            $sql_data_array = array('products_id' => (int)$products_id,
                                    'htmlcontent' => tep_db_prepare_input($HTTP_POST_VARS['products_image_htmlcontent_new_' . $matches[1]]));

            $t = new upload($key);
            $t->set_destination(DIR_FS_CATALOG_IMAGES);
            if ($t->parse() && $t->save()) {
              $pi_sort_order++;

              $sql_data_array['image'] = tep_db_prepare_input($t->filename);
              $sql_data_array['sort_order'] = $pi_sort_order;

              tep_db_perform(TABLE_PRODUCTS_IMAGES, $sql_data_array);

              $piArray[] = tep_db_insert_id();
            }
          }
        }

        $product_images_query = tep_db_query("select image from " . TABLE_PRODUCTS_IMAGES . " where products_id = '" . (int)$products_id . "' and id not in (" . implode(',', $piArray) . ")");
        if (tep_db_num_rows($product_images_query)) {
          while ($product_images = tep_db_fetch_array($product_images_query)) {
            $duplicate_image_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_IMAGES . " where image = '" . tep_db_input($product_images['image']) . "'");
            $duplicate_image = tep_db_fetch_array($duplicate_image_query);

            if ($duplicate_image['total'] < 2) {
              if (file_exists(DIR_FS_CATALOG_IMAGES . $product_images['image'])) {
                @unlink(DIR_FS_CATALOG_IMAGES . $product_images['image']);
              }
            }
          }

          tep_db_query("delete from " . TABLE_PRODUCTS_IMAGES . " where products_id = '" . (int)$products_id . "' and id not in (" . implode(',', $piArray) . ")");
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products_id));
        break;
      case 'copy_to_confirm':
        if (isset($HTTP_POST_VARS['products_id']) && isset($HTTP_POST_VARS['categories_id'])) {
          $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
          $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);

          if ($HTTP_POST_VARS['copy_as'] == 'link') {
            if ($categories_id != $current_category_id) {
              $check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$categories_id . "'");
              $check = tep_db_fetch_array($check_query);
              if ($check['total'] < '1') {
                tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$categories_id . "')");
              }
            } else {
              $messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
            }
          } elseif ($HTTP_POST_VARS['copy_as'] == 'duplicate') {
            $product_query = tep_db_query("select products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_tax_class_id, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
            $product = tep_db_fetch_array($product_query);

            tep_db_query("insert into " . TABLE_PRODUCTS . " (products_quantity, products_model,products_image, products_price, products_date_added, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id) values ('" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', '" . tep_db_input($product['products_price']) . "',  now(), " . (empty($product['products_date_available']) ? "null" : "'" . tep_db_input($product['products_date_available']) . "'") . ", '" . tep_db_input($product['products_weight']) . "', '0', '" . (int)$product['products_tax_class_id'] . "', '" . (int)$product['manufacturers_id'] . "')");
            $dup_products_id = tep_db_insert_id();

            $description_query = tep_db_query("select language_id, products_name, products_description, products_url from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$products_id . "'");
            while ($description = tep_db_fetch_array($description_query)) {
              tep_db_query("insert into " . TABLE_PRODUCTS_DESCRIPTION . " (products_id, language_id, products_name, products_description, products_url, products_viewed) values ('" . (int)$dup_products_id . "', '" . (int)$description['language_id'] . "', '" . tep_db_input($description['products_name']) . "', '" . tep_db_input($description['products_description']) . "', '" . tep_db_input($description['products_url']) . "', '0')");
            }

            $product_images_query = tep_db_query("select image, htmlcontent, sort_order from " . TABLE_PRODUCTS_IMAGES . " where products_id = '" . (int)$products_id . "'");
            while ($product_images = tep_db_fetch_array($product_images_query)) {
              tep_db_query("insert into " . TABLE_PRODUCTS_IMAGES . " (products_id, image, htmlcontent, sort_order) values ('" . (int)$dup_products_id . "', '" . tep_db_input($product_images['image']) . "', '" . tep_db_input($product_images['htmlcontent']) . "', '" . tep_db_input($product_images['sort_order']) . "')");
            }

            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$dup_products_id . "', '" . (int)$categories_id . "')");
            $products_id = $dup_products_id;
          }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('categories');
            tep_reset_cache_block('also_purchased');
          }
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $categories_id . '&pID=' . $products_id));
        break;
    }
  }

// check if the catalog image directory exists
  if (is_dir(DIR_FS_CATALOG_IMAGES)) {
    if (!tep_is_writable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
  } else {
    $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
  }

  require(DIR_WS_INCLUDES . 'template_top.php');

  if ($action == 'new_product') {
    $parameters = array('products_name' => '',
                       'products_description' => '',
                       'products_url' => '',
                       'products_id' => '',
                       'products_quantity' => '',
                       'products_model' => '',
                       'products_image' => '',
                       'products_larger_images' => array(),
                       'products_price' => '',
                       'products_weight' => '',
                       'products_date_added' => '',
                       'products_last_modified' => '',
                       'products_date_available' => '',
                       'products_status' => '',
                       'products_tax_class_id' => '',
                       'manufacturers_id' => '');

    $pInfo = new objectInfo($parameters);

    if (isset($HTTP_GET_VARS['pID']) && empty($HTTP_POST_VARS)) {
      $product_query = tep_db_query("select pd.products_name, pd.products_description, pd.products_url, p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'");
      $product = tep_db_fetch_array($product_query);

      $pInfo->objectInfo($product);

      $product_images_query = tep_db_query("select id, image, htmlcontent, sort_order from " . TABLE_PRODUCTS_IMAGES . " where products_id = '" . (int)$product['products_id'] . "' order by sort_order");
      while ($product_images = tep_db_fetch_array($product_images_query)) {
        $pInfo->products_larger_images[] = array('id' => $product_images['id'],
                                                 'image' => $product_images['image'],
                                                 'htmlcontent' => $product_images['htmlcontent'],
                                                 'sort_order' => $product_images['sort_order']);
      }
    }

    $manufacturers_array = array(array('id' => '', 'text' => TEXT_NONE));
    $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " order by manufacturers_name");
    while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
      $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                                     'text' => $manufacturers['manufacturers_name']);
    }

    $tax_class_array = array(array('id' => '0', 'text' => TEXT_NONE));
    $tax_class_query = tep_db_query("select tax_class_id, tax_class_title from " . TABLE_TAX_CLASS . " order by tax_class_title");
    while ($tax_class = tep_db_fetch_array($tax_class_query)) {
      $tax_class_array[] = array('id' => $tax_class['tax_class_id'],
                                 'text' => $tax_class['tax_class_title']);
    }

    $languages = tep_get_languages();

    if (!isset($pInfo->products_status)) $pInfo->products_status = '1';
    switch ($pInfo->products_status) {
      case '0': $in_status = false; $out_status = true; break;
      case '1':
      default: $in_status = true; $out_status = false;
    }

    $form_action = (isset($HTTP_GET_VARS['pID'])) ? 'update_product' : 'insert_product';
?>
<script type="text/javascript"><!--
var tax_rates = new Array();
<?php
    for ($i=0, $n=sizeof($tax_class_array); $i<$n; $i++) {
      if ($tax_class_array[$i]['id'] > 0) {
        echo 'tax_rates["' . $tax_class_array[$i]['id'] . '"] = ' . tep_get_tax_rate_value($tax_class_array[$i]['id']) . ';' . "\n";
      }
    }
?>

function doRound(x, places) {
  return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
}

function getTaxRate() {
  var selected_value = document.forms["new_product"].products_tax_class_id.selectedIndex;
  var parameterVal = document.forms["new_product"].products_tax_class_id[selected_value].value;

  if ( (parameterVal > 0) && (tax_rates[parameterVal] > 0) ) {
    return tax_rates[parameterVal];
  } else {
    return 0;
  }
}

function updateGross() {
  var taxRate = getTaxRate();
  var grossValue = document.forms["new_product"].products_price.value;

  if (taxRate > 0) {
    grossValue = grossValue * ((taxRate / 100) + 1);
  }

  document.forms["new_product"].products_price_gross.value = doRound(grossValue, 4);
}

function updateNet() {
  var taxRate = getTaxRate();
  var netValue = document.forms["new_product"].products_price_gross.value;

  if (taxRate > 0) {
    netValue = netValue / ((taxRate / 100) + 1);
  }

  document.forms["new_product"].products_price.value = doRound(netValue, 4);
}
//--></script>
          <?php echo  tep_draw_form('new_product', FILENAME_CATEGORIES, 'cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=' . $form_action, 'post', 'enctype="multipart/form-data"'); ?>

            <div class="page-header">
              <h1><?php echo sprintf(TEXT_NEW_PRODUCT, tep_output_generated_category_path($current_category_id)); ?></h1>
            </div>
              		  
<?php    
	$navtabs = '';
	$tabcontent = '';
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
	  
	  $navtabs .= '                      <li '. (($i == 0) ? 'class="active"' : '') .'>' . "\n";
	  $navtabs .= '                        <a href="#tab' . $i . '" data-toggle="tab">' . tep_image(tep_catalog_href_link(DIR_WS_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], '', 'SSL'), $languages[$i]['name'], null, null, null, false) . '</a>' . "\n";
	  $navtabs .= '                        </li>' . "\n";
	  
	  
	  // now the content for each language
	  $tabcontent .= '                      <div class="tab-pane fade'. (($i == 0) ? ' active in' : '') .'" id="tab' . $i . '">' . "\n"; 
	  $tabcontent .= '                        <label>' . TEXT_PRODUCTS_NAME . '</label>' . "\n";
	  $tabcontent .= '                        ' . tep_draw_input_field('products_name[' . $languages[$i]['id'] . ']', (empty($pInfo->products_id) ? '' : tep_get_products_name($pInfo->products_id, $languages[$i]['id']))) . "\n";
	  
	  
      $tabcontent .= '                        <label>' . TEXT_PRODUCTS_DESCRIPTION . '</label>' . "\n";
	  $tabcontent .= '                        ' . tep_draw_textarea_field('products_description[' . $languages[$i]['id'] . ']', 'soft', '70', '15', (empty($pInfo->products_id) ? '' : tep_get_products_description($pInfo->products_id, $languages[$i]['id']))) . "\n";
	  
	   $tabcontent .= '                        <label>' . TEXT_PRODUCTS_URL . ' <small>' . TEXT_PRODUCTS_URL_WITHOUT_HTTP . '</small></label>' . "\n";
	  $tabcontent .= '                        ' . tep_draw_input_field('products_url[' . $languages[$i]['id'] . ']', (isset($products_url[$languages[$i]['id']]) ? stripslashes($products_url[$languages[$i]['id']]) : tep_get_products_url($pInfo->products_id, $languages[$i]['id']))) . "\n";  
	  
	  
	  $tabcontent .= '                      </div>' . "\n"; 

    }
?> 
            <div class="row">
              <div class="col-md-9">
                <div class="panel tabbed-heading panel-default">
                  <div class="panel-heading">
                    <ul class="nav nav-tabs" role="tablist">
<?php                    
	echo $navtabs;
?>                              
                    </ul>
                  </div>
                  <div class="panel-body">
                    <div class="tab-content">
<?php
    echo $tabcontent;
?>                     
                    </div>
                  </div>
                </div>
                
                <hr />
                
                <div class="well well-small">
                
                          <?php echo TEXT_PRODUCTS_IMAGE; ?>
            
              <div><?php echo '<strong>' . TEXT_PRODUCTS_MAIN_IMAGE . ' <small>(' . SMALL_IMAGE_WIDTH . ' x ' . SMALL_IMAGE_HEIGHT . 'px)</small></strong><br />' . (tep_not_null($pInfo->products_image) ? '<a href="' . HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $pInfo->products_image . '" target="_blank">' . $pInfo->products_image . '</a> &#124; ' : '') . tep_draw_file_field('products_image'); ?></div>

              <ul id="piList">
<?php
    $pi_counter = 0;

    foreach ($pInfo->products_larger_images as $pi) {
      $pi_counter++;

      echo '                <li id="piId' . $pi_counter . '"><hr><span class="ui-icon ui-icon-arrowthick-2-n-s" style="float: right;"></span><a href="#" onclick="showPiDelConfirm(' . $pi_counter . ');return false;" class="ui-icon ui-icon-trash" style="float: right;"></a><strong>' . TEXT_PRODUCTS_LARGE_IMAGE . '</strong><br />' . tep_draw_file_field('products_image_large_' . $pi['id']) . '<br /><a href="' . HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $pi['image'] . '" target="_blank">' . $pi['image'] . '</a><br /><label>' . TEXT_PRODUCTS_LARGE_IMAGE_HTML_CONTENT . '</label>' . tep_draw_textarea_field('products_image_htmlcontent_' . $pi['id'], 'soft', '70', '3', $pi['htmlcontent']) . '</li>';
    }
?>
              </ul>

              <a href="#" onclick="addNewPiForm();return false;"><span class="ui-icon ui-icon-plus" style="float: left;"></span><?php echo TEXT_PRODUCTS_ADD_LARGE_IMAGE; ?></a>

<div id="piDelConfirm" title="<?php echo TEXT_PRODUCTS_LARGE_IMAGE_DELETE_TITLE; ?>">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php echo TEXT_PRODUCTS_LARGE_IMAGE_CONFIRM_DELETE; ?></p>
</div>

<style type="text/css">
#piList { list-style-type: none; margin: 0; padding: 0; }
#piList li { margin: 5px 0; padding: 2px; }
</style>

<script type="text/javascript">
$('#piList').sortable({
  containment: 'parent'
});

var piSize = <?php echo $pi_counter; ?>;

function addNewPiForm() {
  piSize++;

  $('#piList').append('<li id="piId' + piSize + '"><hr><span class="ui-icon ui-icon-arrowthick-2-n-s" style="float: right;"></span><a href="#" onclick="showPiDelConfirm(' + piSize + ');return false;" class="ui-icon ui-icon-trash" style="float: right;"></a><strong><?php echo TEXT_PRODUCTS_LARGE_IMAGE; ?></strong><br /><input type="file" name="products_image_large_new_' + piSize + '" /><br /><label><?php echo TEXT_PRODUCTS_LARGE_IMAGE_HTML_CONTENT; ?></label><textarea class="form-control" name="products_image_htmlcontent_new_' + piSize + '" wrap="soft" cols="70" rows="3"></textarea></li>');
}

var piDelConfirmId = 0;

$('#piDelConfirm').dialog({
  autoOpen: false,
  resizable: false,
  draggable: false,
  modal: true,
  buttons: {
    'Delete': function() {
      $('#piId' + piDelConfirmId).effect('blind').remove();
      $(this).dialog('close');
    },
    Cancel: function() {
      $(this).dialog('close');
    }
  }
});

function showPiDelConfirm(piId) {
  piDelConfirmId = piId;

  $('#piDelConfirm').dialog('open');
}
</script>
                
                </div>
                    
              </div><!-- col-9-->
              
              <div class="col-md-3">
              
                <ul class="list-group">
                  <li class="list-group-item">
				    <strong><?php echo TEXT_PRODUCTS_STATUS; ?></strong>
                    <div class="radio">
                      <label>
					  <?php echo tep_draw_radio_field('products_status', '1', $in_status) .  '&nbsp;' . TEXT_PRODUCT_AVAILABLE; ?>
                      </label>
                    </div>
                    <div class="radio">
                      <label>
					  <?php echo tep_draw_radio_field('products_status', '0', $out_status) . '&nbsp;' . TEXT_PRODUCT_NOT_AVAILABLE; ?>
                      </label>
                    </div>
                  </li>
                  <li class="list-group-item">
				    <label><?php echo TEXT_PRODUCTS_DATE_AVAILABLE; ?></label>
                    <?php echo tep_draw_input_field('products_date_available', $pInfo->products_date_available, 'id="products_date_available"') . ' <small>(YYYY-MM-DD)</small>'; ?>
                  </li>
                  <li class="list-group-item">
				    <label><?php echo TEXT_PRODUCTS_MANUFACTURER; ?></label>
                    <?php echo tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $pInfo->manufacturers_id); ?>
                    </li>
                </ul>
                
                <ul class="list-group">
                  <li class="list-group-item list-group-item-info">
				    <label><?php echo TEXT_PRODUCTS_TAX_CLASS; ?></label>
                    <?php echo tep_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id, 'onchange="updateGross()"'); ?>
                    
                  </li>
            
                  <li class="list-group-item list-group-item-info">
				    <label><?php echo TEXT_PRODUCTS_PRICE_NET; ?></label>
                    <?php echo tep_draw_input_field('products_price', $pInfo->products_price, 'onkeyup="updateGross()"'); ?>
                  </li>
          
                  <li class="list-group-item list-group-item-info">
				    <label><?php echo TEXT_PRODUCTS_PRICE_GROSS; ?></label>
                    <?php echo tep_draw_input_field('products_price_gross', $pInfo->products_price, 'onkeyup="updateNet()"'); ?>
                    </li>
                </ul>
<script type="text/javascript">
<!--
  updateGross();
  //-->
</script>
 
                <ul class="list-group">
                  <li class="list-group-item">
                   <label><?php echo TEXT_PRODUCTS_QUANTITY; ?></label>
                   <?php echo tep_draw_input_field('products_quantity', $pInfo->products_quantity); ?>
                  </li>
                  <li class="list-group-item">
                    <label><?php echo TEXT_PRODUCTS_MODEL; ?></label>
                    <?php echo tep_draw_input_field('products_model', $pInfo->products_model); ?>
                  </li>
                  <li class="list-group-item">
                    <label><?php echo TEXT_PRODUCTS_WEIGHT; ?></label>
                    <?php echo tep_draw_input_field('products_weight', $pInfo->products_weight); ?>
                  </li>
                </ul>

                
              </div>
            </div><!--row-->

            <div class="row">
              <div class="col-md-12">
         
		 <?php echo tep_draw_hidden_field('products_date_added', (tep_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d'))) . tep_draw_bs_button(IMAGE_SAVE, 'ok', null) . '&nbsp;&nbsp;' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '')),null,null,'btn-default text-danger'); ?>

              </div>
            </div><!--row-->
         
<script type="text/javascript">
$('#products_date_available').datepicker({
  dateFormat: 'yy-mm-dd'
});
</script>

    </form>
<?php
  } elseif ($action == 'new_product_preview') {
    $product_query = tep_db_query("select p.products_id, pd.language_id, pd.products_name, pd.products_description, pd.products_url, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.manufacturers_id  from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "'");
    $product = tep_db_fetch_array($product_query);

    $pInfo = new objectInfo($product);
    $products_image_name = $pInfo->products_image;

    $languages = tep_get_languages();
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
      $pInfo->products_name = tep_get_products_name($pInfo->products_id, $languages[$i]['id']);
      $pInfo->products_description = tep_get_products_description($pInfo->products_id, $languages[$i]['id']);
      $pInfo->products_url = tep_get_products_url($pInfo->products_id, $languages[$i]['id']);
	  
      $navtabs .= '                      <li '. (($i == 0) ? 'class="active"' : '') .'>' . "\n";
	  $navtabs .= '                        <a href="#tab' . $i . '" data-toggle="tab">' . tep_image(tep_catalog_href_link(DIR_WS_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], '', 'SSL'), $languages[$i]['name'], null, null, null, false) . '</a>' . "\n";
	  $navtabs .= '                      </li>' . "\n";
	  
	  
	  // now the content for each language
	  $tabcontent .= '                      <div class="tab-pane fade'. (($i == 0) ? ' active in' : '') .'" id="tab' . $i . '">' . "\n"; 
	  $tabcontent .= '                        <h1>' . $pInfo->products_name . '<small class="pull-right">' . $currencies->format($pInfo->products_price) .'</small></h1>' . "\n";
	  $tabcontent .= '                        <figure class="pull-right">' . tep_image(HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $products_image_name, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</figure>' . "\n";
	  
	  
      $tabcontent .= '                        ' . $pInfo->products_description  . "\n";
	  
	  if ($pInfo->products_url)  {
	    $tabcontent .= '                        <br><br>' . sprintf(TEXT_PRODUCT_MORE_INFORMATION, $pInfo->products_url) . "\n";
	  }
	  
	  if ($pInfo->products_date_available > date('Y-m-d')){
	    $tabcontent .= '                        <br><br><span class="label label-info">' . sprintf(TEXT_PRODUCT_DATE_AVAILABLE, tep_date_long($pInfo->products_date_available)) . '</span>' . "\n";
	  } else {
        $tabcontent .= '                        <br><br><span class="label label-info">' . sprintf(TEXT_PRODUCT_DATE_ADDED, tep_date_long($pInfo->products_date_added)) . '</span>' . "\n";
	  }
	  
	  $tabcontent .= '                      </div>' . "\n"; 
	  
	}
	  
	  
?>
            <div class="row">
            
              <div class="col-md-12">
                <div class="panel tabbed-heading panel-default">
                  <div class="panel-heading">
                    <ul class="nav nav-tabs" role="tablist">
<?php                    
	echo $navtabs;
?>                              
                    </ul>
                  </div>
                  <div class="panel-body">
                    <div class="tab-content">
<?php
    echo $tabcontent;
?>                     
                    </div>
                  </div>
                </div>  
              </div>
              
<?php

    if (isset($HTTP_GET_VARS['origin'])) {
      $pos_params = strpos($HTTP_GET_VARS['origin'], '?', 0);
      if ($pos_params != false) {
        $back_url = substr($HTTP_GET_VARS['origin'], 0, $pos_params);
        $back_url_params = substr($HTTP_GET_VARS['origin'], $pos_params + 1);
      } else {
        $back_url = $HTTP_GET_VARS['origin'];
        $back_url_params = '';
      }
    } else {
      $back_url = FILENAME_CATEGORIES;
      $back_url_params = 'cPath=' . $cPath . '&pID=' . $pInfo->products_id;
    }
?>
              <div class="col-md-12">             
			    <?php echo tep_draw_bs_button(IMAGE_BACK, 'chevron-left', tep_href_link($back_url, $back_url_params)); ?>
      
              </div>
              
            </div><!--row -->
<?php
  } else {
?>

          <div class="page-header">
            <h1 class="col-sm-12 col-md-6"><?php echo HEADING_TITLE; ?></h1>
            <div class="col-md-6">
              <div class="row">              
<?php
  echo '                ' . tep_draw_form('search', FILENAME_CATEGORIES, '', 'get', 'class="col-sm-6 col-md-6"') . "\n" .
       '                  <label class="sr-only" for="search">' . HEADING_TITLE_SEARCH . '</label>' . "\n" .  
       '                  ' . tep_draw_input_field('search','', 'placeholder="' . HEADING_TITLE_SEARCH . '"') . tep_hide_session_id() . "\n" .
	   '                </form>' . "\n";
	   
  echo '                ' . tep_draw_form('goto', FILENAME_CATEGORIES, '', 'get', 'class="col-sm-6 col-md-6"') . "\n" .
       '                  <label class="sr-only" for="cPath">' . HEADING_TITLE_GOTO . '</label>' . "\n" .  
       '                  ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onchange="this.form.submit();"') . tep_hide_session_id() . "\n" .
	   '                </form>' . "\n";
?>
              </div>
            </div>
            <div class="clearfix"></div>
          </div><!-- page-header-->
          
          <div class="panel panel-default">        
  
            <table class="table table-hover table-condensed table-striped">
              <thead>
                <tr class="heading-row">
                  <th><?php echo TABLE_HEADING_CATEGORIES_PRODUCTS; ?></th>
                  <th class="text-center"><?php echo TABLE_HEADING_STATUS; ?></th>
                  <th class="text-right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</th>
                </tr>
              </thead>
              <tbody>
<?php
    $categories_count = 0;
    $rows = 0;
    if (isset($HTTP_GET_VARS['search'])) {
      $search = tep_db_prepare_input($HTTP_GET_VARS['search']);

      $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and cd.categories_name like '%" . tep_db_input($search) . "%' order by c.sort_order, cd.categories_name");
    } else {
      $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by c.sort_order, cd.categories_name");
    }
    while ($categories = tep_db_fetch_array($categories_query)) {
      $categories_count++;
      $rows++;

// Get parent_id for subcategories if search
      if (isset($HTTP_GET_VARS['search'])) $cPath= $categories['parent_id'];

      if ((!isset($HTTP_GET_VARS['cID']) && !isset($HTTP_GET_VARS['pID']) || (isset($HTTP_GET_VARS['cID']) && ($HTTP_GET_VARS['cID'] == $categories['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
        $category_childs = array('childs_count' => tep_childs_in_category_count($categories['categories_id']));
        $category_products = array('products_count' => tep_products_in_category_count($categories['categories_id']));

        $cInfo_array = array_merge($categories, $category_childs, $category_products);
        $cInfo = new objectInfo($cInfo_array);
      }
	 // move cat path so its available at all times 
	  $category_path_string = '';
      $category_path = tep_generate_category_path($categories['categories_id']);
      for ($i=(sizeof($category_path[0])-1); $i>0; $i--) {
        $category_path_string .= $category_path[0][$i]['id'] . '_';
      }
      $category_path_string = substr($category_path_string, 0, -1);
	  

      if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) {
        echo '                <tr class="active" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, tep_get_path($categories['categories_id'])) . '\'">' . "\n";	
      } else {
        echo '                <tr onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '\'">' . "\n";
      }
?>
                  <td><?php echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, tep_get_path($categories['categories_id'])) . '">' . tep_glyphicon('folder-open', 'warning') . '</a>&nbsp;' . $categories['categories_name']; ?></td>
                  <td class="text-center">&nbsp;</td>
                  <td class="text-right">
                    <div class="btn-toolbar" role="toolbar">                  
<?php
      echo '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_ICON_INFO, 'info-sign', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $categories['categories_id'] . '&action=info'), null, 'info') . '</div>' . "\n" .
           '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_EDIT, 'pencil', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $category_path_string . '&cID=' . $categories['categories_id'] . '&action=edit_category'), null, 'warning') . '</div>' . "\n" .
           '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_MOVE, 'move', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $category_path_string . '&cID=' . $categories['categories_id'] . '&action=move_category'), null, 'muted') . '</div>' . "\n" .
           '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_DELETE, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $category_path_string . '&cID=' . $categories['categories_id'] . '&action=delete_category'), null, 'danger') . '</div>' . "\n"; ?>
                    </div> 
				  </td>
                </tr>
                
<?php 
      if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) && isset($HTTP_GET_VARS['action'])) { 
	    $alertClass = '';
        switch ($action) {
		  case 'edit_category':
			$contents .= '                      ' . tep_draw_form('categories', FILENAME_CATEGORIES, 'action=update_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"') . tep_draw_hidden_field('categories_id', $cInfo->categories_id) . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-5 col-md-5">' . "\n";
			$contents .= '                          <h4>' . TEXT_INFO_HEADING_EDIT_CATEGORY . '</h4>' . "\n";
			$contents .= '                          <p>' . TEXT_EDIT_INTRO . '</p>' . "\n";
            $category_inputs_string = '';
            $languages = tep_get_languages();
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
              $category_inputs_string .= '                                <div class="form-group">' . "\n" .
                                         '                                  <div class="input-group">' . "\n" .
										 '                                    <div class="input-group-addon">' . "\n" . 
										 '                                      ' . tep_image(tep_catalog_href_link(DIR_WS_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], '', 'SSL'), $languages[$i]['name'],24,15) . "\n" .
										 
										 '                                    </div>' . "\n" .
										 '                                    ' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']', tep_get_category_name($cInfo->categories_id, $languages[$i]['id'])) . "\n" .
										 '                                  </div>' . "\n" .
										 '                                </div>' . "\n";
            }
			$contents .= '                          <div class="row">' . "\n";
			$contents .= '                            <div class="col-md-12">' . "\n";
			$contents .= '                              <label>' . TEXT_CATEGORIES_NAME . '</label>' . "\n";
			$contents .= $category_inputs_string;
			$contents .= '                            </div>' . "\n";
			$contents .= '                          </div>' . "\n";
			$contents .= '                          <div class="row">' . "\n";
			$contents .= '                            <div class="col-md-6">' . "\n";
            $contents .= '                              <label>' . TEXT_EDIT_SORT_ORDER . '</label>' . "\n";
            $contents .= '                              ' . tep_draw_input_field('sort_order', $cInfo->sort_order) . "\n";
			$contents .= '                            </div>' . "\n";
			$contents .= '                          </div>' . "\n";
            $contents .= '                        </div>' . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-4 col-md-4">' . "\n";
            $contents .= '                          <figure>' . tep_image(HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT) . '<figcaption><small>' . DIR_WS_CATALOG_IMAGES . '<strong>' . $cInfo->categories_image . '</strong></small></figcaption></figure>' . "\n";
            $contents .= '                          <br>' . "\n";
            $contents .= '                          <label>' . TEXT_EDIT_CATEGORIES_IMAGE . '</label>' . "\n";
            $contents .= '                          <p>'. tep_draw_file_field('categories_image') . '</p>' . "\n";
            $contents .= '                        </div>' . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-3 col-md-3 text-right">' . "\n";
            $contents .= '                          ' . tep_draw_bs_button(IMAGE_SAVE, 'ok', null) . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id), null, null, 'btn-default text-danger') . "\n";
            $contents .= '                        </div>' . "\n";
		    $contents .= '                      </form>' . "\n";
		  break;
        
		  case 'delete_category':
		    $alertClass .= ' alert-message alert-message-danger';
		    $contents .= '                      ' . tep_draw_form('categories', FILENAME_CATEGORIES, 'action=delete_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id) . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-8 col-md-8">' . "\n";
			$contents .= '                          <h4>' . TEXT_INFO_HEADING_DELETE_CATEGORY . ': ' . $cInfo->categories_name. '</h4>' . "\n";
            $contents .= '                          <p>' . TEXT_DELETE_CATEGORY_INTRO . '</p>' . "\n";
            if ($cInfo->childs_count > 0) $contents .= '                          ' . sprintf(TEXT_DELETE_WARNING_CHILDS, $cInfo->childs_count) . "\n";
            if ($cInfo->products_count > 0) $contents .= '                          <br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $cInfo->products_count) . "\n";
            $contents .= '                        </div>' . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-4 col-md-4 text-right">' . "\n";
            $contents .= '                          ' . tep_draw_bs_button(IMAGE_DELETE, 'ban-circle', null, null, null, 'btn-danger') . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id), null, null, 'btn-default text-danger') . "\n";
            $contents .= '                        </div>' . "\n";
		    $contents .= '                      </form>' . "\n";
          break;
		  
          case 'move_category':
		    $contents .= '                      ' . tep_draw_form('categories', FILENAME_CATEGORIES, 'action=move_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id) . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-8 col-md-8">' . "\n";
			$contents .= '                          <h4>' . TEXT_INFO_HEADING_MOVE_CATEGORY . '</h4>' . "\n";
            $contents .= '                          <p>' . sprintf(TEXT_MOVE_CATEGORIES_INTRO, $cInfo->categories_name) . '</p>' . "\n";
            $contents .= '                          <p>' . sprintf(TEXT_MOVE, $cInfo->categories_name) . '<br />' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id) . '</p>' . "\n";
			$contents .= '                        </div>' . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-4 col-md-4 text-right">' . "\n";
            $contents .= '                          ' . tep_draw_bs_button(IMAGE_MOVE, 'ok', null) . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id), null, null, 'btn-default text-danger') . "\n";
            $contents .= '                        </div>' . "\n";
		    $contents .= '                      </form>' . "\n";
          break;
		
		  default:
            $contents .= '                      <div class="col-xs-12 col-sm-2 col-md-4">' . "\n";
			$contents .= '                        <strong>' . $cInfo->categories_name . '</strong>' . "\n";
            $contents .= '                        <figure>' . tep_info_image($cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT) . '<figcaption>' . $cInfo->categories_image.'</figcaption></figure>' . "\n";
            $contents .= '                      </div>' . "\n";
			$contents .= '                      <div class="col-xs-12 col-sm-5 col-md-4">' . "\n";
			$contents .= '                        <ul class="list-group">' . "\n";
			$contents .= '                          <li class="list-group-item">' . "\n";
			$contents .= '                            <span class="badge badge-info">' . $cInfo->childs_count . '</span>' . "\n";			
			$contents .= '                              ' . TEXT_SUBCATEGORIES . "\n";
			$contents .= '                          </li>' . "\n";
			$contents .= '                          <li class="list-group-item">' . "\n";
			$contents .= '                            <span class="badge badge-info">' . $cInfo->products_count . '</span>' . "\n";			
			$contents .= '                              ' . TEXT_PRODUCTS . "\n";
			$contents .= '                          </li>' . "\n";
			$contents .= '                        </ul>' . "\n";
			$contents .= '                      </div>' . "\n";
			$contents .= '                      <div class="col-xs-12 col-sm-5 col-md-4">' . "\n";
			$contents .= '                        <ul class="list-group">' . "\n";
			$contents .= '                          <li class="list-group-item">' . "\n";
			$contents .= '                            <span class="badge badge-info">' . tep_date_short($cInfo->date_added) . '</span>' . "\n";			
			$contents .= '                              ' . TEXT_DATE_ADDED . "\n";
			$contents .= '                          </li>' . "\n";
            if (tep_not_null($cInfo->last_modified)) {
		      $contents .= '                          <li class="list-group-item">' . "\n";
			  $contents .= '                            <span class="badge badge-info">' . tep_date_short($cInfo->last_modified) . '</span>' . "\n";			
			  $contents .= '                              ' . TEXT_LAST_MODIFIED . "\n";
			  $contents .= '                          </li>' . "\n";					
			}
			$contents .= '                        </ul>' . "\n";
			$contents .= '                      </div>' . "\n";
          break;
        }
        echo '                <tr class="content-row">' . "\n" .
             '                  <td colspan="3">' . "\n" .
             '                    <div class="row' . $alertClass . '">' . "\n" .
                                    $contents . 
             '                    </div>' . "\n" .
             '                  </td>' . "\n" .
             '                </tr>' . "\n";
      }
    }


	 /////////
    $products_count = 0;
    if (isset($HTTP_GET_VARS['search'])) {
      $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p2c.categories_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and pd.products_name like '%" . tep_db_input($search) . "%' order by pd.products_name");
    } else {
      $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$current_category_id . "' order by pd.products_name");
    }
    while ($products = tep_db_fetch_array($products_query)) {
      $products_count++;
      $rows++;
      
	  // Get categories_id for product if search
      if (isset($HTTP_GET_VARS['search'])) $cPath = $products['categories_id'];
      if ( (!isset($HTTP_GET_VARS['pID']) && !isset($HTTP_GET_VARS['cID']) || (isset($HTTP_GET_VARS['pID']) && ($HTTP_GET_VARS['pID'] == $products['products_id']))) && !isset($pInfo) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
// find out the rating average from customer reviews
        $reviews_query = tep_db_query("select (avg(reviews_rating) / 5 * 100) as average_rating from " . TABLE_REVIEWS . " where products_id = '" . (int)$products['products_id'] . "'");
        $reviews = tep_db_fetch_array($reviews_query);
        $pInfo_array = array_merge($products, $reviews);
        $pInfo = new objectInfo($pInfo_array);
      }

      if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id) ) {
        echo '                <tr class="active" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product_preview') . '\'">' . "\n";
      } else {
        echo '                <tr onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products['products_id']) . '\'">' . "\n";
      }
?>
                  <td><?php echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product_preview') . '">' . tep_glyphicon('screenshot', 'info') . '</a>&nbsp;' . $products['products_name']; ?></td>
                  <td class="text-center">
<?php
      if ($products['products_status'] == '1') {
        echo '                    ' . tep_glyphicon('ok-sign glyphicon-lg', 'success') . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setflag&flag=0&pID=' . $products['products_id'] . '&cPath=' . $cPath) . '">' . tep_glyphicon('remove-sign glyphicon-lg', 'muted') . '</a>' . "\n";
      } else {
        echo '                    <a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setflag&flag=1&pID=' . $products['products_id'] . '&cPath=' . $cPath) . '">' . tep_glyphicon('ok-sign glyphicon-lg', 'muted') . '</a>&nbsp;&nbsp;' . tep_glyphicon('remove-sign glyphicon-lg', 'danger') . "\n";
      }
?>
                  </td>
                  <td class="text-right">
                    <div class="btn-toolbar" role="toolbar">
                      
<?php
      echo '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_ICON_INFO, 'info-sign', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=info'), null, 'info') . '</div>' . "\n" . 
           '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_EDIT, 'pencil', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product'), null, 'warning') . '</div>' . "\n" . 
           '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_COPY_TO, 'transfer', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=copy_to'), null, 'muted'). '</div>' . "\n" . 
           '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_MOVE, 'move', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=move_product'), null, 'muted') . '</div>' . "\n" . 
           '                      <div class="btn-group">' . tep_glyphicon_button(IMAGE_DELETE, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=delete_product'), null, 'danger') . '</div>' . "\n"; ?>

                    </div>
				  </td>
                </tr>
<?php 
      if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id) && isset($HTTP_GET_VARS['action'])) { 
	    $alertClass = '';
        switch ($action) {
			        
		  case 'delete_product':
		    $alertClass .= ' alert-message alert-message-danger';
		    $contents .= '                      ' . tep_draw_form('products', FILENAME_CATEGORIES, 'action=delete_product_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id) . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-8 col-md-8">' . "\n";
			$contents .= '                          <h4>' . TEXT_INFO_HEADING_DELETE_PRODUCT . ': ' . $pInfo->products_name . '</h4>' . "\n";
            $contents .= '                          <p>' . TEXT_DELETE_PRODUCT_INTRO . '</p>' . "\n";
			
			
            $product_categories_string = '';
            $product_categories = tep_generate_category_path($pInfo->products_id, 'product');
            for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
              $category_path = '';
              for ($j = 0, $k = sizeof($product_categories[$i]); $j < $k; $j++) {
                $category_path .= $product_categories[$i][$j]['text'] . '&nbsp;&gt;&nbsp;';
              }
              $category_path = substr($category_path, 0, -16);
              $product_categories_string .= tep_draw_checkbox_field('product_categories[]', $product_categories[$i][sizeof($product_categories[$i])-1]['id'], true) . '&nbsp;' . $category_path . '<br>';
            }
           // $product_categories_string = substr($product_categories_string, 0, -4);
			
            $contents .= '                          ' . $product_categories_string .  "\n";

			
            $contents .= '                        </div>' . "\n";
			
            $contents .= '                        <div class="col-xs-12 col-sm-4 col-md-4 text-right">' . "\n";
            $contents .= '                          ' . tep_draw_bs_button(IMAGE_DELETE, 'ban-circle', null, null, null, 'btn-danger') . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id), null, null, 'btn-default text-danger') . "\n";
            $contents .= '                        </div>' . "\n";
			
		    $contents .= '                      </form>' . "\n";
          break;
		  
          case 'move_product':
		    $contents .= '                      ' . tep_draw_form('products', FILENAME_CATEGORIES, 'action=move_product_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id) . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-8 col-md-8">' . "\n";
			$contents .= '                          <h4>' . TEXT_INFO_HEADING_MOVE_PRODUCT . '</h4>' . "\n";
            $contents .= '                          <p>' . sprintf(TEXT_MOVE_PRODUCTS_INTRO, $pInfo->products_name) . '</p>' . "\n";
			$contents .= '                          <p>' . TEXT_INFO_CURRENT_CATEGORIES . '<br /><strong>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</strong>' . "\n";
			
            $contents .= '                          <p>' . sprintf(TEXT_MOVE, $pInfo->products_name) . '<br />' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id) . '</p>' . "\n";
			$contents .= '                        </div>' . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-4 col-md-4 text-right">' . "\n";
            $contents .= '                          ' . tep_draw_bs_button(IMAGE_MOVE, 'ok', null) . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id), null, null, 'btn-default text-danger') . "\n";
            $contents .= '                        </div>' . "\n";
		    $contents .= '                      </form>' . "\n";
          break;
		  
          case 'copy_to':
		    $contents .= '                      ' . tep_draw_form('copy_to', FILENAME_CATEGORIES, 'action=copy_to_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id) . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-8 col-md-8">' . "\n";
			$contents .= '                          <h4>' . TEXT_INFO_COPY_TO_INTRO . '</h4>' . "\n";
            $contents .= '                          <p>' . sprintf(TEXT_MOVE_PRODUCTS_INTRO, $pInfo->products_name) . '</p>' . "\n";
			$contents .= '                          <p>' . TEXT_INFO_CURRENT_CATEGORIES . '<br /><strong>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</strong></p>' . "\n";
			
            $contents .= '                          <p>' . TEXT_CATEGORIES . '<br />' . tep_draw_pull_down_menu('categories_id', tep_get_category_tree(), $current_category_id) . '</p>' . "\n";
			
			
			$contents .= '                        </div>' . "\n";
            $contents .= '                        <div class="col-xs-12 col-sm-4 col-md-4">' . "\n";
            $contents .= '                          <strong>' . TEXT_HOW_TO_COPY . '</strong>' . "\n";
            $contents .= '                          <div class="radio">' . "\n";
            $contents .= '                            <label>' . tep_draw_radio_field('copy_as', 'link', true) . ' ' . TEXT_COPY_AS_LINK . '</label>' . "\n";
            $contents .= '                          </div>' . "\n";
            $contents .= '                          <div class="radio">' . "\n";
            $contents .= '                            <label>'. tep_draw_radio_field('copy_as', 'duplicate') . ' ' . TEXT_COPY_AS_DUPLICATE . '</label>' . "\n";
            $contents .= '                          </div>' . "\n";
			
            $contents .= '                          <div class="text-right">' . "\n";
            $contents .= '                            ' . tep_draw_bs_button(IMAGE_COPY, 'ok', null) . '<br>' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id), null, null, 'btn-default text-danger') . "\n";
            $contents .= '                          </div>' . "\n";
			
            $contents .= '                        </div>' . "\n";
		    $contents .= '                      </form>' . "\n";
          break;
		
		  default:
            $contents .= '                      <div class="col-xs-12 col-sm-2 col-md-4">' . "\n";
			$contents .= '                        <strong>' . tep_get_products_name($pInfo->products_id, $languages_id) . '</strong>' . "\n";
            $contents .= '                        <figure>' . tep_info_image($pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<figcaption>' . $pInfo->products_image.'</figcaption></figure>' . "\n";
            $contents .= '                      </div>' . "\n";
			$contents .= '                      <div class="col-xs-12 col-sm-5 col-md-4">' . "\n";
			$contents .= '                        <ul class="list-group">' . "\n";
			$contents .= '                          <li class="list-group-item">' . "\n";
			$contents .= '                            <span class="badge badge-info">' . $currencies->format($pInfo->products_price) . '</span>' . "\n";			
			$contents .= '                              ' . TEXT_PRODUCTS_PRICE_INFO . "\n";
			$contents .= '                          </li>' . "\n";
			$contents .= '                          <li class="list-group-item">' . "\n";
			$contents .= '                            <span class="badge badge-info">' . $pInfo->products_quantity . '</span>' . "\n";			
			$contents .= '                              ' . TEXT_PRODUCTS_QUANTITY_INFO . "\n";
			$contents .= '                          </li>' . "\n";
			$contents .= '                          <li class="list-group-item">' . "\n";
			$contents .= '                            <span class="badge badge-info">' . number_format($pInfo->average_rating, 2) . '%' . '</span>' . "\n";			
			$contents .= '                              ' . TEXT_PRODUCTS_AVERAGE_RATING . "\n";
			$contents .= '                          </li>' . "\n";
			$contents .= '                        </ul>' . "\n";
			$contents .= '                      </div>' . "\n";
			$contents .= '                      <div class="col-xs-12 col-sm-5 col-md-4">' . "\n";
			$contents .= '                        <ul class="list-group">' . "\n";
			$contents .= '                          <li class="list-group-item">' . "\n";
			$contents .= '                            <span class="badge badge-info">' . tep_date_short($pInfo->products_date_added) . '</span>' . "\n";			
			$contents .= '                              ' . TEXT_DATE_ADDED . "\n";
			$contents .= '                          </li>' . "\n";
            if (tep_not_null($pInfo->products_last_modified)) {
		      $contents .= '                          <li class="list-group-item">' . "\n";
			  $contents .= '                            <span class="badge badge-info">' . tep_date_short($pInfo->products_last_modified) . '</span>' . "\n";			
			  $contents .= '                              ' . TEXT_LAST_MODIFIED . "\n";
			  $contents .= '                          </li>' . "\n";					
			}
            if (date('Y-m-d') < $pInfo->products_date_available) {
		      $contents .= '                          <li class="list-group-item">' . "\n";
			  $contents .= '                            <span class="badge badge-info">' . tep_date_short($pInfo->products_date_available) . '</span>' . "\n";			
			  $contents .= '                              ' . TEXT_DATE_AVAILABLE . "\n";
			  $contents .= '                          </li>' . "\n";					
			}
			$contents .= '                        </ul>' . "\n";
			$contents .= '                      </div>' . "\n";
          break;
        }
        echo '                <tr class="content-row">' . "\n" .
             '                  <td colspan="3">' . "\n" .
             '                    <div class="row' . $alertClass . '">' . "\n" .
                                    $contents . 
             '                    </div>' . "\n" .
             '                  </td>' . "\n" .
             '                </tr>' . "\n";
      }                
    }
?>
<?php
?> 
           
              </tbody>
            </table>
            
          </div>
      <?php
    $cPath_back = '';
    if (sizeof($cPath_array) > 0) {
      for ($i=0, $n=sizeof($cPath_array)-1; $i<$n; $i++) {
        if (empty($cPath_back)) {
          $cPath_back .= $cPath_array[$i];
        } else {
          $cPath_back .= '_' . $cPath_array[$i];
        }
      }
    }

    $cPath_back = (tep_not_null($cPath_back)) ? 'cPath=' . $cPath_back . '&' : '';
?>
          <div class="row">
            <!--<div class="col-xs-8 col-sm-4 col-md-3">
              <span class="col-md-8">
			     <?php echo TEXT_CATEGORIES . '<span class="badge badge-info pull-right">' . $categories_count . '</span>'; ?>
              </span>
              <br />
              <span class="col-md-8">
			    <?php echo TEXT_PRODUCTS . '<span class="badge badge-info pull-right">' . $products_count . '</span>'; ?>
              </span>
            </div> !-->
            
            <div class="col-xs-12 col-md-7">
              <?php if (sizeof($cPath_array) > 0) echo tep_draw_bs_button(IMAGE_BACK, 'chevron-left', tep_href_link(FILENAME_CATEGORIES, $cPath_back . 'cID=' . $current_category_id)) . '&nbsp;'; 
		      if (!isset($HTTP_GET_VARS['search'])) echo tep_draw_bs_button(IMAGE_NEW_CATEGORY, 'plus', null,'data-toggle="modal" data-target="#new_category"') . '&nbsp;' . tep_draw_bs_button(IMAGE_NEW_PRODUCT, 'plus', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&action=new_product')); ?>
            </div>
            
            <div class="col-xs-12 col-md-5 text-right">
            <small><?php echo TEXT_CATEGORIES . '&nbsp;<span class="badge badge-info">' . $categories_count . '</span>&nbsp;' . TEXT_PRODUCTS . '&nbsp;<span class="badge badge-info">' . $products_count; ?></small></small>
          </div>
            
          </div><!--row-->
            
          <div class="modal fade" id="new_category" tabindex="-1" role="dialog" aria-labelledby="new_category" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <?php echo tep_draw_form('newcategory', FILENAME_CATEGORIES, 'action=insert_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"'); ?>
                
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel"><?php echo TEXT_INFO_HEADING_NEW_CATEGORY; ?></h4>
                  </div>
                  <div class="modal-body">
                    <p><?php echo TEXT_NEW_CATEGORY_INTRO; ?></p>
<?php
            $category_inputs_string = '';
            $languages = tep_get_languages();
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
              $category_inputs_string .= '                    <div class="form-group">' . "\n" .
                                         '                      <div class="input-group">' . "\n" .
										 '                        <div class="input-group-addon">' . tep_image(tep_catalog_href_link(DIR_WS_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], '', 'SSL'), $languages[$i]['name'],24,15) . '</div>' . "\n" .
										 '                          ' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']') . "\n" .
										 '                        </div>' . "\n" .
										 '                      </div>' . "\n";
            }
?>
                    
                     <div class="row">
                       <div class="col-md-12"> 
                         <strong><?php echo TEXT_CATEGORIES_NAME; ?></strong>
					     <?php echo $category_inputs_string; ?>
                       </div>
                     </div>
                    
                   <div class="row">
                     <div class="col-md-4">
                       <?php echo '<label>' . TEXT_SORT_ORDER . '</label>' . tep_draw_input_field('sort_order', '', 'size="2"') . "\n"; ?>
                     </div>
                     
                     <div class="col-md-4"></div>
                   
                     <div class="col-md-4">
                       <?php echo '<label>' . TEXT_CATEGORIES_IMAGE . '</label>' . tep_draw_file_field('categories_image') . "\n"; ?>   
                     </div>
                   </div>
                   
                  </div>
                  <div class="modal-footer">
                  <?php echo tep_draw_bs_button(IMAGE_SAVE, 'ok', null) . '&nbsp;' . tep_draw_bs_button(IMAGE_CANCEL, 'remove', tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath)); ?>
                  </div>              
                </form>
              </div>
            </div>
          </div><!-- modal #new_category -->
<?php
  }

  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>