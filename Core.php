<?php
namespace TodoPago;

require_once("vendor/autoload.php");


define('TODOPAGO_FORMS_PROD','https://forms.todopago.com.ar');
define('TODOPAGO_FORMS_TEST','https://developers.todopago.com.ar');

class Core {
	const HYBRID_FORM = "hib";
	const EXTERNAL_FORM = "ext";

	private $sdk;

	public function __construct() {
		do_action("todopago_pre_create_sdk");

		$http_header = apply_filters("todopago_header","");
		$mode = apply_filters("todopago_mode","test");

		$this->sdk = new TodoPago\Sdk($http_header, $mode);
		do_action("todopago_post_create_sdk");
	}

	public function call_sar() {
		do_action("todopago_pre_call_sar");

		$dataComercio = apply_filters("todopago_sar_data_comercio", array());
		$dataOperacion = apply_filters("todopago_sar_data_operacion", array());

		$responseSAR = $this->sdk->sendAuthorizaRequest($dataComercio, $dataOperacion);
		//TODO: Loguear
		//TODO: Guardar tabla transaction
		if($responseSAR["StatusCode"] == 702 && !empty($dataComercio["Merchant"]) && !empty($dataSecurity)){
			$responseSAR = $this->sdk->sendAuthorizaRequest($dataComercio, $dataOperacion);
			//TODO: Loguear
		}
		if ($responseSAR["StatusCode"] == -1) {
			do_action("todopago_sar_response_ok");

			//FILTER: Form Type
			$form_type = apply_filters("todopago_sar_formtype", $responseSAR);

			if($form_type == self::HIBRIDO_FORM){
				//TODO: Loguear
				do_action("todopago_sar_hybridform", $responseSAR);

				////HIBRIDO
										$basename = apply_filters("todopago_sar_hybridform_basename");
                    $baseurl = plugins_url();
                    $form_dir = "$baseurl/$basename/view/formulario-hibrido";
                    $firstname = $dataOperacion['CSSTFIRSTNAME'];
                    $lastname = $dataOperacion['CSSTLASTNAME'];
                    $email = $dataOperacion['CSSTEMAIL'];
                    $merchant = $dataOperacion['MERCHANT'];
                    $amount = $dataOperacion['CSPTGRANDTOTALAMOUNT'];
										$prk = $responseSAR["PublicRequestKey"];


                    $home = home_url();

                    $arrayHome = explode ("/", $home);


                    // $return_URL_ERROR = $arrayHome[0].'//'."{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}".'&second_step=true';
										$return_URL_ERROR = apply_filters("todopago_sar_externalform_urlerror");

                    // if($this->url_after_redirection == "order_received"){
                    //     $return_URL_OK = $order->get_checkout_order_received_url().'&second_step=true';
                    // }else{
                    //     $return_URL_OK = $arrayHome[0].'//'."{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}".'&second_step=true';
                    //
                    // }
										$return_URL_OK = apply_filters("todopago_sar_externalform_urlok");

										$mode = apply_filters("todopago_mode","test");

                    $env_url = ($mode == "prod" ? TODOPAGO_FORMS_PROD : TODOPAGO_FORMS_TEST);

										do_action("todopago_sar_hybridform_beforedraw");

                    require 'view/formulario-hibrido/formulario.php';

			}
			else($form_type == self::EXTERNAL_FORM){
				//TODO: Loguear
				do_action("todopago_sar_externalform", $responseSAR);
			}
		}
		else {
			//TODO: loguear
			do_action("todopago_sar_response_error");
		}
		// do_action("todopago_response_sar", $responseSAR);
		//add_action("todopago_response_sar", "mi_responsesar_processor",0,2);

		do_action("todopago_post_call_sar");
	}

	public function call_gaa() {
		do_action("todopago_pre_call_gaa");

		$optionsAnswer = apply_filters("todopago_gaa_options_answer", array());

		$responseGAA = $this->sdk->getAuthorizeAnswer($optionsAnswer);
		do_action("todopago_response_gaa", $responseGAA);

		do_action("todopago_post_call_gaa");
	}

	public function get_status() {

	}

	public function get_credentials() {

	}

	public function void_request() {

	}

	public function return_request() {

	}



}
