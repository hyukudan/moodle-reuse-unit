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
 * Language strings for local_reuseunit (English).
 *
 * @package    local_reuseunit
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Reuse Unit';
$string['plugindescription'] = 'Import sections/units from other courses with all their activities and resources.';

// Capabilities.
$string['reuseunit:import'] = 'Import units from other courses';
$string['reuseunit:export'] = 'Allow units to be exported/shared';
$string['reuseunit:managetemplates'] = 'Manage global unit templates';

// Navigation and actions.
$string['importunit'] = 'Import unit';
$string['importunithere'] = 'Import unit here';
$string['importfromcourse'] = 'Import from another course';

// Wizard steps.
$string['step1_title'] = 'Select source';
$string['step1_description'] = 'Choose the course and section you want to import.';
$string['step2_title'] = 'Preview & options';
$string['step2_description'] = 'Review the content and configure import options.';
$string['step3_title'] = 'Select destination';
$string['step3_description'] = 'Choose where to place the imported section.';

// Source selection.
$string['sourcecourse'] = 'Source course';
$string['selectsourcecourse'] = 'Select source course';
$string['searchcourse'] = 'Search course...';
$string['searchcourseplaceholder'] = 'Type to search courses...';
$string['nocoursesfound'] = 'No courses found';
$string['selectsection'] = 'Select section';
$string['availablesections'] = 'Available sections';
$string['nosections'] = 'This course has no sections';
$string['sectioncontent'] = '{$a->activities} activities, {$a->resources} resources';

// Preview.
$string['preview'] = 'Preview';
$string['contenttobeimported'] = 'Content to be imported';
$string['activities'] = 'activities';
$string['resources'] = 'resources';
$string['activity'] = 'activity';
$string['resource'] = 'resource';
$string['emptysection'] = 'This section is empty';

// Options.
$string['importoptions'] = 'Import options';
$string['options'] = 'Options';
$string['resetdates'] = 'Reset dates';
$string['resetdates_desc'] = 'Reset all activity dates relative to the new course start date.';
$string['includerestrictions'] = 'Include access restrictions';
$string['includerestrictions_desc'] = 'Import access restrictions configured in activities.';
$string['includegradebook'] = 'Include gradebook structure';
$string['includegradebook_desc'] = 'Import gradebook categories and settings (not student grades).';
$string['includegroups'] = 'Include group restrictions';
$string['includegroups_desc'] = 'Import group-based restrictions (groups must exist in destination).';

// Destination selection.
$string['destinationcourse'] = 'Destination course';
$string['selectdestination'] = 'Select destination';
$string['currentcourse'] = 'Current course';
$string['insertposition'] = 'Insert position';
$string['position_start'] = 'At the beginning of the course';
$string['position_end'] = 'At the end of the course';
$string['position_after'] = 'After section:';
$string['newsectionname'] = 'New section name (optional)';
$string['keepsectionname'] = 'Keep original name';

// Import process.
$string['import'] = 'Import';
$string['importing'] = 'Importing...';
$string['importprogress'] = 'Import progress';
$string['preparingimport'] = 'Preparing import...';
$string['creatingbackup'] = 'Creating backup of source section...';
$string['restoringcontent'] = 'Restoring content to destination...';
$string['finalizingimport'] = 'Finalizing import...';

// Results.
$string['importsuccessful'] = 'Import successful';
$string['importcompleted'] = 'The section has been imported successfully.';
$string['importedactivities'] = '{$a} activities imported';
$string['importedresources'] = '{$a} resources imported';
$string['viewimportedsection'] = 'View imported section';
$string['importanother'] = 'Import another unit';

// Errors.
$string['importfailed'] = 'Import failed';
$string['error_nocourseaccess'] = 'You don\'t have access to this course.';
$string['error_nosectionaccess'] = 'You don\'t have access to this section.';
$string['error_sectionnotfound'] = 'Section not found.';
$string['error_coursenotfound'] = 'Course not found.';
$string['error_backupfailed'] = 'Failed to create backup of source section.';
$string['error_restorefailed'] = 'Failed to restore content to destination.';
$string['error_nopermission'] = 'You don\'t have permission to perform this action.';
$string['error_invalidsection'] = 'Invalid section selected.';
$string['error_invalidcourse'] = 'Invalid course selected.';

// Confirmation.
$string['confirmmimport'] = 'Confirm import';
$string['confirmimportmessage'] = 'You are about to import the following content:';
$string['importwarning'] = 'This action cannot be undone. Make sure you have selected the correct section.';

