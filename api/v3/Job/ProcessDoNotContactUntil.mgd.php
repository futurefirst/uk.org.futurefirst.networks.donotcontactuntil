<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'Cron:Job.ProcessDoNotContactUntil',
    'entity' => 'Job',
    'params' => 
    array (
      'version' => 3,
      'name' => 'Process Do Not Contact Until preferences',
      'description' => 'If any Do Not Contact Until preferences have expired, allow contacting them again.',
      'run_frequency' => 'Daily',
      'api_entity' => 'Job',
      'api_action' => 'ProcessDoNotContactUntil',
      'parameters' => '',
    ),
  ),
);
