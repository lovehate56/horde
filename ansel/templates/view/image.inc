<script type="text/javascript">
document.observe('dom:loaded', function() {
        <?php if (empty($this->_params['hide_slideshow'])): ?>
        $$('.ssPlay').each(function(n) { n.show();});
        <?php endif; ?>
        Event.observe($('photodiv'), 'load', function() {
                new Effect.Appear($('photodiv'), { duration: 0.5,
                                                   afterFinish: function() {$$('.imgloading').each(function(n) { n.setStyle({visibility: 'hidden'});});
                                                                            new Effect.Appear($('Caption'), { duration: 0.5 });
                                                                             }});
                var nextImg = new Image();
                var prvImg = new Image();
                nextImg.src = "<?php echo $nextImgUrl ?>";
                prvImg.src = "<?php echo $prvImgUrl ?>";
        });
        new Effect.Opacity('photodiv', {to: 0, duration: 0.5, afterFinish: function() {$('photodiv').src = "<?php echo $this->_urls['imgsrc'] ?>"} });

        // Arrow keys for navigation
        document.observe('keydown', arrowHandler);
});

function arrowHandler(e)
{
    if (e.altKey || e.shiftKey || e.ctrlKey) {
        return;
    }

    theElement = Event.element(e);
    switch (theElement.tagName) {
    case 'INPUT':
    case 'SELECT':
    case 'TEXTAREA':
        return;
    }
    switch (e.keyCode || e.charCode) {
    case Event.KEY_LEFT:
        if ($('PrevLink')) {
            document.location.href = $('PrevLink').href;
        }
        break;

    case Event.KEY_RIGHT:
        if ($('NextLink')) {
            document.location.href = $('NextLink').href;
        }
        break;
    }
}
</script>
<h1 class="header" id="PhotoName"><?php echo Ansel::getBreadCrumbs($this->gallery) ?></h1>
<table style="width: 100%; border-collapse: collapse">
 <tr>
  <td valign="top" style="background-color:<?php echo $this->_style->background; ?>;">
    <?php
        $tempurl = new Horde_Url('#');
        $needSeperator = false;
        $actionHTML = '';
        if ($this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT) && empty($this->_params['api'])) {
            $needSeperator = true;
            $actionHTML .= $tempurl->link(array('target' => '_blank', 'onclick' => $this->_urls['prop_popup'] . 'return false;', 'id' => 'image_properties_link')) . _("Properties") . '</a>';
            $actionHTML .= ' | ' . $this->_urls['edit']->link(array('id' => 'image_edit_link')) . _("Edit") . '</a>';
        }
        if ($this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE) && empty($this->_params['api'])) {
            $actionHTML .= ' | ' . $this->_urls['delete']->link(array('onclick' => 'return window.confirm(\'' . addslashes(sprintf(_("Do you want to permanently delete ''%s''?"), $this->resource->filename)) . '\');', 'id' => 'image_delete_link')) . _("Delete") . '</a>';
        }
        if (!empty($conf['ecard']['enable']) && !empty($this->_urls['ecard'])) {
            $actionHTML .= (empty($this->_params['api']) && $needSeperator ? ' | ' : '') . $this->_urls['ecard']->link(array('target' => '_blank', 'id' => 'image_ecard_link')) . _("Send an Ecard"); $needSeperator = true . '</a>';
        }
        if ($this->gallery->canDownload()) {
            $actionHTML .= (empty($this->_params['api']) && $needSeperator ? ' | ' : '') . $this->_urls['download']->link(array('id' => 'image_download_link')) . _("Download Original Photo"); $needSeperator = true . '</a>';
        }
        if (empty($this->_params['api']) &&
            (!$GLOBALS['registry']->getAuth() ||
             $this->gallery->get('owner') != $GLOBALS['registry']->getAuth()) &&
            !empty($GLOBALS['conf']['report_content']['driver']) &&
            (($conf['report_content']['allow'] == 'authenticated' &&
              $GLOBALS['registry']->isAuthenticated()) ||
             $conf['report_content']['allow'] == 'all')) {

            $actionHTML .=  ($needSeperator ? ' | ' : '' ) . $this->_urls['report']->link() . _("Report") . '</a>';
        }
        if (strlen($actionHTML)) {
            echo '<div class="control anselActions" style="text-align:center;">' . $actionHTML . '</div>';
        }
