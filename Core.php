<?php

require("vendor/autoload.php");

namespace TodoPago;

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
