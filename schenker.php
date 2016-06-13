<?php

if (!defined('_PS_VERSION_'))
	exit;

class Schenker extends CarrierModule
{

	const PREFIX = 'schenker_';
 
	protected $_hooks = array(
		'actionCarrierUpdate', 
	);
 
	protected $_carriers = array(
	//"Public carrier name" => "technical name",
	'Schenker' => 'schenker',
	);
 
	public function __construct()
	{
		$this->name = 'schenker';
		$this->tab = 'shipping_logistics';
		$this->version = '1.6.1';
		$this->author = 'Artem Sopizhenko';
		$this->bootstrap = TRUE;
 
		parent::__construct();
 
		$this->displayName = $this->l('Schenker');
		$this->description = $this->l('Schenker shipping module.');
	}

	public function install()
	{
		if (parent::install()) 
		{
			foreach ($this->_hooks as $hook) 
			{
				if (!$this->registerHook($hook)) 
				{
					return FALSE;
				}
			}
 
			if (!$this->createCarriers()) 
			{ //function for creating new currier
				return FALSE;
			}
 			
 			Configuration::updateValue('SCHENKER_FRA_POSTNUMMER', '');
 			Configuration::updateValue('SCHENKER_KUNDENR', '');
 			Configuration::updateValue('SCHENKER_BRUKER', '');
 			Configuration::updateValue('SCHENKER_PASSORD', '');

			return TRUE;
		}
 
		return FALSE;
	}

	public function getContent()
	{
    	$output = null;
 
    	if (Tools::isSubmit('submit'))
    	{
        	$frapostnr = Tools::getValue('SCHENKER_FRA_POSTNUMMER');
        	if (!$frapostnr || empty($frapostnr))
            	$output .= $this->displayError($this->l('Invalid Configuration value'));
        	else
        	{
            	Configuration::updateValue('SCHENKER_FRA_POSTNUMMER', $frapostnr);
            	$output .= $this->displayConfirmation($this->l('Settings updated'));
        	}

        	$kundenr = Tools::getValue('SCHENKER_KUNDENR');
        	if (!$kundenr || empty($kundenr))
            	$output .= $this->displayError($this->l('Invalid Configuration value'));
        	else
        	{
            	Configuration::updateValue('SCHENKER_KUNDENR', $kundenr);
            	$output .= $this->displayConfirmation($this->l('Settings updated'));
        	}

        	$bruker = Tools::getValue('SCHENKER_BRUKER');
        	if (!$bruker || empty($bruker))
            	$output .= $this->displayError($this->l('Invalid Configuration value'));
        	else
        	{
            	Configuration::updateValue('SCHENKER_BRUKER', $bruker);
            	$output .= $this->displayConfirmation($this->l('Settings updated'));
        	}

        	$passord = Tools::getValue('SCHENKER_PASSORD');
        	if (!$passord || empty($passord))
            	$output .= $this->displayError($this->l('Invalid Configuration value'));
        	else
        	{
            	Configuration::updateValue('SCHENKER_PASSORD', $passord);
            	$output .= $this->displayConfirmation($this->l('Settings updated'));
        	}



    	}
    	return $output.$this->displayForm();
	}

