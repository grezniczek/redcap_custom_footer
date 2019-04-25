<?php

namespace RUB\CustomFooterExternalModule;

use ExternalModules\AbstractExternalModule;

//region Configuration Helper Classes

/**
 * A helper class that holds config information for this module.
 */
class NestedConfig {
    public $enabled;
    public $samefooter;
    public $dataentryfooter;
    public $surveyfooter;
    public $position;
}

/**
 * A helper class that holds config information for this module.
 */
class Config {
    /**
     * @var NestedConfig
     */
    public $System;
    /**
     * @var NestedConfig
     */
    public $Project;

    public $allowsettings;
    public $allowsettingsids;
    public $allowoverride;
    public $allowoverrideids;
    public $dataentryoverride;
    public $surveyoverride;
    public $menutitle;

    function __construct() {
        $this->System = new NestedConfig();
        $this->Project = new NestedConfig();
    }
}

//endregion

/**
 * ExternalModule class for Custom Footer.
 */
class CustomFooterExternalModule extends AbstractExternalModule {

    public $CONFIGVALUE_PREFIX = "customfooter_";

    private $_systemValues;
    private $_projectValues;
    private $_projectId;

    //region Hook implementations

    function redcap_every_page_top($project_id = null) {
        $this->_projectId = $project_id;
        $this->addCustomFooter();
    }

