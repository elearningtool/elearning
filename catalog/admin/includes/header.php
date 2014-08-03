<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/
?>
    <div class="navbar navbar-osc navbar-fixed-top" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="offcanvas" data-target=".sidebar-nav">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
<?php
  echo '          <a class="navbar-brand" href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image(DIR_WS_IMAGES . 'oscommerce.png', 'osCommerce Online Merchant v' . tep_get_version()) . '</a>';
?> 
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav navbar-right">
            <li><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . HEADER_TITLE_ADMINISTRATION . '</a>'; ?></li>
            <li><?php echo '<a href="' . tep_catalog_href_link() . '" target="_blank">' . HEADER_TITLE_ONLINE_CATALOG . '</a>'; ?></li>
            <li><?php echo '<a href="http://www.oscommerce.com" target="_blank">' . HEADER_TITLE_SUPPORT_SITE . '</a>'; ?></li>
            <li><?php echo (tep_session_is_registered('admin') ? '<a href="' . tep_href_link(FILENAME_LOGIN, 'action=logoff') . '">Logoff ' . $admin['username'] . '</a>' : ''); ?></li>
          </ul>
        </div>
      </div>
    </div><!--navbar header-->
    