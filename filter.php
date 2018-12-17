<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details
 *
 * @package    filter
 * @subpackage hvpinsert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Francis Devine <francis@catalyst.net.nz>
 * @author     Lea Cohen <leac@ort.org.il>
 */


class filter_hvpinsert extends moodle_text_filter {

    public function setup($page, $context) {
        $page->requires->js(new moodle_url('/filter/hvpinsert/js/iframeResizer.min.js'), true);
    }

    public function filter($text, array $options = array()) {
        if(!is_string($text) || empty($text)) {
            return $text;
        }
        //avoid doing regex if we can get away with it
        if(strpos($text, '[[mod_hvp:')) {
            $text = preg_replace_callback('/\[\[mod_hvp:(\w+?)(\:\d+?)?\]\]/', function ($matches) {
                global $CFG, $DB, $PAGE;
                global $HVP_COUNT;
                if(!isset($HVP_COUNT)) {
                    $HVP_COUNT = 0;
                }
                $idnumber = clean_param($matches[1], PARAM_TEXT);
                $offset = 40;//40px by default
                if(isset($matches[2])) {
                    $offset = clean_param(trim($matches[2], ':'), PARAM_INT);
                }
                $sql = "
                        SELECT m.id
                          FROM {course_modules} m
                    INNER JOIN {hvp} hvp
                            ON hvp.id = m.instance
                         WHERE m.idnumber = :idnumber
                       ";
                $params = array('idnumber' => $idnumber);
                $record = $DB->get_record_sql($sql, $params);
                if($record) {
                    //insert iframe resizer library (via AMD)
                    $id = 'filter_hvp_'.$HVP_COUNT;
                    $url = new moodle_url('/filter/hvpinsert/view.php', array('id' => $record->id, 'embedded' => 1));
                    $content = '<iframe class="filter_hvp" id="'.$id.'" src="'.$url.'" style="width:1px;min-width:100%;border:none;" frameborder="0" scrolling="no"></iframe>';
                    $content .= "<script> iFrameResize({}, '#".$id."') </script>";
                    $HVP_COUNT += 1;
                    return $content;
                }
                else {
                    return get_string('invalididnumber', 'filter_hvpinsert');
                }
            }, $text);
        }
        return $text;
    }
}
