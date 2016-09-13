<?php

require("vendor/autoload.php");

namespace TodoPago;

class Core {
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

		$http_header = apply_filters("todopago_sar_data_comercio",array());
		$mode = apply_filters("todopago_sar_data_operacion",array());

		$responseSAR = $this->sdk->sendAuthorizaRequest($dataComercio,$dataOperacion);
		do_action("todopago_response_sar",$responseSAR);
		//add_action("todopago_response_sar", "mi_responsesar_processor",0,2);

		do_action("todopago_post_call_sar");	
	}

	public function call_gaa() {

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
