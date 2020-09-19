<?php

class vandarpaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {

		parent::initContent();
        $cart = $this->context->cart;
		echo $this->module->execPayment($cart);
        
    }
}