	public function displayForm()
	{
    	// Get default language
    	$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
     
    	// Init Fields form array
    	$fields_form[0]['form'] = array(
        	'legend' => array(
            'title' => $this->l('Settings'),
            ),
            
            'input' => array(
            array(
                'type' => 'text',
                'label' => $this->l('Innh.postnr.'),
                'name' => 'SCHENKER_FRA_POSTNUMMER',
                'size' => 4,
                'required' => true
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Schenker kundenr'),
                'name' => 'SCHENKER_KUNDENR',
                'size' => 7,
                'required' => true
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Schenker bruker'),
                'name' => 'SCHENKER_BRUKER',
                'size' => 10,
                'required' => true
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Schenker passord'),
                'name' => 'SCHENKER_PASSORD',
                'size' => 10,
                'required' => true
            )
        ),

        'submit' => array(
            'title' => $this->l('Save'),
            'class' => 'button'
        )
    	);
     
    	$helper = new HelperForm();
     
    	// Module, token and currentIndex
    	$helper->module = $this;
    	$helper->name_controller = $this->name;
    	$helper->token = Tools::getAdminTokenLite('AdminModules');
    	$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
     
    	// Language
    	$helper->default_form_language = $default_lang;
    	$helper->allow_employee_form_lang = $default_lang;
     
    	// Title and toolbar
    	$helper->title = $this->displayName;
    	$helper->show_toolbar = true;        // false -> remove toolbar
    	$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    	$helper->submit_action = 'submit';
    	$helper->toolbar_btn = array(
        	'save' =>
        	array(
            	'desc' => $this->l('Save'),
            	'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            	'&token='.Tools::getAdminTokenLite('AdminModules'),
        	),
        	'back' => array(
            	'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            	'desc' => $this->l('Back to list')
        	)
    	);
     
    	// Load current value
    	$helper->fields_value['SCHENKER_FRA_POSTNUMMER'] = Configuration::get('SCHENKER_FRA_POSTNUMMER');
    	$helper->fields_value['SCHENKER_KUNDENR'] = Configuration::get('SCHENKER_KUNDENR');
    	$helper->fields_value['SCHENKER_BRUKER'] = Configuration::get('SCHENKER_BRUKER');
    	$helper->fields_value['SCHENKER_PASSORD'] = Configuration::get('SCHENKER_PASSORD');
     
    	return $helper->generateForm($fields_form);
	}

