<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}
class vandar extends PaymentModule{

	private $_html = '';
	private $_postErrors = array();

	public function __construct(){

		$this->name = 'vandar';
		$this->tab = 'payments_gateways';
		$this->version = '2.0';
		$this->author = 'www.vandar.io';
		$this->controllers = array('payment', 'validation');
		$this->currencies = true;
  		$this->currencies_mode = 'radio';

		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('درگاه پرداخت وندار');
		$this->description = $this->l('ساخته شده توسط وندار به سفارشوندار');
		$this->confirmUninstall = $this->l('شما از حذف این ماژول مطمئن هستید ؟');

		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('ارز تنظیم نشده است');

		$config = Configuration::getMultiple(array('VANDAR_PIN', ''));
		if (!isset($config['VANDAR_PIN']))
			$this->warning = $this->l('شما باید شناسه درگاه خود را تنظیم کرده باشید');
		$config = Configuration::getMultiple(array('sha1Key', ''));
		if (!isset($config['sha1Key']))
			$this->warning = $this->l('شما باید sha1Key درگاه خود را تنظیم کرده باشید');

		if ($_SERVER['SERVER_NAME'] == 'localhost')
			$this->warning = $this->l('این ماژول روی لوکال کار نمیکند');


	}

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

	public function send($api, $amount, $redirect, $mobile = null, $factorNumber = null, $description = null) {
		return $this->curl_post(
			'https://vandar.io/api/ipg/send',
			[
				'api_key' => $api,
				'amount'  => $amount,
				'callback_url' => $redirect,
				'mobile_number' => $mobile,
				'factorNumber' => $factorNumber,
				'description' => $description,
			]
		);
	}



	public function install(){
		 return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
        ;
	}
	public function uninstall(){
		return Configuration::deleteByName('VANDAR_PIN')
            && Configuration::deleteByName('VANDAR_HASHKEY')
            && Configuration::deleteByName('sha1Key')
            && parent::uninstall()
        ;
	}

	public function displayFormSettings()
	{
		$bank_id = Configuration::get('VANDAR_HASHKEY');

		$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
			<fieldset>
				<legend><img src="../img/admin/cog.gif" alt="" class="middle" />'.$this->l('Settings').'</legend>
				<label>'.$this->l('کد پذیرنده - مرچنت ID').'</label>
				<div class="margin-form"><input type="text" size="30" name="VANDARPIN" value="'.Configuration::get('VANDAR_PIN').'" /></div>				
				<p class="hint clear" style="display: block; width: 501px;"><a href="https://www.vandar.io" target="_blank" >'.$this->l('ساخته شده توسط گروه برنامه نویسی وندار').'</a></p></div>
				<center><input type="submit" name="submitVANDAR" value="'.$this->l('به روز رسانی تنظیمات').'" class="button" /></center>			
			</fieldset>
		</form>';
	}

	public function displayConf()
	{

	}

	public function displayErrors()
	{
		foreach ($this->_postErrors AS $err)
		$this->_html .= '<div class="alert error">'. $err .'</div>';
	}

       	public function getContent()
	{
		$this->_html = '<h2>'.$this->l('vandar').'</h2>';
		if (isset($_POST['submitVANDAR']))
		{
			if (empty($_POST['VANDARPIN']))
				$this->_postErrors[] = $this->l('مرچنت را وارد کنید');

			if (!sizeof($this->_postErrors))
			{
				Configuration::updateValue('VANDAR_PIN', $_POST['VANDARPIN']);

				$this->displayConf();
			}
			else
				$this->displayErrors();
		}

		$this->displayFormSettings();
		return $this->_html;
	}

	private function displayvandarPayment()
	{
		$this->_html .= '<img src="../modules/vandar/test.png" style="float:left; margin-right:15px;"><b>'.$this->l('این ماژول امکان واریز آنلاین توسط درگاه پرداخت وندار را مهیا می سازد').'</b><br /><br />
		'.$this->l('تمامی کارت های عضو شتاب').'<br /><br /><br />';

	}

	public function execPayment($cart)
	{
		global $cookie, $smarty;

		$purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));
		$OrderDesc = Configuration::get('PS_SHOP_NAME'). $this->l(' Order');
		$current_currency = new Currency($this->context->cookie->id_currency);
		if($current_currency->id == $purchase_currency->id)
		$PurchaseAmount= number_format($cart->getOrderTotal(true, 3), 0, '', '');
		else
		$PurchaseAmount= number_format(Tools::convertPrice($cart->getOrderTotal(true, 3), $purchase_currency), 0, '', '');

		$terminal_id = Configuration::get('VANDAR_PIN');

		$OrderId = $cart->id;

		$bank_id = Configuration::get('VANDAR_HASHKEY');

		$amount = $PurchaseAmount;

		$redirect_url = $this->context->link->getModuleLink($this->name, 'validation', array(), true);

		$rst = $this->send($terminal_id,$amount,$redirect_url);
		$result = json_decode($rst);

		if ($result->status == 1) {
    		$go = "https://vandar.io/ipg/$result->token";
    		header("Location: $go");
		} else {
    		echo $result->errors[0];
		}


	}
	public function confirmPayment($order_amount,$Status,$Refnumber)
	{

	}
	public function hookPaymentOptions()
	{
		if (!$this->active) {
			return;
		}

 		$this->smarty->assign(
            $this->getTemplateVars()
        );
		$newOption = new PaymentOption();
		$newOption->setCallToActionText($this->trans($this->displayName, array(), 'Modules.vandar.Shop'))
					  ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
					  ->setAdditionalInformation($this->fetch('module:vandar/payment_info.tpl'));
		$payment_options = array($newOption);

	  return $payment_options;

	}

	public function getTemplateVars()
    {
        $cart = $this->context->cart;
        $total = $this->trans(
            '%amount% (tax incl.)',
            array(
                '%amount%' => Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH)),
            ),
            'Modules.vandar.Admin'
        );

        $checkOrder = Configuration::get('CHEQUE_NAME');
        if (!$checkOrder) {
            $checkOrder = '___________';
        }

        $checkAddress = Tools::nl2br(Configuration::get('CHEQUE_ADDRESS'));
        if (!$checkAddress) {
            $checkAddress = '___________';
        }

        return array(
            'checkTotal' => $total,
            'checkOrder' => $checkOrder,
            'checkAddress' => $checkAddress,
        );
    }


}