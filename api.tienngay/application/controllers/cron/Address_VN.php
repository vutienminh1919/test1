<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/NL_Withdraw.php';

use NguyenAry\VietnamAddressAPI\Address;
use GuzzleHttp\Client;

class Address_VN extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('province_model');
		$this->load->model('district_model');
		$this->load->model('ward_model');
		$this->load->model('log_addressVN_model');
		$this->load->model('pti_vta_bn_model');
		$this->load->model('contract_model');
		$this->load->model('fee_loan_model');
		$this->load->model('log_feeId_model');
		$this->load->model('log_corePTI_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());


	}

	public function update_address_vn()
	{

		//Lấy dữ liệu tỉnh, thành phố
		$ch = curl_init('https://provinces.open-api.vn/api/p/');

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$provinces = curl_exec($ch);
		$provinces_new = json_decode($provinces);

		//Lấy dữ liệu Quận, huyện
		$ch = curl_init('https://provinces.open-api.vn/api/d/');

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$districts = curl_exec($ch);
		$districts_new = json_decode($districts);

		//Lấy dữ liệu phường,xã
		$ch = curl_init('https://provinces.open-api.vn/api/w/');

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$wards = curl_exec($ch);
		$wards_new = json_decode($wards);

//		echo "<pre>";
//		print_r($districts_new);
//		echo "</pre>";
//		die();

		foreach ($provinces_new as $value) {
			if (!empty($value)) {

				if ($value->code < 10) {
					$code = "0" . $value->code;
				} else {
					$code = (string)$value->code;
				}

				$province = $this->province_model->findOne(array('code' => $code));

				if (empty($province)) {
					$data = [
						"name" => $value->name,
						"slug" => slugify($value->name),
						"type" => slugify($value->division_type),
						'name_with_type' => $value->name,
						'code' => $code,
						"status" => "active"
					];
					$this->province_model->insert($data);

					$log = [
						"type" => "province",
						"action" => "create",
						"code" => $code,
						"data" => $data,
						"created_at" => $this->createdAt,
					];
					$this->log_addressVN_model->insert($log);
				} else {

					$data = [
						"name" => $value->name,
						"slug" => slugify($value->name),
						"type" => slugify($value->division_type),
						'name_with_type' => $value->name,
						'code' => $code,
						"status" => "active"
					];

					$this->province_model->update(array("code" => $code), $data);

					$log = [
						"type" => "province",
						"action" => "update",
						"old" => $province,
						"new" => $data,
						"created_at" => $this->createdAt,
					];
					$this->log_addressVN_model->insert($log);

				}


			}
		}

		echo "Tỉnh/thành phố";

		foreach ($districts_new as $value) {

			if (!empty($value)) {

				if ($value->code < 10) {
					$code = "00" . $value->code;
				} else if ($value->code >= 10 && $value->code < 100) {
					$code = "0" . $value->code;
				} else {
					$code = (string)$value->code;
				}

				$district = $this->district_model->findOne(array('code' => $code));

				if ($value->province_code < 10) {
					$province_code = "0" . $value->province_code;
				} else {
					$province_code = (string)$value->province_code;
				}

				if (empty($district)) {

					$data = [
						"name" => $value->name,
						"slug" => slugify($value->name),
						"type" => slugify($value->division_type),
						'name_with_type' => $value->name,
						'path' => "",
						"path_with_type" => "",
						'code' => $code,
						"parent_code" => $province_code,
						"status" => "active"
					];
					$this->district_model->insert($data);

					$log = [
						"type" => "district",
						"action" => "create",
						"code" => $code,
						"data" => $data,
						"created_at" => $this->createdAt,
					];
					$this->log_addressVN_model->insert($log);
				} else {

					$data = [
						"name" => $value->name,
						"slug" => slugify($value->name),
						"type" => slugify($value->division_type),
						'name_with_type' => $value->name,
						'path' => !empty($district['path']) ? $district['path'] : "",
						"path_with_type" => !empty($district['path_with_type']) ? $district['path_with_type'] : "",
						'code' => $code,
						"parent_code" => $province_code,
						"status" => "active"
					];

					$this->district_model->update(array("code" => $code), $data);

					$log = [
						"type" => "district",
						"action" => "update",
						"old" => $district,
						"new" => $data,
						"created_at" => $this->createdAt,
					];
					$this->log_addressVN_model->insert($log);

				}

			}

		}

		echo "Quận/huyện";

		foreach ($wards_new as $value) {
			if (!empty($value)) {

				if ($value->code < 10) {
					$code = "0000" . $value->code;
				} else if ($value->code >= 10 && $value->code < 100) {
					$code = "000" . $value->code;
				} else if ($value->code >= 100 && $value->code < 1000) {
					$code = "00" . $value->code;
				} else if ($value->code >= 1000 && $value->code < 10000) {
					$code = "0" . $value->code;
				} else {
					$code = (string)$value->code;
				}

				$ward = $this->ward_model->findOne(array('code' => $code));

				if (empty($ward)) {
					$ward = $this->ward_model->findOne(array('code' => $value->code));
					if (!empty($ward)) {
						$code = (string)$value->code;
					}
				}

				if ($value->district_code < 10) {
					$district_code = "00" . $value->district_code;
				} else if ($value->district_code >= 10 && $value->district_code < 100) {
					$district_code = "0" . $value->district_code;
				} else {
					$district_code = (string)$value->district_code;
				}


				if (empty($ward)) {

					$data = [
						"name" => $value->name,
						"slug" => slugify($value->name),
						"type" => slugify($value->division_type),
						'name_with_type' => $value->name,
						'path' => "",
						"path_with_type" => "",
						'code' => $code,
						"parent_code" => $district_code,
						"status" => "active"
					];
					$this->ward_model->insert($data);

					$log = [
						"type" => "ward",
						"action" => "create",
						"code" => $code,
						"data" => $data,
						"created_at" => $this->createdAt,
					];
					$this->log_addressVN_model->insert($log);
				} else {

					$data = [
						"name" => $value->name,
						"slug" => slugify($value->name),
						"type" => slugify($value->division_type),
						'name_with_type' => $value->name,
						'path' => !empty($ward['path']) ? $ward['path'] : "",
						"path_with_type" => !empty($ward['path_with_type']) ? $ward['path_with_type'] : "",
						'code' => $code,
						"parent_code" => $district_code,
						"status" => "active"
					];

					$this->ward_model->update(array("code" => $code), $data);

					$log = [
						"type" => "ward",
						"action" => "update",
						"old" => $ward,
						"new" => $data,
						"created_at" => $this->createdAt,
					];
					$this->log_addressVN_model->insert($log);

				}

			}
		}

		echo "Phường/Xã";

	}

	public function update_fee_id_contract()
	{

		$contracts = $this->contract_model->find_contract_notFeeId();

		if (!empty($contracts)) {
			foreach ($contracts as $contract) {

				if (!empty($contract['loan_infor']['type_property']['code']) && $contract['loan_infor']['type_property']['code'] == "NĐ") {
					//type: bieu-phi-nha-dat
					$fee_id = $this->fee_loan_model->findOne(['type' => 'bieu-phi-nha-dat']);

					if (!empty($fee_id)) {
						$this->contract_model->update(["code_contract" => $contract['code_contract']], ['fee_id' => (string)$fee_id['_id']]);
					}
				} else {

					$fee_id = $this->fee_loan_model->findOne(array("status" => 'active', 'type' => ['$ne' => 'bieu-phi-nha-dat'], "from" => ['$lte' => (int)$contract['disbursement_date']], "to" => ['$gte' => (int)$contract['disbursement_date']]));

					$this->contract_model->update(["code_contract" => $contract['code_contract']], ['fee_id' => (string)$fee_id['_id']]);

				}

				$log = [
					'code_contract' => $contract['code_contract'],
					'old' => isset($contract['fee_id']) ? $contract['fee_id'] : "",
					'new' => [
						'fee_id' => (string)$fee_id['_id']
					] ,
					'create_at' => $this->createdAt
				];
				$this->log_feeId_model->insert($log);
			}
		}



		echo "ok";
	}

	public function cron_pushCorePTI()
	{
		$condition = [];
		$start = date('Y-m-d');
		$end = date('Y-m-d');
  //       $start = '2021-09-23';
		// $end = '2021-09-23';
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00' . " -2 day"),
				'end' => strtotime(trim($end) . ' 23:59:59'));
		}

		$ptis = $this->pti_vta_bn_model->list_pti($condition);


		if (!empty($ptis)) {
			foreach ($ptis as $value) {
				if (!empty($value)) {

					$gioi_tinh = "Khác";
					
					$ngay_sinh = date('d/m/Y', strtotime($value->request->ngay_sinh));

					$ngay_hl = date('d/m/Y', strtotime($value->request->ngay_hl));
					$ma_thue = "";
					if (!empty($value->request->bmathue)){
						$ma_thue = $value->request->bmathue;
					}
					$ngay_ht = date('Ymd', $this->createdAt);
					$ten = !empty($value->request->ten) ? $value->request->ten : "";
					

					$so_cmt = !empty($value->request->so_cmt) ? $value->request->so_cmt : "";
					$phone = !empty($value->request->phone) ? $value->request->phone : "";
					$email = !empty($value->request->email) ? $value->request->email : "";
					$so_thang_bh = !empty($value->request->so_thang_bh) ? $value->request->so_thang_bh : "";
					$quan_he = !empty($value->request->quan_he) ? $value->request->quan_he : "";
					$phi_bh = !empty($value->request->phi_bh) ? $value->request->phi_bh : "";
					$btendn = !empty($value->request->btendn) ? $value->request->btendn : "";
					$bphonedn = !empty($value->request->bphonedn) ? $value->request->bphonedn : "";
					$bemaildn = !empty($value->request->bemaildn) ? $value->request->bemaildn : "";
					$bdiachidn = !empty($value->request->bdiachidn) ? $value->request->bdiachidn : "";
                    
					$so_tc_gcn = !empty($value->pti_info->chung_thuc) ? $value->pti_info->chung_thuc : "";
                    if($value->type_pti=="HD")
                    {
                       $DCHI = $bdiachidn;
                       $goi = !empty($value->contract_info->loan_infor->bao_hiem_pti_vta->code_pti_vta) ? $value->contract_info->loan_infor->bao_hiem_pti_vta->code_pti_vta : "";
                       if (!empty($ptiVta->contract_info->customer_infor->customer_gender)) {
						if ($ptiVta->contract_info->customer_infor->customer_gender == "1"){
							$gioi_tinh = "Nam";
						}
						if ($ptiVta->contract_info->customer_infor->customer_gender == "2"){
							$gioi_tinh = "Nữ";
						}
					   }
                    }else {
                    	$goi = !empty($value->data_origin->sel_ql) ? $value->data_origin->sel_ql : "";
                    	$DCHI = !empty($value->customer_another_info->address_another) ? $value->customer_another_info->address_another : "";
                    	if (!empty($value->customer_another_info->gender_another)) {
						if ($value->customer_another_info->gender_another == "1"){
							$gioi_tinh = "Nam";
						}
						if ($value->customer_another_info->gender_another == "2"){
							$gioi_tinh = "Nữ";
						}
					}

                    }
					
					if ($goi == "G3"){
						$goi = "GOI3";
					} elseif ($goi == "G2"){
						$goi = "GOI2";
					} elseif ($goi == "G1"){
						$goi = "GOI1";
					}

					//format phí bảo hiểm
					$b = str_replace( ',', '', $phi_bh );
					if( is_numeric( $b ) ) {
						$phi_bh = $b;
					}


					$data_string = "{
'data': '{\\'TEN\\':\\'$ten\\',\\'DCHI\\':\\'$DCHI\\',\\'gioi\\':\\'$gioi_tinh\\',\\'NGAY_SINH\\':\\'$ngay_sinh\\',\\'SO_CMT\\':\\'$so_cmt\\',\\'PHONE\\':\\'$phone\\',\\'EMAIL\\':\\'$email\\',\\'ngay_hl\\':\\'$ngay_hl\\',\\'GOI\\':\\'$goi\\',\\'suc_khoe\\':\\'K\\',\\'SO_THANG_BH\\':\\'$so_thang_bh\\',\\'QHE\\':\\'BT\\',\\'ttoan\\':$phi_bh,\\'TEN_DN\\':\\'$btendn\\',\\'ma_thue\\':\\'$ma_thue\\',\\'PHONE_DN\\':\\'$bphonedn\\',\\'EMAIL_DN\\':\\'$bemaildn\\',\\'DCHI_DN\\':\\'$bdiachidn\\',\\'DVI_SL\\':\\'041\\',\\'kieu_hd\\':\\'G\\',\\'so_hd_g\\':\\'\\',\\'NGAY_HT\\':$ngay_ht,\\'ttrang\\':\\'T\\',\\'so_id\\':0,\\'so_tc_gcn\\':$so_tc_gcn,\\'so_id_kenh\\':271908129,\\'nv\\':\\'PVCOV\\'}'
}";

					//https://betaapikenhban.pti.com.vn - test
					//live - https://apikenhban.pti.com.vn/

					$client = new GuzzleHttp\Client(['base_uri' => $this->config->item('pti_url_doitac')]);

					$response = $client->request('POST', '/api/NGUOI/Fs_VCOVGCN_NHAP', [

						'headers' => [
							'UserName' => $this->config->item('PTI_USERNAME'),
							'Password' => $this->config->item('PTI_PASSWORD'),
							'SecretKey' => $this->config->item('PTI_SECRET'),
							'Channel' => 'TIENNGAY',
							'BlockCode' => ' ',
							'BranchUnit' => ' ',
							'Content-Type' => 'application/json'
						],
						'body' => $data_string
					]);
					$body = $response->getBody()->getContents();
					echo $body;

					$decode_body = json_decode($body, true);

					if (!empty($decode_body) && $decode_body['code'] == "000"){
						$this->pti_vta_bn_model->update(["_id" => new MongoDB\BSON\ObjectId($value->_id) ], ['push_core_pti' => "1"]);
					}

					$logs = [
						"pti_vta_bn_id" => (string)$value->_id,
						"request" => $data_string,
						"response" => $decode_body,
						"created_at" => $this->createdAt
					];
					$this->log_corePTI_model->insert($logs);
				}
			}
		}
	}





}
