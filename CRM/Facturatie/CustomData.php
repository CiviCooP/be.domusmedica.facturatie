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
  private $_creditDatumCustomField = array();
  private $_contributionDataTitle = NULL;

  /**
   * CRM_Facturatie_CustomData constructor.
   */
  public function __construct() {
    $this->_contributionDataTitle = 'Domus Medica factuurgegevens';
    $this->contributionDataExists();
    $this->creditDatumExists();
    $this->factuurDatumExists();
  }

  /**
   * Method to process custom data immediately after extension installation
   */
  public static function enable() {
    $customData = new CRM_Facturatie_CustomData();
    // check if custom group for contribution data already exists and if so change title,  create if not
    if ($customData->contributionDataExists() == TRUE) {
      $customData->changeTitleContributionData();
    } else {
      $customData->createContributionData();
    }
    // check if custom field factuurdatum already exists and create if not
    if ($customData->factuurDatumExists() == FALSE) {
      $customData->createFactuurDatum();
    }
    // check if creditdatum already exists and create if not
    if ($customData->creditDatumExists() == FALSE) {
      $customData->createCreditDatum();
    }
  }


  /**
   * Method to update all custom fields in this extension to is_active = 0 immediately after extension disable
   */
  public static function disable() {
    $customData = new CRM_Facturatie_CustomData();
    try {
      civicrm_api3('CustomField', 'create', array(
        'id' => $customData->_creditDatumCustomField['id'],
        'is_active' => 0,
        'data_type' => 'Date',
        'html_type' => 'Select Date',
      ));
      civicrm_api3('CustomField', 'create', array(
        'id' => $customData->_factuurDatumCustomField['id'],
        'data_type' => 'Date',
        'html_type' => 'Select Date',
        'is_active' => 0,
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
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
          // make sure that it is active!
          civicrm_api3('CustomGroup', 'create', array(
            'id' => $this->_contributionDataCustomGroup['id'],
            'extends' => 'Contribution',
            'is_active' => 1,
          ));
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
          'extends' => 'Contribution',
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }

  }

  /**
   * Method to check if the factuurdatum custom field exists
   *
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
          // make sure it is active!
          civicrm_api3('CustomField', 'create', array(
            'id' => $this->_factuurDatumCustomField['id'],
            'is_active' => 1,
            'data_type' => 'Date',
            'html_type' => 'Select Date',
          ));

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

  /**
   * Method to check if the creditdatum custom field exists
   *
   * @return bool
   */
  public function creditDatumExists() {
    try {
      $creditDatumParams = array(
        'custom_group_id' => $this->_contributionDataCustomGroup['id'],
        'name' => 'domus_creditdatum',
      );
      $count = civicrm_api3('CustomField', 'getcount', $creditDatumParams);
      switch ($count) {
        case 0:
          return FALSE;
          break;
        case 1:
          $this->_creditDatumCustomField = civicrm_api3('CustomField', 'getsingle', $creditDatumParams);
          // make sure it is active!
          civicrm_api3('CustomField', 'create', array(
            'id' => $this->_creditDatumCustomField['id'],
            'is_active' => 1,
            'data_type' => 'Date',
            'html_type' => 'Select Date',
          ));

          return TRUE;
          break;
        default:
          CRM_Core_Error::createError(ts('Could not find one single custom field with name domus_creditdatum in custom group domus_contribution_data, contact your system administrator. (extension be.domusmedica.facturatie)'));
          break;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
    return FALSE;
  }

  /**
   * Method to create custom field factuurdatum
   */
  public function createFactuurDatum() {
    try {
      $created = civicrm_api3('CustomField', 'create', array(
        'custom_group_id' => $this->_contributionDataCustomGroup['id'],
        'name' => 'domus_factuurdatum',
        'column_name' => 'domus_factuurdatum',
        'label' => 'Factuurdatum',
        'data_type' => 'Date',
        'html_type' => 'Select Date',
        'is_active' => 1,
        'is_searchable' => 1,
        'is_search_range' => 1,
        'is_view' => 1,
        'start_date_years' => 1,
        'end_date_years' => 1,
      ));
      $this->_factuurDatumCustomField = $created['values'][$created['id']];
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::createError(ts('Could not create custom field domus_factuurdatum in custom group domus_contribution_data, contact your system administrator. (extension be.domusmedica.facturatie, error from API CustomField create: '.$ex->getMessage()));
    }
  }

  /**
   * Method to create custom field creditdatum
   */
  public function createCreditDatum() {
    try {
      $created = civicrm_api3('CustomField', 'create', array(
        'custom_group_id' => $this->_contributionDataCustomGroup['id'],
        'name' => 'domus_creditdatum',
        'column_name' => 'domus_creditdatum',
        'label' => 'Creditnota datum',
        'data_type' => 'Date',
        'html_type' => 'Select Date',
        'is_active' => 1,
        'is_searchable' => 1,
        'is_search_range' => 1,
        'is_view' => 1,
        'start_date_years' => 1,
        'end_date_years' => 1,
      ));
      $this->_creditDatumCustomField = $created['values'][$created['id']];
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::createError(ts('Could not create custom field domus_creditdatum in custom group domus_contribution_data, contact your system administrator. (extension be.domusmedica.facturatie, error from API CustomField create: '.$ex->getMessage()));
    }
  }

}