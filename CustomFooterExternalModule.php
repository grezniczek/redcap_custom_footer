<?php

namespace RUB\CustomFooterExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


class NestedConfig {
    public $enabled;
    public $samefooter;
    public $dataentryfooter;
    public $surveyfooter;
    public $position;
}

class Config {
    public $System;
    public $Project;

    public $allowsettings;
    public $allowsettingsids;
    public $allowoverride;
    public $allowoverrideids;
    public $allowpositionoverride;
    public $dataentryoverride;
    public $surveyoverride;

    function __construct() {
        $this->System = new NestedConfig();
        $this->Project = new NestedConfig();
    }
}

/**
 * ExternalModule class for Instance-Type Indicator.
 * 
 */
class CustomFooterExternalModule extends AbstractExternalModule {

    private $_configValuePrefix = "customfooter_";
    private $_systemValues;
    private $_projectValues;
    private $_projectId;


    function redcap_every_page_top($project_id = null) {
        $this->_projectId = $project_id;
        $this->addCustomFooter();
    }

    /**
     * Only show the "Configure" button when this is enabled in the
     * system settings for this project (or for all projects).
     */
    function redcap_module_configure_button_display($project_id) {
        if ($project_id) {
            $config = $this->_getConfig($project_id);
            switch ($config->allowsettings) {
                case "deny":
                    return null;
                case "all":
                    return true;
                case "selected":
                    if (in_array($project_id, $config->allowsettingsids))
                        return true;
                    return null;
            }
        }
        return true;
    }

    /**
     * Build and inject the custom footer.
     */
    private function addCustomFooter() {
        $config = $this->_getConfig();
        // Only do the work if the indicator is not 'disabled'.
        if ($config->enabled !== "disabled") {
            // Calculate some values up front.

            echo "<div style=\"z-index:2000;position:fixed;top:0;left:0;width:200px;height:20px;text-align:center;background-color:orange;color:white\"><b>CustomFooterModule</b></div>";
        }
    }

    /**
     * This helper function assembles the config values based on 
     * the settings made in the External Module configuration.
     * 
     * @return array 
     *   The configuration to act upon.
     */
    private function _getConfig() {
        // Cache settings values.
        $this->_systemValues = ExternalModules::getSystemSettingsAsArray($this->PREFIX);
        if ($this->_projectId) $this->_projectValues = ExternalModules::getProjectSettingsAsArray($this->PREFIX, $this->_projectId);

        // Build the configuration.
        $config = new Config();

        // Read system configuration
        $config->System->enabled = $this->_getSystemValue("system_enabled", "disabled");
        $config->System->dataentryfooter = $this->_getSystemValue("system_dataentryfooter", "");
        $config->System->samefooter = $this->_getSystemValue("system_same", "same");
        if ($config->System->samefooter == "same") {
            $config->System->surveyfooter = $config->System->dataentryfooter;
        }
        else {
            $config->System->surveyfooter = $this->_getSystemValue("system_surveyfooter", "");
        }
        $config->allowsettings = $this->_getSystemValue("system_allowsettings", "deny");
        $config->allowsettingsids = $this->_parseIds($this->_getSystemValue("system_allowsettingsids", ""));
        $config->allowoverride = $this->_getSystemValue("system_allowoverride", "deny");
        $config->allowoverrideids = $this->_parseIds($this->_getSystemValue("system_allowoverrideids", ""));
        $config->System->position = $this->_getSystemValue("system_position", "above");
        $config->allowpositionoverride = $this->_getSystemValue("system_allowpositionoverride", false);

        // Read project settings
        if ($this->_projectId) {
            $config->dataentryoverride = $this->_getProjectValue("project_dataentryoverride", false);
            $config->surveyoverride = $this->_getProjectValue("project_surveyoverride", false);
            $positionoverride = $this->_getProjectValue("project_positionoverride", "system");
            $config->Project->enabled = $this->_getProjectValue("project_enabled", "disabled");
            $config->Project->dataentryfooter = $this->_getProjectValue("project_dataentryfooter", "");
            $config->Project->samefooter = $this->_getProjectValue("project_samefooter", "same");
            $config->Project->surveyfooter = $this->_getProjectValue("project_surveyfooter", "");
            $config->Project->position = $config->allowpositionoverride ?
                $positionoverride : "system";
        }
        else {
            $config->dataentryoverride = false;
            $config->surveyoverride = false;
            $config->Project->position = "systen";
            $config->Project->enabled = "disabled";
            $config->Project->dataentryfooter = "";
            $config->Project->samefooter = "same";
            $config->Project->surveyfooter = "";
        }
        if ($config->Project->position == "system")
            $config->Project->position = $config->System->position;
        return $config;
    }

    /**
     * This helper function pulls out values from system settings.
     * 
     * @param string $name 
     *   The name of the settings value (without the 'indicator_' prefix).
     * @param mixed $default 
     *   The default value to return when there is no value configured (null).
     * @param bool $numeric
     *   Indicates whether the configuration value must be numeric. If it's not, 
     *   the default is returned instead.
     * @return string 
     *   The value of the setting as a string.
     */
    private function _getSystemValue($name, $default, $numeric = false) {
        $fullname = $this->_configValuePrefix . $name;
        $value = $this->_systemValues[$fullname]["system_value"];
        if (is_array($value)) $value = $value[0];
        if ($value == null) return $default;
        if ($numeric && !is_numeric($value)) return $default;
        return $value;
    }

    /**
     * This helper function pulls out values from project settings.
     * 
     * @param string $name 
     *   The name of the settings value (without the 'indicator_' prefix).
     * @param mixed $default 
     *   The default value to return when there is no value configured (null).
     * @param bool $numeric
     *   Indicates whether the configuration value must be numeric. If it's not, 
     *   the default is returned instead.
     * @return string 
     *   The value of the setting as a string.
     */
    private function _getProjectValue($name, $default, $numeric = false) {
        $fullname = $this->_configValuePrefix . $name;
        $value = $this->_projectValues[$fullname]["value"];
        if (is_array($value)) $value = $value[0];
        if ($value == null) return $default;
        if ($numeric && !is_numeric($value)) return $default;
        return $value;
    }

    /**
     * This helper function parses a list of project ids (integers) that
     * can be separated by virtually anything. The regex is quite permissive.
     * 
     * @param string $raw
     *   The string to parse.
     * @return array
     *   An array of integers (as strings).
     */
    private function _parseIds($raw) {
        $ids = array();
        $re = '/\s*([0-9]+)/m';
        preg_match_all($re, $raw, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $match)
            array_push($ids, $match[1]);
        return $ids;
    }
}