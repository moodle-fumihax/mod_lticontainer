<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_lticontainer
 * @copyright   2021 Fumi.Iseki <fumi.hax@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin = new stdClass();

$plugin->release   = '1.6.2';

$plugin->version   = 2025122500;    // v1.6.2 for image name bug for Docker
//$plugin->version = 2025122001;    // v1.6.1 for Moodle 5, PHP-8.3
//$plugin->version = 2025020303;    // v1.5.2 for PHP-8.1 later
//$plugin->version = 2024060500;    // add activity limit
//$plugin->version = 2024041300;    // closing submit, join volume
//$plugin->version = 2024010500;    // for Icon
//$plugin->version = 2023082701;
//$plugin->version = 2023050901;
//$plugin->version = 2023050600;
//$plugin->version = 2023040250;
//$plugin->version = 2022072500;
//$plugin->version = 2022071900;
//$plugin->version = 2022070501;
//$plugin->version = 2022063000;
//$plugin->version = 2022042601;

$plugin->requires  = 2018051700;
$plugin->component = 'mod_lticontainer';
$plugin->maturity  = MATURITY_STABLE;

