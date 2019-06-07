<?php

class CRM_Assign2newsletter_Task_AssignNewsletterPreference extends CRM_Contact_Form_Task {

  public function buildQuickForm() {
    $formElements = [];

    // set form title
    CRM_Utils_System::setTitle('Schrijf in voor Kunstenpunt nieuwsbrieven');

    // add field Kunstenpunt Nieuws
    $kpnewsItems = CRM_Core_OptionGroup::values('kunstenpunt_nieuws');
    $this->addCheckBox('kunstenpunt_nieuws', 'Kunstenpunt Nieuws', $kpnewsItems,NULL, NULL, NULL, NULL, '<br>', TRUE);
    $formElements[] = 'kunstenpunt_nieuws';

    // add field Flanders Arts Institute News
    $faiItems = CRM_Core_OptionGroup::values('flanders_ai_news');
    $this->addCheckBox('fai_news', 'Flanders Arts Institute News', $faiItems,NULL, NULL, NULL, NULL, '<br>', TRUE);
    $formElements[] = 'fai_news';

    // add field Kunstenpunt Initiatieven
    $kpinitItems = CRM_Core_OptionGroup::values('initiatieven_themas');
    $this->addCheckBox('kunstenpunt_initiatieven', 'Kunstenpunt Initiatieven en Thema\'s', $kpinitItems,NULL, NULL, NULL, NULL, '<br>', TRUE);
    $formElements[] = 'kunstenpunt_initiatieven';

    // add submit button
    $this->addButtons([
      ['type' => 'submit', 'name' => ts('Confirm'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')]]
    );

    // assign form elements to template
    $this->assign('elementNames', $formElements);
    $this->assign('numContacts', count($this->_contactIds));
  }

  public function postProcess() {
    // get the selection(s)
    $values = $this->exportValues();
    $kunstenpunt_nieuws = CRM_Utils_Array::value('kunstenpunt_nieuws', $values, []);
    $fai_news = CRM_Utils_Array::value('fai_news', $values, []);
    $kunstenpunt_initiatieven = CRM_Utils_Array::value('kunstenpunt_initiatieven', $values, []);

    if (count($kunstenpunt_nieuws) == 0 && count($fai_news) == 0 && count($kunstenpunt_initiatieven) == 0) {
      CRM_Core_Session::setStatus('Selecteer minstens 1 nieuwsbrief', 'Opgelet', 'error');
    }
    else {
      // loop over all selected contacts
      foreach ($this->_contactIds as $contactId) {
        // get custom fields of that contact
        $result = civicrm_api3('CustomValue', 'get', [
          'sequential' => 1,
          'return' => ["kunstenpunt_communicatie:kunstenpunt_nieuws", "kunstenpunt_communicatie:flanders_arts_institute_news", "kunstenpunt_communicatie:initiatieven_themas"],
          'entity_id' => $contactId,
        ]);

        // update the custom fields
        foreach ($result['values'] as $customField) {
          if ($customField['id'] == 69 && $kunstenpunt_nieuws) {
            $params = $this->createCustomValueParams($customField, $kunstenpunt_nieuws);
            civicrm_api3('Contact', 'create', $params);
          }
          elseif ($customField['id'] == 70 && $fai_news) {
            $params = $this->createCustomValueParams($customField, $fai_news);
            civicrm_api3('Contact', 'create', $params);
          }
          elseif ($customField['id'] == 71 && $kunstenpunt_initiatieven) {
            $params = $this->createCustomValueParams($customField, $kunstenpunt_initiatieven);
            civicrm_api3('Contact', 'create', $params);
          }
        }
      }

      CRM_Core_Session::setStatus('De contacten zijn bijgewerkt', '', 'success');
    }

    parent::postProcess();
  }

  /*
   * merge the array of the existing selection with the new selection
   */
  private function createCustomValueParams($customField, $newSelection) {
    $params = [];
    $customFieldName = 'custom_' . $customField['id'];

    // check if we have an existing selection
    if ($customField[0]) {
      $customFieldValue = $customField[0];
    }
    else {
      $customFieldValue = [];
    }

    // add items from the new selection if it's not already there
    foreach ($newSelection as $k => $v) {
      if (!in_array($k, $customFieldValue)) {
        // that's a new one, add it
        $customFieldValue[] = $k . ''; // make it a string
      }
    }
    sort($customFieldValue);

    // return the params array
    $params['id'] = $customField['entity_id'];
    $params[$customFieldName] = $customFieldValue;

    return $params;
  }

}

