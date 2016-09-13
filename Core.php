<?php

require("vendor/autoload.php");

namespace TodoPago;

class Core {
	private $sdk;
	private $logger;

	public function __construct($logger) {
		do_action("todopago_pre_create_sdk");
		
		$this->logger = $logger;
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
		do_action("todopago_response_sar", $responseSAR);
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
		do_action("todopago_pre_get_status");
		$options_request = apply_filters("todopago_status_data",array());
		//$options_request = array('MERCHANT'=>getMerchant(),'OPERATIONID'=>$order_id);
		$response_status = $this->sdk->getStatus($options_request);
		$response_status_formated = apply_filters("todopago_response_get_status_format", $response_status );
		do_action("todopago_response_get_status", $response_status_formated );
		 
		do_action("todopago_post_get_status");			
	}


	public function get_credentials() {
		do_action("todopago_pre_get_credentials");
		$userArray = apply_filters("todopago_credentials_data", array()); 	
		try {
			$user = new TodoPago\Data\User($userArray);
    		$response_credentials = $this->sdk->getCredentials($user);
    		$response_credentials_formated = apply_filters("todopago_response_get_credentials_format",$response_credentials); 

    	}catch(TodoPago\Exception\ResponseException $e){
		    $response_credentials_formated = array(
		        "mensajeResultado" => $e->getMessage()
		    ); 		    
		}catch(TodoPago\Exception\ConnectionException $e){
		    $response_credentials_formated = array(
		        "mensajeResultado" => $e->getMessage()
		    );
		}catch(TodoPago\Exception\Data\EmptyFieldException $e){
		    $response_credentials_formated = array(
		        "mensajeResultado" => $e->getMessage()
		    );
		}
		do_action("todopago_response_get_credentials", $response_credentials_formated );
		do_action("todopago_post_get_credentials");
	}


	public function void_request() {
		do_action("todopago_pre_void_request");
		$options_return = apply_filters("todopago_void_request_data", array()); 	
		try {
            $return_response = $this->sdk->voidRequest($options_return);
            $this->logger->info("Se hace devolucion Total voidRequest : " . var_export($return_response ,true) );
        }
        catch (Exception $e) {
            $this->logger->error("Falló al consultar el servicio: ", $e);
            $return_response = array( 'error_message' => "Falló al consultar el servicio:" . $e->getMessage() );

        }
        do_action("todopago_response_void_request", $return_response );
		do_action("todopago_post_void_request");
	}


	public function return_request() {
		do_action("todopago_pre_return_request");
		$options_return = apply_filters("todopago_return_request_data", array());
		try {
            $return_response = $this->sdk->returnRequest($options_return);
            $this->logger->info("Se hace devolucion Parcial returnRequest : " . var_export($return_response ,true) );
        }
        catch (Exception $e) {
            $this->logger->error("Falló al consultar el servicio: ", $e);
            //throw new Exception("Falló al consultar el servicio");
            $return_response = array( 'error_message' => "Falló al consultar el servicio:" . $e->getMessage() );
        }

        do_action("todopago_response_return_request", $return_response );
        do_action("todopago_post_return_request");
	}

	protected function format_status($response_status ){

		$refunds = $response_status['Operations']['REFUNDS'];
		
		$ref = 0 ;
		if (is_array($refunds)){
			foreach ($refunds as $refund) {
				if (is_array($refund)){
					foreach ($refund as $k => $value) {
						if($k=='AMOUNT' && (!is_array($value)) ){
							$ref = $ref + $value; 
						}elseif( is_array($value)){
							$ref = $ref + $value['AMOUNT'];
						}

					}
				}	
			}

		} 

		return $ref;
	}

	protected function format_credentials( $response_credentials){
		$security = explode(" ", $response_credentials->getApikey()); 
		$response = array( 
            "codigoResultado" => 1,
            "merchandid" => $response_credentials->getMerchant(),
            "apikey" => $response_credentials->getApikey(),
            "security" => $security[1]
    	);
		return $response; 
	}

	add_filter("todopago_response_get_status_format", 'format_status' );
	add_filter("todopago_response_get_credentials_format", 'format_credentials' );


}	
