<?php
require_once 'CRM/Core/Error.php';

/**
 * Job.ProcessDoNotContactUntil API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_job_processdonotcontactuntil_spec(&$spec) {
  // Job.ProcessDoNotContactUntil does not take any arguments
}

// For a specified Do Not Contact type, clear the date and untick the
// box on any entries expiring today or that should have expired in the past.
function processdonotcontactuntil_runupdatequery($dncuTable, $contactColumn, $dncuColumn) {
  $dncuTable     = CRM_Core_DAO::escapeString($dncuTable);
  $contactColumn = CRM_Core_DAO::escapeString($contactColumn);
  $dncuColumn    = CRM_Core_DAO::escapeString($dncuColumn);

$sql = <<<EOT
    UPDATE `$dncuTable`                       INNER JOIN `civicrm_contact`
        ON `$dncuTable`.`entity_id`           = `civicrm_contact`.`id`
       SET `civicrm_contact`.`$contactColumn` = 0,
           `$dncuTable`.`$dncuColumn`         = NULL
     WHERE `civicrm_contact`.`$contactColumn` = 1
       AND `$dncuTable`.`$dncuColumn`         IS NOT NULL
       AND DATE(`$dncuTable`.`$dncuColumn`)   <= CURDATE();
EOT;

  CRM_Core_DAO::executeQuery($sql);
};

// For a specified Do Not Contact type, clear the date on any entries
// where the corresponding box wasn't ticked.
function processdonotcontactuntil_runclearquery($dncuTable, $contactColumn, $dncuColumn) {
  $dncuTable     = CRM_Core_DAO::escapeString($dncuTable);
  $contactColumn = CRM_Core_DAO::escapeString($contactColumn);
  $dncuColumn    = CRM_Core_DAO::escapeString($dncuColumn);

$sql = <<<EOT
    UPDATE `$dncuTable`                       INNER JOIN `civicrm_contact`
        ON `$dncuTable`.`entity_id`           = `civicrm_contact`.`id`
       SET `$dncuTable`.`$dncuColumn`         = NULL
     WHERE `civicrm_contact`.`$contactColumn` = 0
       AND `$dncuTable`.`$dncuColumn`         IS NOT NULL;
EOT;

  CRM_Core_DAO::executeQuery($sql);
};

/**
 * Job.ProcessDoNotContactUntil API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_processdonotcontactuntil($params) {
  $dncuDebug = FALSE;
  if ($dncuDebug) {
    // NB watchdog is Drupal only, using Civi debug logging instead
    CRM_Core_Error::debug_log_message('Job.ProcessDoNotContactUntil: begin', FALSE);
  }

  // Get details of our custom field group's table
  $params = array('name' => 'Do_Not_Contact_Until');
  $result = array();
  CRM_Core_BAO_CustomGroup::retrieve($params, $result);
  $dncuTable = $result['table_name'];
  $dncuGroupID = $result['id'];
  if (empty($dncuTable) || empty($dncuGroupID)) {
    throw new API_Exception("Job.ProcessDoNotContactUntil failed to get the details of the extension's custom group: result = " . print_r($result, TRUE));
  }

  // Get details of our custom field group's fields
  $columnsToProcess = array();
  $field = new CRM_Core_BAO_CustomField();
  $field->custom_group_id = $dncuGroupID;
  $field->find();
  while ($field->fetch()) {
    $dncuField = strtolower($field->name);
    // Only include fields named like Do_not_<something>_until
    // The column names we look for in civicrm_contact are derived from these
    if (preg_match('/do_not_[a-z]+_until/', $dncuField)) {
      // Column name in civicrm_contact
      $contactColumn = preg_replace('/_until$/', '', $dncuField, 1);
      // Column name in the table for this extension's custom field group
      $dncuColumn = $field->column_name;
      $columnsToProcess[$contactColumn] = $dncuColumn;
    }
  }
  if (empty($columnsToProcess)) {
    throw new API_Exception("Job.ProcessDoNotContactUntil failed to get the details of the extension's custom fields: result = " . print_r($result, TRUE));
  }

  // The query appears to fall foul of something similar to CRM-13587
  // "ERROR 1442 (HY000): Can't update table 'civicrm_contact'
  // in stored function/trigger because it is already used by statement
  // which invoked this stored function/trigger."
  // Temporarily dropping the triggers is how CRM-13587 was fixed in core
  if ($dncuDebug) {
    CRM_Core_Error::debug_log_message("Job.ProcessDoNotContactUntil: dropping triggers on $dncuTable", FALSE);
  }
  CRM_Core_DAO::dropTriggers($dncuTable);

  // For each custom field in the group...
  foreach ($columnsToProcess as $key => $value) {
    if ($dncuDebug) {
      CRM_Core_Error::debug_log_message("Job.ProcessDoNotContactUntil: processing $key", FALSE);
    }
    // Process any expired entries
    processdonotcontactuntil_runupdatequery($dncuTable, $key, $value);
    // Clear any entries that weren't ticked
    processdonotcontactuntil_runclearquery($dncuTable, $key, $value);
  }

  // And restore the triggers we dropped
  if ($dncuDebug) {
    CRM_Core_Error::debug_log_message("Job.ProcessDoNotContactUntil: rebuilding triggers on $dncuTable", FALSE);
  }
  CRM_Core_DAO::triggerRebuild($dncuTable);

  // Successful exit
  // (DB failures on update bail out above with an entry in the scheduled jobs log)
  if ($dncuDebug) {
    CRM_Core_Error::debug_log_message('Job.ProcessDoNotContactUntil: success', FALSE);
  }
  return civicrm_api3_create_success(array(), $params, 'Job', 'ProcessDoNotContactUntil');
}
