<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class QuickLoan extends REST_Controller{

	public function __construct(){
		parent::__construct();
		$this->load->model("transaction_model");
		$this->load->model('fee_loan_model');
		$this->load->model('contract_model');
		$this->load->model('quickLoan_model');
		$this->load->model('gic_model');
		$this->load->model('log_contract_model');
		$this->load->model('log_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model('config_gic_model');
		$this->load->model('city_gic_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('investor_model');
		$this->load->model("group_role_model");
		$this->load->model("notification_model");
		$this->load->model("store_model");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->flag_login = 1;
		$this->superadmin = false;
		$this->dataPost = $this->input->post();
		if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
			$headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
			$token = Authorization::validateToken($headers_item);
			if ($token != false) {
				// Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
				$this->app_login = array(
					'_id'=>new \MongoDB\BSON\ObjectId($token->id),
					'email'=>$token->email,
					"status" => "active",
					// "is_superadmin" => 1
				);
				//Web
				if($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1){
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

	}
	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;

	public function check_identify_post(){
		$identify = $this->security->xss_clean($this->dataPost['identify']);
		$contract = $this->quickloan_model->count(array('customer_infor.customer_identify'=>$identify));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function getFee(){
		$default = array(
			"percent_interest_customer" => 0,
			"percent_interest_investor" => 0,
			"percent_advisory" => 0,
			"percent_expertise" => 0,
			"penalty_percent" => 0,
			"penalty_amount" => 0,
			"extend" => 0,
			"percent_prepay_phase_1" => 0,
			"percent_prepay_phase_2" => 0,
			"percent_prepay_phase_3" => 0
		);
		$typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";
		$number_day_loan = !empty($this->dataPost['loan_infor']['number_day_loan']) ? $this->dataPost['loan_infor']['number_day_loan'] : "";
		//Get record by time
		$data = $this->fee_loan_model->findTop(array("from"=>array('$lt'=>$this->createdAt)),1);
		if(!empty($data)) $default = $data[0]['infor'][$number_day_loan][$typeLoan];
		$arrNew = array();
		foreach($default as $key=>$value) {
			$arrNew[$key] = (float)$value;
		}
		//Insert log
		$log = array(
			"type" => "contract",
			"action" => "fee_create_contract",
			"type_loan" => $typeLoan,
			"number_day_loan" => $number_day_loan,
			'fee' => $arrNew,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($log);

		return $arrNew;
	}

	public function bang_phi_vay_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$phi_vay = $this->fee_loan_model->find_where(array("status" => "active"));
		if(!empty($phi_vay)){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $phi_vay,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}else{
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function accountant_extension_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('ke-toan', $groupRoles) && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5e1edf2293bb072bd3adb3fb', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$inforDB = $this->contract_model->findOne(array("_id"=>new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])));
		if(empty($inforDB)) return;
		$count_extension = !empty($inforDB['count_extension']) ?  $inforDB['count_extension'] : 0;
		$origin_code_contract = !empty($inforDB['code_contract_parent']) ?  $inforDB['code_contract_parent'] :  $inforDB['code_contract_disbursement'];
		$inforDB['count_extension'] = $count_extension + 1;
		$inforDB['code_contract_parent'] = $origin_code_contract;

		$maxNumberContract = $this->initNumberContractCode();
		$inforDB['code_contract'] = "00000".$maxNumberContract['max_number_contract'];
		$inforDB['code_contract_disbursement'] = 'GIAHANLAN'.$inforDB['count_extension'].'/'.$origin_code_contract;
		// $inforDB['code_contract'] = 'GIAHANLAN'.$inforDB['count_extension'].$origin_code_contract;
		$inforDB['number_contract'] =  (int)$maxNumberContract['max_number_contract'];
		$inforDB['reason1'] =  !empty($inforDB['reason']) ?  $inforDB['reason'] : "";
		$inforDB['created_at'] =  $this->createdAt;
		$inforDB['status_disbursement'] =  2;
		$inforDB['updated_by'] =  $this->uemail;
		$inforDB['status'] =  17;

		$receiver_infor = $inforDB['receiver_infor'];
		$receiver_infor['order_code'] = 'GIAHANLAN'.$inforDB['count_extension'].'/'.$origin_code_contract;
		$inforDB['receiver_infor'] = $receiver_infor;

		$this->contract_model->update(
			array("_id" => $inforDB['_id']),
			array(
				"code_contract_child" => 'GIAHANLAN'.$inforDB['count_extension'].$origin_code_contract
			)
		);

		unset($inforDB['_id']);
		$contractId = $this->contract_model->insertReturnId($inforDB);
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "create_contract_extension",
			"contract_id" => (string)$contractId,
			"old" => $inforDB,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Extension contract success",
			'data' =>  $inforDB
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


	public function get_contract_chuyentd_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$inforDB = $this->contract_model->find_where(array("reminder_now" => "Call không còn khả năng tác động và đủ điều kiện chuyển qua thực địa"));
		if(empty($inforDB)) return;
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => " success",
			'data' =>  $inforDB
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_contract_extension_by_contractParent_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$inforDB = $this->contract_model->find_where(array("code_contract_parent" => $this->dataPost['id']));
		if(empty($inforDB)) return;
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => " success",
			'data' =>  $inforDB
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_approve_extension_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('ke-toan', $groupRoles) && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		$transaction_id = $this->security->xss_clean($this->dataPost['transaction_id']);
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['reason'] = $this->security->xss_clean($this->dataPost['reason']);
		$this->dataPost['status_contract'] = $this->security->xss_clean($this->dataPost['status_contract']);
		$this->dataPost['description_infor'] = $this->security->xss_clean(!empty($this->dataPost['description_infor']) ?  $this->dataPost['description_infor'] : array());

		//Insert log
		$log = array(
			"type" => "contract",
			"action" => "approve_extension",
			"contract_id" => $this->dataPost['id'],
			"reason" => $this->dataPost['reason'] ,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($log);
		$arr = array(
			"reason" => $this->dataPost['reason'] ,
			"status" =>  (int)$this->dataPost['status_contract'],
			"approve_extension_by" => $this->uemail
		);
		$this->contract_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
			$arr
		);
		foreach($this->dataPost['description_infor'] as $key => $value ){
			$key_img = !empty($value['key']) ? $value['key'] : "";
			$description = !empty($value['description']) ? $value['description'] : "";
			$path = !empty($value['path']) ? $value['path'] : "";

			$data1[$key_img] = array(
				'path' => $path,
				'description' => $description
			);
			// $dataDB['image_banking']['extension'] = (array)$dataDB['image_banking']['extension'];
			// $dataDB['image_banking']['extension'][$key_img] = $data1;
			//Update
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transaction_id)),
				array("image_banking.extension" => $data1)
			);



			// if(!empty($key_img)){
			//     $arrUpdate = array("image_accurecy.extension.".$key_img.".description" => $description);
			//     // $arrUpdate = array("image_accurecy.expertise.57b0ba45036f5a9d28e818b9d903c521f7c29617.description" => $description);
			//     $this->transaction_model->update(
			//         array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
			//         $arrUpdate
			//     );
			// }
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
			'aaa' =>  $this->dataPost['description_infor'],
			'id' => $transaction_id
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function process_update_description_img_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('ke-toan', $groupRoles) && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5def400868a3ff1204003ad9', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['expertise'] = $this->security->xss_clean(!empty($this->dataPost['expertise']) ?  $this->dataPost['expertise'] : array());

		// foreach($this->dataPost['description_infor'] as $key => $value ){
		//     $key_img = !empty($value['key']) ? $value['key'] : "";
		//     $description = !empty($value['description']) ? $value['description'] : "";
		//     if(!empty($key_img)){
		//         $arrUpdate = array("image_accurecy.expertise.".$key_img.".description" => $description);
		//         // $arrUpdate = array("image_accurecy.expertise.57b0ba45036f5a9d28e818b9d903c521f7c29617.description" => $description);
		//         $this->contract_model->update(
		//             array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
		//             $arrUpdate
		//         );
		//     }
		// }

		$arrUpdate = array("image_accurecy.expertise" => $this->dataPost['expertise']);
		$this->contract_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
			$arrUpdate
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
			'aaa' =>  $this->dataPost['description_infor']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}
	public function process_update_fee_post(){

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5def17f668a3ff1204003ad7', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$data['percent_advisory'] = $this->security->xss_clean($data['percent_advisory']);
		$data['percent_expertise'] = $this->security->xss_clean($data['percent_expertise']);
		$data['note'] = $this->security->xss_clean($data['note']);

		$inforDB = $this->contract_model->findOne(array("_id"=>new MongoDB\BSON\ObjectId($data['id'])));
		if(empty($inforDB)) return;
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "update_fee",
			"contract_id" => (string)$data['id'],
			"old" => $data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		//Update contract model
		$this->contract_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($data['id'])),
			array(
				"fee.percent_advisory" => floatval($data['percent_advisory']),
				"fee.percent_expertise" => floatval($data['percent_expertise'])
			)
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update fee success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function process_update_disbursement_contract_post(){

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}


		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['created_at'] = $this->createdAt;
		$inforDB = $this->contract_model->findOne(array("_id"=>new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if(empty($inforDB)) return;
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "update",
			"contract_id" => (string)$this->dataPost['id'],
			"old" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		//Update contract model
		$this->dataPost['created_at'] = $this->createdAt;
		unset($this->dataPost['id']);
		$this->contract_model->update(
			array("_id" => $inforDB['_id']),
			$this->dataPost
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	//tạo hợp đồng trên gic
	public function insert_gic($data,$code_contract) {
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$branch_id_gic=$this->config->item("branch_id_gic");
		$city = $this->city_gic_model->findOne(array('code'=>'GIC'));
		$config = $this->config_gic_model->findOne(array('code'=>'TN_TNNNV'));
		$so_thang_tham_gia_bh=(!empty($data->loan_infor->number_day_loan)) ? (int)$data->loan_infor->number_day_loan/30 : 0;
		$NoiDungBaoHiem_SoHdTinDungKv=(!empty($code_contract)) ? $code_contract : "";
		$TyLeKhoanVay=(!empty($config['TyLeKhoanVay'])) ? $config['TyLeKhoanVay'] : 0;
		$GiaTriKhoanVay=(!empty($data['loan_infor']['amount_money'])) ? $data['loan_infor']['amount_money'] : 0;
		$NgayYeuCauBh=date('Y-m-d H:i:s');
		$NgayHieuLucBaoHiem=date('Y-m-d H:i:s');
		$NgayHieuLucBaoHiemDen=date('Y-m-d H:i:s', strtotime($NgayHieuLucBaoHiem. ' + '.$so_thang_tham_gia_bh.' day'));
		$customer_name=(!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
		$customer_BOD=(!empty( $data['customer_infor']['customer_BOD'])) ? $data['customer_infor']['customer_BOD'] : '';
		$customer_identify=(!empty( $data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
		$name_investor=(!empty( $data['investor_infor']['name'])) ? $data['investor_infor']['name'] : '.';
		$current_address=(!empty( $data['houseHold_address']['ward_name'])) ? $data['houseHold_address']['address_household'].' - '.$data['houseHold_address']['ward_name']  : '.....';
		$province=(!empty( $data['houseHold_address']['province_name'])) ? $data['houseHold_address']['province_name']: '';
		$district=(!empty( $data['houseHold_address']['district_name'])) ? $data['houseHold_address']['district_name'] : '';
		$customer_phone_number=(!empty( $data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';
		$customer_email=(!empty( $data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
		$customer_gender=(!empty( $data['customer_infor']['customer_gender'])) ? $data['customer_infor']['customer_gender'] : '1';
		$customer_gender=($customer_gender=='1') ? 'dbb6424f-3890-4108-a094-3a17884885f3' : '27541417-9bf3-4b96-8bd2-edb4b8cf352a';
		$ProvinceId='5c3b316f-91ad-46fc-a26c-8331be3b7739';
		$DistrictId='5c3b316f-91ad-46fc-a26c-8331be3b7739';

		if(!empty($city['city']))
		{
			foreach ($city['city'] as $key => $value) {

				if($this->slugify($value['name'])==$this->slugify($province))
				{
					$ProvinceId=$value['id'];
				}
			}
		}
		$name="";
		if(!empty($city['district']))
		{

			foreach ($city['district'] as $key => $value) {
				$name=$this->slugify(str_replace("Huyện ","",$value->name));
				$name=$this->slugify(str_replace("thi-xa-","",$name));
				$name=$this->slugify(str_replace("thanh-pho-","",$name));
				$name=$this->slugify(str_replace("quan-","",$name));

				if($this->slugify($name)==$this->slugify($district))
				{
					$DistrictId=$value['id'];
				}
			}
		}

		$dt_gic=array(
			'thongTinChung_NhanVienId'=>$config['NhanVienId']
		,'ThongTinNguoiDuocBaoHiem_CaNhan_NgaySinh'=>$customer_BOD
		,'ThongTinNguoiDuocBaoHiem_CaNhan_SoCMND'=>$customer_identify
		,'NoiDungBaoHiem_NgayHieuLucBaoHiemDen'=>$NgayHieuLucBaoHiemDen
		,'ThongTinNguoiDuocBaoHiem_CaNhan_Ten'=>$customer_name
		,'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_ProvinceId'=>$ProvinceId
		,'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_DistrictId'=>$DistrictId
		,'NoiDungBaoHiem_NgayHieuLucBaoHiem'=>$NgayHieuLucBaoHiem
		,'NoiDungBaoHiem_GiaTriKhoanVay'=>$GiaTriKhoanVay
		,'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi'=>$current_address
		,'ThongTinNguoiDuocBaoHiem_CaNhan_SoDienThoai'=>$customer_phone_number
		,'ThongTinNguoiDuocBaoHiem_CaNhan_Email'=>$customer_email
		,'NoiDungBaoHiem_SoHdTinDungKv'=>$NoiDungBaoHiem_SoHdTinDungKv
		,'NoiDungBaoHiem_NgayYeuCauBh'=>$NgayYeuCauBh
		,'ThongTinNguoiDuocBaoHiem_CaNhan_GioiTinhId'=>$customer_gender
		,'ThongTinNguoiChoVay_HoTen'=>$config['ThongTinNguoiChoVay_HoTen']
		,'ThongTinNguoiChoVay_CMND'=>$config['ThongTinNguoiChoVay_CMND']
		,'ThongTinNguoiChoVay_DienThoai'=>$config['ThongTinNguoiChoVay_DienThoai']
		,'ThongTinNguoiChoVay_Email'=>$config['ThongTinNguoiChoVay_Email']
		,'ThongTinNguoiChoVay_DiaChi'=>$config['ThongTinNguoiChoVay_DiaChi']
		,'Branchid'=>$branch_id_gic
		,'productId'=>$config['id']

		);
		// return  $province;
		$message='';
		// return $dt_gic;
		$res= $this->push_api_gci('SaveProductDetail','',json_encode($dt_gic));
		// return $res;
		// var_dump($res->errors['Thongtinchung_Index']);
		if(!empty($res->errors->Thongtinchung_Index[0])){$message='Thông tin Index không chính xác ';}
		if(!empty($res->errors->ThongTinChung_TrangThaiHdId[0])){$message='Thông tin trạng thái hợp đồng không chính xác ';}
		if(!empty($res->errors->ThongTinChung_SoHopDong[0])){$message='Thông tin số hợp đồng không chính xác ';}
		if(!empty($res->errors->ThongTinChung_ThoiGianGuiMailSms[0])){$message='Thông tin thời gian gửi mail không chính xác ';}
		if(!empty($res->errors->ThongTinChung_ChiNhanhId[0])){$message='Thông tin chi nhánh không chính xác ';}
		if(!empty($res->errors->ThongTinChung_TenNhanVienBanHang[0])){$message='Thông tin tên nhân viên bán hàng không chính xác ';}
		if(!empty($res->errors->ThongTinChung_EmailNhanVien[0])){$message='Thông tin Email nhân viên không chính xác ';}
		if(!empty($res->errors->ThongTinChung_DienThoaiNhanVien[0])){$message='Thông tin điện thoại nhân viên không chính xác ';}
		if(!empty($res->errors->ThongTinChung_MaNhanVien[0])){$message='Thông tin mã nhân viên không chính xác ';}
		if(!empty($res->errors->ThongTinChung_TenNhanVienGIC[0])){$message='Thông tin tên nhân viên GIC không chính xác ';}
		if(!empty($res->errors->ThongTinChung_EmailNhanVienGIC[0])){$message='Thông tin email nhân viên GIC không chính xác ';}
		if(!empty($res->errors->ThongTinChung_DienThoaiNhanVienGIC[0])){$message='Thông tin điện thoại nhân viên GIC không chính xác ';}
		if(!empty($res->errors->ThongTinChung_MaNhanVienGIC[0])){$message='Thông tin mã nhân viên GIC không chính xác ';}
		if(!empty($res->errors->ThongTinChung_MissCodeNhanVienBanHang[0])){$message='Thông tin code nhân viên bán hàng không chính xác ';}
		if(!empty($res->errors->ThongTinChung_MaDonViCuaChiNhanhDoiTac[0])){$message='Thông tin mã đơn vị chi nhánh đối tác không chính xác ';}
		if(!empty($res->errors->ThongTinHoaDon_IBMS[0])){$message='Thông tin hóa đơn IBMS không chính xác ';}
		if(!empty($res->errors->ThongTinHoaDon_Core[0])){$message='Thông tin hó đơn codre không chính xác ';}
		if(!empty($res->errors->ThongTinHoaDon_SoHoaDon[0])){$message='Thông tin số hóa đơn không chính xác ';}
		if(!empty($res->errors->ThongTinHoaDon_MaSoBiMat[0])){$message='Thông tin Mã số bí mật không chính xác ';}
		if(!empty($res->errors->ThongTinHoaDon_TenSPTrenHopDong[0])){$message='Thông tin tên SPT hợp đồng không chính xác ';}
		if(!empty($res->errors->ThongTinHoaDon_MST[0])){$message='Thông tin hóa đơn MST không chính xác ';}
		if(!empty($res->errors->ThongTinHoaDon_LinkHoaDon[0])){$message='Thông tin link hóa đơn không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_Ten[0])){$message='Thông tin tên khách hàng không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_GioiTinhId[0])){$message='Thông tin giới tính khách hàng không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_NgaySinh[0])){$message='Thông tin ngày sinh khách hàng không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_SoCMND[0])){$message='Thông tin số CMND khách hàng không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_Email[0])){$message='Thông tin email khách hàng không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_SoDienThoai[0])){$message='Thông tin số điện thoại khách hàng không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi[0])){$message='Thông tin địa chỉ khách hàng không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_SoHdTinDungKv[0])){$message='Thông tin số hợp đồng tín dụng không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_TyLeKhoanVay[0])){$message='Thông tin tỉ lệ khoản vay không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_GiaTriKhoanVay[0])){$message='Thông tin giá trị khoản vay không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_TyLePhi[0])){$message='Thông tin tỉ lệ phí không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_SoTienBaoHiem[0])){$message='Thông tin số tiền bảo hiểm không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem_VAT[0])){$message='Thông tin phí bảo hiểm VAT không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_Thue_VAT[0])){$message='Thông tin thuế VAT không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem[0])){$message='Thông tin phí bảo hiểm không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_NgayYeuCauBh[0])){$message='Thông tin ngày yêu cầu bảo hiểm không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_NgayHieuLucBaoHiem[0])){$message='Thông tin ngày hiệu lực bảo hiểm không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_NgayHieuLucBaoHiemDen[0])){$message='Thông tin ngày hiệu lực bảo hiểm đến không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_SoThangThamGiaBh[0])){$message='Thông tin số tháng tham gia bảo hiểm không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_NgayHuyHd[0])){$message='Thông tin ngày hủy hợp đồng không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_SoTienHoanKhach[0])){$message='Thông tin số tiền hoàn khách không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_NgayDuyet[0])){$message='Thông tin ngày duyệt không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_TyLeHoaHong[0])){$message='Thông tin tỷ lệ hoa hồng không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_TyLeHoTroDaiLy[0])){$message='Thông tin tỉ lệ hỗ trợ đại lý không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_TyLePhiDichVu[0])){$message='Thông tin tỷ lệ phí dịch vụ không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_HoaHong[0])){$message='Thông tin hoa hồng không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_HoTroDaiLy[0])){$message='Thông tin hỗ trợ đại lý không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_PhiDichVu[0])){$message='Thông tin phí dịch vụ không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_PhiNet[0])){$message='Thông tin phí net không chính xác ';}
		if(!empty($res->errors->NoiDungBaoHiem_LyDoHuyHd[0])){$message='Thông tin lý do hủy hợp đồng không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiChoVay_HoTen[0])){$message='Thông tin họ tên nhà đầu tư không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiChoVay_CMND[0])){$message='Thông tin CMND đầu tư không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiChoVay_DiaChi[0])){$message='Thông tin địa chỉnhà đầu tư không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiChoVay_Email[0])){$message='Thông tin email nhà đầu tư không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiChoVay_DienThoai[0])){$message='Thông tin điện thoại nhà đầu tư không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_DistrictId[0])){$message='Thông tin địa chỉ nhà đầu tư không chính xác ';}
		if(!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_ProvinceId[0])){$message='Thông tin địa chỉ nhà đầu tư không chính xác ';}
		if(!empty($res->messages) && is_array($res->messages))
		{

			foreach ($res->messages as $key => $value) {
				$message= $value->message.' - '.$value->code;
			}
		}
		if(isset($res->success))
		{
			if(!$res->success)
			{
				$dt_re=array(
					'message'=>$message,
					'success'=>false
				);
				return  json_decode(json_encode($dt_re));
			}else{
				return $res;
			}
		}else{
			$dt_re=array(
				'message'=>$message,
				'success'=>false
			);
			return  json_decode(json_encode($dt_re));
		}
		// return $this->slugify($province;
		//return $data;
	}
	private function push_api_gci($action='',$get='',$data_post=[]){
		$url_gic=$this->config->item("url_gic");
		$accessKey=$this->config->item("access_key_gic");
		$service = $url_gic.'/api/PublicApi/'.$action.'?accessKey='.$accessKey.$get;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$service);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec ($ch);
		curl_close ($ch);
		$result1 = json_decode($result);
		return $result1;
	}
	public function process_save_contract_post() {

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
		$this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
		$this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
		$this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
		$this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
		$this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
		$this->dataPost['property_infor'] = $this->security->xss_clean(!empty($this->dataPost['property_infor']) ?  $this->dataPost['property_infor'] : array());
		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
		$this->dataPost['step'] = $this->security->xss_clean($this->dataPost['step']);



		// //Init mã hợp đồng
		// $resCodeContract = $this->initContractCode();
		// //Insert contract model
		// $this->dataPost['code_contract'] = $resCodeContract['code_contract'];
		// $this->dataPost['receiver_infor']['order_code'] = $resCodeContract['code_contract'];
		// $this->dataPost['number_contract'] = $resCodeContract['max_number_contract'] + 1;
		$this->dataPost['status'] = 0;
		$arrImages = array(
			"identify" => "",
			"household" => "",
			"driver_license" => "",
			"vehicle" => "",
			"expertise" => ""
		);
		$this->dataPost['image_accurecy'] = $arrImages;

		$arrFee = $this->getFee();
		$this->dataPost['fee'] = $arrFee;
		$this->dataPost['created_at'] = $this->createdAt;

		$contractId = $this->contract_model->insertReturnId($this->dataPost);
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "save",
			"contract_id" => (string)$contractId,
			"old" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new contract success",
			"data" => $this->dataPost
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_create_contract_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		$this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
		$this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
		$this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
		$this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
		$this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
		$this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
		$this->dataPost['property_infor'] = $this->security->xss_clean($this->dataPost['property_infor']);
		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);

		//Check null
		$checkNull = $this->checkNull();
		if($checkNull['status'] != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				// 'message' => $checkNull['message'],
				'data' => $checkNull
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		//If new customer then create account for customer
		$sendEmail = $this->checkSendEmailForNewCustomer();

		if($sendEmail['status'] != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $sendEmail['message']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Init mã hợp đồng
		$typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
		$typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";

		// $resCodeContract = $this->initContractCode($this->dataPost['store']['id'],$typeProperty,$typeLoan);
		// $this->dataPost['code_contract'] = $resCodeContract['code_contract'];
		// $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
		// $this->dataPost['receiver_infor']['order_code'] = $resCodeContract['code_contract'];

		//Init code_contract_number hợp đồng
		$resNumberCodeContract = $this->initNumberContractCode();
		//Insert contract model
		$this->dataPost['code_contract'] = "00000".$resNumberCodeContract['max_number_contract'];
		$this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
		$this->dataPost['status'] = 1;
		$this->dataPost['store']['object_id'] = new MongoDB\BSON\ObjectId($this->dataPost['store']['id']);

		$arrImages = array(
			"identify" => "",
			"household" => "",
			"driver_license" => "",
			"vehicle" => "",
			"expertise" => ""
		);
		$this->dataPost['image_accurecy'] = $arrImages;
		$arrFee = $this->getFee();
		$this->dataPost['fee'] = $arrFee;
		$this->dataPost['created_at'] = $this->createdAt;
		$contractId = $this->contract_model->insertReturnId($this->dataPost);
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "create",
			"contract_id" => (string)$contractId,
			"old" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new contract success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_create_contract_noheader_post() {

		$this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
		$this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
		$this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['image_accurecy'] = $this->security->xss_clean($this->dataPost['image_accurecy']);
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);;

		//Init mã hợp đồng
		$typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
		$typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";

		//Init code_contract_number hợp đồng
		$resNumberCodeContract = $this->initNumberContractCode();
		//Insert contract model
		$this->dataPost['code_contract'] = "00000".$resNumberCodeContract['max_number_contract'];
		$this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
		$this->dataPost['status'] = 5;
		$this->dataPost['type'] = "vaynhanh";
		$this->dataPost['created_at'] = $this->createdAt;
		var_dump();die($this->dataPost);
		$contractId = $this->contract_model->insertReturnId($this->dataPost);
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "create",
			"contract_id" => (string)$contractId,
			"old" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->dataPost['created_by']
		);
		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new contract success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_continue_create_contract_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
		$this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
		$this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
		$this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
		$this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
		$this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
		// $this->dataPost['property_infor'] = $this->security->xss_clean($this->dataPost['property_infor']);
		$this->dataPost['property_infor'] = $this->security->xss_clean(!empty($this->dataPost['property_infor']) ?  $this->dataPost['property_infor'] : array());
		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
		$inforDB = $this->contract_model->findOne(array("_id"=>new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if(empty($inforDB)) return;
		//Check null
		$checkNull = $this->checkNull();
		if($checkNull['status'] != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $checkNull['message']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "update",
			"contract_id" => $this->dataPost['id'],
			"old" => $inforDB,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		//Update contract model
		unset($this->dataPost['id']);
		//Init mã hợp đồng

		$typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
		$typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";

		// $resCodeContract = $this->initContractCode($this->dataPost['store']['id'],$typeProperty,$typeLoan);
		// $this->dataPost['code_contract'] = $resCodeContract['code_contract'];
		// $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
		// $this->dataPost['receiver_infor']['order_code'] = $resCodeContract['code_contract'];

		//Insert contract model
		$resNumberCodeContract = $this->initNumberContractCode();
		//Insert contract model
		$this->dataPost['code_contract'] = "00000".$resNumberCodeContract['max_number_contract'];
		$this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
		$this->dataPost['status'] = 1;
		$this->dataPost['created_at'] = $this->createdAt;
		$this->contract_model->update(
			array("_id" => $inforDB['_id']),
			$this->dataPost
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create contract success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_contract_continue_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
		$this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
		$this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
		$this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
		$this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
		$this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
		$this->dataPost['property_infor'] = $this->security->xss_clean(!empty($this->dataPost['property_infor']) ?  $this->dataPost['property_infor'] : array());
		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
		$inforDB = $this->contract_model->findOne(array("_id"=>new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if(empty($inforDB)) return;
		// //Check null
		// $checkNull = $this->checkNull();
		// if($checkNull['status'] != 1) {
		//     $response = array(
		//         'status' => REST_Controller::HTTP_UNAUTHORIZED,
		//         'message' => $checkNull['message']
		//     );
		//     $this->set_response($response, REST_Controller::HTTP_OK);
		//     return;
		// }
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "update_continue",
			"contract_id" => $this->dataPost['id'],
			"old" => $inforDB,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		//Update contract model
		$this->dataPost['updated_by'] = $this->createdAt;
		unset($this->dataPost['id']);
		$this->contract_model->update(
			array("_id" => $inforDB['_id']),
			$this->dataPost
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_contract_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
		$this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
		$this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
		$this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
		$this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
		$this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
		$this->dataPost['property_infor'] = $this->security->xss_clean($this->dataPost['property_infor']);
		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
		$inforDB = $this->contract_model->findOne(array("_id"=>new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if(empty($inforDB)) return;
		//Check null
		$checkNull = $this->checkNull();
		if($checkNull['status'] != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $checkNull['message']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "update",
			"contract_id" => $this->dataPost['id'],
			"old" => $inforDB,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		//Update contract model
		$this->dataPost['updated_at'] = $this->createdAt;
		unset($this->dataPost['id']);
		$this->contract_model->update(
			array("_id" => $inforDB['_id']),
			$this->dataPost
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5def17f668a3ff1204003ad7', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$count = $this->contract_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		// $this->log_property($data);
		unset($data['id']);
		//$data
		$this->contract_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_relative_infor_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$id = !empty($data['contract_id']) ? $data['contract_id'] : "";
		$phone1 = !empty($data['phone_1']) ? $data['phone_1'] : "";
		$phone2 = !empty($data['phone_2']) ? $data['phone_2'] : "";
		$address_1 = !empty($data['address_1']) ? $data['address_1'] : "";
		$address_2 = !empty($data['address_2']) ? $data['address_2'] : "";
		$contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if(empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		// $this->log_property($data);
		$relative_infor = $contract['relative_infor'];
		$relative_infor['phone_number_relative_1'] = $phone1;
		$relative_infor['phone_number_relative_2'] = $phone2;
		$relative_infor['hoursehold_relative_1'] = $address_1;
		$relative_infor['hoursehold_relative_2'] = $address_2;
		//$data
		$update = array(
			"relative_infor" => $relative_infor,
			"updated_at" => $this->createdAt,
			"updated_by" => $this->uemail,
		);
		$this->contract_model->findOneAndUpdate(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$update
		);
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "update_info",
			"contract_id" => $id,
			"old" => $contract,
			"new" =>  $data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_code_contract_post(){
		$code_contract = $this->dataPost['code_contract'];
		// $contract = $this->contract_model->find_one_select(array("code_contract" => $code_contract), array("_id", "status", "code_contract", "created_by", "store","receiver_infor"));
		$contract = $this->contract_model->find_one_select(array("code_contract" => $code_contract), array("_id", "status", "code_contract", "created_by", "store","receiver_infor","loan_infor","customer_infor","investor_infor","current_address","houseHold_address"));
		$typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
		$typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
		$resCodeContract = $this->initContractCode($contract['store']['id'],$typeProperty,$typeLoan);
		$this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
		$receiver_infor = $contract['receiver_infor'];
		$receiver_infor['order_code'] = $resCodeContract['code_contract'];
		$this->dataPost['receiver_infor'] = $receiver_infor;
		$this->dataPost['status'] = 16;
		$store=!empty($contract['store']) ? $contract['store'] : "";
		if($contract['loan_infor']['insurrance_contract'] =='1')
		{

			$gic = $this->gic_model->findOne(array("code_contract_disbursement" =>$resCodeContract['code_contract']));
			if(empty($gic)) {
				$gic=  $this->insert_gic($contract,$resCodeContract['code_contract']);
				if($gic->success!=true)
				{
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'data' => $gic->success,
						'message' => $gic->message
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}else{
					$gic= $gic->data;
					$dt_gic=array(
						'contract_id'=> (string)$contract['_id']
					,'code_contract_disbursement'=> $resCodeContract['code_contract']
					,'gic_code'=>$gic->thongTinChung_SoHopDong
					,'gic_id'=>$gic->id
					,'contract_info'=>$contract
					,'gic_info'=>$gic
					,'status_sms'=>'0'
					,'status_email'=>'0'
					,'store'=> $store
					,'status'=>'0'
					,'erro_info'=>'-'
					,'created_at'=>$this->createdAt
					,'created_by' => "superadmin"

					);
					$this->gic_model->insert($dt_gic);
				}
			}else{
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => "",
					'message' =>"Hợp đồng đã có bảo hiểm"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		$this->contract_model->update(
			array("code_contract" => $code_contract),
			$this->dataPost
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function accountant_investors_disbursement_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->getGroupRole($this->id);
		if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
			// Check access right
			if(!in_array('5def15a268a3ff1204003ad6', $this->roleAccessRights)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => array(
						"message" => "No have access right"
					)
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		// Check old status
		$contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])), array("_id", "status", "code_contract", "created_by", "store","receiver_infor","loan_infor","customer_infor","investor_infor","current_address","houseHold_address"));
		if(empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$contract_id = $this->dataPost['contract_id'];
		$typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
		$typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
		$resCodeContract = $this->initContractCode($contract['store']['id'],$typeProperty,$typeLoan);
		$this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
		$store=!empty($contract['store']) ? $contract['store'] : "";
		// $this->dataPost['receiver_infor']['order_code'] = $resCodeContract['code_contract'];
		$receiver_infor = $contract['receiver_infor'];
		$receiver_infor['order_code'] = $resCodeContract['code_contract'];
		$this->dataPost['receiver_infor'] = $receiver_infor;

		// $this->log_property($data);
		$percent_interest_investor = floatval($this->dataPost['percent_interest_investor']);
		$this->dataPost['status'] = 17;
		$this->dataPost['status_disbursement'] = 2;
		$this->dataPost['disbursement_date'] = $this->createdAt;

		$autoDisburseMent = $this->contract_model->getCodeAutoDisburseMent();
		$this->dataPost['max_code_auto_disbursement'] = !empty($autoDisburseMent['max_code_auto_disbursement']) ? $autoDisburseMent['max_code_auto_disbursement'] : "" ;
		$this->dataPost['code_auto_disbursement'] = !empty($autoDisburseMent['code_auto_disbursement']) ? $autoDisburseMent['code_auto_disbursement'] : "";

		unset($this->dataPost['contract_id']);
		unset($this->dataPost['percent_interest_investor']);

		if($contract['loan_infor']['insurrance_contract'] =='1')
		{
			$gic = $this->gic_model->findOne(array("code_contract_disbursement" => $this->dataPost['code_contract_disbursement']));
			if(empty($gic)) {
				$gic=  $this->insert_gic($contract,$this->dataPost['code_contract_disbursement']);
				// $this->set_response($gic, REST_Controller::HTTP_OK);
				//   return ;
				if($gic->success!=true)
				{
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'data' => $gic->success,
						'message' => $gic->message
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}else{
					$gic= $gic->data;
					$dt_gic=array(
						'contract_id'=>(string)$contract['_id']
					,'code_contract_disbursement'=> $this->dataPost['code_contract_disbursement']
					,'gic_code'=>$gic->thongTinChung_SoHopDong
					,'gic_id'=>$gic->id
					,'contract_info'=>$contract
					,'gic_info'=>$gic
					,'status_sms'=>'0'
					,'status_email'=>'0'
					,'store'=> $store
					,'status'=>'0'
					,'erro_info'=>'-'
					,'created_at'=>$this->createdAt
					,'created_by' => "superadmin"

					);
					$this->gic_model->insert($dt_gic);
				}
			}else{
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => "",
					'message' =>"Hợp đồng đã có bảo hiểm"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		//$data
		$this->contract_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($contract_id)),
			array("fee.percent_interest_investor" => $percent_interest_investor)
		);
		$this->contract_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($contract_id)),
			$this->dataPost
		);
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "accountant_investors_disbursement",
			"contract_id" => $contract_id,
			"old" => $contract,
			"new" =>  $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
			"data" =>  $this->dataPost
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_for_quickloan_post(){
		$condition['type'] = "vaynhanh";
		$contract = $this->contract_model->find_where($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_quickloan_search_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start).' 00:00:00'),
				'end' => strtotime(trim($end).' 23:59:59')
			);
		}
		$condition['type'] = "vaynhanh";
		$contract = $this->contract_model->getContractForQuickLoan($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		$property = !empty($this->dataPost['property']) ? $this->dataPost['property'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start).' 00:00:00'),
				'end' => strtotime(trim($end).' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
			$all = true;
		} else if (!in_array('cua-hang-truong', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array()
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}
		$condition['type'] = "vaynhanh";
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}
		if (!empty($condition)) {
			$contract = $this->quickloan_model->getContractByRole($condition);
		} else {
			$contract = $this->quickloan_model->find();
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_one_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));

		if(empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng không tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function upload_image_contract_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$data['image_accurecy'] = $this->security->xss_clean($data['image_accurecy']);
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		if(empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->contract_model->update(
			array("_id" => new \MongoDB\BSON\ObjectId($data['id'])),
			array("image_accurecy" => $data['image_accurecy'])
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "lưu thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function upload_image_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$data['type_img'] = $this->security->xss_clean($data['type_img']);
		$data['file'] = $this->security->xss_clean($data['file']);

		if($data['file']['size'] > 10000000) {
			return $this->pushJson('200', json_encode(array(
				'code' => '201',
				'msg' => 'Kích cỡ max là 10MB'
			)));
		}
		$acceptFormat = array("jpeg", "png", "jpg", "mp3", "mp4");
		if(in_array($data['file']['type'], $acceptFormat)) {
			return $this->pushJson('200', json_encode(array(
				'code' => '201',
				'msg' => 'Định dạng không cho phép',
				'type' => $data['file']['type']
			)));
		}
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		if(empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$cfile = new CURLFile($data['file']["tmp_name"],$data['file']["type"],$data['file']["name"]);

		$push_upload = $this->pushUpload($cfile);
		if($push_upload->code == 200){
			//Update DB
			$random = sha1(random_string());
			$data1 = array(
				'path' => $push_upload->path,
				'file_type' => $data['file']["type"],
				'file_name' => $data['file']["name"]
			);
			$dataDB['image_accurecy'][$data['type_img']] = (array)$dataDB['image_accurecy'][$data['type_img']];
			//$dataDB['image_accurecy'][$data['type_img']][$random] = $push_upload->path;
			$dataDB['image_accurecy'][$data['type_img']][$random] = $data1;
			//Update
			$this->contract_model->update(
				array("_id" => new \MongoDB\BSON\ObjectId($data['id'])),
				array("image_accurecy.".$data['type_img'] => $dataDB['image_accurecy'][$data['type_img']])
			);
			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => "upload_image",
				"contract_id" => $data['id'],
				"path" => $push_upload->path,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);
			$this->log_model->insert($insertLog);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'path' => $push_upload->path,
				'key' => $random
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function get_image_accurecy_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		if(empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataDB['image_accurecy'],
			'contract_status' => $dataDB['status']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function delete_image_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$data['key'] = $this->security->xss_clean($data['key']);
		$data['type_img'] = $this->security->xss_clean($data['type_img']);
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		$arrImg = (array)$dataDB['image_accurecy'][$data['type_img']];
		$path = $arrImg[$data['key']];
		unset($arrImg[$data['key']]);
		if(empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Update
		$this->contract_model->update(
			array("_id" => new \MongoDB\BSON\ObjectId($data['id'])),
			array("image_accurecy.".$data['type_img'] => $arrImg)
		);
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "delete_image",
			"contract_id" => $data['id'],
			"path" => $path,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Delete image success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function pushUpload($cfile){
		$serviceUpload = $this->config->item("url_service_upload");
		// $curlFile  = new CURLFile($_FILES["avatar"]["tmp_name"],$_FILES["avatar"]["type"],$_FILES["avatar"]["name"]);
		$post = array('avatar'=> $cfile );
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$serviceUpload);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec ($ch);
		curl_close ($ch);
		$result1 = json_decode($result);
		return $result1;
	}

	public function checkNull() {
		$res = array(
			"status" => 1,
			"message" => ""
		);

		//Check null mục thông tin phong giao dich
		if(empty($this->dataPost['store']) || empty($this->dataPost['store']['id'])
			|| empty($this->dataPost['store']['name'])
			|| empty($this->dataPost['store']['address'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục thông tin phòng giao dịch"
			);
		}

		//Check null mục thông tin chuyển khoản
		if(empty($this->dataPost['receiver_infor']) || empty($this->dataPost['receiver_infor']['type_payout'])
			|| empty($this->dataPost['receiver_infor']['amount'])
			|| empty($this->dataPost['receiver_infor']['bank_id'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục thông tin chuyển khoản"
			);
		}
		if(!empty($this->dataPost['receiver_infor']['type_payout'])){
			if($this->dataPost['receiver_infor']['type_payout'] == 2 && (empty($this->dataPost['receiver_infor']['bank_account']) || empty($this->dataPost['receiver_infor']['bank_account_holder']) || empty($this->dataPost['receiver_infor']['bank_branch']))){
				$res = array(
					"status" => 2,
					"message" => "Điền đầy đủ mục thông tin chuyển khoản"
				);
			}
			if($this->dataPost['receiver_infor']['type_payout'] == 3 && (empty($this->dataPost['receiver_infor']['atm_card_number']) || empty($this->dataPost['receiver_infor']['atm_card_holder']))){
				$res = array(
					"status" => 2,
					"message" => "Điền đầy đủ mục thông tin chuyển khoản"
				);
			}
		}

		//Check null mục thông tin khách hàng
		if(empty($this->dataPost['customer_infor']) || empty($this->dataPost['customer_infor']['customer_name'])
			|| empty($this->dataPost['customer_infor']['customer_name'])
			|| empty($this->dataPost['customer_infor']['customer_email'])
			|| empty($this->dataPost['customer_infor']['customer_phone_number'])
			|| empty($this->dataPost['customer_infor']['customer_identify'])
			|| empty($this->dataPost['customer_infor']['customer_BOD'])
			|| empty($this->dataPost['customer_infor']['marriage'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục thông tin khách hàng"
			);
		}
		//Check null mục địa chỉ đang ở
		if(empty($this->dataPost['current_address']) || empty($this->dataPost['current_address']['province'])
			|| empty($this->dataPost['current_address']['district'])
			|| empty($this->dataPost['current_address']['ward'])
			|| empty($this->dataPost['current_address']['form_residence'])
			|| empty($this->dataPost['current_address']['time_life'])
			|| empty($this->dataPost['current_address']['current_stay'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục địa chỉ đang ở"
			);
		}
		//Check null mục địa chỉ hộ khẩu
		if(empty($this->dataPost['houseHold_address']) || empty($this->dataPost['houseHold_address']['province'])
			|| empty($this->dataPost['houseHold_address']['district'])
			|| empty($this->dataPost['houseHold_address']['ward'])
			|| empty($this->dataPost['houseHold_address']['address_household'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục địa chỉ hộ khẩu"
			);
		}
		//Check null mục Thông tin việc làm

		if(empty($this->dataPost['job_infor']) || empty($this->dataPost['job_infor']['phone_number_company'])
			|| empty($this->dataPost['job_infor']['job_position'])
			|| empty($this->dataPost['job_infor']['name_company'])
			// || empty($this->dataPost['job_infor']['phone_number_company'])
			// || empty($this->dataPost['job_infor']['number_tax_company'])
			|| empty($this->dataPost['job_infor']['address_company'])
			|| empty($this->dataPost['job_infor']['salary'])
			|| empty($this->dataPost['job_infor']['receive_salary_via'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục thông tin việc làm",
				'data111'=> $this->dataPost
			);

		}
		//Check null mục Thông tin người thân
		if(empty($this->dataPost['relative_infor']) || empty($this->dataPost['relative_infor']['type_relative_1'])
			|| empty($this->dataPost['relative_infor']['fullname_relative_1'])
			|| empty($this->dataPost['relative_infor']['phone_number_relative_1'])
			|| empty($this->dataPost['relative_infor']['hoursehold_relative_1'])
			|| empty($this->dataPost['relative_infor']['confirm_relativeInfor_1'])
			|| empty($this->dataPost['relative_infor']['type_relative_2'])
			|| empty($this->dataPost['relative_infor']['fullname_relative_2'])
			|| empty($this->dataPost['relative_infor']['phone_number_relative_2'])
			|| empty($this->dataPost['relative_infor']['hoursehold_relative_2'])
			|| empty($this->dataPost['relative_infor']['confirm_relativeInfor_2'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục thông tin người thân"
			);

		}
		//Check null mục Thông tin khoản vay
		if(empty($this->dataPost['loan_infor']) || empty($this->dataPost['loan_infor']['type_loan'])
			|| empty($this->dataPost['loan_infor']['type_property'])
			|| empty($this->dataPost['loan_infor']['name_property'])
			|| empty($this->dataPost['loan_infor']['price_property'])
			|| empty($this->dataPost['loan_infor']['amount_money'])
			|| empty($this->dataPost['loan_infor']['type_interest'])
			|| empty($this->dataPost['loan_infor']['number_day_loan'])
			|| empty($this->dataPost['loan_infor']['insurrance_contract'])
			|| empty($this->dataPost['loan_infor']['loan_purpose'])
			|| empty($this->dataPost['loan_infor']['period_pay_interest'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục thông tin khoản vay"
			);

		}
		//Check null mục Thông tin tài sản
		if(!empty($this->dataPost['property_infor'])) {
			foreach($this->dataPost['property_infor'] as $item) {
				if(empty($item['value'])) {
					$res = array(
						"status" => 2,
						"message" => "Điền đầy đủ mục thông tin tài sản"
					);
					break;
				}
				if ($item['slug'] == 'bien-so-xe') {
					$check = $this->checkProperty($item['value']);
					if (!empty($check)) {
						$res = array(
							"status" => 2,
							"message" => "Hợp đồng đang vay đã tồn tại biển số xe"
						);
						break;
					}
				}
			}
		}

		//Check null mục Thông tin thẩm định
		if(empty($this->dataPost['expertise_infor']) || empty($this->dataPost['expertise_infor']['expertise_file'])
			|| empty($this->dataPost['expertise_infor']['expertise_field'])) {
			$res = array(
				"status" => 2,
				"message" => "Điền đầy đủ mục thông tin thẩm định",
			);
		}
		return $res;
	}

	private function checkSendEmailForNewCustomer($type="") {
		$res = array(
			"status" => 1,
			"message" => "false"
		);
		if($this->dataPost['customer_infor']['status_customer'] == 1) {
			$condition = array(
				'$or' => array(
					array('email' => $this->dataPost['customer_infor']['customer_email']),
					array('identify' => $this->dataPost['customer_infor']['customer_identify'])
					// array('phone_number' => $this->dataPost['customer_infor']['customer_phone_number'])
				)
			);
			$count = $this->user_model->count($condition);
			if($count == 0) {
				//Create account for customer
				$password_root = rand(100000, 999999);
				$hash_password = password_hash($password_root, PASSWORD_BCRYPT);
				$tokenActive = password_hash($hash_password, PASSWORD_BCRYPT);
				$urlActive = $this->config->item("cpanel_url").'/user/activeAccount?token='.$tokenActive;
				$newAccount = array(
					"email" => $this->dataPost['customer_infor']['customer_email'],
					"password" => $hash_password,
					"url_active" => $urlActive,
					"token_active" => $tokenActive,
					"status" => "new",
					"created_at" => $this->createdAt,
				);
				$userId = $this->user_model->insertReturnId($newAccount);
				//Update to role customer
				$roleCustomer = $this->role_model->findOne(array("slug" => "customer"));
				if(!empty($roleCustomer)) {
					$users = $roleCustomer['users'];
					$data1 = array();
					$data1['email'] = $this->dataPost['customer_infor']['customer_email'];
					$data = array();
					$data[(string)$userId] = $data1;
					$users[(string)$userId] = $data;
					//Update role customer
					$this->role_model->update(
						array("_id" => $roleCustomer['_id']),
						array('users' => $users)
					);
				}
				//Send email
				$sendEmail = array(
					"email" => $this->dataPost['customer_infor']['customer_email'],
					//"code" => "tienngay_active_account",
					"code" => "ticki_create_account",
					"url" => $urlActive,
					'API_KEY' => $this->config->item('API_KEY')
				);
				$this->user_model->send_Email($sendEmail);
			} else {
				$res = array(
					"status" => 2,
					"message" => "Email hoặc CMND khách hàng đã tồn tại"
				);
			}
		}
		return $res;
	}

	private function initNumberContractCode(){
		$maxNumber = $this->contract_model->getMaxNumberContract();
		$maxNumberContract = !empty($maxNumber[0]['number_contract']) ? (float)$maxNumber[0]['number_contract']+1 : 1;
		$res = array(
			"max_number_contract" => $maxNumberContract
		);
		return $res;
	}

	private function initContractCode($store_id,$typeProperty,$typeLoan) {
		$res = array(
			"code_contract" => "",
			// "max_number_contract" => ""
		);
		// $typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
		// $typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";
		$maxNumber = $this->contract_model->getMaxNumberContract();
		// $maxNumberContract = !empty($maxNumber[0]['number_contract']) ? (float)$maxNumber[0]['number_contract'] : 0;
		// $numberContract = $maxNumberContract + 1;
		// $numberContract = sprintf("%06s", $numberContract);
		//HD_CAMCO_XEMAY_000001
		$store_info = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($store_id)));
		$code_province_store = !empty($store_info['code_province_store']) ? $store_info['code_province_store'] : "";
		$code_address_store = !empty($store_info['code_address_store']) ? $store_info['code_address_store'] : "";

		if($typeLoan == 'CC'){
			$type_loan_property = $typeProperty;
		}else{
			$type_loan_property = "ĐK".$typeProperty;
		}
		$mydate=getdate(date("U"));
		$number = '';
		$first_date = date('01-m-Y');
		$timestamp = strtotime($first_date);
		$time['start'] = $timestamp;
		$time['end'] = time();
		$count = $this->contract_model->countContractActivebyTime($time, $store_id);
		if ($count == 0) {
			$number = '01';
		} elseif ($count > 0 && ($count +1) < 10) {
			$number = '0'.($count+1);
		} else {
			$number = $count + 1;
		}
		$year = substr( $mydate['year'], -2);
		if (intval($mydate['mon']) < 10)  {
			$mydate['mon'] = '0'.$mydate['mon'];
		}
		$codeContract = "HĐCC/".$type_loan_property.'/'.$code_province_store.$code_address_store.'/'.$year.$mydate['mon'].'/'.$number;
		$res = array(
			"code_contract" => $codeContract,
			// "max_number_contract" => $maxNumberContract
		);
		return $res;
	}

	public function contract_tempo_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		if (!empty($condition['status'])) {
			$condition['status'] = intval($condition['status']);
		}
		if (!empty($condition['status_disbursement'])) {
			$condition['status_disbursement'] = intval($condition['status_disbursement']);
		}
		if (!empty($condition['store_id'])) {
			$condition['store.id'] = $condition['store_id'];
			unset($condition['store_id']);
		}
		$contract = array();
		if (!empty($condition)) {
			$contract = $this->contract_model->getContractByTime(array(), $condition);
			// $response = array(
			// 	'status' => REST_Controller::HTTP_OK,
			// 	'data' => $condition,
			// );
			// $this->set_response($response, REST_Controller::HTTP_OK);
			// return;
		} else {
			$contract = $this->contract_model->find();
		}

		if (!empty($contract)) {
			foreach ($contract as $c) {
				$c['investor_name']="";
				if(isset($c['investor_code']))
				{
					$investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
					$c['investor_name']=$investors['name'];
				}
				$cond = array(
					'code_contract' => $c['code_contract'],
					'end' => time() - 5* 24*3600, // 5 ngay tieu chuan
				);
				$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
				$c['detail'] = array();
				if (!empty($detail)) {
					$total_paid = 0;
					foreach ($detail as $de) {

						$total_paid = $total_paid + $de['tien_tra_1_ky'] ;
					}
					$c['detail'] = $detail[0];
					$c['detail']['total_paid'] = $total_paid;
				} else {
					$condition_new = array(
						'code_contract' => $c['code_contract'],
						'status' => 1
					);
					$detail_new = $this->contract_tempo_model->getContract($condition_new);
					if (!empty($detail_new)) {
						$c['detail'] = $detail_new[0];

						$c['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'] ;
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function tempo_detail_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		if (empty($this->dataPost['id'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại id"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if(empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$condition = array(
			'code_contract' => $dataDB['code_contract']
		);
		$contract = $this->contract_tempo_model->getAll($condition);
		//  lấy data các nhà đầu tư còn hoạt động
		$investor = $this->investor_model->find_where(array('status' => 'active'));
		$data_investor = array();
		if (!empty($investor)) {
			foreach ($investor as $in) {
				$data_investor[$in['code']] = $in;
			}
		}
		$total_money_paid = 0;
		$total_money_remaining = 0;
		$total_paid = 0;
		if (!empty($contract)) {
			foreach ($contract as $c) {
				$c['disbursement_date'] = $dataDB['disbursement_date'];
				$c['amount_money'] = $dataDB['loan_infor']['amount_money'];
				if ($c['status'] == 1 && ( time() > ($c['ngay_ky_tra'] - 5* 24*3600))) { // 5 ngay tieu chuan
					$total_money_paid = $total_money_paid + (int)$c['tien_tra_1_ky'] + (int)$c['penalty'] - (int)$c['da_thanh_toan'];
				}
				if ($c['status'] == 2) {
					//$total_money_remaining = (int)$c['tien_goc_con'];
					$total_paid += (int)$c['da_thanh_toan'];
				}
				if ($c['status'] == 1) {
					$total_money_remaining += (isset($c['tien_goc_1ky_con_lai'])) ? (int)$c['tien_goc_1ky_con_lai'] : 0;

				}
			}
			if ($contract[0]['status'] == 1) {
				$total_money_remaining = $dataDB['loan_infor']['amount_money'] - $contract[0]['da_thanh_toan'];
			}
		}

		if (!empty($dataDB['investor_code']) && !empty($data_investor[$dataDB['investor_code']]['name'])) {
			$dataDB['investor_name'] = $data_investor[$dataDB['investor_code']]['name'];
		} else {
			$dataDB['investor_name'] = '';
		}
		$dataDB['total_money_paid'] = $total_money_paid;
		$dataDB['total_money_remaining'] = $total_money_remaining;
		$dataDB['total_paid'] = $total_paid;
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'contract' => $dataDB,
			'message' => 'Success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	// kiem tra trung bien so xe
	public function check_property_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['infor'] = $this->security->xss_clean($this->dataPost['infor']);
		if (empty($this->dataPost['infor'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thông tin tài sản không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$infor = trim($this->dataPost['infor']);
		$dataDB = $this->checkProperty($infor);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataDB,
			'message' => 'Success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function checkProperty($infor) {
		$infor = str_replace('-','',$infor);
		$infor = str_replace('.','',$infor);
		$infor = str_replace(',','',$infor);
		$infor = preg_replace('/\s+/', '', $infor);
		$condition = array(
			'status' => array(19, 3, 0),
			'property_contract_infor' => $infor
		);
		$dataDB = $this->contract_model->findContractRenew($condition);
		if (empty($dataDB)) {
			return array();
		} else {
			return $dataDB;
		}
	}

	public function debt_detail_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		if (empty($this->dataPost['id'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại id"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if(empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		// hop dong
		$fee = array();
		// lai suat tu van
		$pham_tram_phi_tu_van = 0;
		// lai suat tham dinh
		$pham_tram_phi_tham_dinh = 0;
		// lai suat nha dau tu
		$lai_suat_ndt = 0;
		$percent_prepay_phase_1 = 0;
		$percent_prepay_phase_2 = 0;
		$percent_prepay_phase_3 = 0;
		if (!empty($dataDB['fee'])) {
			$fee = $dataDB['fee'];
			$pham_tram_phi_tu_van = floatval($fee['percent_advisory'])/100;
			$pham_tram_phi_tham_dinh = floatval($fee['percent_expertise'])/100;
			$lai_suat_ndt = floatval($fee['percent_interest_customer'])/100;
			$percent_prepay_phase_1 = floatval($fee['percent_prepay_phase_1'])/100;
			$percent_prepay_phase_2 = floatval($fee['percent_prepay_phase_2'])/100;
			$percent_prepay_phase_3 = floatval($fee['percent_prepay_phase_3'])/100;
			$phan_tram_phi_phat_tra_cham = floatval($fee['penalty_percent'])/100;
			$phi_phat_tra_cham = floatval($fee['penalty_amount']);
		}
		$tien_gian_ngan = (int)$dataDB['loan_infor']['amount_money'];
		// tinh toan
		$total_money_remaining = 0;
		$kiem_tra_tat_toan = false;
		$date_now = date('d-m-Y');
		$now = strtotime($date_now);
		$all_contract_tempo = $this->contract_tempo_model->find_where(array('code_contract' => $dataDB['code_contract'], 'status' => 1));
		if (empty($all_contract_tempo)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại lãi kỳ của hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$so_ngay_vay = (int)$dataDB['loan_infor']['number_day_loan'];
		$period_pay_interest = (int)$dataDB['loan_infor']['period_pay_interest'];
		$so_ky_vay = $so_ngay_vay/$period_pay_interest;
		$type_interest = (int)$dataDB['loan_infor']['type_interest'];
		// ngay giai ngan
		$ngay_giai_ngan = date('d-m-Y', $dataDB['disbursement_date']);
		$timestamp_ngay_giai_ngan = strtotime($ngay_giai_ngan);
		$timestamp_ngay_tat_toan = $timestamp_ngay_giai_ngan + $so_ngay_vay*24*3600 - 24*60*60;
		$datediff = $now - $timestamp_ngay_tat_toan;
		$tong_tien_lai_phi_tat_toan = 0;
		$tien_phi_phat_tra_cham = 0;
		if ($so_ngay_vay == 30) {
			$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
			$so_ngay_chenh_lech = $so_ngay_vay_thuc_te - $so_ngay_vay;
			$tien_goc_con = $tien_gian_ngan;
			if ($so_ngay_chenh_lech >= 0 && $so_ngay_chenh_lech <= 3) {
				$tien_lai_nha_dau_tu = $lai_suat_ndt*$tien_gian_ngan;
				$phi_tu_van = $pham_tram_phi_tu_van*$tien_gian_ngan;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh*$tien_gian_ngan;
				$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$tong_tien_thanh_toan = $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu + $tien_gian_ngan;
				$phi_thanh_toan_truoc_han = 0;
			} else if (($so_ngay_vay_thuc_te >= (2*$so_ngay_vay/3)) && $so_ngay_chenh_lech <= 0) {
				$tien_lai_nha_dau_tu = $lai_suat_ndt*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$phi_tu_van = $pham_tram_phi_tu_van*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$phi_thanh_toan_truoc_han =  $percent_prepay_phase_3*$tien_gian_ngan;
				$tong_tien_thanh_toan = $tong_tien_lai_phi_tat_toan*$phi_thanh_toan_truoc_han;
			} else if (($so_ngay_vay_thuc_te >= ($so_ngay_vay/3)) && ($so_ngay_vay_thuc_te <= (2*$so_ngay_vay/3 - 1))) {
				$tien_lai_nha_dau_tu = $lai_suat_ndt*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$phi_tu_van = $pham_tram_phi_tu_van*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$phi_thanh_toan_truoc_han =  $percent_prepay_phase_2*$tien_gian_ngan;
				$tong_tien_thanh_toan = $tong_tien_lai_phi_tat_toan*$phi_thanh_toan_truoc_han;
			} else if (($so_ngay_vay_thuc_te >= 0) && ($so_ngay_vay_thuc_te <= ($so_ngay_vay/3 - 1))) {
				$tien_lai_nha_dau_tu = $lai_suat_ndt*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$phi_tu_van = $pham_tram_phi_tu_van*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$phi_thanh_toan_truoc_han =  $percent_prepay_phase_1*$tien_gian_ngan;
				$tong_tien_thanh_toan = $tong_tien_lai_phi_tat_toan*$phi_thanh_toan_truoc_han;
			} else {
				$tien_phi_phat_tra_cham = $tien_gian_ngan*$phan_tram_phi_phat_tra_cham*$so_ngay_vay_thuc_te / 30;
				$tien_phi_phat_tra_cham = ($tien_phi_phat_tra_cham > $phi_phat_tra_cham) ? $tien_phi_phat_tra_cham : $phi_phat_tra_cham;
				$tien_lai_nha_dau_tu = $lai_suat_ndt*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$phi_tu_van = $pham_tram_phi_tu_van*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh*$tien_gian_ngan/30*$so_ngay_vay_thuc_te;
				$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$phi_thanh_toan_truoc_han =  $percent_prepay_phase_1*$tien_gian_ngan;
				$tong_tien_thanh_toan = $tong_tien_lai_phi_tat_toan*$phi_thanh_toan_truoc_han;
				$tong_tien_thanh_toan = $tong_tien_thanh_toan + $tien_phi_phat_tra_cham;
			}

		} else {
			$tien_goc_con = 0;
			$phi_tu_van = $pham_tram_phi_tu_van*$tien_gian_ngan;
			$phi_tham_dinh = $pham_tram_phi_tham_dinh*$tien_gian_ngan;
			$condition_success = array(
				'status' => 2,
				'code_contract' => $dataDB['code_contract'],
			);
			$contract_success = $this->contract_tempo_model->find_where_success($condition_success);
			if (!empty($contract_success)) { // hop dong da thanh toan ky lai
				$ngay_tra_lai_ky = $contract_success[count($contract_success) -1]['ngay_ky_tra'];
				$tien_goc_con = $contract_success[count($contract_success) -1]['tien_goc_con'];
				// ngay giai ngan
				$ngay_tra_lai_ky_gan_nhat = date('d-m-Y', $ngay_tra_lai_ky);
				$timestamp_tra_lai_ky_gan_nhat = strtotime($ngay_tra_lai_ky_gan_nhat);
				$datediff = $now - $timestamp_tra_lai_ky_gan_nhat;
				$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			} else {
				$tien_goc_con = $tien_gian_ngan;
				$datediff = $now - $timestamp_ngay_giai_ngan;
				$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			}
			$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
			$so_ngay_da_vay = round($datediff / (60 * 60 * 24));
			$so_ngay_da_vay = $so_ngay_da_vay + 1;
			$so_ngay_chenh_lech = $so_ngay_da_vay - $so_ngay_vay;
			if ($type_interest == 1) { // du no giam dan
				$goc_lai_1_ky = round(($tien_gian_ngan*$lai_suat_ndt)/(1-pow((1+$lai_suat_ndt),-$so_ky_vay)));
				$lai_ky = $lai_suat_ndt*$tien_goc_con;
				if ($so_ngay_chenh_lech >= 0 && $so_ngay_chenh_lech <= 3) {
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $goc_lai_1_ky;
					$tong_tien_thanh_toan = $phi_tham_dinh + $phi_tu_van + $goc_lai_1_ky;
					$phi_thanh_toan_truoc_han = 0;
				} else if (($so_ngay_da_vay >= (2*$so_ngay_vay/3)) && $so_ngay_chenh_lech <= 0) {
					$phi_tham_dinh = $phi_tham_dinh/30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van/30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky/30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han =  $percent_prepay_phase_3*$tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if (($so_ngay_da_vay >= ($so_ngay_vay/3)) && ($so_ngay_da_vay <= (2*$so_ngay_vay/3 - 1))) {
					$phi_tham_dinh = $phi_tham_dinh/30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van/30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky/30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han =  $percent_prepay_phase_2*$tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if ($so_ngay_da_vay >= 0 && ($so_ngay_da_vay <= ($so_ngay_vay/3 - 1))) {
					$phi_tham_dinh = $phi_tham_dinh/30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van/30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky/30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han =  $percent_prepay_phase_1*$tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else {
					$phi_tham_dinh = $phi_tham_dinh/30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van/30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky/30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han =  $percent_prepay_phase_1*$tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
					$tien_phi_phat_tra_cham = $tien_goc_con*$phan_tram_phi_phat_tra_cham*$so_ngay_vay_thuc_te / 30;
					$tien_phi_phat_tra_cham = ($tien_phi_phat_tra_cham > $phi_phat_tra_cham) ? $tien_phi_phat_tra_cham : $phi_phat_tra_cham;
					$tong_tien_thanh_toan = $tong_tien_thanh_toan + $tien_phi_phat_tra_cham;
				}
			} else { // lai hang thang goc cuoi ky
				$lai_ky = round($lai_suat_ndt*$tien_gian_ngan);
				if ($so_ngay_chenh_lech >= 0 && $so_ngay_chenh_lech <= 3) {
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$tong_tien_thanh_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky + $tien_gian_ngan;
				} else if (($so_ngay_da_vay >= (2*$so_ngay_vay/3)) && $so_ngay_chenh_lech <= 0) {
					$phi_tham_dinh = $phi_tham_dinh/30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van/30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky/30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han =  $percent_prepay_phase_3*$tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if (($so_ngay_da_vay >= ($so_ngay_vay/3)) && ($so_ngay_da_vay <= (2*$so_ngay_vay/3 - 1))) {
					$phi_tham_dinh = $phi_tham_dinh/30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van/30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky/30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han =  $percent_prepay_phase_2*$tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if ($so_ngay_da_vay >= 0 && ($so_ngay_da_vay <= ($so_ngay_vay/3 - 1))) {
					$phi_tham_dinh = $phi_tham_dinh/30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van/30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky/30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han =  $percent_prepay_phase_1*$tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else {
					$phi_tham_dinh = $phi_tham_dinh/30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van/30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky/30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan =  $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han =  $percent_prepay_phase_1*$tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
					$tien_phi_phat_tra_cham = $tien_goc_con*$phan_tram_phi_phat_tra_cham*$so_ngay_vay_thuc_te / 30;
					$tien_phi_phat_tra_cham = ($tien_phi_phat_tra_cham > $phi_phat_tra_cham) ? $tien_phi_phat_tra_cham : $phi_phat_tra_cham;
					$tong_tien_thanh_toan = $tong_tien_thanh_toan + $tien_phi_phat_tra_cham;
				}
			}

		}

		$res = array(
			'total_paid' => $tong_tien_thanh_toan,
			'day_debt' => $so_ngay_vay_thuc_te,
			'so_ngay_da_vay_hop_dong' => $so_ngay_da_vay,
			'lai_ky' => $lai_ky,
			'phi_tham_dinh' => $phi_tham_dinh,
			'phi_tu_van' => $phi_tu_van,
			'tien_goc_con' => $tien_goc_con,
			'phi_thanh_toan_truoc_han' => $phi_thanh_toan_truoc_han,
			'tien_phi_phat_tra_cham' => $tien_phi_phat_tra_cham,
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $res,
			'message' => 'Success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function getGroupRole($userId) {
		$groupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$arr = array();
		foreach($groupRoles as $groupRole) {
			if(empty($groupRole['users'])) continue;
			foreach($groupRole['users'] as $item) {
				if(key($item) == $userId) {
					array_push($arr, $groupRole['slug']);
					continue;
				}
			}
		}
		return $arr;
	}
	public function get_gdv_by_store_post(){
		$store_id = $this->input->post('store_id');
		$users_by_store = $this->getUserbyStores($store_id);
		$users_by_role = $this->getUserGroupRole(array('5de726e4d6612b6f2c310c78'));
		$result = array_intersect($users_by_store, $users_by_role);
		if(empty($result)){
			$users_by_role = $this->getUserGroupRole(array('5de726c9d6612b6f2a617ef5'));
			$users_by_store = $this->getUserbyStores($store_id);
			$result = array_intersect($users_by_store, $users_by_role);
		}
		$user_id  = array_rand($result,1);
		$email = $this->user_model->findOne(array('_id' =>  new MongoDB\BSON\ObjectId($result[$user_id])));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $email,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	private function getUserGroupRole($GroupIds) {
		$arr = array();
		foreach ($GroupIds as $groupId) {
			$groups = $this->group_role_model->findOne(array('_id' =>  new MongoDB\BSON\ObjectId($groupId)));
			foreach ($groups['users'] as $item) {
				$arr[] = key($item);
			}
		}
		$arr = array_unique($arr);
		return $arr;
	}

	private function getStores($userId) {
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleStores = array();
		if(count($roles) > 0) {
			foreach($roles as $role) {
				if(!empty($role['users']) && count($role['users']) > 0){
					$arrUsers = array();
					foreach($role['users'] as $item) {
						array_push($arrUsers, key($item));
					}
					//Check userId in list key of $users
					if(in_array($userId, $arrUsers) == TRUE) {
						if(!empty($role['stores'])) {
							//Push store
							foreach($role['stores'] as $key=> $item) {
								array_push($roleStores, key($item));
							}
						}
					}
				}
			}
		}
		return $roleStores;
	}

	private function getUserbyStores($storeId) {
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleAllUsers = array();
		if(count($roles) > 0) {
			foreach($roles as $role) {
				if(!empty($role['stores']) && count($role['stores']) > 0){
					$arrStores = array();
					foreach($role['stores'] as $item) {
						array_push($arrStores, key($item));
					}
					//Check userId in list key of $users
					if(in_array($storeId, $arrStores) == TRUE) {
						if(!empty($role['stores'])) {
							//Push store
							foreach($role['users'] as $key=> $item) {
								array_push($roleAllUsers, key($item));
							}
						}
					}
				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}

	// private function getGroupRole($userId) {
	// 	$groupRoles = $this->group_role_model->find_where(array("status" => "active"));
	// 	$arr = array();
	// 	foreach($groupRoles as $groupRole) {
	// 		if(empty($groupRole['users'])) continue;
	// 		foreach($groupRole['users'] as $item) {
	// 			if(key($item) == $userId) {
	// 				array_push($arr, $groupRole['slug']);
	// 				continue;
	// 			}
	// 		}
	// 	}
	// 	return $arr;
	// }



	public function do_note_reminder_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if(empty($this->dataPost['contract_id']) || empty($this->dataPost['result_reminder'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Data'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$this->dataPost['result_reminder'] = $this->security->xss_clean($this->dataPost['result_reminder']);
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);

		// if(!empty($this->info['is_superadmin']) && $this->info['is_superadmin'] != 1){
		//     // Check access right by status
		//     $isAccess = $this->checkApproveByAccessRight($this->roleAccessRights, $this->dataPost['status']);
		//     if($isAccess == FALSE) {
		//         $response = array(
		//             'status' => REST_Controller::HTTP_UNAUTHORIZED,
		//             'message' => 'Do not have access right'
		//         );
		//         $this->set_response($response, REST_Controller::HTTP_OK);
		//         return;
		//     }
		// }
		// Check old status
		$contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])), array("_id","result_reminder"));

		if(empty($contract)) return;
		//Insert log
		$log = array(
			"type" => "contract",
			"action" => "approve",
			"contract_id" => $this->dataPost['contract_id'],
			"old" => $contract,
			"new" =>  $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($log);
		//Update status contract
		$result_reminder = !empty($contract['result_reminder']) ? $contract['result_reminder'] : array();
		$note_reminder = array(
			"reminder" => $this->dataPost['result_reminder'],
			"note" =>   $this->dataPost['note'],
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail

		);
		// $response = array(
		//     'status' => REST_Controller::HTTP_UNAUTHORIZED,
		//     'message' => $result_reminder
		// );
		// $this->set_response($response, REST_Controller::HTTP_OK);
		// return;
		$dataPush = array();
		array_push($dataPush, $note_reminder);
		foreach($result_reminder as $key => $value){
			array_push($dataPush, $value);
		}

		$arrUpdate = array(
			'result_reminder' => $dataPush,
			'reminder_now' => $this->dataPost['result_reminder']
		);
		$this->contract_model->update( array("_id" => $contract['_id']),$arrUpdate);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Note success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	// Luồng duyệt hợp đồng
	public function approve_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if(empty($this->dataPost['contract_id']) || empty($this->dataPost['status'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Data'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);

		if(!empty($this->info['is_superadmin']) && $this->info['is_superadmin'] != 1){
			// Check access right by status
			$isAccess = $this->checkApproveByAccessRight($this->roleAccessRights, $this->dataPost['status']);
			if($isAccess == FALSE) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => 'Do not have access right'
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		// Check old status
		$contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])), array("_id", "status", "code_contract", "created_by", "store","customer_infor","loan_infor","receiver_infor","note",'check_not_approve'));
		$store = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($contract['store']['id'])));
		$isStatus = $this->checkStatusForApprove($contract['status'], $this->dataPost['status']);
		if(empty($contract)) return;
		if($isStatus == false) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Status is not compatible'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Insert log
		$log = array(
			"type" => "contract",
			"action" => "approve",
			"contract_id" => $this->dataPost['contract_id'],
			"old" => $contract,
			"new" =>  $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($log);
		$status = (int)$this->dataPost['status'];
		//Update status contract
		if($status ==  6){
			$this->dataPost['amount_money'] = $this->security->xss_clean($this->dataPost['amount_money']);
			$this->dataPost['amount_loan'] = $this->security->xss_clean($this->dataPost['amount_loan']);
			$this->dataPost['amount_GIC'] = $this->security->xss_clean($this->dataPost['amount_GIC']);
			$arrUpdate = array(
				"status" => (int)$this->dataPost['status'],
				"note" => $this->dataPost['note'],
				"loan_infor.amount_money" => $this->dataPost['amount_money'],
				"loan_infor.amount_GIC" => $this->dataPost['amount_GIC'],
				"loan_infor.amount_loan" => $this->dataPost['amount_loan'],
				"receiver_infor.amount" => $this->dataPost['amount_money'],
			);
		}else{
			$arrUpdate = array(
				"status" => (int)$this->dataPost['status'],
				"note" => $this->dataPost['note']
			);
		}
		if($status ==  8){
			$arrUpdate['check_not_approve'] = true;
		}

		$this->contract_model->update( array("_id" => $contract['_id']),$arrUpdate);
		$note = '';
		$user_ids = array();
		$user_ids_approve = array();
		if ($status == 2) {
			$note  = 'Chờ phê duyệt';
			$cht_id = array(
				'5de726c9d6612b6f2a617ef5'
			);
			$allusers = $this->getUserbyStores($contract['store']['id']);
			$user_ids_groups = $this->getUserGroupRole($cht_id);
			$arr = array_intersect($allusers,$user_ids_groups);
			$user_ids_approve = array_values($arr);
			$data_send = array(
				'code' => "vfc_send_storeman",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"code_contract" => $contract['code_contract'],
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
			);
			$check =  $this->sendEmailApprove($user_ids_approve,$data_send,$status);
		} elseif ($status == 3) {

			$groupRoles = $this->getGroupRole($this->id);
			//5de72198d6612b4076140606 super admin
			//5de726a8d6612b6f2b431749 Van hanh
			//5de726c9d6612b6f2a617ef5 CHT
			//5de726e4d6612b6f2c310c78 GDV
			//5de726fcd6612b77824963b9 Ke Toan
			//5def671dd6612b75532960c5 Hoi so
			if (in_array('cua-hang-truong', $groupRoles)) {
				$note  = 'Trưởng PGD đã hủy';
				$note  = 'Trưởng PGD không duyệt';
				$user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
				$user_ids = array(
					(string)$user_created['_id']
				);
			} else {
				if ($this->superadmin || in_array('supper-admin', $groupRoles)) {
					$note  = 'Super admin đã hủy';
				} elseif (in_array('van-hanh', $groupRoles)) {
					$note  = 'Vận hành đã hủy';
				} elseif (in_array('ke-toan', $groupRoles)) {
					$note  = 'Kế toán đã hủy';
				} elseif (in_array('hoi-so', $groupRoles)) {
					$note  = 'Hội sở đã hủy';
				}
				$cht_id = array(
					'5de726c9d6612b6f2a617ef5'
				);
				$allusers = $this->getUserbyStores($contract['store']['id']);
				$user_ids_groups = $this->getUserGroupRole($cht_id);
				$arr = array_intersect($allusers,$user_ids_groups);
				$user_ids = array_values($arr);
				//
				//
				$user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
				array_push($user_ids, (string)$user_created['_id']);
			}

			// gửi email thông báo cho giao dichj viên
			$data_send = array(
				'code' => "vfc_cancel_send_gdv",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"customer_email" => $contract['customer_infor']['customer_email'],
				"code_contract" => $contract['code_contract'],
				"store_name" => $contract['store']['name'],
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"note" =>  !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
				"API_KEY"=> $this->config->item('API_KEY'),
				"email" => $contract['created_by'],
				"full_name" => !empty($user_created['full_name']) ?  $user_created['full_name'] : ""
			);
			$this->user_model->send_Email($data_send);
			// gửi email thông báo cho khách hang nếu hội sở hủy mới gửi
			if($contract['status'] == 5){
				$data_send1 = array(
					'code' => "vfc_cancel_send_customer",
					"customer_name" => $contract['customer_infor']['customer_name'],
					"API_KEY"=> $this->config->item('API_KEY'),
					"email" => $contract['customer_infor']['customer_email'],
					"phone_store" => $store['phone']
				);
				$this->user_model->send_Email($data_send1);
			}

		} elseif ($status == 4) {
			$note  = 'Trưởng PGD không duyệt';
			$user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
			$user_ids = array(
				(string)$user_created['_id']
			);
		} elseif ($status == 5) {
			$note = 'Trưởng PGD đã duyệt';
			$user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
			$user_ids = array(
				(string)$user_created['_id']
			);
			//approve
			$hoi_so_id = array(
				'5def671dd6612b75532960c5' // cho Hoi so duyet
			);
			$user_ids_approve = $this->getUserGroupRole($hoi_so_id);
			$check_not_approve = !empty($contract['check_not_approve']) ? $contract['check_not_approve'] :  false;
			if($check_not_approve == true){
				// send email bổ sung đã bị hội sở hủy 1 lần
				$data_send = array(
					'code' => "vfc_president_not_approve",
					"customer_name" => $contract['customer_infor']['customer_name'],
					"code_contract" => $contract['code_contract'],
					"store_name" => $contract['store']['name'],
					"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
					"product" => $contract['loan_infor']['type_loan']['text'],
					"product_detail" => $contract['loan_infor']['name_property']['text'],
					"number_day_loan" => (int)$contract['loan_infor']['number_day_loan']/30,
					"note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : $note,
					"phone_store" => $store['phone']
				);
				$this->sendEmailApprove($user_ids_approve,$data_send,$status);
			}else{
				// send email phê duyệt lần đầu
				$data_send = array(
					'code' => "vfc_send_president",
					"customer_name" => $contract['customer_infor']['customer_name'],
					"code_contract" => $contract['code_contract'],
					"store_name" => $contract['store']['name'],
					"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
					"product" => $contract['loan_infor']['type_loan']['text'],
					"product_detail" => $contract['loan_infor']['name_property']['text'],
					"number_day_loan" => (int)$contract['loan_infor']['number_day_loan']/30,
					"phone_store" => $store['phone']
				);
				$this->sendEmailApprove($user_ids_approve,$data_send,$status);
			}

		} elseif ($status == 6) {
			$note = 'Hội sở đã duyệt';
			$cht_id = array(
				'5de726c9d6612b6f2a617ef5'
			);
			$allusers = $this->getUserbyStores($contract['store']['id']);
			$user_ids_groups = $this->getUserGroupRole($cht_id);
			$arr = array_intersect($allusers,$user_ids_groups);
			$user_ids = array_values($arr);
			//
			$user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
			array_push($user_ids, (string)$user_created['_id']);

			$data_send = array(
				'code' => "vfc_president_approved_send_gdv",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"customer_email" => $contract['customer_infor']['customer_email'],
				"code_contract" => $contract['code_contract'],
				"store_name" => $contract['store']['name'],
				// "store_phone" => $contract['store']['name'],
				"amount_money" => !empty($this->dataPost['amount_money']) ? number_format($this->dataPost['amount_money']) : number_format($contract['loan_infor']['amount_money']),
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"number_day_loan" => (int)$contract['loan_infor']['number_day_loan']/30,
				"note" =>  !empty($this->dataPost['note']) ? $this->dataPost['note'] : $note,
				"phone_store" => $store['phone']
			);

			$this->sendEmailApprove($user_ids,$data_send,$status);

		} elseif ($status == 7) {
			$note = 'Kế toán không duyệt';
			$cht_id = array(
				'5de726c9d6612b6f2a617ef5'
			);
			$allusers = $this->getUserbyStores($contract['store']['id']);
			$user_cht = $this->getUserGroupRole($cht_id);
			$arr = array_intersect($allusers,$user_cht);
			$user_ids = array_values($arr);
			//
			$user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
			array_push($user_ids, (string)$user_created['_id']);
			// gửi email thông báo cho giao dich viên
			$data_send = array(
				'code' => "vfc_accounting_approved",
				"full_name" => !empty($user_created['full_name']) ?  $user_created['full_name'] : "",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"code_contract" => $contract['code_contract'],
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"note" =>  !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
				"API_KEY"=> $this->config->item('API_KEY'),
				"email" => $contract['created_by'],
			);
			// $response = array(
			//     'status' => REST_Controller::HTTP_UNAUTHORIZED,
			//     'message' => 'zaa',
			//     'data' => $data_send
			// );
			// $this->set_response($response, REST_Controller::HTTP_OK);
			// return;

			$this->user_model->send_Email($data_send);
		} elseif($status == 8) {
			$note  = 'Yêu cầu bổ sung hồ sơ';
			$cht_id = array(
				'5de726c9d6612b6f2a617ef5'
			);
			$allusers = $this->getUserbyStores($contract['store']['id']);
			$user_ids_groups = $this->getUserGroupRole($cht_id);
			$arr = array_intersect($allusers,$user_ids_groups);
			$user_ids_approve = array_values($arr);
			$data_send = array(
				'code' => "vfc_president_not_approve",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"code_contract" => $contract['code_contract'],
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
				"number_day_loan" => (int)$contract['loan_infor']['number_day_loan']/30,
				"note" => !empty($this->dataPost['note']) ?  $this->dataPost['note'] : $note,
				"phone_store" => $store['phone']
			);
			$check =  $this->sendEmailApprove($user_ids_approve,$data_send,$status);
		} elseif ($status == 15) {
			$note = 'Giao dịch viên gửi yêu cầu giải ngân';
			$cht_id = array(
				'5de726c9d6612b6f2a617ef5'
			);
			$allusers = $this->getUserbyStores($contract['store']['id']);
			$user_cht = $this->getUserGroupRole($cht_id);
			$arr = array_intersect($allusers,$user_cht);
			$user_ids = array_values($arr);
			//approve
			$kt_id = array(
				'5de726fcd6612b77824963b9'
			);
			$user_ids_approve = $this->getUserGroupRole($kt_id);

			$data_send = array(
				'code' => "vfc_send_accounting",
				"bank_account_holder" => $contract['receiver_infor']['bank_account_holder'],
				"bank_account" => $contract['receiver_infor']['bank_account'],
				"bank_name" => $contract['receiver_infor']['bank_name'],
				"bank_branch" => $contract['receiver_infor']['bank_branch'],
				"store_name" => $contract['store']['name'],
				"customer_name" => $contract['customer_infor']['customer_name'],
				"code_contract" => $contract['code_contract'],
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"note" =>  !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
				"API_KEY"=> $this->config->item('API_KEY'),
				"email" => $contract['created_by'],
				"full_name" => !empty($user_created['full_name']) ? $user_created['full_name'] : "",
				"number_day_loan" => (int)$contract['loan_infor']['number_day_loan']/30,
				"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
			);
			$this->sendEmailApprove($user_ids_approve,$data_send,$status);
		} elseif ($status == 16) {
			$note = 'Kế toán đã tạo lệnh giải ngân';
			$cht_id = array(
				'5de726c9d6612b6f2a617ef5'
			);
			$allusers = $this->getUserbyStores($contract['store']['id']);
			$user_cht = $this->getUserGroupRole($cht_id);
			$arr = array_intersect($allusers,$user_cht);
			$user_ids = array_values($arr);
			//
			$user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
			array_push($user_ids, (string)$user_created['_id']);
		} elseif ($status == 17) {
			$note = 'Giải ngân thành công';
			$cht_id = array(
				'5de726c9d6612b6f2a617ef5'
			);
			$allusers = $this->getUserbyStores($contract['store']['id']);
			$user_cht = $this->getUserGroupRole($cht_id);
			$arr = array_intersect($allusers,$user_cht);
			$user_ids = array_values($arr);
			// user created
			$user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
			array_push($user_ids, (string)$user_created['_id']);
			// ke toan
			$kt_id = array(
				'5de726fcd6612b77824963b9'
			);
			$kt_ids = $this->getUserGroupRole($kt_id);
			$user_ids  = array_merge($user_ids, $kt_ids);
		} elseif ($status == 18) {
			$note = 'Giải ngân thất bại';
			$kt_id = array(
				'5de726fcd6612b77824963b9'
			);
			$user_ids = $this->getUserGroupRole($kt_id);
		} else {
			$note = $this->dataPost['note'];
		}

		// oke
		$dataSocket = array();
		if (!empty($user_ids)) {
			$user_ids = array_values($user_ids);
			foreach ($user_ids as $u) {
				$data_notification = [
					'action_id' => (string)$contract['_id'],
					'action' => 'contract',
					'title' => $contract['code_contract'],
					'detail' => 'pawn/detail?id='.(string)$contract['_id'],
					'note' => $note,
					'user_id' => $u,
					'status' => 1, //1: new, 2 : read, 3: block,
					'contract_status' => $status,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->notification_model->insertReturnId($data_notification);
			}
		}
		if (!empty($user_ids_approve)) {
			$user_ids_approve = array_values($user_ids_approve);
			foreach ($user_ids_approve as $us) {
				$data_approve = [
					'action_id' => (string)$contract['_id'],
					'action' => 'contract',
					'detail' => 'pawn/detail?id='.(string)$contract['_id'],
					'title' => $contract['customer_infor']['customer_name'].' - '.$contract['store']['name'],
					'note' => 'Chờ phê duyệt',
					'user_id' => $us,
					'status' => 1, //1: new, 2 : read, 3: block,
					'contract_status' => $status,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->notification_model->insertReturnId($data_approve);
			}
			$dataUserApprove = array(
				'status' => $status,
				'action_id' => (string)$contract['_id'],
				'action' => 'contract',
				'detail' => 'pawn/detail?id='.(string)$contract['_id'],
				'title' => $contract['code_contract'],
				'note' =>  $contract['customer_infor']['customer_name'].' - '.$contract['store']['name'],
				'users' => $user_ids_approve,
				'created_at' => $this->createdAt,
			);
			$dataSocket['approve'] = $dataUserApprove;
		}

		$dataContract = array(
			'status' => (int)$this->dataPost['status'],
			'action_id' => (string)$contract['_id'],
			'action' => 'contract',
			'detail' => 'pawn/detail?id='.(string)$contract['_id'],
			'title' => $contract['code_contract'],
			'note' => $note,
			'users' => $user_ids,
			'created_at' => $this->createdAt,
		);
		$dataSocket['status'] = $dataContract;
		$this->transferSocket($dataSocket);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Approve success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	//vaynhanh
	public function approve_for_quickloan_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if(empty($this->dataPost['contract_id']) || empty($this->dataPost['status'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Data'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);

		if(!empty($this->info['is_superadmin']) && $this->info['is_superadmin'] != 1){
			// Check access right by status
			$isAccess = $this->checkApproveByAccessRight($this->roleAccessRights, $this->dataPost['status']);
			if($isAccess == FALSE) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => 'Do not have access right'
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		// Check old status
		$contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])), array("_id", "status", "code_contract", "created_by", "store","customer_infor","loan_infor","receiver_infor","note",'check_not_approve'));
		$store = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($contract['store']['id'])));
		$isStatus = $this->checkStatusForApprove($contract['status'], $this->dataPost['status']);
		if(empty($contract)) return;
		if($isStatus == false) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Status is not compatible'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Insert log
		$log = array(
			"type" => "contract",
			"action" => "approve",
			"contract_id" => $this->dataPost['contract_id'],
			"old" => $contract,
			"new" =>  $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($log);
		$status = (int)$this->dataPost['status'];
		//Update status contract
		if($status ==  6){
			$this->dataPost['amount_money'] = $this->security->xss_clean($this->dataPost['amount_money']);
			$this->dataPost['amount_loan'] = $this->security->xss_clean($this->dataPost['amount_loan']);
			$this->dataPost['amount_GIC'] = $this->security->xss_clean($this->dataPost['amount_GIC']);
			$arrUpdate = array(
				"status" => (int)$this->dataPost['status'],
				"note" => $this->dataPost['note'],
				"loan_infor.amount_money" => $this->dataPost['amount_money'],
				"loan_infor.amount_GIC" => $this->dataPost['amount_GIC'],
				"loan_infor.amount_loan" => $this->dataPost['amount_loan'],
				"receiver_infor.amount" => $this->dataPost['amount_money'],
			);
		}else{
			$arrUpdate = array(
				"status" => (int)$this->dataPost['status'],
				"note" => $this->dataPost['note']
			);
		}
		if($status ==  8){
			$arrUpdate['check_not_approve'] = true;
		}

		$this->contract_model->update( array("_id" => $contract['_id']),$arrUpdate);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Approve success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function sendEmailApprove($user_id,$data,$status){

		foreach($user_id as $key => $value){
			if($status == 2 || $status == 5 || $status == 6 || $status == 15 || $status == 7){
				$dataUser = $this->user_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($value)));
				$email = !empty($dataUser['email']) ? $dataUser['email'] : "";

				$full_name = !empty($dataUser['full_name']) ? $dataUser['full_name'] : "";
				if(!empty($email)){
					$data['email'] = $email;
					$data['full_name'] = $full_name;
					$data['API_KEY'] = $this->config->item('API_KEY');
					$this->user_model->send_Email($data);

				}
			}

		}

		// status == 6 send email cho khachs hang (sau khi hội sở duyệt, gửi hồ sơ lại cho khach hang)
		if($status == 6 && !empty($data['customer_email'])){
			if(!empty($data['customer_email'])){
				$data['code'] = 'vfc_president_aproved_send_customer';
				$data['email'] = $data['customer_email'];
				$data['API_KEY'] = $this->config->item('API_KEY');
				// return $data;
				$this->user_model->send_Email($data);
			}
		}

	}

	private function transferSocket($data) {
		$version = new Version2X($this->config->item('IP_SOCKET_SERVER'));
		$dataNotify['res'] = $data['status'];
		if (!empty($data['approve'])) {
			$dataApprove['res'] = $data['approve'];
		}
		try {
			$client = new Client($version);
			$client->initialize();
			$client->emit('notify_status', $dataNotify);
			if (!empty($dataUserApprove)) {
				$client->emit('notify_approve', $dataApprove);
			}
			$client->close();
		} catch (Exception $e) {

		}

	}

	private function checkApproveByAccessRight($roleAccessRights, $status) {
		$isAccess = false;
		//Status = 2 = Nhân viên bấm Gửi duyệt cho CHT = 5dedd24f68a3ff3100003649
		//Status = 3 = Hủy HĐ = 5db6b8c9d6612bceeb712375
		//Status = 4 = CHT từ chối = 5dedd2c868a3ff310000364a
		//Status = 5 = CHT duyệt = 5dedd2d868a3ff310000364b
		//Status = 6 = Hội sở duyệt = 5dedd2e668a3ff310000364c
		//Status = 7 = Kế toán từ chối = 5def401b68a3ff1204003adb
		//Status = 15 = GDV đã hoàn thiện hồ sơ và gửi lệnh giải ngân cho kế toán = 5dedd32468a3ff310000364d
		if($status == 2 && in_array('5dedd24f68a3ff3100003649', $roleAccessRights) ||
			$status == 3 && in_array('5db6b8c9d6612bceeb712375', $roleAccessRights) ||
			$status == 4 && in_array('5dedd2c868a3ff310000364a', $roleAccessRights) ||
			$status == 5 && in_array('5dedd2d868a3ff310000364b', $roleAccessRights) ||
			$status == 6 && in_array('5dedd2e668a3ff310000364c', $roleAccessRights) ||
			$status == 7 && in_array('5def401b68a3ff1204003adb', $roleAccessRights) ||
			$status == 15 && in_array('5dedd32468a3ff310000364d', $roleAccessRights))
			$isAccess = true;
		return $isAccess;
	}

	private function checkStatusForApprove($oldStatus, $status) {
		$isCorrect = false;
		//Status = 21 = Hội sở bấm duyệt cho gia han hop đông
		if($oldStatus == 21 &&  $status == 22) $isCorrect = true;
		//Status = 23 = Hợp đồng đã được gia hạn => old_status = 22 = chờ kế toán duyệt gia hạn
		if($oldStatus == 22 &&  $status == 23) $isCorrect = true;
		//Status = 2 = Nhân viên bấm Gửi duyệt cho CHT => old_status = 1 = Mới tạo
		if($oldStatus == 1 &&  $status == 2) $isCorrect = true;
		//Status = 4 = CHT từ chối => old_status = 2
		if($oldStatus == 2 &&  $status == 4) $isCorrect = true;
		//Status = 2 = CHT từ chối => old_status = 4
		if($oldStatus == 4 &&  $status ==2) $isCorrect = true;
		//Status = 5 = CHT duyệt => old_status = 2
		if($oldStatus == 2 &&  $status == 5) $isCorrect = true;
		//Status = 6 = Hội sở duyệt => old_status = 5
		if($oldStatus == 5 &&  $status == 6) $isCorrect = true;
		//Status = 8 = Hội sở bấm từ chối duyệt cho gia han hop đông  => old_status = 5
		if($oldStatus == 5 &&  $status == 8) $isCorrect = true;
		//Status = 5 = Cửa hang trưởng gia hạn lần 2  => old_status = 8
		if($oldStatus == 8 &&  $status == 5) $isCorrect = true;
		//Status = 5 = Hội sở hủy => old_status = 3
		if($oldStatus == 5 &&  $status == 3) $isCorrect = true;
		//Status = 7 = Kế toán từ chối => old_status = 6
		if($oldStatus == 6 &&  $status == 7) $isCorrect = true;
		//Status = 15 = GDV đã hoàn thiện hồ sơ và gửi lệnh giải ngân cho kế toán
		//=> old_status = 6 = hội sở duyệt. Hoặc là
		//=> old_status = 7 = kế toán từ chối vì 1 lí do nào đó
		if(($oldStatus == 6 &&  $status == 15) || ($oldStatus == 7 &&  $status == 15) || ($oldStatus == 15 &&  $status == 7)) $isCorrect = true;
		//Status = 3 = Hủy HĐ
		//=> old_status = 2 = Nhân viên bấm Gửi duyệt cho CHT => CHT hủy HĐ
		//=> old_status = 5 = CHT duyệt và đưa lên hội sở => Hội sở hủy HĐ
		//=> old_status = 15 = GDV đã hoàn thiện hồ sơ và gửi lệnh giải ngân cho kế toán  => Kế toán hủy HĐ
		//=> old_status = 7 = Kế toán từ chối => GDV hoàn thiện LẠI hồ sơ nhưng ko đủ đk  => GDV hủy HĐ
		if($status == 3 && ($oldStatus == 6 || $oldStatus == 2 || $oldStatus == 5 || $oldStatus == 15 || $oldStatus == 7)) $isCorrect = true;
		return $isCorrect;
	}
	function slugify($text) {
		// replace non letter or digits by -
		$text = preg_replace('~[^\pL\d]+~u', '-', $text);
		// transliterate
		$text = vn_to_str($text);
		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);
		// trim
		$text = trim($text, '-');
		// remove duplicate -
		$text = preg_replace('~-+~', '-', $text);
		// lowercase
		$text = strtolower($text);
		if (empty($text)) {
			return 'n-a';
		}
		return $text;
	}
	function vn_to_str ($str){
		$unicode = array(
			'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
			'd'=>'đ',
			'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
			'i'=>'í|ì|ỉ|ĩ|ị',
			'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
			'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
			'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
			'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
			'D'=>'Đ',
			'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
			'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
			'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
			'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
			'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
		);
		foreach($unicode as $nonUnicode=>$uni){
			$str = preg_replace("/($uni)/i", $nonUnicode, $str);
		}
		$str = str_replace(' ','_',$str);
		return $str;
	}
}
?>