?>
    <div class="slideControls">
     <?php echo Horde::fullSrcImg('loading.gif', array('attr' => 'class="imgloading"'));?>
     <?php
     if (empty($this->_params['hide_slideshow'])) {
         echo '<a style="display:none;" href="' . $this->_urls['slideshow'] . '" class="ssPlay" title="' . _("Start Slideshow") . '">' .
               Horde::fullSrcImg('slideshow_play.png', array('attr' => 'alt="' .  _("Play") . '"')) . '</a>';
     }
     echo '<a href="' . $prev_url . '" alt="<" id="PrevLink" title="' . _("Previous") . '">' .
           Horde::fullSrcImg('slideshow_prev.png', array('attr' => 'alt="' . _("Previous") . '"')) . '</a>';
     echo '<a href="' . $next_url . '" alt=">" id="NextLink"  title="' . _("Next") . '">' .
           Horde::fullSrcImg('slideshow_next.png', array('attr' => 'alt="' . _("Next") . '"')) . '</a>';
     echo '<a href="' . $this->_urls['gallery'] . '" id="GalleryLink" title="' . _("Back to gallery") . '">' .
           Horde::fullSrcImg('galleries.png', array('attr' => 'alt="' . _("Back to gallery") . '"')) . '</a>';
     ?>

    </div>
    <div id="Container">
     <noscript>
      <?php echo '<img src="' . $this->_urls['imgsrc'] . '" alt="' . htmlspecialchars($this->resource->filename) . '" />' ?>
     </noscript>
     <?php echo Horde::img('blank.gif', '', array('id' => 'photodiv', 'width' => $this->_geometry['width'], 'height' => $this->_geometry['height'])) ?>
     <div id="CaptionContainer" style="width:<?php echo $this->_geometry['width']?>px;">
     <p id="Caption" style="display:none;">
       <?php echo $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($this->resource->caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
     </p>
     </div>
    </div>
    <div class="slideControls">
     <?php echo Horde::fullSrcImg('loading.gif', array('attr' => 'class="imgloading"'));?>
     <?php
     if (empty($this->_params['hide_slideshow'])) {
         echo '<a style="display:none;" href="' . $this->_urls['slideshow'] . '" class="ssPlay" title="' . _("Start Slideshow") . '">' .
               Horde::fullSrcImg('slideshow_play.png', array('attr' => 'alt="' .  _("Play") . '"')) . '</a>';
     }
     echo '<a href="' . $prev_url . '" alt="<" id="PrevLink" title="' . _("Previous") . '">' .
           Horde::fullSrcImg('slideshow_prev.png', array('attr' => 'alt="' . _("Previous") . '"')) . '</a>';
     echo '<a href="' . $next_url . '" alt=">" id="NextLink"  title="' . _("Next") . '">' .
           Horde::fullSrcImg('slideshow_next.png', array('attr' => 'alt="' . _("Next") . '"')) . '</a>';
     echo '<a href="' . $this->_urls['gallery'] . '" id="GalleryLink" title="' . _("Back to gallery") . '">' .
           Horde::fullSrcImg('galleries.png', array('attr' => 'alt="' . _("Back to gallery") . '"')) . '</a>';
     ?>
    </div>
    <?php
     if ($prefs->getValue('showexif')) {
             echo '<br class="spacer" /><div id="exif">' . $exifHtml . '</div>';
     } ?>
     <div id="messagediv">
      <?php echo $commentHtml; ?>
     </div>
  </td>
  <?php if ($this->countWidgets()): ?>
  <td width="20%" valign="top">
    <!-- Widgets -->
   <?php echo $this->renderWidgets() ?>
  </td>
  <?php endif ?>
 </tr>
</table>