// Settings.
$string['defaultoptions'] = 'Default import options';
$string['defaultoptions_desc'] = 'Configure the default options for importing units.';
$string['maxsections'] = 'Maximum sections';
$string['maxsections_desc'] = 'Maximum number of sections that can be imported at once.';

// History.
$string['importhistory'] = 'Import history';
$string['recentimports'] = 'Recent imports';
$string['nohistory'] = 'No import history';
$string['importedon'] = 'Imported on {$a}';
$string['importedby'] = 'Imported by {$a}';

// Favorites.
$string['favorites'] = 'Favorites';
$string['addtofavorites'] = 'Add to favorites';
$string['removefromfavorites'] = 'Remove from favorites';
$string['nofavorites'] = 'No favorite templates';
$string['favoriteadded'] = 'Added to favorites';
$string['favoriteremoved'] = 'Removed from favorites';

// Navigation.
$string['next'] = 'Next';
$string['previous'] = 'Previous';
$string['cancel'] = 'Cancel';
$string['close'] = 'Close';
$string['back'] = 'Back';

// Misc.
$string['loading'] = 'Loading...';
$string['section'] = 'Section';
$string['course'] = 'Course';
$string['category'] = 'Category';

// Templates.
$string['templates'] = 'Templates';
$string['searchtemplates'] = 'Search templates...';
$string['notemplates'] = 'No templates available';
$string['saveastemplate'] = 'Save as template';
$string['savetemplate'] = 'Save template';
$string['templatesaved'] = 'Template saved successfully';
$string['templatedeleted'] = 'Template deleted successfully';
$string['templatename'] = 'Template name';
$string['templatedescription'] = 'Description';
$string['templatedescription_placeholder'] = 'Describe what this template contains...';
$string['templatetags'] = 'Tags';
$string['templatetags_placeholder'] = 'math, algebra, beginner';
$string['templatetags_help'] = 'Separate tags with commas';

// Share levels.
$string['sharelevel'] = 'Share with';
$string['sharelevel_personal'] = 'Only me';
$string['sharelevel_personal_desc'] = 'Only you can see and use this template';
$string['sharelevel_category'] = 'My department';
$string['sharelevel_category_desc'] = 'Teachers in the same category can use this template';
$string['sharelevel_global'] = 'Entire institution';
$string['sharelevel_global_desc'] = 'All teachers in the site can use this template';

// Template management.
$string['managetemplates'] = 'Manage templates';
$string['mytemplates'] = 'My templates';
$string['sharedtemplates'] = 'Shared templates';
$string['globaltemplates'] = 'Global templates';
$string['templateusage'] = 'Used {$a} times';
$string['updatetemplate'] = 'Update template';
$string['deletetemplate'] = 'Delete template';
$string['confirmdeletetemplate'] = 'Are you sure you want to delete this template?';

// Duplicate section.
$string['duplicatesection'] = 'Duplicate section';
$string['duplicating'] = 'Duplicating...';
$string['duplicatesuccessful'] = 'Section duplicated successfully';
$string['duplicateposition'] = 'Placement';
$string['position_after_source'] = 'After original section';
$string['copy'] = 'copy';

// Export section.
$string['exportsection'] = 'Export section';
$string['exportasmbz'] = 'Export as .mbz';
$string['exporting'] = 'Exporting...';
$string['exportsuccessful'] = 'Export successful';
$string['downloadbackup'] = 'Download backup';
$string['exportfilename'] = 'Export filename';
$string['exportfilename_help'] = 'Leave empty to auto-generate filename';

// Search sections.
$string['searchsections'] = 'Search sections';
$string['searchbyactivity'] = 'Search by activity type';
$string['activitytypes'] = 'Activity types';
$string['filterbyactivity'] = 'Filter by activity type';
$string['selectactivitytypes'] = 'Select activity types...';
$string['allactivities'] = 'All activities';
$string['sectionsmatching'] = '{$a} sections found';
$string['nosectionsfound'] = 'No sections match your criteria';
$string['searchplaceholder'] = 'Search by section name...';
$string['advancedsearch'] = 'Advanced search';
$string['clearfilters'] = 'Clear filters';

// Batch import.
$string['batchimport'] = 'Batch import';
$string['batchimportdesc'] = 'Import multiple sections at once';
$string['selectmultiple'] = 'Select multiple sections';
$string['selectedsections'] = 'Selected sections';
$string['nosectionsselected'] = 'No sections selected';
$string['batchimporting'] = 'Importing sections...';
$string['batchimportcomplete'] = '{$a->success} of {$a->total} sections imported successfully';
$string['batchimportfailed'] = 'Some imports failed. Check individual results.';
$string['addtoqueue'] = 'Add to import queue';
$string['removefromqueue'] = 'Remove from queue';
$string['clearqueue'] = 'Clear all';
$string['importqueue'] = 'Import queue';
$string['queueitems'] = '{$a} items in queue';
$string['error_toomanyimports'] = 'Maximum {$a} sections can be imported at once';
$string['processingitem'] = 'Processing {$a->current} of {$a->total}...';