    /**
     * Only show the Configure button when this is enabled in the
     * system settings for this project (or for all projects).
     */
    function redcap_module_configure_button_display($project_id) {
        // Superusers can always access configuration.
        if (SUPER_USER) return true;
        // Check project-specific settings.
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

    //endregion

    //region Build and inject footer

    /**
     * Build and inject the custom footer.
     */
    private function addCustomFooter() {
        $config = $this->_getConfig();
        $systemFooter = "";
        $projectFooter = "";
        $survey = $this->_isSurveyPage();
        // Build system footer.
        do {
            // Is the system footer enabled?
            if ($config->System->enabled == "disabled") break;
            // Does the page type match?
            if ($survey && $config->System->enabled == "dataentry") break;
            if (!$survey && $config->System->enabled == "survey") break;
            $systemFooter = $survey ?
                ($config->System->samefooter == "same" ? $config->System->dataentryfooter : $config->System->surveyfooter) :
                $config->System->dataentryfooter;
        } while (false);

        // Build project footer.
        do {
            // Is the project footer enabled?
            if ($config->Project->enabled == "disabled") break;
            // Does the page type match?
            if ($survey && $config->Project->enabled == "dataentry") break;
            if (!$survey && $config->Project->enabled == "survey") break;
            $projectFooter = $survey ? 
                ($config->Project->samefooter == "same" ? $config->Project->dataentryfooter : $config->Project->surveyfooter) :
                $config->Project->dataentryfooter;
            // Apply system overrides (if allowed).
            if ($config->allowoverride == "all" || ($config->allowoverride == "selected" && in_array($this->_projectId, $config->allowoverrideids))) {
                // Page type match?
                if ((!$survey && $config->dataentryoverride) || ($survey && $config->surveyoverride)) {
                    // Simply clear the system footer.
                    $systemFooter = "";
                }
            }
        } while (false);

        // Determine position of project footer in relation to system footer.
        $position = $config->System->position;
        if ($config->Project->postion != "system" && ($config->allowoverride == "all" || ($config->allowoverride == "selected" && in_array($this->_projectId, $config->allowoverrideids)))) {
            $position = $config->Project->position;
        }
        // Sanitize footers (no <script> tags).
        $systemFooter = str_ireplace("<script", "**REMOVED**", $systemFooter);
        $projectFooter = str_ireplace("<script", "**REMOVED**", $projectFooter);
        // Determine order.
        $first = "system";
        $firstFooter = $systemFooter;
        $injectFirst = strlen($systemFooter) > 0 ? "true" : "false";
        $second = "project";
        $secondFooter = $projectFooter;
        $injectSecond = strlen($projectFooter) > 0 ? "true" : "false";
        if ($position == "above") {
            $first = "project";
            $firstFooter = $projectFooter;
            $injectFirst = strlen($projectFooter) > 0 ? "true" : "false";
            $second = "system";
            $secondFooter = $systemFooter;
            $injectSecond = strlen($systemFooter) > 0 ? "true" : "false";
        }
        // Construct menu title
        $title = "";
        if (strlen($config->menutitle) > 0) {
            $title = '<div class="x-panel-header x-panel-header-leftmenu"><div style="float:left;">' . $config->menutitle . '</div></div>';
        }
        // Output the footer(s).
        if ($injectFirst == "true") echo <<<EOF
        <template id="{$this->PREFIX}_{$first}_footer_template">
            <div id="{$this->PREFIX}_{$first}_footer">
                {$firstFooter}
            </div>
        </template>
EOF;
        if ($injectSecond == "true") echo <<<EOF
        <template id="{$this->PREFIX}_{$second}_footer_template">
            <div id="{$this->PREFIX}_{$second}_footer">
                {$secondFooter}
            </div>
        </template>
EOF;
        // To put this all in the right place, we will use JavaScript to determine
        // the type of page we are on, by looking whether a div#footer exists. If 
        // it does (survey pages, non-project system pages), we simply append the
        // footers there. If not, then we create a new panel in the left side menu
        // and add them there instead. Not ideal, but the existing "footer" (south) 
        // is quite resilient to changes of its height ....

        echo <<<EOF
        <script>
            $(function() {
                // Survey page showing reCAPTCHA. Need to insert the footer first.
                let footerMarginTop = 10
                if ($("div.g-recaptcha").length == 1 && $("div#footer").length == 0) {
                    footerMarginTop = -40
                    $("#pagecontainer").append(`<div id="footer" class="d-sm-block col-md-12" aria-hidden="true" style="display: block;"><a href="https://projectredcap.org" tabindex="-1" target="_blank" style="margin-bottom: 10px; display: inline-block;">Powered by REDCap</a></div>`)
                }
                // Any page with a footer (but not project data entry pages).
                var f = $("#footer")
                if (f.prop("id") == "footer") {
                    f.removeClass("hidden-xs")
                    f.removeClass("d-none")
                    f.css("display", "block")
                    f.css("margin-top", `\${footerMarginTop}px`)
                    f.children().css("margin-bottom", "10px")
                    f.children().css("display", "inline-block")
                    if ({$injectFirst}) f.append($("#{$this->PREFIX}_{$first}_footer_template").prop("content"))
                    if ({$injectSecond}) {
                         f.append($("#{$this->PREFIX}_{$second}_footer_template").prop("content"))
                    }
                }
                else {
                    // Data entry page.
                    const wrapper = $('<div class="x-panel">{$title}<div class="x-panel-bwrap"><div class="x-panel-body"><div class="menubox"><div class="menubox"></div></div></div></div></div>')
                    f = wrapper.find("div.menubox:last")
                    if ({$injectFirst}) f.append($("#{$this->PREFIX}_{$first}_footer_template").prop("content"))
                    if ({$injectSecond}) {
                        f.append($("#{$this->PREFIX}_{$second}_footer_template").prop("content"))
                    }
                    $("#west div.x-panel:last").after(wrapper)
                }
            })
        </script>
EOF;
    }

    private function _isSurveyPage() {
        return strpos($_SERVER[REQUEST_URI], APP_PATH_SURVEY) !== false;
    }

    //endregion

    //region Config Helpers

    /**
     * This helper function assembles the config values based on 
     * the settings made in the External Module configuration.
     * 
     * @return Config
     *   The configuration to act upon.
     */
    private function _getConfig() : Config {
        // Cache settings values.
        $this->_systemValues = $this->getSystemSettings();
        if ($this->_projectId) $this->_projectValues = $this->getProjectSettings($this->_projectId);

        // Build the configuration.
        $config = new Config();

        // Read system configuration
        $config->System->enabled = $this->_getSystemValue("system_enabled", "disabled");
        $config->System->dataentryfooter = $this->_getSystemValue("system_dataentryfooter", "");
        $config->System->samefooter = $this->_getSystemValue("system_samefooter", "same");
        $config->System->surveyfooter = $this->_getSystemValue("system_surveyfooter", "");
        $config->allowsettings = $this->_getSystemValue("system_allowsettings", "deny");
        $config->allowsettingsids = $this->_parseIds($this->_getSystemValue("system_allowsettingsids", ""));
        $config->allowoverride = $this->_getSystemValue("system_allowoverride", "deny");
        $config->allowoverrideids = $this->_parseIds($this->_getSystemValue("system_allowoverrideids", ""));
        $config->System->position = $this->_getSystemValue("system_position", "above");
        $config->menutitle = $this->_getSystemValue("system_menutitle", "");

        // Read project settings
        if ($this->_projectId) {
            $config->dataentryoverride = $this->_getProjectValue("project_dataentryoverride", false);
            $config->surveyoverride = $this->_getProjectValue("project_surveyoverride", false);
            $config->Project->position = $this->_getProjectValue("project_positionoverride", "system");
            $config->Project->enabled = $this->_getProjectValue("project_enabled", "disabled");
            $config->Project->dataentryfooter = $this->_getProjectValue("project_dataentryfooter", "");
            $config->Project->samefooter = $this->_getProjectValue("project_samefooter", "same");
            $config->Project->surveyfooter = $this->_getProjectValue("project_surveyfooter", "");
        }
        else {
            $config->dataentryoverride = false;
            $config->surveyoverride = false;
            $config->Project->position = "system";
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
        $fullname = $this->CONFIGVALUE_PREFIX . $name;
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
        $fullname = $this->CONFIGVALUE_PREFIX . $name;
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
        $matches = array();
        $re = '/\s*([0-9]+)/m';
        preg_match_all($re, $raw, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $match)
            array_push($ids, $match[1]);
        return $ids;
    }

    //endregion
}
