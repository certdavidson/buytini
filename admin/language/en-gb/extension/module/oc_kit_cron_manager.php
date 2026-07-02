<?php
// Cron Manager | © 2026 oc-kit.com | https://oc-kit.com

$_['heading_title']        = 'oc-kit.com — Cron Manager';
$_['heading_title_simple'] = 'Cron Manager';
$_['text_module_name']     = 'Cron Manager';
$_['text_home']            = 'Home';
$_['text_extension']       = 'Extensions';
$_['text_success']         = 'Job saved.';
$_['text_success_delete']  = 'Job deleted.';

$_['text_no_jobs']         = 'No jobs yet. Click "Add Job" to create one.';
$_['column_name']          = 'Name';
$_['column_type']          = 'Type';
$_['column_command']       = 'Command / URL';
$_['column_schedule']      = 'Schedule';
$_['column_last_run']      = 'Last Run';
$_['column_next_run']      = 'Next Run';
$_['column_status']        = 'Status';
$_['column_enabled']       = 'Enabled';
$_['column_actions']       = 'Actions';
$_['column_date']          = 'Date';
$_['column_duration']      = 'Duration';
$_['column_output']        = 'Output';
$_['column_triggered_by']  = 'Triggered By';

$_['text_type_php']        = 'PHP';
$_['text_type_shell']      = 'Shell';
$_['text_type_url']        = 'URL';

$_['text_status_never']    = 'Never run';
$_['text_status_success']  = 'Success';
$_['text_status_error']    = 'Error';
$_['text_status_running']  = 'Running';

$_['text_triggered_scheduler'] = 'Scheduler';
$_['text_triggered_manual']    = 'Manual';

$_['entry_name']           = 'Job Name';
$_['entry_description']    = 'Description';
$_['entry_type']           = 'Type';
$_['entry_command']        = 'Command';
$_['entry_schedule']       = 'Schedule (cron)';
$_['entry_timeout']        = 'Timeout (sec)';
$_['entry_status']         = 'Enabled';
$_['help_schedule']        = 'Format: min hour dom month dow  |  Example: <code>0 2 * * *</code> — daily at 2:00';
$_['help_command_php']     = 'Absolute path to PHP file, e.g.: <code>/var/www/site/crons/cron_notify.php</code>';
$_['help_command_shell']   = 'Shell command, e.g.: <code>/bin/bash /path/to/script.sh</code>';
$_['help_command_url']     = 'URL for GET request, e.g.: <code>https://site.com/cron?token=xxx</code>';

$_['button_add']           = 'Add Job';
$_['button_scan']          = 'Scan /crons/';
$_['button_save']          = 'Save';
$_['button_cancel']        = 'Cancel';
$_['button_run']           = 'Run Now';
$_['button_logs']          = 'Logs';
$_['button_edit']          = 'Edit';
$_['button_delete']        = 'Delete';
$_['button_clear_logs']    = 'Clear Logs';

$_['text_running']         = 'Running…';
$_['text_run_output']      = 'Run Output';
$_['text_no_logs']         = 'Log is empty.';
$_['text_loading']         = 'Loading…';
$_['text_ms']              = 'ms';
$_['text_sec']             = 'sec';

$_['text_scan_title']      = 'Discovered Files';
$_['text_scan_none']       = 'No new files found in /crons/.';

$_['text_schedule_next']   = 'Next run:';
$_['text_schedule_invalid']= 'Invalid cron expression';

$_['text_confirm_delete']  = 'Delete this job?';
$_['text_confirm_run']     = 'Run this job now?';

$_['text_cron_setup']      = 'System cron entry (run every minute):';

$_['error_permission']       = 'You do not have permission to manage Cron Manager!';
$_['error_name_required']    = 'Please enter a job name.';
$_['error_command_required'] = 'Please enter a command or URL.';
$_['error_schedule_invalid'] = 'Invalid cron expression format.';