// Template versioning.
$string['versions'] = 'Versions';
$string['versionhistory'] = 'Version history';
$string['currentversion'] = 'Current version';
$string['version'] = 'Version {$a}';
$string['versioncreated'] = 'Version {$a} created successfully';
$string['createversion'] = 'Create new version';
$string['updatetemplate'] = 'Update template';
$string['changelog'] = 'Change description';
$string['changelog_help'] = 'Describe what changed in this version';
$string['noversions'] = 'No version history available';
$string['viewversions'] = 'View versions';
$string['restoreversion'] = 'Restore this version';
$string['confirmrestore'] = 'Are you sure you want to restore version {$a}?';
$string['versionrestored'] = 'Version restored successfully';
$string['compareversions'] = 'Compare versions';

// Notifications.
$string['messageprovider:importcompleted'] = 'Import completed notification';
$string['messageprovider:templateshared'] = 'Template shared notification';
$string['messageprovider:templateapprovalneeded'] = 'Template approval needed notification';
$string['messageprovider:templateapproved'] = 'Template approved notification';
$string['messageprovider:templaterejected'] = 'Template rejected notification';
$string['messageprovider:templateupdated'] = 'Template updated notification';
$string['messageprovider:scheduledimportcompleted'] = 'Scheduled import completed notification';

$string['notification_importcompleted_subject'] = 'Section import completed';
$string['notification_importcompleted_message'] = 'The section "{$a->sectionname}" has been successfully imported to the course "{$a->coursename}". {$a->activities} activities and {$a->resources} resources were imported.';

$string['notification_templateshared_subject'] = 'A template has been shared with you';
$string['notification_templateshared_message'] = 'The template "{$a->templatename}" has been shared with you by {$a->sharername}.';

$string['notification_approvalneeded_subject'] = 'Template approval required';
$string['notification_approvalneeded_message'] = 'The template "{$a->templatename}" submitted by {$a->submittername} requires your approval to be published globally.';

$string['notification_templateapproved_subject'] = 'Your template has been approved';
$string['notification_templateapproved_message'] = 'Your template "{$a->templatename}" has been approved and is now available globally.';

$string['notification_templaterejected_subject'] = 'Your template has been rejected';
$string['notification_templaterejected_message'] = 'Your template "{$a->templatename}" has been rejected. Reason: {$a->reason}';

$string['notification_templateupdated_subject'] = 'Template has been updated';
$string['notification_templateupdated_message'] = 'The template "{$a->templatename}" has been updated to version {$a->version}.';

$string['notification_scheduledimport_subject'] = 'Scheduled import completed';
$string['notification_scheduledimport_message'] = 'The scheduled import to course "{$a->coursename}" has completed. {$a->sectionsimported} sections were imported. Status: {$a->status}';

$string['viewcourse'] = 'View course';
$string['viewtemplates'] = 'View templates';
$string['reviewtemplate'] = 'Review template';
$string['noreasonprovided'] = 'No reason provided';

// Dashboard.
$string['dashboard'] = 'Dashboard';
$string['usagestatistics'] = 'Usage Statistics';
$string['periodfilter'] = 'Period filter';
$string['alltime'] = 'All time';
$string['lastmonth'] = 'Last month';
$string['lastweek'] = 'Last week';
$string['totalimports'] = 'Total imports';
$string['successfulimports'] = 'Successful imports';
$string['activitiesimported_stat'] = 'Activities imported';
$string['resourcesimported_stat'] = 'Resources imported';
$string['totaltemplates'] = 'Total templates';
$string['templateusagestat'] = 'Template usages';
$string['toptemplates'] = 'Top templates';
$string['importsbycourse'] = 'Imports by course';
$string['importsbyactivity'] = 'Imports by activity type';
$string['viewallusers'] = 'View all users';
$string['nodata'] = 'No data available';
$string['content'] = 'Content';
$string['when'] = 'When';
$string['justnow'] = 'Just now';
$string['minutesago'] = '{$a} minutes ago';
$string['hoursago'] = '{$a} hours ago';
$string['daysago'] = '{$a} days ago';

