<?php
/**
 * Class for Domus Medica Facturatie to manage contribution hooks and methods
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 15 Jan 2018
 * @license AGPL-3.0
 */

class CRM_Facturatie_Contribution {
  /**
   * CRM_Facturatie_Contribution constructor.
   */
  public function __construct() {
  }

  /**
   * Method to process the hook buildForm
   * - bedrag in bijdrage form mag niet gewijzigd worden als faktuurnummer gegenereerd is (en er dus
   *   geëxporteerd is naar Winbooks)
   * - invoice_id en creditnote_id worden gegenereerd en mogen niet bijgewerkt worden in de UI
   *
   * @param string $formName
   * @param object $form
   */
  public function buildForm($formName, &$form) {
    switch ($formName) {
      case 'CRM_Contribute_Form_Contribution':
        $formType = $form->getVar('_formType');
        // factuur- en creditnotanummer kunnen niet in UI gewijzigd
        if ($form->_action == CRM_Core_Action::UPDATE || $form->_action == CRM_Core_Action::ADD) {
          if ($formType == 'AdditionalDetail') {
            $invoiceId = $form->getElement('invoice_id');
            $creditNoteId = $form->getElement('creditnote_id');
            $invoiceId->freeze();
            $creditNoteId->freeze();
          }
        }
        // bedragen niet te wijzigen als er een factuurnummer is
        if ($form->_action == CRM_Core_Action::UPDATE) {
          if (isset($form->_values['invoice_id']) && !empty($form->_values['invoice_id'])) {
            switch ($formType) {
              case 'AdditionalDetail':
                $feeAmount = $form->getElement('fee_amount');
                $netAmount = $form->getElement('net_amount');
                $nonDeductableAmount = $form->getElement('non_deductible_amount');
                $feeAmount->freeze();
                $netAmount->freeze();
                $nonDeductableAmount->freeze();
                break;
              default:
                $totalAmount = $form->getElement('total_amount');
                $currency = $form->getElement('currency');
                $totalAmount->freeze();
                $currency->freeze();
                break;
            }
          }
        }
        break;
    }
  }

  /**
   * Method to implement post hook
   *
   * @param string $op
   * @param string $objectName
   * @param int $objectId
   * @param mixed $objectRef
   */
  public function post($op, $objectName, $objectId, $objectRef) {
    // if contribution and operation is create or edit and I have contribution object
    if ($objectName == 'Contribution') {
      if ($op == 'edit' || $op == 'create') {
        if ($objectRef instanceof CRM_Contribute_BAO_Contribution) {
          // check if we need to set factuurdatum
          if (isset($objectRef->invoice_id)) {
            $this->checkAndSetDate('factuur', $objectId, $objectRef->invoice_id);
          }
          // check if we need to set the creditdatum
          if (isset($objectRef->creditnote_id)) {
            $this->checkAndSetDate('credit', $objectId, $objectRef->creditnote_id);
          }
        }
      }
    }
  }

  /**
   * Method to set the factuurdatum or creditdatum based on type.
   * Date should only be set for the first time that the invoice or creditnote is created, so
   * the check is if the datum has been set yet. If not, set.
   *
   * @param $type
   * @param $contributionId
   * @param $invoiceId
   */
  public function checkAndSetDate($type, $contributionId, $invoiceId) {
    if (!empty($invoiceId)) {
      $customData = new CRM_Facturatie_CustomData();
      $countQuery = 'SELECT COUNT(*) FROM '.$customData->getContributionData('table_name').' WHERE entity_id = %1';
      $existing = CRM_Core_DAO::singleValueQuery($countQuery, array(
        1 => array($contributionId, 'Integer'),
      ));
      switch ($type) {
        case 'credit':
          if ($existing == 0) {
            $query = 'INSERT INTO '.$customData->getContributionData('table_name').' ('.
              $customData->getCreditdatum('column_name').', entity_id) VALUES(%1, %2)';
          } else {
            $query = 'UPDATE ' . $customData->getContributionData('table_name') . ' SET ' .
              $customData->getCreditdatum('column_name') . ' = %1 WHERE entity_id = %2';
          }
          break;
        case 'factuur':
          if ($existing == 0) {
            $query = 'INSERT INTO '.$customData->getContributionData('table_name').' ('.
              $customData->getFactuurdatum('column_name').', entity_id) VALUES(%1, %2)';
          } else {
            $query = 'UPDATE ' . $customData->getContributionData('table_name') . ' SET ' .
              $customData->getFactuurdatum('column_name') . ' = %1 WHERE entity_id = %2';
          }
          break;
        default:
          return;
          break;
      }
      $saveDate = new DateTime();
      $queryParms = array(
        1 => array($saveDate->format('Ymd'), 'String'),
        2 => array($contributionId, 'Integer'),
      );
      CRM_Core_DAO::executeQuery($query, $queryParms);
    }
  }

  /**
   * Method to get the creditdatum or factuurdatum depending on type
   *
   * @param string $type
   * @param int $contributionId
   * @return bool
   */
  public function getDate($type, $contributionId) {
    if (!empty($contributionId)) {
      $customData = new CRM_Facturatie_CustomData();
      switch ($type) {
        case 'credit':
          $query = 'SELECT '.$customData->getCreditdatum('column_name').' FROM '.
            $customData->getContributionData('table_name').' WHERE entity_id = %1';
          break;
        case 'factuur':
          $query = 'SELECT '.$customData->getFactuurdatum('column_name').' FROM '.
            $customData->getContributionData('table_name').' WHERE entity_id = %1';
          break;
        default:
          return FALSE;
          break;
      }
      return CRM_Core_DAO::singleValueQuery($query, array(
        1 => array($contributionId, 'Integer'),
      ));
    }
    return FALSE;
  }

}