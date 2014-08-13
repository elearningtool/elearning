<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/
  if (tep_session_is_registered('admin')) {
    $cl_box_groups = array();

    if ($dir = @dir(DIR_FS_ADMIN . 'includes/boxes')) {
      $files = array();

      while ($file = $dir->read()) {
        if (!is_dir($dir->path . '/' . $file)) {
          if (substr($file, strrpos($file, '.')) == '.php') {
            $files[] = $file;
          }
        }
      }

      $dir->close();

      natcasesort($files);

      foreach ( $files as $file ) {
        if ( file_exists(DIR_FS_ADMIN . 'includes/languages/' . $language . '/modules/boxes/' . $file) ) {
          include(DIR_FS_ADMIN . 'includes/languages/' . $language . '/modules/boxes/' . $file);
        }

        include($dir->path . '/' . $file);
      }
    }

    function tep_sort_admin_boxes($a, $b) {
      return strcasecmp($a['heading'], $b['heading']);
    }

    usort($cl_box_groups, 'tep_sort_admin_boxes');

    function tep_sort_admin_boxes_links($a, $b) {
      return strcasecmp($a['title'], $b['title']);
    }

    foreach ( $cl_box_groups as &$group ) {
      usort($group['apps'], 'tep_sort_admin_boxes_links');
    }
/* lets build a bootstrap side nav. With help of a counter we can manipulate classes and ids needed for various functions, even dynamically add icons. Define the icon desired in the main language file. Placing constants this way avoids extra markup to the boxes files. 
*/ 
?>
        <div class="col-xs-6 col-sm-3 col-md-2 sidebar-offcanvas equal" id="sidebar" role="navigation">
          <ul class="nav">
<?php
    foreach ($cl_box_groups as $groups) {
    $counter++;

    echo '            <li><a href="#toggle' . $counter  .'" data-toggle="collapse">' . (defined(BOX_HEADING_ICON_ . $counter) ? tep_glyphicon(constant('BOX_HEADING_ICON_' . $counter) . ' hidden-xs hidden-sm') : tep_glyphicon('play')) . $groups['heading'] . '<em class="hidden-xs hidden-sm click clickopen"></em></a>' . "\n" .
         '              <ul class="nav collapse" id="toggle' . $counter  .'">' . "\n";
		 
    foreach ($groups['apps'] as $app) {
      echo '                <li class="active ' .  (($app['code'] === $PHP_SELF) ? 'menu-open' : '') . '"><a href="' . $app['link'] . '">' . $app['title'] . '</a></li>' . "\n";
      }
	  
    echo '              </ul>' . "\n" .
         '            </li>' . "\n";
    }
?>
          </ul>
        </div><!--columnleft/#sidebar-->
<?php
  }
?>