// Approval workflow.
$string['templateapproval'] = 'Template Approval';
$string['pendingtemplates'] = 'Pending templates';
$string['refresh'] = 'Refresh';
$string['submittedby'] = 'Submitted by';
$string['source'] = 'Source';
$string['submittedon'] = 'Submitted on';
$string['actions'] = 'Actions';
$string['approve'] = 'Approve';
$string['reject'] = 'Reject';
$string['nopendingapprovals'] = 'No pending template approvals';
$string['rejecttemplate'] = 'Reject template';
$string['rejectionreason'] = 'Rejection reason';
$string['rejectionreason_placeholder'] = 'Explain why this template is being rejected...';
$string['rejectionreason_help'] = 'This reason will be sent to the template owner.';
$string['confirmapproval'] = 'Confirm approval';
$string['confirmapprovalmsg'] = 'Are you sure you want to approve this template? It will become available globally.';
$string['submittedforapproval'] = 'Template submitted for approval';
$string['templateapprovedmsg'] = 'Template has been approved successfully';
$string['templaterejectedmsg'] = 'Template has been rejected';
$string['submitforglobal'] = 'Submit for global approval';
$string['error_alreadyapproved'] = 'This template is already approved';
$string['error_notpending'] = 'This template is not pending approval';
$string['pendingapproval'] = 'Pending approval';
$string['approved'] = 'Approved';
$string['rejected'] = 'Rejected';

// Section synchronization.
$string['syncsection'] = 'Sync section';
$string['linksection'] = 'Link to template';
$string['unlinksection'] = 'Unlink from template';
$string['linkedsections'] = 'Linked sections';
$string['sectionlinked'] = 'Section linked to template successfully';
$string['sectionunlinked'] = 'Section unlinked from template';
$string['synccompleted'] = 'Synchronization completed successfully';
$string['syncfailed'] = 'Synchronization failed';
$string['autosync'] = 'Auto-sync';
$string['autosync_desc'] = 'Automatically sync when template is updated';
$string['manualonly'] = 'Manual only';
$string['syncnow'] = 'Sync now';
$string['updateavailable'] = 'Update available';
$string['noupdateavailable'] = 'Up to date';
$string['lastsynced'] = 'Last synced';
$string['syncmode'] = 'Sync mode';
$string['syncmode_replace'] = 'Replace (delete existing and import)';
$string['syncmode_merge'] = 'Merge (keep existing and add new)';
$string['confirmsync'] = 'Confirm sync';
$string['confirmsyncmsg'] = 'This will update the section content from the template. Continue?';
$string['keeplinked'] = 'Keep linked for future updates';
$string['keeplinked_desc'] = 'Maintain a link to receive notifications when the template is updated';
$string['nolinkedsections'] = 'No linked sections in this course';
$string['linkedto'] = 'Linked to: {$a}';
$string['error_sourcenotfound'] = 'Source template content not found';

// Scheduled imports.
$string['scheduleimport'] = 'Schedule import';
$string['scheduledimports'] = 'Scheduled imports';
$string['importscheduled'] = 'Import scheduled successfully';
$string['schedulecancelled'] = 'Scheduled import cancelled';
$string['schedulefor'] = 'Schedule for';
$string['importnow'] = 'Import now';
$string['importlater'] = 'Import later';
$string['scheduledtime'] = 'Scheduled time';
$string['noscheduledimports'] = 'No scheduled imports';
$string['status_pending'] = 'Pending';
$string['status_running'] = 'Running';
$string['status_completed'] = 'Completed';
$string['status_completedwitherrors'] = 'Completed with errors';
$string['status_cancelled'] = 'Cancelled';
$string['completed'] = 'Completed';
$string['completedwitherrors'] = 'Completed with errors';
$string['cancelschedule'] = 'Cancel';
$string['viewdetails'] = 'View details';
$string['error_pasttime'] = 'Scheduled time cannot be in the past';
$string['error_cannotcancel'] = 'This scheduled import cannot be cancelled';
$string['task_scheduledimport'] = 'Process scheduled section import';

// Diff comparison.
$string['compare'] = 'Compare';
$string['comparesections'] = 'Compare sections';
$string['compareversions'] = 'Compare versions';
$string['comparison'] = 'Comparison';
$string['differences'] = 'Differences';
$string['nodifferences'] = 'No differences found';
$string['added'] = 'Added';
$string['removed'] = 'Removed';
$string['modified'] = 'Modified';
$string['unchanged'] = 'Unchanged';
$string['itemsadded'] = '{$a} items will be added';
$string['itemsremoved'] = '{$a} items will be removed';
$string['itemsmodified'] = '{$a} items modified';
$string['itemsunchanged'] = '{$a} items unchanged';
$string['previewchanges'] = 'Preview changes';
$string['selectversions'] = 'Select versions to compare';
$string['olderversion'] = 'Older version';
$string['newerversion'] = 'Newer version';
$string['changesummary'] = 'Change summary';
$string['increased'] = 'Increased';
$string['decreased'] = 'Decreased';
$string['changed'] = 'Changed';

