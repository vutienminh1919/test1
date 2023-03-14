<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/DigitalContractMegadoc.php';
require_once APPPATH . 'libraries/VPBank.php';

use Restserver\Libraries\REST_Controller;


class Payment extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
    
        $this->load->model('user_model');
        $this->load->model('role_model');
        $this->load->model('payment_model');
        $this->load->model('allocation_model');
        $this->load->model('generate_model');
        $this->load->model("contract_tempo_model");
        $this->load->helper('lead_helper');
        $this->load->model('exemptions_model');
        $this->load->model('coupon_model');
		$this->load->model("store_model");
		$this->load->model('province_model');
		$this->load->model('district_model');
		$this->load->model('ward_model');
		$this->load->model("bank_nganluong_model");
		$this->load->model('log_megadoc_model');
		$this->load->model('sms_megadoc_model');
        $headers = $this->input->request_headers();
        $this->flag_login = 1;
        $this->superadmin = false;
        $this->dataPost = $this->input->post();
		$this->megadoc = new DigitalContractMegadoc();
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

    public $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;

    /**
    * Lấy thông tin thanh toán hợp đồng
    * @param id String contract's id
    * @param amount_only int get amount number only
    * @param code_contract String contract's code_contract
    */
    public function get_payment_all_contract_post() {
        $idContract = $this->security->xss_clean($this->dataPost['id']);
        $getAmountOnly = $this->security->xss_clean($this->dataPost['amount_only']);
        $codeContract = $this->security->xss_clean($this->dataPost['code_contract']);
        $dataDB = null;
        if ($idContract) {
            $dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($idContract)));
        } else if ($codeContract) {
            $dataDB = $this->contract_model->findOne(array("code_contract" => $codeContract));
        }
        
        if (empty($dataDB)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $id_contract = (String) $dataDB["_id"];
        $date_pay = (empty($this->dataPost['date_pay'])) ?  0 : strtotime($this->dataPost['date_pay'].' 23:59:59');
		$date_pay_coupon = (!empty($this->dataPost['date_pay'])) ?  strtotime($this->dataPost['date_pay'].' 23:59:59') : strtotime(date('Y-m-d').' 23:59:59');

        $arr_data=[
            'date_pay' => $date_pay,
            'id_contract' => $id_contract,
            'code_contract' => $dataDB['code_contract'],
            'id' => $id_contract,
        ];
        $contractDB = $this->payment_model->get_payment($arr_data)['contract'];
        $dataTatToanPart1 = $this->payment_model->get_infor_tat_toan_part_1($arr_data);
        $debtData = $this->payment_model->debt_detail($arr_data);

        $du_no_con_lai_thanhtoan =0;
        $tien_lai_thanhtoan = 0;
        $tien_phi_thanhtoan = 0;
        $tong_tien_thanh_toan_thanhtoan = 0;
        $phi_phat_cham_tra_thanhtoan =0; 
        $tong_penalty_con_lai=0;
		$lai_suat_tru_vao_ky_cuoi = 0;
        $du_no_con_lai_thanhtoan = !empty($dataTatToanPart1['du_no_con_lai']) ? $dataTatToanPart1['du_no_con_lai'] : 0;
        $tien_lai_thanhtoan = !empty($dataTatToanPart1['lai_chua_tra_den_thoi_diem_hien_tai']) ? $dataTatToanPart1['lai_chua_tra_den_thoi_diem_hien_tai'] : 0;
        $tien_phi_thanhtoan = !empty($dataTatToanPart1['phi_chua_tra_den_thoi_diem_hien_tai']) ? $dataTatToanPart1['phi_chua_tra_den_thoi_diem_hien_tai'] : 0;
        $phi_phat_sinh_thanhtoan = !empty($contractDB['phi_phat_sinh']) ? $contractDB['phi_phat_sinh'] : 0;
        $tien_giam_tru_bhkv = !empty($contractDB['tien_giam_tru_bhkv']) ? $contractDB['tien_giam_tru_bhkv'] : 0;
        $tong_so_tien_thieu = !empty($contractDB['tong_so_tien_thieu']) ? $contractDB['tong_so_tien_thieu'] : 0;
        $phi_phat_tat_toan_truoc_han = !empty($debtData['phi_thanh_toan_truoc_han']) ? $debtData['phi_thanh_toan_truoc_han'] : 0;
        $tong_penalty_con_lai = !empty($contractDB['tong_penalty_con_lai']) ? $contractDB['tong_penalty_con_lai'] : 0;
        $tien_du_ky_truoc = !empty($contractDB['tien_du_ky_truoc']) ? $contractDB['tien_du_ky_truoc'] : 0;
        $phi_phat_cham_tra_thanhtoan = !empty($contractDB['penalty_pay']) ? $contractDB['penalty_pay'] : 0;
        $tien_chua_tra_ky_thanh_toan = !empty($contractDB['tien_chua_tra_ky_thanh_toan']) ? $contractDB['tien_chua_tra_ky_thanh_toan'] : 0;
        $tien_thua_thanh_toan = !empty($contractDB['tien_thua_thanh_toan']) ? $contractDB['tien_thua_thanh_toan'] : 0;
        $phiThanhToan = [];
		$expire_date = !empty($contractDB['expire_date']) ? date("Y-m-d", $contractDB['expire_date']) : '';
		if (date('Y-m-d', $date_pay_coupon) == $expire_date) {
			$amount_giam_lai_tru_vao_ky_cuoi = $this->get_interest_end_period_by_coupon($dataDB['code_contract'], $dataDB['loan_infor']['code_coupon'])['amount_interest_reduction'];
			$lai_suat_tru_vao_ky_cuoi = floor($amount_giam_lai_tru_vao_ky_cuoi);
		}
        if (
            strtotime(date('Y-m-d',$contractDB['ngay_ket_thuc']). ' 00:00:00') 
            <=
            strtotime(date('Y-m-d',$contractDB['date_pay']). ' 00:00:00') 
        ) {
            $tong_tien_thanh_toan_thanhtoan = $du_no_con_lai_thanhtoan 
                                            + $phi_phat_cham_tra_thanhtoan 
                                            + $phi_phat_tat_toan_truoc_han
                                            + $phi_phat_sinh_thanhtoan
                                            + $tien_chua_tra_ky_thanh_toan 
                                            - $tien_du_ky_truoc
                                            - $tien_thua_thanh_toan
                                            - $tien_giam_tru_bhkv
                                            + $tong_so_tien_thieu
											- $lai_suat_tru_vao_ky_cuoi;
            $phiThanhToan = [
                'du_no_con_lai'                 => $du_no_con_lai_thanhtoan,
                'phi_phat_cham_tra'             => $phi_phat_cham_tra_thanhtoan,
                'phi_phat_tat_toan_truoc_han'   => $phi_phat_tat_toan_truoc_han,
                'phi_phat_sinh'                 => $phi_phat_sinh_thanhtoan,
                'tien_chua_tra_ky_thanh_toan'   => $tien_chua_tra_ky_thanh_toan, 
                'tien_du_ky_truoc'              => - $tien_du_ky_truoc,
                'tien_thua_thanh_toan'          => - $tien_thua_thanh_toan,
                'tien_giam_tru_bhkv'            => - $tien_giam_tru_bhkv,
                'tong_so_tien_thieu'            => $tong_so_tien_thieu,
                'lai_suat_tru_vao_ky_cuoi'      => $lai_suat_tru_vao_ky_cuoi
            ];
        } else {
            $phiThanhToan = [
                'phi_phat_cham_tra'             => $phi_phat_cham_tra_thanhtoan,
                'tien_du_ky_truoc'              => - $tien_du_ky_truoc,
                'tien_thua_thanh_toan'          => - $tien_thua_thanh_toan,
            ];
            $tong_tien_thanh_toan_thanhtoan = $contractDB['total_money_paid'];
        }
        
        $du_no_con_lai_tt =0;
        $tien_lai_tt = 0;
        $tien_phi_tt = 0;
        $tong_tien_thanh_toan_tt = 0;
        $phi_phat_cham_tra_tt =0; 
        $tong_penalty_con_lai=0;
        $du_no_con_lai_tt = !empty($dataTatToanPart1['du_no_con_lai']) ? $dataTatToanPart1['du_no_con_lai'] : 0;
        $tien_lai_tt = !empty($dataTatToanPart1['lai_chua_tra_den_thoi_diem_hien_tai']) ? $dataTatToanPart1['lai_chua_tra_den_thoi_diem_hien_tai'] : 0;
        $tien_phi_tt = !empty($dataTatToanPart1['phi_chua_tra_den_thoi_diem_hien_tai']) ? $dataTatToanPart1['phi_chua_tra_den_thoi_diem_hien_tai'] : 0;
        $phi_phat_sinh_tt = !empty($contractDB['phi_phat_sinh']) ? $contractDB['phi_phat_sinh'] : 0;
        $phi_phat_tat_toan_truoc_han = !empty($debtData['phi_thanh_toan_truoc_han']) ? $debtData['phi_thanh_toan_truoc_han'] : 0;
        $tong_penalty_con_lai = !empty($contractDB['tong_penalty_con_lai']) ? $contractDB['tong_penalty_con_lai'] : 0;
        $tien_du_ky_truoc = !empty($contractDB['tien_du_ky_truoc']) ? $contractDB['tien_du_ky_truoc'] : 0;
        $phi_phat_cham_tra_tt = !empty($contractDB['penalty_pay']) ? $contractDB['penalty_pay'] : 0;
        $tien_chua_tra_ky_thanh_toan = !empty($contractDB['tien_chua_tra_ky_thanh_toan']) ? $contractDB['tien_chua_tra_ky_thanh_toan'] : 0;
        $tien_thua_thanh_toan = !empty($contractDB['tien_thua_thanh_toan']) ? $contractDB['tien_thua_thanh_toan'] : 0;

        //  Lấy đơn miễn giảm của kỳ hiện tại
        $tien_giam_tru_tattoan = 0;
        $tien_giam_tru_thanhtoan = 0;
        //  Lấy ngày đến hạn và kỳ trả hiện tại chưa gạch nợ gần nhất (tính từ ngày giải ngân) từ bảng temporary_plan_contract
        $period_contract = $this->get_current_period($dataDB['code_contract']);
        $ky_tra_hien_tai = $period_contract['ky_tra_hien_tai'];
        $exemption_contracts = $this->exemptions_model->find_where(['code_contract' => $dataDB['code_contract']]);

        foreach ($exemption_contracts as $key => $contract_ex) {
            $type_payment_exem= (!empty($contract_ex->type_payment_exem) && $contract_ex->type_payment_exem==2) ? 2 : 1;
            if ($contract_ex->ky_tra == $ky_tra_hien_tai && $type_payment_exem == 1) {
                $exemption_contract = $contract_ex;
            } else if($type_payment_exem == 2){
                $exemption_contract = $contract_ex;
            }
        }

        //  Lấy transaction đã áp dụng miễn giảm
        $transaction_discount = $this->get_transaction_discount($dataDB['code_contract'], $ky_tra_hien_tai);
        $issetTransactionDiscount = $transaction_discount['check_discount'];

        // Lấy tiền giảm trừ tất toán
        if ( 
            ($issetTransactionDiscount == false) && 
            !empty($exemption_contract->status) && 
            ($exemption_contract->status == 7 || $exemption_contract->status == 5) && 
            ($exemption_contract->status != 6) && ($type_payment_exem == 2)
        ) {
            $tien_giam_tru_tattoan= !empty($exemption_contract->amount_tp_thn_suggest)  ? $exemption_contract->amount_tp_thn_suggest : 0;
        }

        // Lấy tiền giảm trừ thanh toán
        if ( 
            ($issetTransactionDiscount == false) && 
            !empty($exemption_contract->status) && 
            ($exemption_contract->status == 7 || $exemption_contract->status == 5) && 
            ($exemption_contract->status != 6) && ($type_payment_exem == 1)
        ) {
            $tien_giam_tru_thanhtoan= !empty($exemption_contract->amount_tp_thn_suggest)  ? $exemption_contract->amount_tp_thn_suggest : 0;
        }
        // End lấy đơn miễn giảm

        $tong_tien_thanh_toan_tt = $du_no_con_lai_tt 
                                + $phi_phat_cham_tra_tt 
                                + $phi_phat_tat_toan_truoc_han
                                + $phi_phat_sinh_tt 
                                + $tien_chua_tra_ky_thanh_toan 
                                - $tien_du_ky_truoc
                                - $tien_thua_thanh_toan 
                                - $tien_giam_tru_tattoan 
                                - $tien_giam_tru_bhkv
                                + $tong_so_tien_thieu
								- $lai_suat_tru_vao_ky_cuoi;
        $phiTatToan = [
            'du_no_con_lai'                 => $du_no_con_lai_tt,
            'phi_phat_cham_tra'             => $phi_phat_cham_tra_tt, 
            'phi_phat_tat_toan_truoc_han'   => $phi_phat_tat_toan_truoc_han,
            'phi_phat_sinh'                 => $phi_phat_sinh_tt,
            'tien_chua_tra_ky_thanh_toan'   => $tien_chua_tra_ky_thanh_toan,
            'tien_du_ky_truoc'              => - $tien_du_ky_truoc,
            'tien_thua_thanh_toan'          => - $tien_thua_thanh_toan,
            'tien_giam_tru_tattoan'         => - $tien_giam_tru_tattoan,
            'tien_giam_tru_bhkv'            => - $tien_giam_tru_bhkv,
            'tong_so_tien_thieu'            => $tong_so_tien_thieu,
			'lai_suat_tru_vao_ky_cuoi'      => $lai_suat_tru_vao_ky_cuoi
        ];
        $tong_tien_thanh_toan_thanhtoan = $tong_tien_thanh_toan_thanhtoan - $tien_giam_tru_thanhtoan;
        $phiThanhToan['tien_giam_tru_thanhtoan'] = - $tien_giam_tru_thanhtoan;
        if($contractDB['status']==19) {
           $tong_tien_thanh_toan_tt =0;
        }
        //test update tong tien thanh toan to contract table
		$this->contract_model->update(
			array('_id' => $dataDB["_id"]),
			array(
				'tong_tien_thanh_toan_tt' => $tong_tien_thanh_toan_tt,
				'lai_da_giam' => $lai_suat_tru_vao_ky_cuoi,
			)
		);
        $condition = array(
            'code_contract' => $dataDB['code_contract']
        );
        if ($getAmountOnly) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'id' => $id_contract,
                'code_contract' => $dataDB['code_contract'],
                'tong_tien_thanh_toan' => $tong_tien_thanh_toan_thanhtoan,
                'tong_tien_tat_toan' => $tong_tien_thanh_toan_tt,
                'phi_thanh_toan' => $phiThanhToan,
                'phi_tat_toan' => $phiTatToan
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $contractDB["data"] = $this->contract_tempo_model->getAll($condition);
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'tong_tien_thanh_toan' => $tong_tien_thanh_toan_thanhtoan,
                'tong_tien_tat_toan' => $tong_tien_thanh_toan_tt,
                'dataTatToanPart1' => $dataTatToanPart1,
                'debtData' => $debtData,
                'contractDB' => $contractDB,
                'giam_tru_tat_toan' => $tien_giam_tru_tattoan,
                'giam_tru_thanh_toan' => $tien_giam_tru_thanhtoan,
                
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }
       
    public function payment_all_contract_post()
    {

        if (!empty($this->dataPost['id_contract'])) {
            $id_contract = $this->security->xss_clean($this->dataPost['id_contract']);
            $dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId( $id_contract) ,'status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42]]));
        } else if (!empty($this->dataPost['code_contract'])) {
            $code_contract = $this->security->xss_clean($this->dataPost['code_contract']);
            $dataDB = $this->contract_model->findOne(array("code_contract" => $code_contract ,'status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42]]));
        } else {
            $dataDB = [];
        }

        if (empty($dataDB)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại "
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $id_contract = (string)$dataDB['_id'];

        if(!empty($dataDB['type_gh'])) {
            if($dataDB['type_gh']=='origin') {
                $this->re_run_giahan(['contract_id'=>$id_contract]);
            }else{
                $dataOrigin = $this->contract_model->findOne(array("code_contract" => $dataDB['code_contract_parent_gh']));
                $this->re_run_giahan(['contract_id'=>(string)$dataOrigin['_id']]);
            }
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Chạy lại gia hạn Thành công"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if(!empty($dataDB['type_cc'])) {
            if($dataDB['type_cc']=='origin') {
                $this->re_run_cocau(['contract_id'=>$id_contract]);
            } else {
                $dataOrigin = $this->contract_model->findOne(array("code_contract" => $dataDB['code_contract_parent_cc']));
                $this->re_run_cocau(['contract_id'=>(string)$dataOrigin['_id']]);
            }
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Chạy lại cơ cấu Thành công"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $data_delete = array(
            "code_contract" =>  $dataDB['code_contract'],
        );
        $date_pay = !empty($this->dataPost['date_pay']) ? $this->dataPost['date_pay'] : strtotime(date('Y-m-d'). ' 23:59:59');
        $contract_ck = $this->contract_model->findOne(array("code_contract" =>  $dataDB['code_contract']));
        if( isset($contract_ck['contract_lock']) && $contract_ck['contract_lock']=='lock') {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng đã khóa"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        // Lấy phiếu thu Tất toán thành công (loại thanh toán lãi kỳ)
        $transaction_finish = $this->transaction_model->find_one_tran_finish(['code_contract' => $dataDB['code_contract']]);
		$type_payment = '1';
		$type_transaction = '4';
        if (!empty($transaction_finish)) {
        	$type_payment = $transaction_finish['type_payment'];
			$type_transaction = $transaction_finish['type'];
		}

		// Update chậm trả và lãi đã trừ kỳ cuối
//		$this->get_interest_end_period_by_coupon($dataDB['code_contract'], $dataDB['loan_infor']['code_coupon']);
        $result_delete_and_update = $this->payment_model->delete_lai_ky_lai_thang($data_delete);
        if (!empty($result_delete_and_update['status']) && $result_delete_and_update['status'] == 200) {
            $data_generate = array(
                "code_contract" => $dataDB['code_contract'],
                "investor_code" =>$dataDB['investor_code'],
                "disbursement_date" => $dataDB['disbursement_date'],
                "date_pay" => $date_pay,
				"type_payment" => $type_payment,
				"type_transaction" => $type_transaction
            );
            $result_generate = $this->generate_model->processGenerate($data_generate);
            
            if (!empty($result_generate['status']) && $result_generate['status'] == 200) {
                
                $result  = $this->allocation_model->payment_all_contract($data_generate);
                $store_apply_contract_digital = $this->check_store_create_contract_digital($dataDB['store']['id']);
                if ($store_apply_contract_digital) {
					if (!empty($transaction_finish)) {
						// Check BBBG điện tử đã tồn tại chưa
						$exists_bbbgs = false;
						if (!empty($dataDB['megadoc']['bbbg_after_sign']['status'] && in_array($dataDB['megadoc']['bbbg_after_sign']['status'], [0,1,2,3,7,99]))) {
							$exists_bbbgs = true;
						}
						// Gọi function tạo BBBG TS sau khi thanh lý TTBB
						if (!$exists_bbbgs) {
							$status_create_bbbgs = 19;
							$this->create_contract_megadoc($dataDB, $status_create_bbbgs);
						}
					}
				}
            }else{
                $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "gen lãi không thành công"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;

            }
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "xóa không thành công"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
       $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Chạy lại thanh toán Thành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function re_payment_gh_cc_hd_goc($code_contract, $code_contract_origin, $last = 0, $type_gh_cc = "") {
        $dt_contract = $this->contract_model->findOne(['code_contract'=> $code_contract]);
        $data_delete = array(
            "code_contract" => $code_contract,
            "code_contract_disbursement_origin" => $code_contract_origin,
            "type_gh" => "origin"
        );
        $result_delete_and_update = $this->payment_model->delete_lai_ky_lai_thang($data_delete);
        if (!empty($result_delete_and_update['status']) && $result_delete_and_update['status'] == 200) {
            $dataPost_dele = array(
                "code_contract" => $dt_contract['code_contract'],
                "code_contract_origin" => $dt_contract['code_contract'],
                "code_contract_disbursement_origin" => $code_contract_origin,
                "investor_code" => $dt_contract['investor_code'],
                "disbursement_date" => $dt_contract['disbursement_date'],
                "last" => $last,
                "type_gh_cc" => $type_gh_cc
            );
            $result_delete = $this->generate_model->processGenerate($dataPost_dele);
            if (!empty($result_delete['status']) && $result_delete['status'] == 200) {
                $result = $this->allocation_model->payment_all_contract_gh_cc($dataPost_dele);
            }
            if($type_gh_cc=="GH") {
                $this->allocation_model->generate_money_gh($dataPost_dele);
                $this->allocation_model->generate_money_thieu_gh($dataPost_dele);
            
            }
        }
    }

    public function re_run_giahan($data) {
        $data['contract_id'] = $this->security->xss_clean($data['contract_id']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId( $data['contract_id'])));
        if (!empty($contract)) {
            if( isset($contract['contract_lock']) && $contract['contract_lock']=='lock') {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Hợp đồng đã khóa"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!empty($contract['extend_all'])) {
                $arrPost = array(
                    "code_contract" => $contract['code_contract']
                );
                $array_gh = $contract['extend_all'];
                $array_gh_sort = array_sort($array_gh, 'so_lan', SORT_ASC);
                foreach ($array_gh_sort as $key => $value) {
                    $so_lan = $value['so_lan'];
                    if ($so_lan == 1) {
                        $this->re_payment_gh_cc_hd_goc($contract['code_contract'], $contract['code_contract_disbursement'], 0, "GH");
                    }
                    $arrPost["number_day_loan"] = $value['number_day_loan'];
                    $arrPost["so_lan"] = $so_lan;
                    $arrPost["extend_date"] = $value['extend_date'];

                    $resultExtension =  $this->allocation_model->check_approve_gia_han($arrPost);
                    if ($resultExtension['status'] == 200) {
                        $resultExtension=$resultExtension['data'];
                        $arrPost['code_contract'] = $resultExtension['code_contract'];
                        $dt_contract = $this->contract_model->findOne(['code_contract'=> $arrPost['code_contract']]);
                        $dataPost_dele = array(
                            "code_contract" => $dt_contract['code_contract'],
                            "code_contract_origin" => $contract['code_contract'],
                            "code_contract_disbursement_origin" => $contract['code_contract_disbursement'],
                            "investor_code" => $dt_contract['investor_code'],
                            "disbursement_date" => $dt_contract['disbursement_date'],
                            "type_gh_cc" => 'GH'
                        );

                        $result_delete_and_update = $this->payment_model->delete_lai_ky_lai_thang($dataPost_dele);
                        if (!empty($result_delete_and_update['status']) && $result_delete_and_update['status'] == 200) {
                            $result_delete = $this->generate_model->processGenerate($dataPost_dele);
                            if (!empty($result_delete['status']) && $result_delete['status'] == 200) {
                                $update_stt = array('status' => 33);
                                if ($so_lan == 1) {
                                    $update_stt['code_contract'] = $contract['code_contract'];
                                    
                                    $this->contract_model->update(
                                        array("code_contract" =>$update_stt['code_contract']),
                                        $update_stt
                                    );
                                } else if ($so_lan != 1 && $so_lan < count($array_gh)) {
                                    $update_stt['code_contract'] = $arrPost['code_contract'];
                                    
                                    $this->contract_model->update(
                                        array("code_contract" =>$update_stt['code_contract']),
                                        $update_stt
                                    );

                                }
                                if ($so_lan == count($array_gh)) {
                                    $dataPost_dele['last'] = 1;
                                }
                                $this->generate_model->processGenerate($dataPost_dele);
                                $this->allocation_model->payment_all_contract_gh_cc($dataPost_dele);
                                if ($dataPost_dele['last'] != 1) {
                                    $this->allocation_model->generate_money_gh($dataPost_dele);
                                }
                                $this->allocation_model->generate_money_thieu_gh($dataPost_dele);
                            }
                        }

                    } else {
                        $response = array(
                            'status' => '400',
                            'msg' => 'OK',
                        );
                        return $response;
                    }
                }
                $response = array(
                    'status' => '200',
                    'msg' => 'OK',
                );
                return $response;
            } else {
                $response = array(
                    'status' => '400',
                    'msg' => 'OK',
                );
                return $response;
            }
        } else {
               $response = array(
                    'status' => '400',
                    'msg' => 'OK',
                );
                return $response;
        }
    }

    public function re_run_cocau($data) {
        $data['contract_id'] = $this->security->xss_clean($data['contract_id']);
        $contract =$this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId( $data['contract_id'])));
        if (!empty($contract)) {
            if( isset($contract['contract_lock']) && $contract['contract_lock']=='lock') {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Hợp đồng đã khóa"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!empty($contract['structure_all'])) {
                $arrPost = array(
                    "code_contract" => $contract['code_contract']
                );
                $array_cc = $contract['structure_all'];
                $array_cc_sort = array_sort($array_cc, 'so_lan', SORT_ASC);

                foreach ($array_cc_sort as $key => $value) {
                    $so_lan = $value['so_lan'];
                    if ($so_lan == 1) {
                        $this->re_payment_gh_cc_hd_goc($contract['code_contract'], $contract['code_contract_disbursement'], 0, "CC");
                    }
                    $arrPost["number_day_loan"] = $value['number_day_loan'];
                    $arrPost["so_lan"] = $so_lan;
                    $arrPost["amount_money"] = $value['amount_money'];
                    $arrPost["type_loan"] = $value['type_loan']['code'];
                    $arrPost["type_interest"] = $value['type_interest'];
                    $arrPost["structure_date"] = $value['structure_date'];
                    $resultExtension= $this->allocation_model->check_approve_co_cau($arrPost);
                    if ($resultExtension['status'] == 200) {
                        $resultExtension=$resultExtension['data'];
                        $dt_contract = $this->contract_model->findOne(array("code_contract" => $resultExtension['code_contract']));
                        $dataPost_dele = array(
                            "code_contract" => $dt_contract['code_contract'],
                            "code_contract_origin" => $contract['code_contract'],
                            "code_contract_disbursement_origin" => $contract['code_contract_disbursement'],
                            "investor_code" => $dt_contract['investor_code'],
                            "disbursement_date" => $dt_contract['disbursement_date'],
                            "type_gh_cc" => 'CC'
                        );
                        $result_delete_and_update = $this->payment_model->delete_lai_ky_lai_thang($dataPost_dele);
                        if (!empty($result_delete_and_update['status']) && $result_delete_and_update['status'] == 200) {
                            $result_delete = $this->generate_model->processGenerate($dataPost_dele);
                            if (!empty($result_delete['status']) && $result_delete['status'] == 200) {
                                $update_stt = array('status' => 34);
                                if ($so_lan == 1) {
                                    $update_stt['code_contract'] = $contract['code_contract'];
                                    
                                    $this->contract_model->update(
                                        array("code_contract" =>$update_stt['code_contract']),
                                        $update_stt
                                    );
                                } else if ($so_lan != 1 && $so_lan < count($array_cc)) {
                                    $update_stt['code_contract'] = $arrPost['code_contract'];
                                    $this->contract_model->update(
                                        array("code_contract" =>$update_stt['code_contract']),
                                        $update_stt
                                    );
                                }
                                if ($so_lan == count($array_cc)) {
                                    $dataPost_dele['last'] = 1;
                                }
                                $this->allocation_model->payment_all_contract_gh_cc($dataPost_dele);
                            }
                        }

                    } else {
                        $response = array(
                            'status' => '400',
                            'msg' => 'OK',
                        );
                        return $response;
                    }

                }
                $response = array(
                    'status' => '200',
                    'msg' => 'OK',
                );
                return $response;
            } else {
                $response = array(
                    'status' => '400',
                    'msg' => 'OK',
                );
                return $response;
            }
        } else {
            $response = array(
                'status' => '400',
                'msg' => 'OK',
            );
            return $response;
        }
    }

    public function get_current_period($code_contract)
    {
        $ky_tra_hien_tai = 0;
        $ngay_den_han = 0;
        $contract = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract]);
        $contract_tempo = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $code_contract, 'status' => 1]);
        $contract_tempo_all = $this->temporary_plan_contract_model->getKiDaThanhToanGanNhat($code_contract);
        $ky_tra_hien_tai = !empty($contract_tempo[0]['ky_tra']) ? intval($contract_tempo[0]['ky_tra']) : intval($contract_tempo_all[0]['ky_tra']);
        $ngay_den_han = !empty($contract_tempo[0]['ngay_ky_tra']) ? intval($contract_tempo[0]['ngay_ky_tra']) : intval($contract_tempo_all[0]['ngay_ky_tra']);

        $result = [
            'contract' => $contract,
            'ky_tra_hien_tai' => $ky_tra_hien_tai,
            'ngay_den_han' => $ngay_den_han,
        ];
        return $result;
    }

    //  Lấy transaction đã áp dụng miễn giảm
    public function get_transaction_discount($code_contract, $ky_tra_hien_tai)
    {
        $transaction_discount = $this->transaction_model->find_where(['code_contract' => $code_contract,'ky_tra_hien_tai' => $ky_tra_hien_tai, "type" => array('$in' => array(4)), "status" => array('$ne' => 3)]);
        $transaction_discount_finish = $this->transaction_model->find_where(['code_contract' => $code_contract,'ky_tra_hien_tai' => $ky_tra_hien_tai, "type" => array('$in' => array(3)), "status" => array('$ne' => 3)]);
        if (!empty($transaction_discount)) {
            $check_discount = false;
            foreach ($transaction_discount as $key => $tran) {
                if ($tran->discounted_fee > 0) {
                    $check_discount = true;
                }
            }
        }
        if (!empty($transaction_discount_finish)) {
            $check_discount_finish = false;
            foreach ($transaction_discount as $key1 => $tran_finish) {
                if ($tran_finish->discounted_fee > 0) {
                    $check_discount_finish = true;
                }
            }
        }
        $result = [
            'data' => $transaction_discount,
            'check_discount' => $check_discount,
            'check_discount_finish' => $check_discount_finish,

        ];
        return $result;
    }

	// Update chậm trả và lãi đã trừ kỳ cuối
		private function get_interest_end_period_by_coupon($code_contract, $code_coupon)
	{
		$coupon_infor = $this->coupon_model->findOne(['code' => $code_coupon]);
		$contractDB = $this->contract_model->find_one_select(['code_contract' => $code_contract],['_id']);
		$temporary_plan_contracts = $this->contract_tempo_model->find_where(['code_contract' => $code_contract]);
		$tempo_not_pay = $this->contract_tempo_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
		$current_day = strtotime(date('Y-m-d') . ' 23:59:59');
		$date_pay_tempo = !empty($tempo_not_pay[0]['ngay_ky_tra']) ? intval($tempo_not_pay[0]['ngay_ky_tra']) : $current_day;
		$time = intval(($current_day - strtotime(date('Y-m-d', $date_pay_tempo) . ' 23:59:59')) / (24 * 60 * 60));
		$is_payment_slow = false;
		$reduced_profit = 0;
		$type_interest_reduction = '';
		if ($time > 0) {
			$is_payment_slow = true;
		}
		if (!empty($coupon_infor)) {
			if (!empty($temporary_plan_contracts)) {
				// giam lai 03 thang dau
				if (isset($coupon_infor['is_reduction_interest']) && $coupon_infor['is_reduction_interest'] == "active") {
					$type_interest_reduction = '3';
					foreach ($temporary_plan_contracts as $key => $tempo) {
						if ($tempo['ky_tra'] == 1 || $tempo['ky_tra'] == 2 || $tempo['ky_tra'] == 3) {
							$reduced_profit += $tempo['lai_ky'];
						}
					}
					// giam lai 01 thang dau
				} elseif (isset($coupon_infor['down_interest_on_month']) && $coupon_infor['down_interest_on_month'] == "active") {
					$type_interest_reduction = '1';
					foreach ($temporary_plan_contracts as $key1 => $tempo1) {
						if ($tempo1['ky_tra'] == 1) {
							$reduced_profit += $tempo1['lai_ky'];
						}
					}
				}
				//fore bảng lãi kỳ để xác định chậm trả
				foreach ($temporary_plan_contracts as $temporary) {
					if ($temporary['so_ngay_cham_tra'] > 0) {
						$is_payment_slow = true;
						break;
					}
				}
			}
		}
		$response = array(
			'amount_interest_reduction' => (!$is_payment_slow) ? $reduced_profit : 0,
		);
		if ($is_payment_slow) {
			$this->contract_model->update(
				array('_id' => $contractDB['_id']),
				array(
					'interest_reduction.isset_date_late' => true,
					'interest_reduction.amount_interest_reduction' => $response['amount_interest_reduction'],
					'interest_reduction.type_interest_reduction' => $type_interest_reduction,
					'interest_reduction.time' => strtotime(date("Y-m-d")),
					'interest_reduction.by' => 'VPBank'
				)
			);
		} else {
			$this->contract_model->update(
				array('_id' => $contractDB['_id']),
				array(
					'interest_reduction.isset_date_late' => false,
					'interest_reduction.amount_interest_reduction' => $response['amount_interest_reduction'],
					'interest_reduction.type_interest_reduction' => $type_interest_reduction,
					'interest_reduction.time' => strtotime(date("Y-m-d")),
					'interest_reduction.by' => 'VPBank'
				)
			);
		}
		return $response;
	}

	// check HĐ có phát sinh ngày chậm trả hay không
	private function check_number_day_pay_late($code_contract, $date_pay)
	{
		$temporary_plan_contracts = $this->contract_tempo_model->find_where(['code_contract' => $code_contract]);
		$tempo_not_pay = $this->contract_tempo_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
		$current_day = !empty($date_pay) ? $date_pay : strtotime(date('Y-m-d') . '  23:59:59');
		$date_pay_tempo = !empty($tempo_not_pay[0]['ngay_ky_tra']) ? intval($tempo_not_pay[0]['ngay_ky_tra']) : $current_day;
		$time = intval(($current_day - strtotime(date('Y-m-d', $date_pay_tempo))) / (24 * 60 * 60));
		$is_payment_slow = false;
		if ($time > 0) {
			$is_payment_slow = true;
		}
		if (!empty($temporary_plan_contracts)) {
			//fore bảng lãi kỳ để xác định chậm trả
			foreach ($temporary_plan_contracts as $temporary) {
				if ($temporary['so_ngay_cham_tra'] > 0) {
					$is_payment_slow = true;
					break;
				}
			}
		}
		return $is_payment_slow;
	}


	/** Tạo BBBG TS điện tử sau khi thanh lý TTBB
	 * @param $contractInfo
	 * @param $status
	 * @return array
	 */
	private function create_contract_megadoc($contractInfo, $status)
	{
		$type_doc = '';
		if (!empty($contractInfo['loan_infor']['type_property']['code']) && $contractInfo['loan_infor']['type_property']['code'] == 'NĐ') {
			$type_doc = "thc"; // thế chấp
		} elseif (($contractInfo['loan_infor']['type_loan']['code'] == 'CC' || $contractInfo['loan_infor']['type_loan']['code'] == 'DKX') && $contractInfo['loan_infor']['type_property']['code'] != "TC") {
			$type_doc = "cv"; // cho vay
		}
		$company_code = $this->check_store_tcv_dong_bac($contractInfo['store']['id']);
		$conditon_check_file = array();
		$conditon_check_file['type_doc'] = $type_doc;
		$conditon_check_file['code_contract'] = $contractInfo['code_contract'];
		$conditon_check_file['company_code'] = $company_code;
		$conditon_check_file['status_approve'] = $status;
		$contractInfo['status_approve'] = $status;
		// Tạo file docx thỏa thuận ba bên và biên bản bàn giao

		$create_docx_file = $this->create_contract_docx_file($contractInfo);
		$convert_pdf = $this->execute_convert_docx($conditon_check_file);
		// Tạo metadata và filecontent sendApi
		$metadata = $this->create_metadata_megadoc($contractInfo);
		$filecontent = $this->check_path_file_contract($conditon_check_file);
		$dataSend = array(
			'metadata' => json_encode($metadata),
			'filecontent' => $filecontent
		);
		$check_company_send = $company_code;
		$megadoc = new DigitalContractMegadoc();
		$res_megadoc = $megadoc->create_contract($dataSend,$check_company_send);
		$action_log = "create_contract";
		$this->log_megadoc(json_encode($dataSend), $res_megadoc, $contractInfo['code_contract'], $action_log);
		if (!empty($res_megadoc)) {
			if (!empty($res_megadoc->Success) && $res_megadoc->Success == true) {
				$content ="";
				$link_tra_cuu_megadoc = $this->config->item("link_tra_cuu_megadoc");
				$sms_tcv_bbbgt = $this->config->item("sms_tcv_bbbgt");
				$content = $sms_tcv_bbbgt . $link_tra_cuu_megadoc . $res_megadoc->SearchKey;
				if ($company_code == "TCV") {
					//remove file docx
					if ($contractInfo['loan_infor']['type_property']['code'] == 'NĐ') {
						unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_tl_' . $contractInfo['code_contract'] . '.docx');
						unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_tl_' . $contractInfo['code_contract'] . '.pdf');
					} elseif (($contractInfo['loan_infor']['type_loan']['code'] == 'CC' || $contractInfo['loan_infor']['type_loan']['code'] == 'DKX') && $contractInfo['loan_infor']['type_property']['code'] != "TC") {
						unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_tl_' . $contractInfo['code_contract'] . '.docx');
						unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_tl_' . $contractInfo['code_contract'] . '.pdf');
					}
				} elseif ($company_code == "TCVĐB") {
					if ($contractInfo['loan_infor']['type_property']['code'] == 'NĐ') {
						unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_tl_' . $contractInfo['code_contract'] . '.docx');
						unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_tl_' . $contractInfo['code_contract'] . '.pdf');
					} elseif (($contractInfo['loan_infor']['type_loan']['code'] == 'CC' || $contractInfo['loan_infor']['type_loan']['code'] == 'DKX') && $contractInfo['loan_infor']['type_property']['code'] != "TC") {
						unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_tl_' . $contractInfo['code_contract'] . '.docx');
						unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_tl_' . $contractInfo['code_contract'] . '.pdf');
					}
				}
				//status_email = 2 - Gửi thông báo ký số qua tin nhắn SMS
				if (!empty($contractInfo['customer_infor']['status_email']) && $contractInfo['customer_infor']['status_email'] == 2) {
					//chuan bi gui SMS ky so cho khach hang
					$template = "";
					$template = "60b72056a51b0a227bf4526b";
					$type_sms = "ky_so";
					$type_document = 'bbbgs';
					// insert sms content to database
					$id_sms = $this->insert_sms_megadoc($contractInfo, $template, $content, $res_megadoc->SearchKey, $res_megadoc->FKey, $type_sms, $type_document);
					// send sms to customer
					$data_send_api_sms = array(
						"template" => $template,
						"number" => $contractInfo['customer_infor']['customer_phone_number'],
						"content" => $content
					);
					$res_sms = $this->push_api_sms('POST', json_encode($data_send_api_sms), "/sms");
					$this->log_megadoc($data_send_api_sms, $res_sms, $contractInfo['code_contract'], 'ky-so');
					if (!empty($res_sms)) {
						if (isset($res_sms->sendTime)) {
							$sms_update['status'] = 'success';
							$sms_update['response'] = $res_sms;
							$sms_update['send_time'] = time();
							$this->sms_megadoc_model->update(
								[
									'_id' => $id_sms
								], $sms_update
							);
						} else {
							$sms_update['status'] = 'fail';
							$sms_update['response'] = $res_sms;
							$sms_update['send_time'] = time();
							$this->sms_megadoc_model->update(
								[
									'_id' => $id_sms
								], $sms_update
							);
						}
					}
					//Ket thuc gui SMS ky so cho KH
				}
				$arrUpdate = array(
					'megadoc.bbbg_after_sign.searchkey' => $res_megadoc->SearchKey,
					'megadoc.bbbg_after_sign.fkey' => $res_megadoc->FKey,
					'megadoc.bbbg_after_sign.status' => 1,
					'megadoc.bbbg_after_sign.type_doc' => "bbbg_sau",
					'megadoc.bbbg_after_sign.code_contract' => $contractInfo['code_contract'],
					'megadoc.bbbg_after_sign.contract_no' => $metadata['ContractNo'],
				);
				$this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);

				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => "Tạo BBBGS Megadoc thành công!",
				);
				return $response;
			} else {
				if (!empty($res_megadoc->ErrorMessage)) {
					$message = $res_megadoc->ErrorMessage;
				} else {
					$message = status_contract_megadoc_response($res_megadoc);
				}
				// 99 trạng thái gọi sang đối tác ko thành công
				$arrUpdate = array(
					'megadoc.bbbg_after_sign.status' => 99,
					'megadoc.bbbg_after_sign.type_doc' => "bbbg_sau",
					'megadoc.bbbg_after_sign.code_contract' => $contractInfo['code_contract'],
					'megadoc.bbbg_after_sign.contract_no' => $metadata['ContractNo'],
				);
				$this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);
				$response = array(
					'status' => REST_Controller::HTTP_BAD_REQUEST,
					'message' => $message,
				);
				return $response;
			}
		} else {
			$arrUpdate = array(
				'megadoc.bbbg_after_sign.status' => 99,
				'megadoc.bbbg_after_sign.type_doc' => "bbbg_sau",
				'megadoc.bbbg_after_sign.code_contract' => $contractInfo['code_contract'],
				'megadoc.bbbg_after_sign.contract_no' => $metadata['ContractNo'],
			);
			$this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => "Không kết nối được tới Megadoc!",
			);
			return $response;
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

	private function create_contract_docx_file($data)
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$code_contract = !empty($data['code_contract']) ? $data['code_contract'] : '';
		$mydate = getdate(date("U"));
		$current_hours = $mydate['hours'];
		$current_minutes = $mydate['minutes'];
		if ($current_hours < 10) {
			$current_hours = '0'.$current_hours;
		}
		if ($current_minutes < 10) {
			$current_minutes = '0'.$current_minutes;
		}
		$date_sign_ttbb = '';
		$date_sign_bbbgt = '';
		$day = '';
		$mon = '';
		$year = $mydate['year'];
		$customerDOB = '';
		$identify_date_range = '';
		$type_interest = '';
		$sokhung = '';
		$somay = '';
		$bienkiemsoat = '';
		$nhanhieu = '';
		$model = '';
		$chuxe = '';
		$diachidangky = '';
		$sodangky = '';
		$ngaycapdangky = '';
		$disbursement_date = '';
		$gic_easy_20 = '';
		$gic_easy_40 = '';
		$gic_easy_70 = '';
		$goi_gic = '';
		$chiphivaythang = '';
		$short_name_province = '';
		$code_address_store = '';
		$loai_tai_san = '';
		$thua_dat_so = '';
		$to_ban_do_so = '';
		$dia_chi_thua_dat = '';
		$dien_tich = '';
		$hinh_thuc_su_dung_rieng = '';
		$hinh_thuc_su_dung_chung = '';
		$muc_dich_su_dung = '';
		$thoi_han_su_dung = '';
		$nha_o = '';
		$giay_chung_nhan_so = '';
		$noi_cap_so = '';
		$ngay_cap_so = '';
		$so_vao_so = '';
		$relative_with_contracter = '';
		$with_img_checkbox = 8;
		$vpbank = new VPBank();
		$assignVan = $vpbank->assignVan($code_contract);
		$data['vpbank_van']["van"] = isset($assignVan["van"]) ? $assignVan["van"] : "";
		if ($mydate['mday'] < 10) {
			$day = "0" . $mydate['mday'];
		} else {
			$day = $mydate['mday'];
		}
		if ($mydate['mon'] < 3) {
			$mon = "0" . $mydate['mon'];
		} else {
			$mon = $mydate['mon'];
		}
		if (!empty($data['customer_infor']['customer_BOD'])) {
			$dobArray = explode('-', $data['customer_infor']['customer_BOD']);
			$customerDOB = $dobArray[2] . '-' . $dobArray[1] . '-' . $dobArray[0];
		}
		if (!empty($data['customer_infor']['date_range'])) {
			$date_range_array = explode('-', $data['customer_infor']['date_range']);
			$identify_date_range = $date_range_array[2] . '-' . $date_range_array[1] . '-' . $date_range_array[0];
		}
		if (!empty($data['loan_infor']['type_interest'])) {
			if ($data['loan_infor']['type_interest'] == 1) {
				$type_interest = "Thanh toán gốc, lãi và các khoản phí";
			} else {
				$type_interest = "Thanh toán gốc cuối kỳ, lãi và các khoản phí";
			}
		}
		//Start Địa chỉ hộ khẩu
		$household_address = $data['houseHold_address']['address_household'] . ', ' . $data['houseHold_address']['ward_name'] . ', ' . $data['houseHold_address']['district_name'] . ', ' . $data['houseHold_address']['province_name'];
		//Start Địa chỉ đang ở
		$current_address_final = $data['current_address']['current_stay'] . ', ' . $data['current_address']['ward_name'] . ', ' . $data['current_address']['district_name'] . ', ' . $data['current_address']['province_name'];
		$store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['store']['id'])));
		if (!empty($store)) {
			$store_representative = $store['representative'];
		}
		$bank_id = $data['receiver_infor']['bank_id'];
		$bankNganLuongData = $this->bank_nganluong_model->findOne(array('bank_id' => $bank_id));
		$bank_name_nganluong = '';
		if (!empty($bankNganLuongData)) {
			$bank_name_nganluong = $bankNganLuongData['name'];
		}
		// check id_store TCV Đông Bắc
		$company_code = $this->check_store_tcv_dong_bac($data['store']['id']);
		if ($data['status_approve'] == 15 || $data['status_approve'] == 19) {
			$searchKeyTTBB = '';
			$searchKeyBBBGT = '';
			$searchKeyTTBB = !empty($data['megadoc']['ttbb']['searchkey']) ? $data['megadoc']['ttbb']['searchkey'] : '';
			$searchKeyBBBGT = !empty($data['megadoc']['bbbg_before_sign']['searchkey']) ? $data['megadoc']['bbbg_before_sign']['searchkey'] : '';
			$date_disbursement = !empty($data['disbursement_date']) ? $data['disbursement_date'] : strtotime(date('Y-m-d'));
			// Lấy ngày ký ttbb
			$date_sign_ttbb_search = $this->get_info_contract_megadoc($searchKeyTTBB, $company_code);
			$date_sign_ttbb = !empty($date_sign_ttbb_search) ? $date_sign_ttbb_search : date('d/m/Y', $date_disbursement);
			// Lấy ngày ký bbbgt
			$date_sign_bbbgt_search = $this->get_info_contract_megadoc($searchKeyBBBGT, $company_code);
			$date_sign_bbbgt = !empty($date_sign_bbbgt_search) ? $date_sign_bbbgt_search : date('d/m/Y', $date_disbursement);
		}
		if (!empty($data['loan_infor']['type_property']['code']) && $data['loan_infor']['type_property']['code'] == 'NĐ') {
			$property_land = !empty($data['property_infor']) ? $data['property_infor'] : array();
			foreach ($property_land as $p) {
				if ($p['slug'] === 'loai-tai-san') {
					$loai_tai_san = $p['value'];
				} elseif ($p['slug'] === 'thua-dat-so') {
					$thua_dat_so = $p['value'];
				} elseif ($p['slug'] === 'to-ban-do-so') {
					$to_ban_do_so = $p['value'];
				} elseif ($p['slug'] === 'dia-chi-thua-dat') {
					$dia_chi_thua_dat = $p['value'];
				} elseif ($p['slug'] === 'dien-tich-m2') {
					$dien_tich = $p['value'];
				} elseif ($p['slug'] === 'hinh-thuc-su-dung-rieng-m2') {
					$hinh_thuc_su_dung_rieng = $p['value'];
				} elseif ($p['slug'] === 'hinh-thuc-su-dung-chung-m2') {
					$hinh_thuc_su_dung_chung = $p['value'];
				} elseif ($p['slug'] === 'muc-dich-su-dung') {
					$muc_dich_su_dung = $p['value'];
				} elseif ($p['slug'] === 'thoi-han-su-dung') {
					$thoi_han_su_dung = $p['value'];
				} elseif ($p['slug'] === 'nha-o-neu-co') {
					$nha_o = $p['value'];
				} elseif ($p['slug'] === 'giay-chung-nhan-so') {
					$giay_chung_nhan_so = $p['value'];
				} elseif ($p['slug'] === 'noi-cap') {
					$noi_cap_so = $p['value'];
				} elseif ($p['slug'] === 'ngay-cap') {
					$ngay_cap_so = $p['value'];
				} elseif ($p['slug'] === 'so-vao-so') {
					$so_vao_so = $p['value'];
				}
			}
		} elseif (!empty($data['loan_infor']['type_loan']['code']) && ($data['loan_infor']['type_loan']['code'] == 'CC' || $data['loan_infor']['type_loan']['code'] == 'DKX') && $data['loan_infor']['type_property']['code'] != "TC") {
			$property = !empty($data['property_infor']) ? $data['property_infor'] : array();
			foreach ($property as $p) {
				if ($p['slug'] === 'bien-so-xe') {
					$bienkiemsoat = $p['value'];
				} elseif ($p['slug'] === 'so-khung') {
					$sokhung = $p['value'];
				} elseif ($p['slug'] === 'so-may') {
					$somay = $p['value'];
				} elseif ($p['slug'] === 'nhan-hieu') {
					$nhanhieu = $p['value'];
				} elseif ($p['slug'] === 'model') {
					$model = $p['value'];
				} elseif ($p['slug'] === 'ho-ten-chu-xe') {
					$chuxe = $p['value'];
				} elseif ($p['slug'] === 'dia-chi-dang-ky') {
					$diachidangky = $p['value'];
				} elseif ($p['slug'] === 'so-dang-ky') {
					$sodangky = $p['value'];
				} elseif ($p['slug'] === 'ngay-cap') {
					$ngaycapdangky = $p['value'];
				} elseif ($p['slug'] === 'ngay-cap-dang-ky') {
					$ngaycapdangkyoto = $p['value'];
				}
			}
		}
		$ngaycapdangkyxe = $ngaycapdangky ? $ngaycapdangky : $ngaycapdangkyoto;
		$appraise = number_format($data['loan_infor']['price_property']);
		$appraise_words = convert_number_to_words($data['loan_infor']['price_property']);
		// is =  1 => có tham gia; is = 2 => không tham gia
		$is_bh_tnnv_gic_mic = (($data['loan_infor']['insurrance_contract'] == '1' && $data['loan_infor']['loan_insurance'] == '1' && $data['loan_infor']['amount_GIC'] > 0) || ($data['loan_infor']['insurrance_contract'] == '1' && $data['loan_infor']['loan_insurance'] == '2' && $data['loan_infor']['amount_MIC'] > 0)) ? "1" : "2";
		$is_bh_pti_vta = (isset($data['loan_infor']['bao_hiem_pti_vta']) && isset($data['loan_infor']['bao_hiem_pti_vta']['price_pti_vta']) && $data['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] > 0) ? "1" : "2";
		if (!empty($data['loan_infor']['amount_GIC_easy']) && $data['loan_infor']['amount_GIC_easy'] == '348000') {
			$gic_easy_20 = "1";
			$goi_gic = 'GÓI 20';
		}
		if (!empty($data['loan_infor']['amount_GIC_easy']) && $data['loan_infor']['amount_GIC_easy'] == '398000') {
			$gic_easy_40 = "1";
			$goi_gic = 'GÓI 40';
		}
		if (!empty($data['loan_infor']['amount_GIC_easy']) && $data['loan_infor']['amount_GIC_easy'] == '598000') {
			$gic_easy_70 = "1";
			$goi_gic = 'GÓI 70';
		}
		$name_delivery_records = 'BBBG';
		$current_year_code_contract = date("y");
		$current_month_code_contract = date("m");
		$current_day_code_contract = date("d");
		$current_day_month_year = $current_year_code_contract . $current_month_code_contract . $current_day_code_contract;
		$current_time = date("d/m/Y");
		$code_province_store = !empty($store['province']['name']) ? $store['province']['name'] : "";
		$array_short_name_province = explode(" ", $code_province_store);
		foreach ($array_short_name_province as $short_name) {
			$short_name_province .= $short_name[0];
		}
		$code_address_store = !empty($store['code_address_store']) ? $store['code_address_store'] : "";
		// Create code BBBG (Biên bản bàn giao tài sản)
		$code_delivery_record = $name_delivery_records . "/" . $short_name_province . $code_address_store . "/" . $current_day_month_year . "/...";
		$code_delivery_records = strtoupper($code_delivery_record);
		$disbursement_date = !empty($data['disbursement_date']) ? date('d/m/Y', intval($data['disbursement_date']) + 7 * 60 * 60) : '...............................';
		$date_gn = isset($data['disbursement_date']) ? getdate($data['disbursement_date']) : array();
		$gia_tai_san = !empty($data['loan_infor']['price_property']) ? number_format($data['loan_infor']['price_property']) : "";
		$gia_tai_san_bang_chu = convert_number_to_words($data['loan_infor']['price_property']);
		if ($company_code == "TCV") {
			if ($data['loan_infor']['type_property']['code'] == 'NĐ') {
				// => Tạo file docx bbbg_thechap_tcv_tl_template
				if (file_exists('assets/file/file_megadoc/bbbg_thechap_tcv_tl_template.docx')) {
					$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/bbbg_thechap_tcv_tl_template.docx');
					$templateProcessor->setValue('mabbbg', $code_delivery_records ?? '');
					$templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
					$templateProcessor->setValue('disbursement_date', $disbursement_date ?? '');
					$templateProcessor->setValue('day', $day ?? '');
					$templateProcessor->setValue('mon', $mon ?? '');
					$templateProcessor->setValue('year', $year ?? '');
					$templateProcessor->setValue('store_address', $data['store']['address'] ?? '');
					$templateProcessor->setValue('customer_name', $data['customer_infor']['customer_name'] ?? '');
					$templateProcessor->setValue('customer_dob', $customerDOB ?? '');
					$templateProcessor->setValue('customer_identify', $data['customer_infor']['customer_identify'] ?? '');
					$templateProcessor->setValue('identify_date', $identify_date_range ?? '');
					$templateProcessor->setValue('identify_issued_by', $data['customer_infor']['issued_by'] ?? '');
					$templateProcessor->setValue('current_address', $current_address_final ?? '');
					$templateProcessor->setValue('household_address', $household_address ?? '');
					$templateProcessor->setValue('customer_phone', $data['customer_infor']['customer_phone_number'] ?? '');
					$templateProcessor->setValue('store_representative', $store['representative'] ?? '');
					$templateProcessor->setValue('amount_money', number_format($data['loan_infor']['amount_money']) ?? '');
					$templateProcessor->setValue('amount_loan', number_format($data['loan_infor']['amount_loan']) ?? '');
					$templateProcessor->setValue('number_day_loan', $data['loan_infor']['number_day_loan'] / 30 ?? '');
					$templateProcessor->setValue('type_interest', $type_interest ?? '');
					$templateProcessor->setValue('type_property', $data['loan_infor']['name_property']['text'] ?? '');
					$templateProcessor->setValue('loai_tai_san', $loai_tai_san ?? '');
					$templateProcessor->setValue('thua_dat_so', $thua_dat_so ?? '');
					$templateProcessor->setValue('to_ban_do_so', $to_ban_do_so ?? '');
					$templateProcessor->setValue('dia_chi_thua_dat', $dia_chi_thua_dat ?? '');
					$templateProcessor->setValue('dien_tich', $dien_tich ?? '');
					$templateProcessor->setValue('ht_sdr', $hinh_thuc_su_dung_rieng ?? '');
					$templateProcessor->setValue('ht_sdc', $hinh_thuc_su_dung_chung ?? '');
					$templateProcessor->setValue('muc_dich_su_dung', $muc_dich_su_dung ?? '');
					$templateProcessor->setValue('thoi_han_su_dung', $thoi_han_su_dung ?? '');
					$templateProcessor->setValue('nha_o', $nha_o ?? '');
					$templateProcessor->setValue('giay_chung_nhan_so', $giay_chung_nhan_so ?? '');
					$templateProcessor->setValue('noi_cap', $noi_cap_so ?? '');
					$templateProcessor->setValue('ngay_cap', $ngay_cap_so ?? '');
					$templateProcessor->setValue('so_vao_so', $so_vao_so ?? '');
					$templateProcessor->setValue('date_hours', $date_gn['hours'] ?? '');
					$templateProcessor->setValue('date_minutes', $date_gn['minutes'] ?? '');
					$templateProcessor->setValue('date_mday', $date_gn['mday'] ?? '');
					$templateProcessor->setValue('date_mon', $date_gn['mon'] ?? '');
					$templateProcessor->setValue('date_year', $date_gn['year'] ?? '');
					$templateProcessor->setValue('gia_tai_san', $gia_tai_san ?? '');
					$templateProcessor->setValue('gia_tai_san_bang_chu', $gia_tai_san_bang_chu ?? '');
					$templateProcessor->setValue('relative_with_contracter', $relative_with_contracter ?? '');
					$templateProcessor->setValue('current_time', $current_time ?? '');
					$templateProcessor->setValue('current_hours', $current_hours ?? '');
					$templateProcessor->setValue('current_minutes', $current_minutes ?? '');
					$templateProcessor->setValue('date_sign_ttbb', $date_sign_ttbb ?? '');
					$templateProcessor->setValue('date_sign_bbbgt', $date_sign_bbbgt ?? '');
					$templateProcessor->saveAs(APPPATH . '/file_megadoc/bbbg_thechap_tcv_tl_' . $code_contract . '.docx');
				}
				return true;
			} elseif (($data['loan_infor']['type_loan']['code'] == 'CC' || $data['loan_infor']['type_loan']['code'] == 'DKX') && $data['loan_infor']['type_property']['code'] != "TC") {
				// bbbg_chovay_tcv_tl_template
				if (file_exists('assets/file/file_megadoc/bbbg_chovay_tcv_tl_template.docx')) {
					$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/bbbg_chovay_tcv_tl_template.docx');
					$templateProcessor->setValue('mabbbg', $code_delivery_records ?? '');
					$templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
					$templateProcessor->setValue('disbursement_date', $disbursement_date ?? '');
					$templateProcessor->setValue('day', $day ?? '');
					$templateProcessor->setValue('mon', $mon ?? '');
					$templateProcessor->setValue('year', $year ?? '');
					$templateProcessor->setValue('store_address', $data['store']['address'] ?? '');
					$templateProcessor->setValue('customer_name', $data['customer_infor']['customer_name'] ?? '');
					$templateProcessor->setValue('customer_dob', $customerDOB ?? '');
					$templateProcessor->setValue('customer_identify', $data['customer_infor']['customer_identify'] ?? '');
					$templateProcessor->setValue('identify_date', $identify_date_range ?? '');
					$templateProcessor->setValue('identify_issued_by', $data['customer_infor']['issued_by'] ?? '');
					$templateProcessor->setValue('current_address', $current_address_final ?? '');
					$templateProcessor->setValue('household_address', $household_address ?? '');
					$templateProcessor->setValue('customer_phone', $data['customer_infor']['customer_phone_number'] ?? '');
					$templateProcessor->setValue('store_representative', $store['representative'] ?? '');
					$templateProcessor->setValue('amount_money', number_format($data['loan_infor']['amount_money']) ?? '');
					$templateProcessor->setValue('amount_loan', number_format($data['loan_infor']['amount_loan']) ?? '');
					$templateProcessor->setValue('number_day_loan', $data['loan_infor']['number_day_loan'] / 30 ?? '');
					$templateProcessor->setValue('type_interest', $type_interest ?? '');
					$templateProcessor->setValue('type_property', $data['loan_infor']['type_property']['text'] ?? '');
					$templateProcessor->setValue('so_khung', $sokhung ?? '');
					$templateProcessor->setValue('bks', $bienkiemsoat ?? '');
					$templateProcessor->setValue('somay', $somay ?? '');
					$templateProcessor->setValue('ngaydangky', $ngaycapdangkyxe);
					$templateProcessor->setValue('sodangky', $sodangky ?? '');
					$templateProcessor->setValue('nhan_hieu', $nhanhieu ?? '');
					$templateProcessor->setValue('model', $model ?? '');
					$templateProcessor->setValue('appraise', $appraise ?? '');
					$templateProcessor->setValue('by_words', $appraise_words ?? '');
					$templateProcessor->setValue('relative_with_contracter', $relative_with_contracter ?? '');
					$templateProcessor->setValue('current_time', $current_time ?? '');
					$templateProcessor->setValue('current_hours', $current_hours ?? '');
					$templateProcessor->setValue('current_minutes', $current_minutes ?? '');
					$templateProcessor->setValue('date_sign_ttbb', $date_sign_ttbb ?? '');
					$templateProcessor->setValue('date_sign_bbbgt', $date_sign_bbbgt ?? '');
					$templateProcessor->saveAs(APPPATH . '/file_megadoc/bbbg_chovay_tcv_tl_' . $code_contract . '.docx');
				}
				return true;
			}
		} elseif ($company_code == "TCVĐB") {
			if ($data['loan_infor']['type_property']['code'] == 'NĐ') {
				// => Tạo file docx bbbg_thechap_tcvdb_tl_template
				if (file_exists('assets/file/file_megadoc/bbbg_thechap_tcvdb_tl_template.docx')) {
					$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/bbbg_thechap_tcvdb_tl_template.docx');
					$templateProcessor->setValue('mabbbg', $code_delivery_records ?? '');
					$templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
					$templateProcessor->setValue('disbursement_date', $disbursement_date ?? '');
					$templateProcessor->setValue('day', $day ?? '');
					$templateProcessor->setValue('mon', $mon ?? '');
					$templateProcessor->setValue('year', $year ?? '');
					$templateProcessor->setValue('store_address', $data['store']['address'] ?? '');
					$templateProcessor->setValue('customer_name', $data['customer_infor']['customer_name'] ?? '');
					$templateProcessor->setValue('customer_dob', $customerDOB ?? '');
					$templateProcessor->setValue('customer_identify', $data['customer_infor']['customer_identify'] ?? '');
					$templateProcessor->setValue('identify_date', $identify_date_range ?? '');
					$templateProcessor->setValue('identify_issued_by', $data['customer_infor']['issued_by'] ?? '');
					$templateProcessor->setValue('current_address', $current_address_final ?? '');
					$templateProcessor->setValue('household_address', $household_address ?? '');
					$templateProcessor->setValue('customer_phone', $data['customer_infor']['customer_phone_number'] ?? '');
					$templateProcessor->setValue('store_representative', $store['representative'] ?? '');
					$templateProcessor->setValue('amount_money', number_format($data['loan_infor']['amount_money']) ?? '');
					$templateProcessor->setValue('amount_loan', number_format($data['loan_infor']['amount_loan']) ?? '');
					$templateProcessor->setValue('number_day_loan', $data['loan_infor']['number_day_loan'] / 30 ?? '');
					$templateProcessor->setValue('type_interest', $type_interest ?? '');
					$templateProcessor->setValue('type_property', $data['loan_infor']['name_property']['text'] ?? '');
					$templateProcessor->setValue('loai_tai_san', $loai_tai_san ?? '');
					$templateProcessor->setValue('thua_dat_so', $thua_dat_so ?? '');
					$templateProcessor->setValue('to_ban_do_so', $to_ban_do_so ?? '');
					$templateProcessor->setValue('dia_chi_thua_dat', $dia_chi_thua_dat ?? '');
					$templateProcessor->setValue('dien_tich', $dien_tich ?? '');
					$templateProcessor->setValue('ht_sdr', $hinh_thuc_su_dung_rieng ?? '');
					$templateProcessor->setValue('ht_sdc', $hinh_thuc_su_dung_chung ?? '');
					$templateProcessor->setValue('muc_dich_su_dung', $muc_dich_su_dung ?? '');
					$templateProcessor->setValue('thoi_han_su_dung', $thoi_han_su_dung ?? '');
					$templateProcessor->setValue('nha_o', $nha_o ?? '');
					$templateProcessor->setValue('giay_chung_nhan_so', $giay_chung_nhan_so ?? '');
					$templateProcessor->setValue('noi_cap', $noi_cap_so ?? '');
					$templateProcessor->setValue('ngay_cap', $ngay_cap_so ?? '');
					$templateProcessor->setValue('so_vao_so', $so_vao_so ?? '');
					$templateProcessor->setValue('date_hours', $date_gn['hours'] ?? '');
					$templateProcessor->setValue('date_minutes', $date_gn['minutes'] ?? '');
					$templateProcessor->setValue('date_mday', $date_gn['mday'] ?? '');
					$templateProcessor->setValue('date_mon', $date_gn['mon'] ?? '');
					$templateProcessor->setValue('date_year', $date_gn['year'] ?? '');
					$templateProcessor->setValue('gia_tai_san', $gia_tai_san ?? '');
					$templateProcessor->setValue('gia_tai_san_bang_chu', $gia_tai_san_bang_chu ?? '');
					$templateProcessor->setValue('relative_with_contracter', $relative_with_contracter ?? '');
					$templateProcessor->setValue('current_time', $current_time ?? '');
					$templateProcessor->setValue('current_hours', $current_hours ?? '');
					$templateProcessor->setValue('current_minutes', $current_minutes ?? '');
					$templateProcessor->setValue('date_sign_ttbb', $date_sign_ttbb ?? '');
					$templateProcessor->setValue('date_sign_bbbgt', $date_sign_bbbgt ?? '');
					$templateProcessor->saveAs(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_tl_' . $code_contract . '.docx');
				}
				return true;
			} elseif (($data['loan_infor']['type_loan']['code'] == 'CC' || $data['loan_infor']['type_loan']['code'] == 'DKX') && $data['loan_infor']['type_property']['code'] != "TC") {
				// bbbg_chovay_tcvdb_tl_template
				if (file_exists('assets/file/file_megadoc/bbbg_chovay_tcvdb_tl_template.docx')) {
					$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/bbbg_chovay_tcvdb_tl_template.docx');
					$templateProcessor->setValue('mabbbg', $code_delivery_records ?? '');
					$templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
					$templateProcessor->setValue('disbursement_date', $disbursement_date ?? '');
					$templateProcessor->setValue('day', $day ?? '');
					$templateProcessor->setValue('mon', $mon ?? '');
					$templateProcessor->setValue('year', $year ?? '');
					$templateProcessor->setValue('store_address', $data['store']['address'] ?? '');
					$templateProcessor->setValue('customer_name', $data['customer_infor']['customer_name'] ?? '');
					$templateProcessor->setValue('customer_dob', $customerDOB ?? '');
					$templateProcessor->setValue('customer_identify', $data['customer_infor']['customer_identify'] ?? '');
					$templateProcessor->setValue('identify_date', $identify_date_range ?? '');
					$templateProcessor->setValue('identify_issued_by', $data['customer_infor']['issued_by'] ?? '');
					$templateProcessor->setValue('current_address', $current_address_final ?? '');
					$templateProcessor->setValue('household_address', $household_address ?? '');
					$templateProcessor->setValue('customer_phone', $data['customer_infor']['customer_phone_number'] ?? '');
					$templateProcessor->setValue('store_representative', $store['representative'] ?? '');
					$templateProcessor->setValue('amount_money', number_format($data['loan_infor']['amount_money']) ?? '');
					$templateProcessor->setValue('amount_loan', number_format($data['loan_infor']['amount_loan']) ?? '');
					$templateProcessor->setValue('number_day_loan', $data['loan_infor']['number_day_loan'] / 30 ?? '');
					$templateProcessor->setValue('type_interest', $type_interest ?? '');
					$templateProcessor->setValue('type_property', $data['loan_infor']['type_property']['text'] ?? '');
					$templateProcessor->setValue('so_khung', $sokhung ?? '');
					$templateProcessor->setValue('bks', $bienkiemsoat ?? '');
					$templateProcessor->setValue('somay', $somay ?? '');
					$templateProcessor->setValue('ngaydangky', $ngaycapdangkyxe);
					$templateProcessor->setValue('sodangky', $sodangky ?? '');
					$templateProcessor->setValue('nhan_hieu', $nhanhieu ?? '');
					$templateProcessor->setValue('model', $model ?? '');
					$templateProcessor->setValue('appraise', $appraise ?? '');
					$templateProcessor->setValue('by_words', $appraise_words ?? '');
					$templateProcessor->setValue('relative_with_contracter', $relative_with_contracter ?? '');
					$templateProcessor->setValue('current_time', $current_time ?? '');
					$templateProcessor->setValue('current_hours', $current_hours ?? '');
					$templateProcessor->setValue('current_minutes', $current_minutes ?? '');
					$templateProcessor->setValue('date_sign_ttbb', $date_sign_ttbb ?? '');
					$templateProcessor->setValue('date_sign_bbbgt', $date_sign_bbbgt ?? '');
					$templateProcessor->saveAs(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_tl_' . $code_contract . '.docx');
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Lấy thông tin hợp đồng megadoc
	 */
	private function get_info_contract_megadoc($searchkey, $company_code)
	{
		if (!empty($company_code) && !empty($searchkey)) {
			$response_megadoc = $this->megadoc->status_contract($searchkey, $company_code);
			$response_megadoc_decode = json_decode(json_decode($response_megadoc, true));
			$date_complete_sign = !empty($response_megadoc_decode[0]->CompleteDate) ? $response_megadoc_decode[0]->CompleteDate : '';
			$date_complete_sign_convert = date('d/m/Y', strtotime($date_complete_sign));
		} else {
			$date_complete_sign_convert = '';
		}
		return $date_complete_sign_convert;
	}


	/** Convert file docx sang pdf
	 * @param $condition
	 * @return bool
	 */
	private function execute_convert_docx($condition)
	{
		$CI = &get_instance();
		$CI->load->config('config');
		$keyApiConvertIo = $CI->config->item('MGD_KEYCONVERTIO');
		try {
			$result = $this->convert_docx_to_pdf_enterprice($keyApiConvertIo, $condition);
//			$result = $this->convert_docx_to_pdf_enterprice("c2f26e774d6a27dfac78e8c1ceeffa15", $condition);
			return $result;
		} catch (\Exception $e) {
			try {
				$result = $this->convert_docx_to_pdf_enterprice("72924f7066eea99ebddcb734ce156b81", $condition);
				return $result;
			} catch (\Exception $e) {
				try {
					$result = $this->convert_docx_to_pdf_enterprice("92d9f98e7e7f7bed30c5a97fd4741e1f", $condition);
					return $result;
				} catch (\Exception $e) {
					try {
						$result = $this->convert_docx_to_pdf_enterprice("4026ca62e8e969f986dbe2a05ad8f69b", $condition);
						return $result;
					} catch (\Exception $e) {
						try {
							$result = $this->convert_docx_to_pdf_enterprice("7c2f42f4b73aa80bc6760f015c42b8cc", $condition);
							return $result;
						} catch (\Exception $e) {
							try {
								$result = $this->convert_docx_to_pdf_enterprice("58975c8b79dcc1f1b213db25528ba015", $condition);
								return $result;
							} catch (\Exception $e) {
								try {
									$result = $this->convert_docx_to_pdf_enterprice("d4d6106f3676fa35d6ebb2ddc2fc81f4", $condition);
									return $result;
								} catch (\Exception $e) {
									return false;
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param $code //key api convetio
	 * @param $condition //code_contract type_loan type_property company_code
	 * @return bool
	 * @throws \Convertio\Exceptions\APIException
	 * @throws \Convertio\Exceptions\CURLException
	 */
	public function convert_docx_to_pdf_enterprice($code, $condition)
	{
		$API = new \Convertio\Convertio($code);
		if ($condition['company_code'] == "TCV") {
			if ($condition['type_doc'] == "cv") {
				if ($condition['status_approve'] == 6) {
					if (file_exists(APPPATH . '/file_megadoc/chovay_tcv_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/chovay_tcv_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/chovay_tcv_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 15) {
					if (file_exists(APPPATH . '/file_megadoc/bbbg_chovay_tcv_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/bbbg_chovay_tcv_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 16) {
					if (file_exists(APPPATH . '/file_megadoc/thongbao_tcv_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/thongbao_tcv_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/thongbao_tcv_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 19) {
					if (file_exists(APPPATH . '/file_megadoc/bbbg_chovay_tcv_tl_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/bbbg_chovay_tcv_tl_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_tl_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				}
			}
			if ($condition['type_doc'] == "thc") {
				if ($condition['status_approve'] == 6) {
					if (file_exists(APPPATH . '/file_megadoc/thechap_tcv_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/thechap_tcv_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/thechap_tcv_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 15) {
					if (file_exists(APPPATH . '/file_megadoc/bbbg_thechap_tcv_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/bbbg_thechap_tcv_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 19) {
					if (file_exists(APPPATH . '/file_megadoc/bbbg_thechap_tcv_tl_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/bbbg_thechap_tcv_tl_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_tl_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				}
			}
		} elseif ($condition['company_code'] == "TCVĐB") {
			if ($condition['type_doc'] == "cv") {
				if ($condition['status_approve'] == 6) {
					if (file_exists(APPPATH . '/file_megadoc/chovay_tcvdb_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/chovay_tcvdb_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/chovay_tcvdb_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;

				} elseif ($condition['status_approve'] == 15) {
					if (file_exists(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 16) {
					if (file_exists(APPPATH . '/file_megadoc/thongbao_tcvdb_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/thongbao_tcvdb_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/thongbao_tcvdb_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 19) {
					if (file_exists(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_tl_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_tl_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_tl_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				}
			}
			if ($condition['type_doc'] == "thc") {
				if ($condition['status_approve'] == 6) {
					if (file_exists(APPPATH . '/file_megadoc/thechap_tcvdb_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/thechap_tcvdb_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/thechap_tcvdb_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 15) {
					if (file_exists(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				} elseif ($condition['status_approve'] == 19) {
					if (file_exists(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_tl_' . $condition['code_contract'] . '.docx')) {
						$API->start(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_tl_' . $condition['code_contract'] . '.docx', 'pdf')
							->wait()
							->download(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_tl_' . $condition['code_contract'] . '.pdf');
						return true;
					}
					return false;
				}
			}
		}
	}


	/** Tạo infor metadata hợp đồng Megadoc
	 * @param $contractInfo
	 * @param int $create_type
	 * @return array
	 */
	public function create_metadata_megadoc($contractInfo, $create_type = 0)
	{
		$customer_info = $contractInfo['customer_infor'];
		//Địa chỉ đang ở
		$address_cus = $contractInfo['current_address'];
		$send_email = true;
		$send_sms = false;
		//status_email = 1 - Nhận thông báo ký số qua email
		//status_email = 2 - Nhận thông báo ký số qua tin nhắn SMS
		$customer_phone = "";
		$customer_email = "";
		if (!empty($customer_info['status_email']) && $customer_info['status_email'] == 2) {
			$customer_phone = $customer_info['customer_phone_number'];
			$customer_email = "";
			$send_email = false;
		} elseif (!empty($customer_info['status_email']) && $customer_info['status_email'] == 1) {
			$customer_phone = "";
			$customer_email = $customer_info['customer_email'];
			$send_email = true;
		}
		$current_address_final = "";
		$current_address_final = $address_cus['current_stay'] . ', ' . $address_cus['ward_name'] . ', ' . $address_cus['district_name'] . ', ' . $address_cus['province_name'];
		$company_code = $this->check_store_tcv_dong_bac($contractInfo['store']['id']);
		$megadoc = new DigitalContractMegadoc();
		$cateCodeInit = "";
		$subCateCodeInit = "";
		if ($company_code == 'TCV') {
			$cateCodeInit = $megadoc->MGD_CATECODE;
			$subCateCodeInit = $megadoc->MGD_SUBCATECODE;
		} elseif ($company_code == 'TCVĐB') {
			$cateCodeInit = $megadoc->MGD_DB_CATECODE;
			$subCateCodeInit = $megadoc->MGD_DB_SUBCATECODE;
		}

		$fkey = "";
		$contractNo = "";
		$short_name_store = "";
		$store_infor = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contractInfo['store']['id'])));
		if (!empty($store_infor)) {
			$short_name_store = $store_infor['code_address_store'];
		}
		$metadata = array();
		$status = $contractInfo['status_approve'];

		if (!empty($status) && $status == 6) {
			$fkey = $contractInfo['code_contract']; // Đã duyệt => Send Thỏa thuận ba bên (main)
			$contractNo = $contractInfo['code_contract_disbursement'];
			$cateCode = $cateCodeInit;
		} elseif (!empty($status) && $status == 15) {
			$fkey = $contractInfo['code_contract'] . '_bbbgtruoc'; // Send Biên bản bàn giao tài sản trước khi ký thỏa thuận ba bên
			$cateCode = $subCateCodeInit;
			$contractNo = $contractInfo['code_contract_disbursement'].'_bgtruoc';
		} elseif (!empty($status) && $status == 16) {
			$fkey = $contractInfo['code_contract'] . '_tb'; // Send Thông báo
			$cateCode = $subCateCodeInit;
			$contractNo = $contractInfo['code_contract_disbursement'].'_tb';
		} elseif (!empty($status) && $status == 19) {
			$fkey = $contractInfo['code_contract'] . '_bbbgsau'; // Đã tất toán => Send Biên bản bàn giao tài sản sau khi thanh lý (tất toán) hợp đồng vay
			$cateCode = $subCateCodeInit;
			$contractNo = $contractInfo['code_contract_disbursement']."_bgssau";
		}
		$metadata = array(
			'Fkey' => $fkey,
			'CreateType' => $create_type,
			'Amount' => $contractInfo['loan_infor']['amount_money'],
			'SendEmail' => $send_email,
			"DeptCode" => $short_name_store,
			"CateCode" => $cateCode,
			"ContractDate" => date('d/m/Y', $contractInfo['created_at']),
			"ContractNo" => $contractNo,
			"CusName" => $customer_info['customer_name'],
			"CusCode" => $customer_info['customer_identify'],
			"CusAddress" => $current_address_final,
			"CusPhone" => $customer_phone,
			"CusEmail" => $customer_email,
			"Status" =>  1,
		);
		if (!empty($status) && in_array($status, [15,16,19])) {
			$metadata['RefNo'] = $contractInfo['code_contract_disbursement'];
		}
		return $metadata;
	}

	/**
	 * @param array $conditon_check_file
	 * @param ten Cty
	 * @param loai van ban
	 * @param trang thai gui duyet
	 * @return CURLFILE
	 */
	public function check_path_file_contract($conditon_check_file)
	{
		// status_approve = 6 => Khi BPD duyệt HĐ, sẽ gửi MEGADOC mẫu thỏa thuận ba bên
		// status_approve = 15 => Khi TTBB đủ chữ ký, sẽ gửi MEGADOC mẫu BBBG tài sản trước khi ký TTBB
		// status_approve = 16 => Khi BBBG TS đủ chữ ký, sẽ gửi MEGADOC mẫu Thông báo
		// status_approve = 19 => Khi tất toán HĐ, sẽ gửi MEGADOC mẫu BBBG tài sản sau khi ký TTBB
		if ($conditon_check_file['company_code'] == "TCV") {
			if ($conditon_check_file['type_doc'] == "cv") {
				if ($conditon_check_file['status_approve'] == 6) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/chovay_tcv_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/chovay_tcv_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'chovay_tcv_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 15) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'bbbg_chovay_tcv_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 16) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/thongbao_tcv_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/thongbao_tcv_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'thongbao_tcv_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 19) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_tl_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_tl_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'bbbg_chovay_tcv_tl_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				}
			} elseif ($conditon_check_file['type_doc'] == "thc") {
				if ($conditon_check_file['status_approve'] == 6) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/thechap_tcv_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/thechap_tcv_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'thechap_tcv_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 15) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'bbbg_thechap_tcv_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 19) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_tl_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_tl_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'bbbg_thechap_tcv_tl_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				}
			}
		} elseif ($conditon_check_file['company_code'] == "TCVĐB") {
			if ($conditon_check_file['type_doc'] == "cv") {
				if ($conditon_check_file['status_approve'] == 6) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/chovay_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/chovay_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'chovay_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 15) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'bbbg_chovay_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 16) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/thongbao_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/thongbao_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'thongbao_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 19) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_tl_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_tl_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'bbbg_chovay_tcvdb_tl_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				}
			} elseif ($conditon_check_file['type_doc'] == "thc") {
				if ($conditon_check_file['status_approve'] == 6) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/thechap_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/thechap_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'thechap_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 15) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'bbbg_thechap_tcvdb_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				} elseif ($conditon_check_file['status_approve'] == 19) {
					if (file_exists(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_tl_' . $conditon_check_file['code_contract'] . '.pdf')) {
						$filecontent = new CURLFILE(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_tl_' . $conditon_check_file['code_contract'] . '.pdf', 'application/pdf', 'bbbg_thechap_tcvdb_tl_' . $conditon_check_file['code_contract'] . '.pdf');
					} else {
						$filecontent = "";
					}
				}
			}
		}
		return $filecontent;
	}

	//Insert log vao bang megadoc
	public function log_megadoc($request, $response, $code_contract, $action = "")
	{
		$dataInsert = array(
			"action" => $action,
			"code_contract" => $code_contract,
			"request_data" => $request,
			"response_data" => $response,
			"created_at" => $this->createdAt ? $this->createdAt : strtotime(date('d-m-Y H:i:s')),
			"created_by" => $this->uemail ? $this->uemail : "system"
		);
		$this->log_megadoc_model->insert($dataInsert);
	}

	/** insert data sms megadoc to database
	 * @param $contractInfo
	 * @param $template
	 * @param $content
	 * @param string $searchKey
	 * @param string $fKey
	 * @param string $type
	 * @return mixed
	 */
	private function insert_sms_megadoc($contractInfo, $template, $content, $searchKey = '', $fKey = '', $type = '', $type_document = '')
	{
		$data_sms_insert = array(
			'id_contract' => (string)$contractInfo['_id'],
			'code_contract' => $contractInfo['code_contract'],
			'code_contract_disbursement' => $contractInfo['code_contract_disbursement'],
			'customer_name' => $contractInfo['customer_infor']['customer_name'],
			'customer_phone' => $contractInfo['customer_infor']['customer_phone_number'],
			'content' => $content,
			'searchkey' => $searchKey,
			'fKey' => $fKey,
			'template' => $template,
			'response' => "",
			'status' => "new",
			'ngay_gui' => date('d/m/Y',$this->createdAt),
			'store' => $contractInfo['store'],
			'type' => $type,
			'type_document' => $type_document,
			'month' => date('m',$this->createdAt),
			'year' => date('Y',$this->createdAt),
			'created_at' => $this->createdAt,
			'created_by' => "superadmin",
		);
		$id_sms = $this->sms_megadoc_model->insertReturnId($data_sms_insert);
		return $id_sms;
	}

	//API send SMS
	private function push_api_sms($post = '', $data_post = "", $get = "")
	{
		$url_phonenet = $this->config->item("url_phonenet");
		$accessKey = $this->config->item("access_key_phonenet");
		$service = $url_phonenet . $get;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $service);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $post);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'token:' . $accessKey));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$result1 = json_decode($result);
		return $result1;
	}

	// Check PGD có áp dụng hợp đồng điện tử hay không
	public function check_store_create_contract_digital($storeId)
	{
		$role = $this->role_model->findOne(["slug" => "hop-dong-dien-tu"]);
		$store_megadoc = array();
		foreach ($role['stores'] as $store) {
			foreach ($store as $key => $st) {
				array_push($store_megadoc, $key);
			}
		}
		if (in_array($storeId, $store_megadoc)) {
			return true;
		} else {
			return false;
		}
	}

}
