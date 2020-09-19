<?php

class vandarvalidationModuleFrontController extends ModuleFrontController
{
	function curl_post($action, $params)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $action);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    return $res;
}
	function verify($api, $token)
{
    return $this->curl_post(
        'https://vandar.io/api/ipg/verify',
        [
            'api_key' => $api,
            'token' => $token,
        ]
    );
}


    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'vandar') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->trans('This payment method is not available.', array(), 'Modules.vandar.Shop'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

		// $resultCode = $_POST['resultCode'];
		$referenceId = isset($_GET['token']) ? $_GET['token'] : 0;
		$orderId = Tools::getValue('paid');
		$order = new Order($orderId);
		$terminal_id = Configuration::get('VANDAR_PIN');	

		$result = json_decode($this->verify($terminal_id, $referenceId));

		if (isset($result->status)) {
			if ($result->status == 1) {
				$this->module->validateOrder((int)$cart->id, _PS_OS_PAYMENT_, $total, $this->module->displayName, "سفارش تایید شده / کد رهگیری :".$referenceId,array(), (int)$currency->id, false, $customer->secure_key);

				Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
			} else {
				$message ='پرداخت ناموفق';
			}
		} else {
			if ($_GET['status'] == 0) {
				$message ='پرداخت ناموفق';
			}
		}

		
		 if ((bool)Context::getContext()->customer->is_guest) {
				$url=Context::getContext()->link->getPageLink('guest-tracking', true);
			} else {
				$url=Context::getContext()->link->getPageLink('history', true);
			}
			
		 $this->context->smarty->assign([
				'message' => $message,
				'redirectUrl' => $url,
				'orderReference' => $order->reference,

			]);
			
			return $this->setTemplate('module:vandar/back.tpl');
    }
	
	// private function bankShowStatus($ErrorCode)
	// {
	// 	{
	// 	case 110:
	// 			return" انصراف دارنده کارت";
	// 		break;
	// 	case 120:
	// 		return"   موجودی کافی نیست";
	// 		break;
	// 	case 130:
	// 	case 131:
	// 	case 160:
	// 		return"   اطلاعات کارت اشتباه است";
	// 		break;
	// 	case 132:
	// 	case 133:
	// 		return"   کارت مسدود یا منقضی می باشد";
	// 		break;
	// 	case 140:
	// 		return" زمان مورد نظر به پایان رسیده است";
	// 		break;
	// 	case 200:
	// 	case 201:
	// 	case 202:
	// 		return" مبلغ بیش از سقف مجاز";
	// 		break;
	// 	case 166:
	// 		return" بانک صادر کننده مجوز انجام  تراکنش را صادر نکرده";
	// 		break;
	// 	case 150:
	// 	default:
	// 		return" خطا بانک  $resultCode";
	// 	break;
	// 	}
		
	// }
}
