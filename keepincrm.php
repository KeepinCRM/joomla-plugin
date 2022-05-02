<?php
defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

if (!class_exists('vmCustomPlugin')) {
  require JPATH_VM_PLUGINS . '/vmcustomplugin.php';
}
if (!class_exists('vmPSPlugin')) {
  require JPATH_VM_PLUGINS . '/vmpsplugin.php';
}

class plgVmPaymentKeepincrm extends vmPSPlugin {

  public function plgVmConfirmedOrder($cart, $orderDetails) {
    $order = $orderDetails['details']['BT'];
    $orderItems = $orderDetails['items'];

    $api_key = $this->params->get('keepincrm_api_key');
    $source_id = $this->params->get('keepincrm_source_id');

    $user = '';
    $comment = '';

    $country = shopfunctions::getCountryByID($order->virtuemart_country_id);

    $payment_method = VmModel::getModel('paymentmethod')->getPayment($order->virtuemart_paymentmethod_id);
    $shipping_method = VmModel::getModel('shipmentmethod')->getShipment($order->virtuemart_shipmentmethod_id);

    $i = 0;
    $products_list = array();

    foreach($orderItems as $item) {
      $title .= $item->order_item_name;

      if (!empty($item->product_attribute)) {
        if(!class_exists('VirtueMartModelCustomfields')) require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'customfields.php');
        $product_attribute = VirtueMartModelCustomfields::CustomsFieldOrderDisplay($item, 'BE');
        $title .= '; ' . strip_tags($product_attribute);
      }

      $products_list[$i] = array (
        'amount'              => $item->product_quantity,
        'product_attributes'  => array (
          'sku'               => $item->product_sku,
          'title'             => $title,
          'price'             => $item->product_final_price
        )
      );

      $i++;
    }

    if ($country) {
      $comment .= 'Страна: ' . $country . '; ';
    }
    if ($order->city) {
      $comment .= 'Город: ' . $order->city . '; ';
    }
    if ($payment_method->payment_name) {
      $comment .= 'Оплата: ' . $payment_method->payment_name . '; ';
    }
    if ($shipping_method->shipment_name) {
      $comment .= 'Доставка: ' . $shipping_method->shipment_name . '; ';
    }
    if ($order->comment) {
      $comment .= 'Комментарий: ' . $order->comment;
    }

    if ($order->user_name) {
      $user .= $order->user_name;
    }
    if ($order->first_name || $order->last_name) {
      $user .= $order->first_name . ' ' . $order->last_name;
    }

    $agreement_details = array (
      'title'                 => 'Заказ #'.$order->order_number,
      'total'                 => $order->order_total,
      'comment'               => $comment,
      'client_attributes'     => array (
        'person'              => $user,
        'lead'                => false,
        'source_id'           => $source_id,
        'email'               => $order->email,
        'phones' => array (
          0 => $order->phone_1
        )
      ),
      'jobs_attributes' => $products_list
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://api.keepincrm.com/v1/agreements');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'X-Auth-Token: ' . $api_key . '', 'Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($agreement_details));
    curl_exec($curl);
    curl_close($curl);

    return true;
  }
}