	protected function createCarriers()
	{
		foreach ($this->_carriers as $key => $value) 
		{
			//Create new carrier
			$carrier = new Carrier();
			$carrier->name = $key;
			$carrier->active = TRUE;
			$carrier->deleted = 0;
			$carrier->shipping_handling = FALSE;
			$carrier->range_behavior = 0;
			$carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = $key;
			$carrier->shipping_external = TRUE;
			$carrier->is_module = TRUE;
			$carrier->external_module_name = $this->name;
			$carrier->need_range = TRUE;
 
			if ($carrier->add()) 
			{
				$groups = Group::getGroups(true);
				foreach ($groups as $group) 
				{
					Db::getInstance()->autoExecute(_DB_PREFIX_ . 'carrier_group', array(
						'id_carrier' => (int) $carrier->id,
						'id_group' => (int) $group['id_group']
					), 	'INSERT');
				}
 
				$rangePrice = new RangePrice();
				$rangePrice->id_carrier = $carrier->id;
				$rangePrice->delimiter1 = '0';
				$rangePrice->delimiter2 = '1000000';
				$rangePrice->add();
 
				$rangeWeight = new RangeWeight();
				$rangeWeight->id_carrier = $carrier->id;
				$rangeWeight->delimiter1 = '0';
				$rangeWeight->delimiter2 = '1000000';
				$rangeWeight->add();
 
				$zones = Zone::getZones(true);

				foreach ($zones as $z) 
					{
						Db::getInstance()->autoExecute(_DB_PREFIX_ . 'carrier_zone',
							array('id_carrier' => (int) $carrier->id, 'id_zone' => (int) $z['id_zone']), 'INSERT');
						Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_ . 'delivery',
							array('id_carrier' => $carrier->id, 'id_range_price' => (int) $rangePrice->id, 'id_range_weight' => NULL, 'id_zone' => (int) $z['id_zone'], 'price' => '0'), 'INSERT');
						Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_ . 'delivery',
							array('id_carrier' => $carrier->id, 'id_range_price' => NULL, 'id_range_weight' => (int) $rangeWeight->id, 'id_zone' => (int) $z['id_zone'], 'price' => '0'), 'INSERT');
					}
 
				copy(dirname(__FILE__) . '/views/img/logo.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg'); //assign carrier logo

				Configuration::updateValue(self::PREFIX . $value, $carrier->id);
				Configuration::updateValue(self::PREFIX . $value . '_reference', $carrier->id);
			}
		}
 
		return TRUE;
	}

	protected function deleteCarriers()
	{
		foreach ($this->_carriers as $value) 
		{
			$tmp_carrier_id = Configuration::get(self::PREFIX . $value);
			$carrier = new Carrier($tmp_carrier_id);
			$carrier->delete();
		}
 
		return TRUE;
	}
 
	public function uninstall()
	{
		if (parent::uninstall()) 
		{
			foreach ($this->_hooks as $hook) 
			{
				if (!$this->unregisterHook($hook)) 
				{
					return FALSE;
				}
			}
 
			if (!$this->deleteCarriers()) 
			{
				return FALSE;
			}
 
			return TRUE;
		}
 
		Configuration::deleteByName('SCHENKER_FRA_POSTNUMMER');
		Configuration::deleteByName('SCHENKER_KUNDENR');
		Configuration::deleteByName('SCHENKER_BRUKER');
		Configuration::deleteByName('SCHENKER_PASSORD');

		return FALSE;
	}

	public function getOrderShippingCost($cart, $shipping_cost) 
    {
        $address = new Address((int)$cart->id_address_delivery);
        $to_postnr = $address->postcode;
        $from_postnr = Configuration::get('SCHENKER_FRA_POSTNUMMER');
        $send_kunde_nr = Configuration::get('SCHENKER_KUNDENR');
        $bruker = Configuration::get('SCHENKER_BRUKER');
        $passord = Configuration::get('SCHENKER_PASSORD');
        $productsForShipp = $cart->getProducts();
        $orderWeight = $cart->getTotalWeight() * 1000;
        $calcWeight = 0;
        $shipping_cost = 0;
        $visors_shipping_cost = 0;
        $other_products_shipping_cost = 0;
        $item_shipping_cost = 0;


        foreach ($productsForShipp as $productForShipp) 
        {
        	$ref = $productForShipp['reference'];
        	$qty = $productForShipp['cart_quantity'];
            $weight = round($productForShipp['weight']);
            $width = round($productForShipp['width']);
            $height = round($productForShipp['height']);
            $depth = round($productForShipp['depth']);
            
            	
            	switch ($ref) {
            		case preg_match('/APBG[^C]/', $ref) == 1:
            			$hvisors = true;
            			$visors_shipping_cost = 350;
            			break;

            		case preg_match('/^AP0/', $ref) == 1:
            			
            			if ($hvisors) {
            				$visors_shipping_cost = 350;
            			}
            			else {
            				$visors_shipping_cost = 250;
            			}
          
            			break;
            		
            		default:
            			$ch = curl_init('https://www.myschenker.no/Fraktberegning/Webgrensesnitt/Fraktberegning.aspx');
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, 'send_kunde_nr='.$send_kunde_nr.'&bruker='.$bruker.'&passord='.$passord.'&send_post_nr='.$from_postnr.'&mott_post_nr='.$to_postnr.'&virk_vekt_kg='.$weight.'&lengde_cm='.$depth.'&bredde_cm='.$width.'&hoeyde_cm='.$height);
						curl_setopt($ch, CURLOPT_COOKIEJAR, "my_cookies.txt");  
						curl_setopt($ch, CURLOPT_COOKIEFILE, "my_cookies.txt");  
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
						$result = curl_exec($ch); 
						curl_close($ch);
						$xml=simplexml_load_string($result) or die("Error: Cannot create object");
			
						$item_shipping_cost = $xml->pris->kr_avtalefrakt;
            			break;
            	}
            	
			
            
            $arrayName = array('item_cost' => $item_shipping_cost,);
            print_r($arrayName);
            $calcWeight += $weight * $qty; 
            $other_products_shipping_cost += $item_shipping_cost * $qty;
        }
		
		$shipping_cost = $other_products_shipping_cost + $visors_shipping_cost;
		
		if($shipping_cost) 
		{
	    	
	    	return $shipping_cost;
        }
        
        else
        
        return false; // Indicates that carrier is not available due to size/weight restrictions
    }
 
	public function getOrderShippingCostExternal($params)
	{
		return $this->getOrderShippingCost($params, 0);
	}

	public function hookActionCarrierUpdate($params)
	{
		if ($params['carrier']->id_reference == Configuration::get(self::PREFIX . 'swipbox_reference')) 
		{
			Configuration::updateValue(self::PREFIX . 'swipbox', $params['carrier']->id);
		}
	}
}