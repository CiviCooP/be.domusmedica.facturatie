<?php
/**
 * Class for Domus Medica Facturatie to manage custom data
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 14 Jan 2018
 * @license AGPL-3.0
 */

class CRM_Facturatie_CustomData {
  private $_contributionDataCustomGroup = array();
  private $_factuurDatumCustomField = array();
  private $_contributionDataTitle = NULL;

  /**
   * CRM_Facturatie_CustomData constructor.
   */
  public function __construct() {
    $this->_contributionDataTitle = 'Domus Medica factuurgegevens';
  }

  /**
   * Method to check if the custom group domus_contribution_data exists
   *
   * @return bool
   */
  public function contributionDataExists() {
    try {
      $customGroupParams = array(
        'extends' => 'Contribution',
        'name' => 'domus_contribution_data',
      );
      $count = civicrm_api3('CustomGroup', 'getcount', $customGroupParams);
      switch ($count) {
        case 0:
          return FALSE;
          break;
        case 1:
          $this->_contributionDataCustomGroup = civicrm_api3('CustomGroup', 'getsingle', $customGroupParams);
          return TRUE;
          break;
        default:
          CRM_Core_Error::createError(ts('Could not find one single custom group with name domus_contribution_data extending Contribution, contact your system administrator. (extension be.domusmedica.facturatie)'));
          break;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
    return FALSE;
  }

  /**
   * Getter for contribution data custom group
   *
   * @param $key
   * @return array|mixed
   */
  public function getContributionData($key) {
    if (!empty($key) && isset($this->_contributionDataCustomGroup[$key])) {
      return $this->_contributionDataCustomGroup[$key];
    } else {
      return $this->_contributionDataCustomGroup;
    }
  }

  /**
   * Method to create the domus_contribution_data custom group
   */
  public function createContributionData() {
    try {
      $created = civicrm_api3('CustomGroup', 'create', array(
        'name' => 'domus_contribution_data',
        'extends' => 'Contribution',
        'table_name' => 'civicrm_value_domus_contribution_data',
        'title' => $this->_contributionDataTitle,
        'is_active' => 1,
        'is_reserved' => 1,
      ));
      $this->_contributionDataCustomGroup = $created['values'][$created['id']];
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::createError(ts('Could not create a custom group with name domus_contribution_data extending Contribution, contact your system administrator. (extension de.domusmedica.facturatie, error from API CustomGroup create: '.$ex->getMessage()));
    }
  }

  /**
   * Method to change the title of the custom group (probably only usefull during install if already exists)
   */
  public function changeTitleContributionData() {
    if (isset($this->_contributionDataCustomGroup['id'])) {
      try {
        civicrm_api3('CustomGroup', 'create', array(
          'id' => $this->_contributionDataCustomGroup['id'],
          'title' => $this->_contributionDataTitle,
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }

  }

  /**
   * Method to
   * @return bool
   */
  public function factuurDatumExists() {
    try {
      $factuurDatumParams = array(
        'custom_group_id' => $this->_contributionDataCustomGroup['id'],
        'name' => 'domus_factuurdatum',
      );
      $count = civicrm_api3('CustomField', 'getcount', $factuurDatumParams);
      switch ($count) {
        case 0:
          return FALSE;
          break;
        case 1:
          $this->_factuurDatumCustomField = civicrm_api3('CustomField', 'getsingle', $factuurDatumParams);
          return TRUE;
          break;
        default:
          CRM_Core_Error::createError(ts('Could not find one single custom field with name domus_factuurdatum in custom group domus_contribution_data, contact your system administrator. (extension be.domusmedica.facturatie)'));
          break;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
    return FALSE;
  }
  public function createFactuurDatum() {

  }


}