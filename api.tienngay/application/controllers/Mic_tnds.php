<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Mic_tnds extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('mic_tnds_model');
		$this->load->model('log_mic_tnds_model');
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("transaction_model");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$dataPost = $this->input->post();
		$this->flag_login = 1;
		$this->superadmin = false;
		if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
			$headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
			$token = Authorization::validateToken($headers_item);
			if ($token != false) {
				// Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
				$this->app_login = array(
					'_id' => new \MongoDB\BSON\ObjectId($token->id),
					'email' => $token->email,
					"status" => "active",
					// "is_superadmin" => 1
				);
				//Web
				if ($dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->name = $this->info['full_name'];
					$this->phone = $this->info['phone_number'];
					// $this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
	}

	public function insert_mic_tnds_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$id_pgd = !empty($data['id_pgd']) ? $data['id_pgd'] : '';
		$effective_time = !empty($data['effective_time']) ? $this->security->xss_clean($data['effective_time']) : '';
		if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{4,255}$/", $data['ten_kh'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tên khách hàng không đúng định dạng hoặc không chứa kí tự số!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!preg_match("/^[A-Z0-9]{7,9}$/", $data['bien_xe'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Biển số xe không đúng định dạng, không chứa khoảng trắng và kí tự đặc biệt! VD:30L18888"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!preg_match("/^[0-9]{10,12}$/", $data['phone'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số điện thoại trong khoảng 10 đến 12 số!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($data['ngay_sinh'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày sinh khách hàng không thể trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!preg_match("/^[0-9]{9,12}$/", $data['cmt'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Chứng minh thư trong khoảng 9 đến 12 số!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Định dạng email không hợp lệ!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($data['address'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Địa chỉ không hợp lệ!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($id_pgd)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Phòng giao dịch không thể trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($data['chinh_chu'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hình thức xe khách hàng không thể trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($data['loai_xe'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Loại xe không thể trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($data['chinh_chu']) && $data['chinh_chu'] == "C") {
			if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{4,255}$/", $data['ten_kh_ko_chinh_chu'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Tên khách hàng không đúng định dạng!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (!preg_match("/^[0-9]{9,12}$/", $data['cmt_kh_ko_chinh_chu'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Chứng minh thư trong khoảng 9 đến 12 số!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (!preg_match("/^[0-9]{10,12}$/", $data['phone_khong_chinh_chu'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số điện thoại trong khoảng 10 đến 12 số!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (!filter_var($data['email_khong_chinh_chu'], FILTER_VALIDATE_EMAIL)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Định dạng email không hợp lệ!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($data['dia_chi_khong_chinh_chu'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Địa chỉ khách hàng không thể trống!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($data['ngay_sinh_khong_chinh_chu'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Ngày sinh khách hàng không thể trống!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}


		$store = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_pgd)]);
		$type_mic = "MIC_TNDS";
		if ($effective_time == '1') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+1 year"));
		} elseif ($effective_time == '3') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+3 year"));
		} elseif ($effective_time == '2') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+2 year"));
		}
		$code = "MIC_TNDS_" . date("dmY") . "_" . time();
		$ma_cty = $this->check_store_tcv_dong_bac($data['id_pgd']);
		$mic_tnds = $this->inser_mic_tnds($code, $NGAY_HL, $NGAY_KT, $data, $ma_cty);
		$this->log_mic_tnds($mic_tnds->request, $mic_tnds->response, $type_mic, $code);
		if ($mic_tnds->res != true) {
			$mes = ($mic_tnds->response->ResponseStatus->ErrorInfo->ErrorDesc);
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $mes
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$mic_data = $mic_tnds->data;
			$dt_mic = array(
				'type_mic' => $type_mic,
				'mic_code' => $code,
				'mic_gcn' => $mic_data->GCN,
				'mic_fee' => $mic_data->PHI,
				'SO_ID' => $mic_data->SO_ID,
				'FILE' => $mic_data->FILE,
				'NGAY_HL' => $NGAY_HL,
				'NGAY_KT' => $NGAY_KT,
				'company_code' => $ma_cty,
				"license_plates" => !empty($data['bien_xe']) ? $this->security->xss_clean($data['bien_xe']) : '',
				'vehicle_capacity' => !empty($data['loai_xe']) ? $this->security->xss_clean($data['loai_xe']) : '',
				'muc_trach_nhiem' => !empty($data['muc_trach_nhiem']) ? $this->security->xss_clean($data['muc_trach_nhiem']) : '',
				'customer_info' => [
					'customer_name' => !empty($data['ten_kh']) ? $this->security->xss_clean($data['ten_kh']) : '',
					'customer_phone' => !empty($data['phone']) ? $this->security->xss_clean((string)$data['phone']) : '',
					'card' => !empty($data['cmt']) ? $this->security->xss_clean($data['cmt']) : '',
					'email' => !empty($data['mail']) ? $this->security->xss_clean($data['mail']) : '',
					'birthday' => !empty($data['ngay_sinh']) ? $this->security->xss_clean(($data['ngay_sinh'])) : '',
					'address' => !empty($data['address']) ? $this->security->xss_clean(($data['address'])) : '',
				],
				'customer_info_extra' => [
					'customer_name' => !empty($data['ten_kh_ko_chinh_chu']) ? $this->security->xss_clean($data['ten_kh_ko_chinh_chu']) : '',
					'customer_phone' => !empty($data['phone_khong_chinh_chu']) ? $this->security->xss_clean((string)$data['phone_khong_chinh_chu']) : '',
					'card' => !empty($data['cmt_kh_ko_chinh_chu']) ? $this->security->xss_clean($data['cmt_kh_ko_chinh_chu']) : '',
					'email' => !empty($data['email_khong_chinh_chu']) ? $this->security->xss_clean($data['email_khong_chinh_chu']) : '',
					'birthday' => !empty($data['ngay_sinh_khong_chinh_chu']) ? $this->security->xss_clean(($data['ngay_sinh_khong_chinh_chu'])) : '',
					'address' => !empty($data['dia_chi_khong_chinh_chu']) ? $this->security->xss_clean($data['dia_chi_khong_chinh_chu']) : ''
				],
				'store' => [
					'id' => (string)$store['_id'],
					'name' => $store['name']
				],
				'status' => 10,
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail,
				
			);
			$this->mic_tnds_model->insert($dt_mic);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'file' => $mic_data->FILE,
				'message' => "Bán bảo hiểm thành công!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function log_mic_tnds($request, $response, $type, $code)
	{
		if ($response->STATUS == TRUE) {
			$response1 = json_decode(json_encode($response));
			$response_data = [
				'TRXID' => $response1->TRXID,
				'TRXDATETIME' => $response1->TRXDATETIME,
				'STATUS' => $response1->STATUS,
				'GCN' => $response1->GCN,
				'SO_ID' => $response1->SO_ID,
				'PHI' => $response1->PHI,
				'FILE' => $response1->FILE,
				'ERRORINFO' => $response1->ERRORINFO,
			];
		}
		$dataInser = array(
			"type" => $type,
			'code' => $code,
			"response_data" => !empty($response_data) ? $response_data : $response,
			"request_data" => $request,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail,
		);
		$this->log_mic_tnds_model->insert($dataInser);
	}

	public function inser_mic_tnds($code, $NGAY_HL, $NGAY_KT, $data, $ma_cty)
	{
		$loai_xe = !empty($data['loai_xe']) ? $this->security->xss_clean($data['loai_xe']) : '';
		$bien_xe = !empty($data['bien_xe']) ? $this->security->xss_clean($data['bien_xe']) : '';
		$muc_trach_nhiem = !empty($data['muc_trach_nhiem']) ? $this->security->xss_clean($data['muc_trach_nhiem']) : '';
		$price = !empty($data['price']) ? str_replace(array(','), '', $this->security->xss_clean($data['price'])) : '';
//		$so_nguoi = !empty($data['so_nguoi']) ? $this->security->xss_clean($data['so_nguoi']) : '';
//		$loai_kh = !empty($data['loai_kh']) ? $this->security->xss_clean($data['loai_kh']) : '';
		$ten_kh = !empty($data['ten_kh']) ? $this->security->xss_clean($data['ten_kh']) : '';
		$cmt = !empty($data['cmt']) ? $this->security->xss_clean($data['cmt']) : '';
		$ngay_sinh = !empty($data['ngay_sinh']) ? $this->security->xss_clean(date('d/m/Y', $data['ngay_sinh'])) : '';
		$phone = !empty($data['phone']) ? $this->security->xss_clean((string)$data['phone']) : '';
		$mail = !empty($data['mail']) ? $this->security->xss_clean($data['mail']) : '';
		$address = !empty($data['address']) ? $this->security->xss_clean($data['address']) : '';
		$chinh_chu = !empty($data['chinh_chu']) ? $this->security->xss_clean($data['chinh_chu']) : '';
		$ten_kh_ko_chinh_chu = !empty($data['ten_kh_ko_chinh_chu']) ? $this->security->xss_clean($data['ten_kh_ko_chinh_chu']) : '';
		$cmt_kh_ko_chinh_chu = !empty($data['cmt_kh_ko_chinh_chu']) ? $this->security->xss_clean($data['cmt_kh_ko_chinh_chu']) : '';
		$ngay_sinh_khong_chinh_chu = !empty($data['ngay_sinh_khong_chinh_chu']) ? $this->security->xss_clean(date('d/m/Y', $data['ngay_sinh_khong_chinh_chu'])) : '';
		$phone_khong_chinh_chu = !empty($data['phone_khong_chinh_chu']) ? $this->security->xss_clean((string)$data['phone_khong_chinh_chu']) : '';
		$dia_chi_khong_chinh_chu = !empty($data['dia_chi_khong_chinh_chu']) ? $this->security->xss_clean($data['dia_chi_khong_chinh_chu']) : '';
		$email_khong_chinh_chu = !empty($data['email_khong_chinh_chu']) ? $this->security->xss_clean($data['email_khong_chinh_chu']) : '';
//		$price = $this->get_price_mic_tnds($loai_xe);
		$originalXML = '<ns1:ws_GCN_TRA>
                     <!--Optional:-->
                     <ns1:xmlinput>
                     <![CDATA[
                   <XMLINPUT>
					<MA_DVI>' . $this->config->item("MIC_MA_DVI") . '</MA_DVI>
                    <NSD>' . $this->config->item("MIC_NSD") . '</NSD>
                    <PAS>' . $this->config->item("MIC_PAS") . '</PAS>
                    <NV>2BL</NV>
                    <ID_TRAS>' . $code . '</ID_TRAS>
                    <KIEU_HD>G</KIEU_HD> 
                    <TTOAN>' . (double)$price . '</TTOAN>
                    <LOAI_XE>' . $loai_xe . '</LOAI_XE>
                    <BIEN_XE>' . $bien_xe . '</BIEN_XE>
                    <SO_KHUNG></SO_KHUNG>
                    <SO_MAY></SO_MAY>
                    <NGAY_HL>' . $NGAY_HL . '</NGAY_HL>
                    <NGAY_KT>' . $NGAY_KT . '</NGAY_KT> 
                    <SO_CN>2</SO_CN>
                    <TL>' . (int)$muc_trach_nhiem . '</TL>
                    <LKH>C</LKH>
                    <TEN>' . $ten_kh . '</TEN>
                    <CMT>' . $cmt . '</CMT>
                    <NG_SINH>' . $ngay_sinh . '</NG_SINH>
                    <GIOI>1</GIOI>
                    <MOBI>' . $phone . '</MOBI>
                    <EMAIL>' . $mail . '</EMAIL>
                    <DCHI>' . $address . '</DCHI> 
                    <DBHM>' . $chinh_chu . '</DBHM>
                    <TENM>' . $ten_kh_ko_chinh_chu . '</TENM>
                    <CMTM>' . $cmt_kh_ko_chinh_chu . '</CMTM>
                    <NG_SINHM>' . $ngay_sinh_khong_chinh_chu . '</NG_SINHM>                     
                    <MOBIM>' . $phone_khong_chinh_chu . '</MOBIM>
                    <EMAILM>' . $email_khong_chinh_chu . '</EMAILM>
                    <DCHIM>' . $dia_chi_khong_chinh_chu . '</DCHIM>
                    <MA_CTY>' . $ma_cty . '</MA_CTY>
				  </XMLINPUT>
            ]]>  </ns1:xmlinput>
                  </ns1:ws_GCN_TRA>
            ';
		$wsdl_url = $this->config->item("API_MIC") . '?wsdl';
		try {
			$timeout = microtime(true);
			$params = new SoapVar($originalXML, XSD_ANYXML);
//			var_dump($params ); die;
			$this->soapClient = new SoapClient($wsdl_url, array('soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => false));
			$this->soapClient->__setLocation($this->config->item("API_MIC"));
			$result = $this->soapClient->ws_GCN_TRA($params);
			$xml = simplexml_load_string($result->ws_GCN_TRAResult);
			$this->log_mic_tnds($originalXML, $xml, 'MIC_TNDS', $code);
			if ($xml->STATUS == "TRUE") {
				$response = [
					'res' => true,
					'status' => "200",
					'data' => $xml,
					'request' => $originalXML,
					'response' => $xml,
					'NGAY_HL' => $NGAY_HL,
					'NGAY_KT' => $NGAY_KT

				];
				return json_decode(json_encode($response));
			} else {
				$response = [
					'res' => false,
					'status' => "401",
					'request' => $originalXML,
					'response' => $xml,
					'NGAY_HL' => $NGAY_HL,
					'NGAY_KT' => $NGAY_KT
				];
				return json_decode(json_encode($response));
			}
		} catch (Exception $e) {
			$response = [
				'res' => false,
				'status' => "401",
				'request' => $originalXML,
				'response' => $e->getMessage(),
				'NGAY_HL' => $NGAY_HL,
				'NGAY_KT' => $NGAY_KT
			];
			return json_decode(json_encode($response));
		}
	}

	public function get_price_mic_tnds_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$loai_xe = !empty($data['loai_xe']) ? $this->security->xss_clean($data['loai_xe']) : '';
		$CI = &get_instance();
		$CI->load->config('config_money_tnds');
		if ($loai_xe == "L") {
			$price = $CI->config->item("MONEY_TNDS_100CC");
		} else {
			$price = $CI->config->item("MONEY_TNDS_50CC");
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'phi_bh' => number_format($price),
			'message' => "thành công!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_list_mic_tnds_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$tab = !empty($data['tab']) ? $data['tab'] : 'mic_tnds';
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';

		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : '';
		$customer_phone = !empty($data['customer_phone']) ? $this->security->xss_clean($data['customer_phone']) : '';
		$code = !empty($data['code']) ? $this->security->xss_clean($data['code']) : '';
		$code_mic_tnds = !empty($data['code_mic_tnds']) ? $this->security->xss_clean($data['code_mic_tnds']) : '';
		$filter_by_store = !empty($data['filter_by_store']) ? $this->security->xss_clean($data['filter_by_store']) : '';
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone)) {
			$condition['customer_phone'] = $customer_phone;
		}
		if (!empty($code)) {
			$condition['code'] = $code;
		}
		if (!empty($filter_by_store)) {
			$condition['filter_by_store'] = $filter_by_store;
		}
		if (!empty($code_mic_tnds)) {
			$condition['code_mic_tnds'] = $code_mic_tnds;
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}

		if (!$all) {
			// neu khong thuoc cac quyen all thi lay ra id cua store cua user hien tai
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'groupRoles' => array(),
					'total' => 0
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}
		if (empty($filter_by_store)) {
			if (in_array('quan-ly-khu-vuc', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('hoi-so', $groupRoles)) {
				$stores = $this->getStores($this->id);
				$condition['stores'] = $stores;
			}
		} else {
			$condition['filter_by_store'] = $filter_by_store;
		}
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 20;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		if ($tab == 'mic_tnds') {
			$result = $this->mic_tnds_model->get_list_mic_tnds($condition, $per_page, $uriSegment);
			$total = $this->mic_tnds_model->get_count_mic_tnds($condition);
		} else {
			$result = $this->transaction_model->list_transaction_mic_tnds($condition, $per_page, $uriSegment);
			$total = $this->transaction_model->total_list_transaction_mic_tnds($condition);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result,
			'total' => $total,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function statistical_mic_tnds_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
		$stores = $this->store_model->find_where(['status' => 'active']);
		foreach ($stores as $store) {
			if (!empty($start) && !empty($end)) {
				$condition = array(
					'start' => strtotime(trim($start) . ' 00:00:00'),
					'end' => strtotime(trim($end) . ' 23:59:59')
				);
			}
			$condition['store'] = (string)$store['_id'];
			$data = $this->get_mic_store_post($condition);
			$store['price'] = $data['price'];
			$store['total_transaction'] = $data['total_transaction'];

		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $stores,
			'message' => "thành công!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_mic_store_post($condition)
	{
		$data = [];
		$mics = $this->mic_tnds_model->get_list_mic_store($condition);
		if (!empty($mics)) {
			$sum_price = 0;
			foreach ($mics as $mic) {
				$sum_price += $mic['mic_fee'];
			}
			$data['price'] = !empty($sum_price) ? ($sum_price) : 0;
			$data['total_transaction'] = count($mics);
		}
		return $data;

	}

	public function get_price_mic_tnds($loai_xe)
	{
		$CI = &get_instance();
		$CI->load->config('config_money_tnds');
		if ($loai_xe == "L") {
			$price = $CI->config->item("MONEY_TNDS_100CC");
		} else {
			$price = $CI->config->item("MONEY_TNDS_50CC");
		}
		return $price;
	}

	public function get_store_by_user_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$store = $this->role_model->get_store_user((string)$this->id);
		$data = [];
		foreach ($store as $value) {
			$data[] = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($value['id'])]);
		}
		if (count($data) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $data,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function detail_mic_tnds_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$data = $this->mic_tnds_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
			'message' => "thành công!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_phi_mic_tnds_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$loai_xe = !empty($data['loai_xe']) ? $this->security->xss_clean($data['loai_xe']) : '';
		$muc_trach_nhiem = !empty($data['muc_trach_nhiem']) ? $this->security->xss_clean($data['muc_trach_nhiem']) : '';
		$effective_time = !empty($data['effective_time']) ? $this->security->xss_clean($data['effective_time']) : '';
		if ($effective_time == '1') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+1 year"));
		} elseif ($effective_time == '3') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+3 year"));
		} elseif ($effective_time == '2') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+2 year"));
		}
		$originalXML = '<ns1:ws_BPHI>
                     <!--Optional:-->
                     <ns1:xmlinput>
                     <![CDATA[
                   <XMLINPUT>
					<MA_DVI>' . $this->config->item("MIC_MA_DVI") . '</MA_DVI>
                    <NSD>' . $this->config->item("MIC_NSD") . '</NSD>
                    <PAS>' . $this->config->item("MIC_PAS") . '</PAS>
                    <NV>2BL</NV>
                    <KIEU_HD>G</KIEU_HD> 
                    <LOAI_XE>' . $loai_xe . '</LOAI_XE>
                    <NGAY_HL>' . $NGAY_HL . '</NGAY_HL>
                    <NGAY_KT>' . $NGAY_KT . '</NGAY_KT> 
                    <SO_CN>2</SO_CN>
                    <TL>' . (int)$muc_trach_nhiem . '</TL>
				  </XMLINPUT>
            ]]>  </ns1:xmlinput>
                  </ns1:ws_BPHI>
            ';
		$wsdl_url = $this->config->item("API_MIC") . '?wsdl';

		$timeout = microtime(true);
		$params = new SoapVar($originalXML, XSD_ANYXML);
//			var_dump($params ); die;
		$this->soapClient = new SoapClient($wsdl_url, array('soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => false, "connection_timeout" => 30));
		$this->soapClient->__setLocation($this->config->item("API_MIC"));
		$result = $this->soapClient->ws_BPHI($params);
		$xml = simplexml_load_string($result->ws_BPHIResult);
		$value = json_decode(json_encode($xml));
		if ($value->STATUS == TRUE) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'phi' => number_format($value->PHI),
				'message' => "thành công!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => !empty($value->ERRORINFO->ERRORDESC) ? $value->ERRORINFO->ERRORDESC : "Có lỗi xảy ra."
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	private function getGroupRole($userId)
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (empty($groupRole['users'])) continue;
			foreach ($groupRole['users'] as $item) {
				if (key($item) == $userId) {
					array_push($arr, $groupRole['slug']);
					continue;
				}
			}
		}
		return $arr;
	}

	private function getStores($userId)
	{
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleStores = array();
		if (count($roles) > 0) {
			foreach ($roles as $role) {
				if (!empty($role['users']) && count($role['users']) > 0) {
					$arrUsers = array();
					foreach ($role['users'] as $item) {
						array_push($arrUsers, key($item));
					}
					//Check userId in list key of $users
					if (in_array($userId, $arrUsers) == TRUE) {
						if (!empty($role['stores'])) {
							//Push store
							foreach ($role['stores'] as $key => $item) {
								array_push($roleStores, key($item));
							}
						}
					}
				}
			}
		}
		return $roleStores;
	}

	public function get_mic_tnds_accounting_transfe_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = [];
		$data = $this->input->post();
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}

		if (!$all) {
			// neu khong thuoc cac quyen all thi lay ra id cua store cua user hien tai
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'groupRoles' => array(),
					'total' => 0
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}
		$mic = $this->mic_tnds_model->get_mic_tnds_accounting_transfe($condition);
		$total_money = 0;
		foreach ($mic as $value) {
			$total_money += (int)$value['mic_fee'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic,
			"total_money" => $total_money
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function create_transaction_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code_mic = !empty($data['code']) ? $data['code'] : '';
		$store_id = !empty($data['store']) ? $data['store'] : '';
		$loai_khach = !empty($data['loai_khach']) ? $data['loai_khach'] : '';
		$storeUser = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($store_id)]);
		$store = $this->role_model->get_store_user((string)$this->id);
         $code_coupon = !empty($data['code_coupon']) ? $data['code_coupon'] : '';
		if (empty($store)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Bạn không phải nhân viên PGD"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($code_mic)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không có dữ liệu gửi sang kế toán"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$code = "PT_" . date('dmY') . '_' . uniqid();
		$money = 0;
		foreach ($code_mic as $value) {
			$mic_tnds = $this->mic_tnds_model->findOne(['mic_code' => $value]);
			$this->mic_tnds_model->update(['_id' => $mic_tnds['_id']], ['receipt_code' => $code, 'status' => 2]);
			$money += (int)$mic_tnds['mic_fee'];
		}
		$data_transaction = [
			'code' => $code,
			'total' => (string)$money,
			'payment_method' => "1",
			'store' => [
				'name' => $storeUser['name'],
				'id' => (string)$storeUser['_id']
			],
			"customer_bill_name" => $this->name,
			"customer_bill_phone" => $this->phone,
			'type' => 8,
			'status' => 2,
			'loai_khach' => $loai_khach,
			'code_coupon_cash' => $code_coupon,
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail
		];
		$id_transaction = $this->transaction_model->insertReturnId($data_transaction);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Gửi yêu cầu thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function detail_transaction_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code = !empty($data['code']) ? $data['code'] : '';
		$mic = $this->mic_tnds_model->find_where(['receipt_code' => $code]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_total_pay_post()
	{
		$data = $this->input->post();
		$code = !empty($data['code']) ? $data['code'] : '';
		$total = 0;
		foreach ($code as $value) {
			$mic = $this->mic_tnds_model->findOne(['mic_code' => $value]);
			$total += (int)$mic['mic_fee'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'total' => number_format($total) . " VND",
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_time_post()
	{
		$data = $this->input->post();
		$year = !empty($data['year']) ? $data['year'] : '';
		if ($year == '1') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+1 year"));
		} elseif ($year == '3') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+3 year"));
		} elseif ($year == '2') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+2 year"));
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'date' => $NGAY_KT,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function test_get_phi_mic_tnds_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$loai_xe = !empty($data['loai_xe']) ? $this->security->xss_clean($data['loai_xe']) : '';
		$muc_trach_nhiem = !empty($data['muc_trach_nhiem']) ? $this->security->xss_clean($data['muc_trach_nhiem']) : '';
		$effective_time = !empty($data['effective_time']) ? $this->security->xss_clean($data['effective_time']) : '';
		if ($effective_time == '1') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+1 year"));
		} elseif ($effective_time == '3') {
			$NGAY_HL = date('d/m/Y');
			$NGAY_KT = date('d/m/Y', strtotime("+3 year"));
		}
		$originalXML = '<ns1:ws_BPHI>
                     <!--Optional:-->
                     <ns1:xmlinput>
                     <![CDATA[
                   <XMLINPUT>
					<MA_DVI>' . $this->config->item("MIC_MA_DVI") . '</MA_DVI>
                    <NSD>' . $this->config->item("MIC_NSD") . '</NSD>
                    <PAS>' . $this->config->item("MIC_PAS") . '</PAS>
                    <NV>2BL</NV>
                    <KIEU_HD>G</KIEU_HD> 
                    <LOAI_XE>' . $loai_xe . '</LOAI_XE>
                    <NGAY_HL>' . $NGAY_HL . '</NGAY_HL>
                    <NGAY_KT>' . $NGAY_KT . '</NGAY_KT> 
                    <SO_CN>2</SO_CN>
                    <TL>' . (int)$muc_trach_nhiem . '</TL>
				  </XMLINPUT>
            ]]>  </ns1:xmlinput>
                  </ns1:ws_BPHI>
            ';
		$wsdl_url = $this->config->item("API_MIC") . '?wsdl';
		$params = new SoapVar($originalXML, XSD_ANYXML);
		echo "<pre>";
		print_r($params);
		echo "</pre>";
//			var_dump($params ); die;
		$this->soapClient = new SoapClient($wsdl_url, array('soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => false));
		$this->soapClient->__setLocation($this->config->item("API_MIC"));
		$result = $this->soapClient->ws_BPHI($params);
		echo "<pre>";
		print_r($result);
		echo "</pre>";
		$xml = simplexml_load_string($result->ws_BPHIResult);
		$value = json_decode(json_encode($xml));
		echo "<pre>";
		print_r($value);
		echo "</pre>";
		if ($value->STATUS == TRUE) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'phi' => number_format($value->PHI),
				'message' => "thành công!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => !empty($value->ERRORINFO->ERRORDESC) ? $value->ERRORINFO->ERRORDESC : "Có lỗi xảy ra."
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function check_store_tcv_dong_bac($id_pgd)
	{
		$role = $this->role_model->findOne(['slug' => 'cong-ty-cpcn-tcv-dong-bac']);
		$id_store = [];
		if (count($role['stores']) > 0) {
			foreach ($role['stores'] as $store) {
				foreach ($store as $key => $value) {
					$id_store[] = $key;
				}
			}
		}
		if (in_array($id_pgd, $id_store)) {
			return 'TCVĐB';
		}
		return 'TCV';
	}

	public function find_by_select($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$where['customer_info.customer_phone'] = $condition['lead_phone'];
		$where['type_pti'] = "BN";
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->select(['status'])
			->get($this->collection);
	}

}
