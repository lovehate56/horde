<?php
require_once ANSEL_BASE . '/lib/Tags.php';

/**
 * Ansel_Widget_Tags:: class to display a tags widget in the image and gallery
 * views.
 *
 * $Horde: ansel/lib/Widget/Tags.php,v 1.15 2009/07/28 15:15:04 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Tags extends Ansel_Widget {

    var $_resourceType;

    function Ansel_Widget_Tags($params)
    {
        parent::Ansel_Widget($params);
        $this->_resourceType = $params['view'];
        $this->_title = _("Tags");
    }

    /**
     * Build the HTML for this widget
     *
     * @return string  The HTML representing this widget.
     */
    function html()
    {
        if ($this->_resourceType == 'image') {
            $image_id = $this->_view->resource->id;
        } else {
            $image_id = null;
        }

        /* Build the tag widget */
        $html = $this->_htmlBegin();
        $html .= '<div id="tags">' . $this->_getTagHTML() . '</div>';
        if ($this->_view->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            ob_start();

            /* Attach the Ajax action */
            $imple = Horde_Ajax_Imple::factory(array('ansel', 'TagActions'),
                                               array('bindTo' => array('add' => 'tagbutton'),
                                                     'gallery' => $this->_view->gallery->id,
                                                     'image' => $image_id));
            $imple->attach();
            $html .= ob_get_clean();

            // JS fallback is getting refactoring into xrequest.php
            $actionUrl = Horde_Util::addParameter('image.php',
                                                  array('image' => $this->_view->resource->id,
                                                        'gallery' => $this->_view->gallery->id));
            $html .= '<form name="tagform" action="' . $actionUrl . '" onsubmit="return !addTag();" method="post">';
            $html .= '<input id="addtag" name="addtag" type="text" size="15" /> <input onclick="return !addTag();" name="tagbutton" id="tagbutton" class="button" value="' . _("Add") . '" type="submit" />';
            $html .= '</form>';
        }
        $html .= $this->_htmlEnd();

        return $html;
    }


    /**
     * Helper function to build the list of tags
     *
     * @return string  The HTML representing the tag list.
     */
    function _getTagHTML()
    {
        global $registry;

            /* Clear the tag cache? */
        if (Horde_Util::getFormData('havesearch', 0) == 0) {
            Ansel_Tags::clearSearch();
        }

        // TODO - Degrade the delete links to work without js
        $hasEdit = $this->_view->gallery->hasPermission(Horde_Auth::getAuth(),
                                                        PERMS_EDIT);
        $owner = $this->_view->gallery->get('owner');
        $tags = $this->_view->resource->getTags();
        if (count($tags)) {
            $tags = Ansel_Tags::listTagInfo(array_keys($tags));
        }

        $links = Ansel_Tags::getTagLinks($tags, 'add', $owner);
        $html = '<ul>';
        foreach ($tags as $tag_id => $taginfo) {
            $html .= '<li>' . Horde::link($links[$tag_id], sprintf(ngettext("%d photo", "%d photos", $taginfo['total']), $taginfo['total'])) . $taginfo['tag_name'] . '</a>' . ($hasEdit ? '<a href="#" onclick="removeTag(' . $tag_id . ');">' . Horde::img('delete-small.png', _("Remove Tag"), '', $registry->getImageDir('horde')) . '</a>' : '') . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

}