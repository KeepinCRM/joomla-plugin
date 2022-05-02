<?php

class JFormRuleKeepincrmkey extends JFormRule {

  public function test($element, $value, $group = NULL, $input = NULL, $form = NULL) {
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', true);

    /* @var $form JForm
     * @var $input JInput
     */
    $app = JFactory::getApplication();

    $api_key = $input->get('params.keepincrm_api_key');

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://api.keepincrm.com/v1/agreements');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'X-Auth-Token: '.$api_key.' ','Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
    $json = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    if ($json) {
      if ($info['http_code'] != '200') {
        $app->enqueueMessage('Api key is invalid', 'warning');
        return false;
      }
    } else {
      $app->enqueueMessage('Connection error');
      ob_start();
      ini_set('html_errors', false);
      phpinfo();
      $phpinfo = strip_tags(ob_get_clean());
      $app->enqueueMessage('<pre>'.$phpinfo.'</pre>','warning');
    }

    return true;
  }
}
