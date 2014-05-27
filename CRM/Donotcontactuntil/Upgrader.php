<?php

/**
 * Collection of upgrade steps
 */
class CRM_Donotcontactuntil_Upgrader extends CRM_Donotcontactuntil_Upgrader_Base {

  const GROUP_NAME = 'Do_Not_Contact_Until';

  private static function getgroupbyname($name) {
    $result = civicrm_api('CustomGroup', 'get', array(
      'version'    => 3,
      'sequential' => 1,
      'name'       => $name,
    ));
    if (civicrm_error($result)) {
      return FALSE;
    } else {
      return $result['values'][0];
    }
  }

  private static function getfieldsbygroupid($custom_group_id) {
    $result = civicrm_api('CustomField', 'get', array(
      'version'         => 3,
      'sequential'      => 1,
      'custom_group_id' => $custom_group_id,
    ));
    if (civicrm_error($result)) {
      return FALSE;
    } else {
      return $result['values'];
    }
  }

  // Currently commented out due to a possible bug
  // http://forum.civicrm.org/index.php/topic,32770.0.html
  /*
  private static function deletefieldbyid($id) {
    $result = civicrm_api('CustomField', 'delete', array(
      'version'    => 3,
      'sequential' => 1,
      'id'         => $id,
    ));
    CRM_Core_Error::debug_log_message('Do_Not_Contact_Until uninstaller, CustomField delete result: ' . print_r($result, TRUE), FALSE);
    return !civicrm_error($result);
  }
  */

  // This is a hack while the above is out of action.
  private static function deletefieldmanually($cgid, $cgTable, $column_name) {
    $cgid        = CRM_Core_DAO::escapeString($cgid);
    $cgTable     = CRM_Core_DAO::escapeString($cgTable);
    $column_name = CRM_Core_DAO::escapeString($column_name);

$sql = <<<EOT
DELETE FROM `civicrm_custom_field`
      WHERE `custom_group_id` = '$cgid'
        AND `column_name`     = '$column_name';
EOT;
    CRM_Core_DAO::executeQuery($sql);

$sql = <<<EOT
ALTER TABLE `$cgTable`
       DROP `$column_name`;
EOT;
    CRM_Core_DAO::executeQuery($sql);
  }

  private static function deletegroupbyid($id) {
    $result = civicrm_api('CustomGroup', 'delete', array(
      'version'    => 3,
      'sequential' => 1,
      'id'         => $id,
    ));
    return !civicrm_error($result);
  }

  /**
   * Remove our custom fields and custom field group when the extension is uninstalled
   */
  public function uninstall() {
    // Get the group's ID number
    $group = self::getgroupbyname(self::GROUP_NAME);
    if (!$group) {
      throw new CRM_Extension_Exception(
        "Do Not Contact Until: Failed to retrieve ID of custom group during uninstall.",
        0,
        array()
      );
    }
    $cgid = $group['id'];
    $cgTable = $group['table_name'];

    // Get the fields' ID numbers
    $fields = self::getfieldsbygroupid($cgid);
    if (!$fields) {
      throw new CRM_Extension_Exception(
        "Do Not Contact Until: Failed to retrieve IDs of custom fields during uninstall.",
        0,
        array()
      );
    }

    // Delete each custom field in the group
    foreach ($fields as $field) {
      /*if (!self::deletefieldbyid($field['id'])) {
        throw new CRM_Extension_Exception(
          "Do Not Contact Until: Failed to delete custom field {$field['name']} during uninstall.",
          0,
          array()
        );
      }*/
      self::deletefieldmanually($cgid, $cgTable, $field['column_name']);
    }

    // Delete the custom group
    if (!self::deletegroupbyid($cgid)) {
      throw new CRM_Extension_Exception(
        "Do Not Contact Until: Failed to delete custom group during uninstall.",
        0,
        array()
      );
    }

    // Delete the logging table. The API call to delete the group doesn't do this,
    // causing a fatal DB Error if the extension is then reinstalled.
    CRM_Core_DAO::executeQuery(
      "DROP TABLE IF EXISTS `log_" . CRM_Core_DAO::escapeString($cgTable) . "`;"
    );
  }
}
