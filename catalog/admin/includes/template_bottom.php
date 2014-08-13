<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/
?>
          <br />   
        </div><!-- #bodyContent -->
        
      </div><!-- .row-offcanvas -->
    </div><!--#main-->
    
<?php  
  require(DIR_WS_INCLUDES . 'footer.php'); 
?>
  
  </div><!-- bodyWrapper -->
<?php
  if (tep_session_is_registered('admin')) {
?>      
<script>
$(document).ready(function() {

  $('.selectpicker').selectpicker({
    style: 'btn-default'
  });

  $('[data-toggle=offcanvas]').click(function() {
    $('.row-offcanvas').toggleClass('selected');
  });

  $('.menu-open').parent('ul').addClass('in');

  $('[data-toggle=collapse]').click(function() {
    $(this).find('.click').toggleClass('clickopen clickclose');
  });
  
  $('.file-wrapper input[type=file]').bind('change focus click', fileinputbtn.fileInputs);
  
});
var fileinputbtn = fileinputbtn || {};
fileinputbtn.fileInputs = function() {
//SITE.fileInputs = function() {
var $this = $(this),
$val = $this.val(),
valArray = $val.split('\\'),
newVal = valArray[valArray.length-1],
$button = $this.siblings('.btn'),
$fakeFile = $this.siblings('.file-holder');
if(newVal !== '') { 
$button.text('<?php echo TEXT_BROWSE_DONE; ?>');
if($fakeFile.length === 0) {
$button.after('<br><span class="label label-info file-holder">' + newVal + '</span>');
} else {
$fakeFile.text(newVal);
}
}
};


  equalheight = function(container) {

    var currentTallest = 590,
      currentRowStart = 0,
      rowDivs = new Array(),
      $el,
      topPosition = 0;
    $(container).each(function() {

      $el = $(this);
      $($el).height('auto')
      topPostion = $el.position().top;

      if (currentRowStart != topPostion) {
        for (currentDiv = 0; currentDiv < rowDivs.length; currentDiv++) {
          rowDivs[currentDiv].height(currentTallest);
        }
        rowDivs.length = 0; // empty the array
        currentRowStart = topPostion;
        currentTallest = $el.height();
        rowDivs.push($el);
      } else {
        rowDivs.push($el);
        currentTallest = (currentTallest < $el.height()) ? ($el.height()) : (currentTallest);
      }
      for (currentDiv = 0; currentDiv < rowDivs.length; currentDiv++) {
        rowDivs[currentDiv].height(currentTallest);
      }
    });
  }

  $(window).load(function() {
    equalheight('.row-offcanvas .equal');
  });

  $(window).resize(function() {
    equalheight('.row-offcanvas .equal');
  });

  $(window).scroll(function() {
    equalheight('.row-offcanvas .equal');
  });
</script> 
<?php 
  } 
?>
</body>
</html>