// Privacy API.
$string['privacy:metadata:templates'] = 'Information about templates created by users.';
$string['privacy:metadata:templates:userid'] = 'The ID of the user who created the template.';
$string['privacy:metadata:templates:name'] = 'The name of the template.';
$string['privacy:metadata:templates:description'] = 'The description of the template.';
$string['privacy:metadata:templates:tags'] = 'Tags associated with the template.';
$string['privacy:metadata:templates:timecreated'] = 'When the template was created.';
$string['privacy:metadata:templates:timemodified'] = 'When the template was last modified.';

$string['privacy:metadata:history'] = 'A record of section imports performed by users.';
$string['privacy:metadata:history:userid'] = 'The ID of the user who performed the import.';
$string['privacy:metadata:history:source_courseid'] = 'The source course ID.';
$string['privacy:metadata:history:source_sectionid'] = 'The source section ID.';
$string['privacy:metadata:history:target_courseid'] = 'The target course ID.';
$string['privacy:metadata:history:target_sectionid'] = 'The target section ID.';
$string['privacy:metadata:history:timecreated'] = 'When the import was performed.';

$string['privacy:metadata:favorites'] = 'Information about user\'s favorite templates.';
$string['privacy:metadata:favorites:userid'] = 'The ID of the user who added the favorite.';
$string['privacy:metadata:favorites:templateid'] = 'The ID of the favorited template.';
$string['privacy:metadata:favorites:timecreated'] = 'When the template was added to favorites.';

$string['privacy:metadata:scheduled'] = 'Information about scheduled imports.';
$string['privacy:metadata:scheduled:userid'] = 'The ID of the user who scheduled the import.';
$string['privacy:metadata:scheduled:scheduled_time'] = 'When the import is scheduled to run.';
$string['privacy:metadata:scheduled:status'] = 'The status of the scheduled import.';
$string['privacy:metadata:scheduled:timecreated'] = 'When the scheduled import was created.';

$string['privacy:metadata:links'] = 'Information about linked sections for synchronization.';
$string['privacy:metadata:links:userid'] = 'The ID of the user who created the link.';
$string['privacy:metadata:links:templateid'] = 'The ID of the linked template.';
$string['privacy:metadata:links:timecreated'] = 'When the link was created.';

// Settings.
$string['generalsettings'] = 'General settings';
$string['enabled'] = 'Enable plugin';
$string['enabled_desc'] = 'Enable or disable the Reuse Unit plugin functionality.';
$string['showincourse'] = 'Show in course menu';
$string['showincourse_desc'] = 'Display the import unit option in the course menu.';
$string['limits'] = 'Limits';
$string['maxtemplates'] = 'Maximum templates per user';
$string['maxtemplates_desc'] = 'Maximum number of templates a user can create.';
$string['historyretention'] = 'History retention (days)';
$string['historyretention_desc'] = 'Number of days to keep import history records.';
$string['templatesettings'] = 'Template settings';
$string['allowglobal'] = 'Allow global templates';
$string['allowglobal_desc'] = 'Allow users to share templates globally across the site.';
$string['requireapproval'] = 'Require approval for global templates';
$string['requireapproval_desc'] = 'Require administrator approval before templates become globally available.';
$string['allowcategoryshare'] = 'Allow category sharing';
$string['allowcategoryshare_desc'] = 'Allow users to share templates within their course category.';
$string['syncsettings'] = 'Synchronization settings';
$string['allowautosync'] = 'Allow auto-sync';
$string['allowautosync_desc'] = 'Allow sections to be automatically synchronized when templates are updated.';
$string['defaultsyncmode'] = 'Default sync mode';
$string['defaultsyncmode_desc'] = 'Default mode for synchronizing sections with templates.';
$string['notificationsettings'] = 'Notification settings';
$string['notifyimport'] = 'Notify on import completion';
$string['notifyimport_desc'] = 'Send a notification when an import is completed.';
$string['notifytemplate'] = 'Notify on template updates';
$string['notifytemplate_desc'] = 'Send notifications when templates are shared or updated.';
$string['cleanupsettings'] = 'Cleanup settings';
$string['autocleanup'] = 'Auto-cleanup old data';
$string['autocleanup_desc'] = 'Automatically clean up old scheduled imports and history records.';
$string['cleanupage'] = 'Cleanup age (days)';
$string['cleanupage_desc'] = 'Age in days after which completed scheduled imports are cleaned up.';
