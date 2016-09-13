<?php
namespace TodoPago;

require_once("vendor/autoload.php");


define('TODOPAGO_FORMS_PROD','https://forms.todopago.com.ar');
define('TODOPAGO_FORMS_TEST','https://developers.todopago.com.ar');

class Core {
	const HYBRID_FORM = "hib";
	const EXTERNAL_FORM = "ext";

	private $sdk;
	private $logger;
	private $mode;
	private $http_header;
	private $wpdb;

	public function __construct($logger) {
		do_action("todopago_pre_create_sdk");

		global $wpdb;
		$this->logger = $logger;
		$this->http_header = apply_filters("todopago_header","");
		$this->mode = apply_filters("todopago_mode","test");
		$this->wpdb = $wpdb

		$this->sdk = new TodoPago\Sdk($this->http_header, $this->mode);
		do_action("todopago_post_create_sdk");
	}

	public function set_logger($logger ) {
		$this->logger = $logger;
	}

	public function get_logger($level, $message) {
		if($this->logger != null) {
			$this->logger->{$level}($message);
		}
	}

	public function getDataBase() {
		return $this->wpdb;
	}

	public function call_sar() {
		do_action("todopago_pre_call_sar");

		$this->get_logger("debug", __METHOD__);
		$dataComercio = apply_filters("todopago_sar_data_comercio", array());
		$dataOperacion = apply_filters("todopago_sar_data_operacion", array());

		$responseSAR = $this->sdk->sendAuthorizaRequest($dataComercio, $dataOperacion);
		update_post_meta( $order_id, 'response_SAR', serialize($response_SAR));
		$this->getDataBase()->insert(
                $this->getDataBase()->prefix.'todopago_transaccion',
                array('id_orden' => $dataOperacion['OPERATIONID'],
                      'params_SAR'=>json_encode(array("Operacion" => $dataOperacion, "comercio" => $dataComercio)),
                      'first_step'=>date("Y-m-d H:i:s"),
                      'response_SAR'=>json_encode($response_sar),
                      'request_key'=>$response_sar["RequestKey"],
                      'public_request_key'=>$response_sar['PublicRequestKey']
                     ),
                array('%d','%s','%s','%s','%s')
            );
		if($responseSAR["StatusCode"] == 702 && !empty($dataComercio["Merchant"]) && !empty($dataSecurity)){
			$responseSAR = $this->sdk->sendAuthorizaRequest($dataComercio, $dataOperacion);
		}
		$this->get_logger("debug", "resultado SAR: ".json_encode($responseSAR));
		if ($responseSAR["StatusCode"] == -1) {
			$form_type = apply_filters("todopago_sar_formtype", $responseSAR);

			if($form_type == self::HIBRIDO_FORM){
				do_action("todopago_draw_hybrid_form");
			}
			else($form_type == self::EXTERNAL_FORM){
				$this->get_logger("debug", "formulario externo");
				do_action("todopago_sar_externalform", $responseSAR);
			}
		}
		else {
			$this->get_logger("debug", "fallo");
			do_action("todopago_sar_response_error");
		}
		do_action("todopago_post_call_sar");
	}

	public function draw_hibryd_form()
	{
		do_action("todopago_pre_draw_hybrid_form");

		$this->get_logger("debug", __METHOD__);
		$basename = apply_filters("todopago_sar_hybridform_basename");
		$baseurl = plugins_url();
		$form_dir = "$baseurl/$basename/view/formulario-hibrido";
		$firstname = $dataOperacion['CSSTFIRSTNAME'];
		$lastname = $dataOperacion['CSSTLASTNAME'];
		$email = $dataOperacion['CSSTEMAIL'];

		$merchant = $dataOperacion['MERCHANT'];
		$amount = $dataOperacion['CSPTGRANDTOTALAMOUNT'];
		$prk = $responseSAR["PublicRequestKey"];

		$return_URL_ERROR = apply_filters("todopago_sar_externalform_urlerror");
		$return_URL_OK = apply_filters("todopago_sar_externalform_urlok");

		$env_url = ($this->mode == "prod" ? TODOPAGO_FORMS_PROD : TODOPAGO_FORMS_TEST);

		do_action("todopago_hybridform_beforedraw");

		require 'view/formulario-hibrido/formulario.php';

		do_action("todopago_post_draw_hybrid_form");
	}

	public function call_gaa() {
		do_action("todopago_pre_call_gaa");

		$this->get_logger("debug", __METHOD__);
		$order_id = apply_filters("todopago_gaa_orderid");
		$optionsAnswer = apply_filters("todopago_gaa_options_answer", array());

		$row = get_post_meta($order_id, 'response_SAR', true);
		$response_SAR = unserialize($row);
		$optionsAnswer['RequestKey'] = $responseSAR['RequestKey'];

		$this->get_logger("debug", "params GAA: ".json_encode($optionsAnswer));

		$responseGAA = $this->sdk->getAuthorizeAnswer($optionsAnswer);
		$this->get_logger("info", "responseGAA: ".json_encode($responseGAA));

		$this->getDataBase()->update(
                $this->getDataBase()->prefix.'todopago_transaccion',
                array(
                    'second_step' => date("Y-m-d H:i:s"), // string
                    'params_GAA' => json_encode($optionsAnswer), // string
                    'response_GAA' => json_encode($responseGAA), // string
                    'answer_key' => $optionsAnswer['AnswerKey'] //string
                ),
                array('id_orden'=>$order->id), // int
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ),
                array('%d')
            );

			if ($response_GAA["StatusCode"] == -1) {
				do_action("todopago_response_gaa_ok");
			} else {
				do_action("todopago_response_gaa_error");
			}

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

			$this->get_logger("info","Se hace devolucion Total voidRequest - Request : " . var_export($options_return ,true) );

			try {
	            $return_response = $this->sdk->voidRequest($options_return);
	            $this->get_logger("info", "Se hace devolucion Total voidRequest - Response : " . var_export($return_response ,true) );
	        }
	        catch (Exception $e) {
	            $this->get_logger("error", "Falló al consultar el servicio: ". $e->getMessage());
	            $return_response = array( 'error_message' => "Falló al consultar el servicio:" . $e->getMessage() );

	        }
	        do_action("todopago_response_void_request", $return_response );
			do_action("todopago_post_void_request");
		}


		public function return_request() {
			do_action("todopago_pre_return_request");
			$options_return = apply_filters("todopago_return_request_data", array());
			$this->get_logger("info","Se hace devolucion Total returnRequest - Request : " . var_export($options_return ,true) );
			try {
	            $return_response = $this->sdk->returnRequest($options_return);
	            $this->get_logger("info", "Se hace devolucion Parcial returnRequest - Response : " . var_export($return_response ,true) );
	        }
	        catch (Exception $e) {
	            $this->get_logger("error", "Falló al consultar el servicio: ". $e->getMessage());
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
		add_action("todopago_draw_hybrid_form", "draw_hybrid_form");

	}
