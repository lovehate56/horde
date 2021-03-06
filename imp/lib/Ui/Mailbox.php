<?php
/**
 * The IMP_Ui_Mailbox:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for the mailbox page.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ui_Mailbox
{
     const DATE_FORCE = 1;
     const DATE_FULL = 2;

    /**
     * The current mailbox.
     *
     * @var IMP_Mailbox
     */
    private $_mailbox;

    /**
     * Cached data.
     *
     * @var array
     */
    private $_cache = array();

    /**
     * Constructor.
     *
     * @param IMP_Mailbox $mailbox  The current mailbox.
     */
    public function __construct($mailbox = null)
    {
        $this->_mailbox = $mailbox;
    }

    /**
     * Get From address information for display on mailbox page.
     *
     * @param Horde_Imap_Client_Data_Envelope $ob  An envelope object.
     * @param array $options                       Additional options:
     *   - fullfrom: (boolean) If true, returns 'fullfrom' information.
     *               DEFAULT: false
     *   - specialchars: (string) If set, run 'from' return through
     *                   htmlspecialchars() using the given charset.
     *
     * @return array  An array of information:
     * <pre>
     * 'error' - (boolean) True on error.
     * 'from' - (string) The personal part of the From address.
     * 'fullfrom' - (string) The full From address.
     * 'to' - (boolean) True if this is who the message was sent to.
     * </pre>
     */
    public function getFrom($ob, $options = array())
    {
        $ret = array('error' => false, 'to' => false);

        if (empty($ob->from)) {
            $ret['from'] = $ret['fullfrom'] = _("Invalid Address");
            $ret['error'] = true;
            return $ret;
        }

        if (!isset($this->_cache['drafts_sm_folder'])) {
            $this->_cache['drafts_sm_folder'] = $this->_mailbox->special_outgoing;
        }

        $from = Horde_Mime_Address::getAddressesFromObject($ob->from, array('charset' => 'UTF-8'));
        $from = reset($from);

        if (!empty($from) && !isset($from['inner'])) {
            $from = (isset($from['addresses']) && count($from['addresses']))
                ? $from['addresses'][0]
                : null;
        }

        if (empty($from)) {
            $ret['from'] = _("Invalid Address");
            $ret['error'] = true;
        } else {
            if ($GLOBALS['injector']->getInstance('IMP_Identity')->hasAddress($from['inner'])) {
                /* This message was sent by one of the user's identity
                 * addresses - show To: information instead. */
                if (empty($ob->to)) {
                    $ret['from'] = _("Undisclosed Recipients");
                    $ret['error'] = true;
                } else {
                    $to = Horde_Mime_Address::getAddressesFromObject($ob->to, array('charset' => 'UTF-8'));
                    $first_to = reset($to);
                    if (empty($first_to)) {
                        $ret['from'] = _("Undisclosed Recipients");
                        $ret['error'] = true;
                    } else {
                        $ret['from'] = empty($first_to['personal'])
                            ? $first_to['inner']
                            : $first_to['personal'];
                        if (!empty($options['fullfrom'])) {
                            $ret['fullfrom'] = $first_to['display'];
                        }
                    }
                }
                if (!$this->_cache['drafts_sm_folder']) {
                    $ret['from'] = _("To") . ': ' . $ret['from'];
                }
                $ret['to'] = true;
            } else {
                $ret['from'] = empty($from['personal'])
                    ? $from['inner']
                    : $from['personal'];
                if ($this->_cache['drafts_sm_folder']) {
                    $ret['from'] = _("From") . ': ' . $ret['from'];
                }
                if (!empty($options['fullfrom'])) {
                    $ret['fullfrom'] = $from['display'];
                }
            }
        }

        if (!empty($options['fullfrom']) && !isset($ret['fullfrom'])) {
            $ret['fullfrom'] = $ret['from'];
        }

        if (!empty($ret['from']) && !empty($options['specialchars'])) {
            $res = @htmlspecialchars($ret['from'], ENT_QUOTES, $options['specialchars']);
            if (empty($res)) {
                $res = @htmlspecialchars($ret['from']);
            }
            $ret['from'] = $res;
        }

        return $ret;
    }

    /**
     * Get size display information.
     *
     * @param integer $size  The size of the message, in bytes.
     *
     * @return string  A formatted size string.
     */
    public function getSize($size)
    {
        return ($size >= 1048576)
            ? sprintf(_("%s MB"), IMP::numberFormat($size / 1048576, 1))
            : sprintf(_("%s KB"), IMP::numberFormat($size / 1024, 0));
    }

    /**
     * Formats the date header.
     *
     * @param mixed $date      The date object. Either a DateTime object or a
     *                         date string.
     * @param integer $format  Mask of formatting options:
     *   - IMP_Ui_Mailbox::DATE_FORCE - Force use of date formatting, instead
     *                                  of time formatting, for all dates.
     *   - IMP_Ui_Mailbox::DATE_FULL - Use full representation of date,
     *                                 including time information.
     *
     * @return string  The formatted date header.
     */
    public function getDate($date, $format = 0)
    {
        if (!is_object($date)) {
            $date = new Horde_Imap_Client_DateTime($date);
        }

        if (!($format & self::DATE_FORCE) &&
            !isset($this->_cache['today_start'])) {
            $this->_cache['today_start'] = new DateTime('today');
            $this->_cache['today_end'] = new DateTime('today + 1 day');
        }

        $udate = null;
        if (!$date->error()) {
            try {
                $udate = $date->format('U');
            } catch (Exception $e) {}
        }

        if (is_null($udate)) {
            return _("Unknown Date");
        }

        if (($format & self::DATE_FORCE) ||
            ($udate < $this->_cache['today_start']->format('U')) ||
            ($udate > $this->_cache['today_end']->format('U'))) {
            /* Not today, use the date. */
            if ($format & self::DATE_FULL) {
                return strftime($GLOBALS['prefs']->getValue('date_format'), $udate) .
                    ' [' . strftime($GLOBALS['prefs']->getValue('time_format'), $udate) . ']';
            }

            return strftime($GLOBALS['prefs']->getValue('date_format_mini'), $udate);
        }

        /* Else, it's today, use the time. */
        return strftime($GLOBALS['prefs']->getValue('time_format'), $udate);
    }

    /**
     * Formats the subject header.
     *
     * @param string $subject     The MIME encoded subject header.
     * @param string $htmlspaces  HTML-ize spaces?
     *
     * @return string  The formatted subject header.
     */
    public function getSubject($subject, $htmlspaces = false)
    {
        $subject = Horde_Mime::decode($subject, 'UTF-8');
        if (empty($subject)) {
            return _("[No Subject]");
        }

        $new_subject = $subject = IMP::filterText(preg_replace("/\s+/", ' ', $subject));

        if ($htmlspaces) {
            $new_subject = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($subject, 'space2html', array('encode' => true));
            if (empty($new_subject)) {
                $new_subject = htmlspecialchars($subject);
            }
        }

        return empty($new_subject) ? $subject : $new_subject;
    }

}
