<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

require_once APPPATH . 'libraries/BaoHiemPTI.php';

class Ctv_Tienngay extends REST_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->load->model("dashboard_model");
		$this->load->model("customer_billing_model");
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("storage_card_model");
		$this->load->model("group_role_model");
		$this->load->helper('lead_helper');
		$this->load->model('store_model');
		$this->load->model('collaborator_model');
		$this->load->model("lead_model");
		$this->load->model('log_lead_model');
		$this->load->model('log_lead_pgd_model');
		$this->load->model('contract_model');
		$this->load->model('pti_vta_bn_model');
		$this->load->model('gic_plt_bn_model');
		$this->load->model("vbi_utv_model");
		$this->load->model("vbi_sxh_model");
		$this->load->model('mic_tnds_model');
		$this->load->model('commission_setup_model');

		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$this->dataPost = $this->input->post();
		$headers = $this->input->request_headers();
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
				if ($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
					$this->uemail = $this->info['email'];
					// Get access right
					$roles = $this->role_model->getRoleByUserId((string)$this->id);
					$this->roleAccessRights = $roles['role_access_rights'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
		unset($this->dataPost['type']);
		date_default_timezone_set('Asia/Ho_Chi_Minh');
	}

	private $createdAt, $dataPost, $isTriple, $libraries, $flag_login, $id, $uemail, $ulang, $app_login, $superadmin;

	public function get_all_ctv_intro_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : '';
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : '';
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : '';
		$ctv_name = !empty($this->dataPost['ctv_name']) ? $this->dataPost['ctv_name'] : '';
		$ctv_phone = !empty($this->dataPost['ctv_phone']) ? $this->dataPost['ctv_phone'] : '';
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : '';
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : '';
		$condition = [];
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($ctv_name)) {
			$condition['ctv_name'] = $ctv_name;
		}
		if (!empty($ctv_phone)) {
			$condition['ctv_phone'] = $ctv_phone;
		}

		$collaborator = $this->collaborator_model->getAllCTVIntro($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total_tran = $this->collaborator_model->getAllCTVIntro($condition);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $collaborator,
			'total' => $total_tran,
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_order_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : '';
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : '';
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : '';
		$ctv_name = !empty($this->dataPost['ctv_name']) ? $this->dataPost['ctv_name'] : '';
		$ctv_phone = !empty($this->dataPost['ctv_phone']) ? $this->dataPost['ctv_phone'] : '';
		$lead_name = !empty($this->dataPost['lead_name']) ? $this->dataPost['lead_name'] : '';
		$lead_phone = !empty($this->dataPost['lead_phone']) ? $this->dataPost['lead_phone'] : '';
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$condition = [];
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 00:00:00')
			);
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($ctv_name)) {
			$condition['ctv_name'] = $ctv_name;
		}
		if (!empty($ctv_phone)) {
			$condition['ctv_phone'] = $ctv_phone;
		}
		if (!empty($lead_name)) {
			$condition['lead_name'] = $lead_name;
		}
		if (!empty($lead_phone)) {
			$condition['lead_phone'] = $lead_phone;
		}
		$leads = $this->lead_model->getAllOrderCtv($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total_tran = $this->lead_model->getAllOrderCtv($condition);
		if (!empty($leads)) {
			foreach ($leads as $lead) {
				$lead_phone = $lead['phone_number'];
				$type_finance = $lead['type_finance'];
				$condition['lead_phone'] = $lead_phone;
				if (!empty($type_finance) && $type_finance < "10") {
					$lead['dichvusanpham'] = "Hợp đồng vay";
					$lead['status_web'] = "Đang xử lý";
					$condition['phone_number'] = $lead['phone_number'];
					$contracts = $this->contract_model->find_by_select($condition);
					if (!empty($contracts)) {
						foreach ($contracts as $contract) {
							$status = $contract['status'];
							$lead['price'] = $contract['loan_infor']['amount_loan'];
							$lead['mahoahong'] = $contract['loan_infor']['type_loan']['text'];
							if (in_array($contract['loan_infor']['loan_product']['code'], ['16', '17'])) {
								if ($contract['loan_infor']['amount_loan'] > 200000000 ) {
									$lead['mahoahong'] = "nha-dat-2";
								} else {
									$lead['mahoahong'] = "nha-dat-1";
								}
							}
							if ($status == 3) {
								$lead['status_web'] = "Thất bại";
							} elseif ($status >= 17 && $status != 18 && $status != 35 && $status != 36) {
								$lead['status_web'] = "Thành công";
//								if ( in_array($contract['loan_infor']['loan_product']['code'], ['1', '2', '3', '10', '11'])) {
//									$this->lead_model->update(
//										['_id' => $contract['_id']],
//										[
//											'type_finance' => '4'
//										]
//									);
//								} elseif (in_array($contract['loan_infor']['loan_product']['code'], ['6', '7'])) {
//									$this->lead_model->update(
//										['_id' => $contract['_id']],
//										[
//											'type_finance' => '3'
//										]
//									);
//								} elseif (in_array($contract['loan_infor']['loan_product']['code'], ['4'])) {
//									$this->lead_model->update(
//										['_id' => $contract['_id']],
//										[
//											'type_finance' => '2'
//										]
//									);
//								} elseif (in_array($contract['loan_infor']['loan_product']['code'], ['5'])) {
//									$this->lead_model->update(
//										['_id' => $contract['_id']],
//										[
//											'type_finance' => '1'
//										]
//									);
//								} elseif (in_array($contract['loan_infor']['loan_product']['code'], ['18'])) {
//									$this->lead_model->update(
//										['_id' => $contract['_id']],
//										[
//											'type_finance' => '7'
//										]
//									);
//								} elseif (in_array($contract['loan_infor']['loan_product']['code'], ['16'])) {
//									$this->lead_model->update(
//										['_id' => $contract['_id']],
//										[
//											'type_finance' => '8'
//										]
//									);
//								} elseif (in_array($contract['loan_infor']['loan_product']['code'], ['17'])) {
//									$this->lead_model->update(
//										['_id' => $contract['_id']],
//										[
//											'type_finance' => '9'
//										]
//									);
//								} elseif (in_array($contract['loan_infor']['loan_product']['code'], ['14'])) {
//									$this->lead_model->update(
//										['_id' => $contract['_id']],
//										[
//											'type_finance' => '6'
//										]
//									);
//								}
							} else {
								$lead['status_web'] = "Đang xử lý";
							}
						}
					}
				}
				if (!empty($type_finance) && $type_finance == "10") {
					$lead["dichvusanpham"] = "Bảo hiểm Vững Tâm An";
					$lead['status_web'] = "Đang xử lý";
					$lead['mahoahong'] = "pti-vung-tam-an";
					$insurance_pti = $this->pti_vta_bn_model->find_by_select($condition);
					if (!empty($insurance_pti)) {
						foreach ($insurance_pti as $pti) {
							$lead['price'] = $pti['price'];
							if (!empty($pti['data_origin']['sel_ql']) && !empty($pti['data_origin']['sel_year'])) {
								$product_name_pti = "Bảo hiểm Vững Tâm An " . $pti['data_origin']['sel_ql'] . ' - ' . $pti['data_origin']['sel_year'];
							} else {
								$product_name_pti = "Bảo hiểm Vững Tâm An";
							}
							$lead["dichvusanpham"] = $product_name_pti;
							$lead->dichvusanpham = $product_name_pti;
							if ($pti['status'] == 1) {
								$lead['status_web'] = "Thành công";
							} elseif ($pti['status'] == 3) {
								$lead['status_web'] = "Thất bại";
							} else {
								$lead['status_web'] = "Đang xử lý";
							}
						}
					}
				}
				if (!empty($type_finance) && $type_finance == "11") {
					$lead["dichvusanpham"] = "Bảo hiểm Phúc Lộc Thọ";
					$lead['status_web'] = "Đang xử lý";
					$lead['mahoahong'] = "bh-phuc-loc-tho";
					$insurance_gic_plt = $this->gic_plt_bn_model->find_by_select($condition);
					if (!empty($insurance_gic_plt)) {
						foreach ($insurance_gic_plt as $plt) {
							$lead['price'] = $plt['price'];
							if (!empty($plt['request']['code_GIC_plt'])) {
								$product_name_plt = "Bảo hiểm Phúc Lộc Thọ - " . $plt['request']['code_GIC_plt'];
							} else {
								$product_name_plt = "Bảo hiểm Phúc Lộc Thọ";
							}
							$lead["dichvusanpham"] = $product_name_plt;
							if ($plt['status'] == 1) {
								$lead['status_web'] = "Thành công";
							} elseif ($plt['status'] == 3) {
								$lead['status_web'] = "Thất bại";
							} else {
								$lead['status_web'] = "Đang xử lý";
							}
						}
					}
				}
				if (!empty($type_finance) && $type_finance == "12") {
					$lead["dichvusanpham"] = "Bảo hiểm Ung thư vú";
					$lead['status_web'] = "Đang xử lý";
					$lead['mahoahong'] = "ung-thu-vu";
					$insurance_vbi_utv = $this->vbi_utv_model->find_by_select($condition);
					if (!empty($insurance_vbi_utv)) {
						foreach ($insurance_vbi_utv as $utv) {
							$lead['price'] = $utv['fee'];
							if (!empty($utv['goi_bh'])) {
								$product_name_utv = "Bảo hiểm Ung thư vú - " . $utv['goi_bh'];
							} else {
								$product_name_utv = "Bảo hiểm Ung thư vú";
							}
							$lead["dichvusanpham"] = $product_name_utv;
							if ($utv['status'] == 1) {
								$lead['status_web'] = "Thành công";
							} elseif ($utv['status'] == 3) {
								$lead['status_web'] = "Thất bại";
							} else {
								$lead['status_web'] = "Đang xử lý";
							}
						}
					}
				}
				if (!empty($type_finance) && $type_finance == "13") {
					$lead["dichvusanpham"] = "Bảo hiểm Sốt xuất huyết";
					$lead['status_web'] = "Đang xử lý";
					$lead['mahoahong'] = "sot-xuat-huyet";
					$insurance_vbi_sxh = $this->vbi_sxh_model->find_by_select($condition);
					if (!empty($insurance_vbi_sxh)) {
						foreach ($insurance_vbi_sxh as $sxh) {
							$lead['price'] = $sxh['fee'];
							if (!empty($sxh['goi_bh'])) {
								$product_name_sxh = "Bảo hiểm Sốt xuất huyết - " . $sxh['goi_bh'];
							} else {
								$product_name_sxh = "Bảo hiểm Sốt xuất huyết";
							}
							$lead["dichvusanpham"] = $product_name_sxh;
							if ($sxh['status'] == 1) {
								$lead['status_web'] = "Thành công";
							} elseif ($sxh['status'] == 3) {
								$lead['status_web'] = "Thất bại";
							} else {
								$lead['status_web'] = "Đang xử lý";
							}
						}
					}
				}
				if (!empty($type_finance) && $type_finance == "14") {
					$lead["dichvusanpham"] = "Bảo hiểm TNDS xe máy/ô tô";
					$lead['status_web'] = "Đang xử lý";
					$lead['mahoahong'] = "bh-tnds";
					$insurance_mic_tnds = $this->mic_tnds_model->find_by_select($condition);
					if (!empty($insurance_mic_tnds)) {
						foreach ($insurance_mic_tnds as $mic_tnds) {
							$lead['price'] = $mic_tnds['mic_fee'];
							if ($mic_tnds['status'] == 1) {
								$lead['status_web'] = "Thành công";
							} elseif ($mic_tnds['status'] == 3) {
								$lead['status_web'] = "Thất bại";
							} else {
								$lead['status_web'] = "Đang xử lý";
							}
						}
					}
				}
				$tien_hoa_hong = 0;
				$log_commission_setup = "";
				if (!empty($lead['mahoahong'])) {
					if (in_array($lead['mahoahong'], ["Cho vay", "Cầm cố", "nha-dat-2", "nha-dat-1"])) {
						$commission_setup = $this->commission_setup_model->findOne(['product_type.code' => 'KV', 'status' => 'active']);
						if ($lead['mahoahong'] == "Cho vay") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][1]['percent']/100;
						}
						if ($lead['mahoahong'] == "Cầm cố") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][0]['percent']/100;
						}
						if ($lead['mahoahong'] == "nha-dat-1") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][2]['percent']/100;
						}
						if ($lead['mahoahong'] == "nha-dat-2") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][3]['percent']/100;
						}
						$log_commission_setup = $commission_setup['_id'];
					}
					if (in_array($lead['mahoahong'], ["bh-phuc-loc-tho", "pti-vung-tam-an", "bh-tnds", "sot-xuat-huyet", "ung-thu-vu"])) {
						$commission_setup = $this->commission_setup_model->findOne(['product_type.code' => 'BH', 'status' => 'active']);
						if ($lead['mahoahong'] == "pti-vung-tam-an") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][0]['percent']/100;
						}
						if ($lead['mahoahong'] == "sot-xuat-huyet") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][1]['percent']/100;
						}
						if ($lead['mahoahong'] == "bh-tnds") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][4]['percent']/100;
						}
						if ($lead['mahoahong'] == "ung-thu-vu") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][2]['percent']/100;
						}
						if ($lead['mahoahong'] == "bh-phuc-loc-tho") {
							$tien_hoa_hong = $lead["price"] * $commission_setup['product_list'][5]['percent']/100;
						}
						$log_commission_setup = $commission_setup['_id'];
					}

				}
				if (!empty($lead['status_sale']) && $lead['status_sale'] == '19') {
					$lead['status_web'] = "Thất bại";
				}
				$arr_update = [
					"dichvusanpham" => $lead["dichvusanpham"],
					"status_web" => $lead["status_web"],
					"price" => !empty($lead["price"]) ? $lead["price"] : 0,
					'tien_hoa_hong' => !empty($tien_hoa_hong) ? $tien_hoa_hong : 0,
					'commission_setup_id' => !empty($log_commission_setup) ? $log_commission_setup : "",
				];
				$this->lead_model->update([
					"_id" => new MongoDB\BSON\ObjectId((string)$lead['_id'])
				], $arr_update);
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads,
			'total' => $total_tran,
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function create_product_type_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		//Count name
		$count = $this->main_property_model->count(array("name" => $data['name'], "status"=>"active"));
		if($count > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Name already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$parent_id = !empty($data['parent_id']) ? $data['parent_id'] : "";
		$property_name = !empty($data['name']) ? $data['name'] : "";
		$str_name = "";
		if(!empty($parent_id)){
			$main_property = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($parent_id)));
			$str_name = $main_property['str_name']." ".$property_name;
		}else{
			$str_name = $property_name;
		}
		$data['str_name'] = $str_name;
		unset($data['type_login']);
		$this->main_property_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create menu success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_ctv_by_group_post()
	{
//    $flag = notify_token($this->flag_login);
//    if ($flag == false) return;

		$ctv_group = $this->collaborator_model->getAllGroup();
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $ctv_group
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function test_post()
	{
		var_dump(111);
		die();
	}


}
