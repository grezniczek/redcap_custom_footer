# Custom Footer - Manual Testing Procedure

Version 1 - 2020-04-12

## Prerequisites

- A project with at least one instruments enabled as a survey.
- A non-superuser account with project design rights in the test project.
- Custom Footer is enabled for this project.
- No other external modules should be enabled, except those with which this module's interaction should be tested.

## Test Procedure

Configuration options for this module are complex, as system settings and project settings interplay with each other.

1. Using an admin account, configure the module **on the system level**:
   - Set _Enabled on_ to "Both".
   - Leave _Do not force a footer on pages lacking a #footer_ turned off.
   - Set the _Title for section in left side menu_ to "Footer Title".
   - Set the _Custom footer for project / data entry pages_ to "System Footer (Data Entry)".
   - Set to use a "Different" footer for survey pages and set it to "System Footer (Survey)".
   - Set _Allow project admins to configure settings_ to "Allow for selected projects only".
   - Set _List of project IDs where access to configuration is allowed_ to the project id of the test project.
   - Set _Allow override of system settings in projects_ to "Allow for selected projects only".
   - Set _List of project IDs that are allowed to override the system settings_ to the project id of the test project.
   - Set _Position of custom project footers_ to "below system footers".
1. Using an admin account, configure the module **in the test project**:
   - Leave _Override system settings for project / data entry pages_ and _Override system settings for survey pages_ turned off.
   - Set _Override footer position - show project footer_ to "use system setting".
   - Set _Enabled on_ to "Both".
   - Set _Custom footer for project / data entry pages_ to "Project Footer (Data Entry)".
   - Set to use a "Different" footer for survey pages and set it to "Project Footer (Survey)".
1. Go to any project page that is not a survey (e.g. the _Project Home_ page) and verify the following:
   - A section entitled "Footer Title" is visible at the bottom of the left side menu.
   - This section contains both, "System Footer (Data Entry)" and "Project Footer (Data Entry)", in that order.
1. As super user, go to the _Control Center_ and verify the following:
   - "System Footer (Data Entry)" is shown below the REDCap version info footer.
1. Open a survey in the test project and verify the following:
   - Both, "System Footer (Data Entry)" and "Project Footer (Data Entry)" are displayed, in that order, after the "Powered by REDCap" link.
1. Change the module configuration in the test project:
   - Set _Override footer position_ to "above system footer".
   - Set _Custom footer for survey pages_ to "Same as for project / data entry pages".
1. After the _External Modules - Project Module Manager_ page refreshes, verify the following:
   - In the left side menu, in the "Footer Title" section, "Project Footer (Data Entry)" is shown above "System Footer (Data Entry)".
1. Open a survey page, and verify the following:
   - Below "Powered by REDCap", "Project Footer (Data Entry)" is shown, followed by "System Footer (Survey)".
1. Change the module configuration in the test project:
   - Turn on both override options.
   - Change _Enabled on_ to _Survey Pages_.
1. After the _External Modules - Project Module Manager_ page refreshes, verify the following:
   - In the left side menu, in the "Footer Title" section, only "System Footer (Data Entry)" is shown.
1. Open a survey page, and verify the following:
   - Below "Powered by REDCap", only "Project Footer (Data Entry)" is shown.
1. Log into the test project using the limited account with project design rights.
1. Go to the _External Modules_ page and verify the following:
   - The _Configure_ button for the Custom Footer module is shown.
1. Change the module configuration in the test project:
   - Set _Enabled on_ to "Both".
   - Set _Custom footer for survey pages_ to "Different".
1. As a super user, change the module's system configuration:
   - Set _Allow project admins to configure settings_ to "Deny configuration".
   - Set _Allow override of system settings in projects_ to "Deny override".
1. With the limited account, refresh the _External Modules_ page in the test project and verify the following:
   - The _Configure_ button for the module is not available.
   - In the left side menu, in the "Footer Title" section, "System Footer (Data Entry)" is shown above "Project Footer (Data Entry)".

Done.

## Reporting Errors

Before reporting errors:
- Make sure there is no interference with any other external module by turning off all others and repeating the tests.
- Check if you are using the latest version of the module. If not, see if updating fixes the issue.

To report an issue:
- Please report errors by opening an issue on [GitHub](https://github.com/grezniczek/redcap_custom_footer/issues) or on the community site (please tag @gunther.rezniczek). 
- Include essential details about your REDCap installation such as **version** and platform (operating system, PHP version).
- If the problem occurs only in conjunction with another external module, please provide its details (you may also want to report the issue to that module's authors).
