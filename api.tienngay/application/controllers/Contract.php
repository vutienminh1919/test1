<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Fcm.php';
require_once APPPATH . 'libraries/Vbi_tnds_oto.php';
require_once APPPATH . 'libraries/BaoHiemVbi.php';
require_once APPPATH . 'libraries/BaoHiemPTI.php';
require_once APPPATH . 'libraries/VPBank.php';
require_once APPPATH . 'libraries/DigitalContractMegadoc.php';
require_once APPPATH . 'libraries/Vfcpayment.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Contract extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model("transaction_model");
        $this->load->model('fee_loan_model');
        $this->load->model('contract_model');
        $this->load->model('gic_model');
        $this->load->model('mic_model');
        $this->load->model('gic_easy_model');
        $this->load->model('gic_plt_model');
        $this->load->model('log_contract_model');
        $this->load->model('log_model');
        $this->load->model('log_gic_model');
        $this->load->model('log_mic_model');
        $this->load->model('user_model');
        $this->load->model('role_model');
        $this->load->model('config_gic_model');
        $this->load->model('city_gic_model');
        $this->load->model('contract_tempo_model');
        $this->load->model('investor_model');
        $this->load->model("group_role_model");
        $this->load->model("notification_model");
        $this->load->model("notification_app_model");
        $this->load->model("store_model");
        $this->load->model("lead_model");
        $this->load->model("sms_model");
        $this->load->model("temporary_plan_contract_model");
        $this->load->model('log_contract_tempo_model');
        $this->load->model('tempo_contract_accounting_model');
        $this->load->model('dashboard_model');
        $this->load->model('coupon_model');
        $this->load->model('verify_identify_contract_model');
        $this->load->model('device_model');
        $this->load->helper('lead_helper');
        $this->load->model('vbi_model');
        $this->load->model('log_hs_model');
        $this->load->model('log_call_debt_model');
        $this->load->model('asset_management_model');
        $this->load->model("transaction_extend_model");
        $this->load->model("contract_tnds_model");
        $this->load->model("log_mic_tnds_model");
        $this->load->model("log_vbi_tnds_model");
        $this->load->model("main_property_model");
        $this->load->model("area_model");
        $this->load->model("store_model");
        $this->load->model("log_bh_vbi_model");
        $this->load->model("vbi_utv_model");
        $this->load->model("vbi_sxh_model");
        $this->load->model("log_bh_vbi_model");
        $this->load->model("coupon_bhkv_model");
        $this->load->model('pti_vta_bn_model');
        $this->load->model('log_pti_model');
        $this->load->model("contract_debt_caller_model");
        $this->load->model("log_debt_caller_model");
        $this->load->model('email_template_model');
        $this->load->model('email_history_model');
        $this->load->model('file_manager_model');
        $this->load->model('log_fileManager_model');
        $this->load->model('borrowed_noti_model');
        $this->load->model('pti_vta_fee_model');
        $this->load->model('log_ksnb_model');
        $this->load->model('province_model');
        $this->load->model('district_model');
        $this->load->model('ward_model');
        $this->load->model("bank_nganluong_model");
        $this->load->model('log_megadoc_model');
        $this->load->model('sms_megadoc_model');
        $this->load->helper('download_helper');
        $this->load->model('pti_bhtn_model');
        $this->load->model('list_topup_model');
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
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


    }

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;

    public function get_cskh_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //5de72198d6612b4076140606 super admin
        //5de726a8d6612b6f2b431749 Van hanh
        //5de726c9d6612b6f2a617ef5 CHT
        //5de726e4d6612b6f2c310c78 GDV
        //5de726fcd6612b77824963b9 Ke Toan
        //5def671dd6612b75532960c5 Hoi so
        //5ea1b6d2d6612b65473f2b68 marketing
        //5ea1b6abd6612b6dd20de539 thu-hoi-no
        //5ea1b686d6612bdf6c0422af telesales
        $leads = $this->getUserGroupRole(array('5de72198d6612b4076140606'));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $leads
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }


    public function ap_dung_coupon_giam_bhkv($code_contract)
    {
        $contract = $this->contract_model->findOne(['code_contract' => $code_contract, 'status' => ['$lt' => 17]]);
        if (empty($contract)) {
            return array();
        }
        $created_at = !empty($contract['created_at']) ? (int)$contract['created_at'] : $this->createdAt;
        $data_coupon_DB = $this->coupon_bhkv_model->find_where_not_in(['type_coupon' => '1','status' => 'active', 'start_date' => array('$lte' => $created_at), 'end_date' => array('$gte' => $created_at)]);
        if (!empty($data_coupon_DB)) {

            $code_coupon = "";
            $percent_reduction = 0;
            foreach ($data_coupon_DB as $key => $data_coupon) {
                $code_coupon = $data_coupon['code'];
                $percent_reduction = (int)$data_coupon['percent_reduction'];
                if (isset($contract['store']['id'])) {
                    $data_store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract['store']['id'])));
                    if (!empty($data_store)) {


                        if ((string)$data_store['_id'] != $data_coupon['code_store'] && !empty($data_coupon['code_store'])) {
                            $code_coupon = "";
                            $percent_reduction = 0;
                        }
                        if (!empty($data_coupon['code_area']) && is_array((array)$data_coupon['code_area']) && !in_array($data_store['code_area'], (array)$data_coupon['code_area']) && !in_array('null', (array)$data_coupon['code_area'])) {
                            $code_coupon = "";
                            $percent_reduction = 0;
                        }
                    }
                }

                if (!empty($data_coupon['type_loan']) && is_array((array)$data_coupon['type_loan']) && !in_array($contract['loan_infor']['type_loan']['id'], (array)$data_coupon['type_loan']) && !in_array('null', (array)$data_coupon['type_loan'])) {
                    $code_coupon = "";
                    $percent_reduction = 0;
                }
                if ((int)$data_coupon['end_money'] > 0 && ((int)$contract['loan_infor']['amount_money'] < (int)$data_coupon['start_money'] || (int)$contract['loan_infor']['amount_money'] > (int)$data_coupon['end_money'])) {
                    $code_coupon = "";
                    $percent_reduction = 0;
                }
                if ((int)$data_coupon['start_money'] > 0 && (int)$data_coupon['end_money'] == 0 && (int)$contract['loan_infor']['amount_money'] < (int)$data_coupon['start_money']) {
                    $code_coupon = "";
                    $percent_reduction = 0;
                }

                if (!empty($data_coupon['type_property']) && is_array((array)$data_coupon['type_property']) && !in_array($contract['loan_infor']['type_property']['id'], (array)$data_coupon['type_property']) && !in_array('null', (array)$data_coupon['type_property'])) {
                    $code_coupon = "";
                    $percent_reduction = 0;
                }

                if (!empty($data_coupon['loan_product']) && is_array((array)$data_coupon['loan_product']) && !in_array($contract['loan_infor']['loan_product']['code'], (array)$data_coupon['loan_product']) && !in_array('null', (array)$data_coupon['loan_product'])) {
                    $code_coupon = "";
                    $percent_reduction = 0;
                }

                if (!empty($data_coupon['number_day_loan']) && is_array((array)$data_coupon['number_day_loan']) && !in_array($contract['loan_infor']['number_day_loan'], (array)$data_coupon['number_day_loan']) && !in_array('null', (array)$data_coupon['number_day_loan'])) {
                    $code_coupon = "";
                    $percent_reduction = 0;
                }
                if ($code_coupon != "" && $percent_reduction > 0) {
                    break;
                }
            }

            $amount_bhkv = 0;
            if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '1' && $contract['loan_infor']['amount_GIC'] > 0) {
                $amount_bhkv = (int)$contract['loan_infor']['amount_GIC'];

            }
            if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '2' && $contract['loan_infor']['amount_MIC'] > 0) {
                $amount_bhkv = (int)$contract['loan_infor']['amount_MIC'];

            }
            if ($code_coupon != "" && $percent_reduction > 0 && $amount_bhkv > 0) {
                $tien_giam_tru_bhkv = $amount_bhkv * ($percent_reduction / 100);
                if ($tien_giam_tru_bhkv > 0) {
                    if (!in_array($contract['status'], [11, 12, 13, 14]))
                        $this->contract_model->update(
                            array("_id" => $contract['_id']),
                            [
                                "code_coupon_bhkv" => $code_coupon,
                                "tien_giam_tru_bhkv" => (int)$tien_giam_tru_bhkv,
                            ]
                        );
                }
            } else {
                if (!in_array($contract['status'], [11, 12, 13, 14]))
                    $this->contract_model->update(
                        array("_id" => $contract['_id']),
                        [
                            "code_coupon_bhkv" => "",
                            "tien_giam_tru_bhkv" => 0,
                        ]
                    );
            }

            return array();
        } else {
            if (!in_array($contract['status'], [11, 12, 13, 14]))
                $this->contract_model->update(
                    array("_id" => $contract['_id']),
                    [
                        "code_coupon_bhkv" => "",
                        "tien_giam_tru_bhkv" => 0,
                    ]
                );
        }
    }

    public function getFee($contract)
    {

        if (empty($contract)) return array();
        $date_fee = isset($contract['created_at']) ? (int)$contract['created_at'] : (int)$this->createdAt;
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
        $typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
        $typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
        $number_day_loan = !empty($contract['loan_infor']['number_day_loan']) ? $contract['loan_infor']['number_day_loan'] : "";
        $loan_infor_kdol = !empty($contract['loan_infor']['loan_product']['code']) ? $contract['loan_infor']['loan_product']['code'] : "";
        $amount_loan = !empty($contract['loan_infor']['amount_money']) ? $contract['loan_infor']['amount_money'] : '';

        if ($typeProperty == "NĐ") {
            $typeLoan = "NĐ";
        }
        if ($typeLoan == "DKX" && $typeProperty == "XM") {
            $typeLoan = "DKXM";
        }
        if ($typeLoan == "DKX" && $typeProperty == "OTO") {
            $typeLoan = "DKXOTO";
        }
        if ($typeLoan == "DKX" && $typeProperty == "TC") {
            $typeLoan = "TC";
        }
        if ($loan_infor_kdol == 14) {
            $typeLoan = "KDOL";
        }
        if ($loan_infor_kdol == 14 && $typeProperty == "TC") {
            $typeLoan = "KDOL_TC";
        }

        if ($typeLoan == "NĐ") {
            $data = $this->fee_loan_model->findOne(array("status" => 'active', 'type' => "bieu-phi-nha-dat", "from" => ['$lte' => $date_fee], "to" => ['$gte' => $date_fee]));

            $default = array();
            if (!empty($data)) {
                $default['percent_prepay_phase_1'] = $data['infor']['percent_prepay_phase_1'];
                $default['percent_prepay_phase_2'] = $data['infor']['percent_prepay_phase_2'];
                $default['percent_prepay_phase_3'] = $data['infor']['percent_prepay_phase_3'];
                $default['extend'] = $data['infor']['extend'];
                $default['penalty_amount'] = $data['infor']['penalty_amount'];
                $default['penalty_percent'] = $data['infor']['penalty_percent'];
                if ($amount_loan <= 100000000) {
                    $default['percent_interest_customer'] = $data['infor']['100']['percent_interest_customer'];
                    $default['percent_advisory'] = $data['infor']['100']['percent_advisory'];
                    $default['percent_expertise'] = $data['infor']['100']['percent_expertise'];
                } elseif ($amount_loan > 100000000 && $amount_loan <= 200000000) {
                    $default['percent_interest_customer'] = $data['infor']['100-200']['percent_interest_customer'];
                    $default['percent_advisory'] = $data['infor']['100-200']['percent_advisory'];
                    $default['percent_expertise'] = $data['infor']['100-200']['percent_expertise'];
                } elseif ($amount_loan > 200000000) {
                    $default['percent_interest_customer'] = $data['infor']['200']['percent_interest_customer'];
                    $default['percent_advisory'] = $data['infor']['200']['percent_advisory'];
                    $default['percent_expertise'] = $data['infor']['200']['percent_expertise'];
                }
            }
        } else {
            //Get record by time
            $data = $this->fee_loan_model->findOne(
                array(
                    "status" => 'active',
                    "type" => array(
                        '$exists' => false
                    ),
                    "from" => ['$lte' => $date_fee],
                    "to" => ['$gte' => $date_fee]
                )
            );
            if (!empty($data)) $default = $data['infor'][$number_day_loan][$typeLoan];
        }

        //Get record by time
        $data['code_coupon'] = $this->security->xss_clean($contract['loan_infor']['code_coupon']);


        $data_coupon = $this->coupon_model->findOne(array("code" => $data['code_coupon'], 'status' => 'active'));
        if (!empty($data_coupon)) {
            if (isset($contract['store']['id'])) {
                $data_store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract['store']['id'])));
                if (!empty($data_store)) {

                    if ($data_store['province_id'] != $data_coupon['selectize_province'] && !empty($data_coupon['selectize_province'])) {
                        return array();
                    }
                    if ((string)$data_store['_id'] != $data_coupon['code_store'] && !empty($data_coupon['code_store'])) {
                        return array();
                    }
                    if (!empty($data_coupon['code_area']) && is_array((array)$data_coupon['code_area']) && !in_array($data_store['code_area'], (array)$data_coupon['code_area']) && !in_array('null', (array)$data_coupon['code_area'])) {
                        return array();
                    }
                }
            }
            if ($contract['loan_infor']['type_loan']['id'] != $data_coupon['type_loan'] && !empty($data_coupon['type_loan'])) {
                return array();
            }
            if ($contract['loan_infor']['type_property']['id'] != $data_coupon['type_property'] && !empty($data_coupon['type_property'])) {
                return array();
            }

            if (!empty($data_coupon['loan_product']) && is_array((array)$data_coupon['loan_product']) && !in_array($contract['loan_infor']['loan_product']['code'], (array)$data_coupon['loan_product']) && !in_array('null', (array)$data_coupon['loan_product'])) {
                return array();
            }

            if (!empty($data_coupon['number_day_loan']) && is_array((array)$data_coupon['number_day_loan']) && !in_array($contract['loan_infor']['number_day_loan'], (array)$data_coupon['number_day_loan']) && !in_array('null', (array)$data_coupon['number_day_loan'])) {
                return array();
            }
        }

        $arrNew = array();
        foreach ($default as $key => $value) {
            if (empty($data_coupon)) {
                $arrNew[$key] = (float)$value;
            } else {
                if (isset($data_coupon['set_by_coupon']) && $data_coupon['set_by_coupon'] == 'active') {
                    if (isset($data_coupon[$key]) && $data_coupon[$key] > 0) {
                        $fee = (float)$data_coupon[$key];
                    } else {
                        if (isset($data_coupon[$key]) && ($key == 'percent_advisory' || $key == 'percent_expertise' || $key == 'percent_interest_customer')) {
                            $fee = (float)$data_coupon[$key];
                        } else {
                            $fee = (float)$value;
                        }
                    }
                    $arrNew[$key] = ($fee < 0) ? 0 : $fee;

                } else {
                    if (isset($data_coupon[$key])) {
                        if (isset($data_coupon[$key]) && ($key == 'percent_advisory' || $key == 'percent_expertise')) {
                            $fee = (float)$value - (float)$data_coupon[$key];
                        } else {
                            $fee = (float)$value;
                        }
                    } else {
                        $fee = (float)$value;
                    }
                    $arrNew[$key] = ($fee < 0) ? 0 : $fee;

                }

            }
        }

        //Insert log
        $log = array(
            "type" => "contract",
            "action" => "fee_create_contract",
            "type_loan" => $typeLoan,
            "number_day_loan" => $number_day_loan,
            'fee' => $arrNew,
            'contract' => $contract,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($log);
        $arr_return = [
            'fee' => $arrNew,
            'id' => isset($data['_id']) ? (string)$data['_id'] : '',
            'date' => $typeLoan
        ];
        return $arr_return;
    }

    public function updateLoan_insurance_post()
    {
        $contract = $this->contract_model->find();
        $cd_ct = array();
        foreach ($contract as $key1 => $ct) {
            if (isset($ct['code_contract'])) {
                if (isset($ct['loan_infor']['amount_GIC'])) {
                    $loan_insurance = ($ct['loan_infor']['amount_GIC'] > 0) ? "1" : "0";
                    $this->contract_model->update(
                        array("code_contract" => $ct['code_contract']),
                        array('loan_infor.loan_insurance' => $loan_insurance)
                    );
                }
            }

        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "OK"


        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function bang_phi_vay_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        $phi_vay = $this->fee_loan_model->find_where(array("status" => "active"));
        if (!empty($phi_vay)) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $phi_vay,
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => array(),
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

    }


    public function get_contract_chuyentd_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

        $loan = !empty($this->dataPost['loan']) ? $this->dataPost['loan'] : "";

        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')

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
        }
        $condition['reminder_now'] = "37";
        if (!empty($loan)) {
            $condition['loan'] = $loan;

        }

        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
        $contract = $this->contract_model->get_contract_ctd($condition, $per_page, $uriSegment);
        $total = $this->contract_model->get_contract_ctd_total($condition);


        if (empty($contract)) return;
        if (!empty($contract)) {
            foreach ($contract as $key => $c) {
                $cond = array();
                $c['investor_name'] = "";
                if (isset($c['investor_code'])) {
                    $investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
                    $c['investor_name'] = $investors['name'];
                }
                if (isset($c['code_contract'])) {
                    $cond = array(
                        'code_contract' => $c['code_contract'],

                    );
                }
                $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                $c['detail'] = array();
                if (!empty($detail)) {
                    $total_paid = 0;
                    $total_phi_phat_cham_tra = 0;
                    $total_da_thanh_toan = 0;
                    $tong_phi_lai = 0;
                    $tong_goc = 0;

                    foreach ($detail as $de) {

                        $total_paid = $total_paid + $de['tien_tra_1_ky'];
                        $total_phi_phat_cham_tra += $de['penalty'];
                        $total_da_thanh_toan += $de['da_thanh_toan'];
                        $tong_phi_lai += $de['tien_tra_1_ky'] - $de['tien_goc_1ky'];
                        $tong_goc += $de['tien_goc_1ky'];

                    }
                    $c['detail'] = $detail[0];
                    $c['detail']['total_paid'] = $total_paid;
                    $c['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
                    $c['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
                    $c['tong_lai_con_phai_dong'] = $c['detail']['total_paid'] - $c['detail']['da_thanh_toan'];

                    $c['tong_phi_lai'] = $tong_phi_lai;
                    $c['tong_goc'] = $tong_goc;
                }


            }

        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => " success",
            'data' => $contract,
            'total' => $total
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_contract_extension_by_contractParent_post()
    {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
        $inforDB = $this->contract_model->find_where(array("code_contract_parent" => $this->dataPost['id']));
        if (empty($inforDB)) return;
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => " success",
            'data' => $inforDB
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function process_approve_extension_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $groupRoles = $this->getGroupRole($this->id);
        if ($this->superadmin == false && !in_array('ke-toan', $groupRoles) && !in_array('van-hanh', $groupRoles)) {
            // Check access right
            if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
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
        $this->dataPost['description_infor'] = $this->security->xss_clean(!empty($this->dataPost['description_infor']) ? $this->dataPost['description_infor'] : array());

        //Insert log
        $log = array(
            "type" => "contract",
            "action" => "approve_extension",
            "contract_id" => $this->dataPost['id'],
            "reason" => $this->dataPost['reason'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($log);
        $arr = array(
            "reason" => $this->dataPost['reason'],
            "status" => (int)$this->dataPost['status_contract'],
            "approve_extension_by" => $this->uemail
        );
        $this->contract_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
            $arr
        );
        foreach ($this->dataPost['description_infor'] as $key => $value) {
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
            'aaa' => $this->dataPost['description_infor'],
            'id' => $transaction_id
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;

    }

    public function process_update_description_img_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;


        $this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
        $this->dataPost['expertise'] = $this->security->xss_clean(!empty($this->dataPost['expertise']) ? $this->dataPost['expertise'] : array());


        $arrUpdate = array("image_accurecy.expertise" => $this->dataPost['expertise']);
        $this->contract_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
            $arrUpdate
        );

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update contract success",
            'data' => $arrUpdate
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;

    }

    public function process_update_fee_post()
    {

        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        $data = $this->input->post();
        $data['id'] = $this->security->xss_clean($data['id']);

        $data['percent_advisory'] = $this->security->xss_clean($data['percent_advisory']);
        $data['percent_expertise'] = $this->security->xss_clean($data['percent_expertise']);
        $data['percent_prepay_phase_1'] = $this->security->xss_clean($data['percent_prepay_phase_1']);
        $data['percent_prepay_phase_2'] = $this->security->xss_clean($data['percent_prepay_phase_2']);
        $data['percent_prepay_phase_3'] = $this->security->xss_clean($data['percent_prepay_phase_3']);

        $inforDB = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));

        if (empty($inforDB)) return;

        //Insert log
        $insertLog = array(
            "type" => "contract",
            "action" => "update_fee",
            "contract_id" => (string)$data['id'],
            "old" => $data,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => "update_fee",
            "contract_id" => (string)$data['id'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;
        $this->insert_log_file($insertLog, (string)$data['id']);

        /**
         * ----------------------
         */

        //Update contract model
        $this->contract_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($data['id'])),
            array(

                "fee.percent_advisory" => (float)$data['percent_advisory'],
                "fee.percent_expertise" => (float)$data['percent_expertise'],
                "fee.percent_prepay_phase_1" => (float)$data['percent_prepay_phase_1'],
                "fee.percent_prepay_phase_2" => (float)$data['percent_prepay_phase_2'],
                "fee.percent_prepay_phase_3" => (float)$data['percent_prepay_phase_3'],
            )
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update fee success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function process_get_fee_post()
    {

        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        $data = $this->input->post();
        $data['id'] = $this->security->xss_clean($data['id']);
        $data['code_coupon'] = $this->security->xss_clean($data['code_coupon']);
        $inforDB = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));
        if (empty($inforDB)) return;
        if (isset($inforDB['fee_origin'])) {
            $data_fee = $inforDB['fee_origin'];
        } else {
            $data_fee = $this->getFee_origin_by_data($inforDB);
        }
        $data_coupon = $this->coupon_model->findOne(array("code" => $data['code_coupon'], 'status' => 'active'));
        if (!empty($data_coupon)) {
            if (isset($inforDB['store']['id'])) {
                $data_store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($inforDB['store']['id'])));
                if (!empty($data_store)) {

                    if (($data_store['province_id'] != $data_coupon['selectize_province']) && !empty($data_coupon['selectize_province'])) {
                        $response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            "fee" => $data_fee,
                            'message' => "Tỉnh thành phố không phù hợp",
                            'data' => $data
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    }
                    if ((string)$data_store['_id'] != $data_coupon['code_store'] && !empty($data_coupon['code_store'])) {
                        $response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            "fee" => $data_fee,
                            'message' => "Phòng giao dịch không phù hợp",
                            'data' => $data
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    }
                }
            }
            if ($inforDB['loan_infor']['type_loan']['id'] != $data_coupon['type_loan'] && !empty($data_coupon['type_loan'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    "fee" => $data_fee,
                    'message' => "Hình thức vay không phù hợp",
                    'data' => $data
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if ($inforDB['loan_infor']['type_property']['id'] != $data_coupon['type_property'] && !empty($data_coupon['type_property'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    "fee" => $data_fee,
                    'message' => "Loại tài sản không phù hợp",
                    'data' => $data
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if ($inforDB['loan_infor']['loan_product']['code'] != $data_coupon['loan_product'] && !empty($data_coupon['loan_product'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    "fee" => $data_fee,
                    'message' => "Sản phẩm vay không phù hợp",
                    'data' => $data
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }

            if (!empty($data_coupon['number_day_loan']) && is_array($data_coupon['number_day_loan']) && !in_array($inforDB['loan_infor']['number_day_loan'], $data_coupon['number_day_loan']) && !in_array('null', (array)$data_coupon['number_day_loan'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    "fee" => $data_fee,
                    'message' => "Thời gian vay không phù hợp",
                    'data' => $data
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }

        } else {

            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                "fee" => $data_fee,
                'message' => "Không có thông tin coupon",
                'data' => $data
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $arrNew = array();
        foreach ($data_fee as $key => $value) {
            if (empty($data_coupon)) {
                $arrNew[$key] = (float)$value;
            } else {
                if (isset($data_coupon[$key])) {
                    $fee = (float)$value - (float)$data_coupon[$key];
                } else {
                    $fee = (float)$value;
                }
                $arrNew[$key] = ($fee < 0) ? 0 : $fee;
            }
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            "fee" => $arrNew,
            'message' => "Get fee success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function getFee_origin_by_data($data)
    {
        $date_fee = isset($data['created_at']) ? $data['created_at'] : $this->createdAt;
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
        $typeLoan = !empty($data['loan_infor']['type_loan']['code']) ? $data['loan_infor']['type_loan']['code'] : "";
        $typeProperty = !empty($data['loan_infor']['type_property']['code']) ? $data['loan_infor']['type_property']['code'] : "";
        $number_day_loan = !empty($data['loan_infor']['number_day_loan']) ? $data['loan_infor']['number_day_loan'] : "";
        if ($typeLoan == "DKX" && $typeProperty == "XM") {
            $typeLoan = "DKXM";
        }
        if ($typeLoan == "DKX" && $typeProperty == "OTO") {
            $typeLoan = "DKXOTO";
        }
        if ($typeLoan == "DKX" && $typeProperty == "TC") {
            $typeLoan = "TC";
        }
        //Get record by time
        $data = $this->fee_loan_model->findOne(array("status" => 'active', "from" => ['$lte' => $date_fee], "to" => ['$gte' => $date_fee]));
        if (!empty($data)) $default = $data['infor'][$number_day_loan][$typeLoan];

        $arrNew = array();
        foreach ($default as $key => $value) {

            $arrNew[$key] = (float)$value;

        }
        return $arrNew;
    }

    public function process_update_disbursement_contract_post()
    {

        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $groupRoles = $this->getGroupRole($this->id);
        if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
            // Check access right
            if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
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
        $inforDB = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        if (empty($inforDB)) return;
        //Insert log
        $insertLog = array(
            "type" => "contract",
            "action" => "update",
            "contract_id" => (string)$this->dataPost['id'],
            "old" => $this->dataPost,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => "update",
            "contract_id" => (string)$this->dataPost['id'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;
        $this->insert_log_file($insertLog, (string)$this->dataPost['id']);

        /**
         * ----------------------
         */



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
    public function insert_gic($data, $code_contract, $disbursement_date)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $branch_id_gic = $this->config->item("branch_id_gic");
        $city = $this->city_gic_model->findOne(array('code' => 'GIC'));
        $config = $this->config_gic_model->findOne(array('code' => 'TN_TNNNV'));
        $code_gic = (!empty($config['code'])) ? $config['code'] : "";
        $NoiDungBaoHiem_TyLePhi = (!empty($config['TyLePhi'])) ? $config['TyLePhi'] : "2.5";
        $number_day_loan = (!empty($data['loan_infor']['number_day_loan'])) ? (int)$data['loan_infor']['number_day_loan'] / 30 : 0;
        $so_thang_tham_gia_bh_goc = $number_day_loan;
        $so_thang_tham_gia_bh = ($number_day_loan <= 12) ? 12 : 24;
        $NoiDungBaoHiem_SoHdTinDungKv = (!empty($code_contract)) ? $code_contract : "";
        $TyLeKhoanVay = ($so_thang_tham_gia_bh == 12) ? 200 : 120;
        $GiaTriKhoanVay = (!empty($data['loan_infor']['amount_money'])) ? $data['loan_infor']['amount_money'] : 0;
        $NgayYeuCauBh = $disbursement_date;
        $NgayHieuLucBaoHiem = $disbursement_date;
        $NgayHieuLucBaoHiemDen = date('Y-m-d H:i:s', strtotime($NgayHieuLucBaoHiem . ' + ' . $so_thang_tham_gia_bh . ' month'));
        $customer_name = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $company_code = (!empty($data['company_code'])) ? $data['company_code'] : '';
        $customer_BOD = (!empty($data['customer_infor']['customer_BOD'])) ? $data['customer_infor']['customer_BOD'] : '';
        $customer_identify = (!empty($data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
        $name_investor = (!empty($data['investor_infor']['name'])) ? $data['investor_infor']['name'] : '.';
        $current_address = (!empty($data['houseHold_address']['ward_name'])) ? $data['houseHold_address']['address_household'] . ' - ' . $data['houseHold_address']['ward_name'] . ' - ' . $data['houseHold_address']['district_name'] . ' - ' . $data['houseHold_address']['province_name'] : '.....';
        $province = (!empty($data['houseHold_address']['province_name'])) ? $data['houseHold_address']['province_name'] : '';
        $district = (!empty($data['houseHold_address']['district_name'])) ? $data['houseHold_address']['district_name'] : '';
        $customer_phone_number = (!empty($data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';
        $customer_email = (!empty($data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
        $customer_gender = (!empty($data['customer_infor']['customer_gender'])) ? $data['customer_infor']['customer_gender'] : '1';
        $customer_gender = ($customer_gender == '1') ? 'dbb6424f-3890-4108-a094-3a17884885f3' : '27541417-9bf3-4b96-8bd2-edb4b8cf352a';
        $ProvinceId = '5c3b316f-91ad-46fc-a26c-8331be3b7739';
        $DistrictId = '131EBCCF-CC5E-4C5F-B324-48F8BC8A2F56';
        $NoiDungBaoHiem_PhiBaoHiem = (!empty($data['loan_infor']['amount_GIC'])) ? $data['loan_infor']['amount_GIC'] : 0;

        if (!empty($city['city'])) {
            // chỉ giữ lại tên slug của tỉnh/thành phố. VD: Thành phố Hà Nội => ha-noi
            $name_slug_province = $this->slugify(str_replace("Tỉnh ", "", $province));
            $name_slug_province = $this->slugify(str_replace("Thành phố", "", $name_slug_province));
            $name_slug_province = $this->slugify(str_replace("thi-xa-", "", $name_slug_province));
            $name_slug_province = $this->slugify(str_replace("thanh-pho-", "", $name_slug_province));
            foreach ($city['city'] as $key => $value) {
                if ($this->slugify($value['name']) == $name_slug_province) {
                    $ProvinceId = $value['id'];
                }
            }
        }
        $name = "";
        if (!empty($city['district'])) {
            foreach ($city['district'] as $key => $value) {
                if ($this->slugify($value['name']) == $this->slugify($district)) {
                    $DistrictId = $value['id'];
                }
            }
        }

        $dt_gic = array(
            'ThongTinChung_NhanVienId' => $config['NhanVienId']
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_NgaySinh' => $customer_BOD
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_SoCMND' => $customer_identify
        , 'NoiDungBaoHiem_NgayHieuLucBaoHiemDen' => $NgayHieuLucBaoHiemDen
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_Ten' => $customer_name
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_ProvinceId' => $ProvinceId
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_DistrictId' => $DistrictId
        , 'NoiDungBaoHiem_NgayHieuLucBaoHiem' => $NgayHieuLucBaoHiem
        , 'NoiDungBaoHiem_GiaTriKhoanVay' => $GiaTriKhoanVay
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi' => $current_address
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_SoDienThoai' => $customer_phone_number
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_Email' => $customer_email
        , 'NoiDungBaoHiem_SoHdTinDungKv' => $NoiDungBaoHiem_SoHdTinDungKv
        , 'NoiDungBaoHiem_NgayYeuCauBh' => $NgayYeuCauBh
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_GioiTinhId' => $customer_gender
        , 'ThongTinNguoiChoVay_HoTen' => $config['ThongTinNguoiChoVay_HoTen']
        , 'ThongTinNguoiChoVay_CMND' => $config['ThongTinNguoiChoVay_CMND']
        , 'ThongTinNguoiChoVay_DienThoai' => $config['ThongTinNguoiChoVay_DienThoai']
        , 'ThongTinNguoiChoVay_Email' => $config['ThongTinNguoiChoVay_Email']
        , 'ThongTinNguoiChoVay_DiaChi' => $config['ThongTinNguoiChoVay_DiaChi']
        , 'Branchid' => $branch_id_gic
        , "productCode" => $code_gic
        , "NoiDungBaoHiem_SoThangVay" => (int)$so_thang_tham_gia_bh_goc
        , "NoiDungBaoHiem_SoThangThamGiaBh" => (int)$so_thang_tham_gia_bh
        , "ThongTinChung_SoHopDong" => $NoiDungBaoHiem_SoHdTinDungKv
        , "NoiDungBaoHiem_TyLeKhoanVay" => (int)$TyLeKhoanVay
        , "NoiDungBaoHiem_PhiBaoHiem_VAT" => (int)$NoiDungBaoHiem_PhiBaoHiem
        , "NoiDungBaoHiem_TyLePhi" => (float)$NoiDungBaoHiem_TyLePhi
        , "NoiDungBaoHiem_SoTienBaoHiem" => ((int)$TyLeKhoanVay * (int)$GiaTriKhoanVay) / 100
        , "NoiDungBaoHiem_TyLeKhoanVay" => (int)$TyLeKhoanVay
        , "NoiDungBaoHiem_GiaTriKhoanVay" => (int)$GiaTriKhoanVay
        , "field_1"=>$company_code
        );
        // return  $province;
        $message = '';
        //return json_encode($dt_gic);
        $res = $this->push_api_gci('SaveProductDetail_Code', '', json_encode($dt_gic));
        $type_gic = "TN_TNNNV";
        $this->log_gic(json_encode($dt_gic), $res, $NoiDungBaoHiem_SoHdTinDungKv, $type_gic);
        //return $res;
        // var_dump($res->errors['Thongtinchung_Index']);
        if (!empty($res)) {
            if (!empty($res->errors->Thongtinchung_Index[0])) {
                $message = 'Thông tin Index không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TrangThaiHdId[0])) {
                $message = 'Thông tin trạng thái hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_SoHopDong[0])) {
                $message = 'Thông tin số hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_ThoiGianGuiMailSms[0])) {
                $message = 'Thông tin thời gian gửi mail không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_ChiNhanhId[0])) {
                $message = 'Thông tin chi nhánh không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TenNhanVienBanHang[0])) {
                $message = 'Thông tin tên nhân viên bán hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_EmailNhanVien[0])) {
                $message = 'Thông tin Email nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_DienThoaiNhanVien[0])) {
                $message = 'Thông tin điện thoại nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaNhanVien[0])) {
                $message = 'Thông tin mã nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TenNhanVienGIC[0])) {
                $message = 'Thông tin tên nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_EmailNhanVienGIC[0])) {
                $message = 'Thông tin email nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_DienThoaiNhanVienGIC[0])) {
                $message = 'Thông tin điện thoại nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaNhanVienGIC[0])) {
                $message = 'Thông tin mã nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MissCodeNhanVienBanHang[0])) {
                $message = 'Thông tin code nhân viên bán hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaDonViCuaChiNhanhDoiTac[0])) {
                $message = 'Thông tin mã đơn vị chi nhánh đối tác không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_IBMS[0])) {
                $message = 'Thông tin hóa đơn IBMS không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_Core[0])) {
                $message = 'Thông tin hó đơn codre không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_SoHoaDon[0])) {
                $message = 'Thông tin số hóa đơn không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_MaSoBiMat[0])) {
                $message = 'Thông tin Mã số bí mật không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_TenSPTrenHopDong[0])) {
                $message = 'Thông tin tên SPT hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_MST[0])) {
                $message = 'Thông tin hóa đơn MST không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_LinkHoaDon[0])) {
                $message = 'Thông tin link hóa đơn không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_Ten[0])) {
                $message = 'Thông tin tên khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_GioiTinhId[0])) {
                $message = 'Thông tin giới tính khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_NgaySinh[0])) {
                $message = 'Thông tin ngày sinh khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_SoCMND[0])) {
                $message = 'Thông tin số CMND khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_Email[0])) {
                $message = 'Thông tin email khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_SoDienThoai[0])) {
                $message = 'Thông tin số điện thoại khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi[0])) {
                $message = 'Thông tin địa chỉ khách hàng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoHdTinDungKv[0])) {
                $message = 'Thông tin số hợp đồng tín dụng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeKhoanVay[0])) {
                $message = 'Thông tin tỉ lệ khoản vay không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_GiaTriKhoanVay[0])) {
                $message = 'Thông tin giá trị khoản vay không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLePhi[0])) {
                $message = 'Thông tin tỉ lệ phí không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoTienBaoHiem[0])) {
                $message = 'Thông tin số tiền bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem_VAT[0])) {
                $message = 'Thông tin phí bảo hiểm VAT không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_Thue_VAT[0])) {
                $message = 'Thông tin thuế VAT không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem[0])) {
                $message = 'Thông tin phí bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayYeuCauBh[0])) {
                $message = 'Thông tin ngày yêu cầu bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHieuLucBaoHiem[0])) {
                $message = 'Thông tin ngày hiệu lực bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHieuLucBaoHiemDen[0])) {
                $message = 'Thông tin ngày hiệu lực bảo hiểm đến không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoThangThamGiaBh[0])) {
                $message = 'Thông tin số tháng tham gia bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHuyHd[0])) {
                $message = 'Thông tin ngày hủy hợp đồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoTienHoanKhach[0])) {
                $message = 'Thông tin số tiền hoàn khách không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayDuyet[0])) {
                $message = 'Thông tin ngày duyệt không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeHoaHong[0])) {
                $message = 'Thông tin tỷ lệ hoa hồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeHoTroDaiLy[0])) {
                $message = 'Thông tin tỉ lệ hỗ trợ đại lý không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLePhiDichVu[0])) {
                $message = 'Thông tin tỷ lệ phí dịch vụ không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_HoaHong[0])) {
                $message = 'Thông tin hoa hồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_HoTroDaiLy[0])) {
                $message = 'Thông tin hỗ trợ đại lý không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiDichVu[0])) {
                $message = 'Thông tin phí dịch vụ không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiNet[0])) {
                $message = 'Thông tin phí net không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_LyDoHuyHd[0])) {
                $message = 'Thông tin lý do hủy hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_HoTen[0])) {
                $message = 'Thông tin họ tên nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_CMND[0])) {
                $message = 'Thông tin CMND đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_DiaChi[0])) {
                $message = 'Thông tin địa chỉnhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_Email[0])) {
                $message = 'Thông tin email nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_DienThoai[0])) {
                $message = 'Thông tin điện thoại nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_DistrictId[0])) {
                $message = 'Thông tin địa chỉ nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_ProvinceId[0])) {
                $message = 'Thông tin địa chỉ nhà đầu tư không chính xác ';
            }
            if (!empty($res->messages) && is_array($res->messages)) {

                foreach ($res->messages as $key => $value) {
                    $message = $value->message . ' - ' . $value->code;
                }
            }
            if (isset($res->success)) {
                if (!$res->success) {
                    $dt_re = array(
                        'message' => $message,
                        'success' => false
                    );
                    return json_decode(json_encode($dt_re));
                } else {
                    return $res;
                }
            } else {
                $dt_re = array(
                    'message' => $message,
                    'success' => false
                );
                return json_decode(json_encode($dt_re));
            }
        } else {

            $dt_re = array(
                'message' => "Kết nối đến GIC bị lỗi !",
                'success' => false
            );
            return json_decode(json_encode($dt_re));
        }
        // return $this->slugify($province;
        //return $data;
    }


    //tạo hợp đồng trên gic easy
    public function insert_gic_easy($data, $code_contract, $disbursement_date)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $branch_id_gic = $this->config->item("branch_id_gic");
        $city = $this->city_gic_model->findOne(array('code' => 'GIC'));
        $config = $this->config_gic_model->findOne(array('name' => 'GICEASY_TIENNGAY'));
        $NoiDungBaoHiem_SoHdTinDungKv = (!empty($code_contract)) ? $code_contract : "";
        $TyLeKhoanVay = (!empty($config['TyLeKhoanVay'])) ? $config['TyLeKhoanVay'] : 0;
        $code_gic = (!empty($config['code'])) ? $config['code'] : "";
        $GiaTriKhoanVay = (!empty($data['loan_infor']['amount_money'])) ? $data['loan_infor']['amount_money'] : 0;
        $NgayYeuCauBh = $disbursement_date;
        $NgayHieuLucBaoHiem = $disbursement_date;
        $NgayHieuLucBaoHiemDen = date('Y-m-d H:i:s', strtotime($NgayHieuLucBaoHiem . ' + 1 year'));
        $company_code = !empty($data['company_code']) ? $data['company_code'] : '';
        $customer_name = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $customer_BOD = (!empty($data['customer_infor']['customer_BOD'])) ? $data['customer_infor']['customer_BOD'] : '';
        $customer_identify = (!empty($data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
        $name_investor = (!empty($data['investor_infor']['name'])) ? $data['investor_infor']['name'] : '.';
        $houseHold_address = (!empty($data['houseHold_address']['ward_name'])) ? $data['houseHold_address']['address_household'] . ' - ' . $data['houseHold_address']['ward_name'] : '.....';
        $current_address = (!empty($data['current_address']['ward_name'])) ? $data['current_address']['current_stay'] . ' - ' . $data['current_address']['ward_name'] . ' - ' . $data['current_address']['district_name'] . ' - ' . $data['current_address']['province_name'] : '.....';
        $province = (!empty($data['houseHold_address']['province_name'])) ? $data['houseHold_address']['province_name'] : '';
        $district = (!empty($data['houseHold_address']['district_name'])) ? $data['houseHold_address']['district_name'] : '';
        $province_current = (!empty($data['current_address']['province_name'])) ? $data['current_address']['province_name'] : '';
        $district_current = (!empty($data['current_address']['district_name'])) ? $data['current_address']['district_name'] : '';
        $customer_phone_number = (!empty($data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';
        $customer_email = (!empty($data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
        $customer_gender = (!empty($data['customer_infor']['customer_gender'])) ? $data['customer_infor']['customer_gender'] : '1';
        $customer_gender = ($customer_gender == '1') ? 'dbb6424f-3890-4108-a094-3a17884885f3' : '27541417-9bf3-4b96-8bd2-edb4b8cf352a';
        $ProvinceId = '5c3b316f-91ad-46fc-a26c-8331be3b7739';
        $DistrictId = '131EBCCF-CC5E-4C5F-B324-48F8BC8A2F56';
        $ProvinceId_current = '5c3b316f-91ad-46fc-a26c-8331be3b7739';
        $DistrictId_current = '131EBCCF-CC5E-4C5F-B324-48F8BC8A2F56';
        $nhanhieu = "";
        $model = "";
        $biensoxe = "";
        $sokhung = "";
        $somay = "";
        $hotenchuxe = "";
        $diachidangky = "000000";
        if (!empty($data['property_infor'])) {

            if (isset($data['property_infor'][0]['value'])) {
                $nhanhieu = $data['property_infor'][0]['value'];
            }
            if (isset($data['property_infor'][1]['value'])) {
                $model = $data['property_infor'][1]['value'];
            }
            if (isset($data['property_infor'][2]['value'])) {
                $biensoxe = $data['property_infor'][2]['value'];
            }
            if (isset($data['property_infor'][3]['value'])) {
                $sokhung = $data['property_infor'][3]['value'];
            }
            if (isset($data['property_infor'][4]['value'])) {
                $somay = $data['property_infor'][4]['value'];
            }
            if (isset($data['property_infor'][5]['value'])) {
                $hotenchuxe = $data['property_infor'][5]['value'];
            }
            if (isset($data['property_infor'][6]['value'])) {
                $diachidangky = $data['property_infor'][6]['value'];
            }
        }
        $amount_GIC_easy = (!empty($data['loan_infor']['amount_GIC_easy'])) ? $data['loan_infor']['amount_GIC_easy'] : 0;
        $code_GIC_easy = (!empty($data['loan_infor']['code_GIC_easy'])) ? $data['loan_infor']['code_GIC_easy'] : "";
        if (!empty($city['city'])) {
            // chỉ giữ lại tên slug của tỉnh/thành phố. VD: Thành phố Hà Nội => ha-noi
            $name_slug_province = $this->slugify(str_replace("Tỉnh ", "", $province));
            $name_slug_province = $this->slugify(str_replace("Thành phố", "", $name_slug_province));
            $name_slug_province = $this->slugify(str_replace("thi-xa-", "", $name_slug_province));
            $name_slug_province = $this->slugify(str_replace("thanh-pho-", "", $name_slug_province));

            $name_slug_province_current = $this->slugify(str_replace("Tỉnh ", "", $province_current));
            $name_slug_province_current = $this->slugify(str_replace("Thành phố", "", $name_slug_province_current));
            $name_slug_province_current = $this->slugify(str_replace("thi-xa-", "", $name_slug_province_current));
            $name_slug_province_current = $this->slugify(str_replace("thanh-pho-", "", $name_slug_province_current));
            foreach ($city['city'] as $key => $value) {
                if ($this->slugify($value['name']) == $name_slug_province) {
                    $ProvinceId = $value['id'];
                }
                if ($this->slugify($value['name']) == $name_slug_province_current) {
                    $ProvinceId_current = $value['id'];
                }
            }
        }
        if (!empty($city['district'])) {
            foreach ($city['district'] as $key => $value) {
                if ($this->slugify($value['name']) == $this->slugify($district)) {
                    $DistrictId = $value['id'];
                }
                if ($this->slugify($value['name']) == $this->slugify($district_current)) {
                    $DistrictId_current = $value['id'];
                }
            }
        }
        $r = $this->push_api_gci('GetInsurancePackageFromProductCode', '&code=' . $code_gic);
        if (isset($r->success) && $r->success) {
            if (!empty($r->data)) {
                foreach ($r->data as $key => $value) {
                    if ($value->code == $code_GIC_easy) {
                        $code_GIC_easy = $value->id;
                    }
                }
            } else {
                $dt_re = array(
                    'message' => "Kết nối đến GIC bị lỗi, không lấy được ID GIC EASY ! 1",
                    'success' => false
                );
                return json_decode(json_encode($dt_re));
            }

        } else {
            $dt_re = array(
                'message' => "Kết nối đến GIC bị lỗi, không lấy được ID GIC EASY ! 2",
                'success' => false
            );
            return json_decode(json_encode($dt_re));
        }
        if ($code_GIC_easy == "" || in_array($code_GIC_easy, array("GIC_EASY_40", "GIC_EASY_70", "GIC_EASY_20", FALSE))) {
            $dt_re = array(
                'message' => "Kết nối đến GIC bị lỗi, không lấy được ID GIC EASY ! 3",
                'success' => false
            );
            return json_decode(json_encode($dt_re));
        }
        $dt_gic = array(
            'thongTinChung_NhanVienId' => $config['NhanVienId']
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_NgaySinh' => $customer_BOD
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_SoCMND' => $customer_identify
        , 'NoiDungBaoHiem_NgayHieuLucBaoHiemDen' => $NgayHieuLucBaoHiemDen
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_Ten' => $customer_name
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_ProvinceId' => $ProvinceId
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_DistrictId' => $DistrictId
        , 'NoiDungBaoHiem_NgayHieuLucBaoHiem' => $NgayHieuLucBaoHiem
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi' => $houseHold_address
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_SoDienThoai' => $customer_phone_number
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_Email' => $customer_email
        , 'NoiDungBaoHiem_SoHdTinDungKv' => $NoiDungBaoHiem_SoHdTinDungKv
        , 'NoiDungBaoHiem_NgayYeuCauBh' => $NgayYeuCauBh
        , 'ThongTinNguoiDuocBaoHiem_CaNhan_GioiTinhId' => $customer_gender
        , 'ThongTinNguoiNhanCNBH_TenNguoiNhan' => $customer_name
        , 'ThongTinNguoiNhanCNBH_DienThoaiNhan' => $customer_phone_number
        , 'ThongTinNguoiNhanCNBH_EmailNhan' => $customer_email
        , 'ThongTinNguoiNhanCNBH_DiaChiNhan' => $current_address
        , 'NoiDungBaoHiem_GoiBaoHiemId' => $code_GIC_easy
        , 'NoiDungBaoHiem_PhiBaoHiem_VAT' => $amount_GIC_easy
        , 'NoiDungBaoHiem_ThongTinChuXe_HoTen' => $hotenchuxe
        , 'NoiDungBaoHiem_ThongTinXe_TheoBienKiemSoat' => 'False'
        , 'NoiDungBaoHiem_ThongTinXe_BienKiemSoat' => $biensoxe
        , 'NoiDungBaoHiem_ThongTinXe_SoKhung' => $sokhung
        , 'NoiDungBaoHiem_ThongTinXe_SoMay' => $somay
        , 'NoiDungBaoHiem_ThongTinChuXe_DiaChi' => $diachidangky
        , 'ThongTinNguoiNhanCNBH_DiaChiNhan_ProvinceId' => $ProvinceId_current
        , 'ThongTinNguoiNhanCNBH_DiaChiNhan_DistrictId' => $DistrictId_current
        , "productCode" => $code_gic
        , "NoiDungBaoHiem_SoThangThamGiaBh" => 12
        , "field_1"=>$company_code
        );
        // return  $province;
        $message = '';
        // $dt_re=array(
        //      'message'=>$dt_gic,
        //      'success'=>false
        //  );
        //  return  json_decode(json_encode($dt_re));
        $res = $this->push_api_gci('SaveProductDetail_Code', '', json_encode($dt_gic));
        $type_gic = $code_gic;
        $this->log_gic(json_encode($dt_gic), $res, $NoiDungBaoHiem_SoHdTinDungKv, $type_gic);
        //return $res;
        // var_dump($res->errors['Thongtinchung_Index']);
        if (!empty($res)) {
            if (!empty($res->errors->Thongtinchung_Index[0])) {
                $message = 'Thông tin Index không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TrangThaiHdId[0])) {
                $message = 'Thông tin trạng thái hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_SoHopDong[0])) {
                $message = 'Thông tin số hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_ThoiGianGuiMailSms[0])) {
                $message = 'Thông tin thời gian gửi mail không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_ChiNhanhId[0])) {
                $message = 'Thông tin chi nhánh không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TenNhanVienBanHang[0])) {
                $message = 'Thông tin tên nhân viên bán hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_EmailNhanVien[0])) {
                $message = 'Thông tin Email nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_DienThoaiNhanVien[0])) {
                $message = 'Thông tin điện thoại nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaNhanVien[0])) {
                $message = 'Thông tin mã nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TenNhanVienGIC[0])) {
                $message = 'Thông tin tên nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_EmailNhanVienGIC[0])) {
                $message = 'Thông tin email nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_DienThoaiNhanVienGIC[0])) {
                $message = 'Thông tin điện thoại nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaNhanVienGIC[0])) {
                $message = 'Thông tin mã nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MissCodeNhanVienBanHang[0])) {
                $message = 'Thông tin code nhân viên bán hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaDonViCuaChiNhanhDoiTac[0])) {
                $message = 'Thông tin mã đơn vị chi nhánh đối tác không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_IBMS[0])) {
                $message = 'Thông tin hóa đơn IBMS không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_Core[0])) {
                $message = 'Thông tin hó đơn codre không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_SoHoaDon[0])) {
                $message = 'Thông tin số hóa đơn không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_MaSoBiMat[0])) {
                $message = 'Thông tin Mã số bí mật không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_TenSPTrenHopDong[0])) {
                $message = 'Thông tin tên SPT hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_MST[0])) {
                $message = 'Thông tin hóa đơn MST không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_LinkHoaDon[0])) {
                $message = 'Thông tin link hóa đơn không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_Ten[0])) {
                $message = 'Thông tin tên khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_GioiTinhId[0])) {
                $message = 'Thông tin giới tính khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_NgaySinh[0])) {
                $message = 'Thông tin ngày sinh khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_SoCMND[0])) {
                $message = 'Thông tin số CMND khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_Email[0])) {
                $message = 'Thông tin email khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_SoDienThoai[0])) {
                $message = 'Thông tin số điện thoại khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi[0])) {
                $message = 'Thông tin địa chỉ khách hàng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoHdTinDungKv[0])) {
                $message = 'Thông tin số hợp đồng tín dụng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeKhoanVay[0])) {
                $message = 'Thông tin tỉ lệ khoản vay không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_GiaTriKhoanVay[0])) {
                $message = 'Thông tin giá trị khoản vay không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLePhi[0])) {
                $message = 'Thông tin tỉ lệ phí không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoTienBaoHiem[0])) {
                $message = 'Thông tin số tiền bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem_VAT[0])) {
                $message = 'Thông tin phí bảo hiểm VAT không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_Thue_VAT[0])) {
                $message = 'Thông tin thuế VAT không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem[0])) {
                $message = 'Thông tin phí bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayYeuCauBh[0])) {
                $message = 'Thông tin ngày yêu cầu bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHieuLucBaoHiem[0])) {
                $message = 'Thông tin ngày hiệu lực bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHieuLucBaoHiemDen[0])) {
                $message = 'Thông tin ngày hiệu lực bảo hiểm đến không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoThangThamGiaBh[0])) {
                $message = 'Thông tin số tháng tham gia bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHuyHd[0])) {
                $message = 'Thông tin ngày hủy hợp đồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoTienHoanKhach[0])) {
                $message = 'Thông tin số tiền hoàn khách không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayDuyet[0])) {
                $message = 'Thông tin ngày duyệt không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeHoaHong[0])) {
                $message = 'Thông tin tỷ lệ hoa hồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeHoTroDaiLy[0])) {
                $message = 'Thông tin tỉ lệ hỗ trợ đại lý không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLePhiDichVu[0])) {
                $message = 'Thông tin tỷ lệ phí dịch vụ không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_HoaHong[0])) {
                $message = 'Thông tin hoa hồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_HoTroDaiLy[0])) {
                $message = 'Thông tin hỗ trợ đại lý không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiDichVu[0])) {
                $message = 'Thông tin phí dịch vụ không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiNet[0])) {
                $message = 'Thông tin phí net không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_LyDoHuyHd[0])) {
                $message = 'Thông tin lý do hủy hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_HoTen[0])) {
                $message = 'Thông tin họ tên nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_CMND[0])) {
                $message = 'Thông tin CMND đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_DiaChi[0])) {
                $message = 'Thông tin địa chỉnhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_Email[0])) {
                $message = 'Thông tin email nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_DienThoai[0])) {
                $message = 'Thông tin điện thoại nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_DistrictId[0])) {
                $message = 'Thông tin địa chỉ nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_GoiBaoHiemId[0])) {
                $message = 'Thông tin gói bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem_VAT[0])) {
                $message = 'Thông tin phí bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_ThongTinChuXe_HoTen[0])) {
                $message = 'Thông tin họ tên chủ xe không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_ThongTinXe_BienKiemSoat[0])) {
                $message = 'Thông tin biển kiểm soát không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_ThongTinXe_SoKhung[0])) {
                $message = 'Thông tin số khung không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_ThongTinXe_SoMay[0])) {
                $message = 'Thông tin số máy không chính xác ';
            }
            if (!empty($res->messages) && is_array($res->messages)) {

                foreach ($res->messages as $key => $value) {
                    $message = $value->message . ' - ' . $value->code;
                }
            }
            if (isset($res->success)) {
                if (!$res->success) {
                    $dt_re = array(
                        'message' => $message,
                        'success' => false
                    );
                    return json_decode(json_encode($dt_re));
                } else {
                    return $res;
                }
            } else {
                $dt_re = array(
                    'message' => $message,
                    'success' => false
                );
                return json_decode(json_encode($dt_re));
            }
        } else {
            $dt_re = array(
                'message' => "Kết nối đến GIC bị lỗi !",
                'success' => false
            );
            return json_decode(json_encode($dt_re));
        }
        // return $this->slugify($province;
        //return $data;
    }

    //tạo hợp đồng trên gic phúc lộc thọ
    public function insert_gic_plt($data, $code_contract, $disbursement_date)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $branch_id_gic = $this->config->item("branch_id_gic");
        $city = $this->city_gic_model->findOne(array('code' => 'GIC'));
        $config = $this->config_gic_model->findOne(array('code' => 'TN_BHPLT'));
        $NoiDungBaoHiem_SoHdTinDungKv = (!empty($code_contract)) ? $code_contract : "";
        $TyLeKhoanVay = (!empty($config['TyLeKhoanVay'])) ? $config['TyLeKhoanVay'] : 0;
        $LoaiNguoiThuHuongId = (!empty($config['LoaiNguoiThuHuongId'])) ? $config['LoaiNguoiThuHuongId'] : '';
        $code_gic = (!empty($config['code'])) ? $config['code'] : "";
        $GiaTriKhoanVay = (!empty($data['loan_infor']['amount_money'])) ? $data['loan_infor']['amount_money'] : 0;
        $NgayYeuCauBh = $disbursement_date;
        $NgayHieuLucBaoHiem = $disbursement_date;
        $NgayHieuLucBaoHiemDen = date('Y-m-d H:i:s', strtotime($NgayHieuLucBaoHiem . ' + 1 year'));
        $customer_name = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $customer_BOD = (!empty($data['customer_infor']['customer_BOD'])) ? $data['customer_infor']['customer_BOD'] : '';
        $customer_identify = (!empty($data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
        $name_investor = (!empty($data['investor_infor']['name'])) ? $data['investor_infor']['name'] : '.';
        $houseHold_address = (!empty($data['houseHold_address']['ward_name'])) ? $data['houseHold_address']['address_household'] . ' - ' . $data['houseHold_address']['ward_name'] : '.....';
        $current_address = (!empty($data['current_address']['ward_name'])) ? $data['current_address']['current_stay'] . ' - ' . $data['current_address']['ward_name'] . ' - ' . $data['current_address']['district_name'] . ' - ' . $data['current_address']['province_name'] : '.....';
        $province = (!empty($data['houseHold_address']['province_name'])) ? $data['houseHold_address']['province_name'] : '';
        $district = (!empty($data['houseHold_address']['district_name'])) ? $data['houseHold_address']['district_name'] : '';
        $province_current = (!empty($data['current_address']['province_name'])) ? $data['current_address']['province_name'] : '';
        $district_current = (!empty($data['current_address']['district_name'])) ? $data['current_address']['district_name'] : '';
        $customer_phone_number = (!empty($data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';
        $customer_email = (!empty($data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
        $customer_gender = (!empty($data['customer_infor']['customer_gender'])) ? $data['customer_infor']['customer_gender'] : '1';
        $company_code = !empty($data['company_code']) ? $data['company_code'] : '';
        $customer_gender = ($customer_gender == '1') ? 'dbb6424f-3890-4108-a094-3a17884885f3' : '27541417-9bf3-4b96-8bd2-edb4b8cf352a';
        $ProvinceId = '5c3b316f-91ad-46fc-a26c-8331be3b7739';
        $DistrictId = '131EBCCF-CC5E-4C5F-B324-48F8BC8A2F56';
        $ProvinceId_current = '5c3b316f-91ad-46fc-a26c-8331be3b7739';
        $DistrictId_current = '131EBCCF-CC5E-4C5F-B324-48F8BC8A2F56';

        $amount_GIC_plt = (!empty($data['loan_infor']['code_GIC_plt'])) ? money_gic_plt($data['loan_infor']['code_GIC_plt']) : 0;
        $code_GIC_plt = (!empty($data['loan_infor']['code_GIC_plt'])) ? $data['loan_infor']['code_GIC_plt'] : "";
        if (!empty($city['city'])) {
            // chỉ giữ lại tên slug của tỉnh/thành phố. VD: Thành phố Hà Nội => ha-noi
            $name_slug_province = $this->slugify(str_replace("Tỉnh ", "", $province));
            $name_slug_province = $this->slugify(str_replace("Thành phố", "", $name_slug_province));
            $name_slug_province = $this->slugify(str_replace("thi-xa-", "", $name_slug_province));
            $name_slug_province = $this->slugify(str_replace("thanh-pho-", "", $name_slug_province));

            $name_slug_province_current = $this->slugify(str_replace("Tỉnh ", "", $province_current));
            $name_slug_province_current = $this->slugify(str_replace("Thành phố", "", $name_slug_province_current));
            $name_slug_province_current = $this->slugify(str_replace("thi-xa-", "", $name_slug_province_current));
            $name_slug_province_current = $this->slugify(str_replace("thanh-pho-", "", $name_slug_province_current));
            foreach ($city['city'] as $key => $value) {
                if ($this->slugify($value['name']) == $name_slug_province) {
                    $ProvinceId = $value['id'];
                }
                if ($this->slugify($value['name']) == $name_slug_province_current) {
                    $ProvinceId_current = $value['id'];
                }
            }
        }
        $name = "";
        if (!empty($city['district'])) {
            foreach ($city['district'] as $key => $value) {
                if ($this->slugify($value['name']) == $this->slugify($district)) {
                    $DistrictId = $value['id'];
                }
                if ($this->slugify($value['name']) == $this->slugify($district_current)) {
                    $DistrictId_current = $value['id'];
                }
            }
        }
        $id_GIC_plt = "";
        $r = $this->push_api_gci('GetInsurancePackageFromProductCode', '&code=' . $code_gic);
        if (isset($r->success) && $r->success) {
            if (!empty($r->data)) {
                foreach ($r->data as $key => $value) {
                    if ($value->code == $code_GIC_plt) {
                        $id_GIC_plt = $value->id;
                    }
                }
            } else {
                $this->log_gic('Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 1', $r, $NoiDungBaoHiem_SoHdTinDungKv, $code_gic);
                $dt_re = array(
                    'message' => "Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 1",
                    'success' => false
                );
                return json_decode(json_encode($dt_re));
            }

        } else {
            $this->log_gic('Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 2', $r, $NoiDungBaoHiem_SoHdTinDungKv, $code_gic);
            $dt_re = array(
                'message' => "Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 2",
                'success' => false
            );
            return json_decode(json_encode($dt_re));
        }
        if ($code_GIC_plt == "" || !in_array($code_GIC_plt, array("SILVER", "COPPER", "GOLD", FALSE))) {
            $this->log_gic('Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 3', $r, $NoiDungBaoHiem_SoHdTinDungKv, $code_gic);
            $dt_re = array(
                'message' => "Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 3",
                'success' => false
            );
            return json_decode(json_encode($dt_re));
        }
        $dt_gic = array(
            'thongTinChung_MaNhanVien' => $config['NhanVienId']
        , 'thongTinNguoiDuocBaoHiem_CaNhan_NgaySinh' => $customer_BOD
        , 'thongTinNguoiDuocBaoHiem_CaNhan_SoCMND' => $customer_identify
        , 'noiDungBaoHiem_NgayHieuLucBaoHiemDen' => $NgayHieuLucBaoHiemDen
        , 'noiDungBaoHiem_NgayHieuLucBaoHiem' => $NgayHieuLucBaoHiem
        , 'noiDungBaoHiem_NgayYeuCauBh' => $NgayYeuCauBh
        , 'thongTinNguoiDuocBaoHiem_CaNhan_GioiTinhId' => $customer_gender
        , 'thongTinNguoiDuocBaoHiem_CaNhan_Ten' => $customer_name
        , 'thongTinNguoiDuocBaoHiem_CaNhan_SoDienThoai' => $customer_phone_number
        , 'thongTinNguoiDuocBaoHiem_CaNhan_Email' => $customer_email
        , 'thongTinNguoiDuocBaoHiem_CaNhan_DiaChi' => $current_address
        , 'noiDungBaoHiem_GoiBaoHiemId' => $id_GIC_plt
        , 'noiDungBaoHiem_PhiBaoHiem_VAT' => $amount_GIC_plt
        , 'thongTinNguoiDuocBaoHiem_CaNhan_DiaChi_ProvinceId' => $ProvinceId_current
        , 'thongTinNguoiDuocBaoHiem_CaNhan_DiaChi_DistrictId' => $DistrictId_current
        , "productCode" => $code_gic
        , "noiDungBaoHiem_SoThangThamGiaBh" => 12
        , 'nguoiThuHuong_LoaiNguoiThuHuongId' => $LoaiNguoiThuHuongId
        , "field_1"=>$company_code
        );
        // return  $province;
        $message = '';
        // $dt_re=array(
        //      'message'=>$dt_gic,
        //      'success'=>false
        //  );
        //  return  json_decode(json_encode($dt_re));
        $res = $this->push_api_gci('SaveProductDetail_Code', '', json_encode($dt_gic));
        $type_gic = $code_gic;
        $this->log_gic(json_encode($dt_gic), $res, $NoiDungBaoHiem_SoHdTinDungKv, $type_gic);
        //return $res;
        // var_dump($res->errors['Thongtinchung_Index']);
        if (!empty($res)) {
            if (!empty($res->errors->Thongtinchung_Index[0])) {
                $message = 'Thông tin Index không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TrangThaiHdId[0])) {
                $message = 'Thông tin trạng thái hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_SoHopDong[0])) {
                $message = 'Thông tin số hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_ThoiGianGuiMailSms[0])) {
                $message = 'Thông tin thời gian gửi mail không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_ChiNhanhId[0])) {
                $message = 'Thông tin chi nhánh không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TenNhanVienBanHang[0])) {
                $message = 'Thông tin tên nhân viên bán hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_EmailNhanVien[0])) {
                $message = 'Thông tin Email nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_DienThoaiNhanVien[0])) {
                $message = 'Thông tin điện thoại nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaNhanVien[0])) {
                $message = 'Thông tin mã nhân viên không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_TenNhanVienGIC[0])) {
                $message = 'Thông tin tên nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_EmailNhanVienGIC[0])) {
                $message = 'Thông tin email nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_DienThoaiNhanVienGIC[0])) {
                $message = 'Thông tin điện thoại nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaNhanVienGIC[0])) {
                $message = 'Thông tin mã nhân viên GIC không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MissCodeNhanVienBanHang[0])) {
                $message = 'Thông tin code nhân viên bán hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinChung_MaDonViCuaChiNhanhDoiTac[0])) {
                $message = 'Thông tin mã đơn vị chi nhánh đối tác không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_IBMS[0])) {
                $message = 'Thông tin hóa đơn IBMS không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_Core[0])) {
                $message = 'Thông tin hó đơn codre không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_SoHoaDon[0])) {
                $message = 'Thông tin số hóa đơn không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_MaSoBiMat[0])) {
                $message = 'Thông tin Mã số bí mật không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_TenSPTrenHopDong[0])) {
                $message = 'Thông tin tên SPT hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_MST[0])) {
                $message = 'Thông tin hóa đơn MST không chính xác ';
            }
            if (!empty($res->errors->ThongTinHoaDon_LinkHoaDon[0])) {
                $message = 'Thông tin link hóa đơn không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_Ten[0])) {
                $message = 'Thông tin tên khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_GioiTinhId[0])) {
                $message = 'Thông tin giới tính khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_NgaySinh[0])) {
                $message = 'Thông tin ngày sinh khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_SoCMND[0])) {
                $message = 'Thông tin số CMND khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_Email[0])) {
                $message = 'Thông tin email khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_SoDienThoai[0])) {
                $message = 'Thông tin số điện thoại khách hàng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi[0])) {
                $message = 'Thông tin địa chỉ khách hàng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoHdTinDungKv[0])) {
                $message = 'Thông tin số hợp đồng tín dụng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeKhoanVay[0])) {
                $message = 'Thông tin tỉ lệ khoản vay không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_GiaTriKhoanVay[0])) {
                $message = 'Thông tin giá trị khoản vay không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLePhi[0])) {
                $message = 'Thông tin tỉ lệ phí không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoTienBaoHiem[0])) {
                $message = 'Thông tin số tiền bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem_VAT[0])) {
                $message = 'Thông tin phí bảo hiểm VAT không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_Thue_VAT[0])) {
                $message = 'Thông tin thuế VAT không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem[0])) {
                $message = 'Thông tin phí bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayYeuCauBh[0])) {
                $message = 'Thông tin ngày yêu cầu bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHieuLucBaoHiem[0])) {
                $message = 'Thông tin ngày hiệu lực bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHieuLucBaoHiemDen[0])) {
                $message = 'Thông tin ngày hiệu lực bảo hiểm đến không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoThangThamGiaBh[0])) {
                $message = 'Thông tin số tháng tham gia bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayHuyHd[0])) {
                $message = 'Thông tin ngày hủy hợp đồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_SoTienHoanKhach[0])) {
                $message = 'Thông tin số tiền hoàn khách không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_NgayDuyet[0])) {
                $message = 'Thông tin ngày duyệt không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeHoaHong[0])) {
                $message = 'Thông tin tỷ lệ hoa hồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLeHoTroDaiLy[0])) {
                $message = 'Thông tin tỉ lệ hỗ trợ đại lý không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_TyLePhiDichVu[0])) {
                $message = 'Thông tin tỷ lệ phí dịch vụ không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_HoaHong[0])) {
                $message = 'Thông tin hoa hồng không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_HoTroDaiLy[0])) {
                $message = 'Thông tin hỗ trợ đại lý không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiDichVu[0])) {
                $message = 'Thông tin phí dịch vụ không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiNet[0])) {
                $message = 'Thông tin phí net không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_LyDoHuyHd[0])) {
                $message = 'Thông tin lý do hủy hợp đồng không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_HoTen[0])) {
                $message = 'Thông tin họ tên nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_CMND[0])) {
                $message = 'Thông tin CMND đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_DiaChi[0])) {
                $message = 'Thông tin địa chỉnhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_Email[0])) {
                $message = 'Thông tin email nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiChoVay_DienThoai[0])) {
                $message = 'Thông tin điện thoại nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->ThongTinNguoiDuocBaoHiem_CaNhan_DiaChi_DistrictId[0])) {
                $message = 'Thông tin địa chỉ nhà đầu tư không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_GoiBaoHiemId[0])) {
                $message = 'Thông tin gói bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_PhiBaoHiem_VAT[0])) {
                $message = 'Thông tin phí bảo hiểm không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_ThongTinChuXe_HoTen[0])) {
                $message = 'Thông tin họ tên chủ xe không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_ThongTinXe_BienKiemSoat[0])) {
                $message = 'Thông tin biển kiểm soát không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_ThongTinXe_SoKhung[0])) {
                $message = 'Thông tin số khung không chính xác ';
            }
            if (!empty($res->errors->NoiDungBaoHiem_ThongTinXe_SoMay[0])) {
                $message = 'Thông tin số máy không chính xác ';
            }
            if (!empty($res->messages) && is_array($res->messages)) {

                foreach ($res->messages as $key => $value) {
                    $message = $value->message . ' - ' . $value->code;
                }
            }
            if (isset($res->success)) {
                if (!$res->success) {
                    $dt_re = array(
                        'message' => $message,
                        'success' => false
                    );
                    return json_decode(json_encode($dt_re));
                } else {
                    return $res;
                }
            } else {
                $dt_re = array(
                    'message' => $message,
                    'success' => false
                );
                return json_decode(json_encode($dt_re));
            }
        } else {

            $dt_re = array(
                'message' => "Kết nối đến GIC bị lỗi !",
                'success' => false
            );
            return json_decode(json_encode($dt_re));
        }
        // return $this->slugify($province;
        //return $data;
    }

    private function insert_mic($data, $code_contract, $disbursement_date, $ma_cty)
    {
        $NGAY_HL = date('d/m/Y');
        $number_day_loan = (!empty($data['loan_infor']['number_day_loan'])) ? (int)$data['loan_infor']['number_day_loan'] / 30 : 0;
        $so_ngay_tham_gia_bh = ($number_day_loan <= 12) ? 12 * 30 : 24 * 30;
        $day = (!empty($data['loan_infor']['number_day_loan'])) ? $data['loan_infor']['number_day_loan'] : 0;
        $NGAY_KT = date('d/m/Y', strtotime('+' . $so_ngay_tham_gia_bh . ' days'));
        $SO_HD_VAY = $code_contract;
        $DCHI = (!empty($data['current_address'])) ? $data['current_address']['ward_name'] . ' - ' . $data['current_address']['district_name'] . ' - ' . $data['current_address']['province_name'] : '.....';
        $NG_HUONG = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $TEN = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $EMAIL = (!empty($data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
        $NG_SINH = (!empty($data['customer_infor']['customer_BOD'])) ? date('d/m/Y', strtotime($data['customer_infor']['customer_BOD'])) : '';
        $CMT = (!empty($data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
        $MOBI = (!empty($data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';
        $TTOAN = (!empty($data['loan_infor']['amount_MIC'])) ? $data['loan_infor']['amount_MIC'] : '';
        $TIEN = (!empty($data['loan_infor']['amount_money'])) ? $data['loan_infor']['amount_money'] : '';

        $originalXML = '<ns1:ws_GCN_TRA>
                     <!--Optional:-->
                     <ns1:xmlinput>
                     <![CDATA[
                    <XMLINPUT>
                    <MA_DVI>' . $this->config->item("MIC_MA_DVI") . '</MA_DVI>
                    <NSD>' . $this->config->item("MIC_NSD") . '</NSD>
                    <PAS>' . $this->config->item("MIC_PAS") . '</PAS>
                    <NV>NG_TD</NV>
                    <ID_TRAS>' . $SO_HD_VAY . '</ID_TRAS>
                    <TTOAN>' . (float)$TTOAN . '</TTOAN>
                    <TEN>' . $TEN . '</TEN>
                    <KIEU_HD>G</KIEU_HD>
                    <LKH>C</LKH>
                    <NG_SINH>' . $NG_SINH . '</NG_SINH>
                    <CMT>' . $CMT . '</CMT>
                    <MOBI>' . $MOBI . '</MOBI>
                    <EMAIL>' . $EMAIL . '</EMAIL>
                    <DCHI>' . $DCHI . '</DCHI>
                    <NG_HUONG>' . $NG_HUONG . '</NG_HUONG>
                    <SO_HDL>E</SO_HDL>
                    <SO_HD_VAY>' . $SO_HD_VAY . '</SO_HD_VAY>
                    <GUIHD>N</GUIHD>
                    <KIEUHD>E</KIEUHD>
                    <NGAY_HL>' . $NGAY_HL . '</NGAY_HL>
                    <NGAY_KT>' . $NGAY_KT . '</NGAY_KT>
                    <TIEN>' . (float)$TIEN . '</TIEN>
                    <MA_CTY>'.$ma_cty.'</MA_CTY>
                    </XMLINPUT>
            ]]>  </ns1:xmlinput>
                  </ns1:ws_GCN_TRA>
            ';

        $wsdl_url = $this->config->item("API_MIC") . '?wsdl';
        try {
            $params = new \SoapVar($originalXML, XSD_ANYXML);
            //var_dump($params ); die;
            $this->soapClient = new \SoapClient($wsdl_url, array('soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => false));
            $this->soapClient->__setLocation($this->config->item("API_MIC"));
            $result = $this->soapClient->ws_GCN_TRA($params);

            $xml = simplexml_load_string($result->ws_GCN_TRAResult);
            $this->log_mic($originalXML, $xml, $SO_HD_VAY, 'MIC_TDCN');
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

    private function insert_utv_vbi($data, $code_contract, $disbursement_date, $code_vbi)
    {
        $data['ten_chu_hd'] = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $data['diachi_chu_hd'] = (!empty($data['current_address'])) ? $data['current_address']['ward_name'] . ' - ' . $data['current_address']['district_name'] . ' - ' . $data['current_address']['province_name'] : '.....';
        $data['ngaysinh_chu_hd'] = (!empty($data['customer_infor']['customer_BOD'])) ? $data['customer_infor']['customer_BOD'] : '';
        $data['cmt_chu_hd'] = (!empty($data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
        $data['gioi_tinh_chu_hd'] = ($data['customer_infor']['customer_gender'] == 1) ? "NAM" : 'NU';
        $data['email_chu_hd'] = (!empty($data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
        $data['sdt_chu_hd'] = (!empty($data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';
        if (empty($code_vbi) || !is_numeric($code_vbi)) {
            $result = [
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Có lỗi xảy ra, liên hệ IT để hỗ trợ!'
            ];
            return json_decode(json_encode($result));
        }
        $data['goi_bao_hiem'] = get_goi_vbi((int)$code_vbi);
        $data['ngaysinh_nguoi_bh'] = $data['ngaysinh_chu_hd'];
        $data['ten_nguoi_bh'] = $data['ten_chu_hd'];
        $data['diachi_nguoi_bh'] = $data['diachi_chu_hd'];
        $data['gioi_tinh_nguoi_bh'] = $data['gioi_tinh_chu_hd'];
        $data['cmt_nguoi_bh'] = $data['cmt_chu_hd'];
        $data['cmt_ngay_cap_nguoi_bh'] = (!empty($data['customer_infor']['date_range'])) ? $data['customer_infor']['date_range'] : '';
        $data['cmt_noi_cap_nguoi_bh'] = (!empty($data['customer_infor']['issued_by'])) ? $data['customer_infor']['issued_by'] : '';
        $data['sdt_nguoi_bh'] = $data['sdt_chu_hd'];
        $data['email_nguoi_bh'] = $data['email_chu_hd'];
        $data['moi_quan_he'] = 'QH00000';

        $NGAY_HL = date('Ymd');
        $NGAY_KT = date('Ymd', strtotime("+1 year"));
        $nsd = $this->config->item("nsd_vbi_tnds");
        $so_id_dtac = md5(uniqid(time()));
        $raw = "nsd=$nsd&so_id_dtac=$so_id_dtac&nv=UTV&ten=" . $data['ten_chu_hd'] . "&dia_chi=" . $data['diachi_chu_hd'] . "&ngay_sinh=" . date('Ymd', strtotime($data['ngaysinh_chu_hd'])) . "&gioi_tinh=" . $data['gioi_tinh_chu_hd'] . "&cmt=" . $data['cmt_chu_hd'];
        $key = $this->config->item("private_key_vbi");
        $signature = $this->create_signature($raw, $key);

        $param = [
            "dtac_key" => $this->config->item("VBI_CODE"),
            "nsd" => $nsd,
            "so_id_dtac" => $so_id_dtac,
            "nv" => "UTV",
            "ten" => $data['ten_chu_hd'],
            "dchi" => $data['diachi_chu_hd'],
            "ngay_sinh" => date('Ymd', strtotime($data['ngaysinh_chu_hd'])),
            "gioi_tinh" => $data['gioi_tinh_chu_hd'],
            "cmt" => $data['cmt_chu_hd'],
            "d_thoai" => $data['sdt_chu_hd'],
            "email" => $data['email_chu_hd'],
            "mst" => "",
            "trang_thai_tt" => "D",
            "gcns" => [
                0 => [
                    "so_id_dt_dtac" => $so_id_dtac,
                    "goi_bh" => $data['goi_bao_hiem'],
                    "ten" => $data['ten_nguoi_bh'],
                    "dchi" => $data['diachi_nguoi_bh'],
                    "ngay_sinh" => date('Ymd', strtotime($data['ngaysinh_nguoi_bh'])),
                    "gioi_tinh" => $data['gioi_tinh_nguoi_bh'],
                    "cmt" => $data['cmt_nguoi_bh'],
                    "cmt_ngay_cap" => date('Ymd', strtotime($data['cmt_ngay_cap_nguoi_bh'])),
                    "cmt_noi_cap" => $data['cmt_noi_cap_nguoi_bh'],
                    "d_thoai" => $data['sdt_nguoi_bh'],
                    "email" => $data['email_nguoi_bh'],
                    "ngay_hl" => $NGAY_HL,
                    "ngay_kt" => $NGAY_KT,
                    "moi_qh" => $data['moi_quan_he'],
                ]
            ],
            "signature" => $signature
        ];
        $vbi = new BaoHiemVbi();
        $check_vbi = $this->vbi_model->findOne(["code_contract" => $data['code_contract'], 'type' => 'VBI_UTV']);
        if (!empty($check_vbi) && ($check_vbi['status_vbi'] == "active" || $check_vbi['status_vbi'] == "delete")) {
            $result = [
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Có lỗi xảy ra, liên hệ IT để hỗ trợ!'
            ];
            return json_decode(json_encode($result));
        }
        $result = $vbi->tao_don_bh_utv($param);
        $this->log_bh_vbi_model->insert(
            [
                'type' => 'utv',
                'contract_id' => (string)$data['_id'],
                'code_contract' => $code_contract,
                'request' => $param,
                'response' => $result,
                'created_at' => $this->createdAt,
                'created_by' => $this->uemail
            ]
        );
        if ($result->response_code == '00') {
            $store = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($data['id_pgd'])]);
            $code = "VBI_UTV_" . date("dmY") . "_" . uniqid();
            $insert = [
                'type' => 'VBI_UTV',
                'contract_id' => (string)$data['_id'],
                'code_contract' => $code_contract,
                'code' => $code,
                'fee' => $result->tong_phi,
                "goi_bh" => $data['goi_bao_hiem'],
                'NGAY_HL' => $NGAY_HL,
                'NGAY_KT' => $NGAY_KT,
                'customer_info' => [
                    'customer_name' => $data['ten_chu_hd'],
                    'customer_phone' => $data['sdt_chu_hd'],
                    'address' => $data['diachi_chu_hd'],
                    "cmt" => $data['cmt_chu_hd'],
                    "email" => $data['email_chu_hd'],
                    'ngay_sinh' => strtotime($data['ngaysinh_chu_hd'])
                ],
                'store' => [
                    'id' => (string)$store['_id'],
                    'name' => $store['name']
                ],
                'vbi_utv' => $result,
                'status_vbi' => 'active',
                'contract_info' => $data,
                'created_at' => $this->createdAt,
                'created_by' => $this->uemail,
                'updated_at' => $this->createdAt,
                'updated_by' => $this->uemail
            ];
            $check_vbi = $this->vbi_model->findOne(["code_contract" => $contract['code_contract'], 'type' => 'VBI_UTV']);
            if (!empty($check_vbi)) {
                unset($insert['created_at']);
                unset($insert['created_by']);
                unset($insert['code']);
                $this->vbi_model->update(['_id' => $check_vbi['_id']], $insert);
            } else {
                $old_vbi = $this->vbi_model->findOne(["code_contract" => $data['code_contract'], 'type' => 'VBI_UTV', 'status_vbi' => ['$ne' => 'active']]);
                $this->vbi_model->update(['_id' => $old_vbi['_id']], ['status_vbi' => 'delete']);
                $this->vbi_model->insert($insert);

            }
            $result = [
                'status' => REST_Controller::HTTP_OK,
                'message' => 'Thành công!',
            ];
            return json_decode(json_encode($result));

        } else {
            $result = [
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Bán bảo hiểm không thành công'
            ];
            return json_decode(json_encode($result));

        }
    }

    private function insert_sxh_vbi($data, $code_contract, $disbursement_date, $code_vbi)
    {
        $data['ten_chu_hd'] = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $data['diachi_chu_hd'] = (!empty($data['current_address'])) ? $data['current_address']['ward_name'] . ' - ' . $data['current_address']['district_name'] . ' - ' . $data['current_address']['province_name'] : '.....';
        $data['ngaysinh_chu_hd'] = (!empty($data['customer_infor']['customer_BOD'])) ? $data['customer_infor']['customer_BOD'] : '';
        $data['cmt_chu_hd'] = (!empty($data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
        $data['gioi_tinh_chu_hd'] = ($data['customer_infor']['customer_gender'] == 1) ? "NAM" : 'NU';
        $data['email_chu_hd'] = (!empty($data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
        $data['sdt_chu_hd'] = (!empty($data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';

        if (empty($code_vbi) || !is_numeric($code_vbi)) {
            $result = [
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Có lỗi xảy ra, liên hệ IT để hỗ trợ!'
            ];
            return json_decode(json_encode($result));
        }
        $data['goi_bao_hiem'] = get_goi_vbi((int)$code_vbi);
        $data['ngaysinh_nguoi_bh'] = $data['ngaysinh_chu_hd'];
        $data['ten_nguoi_bh'] = $data['ten_chu_hd'];
        $data['diachi_nguoi_bh'] = $data['diachi_chu_hd'];
        $data['gioi_tinh_nguoi_bh'] = $data['gioi_tinh_chu_hd'];
        $data['cmt_nguoi_bh'] = $data['cmt_chu_hd'];
        $data['cmt_ngay_cap_nguoi_bh'] = (!empty($data['customer_infor']['date_range'])) ? $data['customer_infor']['date_range'] : '';
        $data['cmt_noi_cap_nguoi_bh'] = (!empty($data['customer_infor']['issued_by'])) ? $data['customer_infor']['issued_by'] : '';
        $data['sdt_nguoi_bh'] = $data['sdt_chu_hd'];
        $data['email_nguoi_bh'] = $data['email_chu_hd'];
        $data['moi_quan_he'] = 'QH00000';

        $NGAY_HL = date('Ymd');
        $NGAY_KT = date('Ymd', strtotime("+1 year"));
        $nsd = $this->config->item("nsd_vbi_tnds");
        $so_id_dtac = md5(uniqid(time()));
        $raw = "nsd=$nsd&so_id_dtac=$so_id_dtac&nv=CN.9&ten=" . $data['ten_chu_hd'] . "&dia_chi=" . $data['diachi_chu_hd'] . "&ngay_sinh=" . date('Ymd', strtotime($data['ngaysinh_chu_hd'])) . "&gioi_tinh=" . $data['gioi_tinh_chu_hd'] . "&cmt=" . $data['cmt_chu_hd'];
        $key = $this->config->item("private_key_vbi");
        $signature = $this->create_signature($raw, $key);

        //nv sxh CN.9
        $param = [
            "dtac_key" => $this->config->item("VBI_CODE"),
            "nsd" => $nsd,
            "so_id_dtac" => $so_id_dtac,
            "nv" => "CN.9",
            "ten" => $data['ten_chu_hd'],
            "dchi" => $data['diachi_chu_hd'],
            "ngay_sinh" => date('Ymd', strtotime($data['ngaysinh_chu_hd'])),
            "gioi_tinh" => $data['gioi_tinh_chu_hd'],
            "cmt" => $data['cmt_chu_hd'],
            "d_thoai" => $data['sdt_chu_hd'],
            "email" => $data['email_chu_hd'],
            "mst" => "",
            "trang_thai_tt" => "D",
            "gcns" => [
                0 => [
                    "so_id_dt_dtac" => $so_id_dtac,
                    "goi_bh" => $data['goi_bao_hiem'],
                    "ten" => $data['ten_nguoi_bh'],
                    "dchi" => $data['diachi_nguoi_bh'],
                    "ngay_sinh" => date('Ymd', strtotime($data['ngaysinh_nguoi_bh'])),
                    "gioi_tinh" => $data['gioi_tinh_nguoi_bh'],
                    "cmt" => !empty($data['cmt_nguoi_bh']) ? $data['cmt_nguoi_bh'] : $data['cmt_chu_hd'],
                    "cmt_ngay_cap" => !empty($data['cmt_ngay_cap_nguoi_bh']) ? date('Ymd', strtotime($data['cmt_ngay_cap_nguoi_bh'])) : '',
                    "cmt_noi_cap" => !empty($data['cmt_noi_cap_nguoi_bh']) ? $data['cmt_noi_cap_nguoi_bh'] : '',
                    "d_thoai" => $data['sdt_nguoi_bh'],
                    "email" => $data['email_nguoi_bh'],
                    "ngay_hl" => $NGAY_HL,
                    "ngay_kt" => $NGAY_KT,
                    "moi_qh" => $data['moi_quan_he'],
                ]
            ],
            "signature" => $signature
        ];
        $vbi = new BaoHiemVbi();
        $check_vbi = $this->vbi_model->findOne(["code_contract" => $data['code_contract'], 'type' => 'VBI_SXH']);
        if (!empty($check_vbi) && ($check_vbi['status_vbi'] == "active" || $check_vbi['status_vbi'] == "delete")) {
            $result = [
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Có lỗi xảy ra, liên hệ IT để hỗ trợ!'
            ];
            return json_decode(json_encode($result));
        }
        $result = $vbi->tao_don_bh_sxh($param);
        $this->log_bh_vbi_model->insert(
            [
                'type' => 'sxh',
                'contract_id' => (string)$data['_id'],
                'code_contract' => $code_contract,
                'request' => $param,
                'response' => $result,
                'created_at' => $this->createdAt,
                'created_by' => $this->uemail
            ]
        );
        if ($result->response_code == '00') {
            $store = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($data['id_pgd'])]);
            $code = "VBI_SXH_" . date("dmY") . "_" . uniqid();
            $insert = [
                'type' => 'VBI_SXH',
                'contract_id' => (string)$data['_id'],
                'code_contract' => $code_contract,
                'code' => $code,
                'fee' => $result->tong_phi,
                "goi_bh" => $data['goi_bao_hiem'],
                'NGAY_HL' => $NGAY_HL,
                'NGAY_KT' => $NGAY_KT,
                'customer_info' => [
                    'customer_name' => $data['ten_chu_hd'],
                    'customer_phone' => $data['sdt_chu_hd'],
                    'address' => $data['diachi_chu_hd'],
                    "cmt" => $data['cmt_chu_hd'],
                    "email" => $data['email_chu_hd'],
                    'ngay_sinh' => strtotime($data['ngaysinh_chu_hd'])
                ],
                'store' => [
                    'id' => (string)$store['_id'],
                    'name' => $store['name']
                ],
                'vbi_sxh' => $result,
                'contract_info' => $data,
                'status_vbi' => 'active',
                'created_at' => $this->createdAt,
                'created_by' => $this->uemail,
                'updated_at' => $this->createdAt,
                'updated_by' => $this->uemail
            ];

            if (!empty($check_vbi)) {
                unset($insert['created_at']);
                unset($insert['created_by']);
                unset($insert['code']);
                $this->vbi_model->update(['_id' => $check_vbi['_id']], $insert);
            } else {
                $old_vbi = $this->vbi_model->findOne(["code_contract" => $data['code_contract'], 'type' => 'VBI_SXH', 'status_vbi' => ['$ne' => 'active']]);
                $this->vbi_model->update(['_id' => $old_vbi['_id']], ['status_vbi' => 'delete']);
                $this->vbi_model->insert($insert);


            }

            $result = [
                'status' => REST_Controller::HTTP_OK,
                'message' => 'Thành công!',
            ];
            return json_decode(json_encode($result));
        } else {
            $result = [
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Có lỗi xảy ra, liên hệ IT để hỗ trợ!'
            ];
            return json_decode(json_encode($result));
        }
    }

    private function create_signature($raw, $key)
    {
        $signature = hash_hmac('sha256', $raw, $key);
        return $signature;
    }

    public function validation_form_vbi($data)
    {
        if (empty($data['ten_chu_hd'])) {
            $response[] = "Tên chủ hợp đồng không để trống!";
        }
        if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{4,255}$/", $data['ten_chu_hd'])) {
            $response[] = "Tên chủ hợp đồng không chứa kí tự số hoặc kí tự đặc biệt!";
        }
        if (empty($data['email_chu_hd'])) {
            $response[] = "Email chủ hợp đồng không để trống!";
        }

        if (!filter_var($data['email_chu_hd'], FILTER_VALIDATE_EMAIL)) {
            $response[] = "Email chủ hợp đồng không đúng định dạng!";
        }

        if (empty($data['diachi_chu_hd'])) {
            $response[] = "Địa chỉ chủ hợp đồng không để trống!";
        }

        if (empty($data['ngaysinh_chu_hd'])) {
            $response[] = "Ngày sinh chủ hợp đồng không để trống!";
        }

        if (empty($data['gioi_tinh_chu_hd'])) {
            $response[] = "Giới tính chủ hợp đồng không để trống!";
        }

        if (empty($data['cmt_chu_hd'])) {
            $response[] = "CMT chủ hợp đồng không để trống!";
        }

        if (strlen($data['cmt_chu_hd']) < 9) {
            $response[] = "CMT chủ hợp đồng tối thiểu 9 kí tự!";
        }
        if (!filter_var($data['cmt_chu_hd'], FILTER_VALIDATE_FLOAT)) {
            $response[] = "CMT chủ hợp đồng phải dạng số!";
        }

        if (empty($data['sdt_chu_hd'])) {
            $response[] = "Số điện thoại chủ hợp đồng không để trống!";
        }
        if (!filter_var($data['sdt_chu_hd'], FILTER_VALIDATE_FLOAT)) {
            $response[] = "Số điện thoại chủ hợp đồng phải dạng số!";
        }
        if (strlen($data['sdt_chu_hd']) < 9) {
            $response[] = "Số điện thoại chủ hợp đồng tối thiểu 9 kí tự!";
        }

        if (empty($data['ten_nguoi_bh'])) {
            $response[] = "Tên người được bảo hiểm không để trống!";
        }
        if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{4,255}$/", $data['ten_nguoi_bh'])) {
            $response[] = "Tên người được bảo hiểm không chứa kí tự số hoặc kí tự đặc biệt!";
        }
        if (empty($data['email_nguoi_bh'])) {
            $response[] = "Email người được bảo hiểm không để trống!";
        }

        if (!filter_var($data['email_nguoi_bh'], FILTER_VALIDATE_EMAIL)) {
            $response[] = "Email người được bảo hiểm không đúng định dạng!";
        }

        if (empty($data['diachi_nguoi_bh'])) {
            $response[] = "Địa chỉ người được bảo hiểm không để trống!";
        }

        if (empty($data['ngaysinh_nguoi_bh'])) {
            $response[] = "Ngày sinh người được bảo hiểm không để trống!";
        }
        $diff = date_diff(date_create(), date_create($data['ngaysinh_nguoi_bh']));
        $age = $diff->format('%Y');
        if ($age < 18 || $age > 65) {
            $response[] = "Độ tuổi người được bảo hiểm không hợp lệ phải từ 18 đến 65 tuổi!";
        }
        if (empty($data['gioi_tinh_nguoi_bh'])) {
            $response[] = "Giới tính người được bảo hiểm không để trống!";
        }

        if (empty($data['cmt_nguoi_bh'])) {
            $response[] = "CMT người được bảo hiểm không để trống!";
        }

        if (strlen($data['cmt_nguoi_bh']) < 9) {
            $response[] = "CMT người được bảo hiểm tối thiểu 9 kí tự!";
        }
        if (!filter_var($data['cmt_nguoi_bh'], FILTER_VALIDATE_FLOAT)) {
            $response[] = "CMT người được bảo hiểm phải dạng số!";
        }

        if (empty($data['sdt_nguoi_bh'])) {
            $response[] = "Số điện thoại người được bảo hiểm không để trống!";
        }
        if (!filter_var($data['sdt_nguoi_bh'], FILTER_VALIDATE_FLOAT)) {
            $response[] = "Số điện thoại người được bảo hiểm phải dạng số!";
        }
        if (strlen($data['sdt_nguoi_bh']) < 9) {
            $response[] = "Số điện thoại người được bảo hiểm tối thiểu 9 kí tự!";
        }
        if (empty($data['goi_bao_hiem'])) {
            $response[] = "Gói bảo hiểm đang trống!";
        }
        if (empty($data['cmt_ngay_cap_nguoi_bh'])) {
            $response[] = "Ngày cấp CMT người được bảo hiểm không để trống!";
        }
        if (empty($data['cmt_noi_cap_nguoi_bh'])) {
            $response[] = "Nơi cấp CMT người được bảo hiểm không để trống!";
        }
        if (empty($data['price'])) {
            $response[] = "Số tiền không để trống";
        }

        return $response;
    }

    private function insert_mic_investor($data, $code_contract, $disbursement_date)
    {
        $NGAY_HL = date('d/m/Y');
        $number_day_loan = (!empty($data['loan_infor']['number_day_loan'])) ? (int)$data['loan_infor']['number_day_loan'] / 30 : 0;
        $so_ngay_tham_gia_bh = ($number_day_loan <= 12) ? 12 * 30 : 24 * 30;
        $NGAY_KT = date('d/m/Y', strtotime('+' . $so_ngay_tham_gia_bh . ' days'));
        $SO_HD_VAY = $code_contract;
        $DCHI = (!empty($data['current_address']['ward_name'])) ? $data['current_address']['current_stay'] . ' - ' . $data['current_address']['ward_name'] : '.....';
        $NG_HUONG = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $TEN = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $EMAIL = (!empty($data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
        $NG_SINH = (!empty($data['customer_infor']['customer_BOD'])) ? date('d/m/Y', strtotime($data['customer_infor']['customer_BOD'])) : '';
        $CMT = (!empty($data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
        $MOBI = (!empty($data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';

        $TIEN = (!empty($data['loan_infor']['amount_money'])) ? $data['loan_infor']['amount_money'] : '';
        $TTOAN = $this->get_fee_mic($TIEN, $number_day_loan);
        $investor_code = (!empty($data['investor_code'])) ? $data['investor_code'] : '';
        $investor_info = $this->investor_model->findOne(array("code" => $investor_code));
        $TEN_NGH = (!empty($investor_info['name'])) ? $investor_info['name'] : '';
        $NG_SINH_NGH = (!empty($investor_info['date_of_birth'])) ? date('d/m/Y', strtotime($investor_info['date_of_birth'] . ' 00:00:00')) : '';
        $DCHI_NGH = (!empty($investor_info['address'])) ? $investor_info['address'] : '';
        $MOBI_NGH = '0976764066';
        $EMAIL_NGH = 'mic@tienngay.vn';
        $SO_CMT_NGH = (!empty($investor_info['dentity_card'])) ? $investor_info['dentity_card'] : '';
        $LXUAT = (!empty($investor_info['percent_interest_investor'])) ? $investor_info['percent_interest_investor'] : '';
        $TEN_NGV = $TEN;
        $EMAIL_NGV = $EMAIL;
        $DCHI_NGV = $DCHI;
        $SO_CMT_NGV = $CMT;
        $MOBI_NGV = $MOBI;
        $NG_SINH_NGV = $NG_SINH;
        $originalXML = '<ns1:ws_GCN_TRA>
                     <!--Optional:-->
                     <ns1:xmlinput>
                     <![CDATA[
                    <XMLINPUT>
                    <MA_DVI>' . $this->config->item("MIC_MA_DVI") . '</MA_DVI>
                    <NSD>' . $this->config->item("MIC_NSD") . '</NSD>
                    <PAS>' . $this->config->item("MIC_PAS") . '</PAS>
                    <NV>NG_DT</NV>
                    <TTOAN>' . (float)$TTOAN . '</TTOAN>
                    <TEN>' . $TEN_NGH . '</TEN>
                    <KIEU_HD>G</KIEU_HD>
                    <LKH>C</LKH>
                    <NG_SINH>' . $NG_SINH_NGH . '</NG_SINH>
                    <CMT>' . $SO_CMT_NGH . '</CMT>
                    <MOBI>' . $MOBI_NGH . '</MOBI>
                    <EMAIL>' . $EMAIL_NGH . '</EMAIL>
                    <DCHI>' . $DCHI_NGH . '</DCHI>
                    <NG_HUONG>' . $TEN_NGH . '</NG_HUONG>
                    <SO_HDL>E</SO_HDL>
                    <SO_HD_VAY>' . $SO_HD_VAY . '</SO_HD_VAY>
                    <ID_TRAS>' . $SO_HD_VAY . '</ID_TRAS>
                    <GUIHD>K</GUIHD>
                    <KIEUHD>E</KIEUHD>
                    <NG_TRA>D</NG_TRA>
                    <NGAY_HL>' . $NGAY_HL . '</NGAY_HL>
                    <NGAY_KT>' . $NGAY_KT . '</NGAY_KT>
                    <TIEN>' . (float)$TIEN . '</TIEN>
                    <TIEN_DT>' . (float)$TIEN . '</TIEN_DT>
                    <TEN_NGH>' . $TEN_NGH . '</TEN_NGH>
                    <NG_SINH_NGH>' . $NG_SINH_NGH . '</NG_SINH_NGH>
                    <DCHI_NGH>' . $DCHI_NGH . '</DCHI_NGH>
                    <MOBI_NGH>' . $MOBI_NGH . '</MOBI_NGH >
                    <EMAIL_NGH>' . $EMAIL_NGH . '</EMAIL_NGH>
                    <SO_CMT_NGH>' . $SO_CMT_NGH . '</SO_CMT_NGH>
                    <LXUAT>' . $LXUAT . '</LXUAT>
                    <TEN_NGV>' . $TEN_NGV . '</TEN_NGV>
                    <NG_SINH_NGV>' . $NG_SINH_NGV . '</NG_SINH_NGV>
                    <SO_CMT_NGV>' . $SO_CMT_NGV . '</SO_CMT_NGV>
                    <MOBI_NGV>' . $MOBI_NGV . '</MOBI_NGV>
                    <EMAIL_NGV>' . $EMAIL_NGV . '</EMAIL_NGV>
                    <DCHI_NGV>' . $DCHI_NGV . '</DCHI_NGV>
                    </XMLINPUT>
            ]]>  </ns1:xmlinput>
                  </ns1:ws_GCN_TRA>
            ';

        $wsdl_url = $this->config->item("API_MIC") . '?wsdl';
        try {
            $params = new \SoapVar($originalXML, XSD_ANYXML);
            // var_dump($params ); die;
            $this->soapClient = new \SoapClient($wsdl_url, array('soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => false));
            $this->soapClient->__setLocation($this->config->item("API_MIC"));
            $result = $this->soapClient->ws_GCN_TRA($params);
            // var_dump($result); die;
            $xml = simplexml_load_string($result->ws_GCN_TRAResult);
            $this->log_mic($originalXML, $xml, $SO_HD_VAY, 'MIC_TDT');
            if ($xml->STATUS == "TRUE") {
                $response = [
                    'res' => true,
                    'status' => "200",
                    'data' => $xml,
                    "investor_info" => $investor_info,
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
                    "investor_info" => $investor_info,
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
                "investor_info" => $investor_info,
                'response' => $e->getMessage(),
                'NGAY_HL' => $NGAY_HL,
                'NGAY_KT' => $NGAY_KT
            ];
            return json_decode(json_encode($response));


        }


    }

    private function get_fee_mic($money = 0, $month = 0)
    {

        $ngay_hl = date('d/m/Y');
        $ngay_kt = date('d/m/Y', strtotime('+' . $month . ' month'));
        $originalXML = '<ns1:ws_BPHI>
                     <!--Optional:-->
                     <ns1:xmlinput>
                     <![CDATA[
                     <XMLINPUT>
            <MA_DVI>' . $this->config->item("MIC_MA_DVI") . '</MA_DVI>
            <NSD>' . $this->config->item("MIC_NSD") . '</NSD>
            <PAS>' . $this->config->item("MIC_PAS") . '</PAS>
            <NV>NG_DT</NV>
            <KIEU_HD>G</KIEU_HD>
            <NGAY_HL>' . $ngay_hl . '</NGAY_HL>
            <NGAY_KT>' . $ngay_kt . '</NGAY_KT>
            <TIEN>' . (float)$money . '</TIEN>
            </XMLINPUT>
            ]]>  </ns1:xmlinput>
                  </ns1:ws_BPHI>
            ';

        $wsdl_url = $this->config->item("API_MIC") . '?wsdl';
        try {
            $params = new \SoapVar($originalXML, XSD_ANYXML);
            // var_dump($params ); die;
            $this->soapClient = new \SoapClient($wsdl_url, array('soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => false));
            $this->soapClient->__setLocation($this->config->item("API_MIC"));
            $result = $this->soapClient->ws_BPHI($params);

            $xml = simplexml_load_string($result->ws_BPHIResult);
            return (string)$xml->PHI;

        } catch (Exception $e) {
            return 0;

        }

    }

    public function insert_pti_vta($data, $NGAY_HL, $code)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $fullname = (!empty($data['customer_infor']['customer_name'])) ? $data['customer_infor']['customer_name'] : '';
        $birthday = (!empty($data['customer_infor']['customer_BOD'])) ? $data['customer_infor']['customer_BOD'] : '';
        $cmt = (!empty($data['customer_infor']['customer_identify'])) ? $data['customer_infor']['customer_identify'] : '';
        $email = (!empty($data['customer_infor']['customer_email'])) ? $data['customer_infor']['customer_email'] : '';
        $phone = (!empty($data['customer_infor']['customer_phone_number'])) ? $data['customer_infor']['customer_phone_number'] : '';
        $address = (!empty($data['current_address'])) ? $data['current_address']['ward_name'] . ' - ' . $data['current_address']['district_name'] . ' - ' . $data['current_address']['province_name'] : '';
        $relationship = 'BT';
        $btendn = $fullname;
        $bdiachidn = $address;
        $bemaildn = $email;
        $bphonedn = $phone;
        $bmathue = $cmt;
        $sel_ql = !empty($data['loan_infor']['bao_hiem_pti_vta']['code_pti_vta']) ? $data['loan_infor']['bao_hiem_pti_vta']['code_pti_vta'] : '';
        $sel_year = !empty($data['loan_infor']['bao_hiem_pti_vta']['year_pti_vta']) ? $data['loan_infor']['bao_hiem_pti_vta']['year_pti_vta'] : '';
        $price = !empty($data['loan_infor']['bao_hiem_pti_vta']['price_pti_vta']) ? $data['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] : 0;
        $code_fee = !empty($data['loan_infor']['bao_hiem_pti_vta']['code_fee']) ? $data['loan_infor']['bao_hiem_pti_vta']['code_fee'] : 0;

        $ngay_kt_old = $this->pti_vta_bn_model->findNgayKTByCCCD($cmt);

        if ($ngay_kt_old && strtotime($ngay_kt_old) > strtotime($NGAY_HL)) {
            $NgayHieuLucBaoHiem = date('d-m-Y', strtotime($ngay_kt_old . ' + 1 day'));
        } else {
            $NgayHieuLucBaoHiem = $NGAY_HL;
        }
        if ($sel_year == "1Y") {
            $so_thang_bh = 12;
        } else if ($sel_year == "6M") {
            $so_thang_bh = 6;
        } else if ($sel_year == "3M") {
            $so_thang_bh = 3;
        }

        $NgayHieuLucBaoHiemDen = date('d-m-Y', strtotime($NgayHieuLucBaoHiem . ' + ' . $so_thang_bh . ' month'));

        $number_item = $this->initNumberItem_pti_vta();

        //$so_hd = 'TN' . str_pad((string)$number_item, 7, '0', STR_PAD_LEFT) . '/041/CN.1.14/' . date('Y');

        $ten = $fullname;
        $ngay_sinh = $birthday;
        $email = $email;
        $phone = $phone;
        $so_cmt = $cmt;

        $goi = "";
        if ($sel_ql == "G1") {
            $goi = "GOI1";
        } else if ($sel_ql == "G2") {
            $goi = "GOI2";
        } else if ($sel_ql == "G3") {
            $goi = "GOI3";
        }

        $dt_pti = array(
        //'so_hd' => $so_hd
         'btendn' => $btendn
        , 'bdiachidn' => $bdiachidn
        , 'bemaildn' => $bemaildn
        , 'bphonedn' => $bphonedn
        , 'bmathue' => $bmathue
        , 'quan_he' => $relationship
        , 'ten' => $ten
        , 'ngay_sinh' => date('d-m-Y', strtotime($birthday))
        , 'so_cmt' => $so_cmt
        , 'email' => $email
        , 'phone' => $phone
        , 'phi_bh' => $price
        , 'so_thang_bh' => $so_thang_bh
        , 'ngay_hl' => $NgayHieuLucBaoHiem
        , 'ngay_kt' => $NgayHieuLucBaoHiemDen
        , 'goi' => $goi
        , 'gioi' => ($data['customer_infor']['customer_gender'] == 1) ? "NAM" : 'NU'
        , 'code_contract' => $code
        );
        // return  $province;
        $message = '';
        $baohiem = new BaoHiemPTI();
        $res = $baohiem->call_api($dt_pti);
        $this->log_pti(json_encode($dt_pti), $res, 'HD', $code);
        log_message('info', 'PTI response0 ' . json_encode($pti));
        if (!empty($res)) {
            if ($res['status'] == 200) {
                log_message('info', 'PTI response1 ' . json_encode($pti));
                $dt_pti['ma_goi_bh_ap_dung'] = $code_fee;
                $dt_pti['so_hd'] = $res["data"]["so_hd"];
                $dt_re = array(
                    'message' => 'Thành công',
                    'data' => $res["data"],
                    'number_item' => $number_item,
                    'success' => true,
                    'request' => (object)$dt_pti,
                    'NGAY_KT' => $NgayHieuLucBaoHiemDen,
                    'NGAY_HL' => $NgayHieuLucBaoHiem
                );
                return (object)$dt_re;

            } else {
                $dt_re = array(
                    'message' => 'Không thành công',
                    'success' => false
                );
                return (object)$dt_re;
            }
        } else {

            $dt_re = array(
                'message' => "Kết nối đến PTI bị lỗi !",
                'success' => false
            );
            return (object)$dt_re;
        }

    }

    private function initNumberItem_pti_vta()
    {
        $maxNumber = $this->pti_vta_bn_model->getMaxNumberItem();
        $maxNumberContract = !empty($maxNumber[0]['number_item']) ? (float)$maxNumber[0]['number_item'] + 1 : 1;

        return $maxNumberContract;
    }

    public function log_pti($request, $data, $code, $type)
    {

        $dataInser = array(
            "type" => $type,
            "code" => $code,
            "res_data" => $data,
            "request_data" => $request,
            "created_at" => $this->createdAt
        );
        $this->log_pti_model->insert($dataInser);
    }

    private function push_api_gci($action = '', $get = '', $data_post = [])
    {
        $url_gic = $this->config->item("url_gic");
        $accessKey = $this->config->item("access_key_gic");
        $service = $url_gic . '/api/PublicApi/' . $action . '?accessKey=' . $accessKey . $get;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result1 = json_decode($result);
        return $result1;
    }

    public function process_save_contract_post()
    {

        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $groupRoles = $this->getGroupRole($this->id);
        // if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
        //  // Check access right
        //  if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
        //      $response = array(
        //          'status' => REST_Controller::HTTP_UNAUTHORIZED,
        //          'data' => array(
        //              "message" => "No have access right"
        //          )
        //      );
        //      $this->set_response($response, REST_Controller::HTTP_OK);
        //      return;
        //  }
        // }

        $this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
        $this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
        $this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
        $this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
        $this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
        $this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
        $this->dataPost['property_infor'] = $this->security->xss_clean(!empty($this->dataPost['property_infor']) ? $this->dataPost['property_infor'] : array());
        $this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
        $this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
        $this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
        $this->dataPost['step'] = $this->security->xss_clean($this->dataPost['step']);

        // $lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
        // if (isset($lead[0]['_id'])) {
        //  $current_day = strtotime(date('m/d/Y'));
        //  $datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
        //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
        //  $last = 3 - $time;
        //  if ($time <= 8) {
        //      $this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
        //      $this->dataPost['customer_infor']['customer_resources'] = "hoiso";
        //  }
        // }
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
        //$this->dataPost['image_accurecy'] = $arrImages;
        $contract = $this->dataPost;
        $typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
        $typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
        $number_day_loan = !empty($contract['loan_infor']['number_day_loan']) ? $contract['loan_infor']['number_day_loan'] : "";
        if ($typeLoan != "" && $typeProperty != "" && $number_day_loan != "") {
            $arrFee = $this->getFee($this->dataPost);
            if (empty($arrFee['fee'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Không lấy được biểu phí.",
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            $this->dataPost['fee'] = $arrFee['fee'];
            $this->dataPost['fee_id'] = $arrFee['id'];
        }
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
        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => "save",
            "contract_id" => (string)$contractId,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;

        $this->insert_log_file($insertLog, $this->dataPost['id']);

        /**
         * ----------------------
         */

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create new contract success",
            "data" => $this->dataPost
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function isValidEmail($email)
    {
        $email = strtolower($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL)
            && preg_match('/@.+\./', $email);
    }

    function validateAge($birthday, $from = 18, $to = 59)
    {
        $today = new DateTime(date("Y-m-d"));
        $bday = new DateTime($birthday);
        $interval = $today->diff($bday);
        if (intval($interval->y) >= $from && intval($interval->y) <= $to) {
            return 'TRUE';
        } else {
            return 'FALSE';
        }
    }

    public function process_create_contract_post()
    {
//      $flag = notify_token($this->flag_login);
//      if ($flag == false) return;

        // $groupRoles = $this->getGroupRole($this->id);
        // if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
        //  // Check access right
        //  if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
        //      $response = array(
        //          'status' => REST_Controller::HTTP_UNAUTHORIZED,
        //          'data' => array(
        //              "message" => "No have access right"
        //          )
        //      );
        //      $this->set_response($response, REST_Controller::HTTP_OK);
        //      return;
        //  }
        // }
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

        //$this->dataPost['image_accurecy'] = $this->security->xss_clean($this->dataPost['image_accurecy']);

        //Thông tin khách hàng
        if (empty($this->dataPost['customer_infor']['customer_name'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tên khách hàng không thể để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['customer_infor']['customer_email'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Email không thể để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!$this->isValidEmail($this->dataPost['customer_infor']['customer_email'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Email không đúng định dạng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!preg_match("/^[0-9]{10}$/", $this->dataPost['customer_infor']['customer_phone_number'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại không đúng định dạng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if (!empty($this->dataPost['customer_infor']['customer_phone_number'])){

            $ctv_code = $this->lead_model->findOne(["phone_number" => $this->dataPost['customer_infor']['customer_phone_number'] ]);

            if (!empty($ctv_code)){
                $this->dataPost['ctv_code'] = (string)$ctv_code['ctv_code'];
                $this->dataPost['customer_infor']['id_lead'] = (string)$ctv_code['_id'];
            }

        }

        if (!preg_match("/^[0-9]{9,12}$/", $this->dataPost['customer_infor']['customer_identify'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "CMND/CCCD hiện tại không đúng định dạng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['customer_infor']['date_range'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Ngày cấp không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['customer_infor']['issued_by'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Nơi cấp không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['customer_infor']['customer_BOD'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Ngày sinh không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!empty($this->dataPost['customer_infor']['customer_identify_old'])) {
            if (!preg_match("/^[0-9]{9,12}$/", $this->dataPost['customer_infor']['customer_identify_old'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "CMND/CCCD cũ không đúng định dạng"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        if (empty($this->dataPost['customer_infor']['img_id_front'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Ảnh không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['customer_infor']['img_id_back'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Ảnh không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['customer_infor']['img_portrait'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Ảnh không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['current_address']['province'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tỉnh, thành phố không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if (empty($this->dataPost['current_address']['district'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Quận, huyện không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['current_address']['ward'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Phường, xã không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['current_address']['current_stay'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Xóm, tổ không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['current_address']['time_life'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thời gian sinh sống không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['houseHold_address']['province'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tỉnh, thành phố không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['houseHold_address']['district'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Quận, huyện phố không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['houseHold_address']['ward'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Phường, xã không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['houseHold_address']['address_household'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thôn, xóm không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        // Thông tin việc làm
        if (empty($this->dataPost['job_infor']['name_company'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tên công ty không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['job_infor']['phone_number_company'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['job_infor']['address_company'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Địa chỉ không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['job_infor']['salary'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thu nhập không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['job_infor']['receive_salary_via'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hình thức nhận lương không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['job_infor']['job_position'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Vị trí không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['job_infor']['job'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Nghề nghiệp không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        // Thông tin người thân
        if (empty($this->dataPost['relative_infor']['type_relative_1'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Mối quan hệ không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['fullname_relative_1'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tên người tham chiếu không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['phone_number_relative_1'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!preg_match("/^[0-9]{10}$/", $this->dataPost['relative_infor']['phone_number_relative_1'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại không đúng định dạng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['hoursehold_relative_1'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Địa chỉ cư trú không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['confirm_relativeInfor_1'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Phản hồi không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['type_relative_2'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Mối quan hệ không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['fullname_relative_2'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tên người tham chiếu không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['phone_number_relative_2'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!preg_match("/^[0-9]{10}$/", $this->dataPost['relative_infor']['phone_number_relative_2'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại không đúng định dạng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['hoursehold_relative_2'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Địa chỉ cư trú không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['relative_infor']['confirm_relativeInfor_2'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Phản hồi không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!empty($this->dataPost['relative_infor']['phone_number_relative_3'])) {
            if (!preg_match("/^[0-9]{10}$/", $this->dataPost['relative_infor']['phone_number_relative_3'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Số điện thoại không đúng định dạng"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }

        //Thông tin khoản vay

        if (empty($this->dataPost['loan_infor']['type_property'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Loại tài sản không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['loan_infor']['loan_product']['code'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Sản phẩm vay không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['loan_infor']['name_property'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tên tài sản không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['loan_infor']['amount_money'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số tiền vay không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['loan_infor']['loan_purpose'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Mục đích vay không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['loan_infor']['number_day_loan'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thời gian vay không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['loan_infor']['type_interest'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hình thức trả lãi không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['loan_infor']['price_property'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tổng giá trị tài sản không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if ($this->dataPost['loan_infor']['insurrance_contract'] == 1 && empty($this->dataPost['loan_infor']['loan_insurance'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Bảo hiểm khoản vay không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if ($this->dataPost['loan_infor']['insurrance_contract'] == 1 && !empty($this->dataPost['loan_infor']['loan_insurance'])) {
            if ($this->dataPost['loan_infor']['loan_insurance'] == 1 && $this->dataPost['loan_infor']['amount_GIC'] > 0) {
                if ($this->validateAge($this->dataPost['customer_infor']['customer_BOD'], 18, 64) == "FALSE" && $this->dataPost['loan_infor']['insurrance_contract'] == 1) {
                    $response = array(
                        'status' => REST_Controller::HTTP_UNAUTHORIZED,
                        'message' => "Khách hàng đăng ký bảo hiểm khoản vay GIC phải lớn hơn 18 tuổi và <= 65 tuổi"
                    );
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                }
            }
            if ($this->dataPost['loan_infor']['loan_insurance'] == 2 && $this->dataPost['loan_infor']['amount_MIC'] > 0) {
                if ($this->validateAge($this->dataPost['customer_infor']['customer_BOD'], 18, 59) == "FALSE" && $this->dataPost['loan_infor']['insurrance_contract'] == 1) {
                    $response = array(
                        'status' => REST_Controller::HTTP_UNAUTHORIZED,
                        'message' => "Khách hàng đăng ký bảo hiểm khoản vay MIC phải lớn hơn 18 tuổi và <= 60 tuổi"
                    );
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                }
            }
            if ($this->dataPost['loan_infor']['amount_GIC_plt'] > 0) {
                if ($this->validateAge($this->dataPost['customer_infor']['customer_BOD'], 18, 59) == "FALSE") {
                    $response = array(
                        'status' => REST_Controller::HTTP_UNAUTHORIZED,
                        'message' => "Độ tuổi áp dụng bảo hiểm phúc lộc thọ từ 18 đến 60 tuổi"
                    );
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                }

            }
            if ($this->dataPost['loan_infor']['loan_insurance'] == 1 && $this->dataPost['loan_infor']['amount_GIC'] <= 0) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Điền đầy đủ mục thông tin khoản vay"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;

            }
            if ($this->dataPost['loan_infor']['loan_insurance'] == 2 && $this->dataPost['loan_infor']['amount_MIC'] <= 0) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Điền đầy đủ mục thông tin khoản vay"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }


            //Check null mục Thông tin tài sản

            if (!empty($this->dataPost['property_infor'])) {
                foreach ($this->dataPost['property_infor'] as $item) {
                    if (empty($item['value'])) {
                        $response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            'message' => "Điền đầy đủ mục thông tin tài sản"
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    }
                    if (!empty($item['value']) && $item['slug'] == 'so-khung' && strlen($item['value']) < 6) {
                        $response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            'message' => "Số khung không được nhỏ hơn 7 ký tự"
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    }
                    if (!empty($item['value']) && $item['slug'] == 'so-may' && strlen($item['value']) < 6) {
                        $response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            'message' => "Số máy không được nhỏ hơn 7 ký tự"
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    }
                    if (!empty($item['value']) && $item['slug'] == 'bien-so-xe' && strlen($item['value']) < 7) {
                        $response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            'message' => "Biển số xe không được nhỏ hơn 7 ký tự"
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    }
                    if ($item['slug'] == 'bien-so-xe') {
                        $check = $this->checkProperty($item['value']);
                        if (!empty($check)) {
                            $response = array(
                                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                                'message' => "Hợp đồng đang vay đã tồn tại biển số xe"
                            );
                            $this->set_response($response, REST_Controller::HTTP_OK);
                            return;
                        }
                    }
                    if ($item['slug'] == 'ngay-cap') {
                        if (strtotime($item['value']) > $this->createdAt) {
                            $response = array(
                                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                                'message' => "Ngày cấp đăng kí xe không hợp lệ!"
                            );
                            $this->set_response($response, REST_Controller::HTTP_OK);
                            return;
                        }
                    }
                    if ($item['slug'] == 'so-dang-ky') {
                        if (!preg_match("/^[0-9]{6,7}$/", $item['value'])) {
                            $response = array(
                                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                                'message' => "Số đăng ký xe không hợp lệ!"
                            );
                            $this->set_response($response, REST_Controller::HTTP_OK);
                            return;
                        }
                    }
                }
            } else {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Điền đầy đủ mục thông tin tài sản"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }


        if (empty($this->dataPost['receiver_infor']) || empty($this->dataPost['receiver_infor']['type_payout'])
            || empty($this->dataPost['receiver_infor']['amount'])
            || empty($this->dataPost['receiver_infor']['bank_id'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                "message" => "Điền đầy đủ thông tin tài khoản"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if (!empty($this->dataPost['receiver_infor']['type_payout'])) {
            if ($this->dataPost['receiver_infor']['type_payout'] == 2 && (empty($this->dataPost['receiver_infor']['bank_account']) || empty($this->dataPost['receiver_infor']['bank_account_holder']) || empty($this->dataPost['receiver_infor']['bank_branch']))) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    "message" => "Điền đầy đủ thông tin tài khoản"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if ($this->dataPost['receiver_infor']['type_payout'] == 3 && (empty($this->dataPost['receiver_infor']['atm_card_number']) || empty($this->dataPost['receiver_infor']['atm_card_holder']))) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    "message" => "Điền đầy đủ thông tin tài khoản"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }

        //Check null mục thông tin phong giao dich
        if (empty($this->dataPost['store']) || empty($this->dataPost['store']['id'])
            || empty($this->dataPost['store']['name'])
            || empty($this->dataPost['store']['address'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                "message" => "Điền đầy đủ mục thông tin phòng giao dịch"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }


        //Thông tin thẩm định
        if (empty($this->dataPost['expertise_infor']['expertise_file'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thông tin thẩm định không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($this->dataPost['expertise_infor']['expertise_field'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thẩm định thực địa không được để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }


//      //Check null
//      $checkNull = $this->checkNull();
//      if ($checkNull['status'] != 1) {
//          $response = array(
//              'status' => REST_Controller::HTTP_UNAUTHORIZED,
//              // 'message' => $checkNull['message'],
//              'data' => $checkNull
//          );
//          $this->set_response($response, REST_Controller::HTTP_OK);
//          return;
//      }


        // $lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
        // if (isset($lead[0]['_id'])) {
        //  $current_day = strtotime(date('m/d/Y'));
        //  $datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
        //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
        //  $last = 3 - $time;
        //  if ($time <= 8) {
        //      $this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
        //      $this->dataPost['customer_infor']['customer_resources'] = "hoiso";
        //  }
        // }

        //If new customer then create account for customer
//      $sendEmail = $this->checkSendEmailForNewCustomer();
//
//      if ($sendEmail['status'] != 1) {
//          $response = array(
//              'status' => REST_Controller::HTTP_UNAUTHORIZED,
//              'message' => $sendEmail['message'],
//              'data' => $sendEmail['data']
//          );
//          $this->set_response($response, REST_Controller::HTTP_OK);
//          return;
//      }

        //Init mã hợp đồng
        $typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";
        $loanProduct = !empty($this->dataPost['loan_infor']['loan_product']['code']) ? $this->dataPost['loan_infor']['loan_product']['code'] : "";
        $loanProductText = !empty($this->dataPost['loan_infor']['loan_product']['text']) ? $this->dataPost['loan_infor']['loan_product']['text'] : "";

        $resCodeContract = $this->initAutoCodeContract($this->dataPost['store']['id'], $typeProperty, $typeLoan, $loanProduct, $loanProductText, $this->dataPost['customer_infor']['customer_phone_number']);
        $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
        $this->dataPost['receiver_infor']['order_code'] = $resCodeContract['code_contract'];

        //Init code_contract_number hợp đồng
        $resNumberCodeContract = $this->initNumberContractCode();
        //Insert contract model
        $this->dataPost['code_contract'] = "00000" . $resNumberCodeContract['max_number_contract'];
        $this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
        $this->dataPost['status'] = 1;
        $this->dataPost['store']['object_id'] = new MongoDB\BSON\ObjectId($this->dataPost['store']['id']);

        if (empty($this->dataPost['image_accurecy'])) {
            $arrImages = array(
                "identify" => "",
                "household" => "",
                "driver_license" => "",
                "vehicle" => "",
                "expertise" => ""
            );
            $this->dataPost['image_accurecy'] = $arrImages;
        }


        $arrFee = $this->getFee($this->dataPost);
        if (empty($arrFee['fee'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không lấy được biểu phí.",
                'arrFee' => $arrFee
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->dataPost['fee'] = $arrFee['fee'];
        $this->dataPost['fee_id'] = $arrFee['id'];
        $this->dataPost['created_at'] = $this->createdAt;

        // init ma tai san
//      $asset_id = $this->get_asset_manager($this->dataPost['customer_infor']['customer_name'], $this->dataPost['property_infor'], $this->dataPost['loan_infor'], $this->dataPost['image_accurecy']);
//      $asset = $this->asset_management_model->findOne(['_id' => $asset_id]);
//      $this->dataPost['asset_code'] = $asset['asset_code'];
        $contractId = $this->contract_model->insertReturnId($this->dataPost);
        if (isset($this->dataPost['code_contract'])) {
            $this->ap_dung_coupon_giam_bhkv($this->dataPost['code_contract']);
        }

        /**
        *
        * VPBank assign vitual account number for contract
        */
        if (isset($this->dataPost['code_contract'])) {
            $vpbank = new VPBank();
            $assignVan = $vpbank->assignVan($this->dataPost['code_contract']);
        }

        //udate dashboard
        //$this->process_update_dashboard($contractId);
        //Insert log
        $insertLog = array(
            "type" => "contract",
            "action" => "create",
            "contract_id" => (string)$contractId,
            "old" => $this->dataPost,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => "create",
            "contract_id" => (string)$contractId,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;

        $this->insert_log_file($insertLog, (string)$contractId);

        /**
         * ----------------------
         */

        //update noti and firebase
//      $note = "new";
//      $status = 1;
//      $this->push_noti_app($status, $note, $contractId, $this->dataPost['customer_infor']['customer_phone_number'], $this->dataPost['code_contract']);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create new contract success"

        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function get_count_notification_user($user_id)
    {
        $condition = [];
        $condition['user_id'] = (string)$user_id;
        $condition['status'] = 1;
        $unRead = $this->notification_app_model->get_count_notification_user($condition);
        return $unRead;
    }

    public function client_process_create_contract_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
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
        if ($checkNull['status'] != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                // 'message' => $checkNull['message'],
                'data' => $checkNull
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        // $lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
        // if (isset($lead[0]['_id'])) {
        //  $current_day = strtotime(date('m/d/Y'));
        //  $datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
        //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
        //  $last = 3 - $time;
        //  if ($time <= 8) {
        //      $this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
        //      $this->dataPost['customer_infor']['customer_resources'] = "hoiso";
        //  }
        // }

        //If new customer then create account for customer
        $sendEmail = $this->checkSendEmailForNewCustomer();

        if ($sendEmail['status'] != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => $sendEmail['message'],
                'data' => $sendEmail['data']
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
        $this->dataPost['code_contract'] = "00000" . $resNumberCodeContract['max_number_contract'];
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
        //$this->dataPost['image_accurecy'] = $arrImages;
        $arrFee = $this->getFee($this->dataPost);
        if (empty($arrFee['fee'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không lấy được biểu phí.",
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->dataPost['fee'] = $arrFee['fee'];
        $this->dataPost['fee_id'] = $arrFee['id'];
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


    public function process_create_contract_import_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        // $groupRoles = $this->getGroupRole($this->id);
        // if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
        //  // Check access right
        //  if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
        //      $response = array(
        //          'status' => REST_Controller::HTTP_UNAUTHORIZED,
        //          'data' => array(
        //              "message" => "No have access right"
        //          )
        //      );
        //      $this->set_response($response, REST_Controller::HTTP_OK);
        //      return;
        //  }
        // }
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
        $this->dataPost['fee'] = $this->security->xss_clean($this->dataPost['fee']);
        $this->dataPost['status'] = (int)$this->security->xss_clean($this->dataPost['status']);
        $this->dataPost['type'] = "old_contract";
        $this->dataPost['status_disbursement'] = 2;
        $this->dataPost['created_by'] = $this->security->xss_clean($this->dataPost['created_by']);
        //Check null
        // $lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
        // if (isset($lead[0]['_id'])) {
        //  $current_day = strtotime(date('m/d/Y'));
        //  $datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
        //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
        //  $last = 3 - $time;
        //  if ($time <= 8) {
        //      $this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
        //      $this->dataPost['customer_infor']['customer_resources'] = "hoiso";
        //  }
        // }

        //If new customer then create account for customer
        //Init mã hợp đồng
        $typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";

        //Init code_contract_number hợp đồng
        $resNumberCodeContract = $this->initNumberContractCode();
        //Insert contract model
        $this->dataPost['code_contract'] = "00000" . $resNumberCodeContract['max_number_contract'];
        $this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
        $this->dataPost['store']['object_id'] = new MongoDB\BSON\ObjectId($this->dataPost['store']['id']);
        $code_contract_parent = $this->security->xss_clean($this->dataPost['code_contract_parent']);
        if (!empty($code_contract_parent)) {
            $this->dataPost['code_contract_parent'] = $code_contract_parent;
            $this->dataPost['count_extension'] = (int)$this->contract_model->count_in('code_contract_parent', array($code_contract_parent)) + 1;
        }
        $arrImages = array(
            "identify" => "",
            "household" => "",
            "driver_license" => "",
            "vehicle" => "",
            "expertise" => ""
        );
        $this->dataPost['image_accurecy'] = $arrImages;
//      $arrFee = $this->getFee();
//      $this->dataPost['fee'] = $arrFee;
        $this->dataPost['created_at'] = (int)$this->dataPost['created_at'];
        $this->dataPost['disbursement_date'] = (int)$this->dataPost['disbursement_date'];
        $this->dataPost['updated_at'] = (int)$this->dataPost['updated_at'];


        //udate dashboard
        //$dashboard = $this->process_update_dashboard($contractId, "import");
        $contractData = $this->contract_model->findOne(array("code_contract_disbursement" => trim($this->dataPost['code_contract_disbursement'])));
        if (empty($contractData)) {
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
        } else {

            if ($contractData['type'] != "old_contract") {
                unset($this->dataPost['loan_infor']);
                unset($this->dataPost['image_accurecy']);
                unset($this->dataPost['fee']);
                unset($this->dataPost['status']);
                unset($this->dataPost['number_contract']);
                unset($this->dataPost['store']);
                unset($this->dataPost['code_contract_parent']);
                unset($this->dataPost['count_extension']);
                unset($this->dataPost['type']);
                unset($this->dataPost['status_disbursement']);
                unset($this->dataPost['created_by']);
                unset($this->dataPost['disbursement_date']);
                unset($this->dataPost['created_at']);
                unset($this->dataPost['receiver_infor']);
                unset($this->dataPost['expertise_infor']);
                unset($this->dataPost['investor_code']);
            }
            unset($this->dataPost['code_contract']);
            $this->contract_model->update(array('_id' => $contractData['_id']), $this->dataPost);
            //Insert log
            $insertLog = array(
                "type" => "contract",
                "action" => "update_import",
                "contract_id" => (string)$contractId,
                "old" => $this->dataPost,
                "created_at" => $this->createdAt,
                "created_by" => $this->dataPost['created_by']
            );

            $this->log_model->insert($insertLog);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create new contract success",
            'db' => $this->dataPost['code_contract_disbursement'],
            //'dashboard' => $contractData
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }


    public function process_create_contract_fake_import_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        // $groupRoles = $this->getGroupRole($this->id);
        // if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
        //  // Check access right
        //  if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
        //      $response = array(
        //          'status' => REST_Controller::HTTP_UNAUTHORIZED,
        //          'data' => array(
        //              "message" => "No have access right"
        //          )
        //      );
        //      $this->set_response($response, REST_Controller::HTTP_OK);
        //      return;
        //  }
        // }
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
        $this->dataPost['fee'] = $this->security->xss_clean($this->dataPost['fee']);
        $this->dataPost['status'] = (int)$this->security->xss_clean($this->dataPost['status']);
        $this->dataPost['type'] = "old_contract";
        $this->dataPost['status_disbursement'] = 3;
        $this->dataPost['created_by'] = $this->security->xss_clean($this->dataPost['created_by']);

        //Check null
        // $lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
        // if (isset($lead[0]['_id'])) {
        //  $current_day = strtotime(date('m/d/Y'));
        //  $datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
        //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
        //  $last = 3 - $time;
        //  if ($time <= 8) {
        //      $this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
        //      $this->dataPost['customer_infor']['customer_resources'] = "hoiso";
        //  }
        // }

        //If new customer then create account for customer
        //Init mã hợp đồng
        $typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";

        //Init code_contract_number hợp đồng
        $resNumberCodeContract = $this->initNumberContractCode();
        //Insert contract model
        $this->dataPost['code_contract'] = "00000" . $resNumberCodeContract['max_number_contract'];
        $this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
        $this->dataPost['store']['object_id'] = new MongoDB\BSON\ObjectId($this->dataPost['store']['id']);
        $code_contract_parent = $this->security->xss_clean($this->dataPost['code_contract_parent']);
        if (!empty($code_contract_parent)) {
            $this->dataPost['code_contract_parent'] = $code_contract_parent;
            $this->dataPost['count_extension'] = (int)$this->contract_model->count_in('code_contract_parent', array($code_contract_parent)) + 1;
        }
        $arrImages = array(
            "identify" => "",
            "household" => "",
            "driver_license" => "",
            "vehicle" => "",
            "expertise" => ""
        );
        $this->dataPost['image_accurecy'] = $arrImages;
//      $arrFee = $this->getFee();
//      $this->dataPost['fee'] = $arrFee;
        $this->dataPost['created_at'] = $this->createdAt;
        $contractId = $this->contract_model->insertReturnId($this->dataPost);

        //udate dashboard
//      $dashboard = $this->process_update_dashboard($contractId, "import");

        //Insert log
        $insertLog = array(
            "type" => "contract",
            "action" => "create",
            "contract_id" => (string)$contractId,
            "old" => $this->dataPost,
            "created_at" => $this->createdAt,
            "created_by" => $this->dataPost['created_by']
        );
//      $this->spreadsheetFeeLoan(
//          $this->dataPost['code_contract'],
//          $this->security->xss_clean($this->dataPost['customer_infor']),
//          $this->security->xss_clean($this->dataPost['investor_code']),
//          $this->security->xss_clean($this->dataPost['disbursement_date']),
//          (int)$this->security->xss_clean($this->dataPost['loan_infor']['amount_money']),
//          (int)$this->security->xss_clean($this->dataPost['loan_infor']['number_day_loan']),
//          $this->security->xss_clean($this->dataPost['loan_infor']['period_pay_interest']),
//          $this->security->xss_clean($this->dataPost['loan_infor']['type_interest']),
//          $this->security->xss_clean($this->dataPost['loan_infor']['insurrance_contract'])
//          );
//      $this->generateFeeLoanbyMonth(
//          $this->dataPost['code_contract'],
//          $this->security->xss_clean($this->dataPost['customer_infor']),
//          $this->security->xss_clean($this->dataPost['investor_code']),
//          $this->security->xss_clean($this->dataPost['disbursement_date']),
//          (int)$this->security->xss_clean($this->dataPost['loan_infor']['amount_money']),
//          (int)$this->security->xss_clean($this->dataPost['loan_infor']['number_day_loan']),
//          $this->security->xss_clean($this->dataPost['loan_infor']['period_pay_interest']),
//          $this->security->xss_clean($this->dataPost['loan_infor']['type_interest']),
//          $this->security->xss_clean($this->dataPost['loan_infor']['insurrance_contract'])
//      );
        $this->log_model->insert($insertLog);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create new contract success",
//          'db'=> $this->dataPost['code_contract_disbursement'],
//          'dashboard'=> $dashboard
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function get_gdv_by_store_post($store_id)
    {

        $users_by_store = $this->getUserbyStores($store_id);
        $users_by_role = $this->getUserGroupRole(array('5de726e4d6612b6f2c310c78'));
        $result = array_intersect($users_by_store, $users_by_role);
        if (empty($result)) {
            $users_by_role = $this->getUserGroupRole(array('5de726c9d6612b6f2a617ef5'));
            $users_by_store = $this->getUserbyStores($store_id);
            $result = array_intersect($users_by_store, $users_by_role);
        }
        $user_id = array_rand($result, 1);
        $gdv = $this->user_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($result[$user_id])));
        if (!empty($gdv)) {
            return $gdv;
        }
    }

    public function check_identify_post()
    {
        $identify = $this->security->xss_clean($this->dataPost['identify']);
        $contract = $this->contract_model->count(array('customer_infor.customer_identify' => $identify));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function process_create_contract_noheader_post()
    {

        $this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
        $this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
        $this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
        $this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
        //$this->dataPost['image_accurecy'] = $this->security->xss_clean($this->dataPost['image_accurecy']);
        $this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);;

        //Init mã hợp đồng
        $typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";
        // $lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
        // if (isset($lead[0]['_id'])) {
        //  $current_day = strtotime(date('m/d/Y'));
        //  $datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
        //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
        //  $last = 3 - $time;
        //  if ($time <= 8) {
        //      $this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
        //      $this->dataPost['customer_infor']['customer_resources'] = "hoiso";
        //  }
        // }
        $this->dataPost['info_disbursement_max'] = $this->security->xss_clean($this->dataPost['info_disbursement_max']);
        $this->dataPost['status_disbursement_max'] = $this->security->xss_clean($this->dataPost['status_disbursement_max']);
        //Init code_contract_number hợp đồng
        $resNumberCodeContract = $this->initNumberContractCode();
        //Insert contract model
        $gdv = $this->get_gdv_by_store_post($this->dataPost['store']['id']);
        $this->dataPost['code_contract'] = "00000" . $resNumberCodeContract['max_number_contract'];
        $this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
        $this->dataPost['status'] = 5;
        $this->dataPost['type'] = "vaynhanh";
        $this->dataPost['created_at'] = $this->createdAt;
        $this->dataPost['created_by'] = $gdv['email'];
        $arrFee = $this->getFee($this->dataPost);
        if (empty($arrFee['fee'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không lấy được biểu phí.",
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->dataPost['fee'] = $arrFee['fee'];
        $this->dataPost['fee_id'] = $arrFee['id'];
        $contractId = $this->contract_model->insertReturnId($this->dataPost);
        //Insert log
        $insertLog = array(
            "type" => "contract",
            "action" => "create",
            "contract_id" => (string)$contractId,
            "old" => $this->dataPost,
            "created_at" => $this->createdAt,
            "created_by" => $gdv['email']
        );
        $this->log_model->insert($insertLog);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create new contract success",

        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }


    public function process_continue_create_contract_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        // $groupRoles = $this->getGroupRole($this->id);
        // if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
        //  // Check access right
        //  if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
        //      $response = array(
        //          'status' => REST_Controller::HTTP_UNAUTHORIZED,
        //          'data' => array(
        //              "message" => "No have access right"
        //          )
        //      );
        //      $this->set_response($response, REST_Controller::HTTP_OK);
        //      return;
        //  }
        // }

        $this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
        $this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
        $this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
        $this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
        $this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
        $this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
        $this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
        // $this->dataPost['property_infor'] = $this->security->xss_clean($this->dataPost['property_infor']);
        $this->dataPost['property_infor'] = $this->security->xss_clean(!empty($this->dataPost['property_infor']) ? $this->dataPost['property_infor'] : array());
        $this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
        $this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
        $this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
        $this->dataPost['info_disbursement_max'] = $this->security->xss_clean($this->dataPost['info_disbursement_max']);
        $this->dataPost['status_disbursement_max'] = $this->security->xss_clean($this->dataPost['status_disbursement_max']);
        $data_Face_search = $this->security->xss_clean($this->dataPost['data_Face_search']);
        $data_Face_Identify = $this->security->xss_clean($this->dataPost['data_Face_Identify']);
        unset($this->dataPost['data_Face_search']);
        unset($this->dataPost['data_Face_Identify']);
        $inforDB = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        // $lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
        // if (isset($lead[0]['_id'])) {
        //  $current_day = strtotime(date('m/d/Y'));
        //  $datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
        //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
        //  $last = 3 - $time;
        //  if ($time <= 8) {
        //      $this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
        //      $this->dataPost['customer_infor']['customer_resources'] = "hoiso";
        //  }
        // }
        if (empty($inforDB)) return;
        //Check null
        $checkNull = $this->checkNull();
        if ($checkNull['status'] != 1) {
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
        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => "update",
            "contract_id" => $this->dataPost['id'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;

        $this->insert_log_file($insertLog, $this->dataPost['id']);

        /**
         * ----------------------
         */

        //Update contract model
        unset($this->dataPost['id']);
        //Init mã hợp đồng

        $typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";
        $loanProduct = !empty($this->dataPost['loan_infor']['loan_product']['code']) ? $this->dataPost['loan_infor']['loan_product']['code'] : "";
        $loanProductText = !empty($this->dataPost['loan_infor']['loan_product']['text']) ? $this->dataPost['loan_infor']['loan_product']['text'] : "";
        $resCodeContract = $this->initAutoCodeContract($this->dataPost['store']['id'], $typeProperty, $typeLoan, $loanProduct, $loanProductText, $this->dataPost['customer_infor']['customer_phone_number']);
        $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
        $this->dataPost['receiver_infor']['order_code'] = $resCodeContract['code_contract'];
        // $resCodeContract = $this->initContractCode($this->dataPost['store']['id'],$typeProperty,$typeLoan);
        // $this->dataPost['code_contract'] = $resCodeContract['code_contract'];
        // $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
        // $this->dataPost['receiver_infor']['order_code'] = $resCodeContract['code_contract'];

        //Insert contract model
        $resNumberCodeContract = $this->initNumberContractCode();
        //Insert contract model
        $this->dataPost['code_contract'] = "00000" . $resNumberCodeContract['max_number_contract'];
        $this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
        $this->dataPost['status'] = 1;
        $this->dataPost['created_at'] = $this->createdAt;
        $arrFee = $this->getFee($this->dataPost);
        if (empty($arrFee['fee'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không lấy được biểu phí.",
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->dataPost['fee'] = $arrFee['fee'];
        $this->dataPost['fee_id'] = $arrFee['id'];

        //init ma tai san
//      if (!empty($inforDB['asset_code'])) {
//          $asset = $this->asset_management_model->findOne(['asset_code' => $inforDB['asset_code']]);
//          $image = !empty($asset['image']) ? $asset['image'] : [];
//          $image1 = [];
//          foreach ($image as $key => $value) {
//              $image1[$key] = $value;
//          }
//          $driver_license = [];
//          foreach ($this->dataPost['image_accurecy']['driver_license'] as $k => $value) {
//              $driver_license[$k] = $value;
//          }
//          $this->asset_management_model->update(
//              ['asset_code' => $inforDB['asset_code']],
//              [
//                  'type' => !empty($this->dataPost['loan_infor']['type_property']) ? $this->dataPost['loan_infor']['type_property']['code'] : '',
//                  'so_khung' => !empty($this->dataPost['property_infor'][3]) ? strtoupper(trim($this->dataPost['property_infor'][3]['value'])) : '',
//                  'so_may' => !empty($this->dataPost['property_infor'][4]) ? strtoupper(trim($this->dataPost['property_infor'][4]['value'])) : '',
//                  'dia_chi' => !empty($this->dataPost['property_infor']) ? trim($this->dataPost['property_infor'][6]['value']) : '',
//                  'product' => !empty($this->dataPost['loan_infor']['name_property']) ? $this->dataPost['loan_infor']['name_property']['text'] : '',
//                  'bien_so_xe' => !empty($this->dataPost['property_infor'][2]) ? strtoupper(trim(str_replace(array('.', '-', ' '), '', $this->dataPost['property_infor'][2]['value']))) : '',
//                  'image' => !empty($this->dataPost['image_accurecy']['driver_license']) ? (object)array_merge($driver_license, $image1) : $image,
//                  'nhan_hieu' => !empty($this->dataPost['property_infor'][0]) ? trim($this->dataPost['property_infor'][0]['value']) : '',
//                  'model' => !empty($this->dataPost['property_infor'][1]) ? trim($this->dataPost['property_infor'][1]['value']) : '',
//                  'so_dang_ki' => !empty($this->dataPost['property_infor'][7]) ? (trim($this->dataPost['property_infor'][7]['value'])) : '',
//                  'ngay_cap' => !empty($this->dataPost['property_infor'][8]) ? strtotime($this->dataPost['property_infor'][8]['value']) : ''
//              ]);
//      } else {
//          $image_accurecy = $this->dataPost['image_accurecy'] ?? '';
//          $asset_id = $this->get_asset_manager($this->dataPost['customer_infor']['customer_name'], $this->dataPost['property_infor'], $this->dataPost['loan_infor'], $this->dataPost['loan_infor'], $image_accurecy);
//          $asset = $this->asset_management_model->findOne(['_id' => $asset_id]);
//          $this->dataPost['asset_code'] = $asset['asset_code'];
//      }
        $this->contract_model->update(
            array("_id" => $inforDB['_id']),
            $this->dataPost
        );
        if (isset($this->dataPost['code_contract'])) {
            $this->ap_dung_coupon_giam_bhkv($this->dataPost['code_contract']);
        }

        /**
        *
        * VPBank assign vitual account number for contract
        */
        if (isset($this->dataPost['code_contract'])) {
            $vpbank = new VPBank();
            $assignVan = $vpbank->assignVan($this->dataPost['code_contract']);
        }

        //update noti and firebase
//      $note = "new";
//      $this->push_noti_app($this->dataPost['status'], $note, (string)$inforDB['_id'], $this->dataPost['customer_infor']['customer_phone_number'], $this->dataPost['code_contract']);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create contract success",
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }


    public function process_update_contract_continue_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        // $groupRoles = $this->getGroupRole($this->id);
        // if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
        //  // Check access right
        //  if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
        //      $response = array(
        //          'status' => REST_Controller::HTTP_UNAUTHORIZED,
        //          'data' => array(
        //              "message" => "No have access right"
        //          )
        //      );
        //      $this->set_response($response, REST_Controller::HTTP_OK);
        //      return;
        //  }
        // }

        $this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
        $this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
        $this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
        $this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
        $this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
        $this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
        $this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
        $this->dataPost['property_infor'] = $this->security->xss_clean(!empty($this->dataPost['property_infor']) ? $this->dataPost['property_infor'] : array());
        $this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
        $this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
        $this->dataPost['info_disbursement_max'] = $this->security->xss_clean($this->dataPost['info_disbursement_max']);
        $this->dataPost['status_disbursement_max'] = $this->security->xss_clean($this->dataPost['status_disbursement_max']);
        $this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
        $inforDB = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        $lead = $this->lead_model->findOne(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));

        if (isset($lead['_id'])) {
            $this->dataPost['customer_infor']['id_lead'] = (string)$lead['_id'];
        }
        if (empty($inforDB)) return;
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
        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => "update_continue",
            "contract_id" => $this->dataPost['id'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;

        $this->insert_log_file($insertLog, $this->dataPost['id']);

        /**
         * ----------------------
         */


        //Update contract model
        $this->dataPost['updated_by'] = $this->createdAt;
        unset($this->dataPost['id']);
        $this->contract_model->update(
            array("_id" => $inforDB['_id']),
            $this->dataPost
        );

        if (isset($inforDB['code_contract'])) {
            $this->ap_dung_coupon_giam_bhkv($inforDB['code_contract']);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update contract success",
            "data" => $this->dataPost
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function process_update_contract_post()
    {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;

        // $groupRoles = $this->getGroupRole($this->id);
        // if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
        //  // Check access right
        //  if (!in_array('5da98b8568a3ff2f10001b06', $this->roleAccessRights)) {
        //      $response = array(
        //          'status' => REST_Controller::HTTP_UNAUTHORIZED,
        //          'data' => array(
        //              "message" => "No have access right"
        //          )
        //      );
        //      $this->set_response($response, REST_Controller::HTTP_OK);
        //      return;
        //  }
        // }

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
        $this->dataPost['info_disbursement_max'] = $this->security->xss_clean($this->dataPost['info_disbursement_max']);
        $this->dataPost['status_disbursement_max'] = $this->security->xss_clean($this->dataPost['status_disbursement_max']);
        $inforDB = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        // $lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
        // if (isset($lead[0]['_id'])) {
        //  $current_day = strtotime(date('m/d/Y'));
        //  $datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
        //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
        //  $last = 3 - $time;
        //  if ($time <= 8) {
        //      $this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
        //      $this->dataPost['customer_infor']['customer_resources'] = "hoiso";
        //  }
        // }
        $this->dataPost['created_at'] = isset($inforDB['created_at']) ? $inforDB['created_at'] : $this->createdAt;

        if (!empty($this->dataPost['customer_infor']['customer_phone_number'])){

            $ctv_code = $this->lead_model->findOne(["phone_number" => $this->dataPost['customer_infor']['customer_phone_number'] ]);

            if (!empty($ctv_code)){
                $this->dataPost['ctv_code'] = (string)$ctv_code['ctv_code'];
                $this->dataPost['customer_infor']['id_lead'] = (string)$ctv_code['_id'];
            }

        }

        $arrFee = $this->getFee($this->dataPost);
        if (empty($arrFee['fee'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không lấy được biểu phí.",
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->dataPost['fee'] = $arrFee['fee'];
        $this->dataPost['fee_id'] = $arrFee['id'];
        if (empty($inforDB)) return;
        //Check null
        $checkNull = $this->checkNull();
        if ($checkNull['status'] != 1) {
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
        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => "update",
            "contract_id" => $this->dataPost['id'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;

        $this->insert_log_file($insertLog, $this->dataPost['id']);

        /**
         * ----------------------
         */

        //Update contract model
        $this->dataPost['updated_at'] = $this->createdAt;
        unset($this->dataPost['id']);
        $this->contract_model->update(
            array("_id" => $inforDB['_id']),
            $this->dataPost
        );
        if (isset($inforDB['code_contract'])) {
            $this->ap_dung_coupon_giam_bhkv($inforDB['code_contract']);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update contract success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $groupRoles = $this->getGroupRole($this->id);
        if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
            // Check access right
            if (!in_array('5def17f668a3ff1204003ad7', $this->roleAccessRights)) {
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
        if ($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        // $this->log_property($data);
        unset($data['id']);
        if (isset($data['disbursement_date'])) {
            $data['disbursement_date'] = (int)$data['disbursement_date'];
        }
        if (isset($data['max_code_auto_disbursement'])) {
            $data['max_code_auto_disbursement'] = (int)$data['max_code_auto_disbursement'];
        }
        if (isset($data['status_disbursement'])) {
            $data['status_disbursement'] = (int)$data['status_disbursement'];
        }
        if (isset($data['status'])) {
            $data['status'] = (int)$data['status'];
        }
        if (isset($data['response_get_transaction_withdrawal_status_nl']['total_amount'])) {
            $data['response_get_transaction_withdrawal_status_nl']['total_amount'] = (float)$data['response_get_transaction_withdrawal_status_nl']['total_amount'];
        }
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

    public function update_status_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;


        $data = $this->input->post();
        $code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";

        $count = $this->contract_model->count(array("code_contract" => $code_contract));
        if ($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if (isset($data['status'])) {
            $data['status'] = (int)$data['status'];
        }

        //$data
        $this->contract_model->update(
            array("code_contract" => $code_contract),
            $data
        );

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update contract success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }



    public function accountant_investors_disbursement_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //quyền chỉ view kế toán
        if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => array(
                    "message" => "No have access right"
                )
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $groupRoles = $this->getGroupRole($this->id);
        if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
            // Check access right
            if (!in_array('5def15a268a3ff1204003ad6', $this->roleAccessRights)) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,

                    "message" => "No have access right"

                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        // Check old status
        $contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id']), 'status' => array('$ne' => 17)), array("_id", "status", "code_contract", "code_contract_disbursement", "created_by", "store", "receiver_infor", "loan_infor", "customer_infor", "investor_infor", "current_address", "houseHold_address", "property_infor", "megadoc"));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $contract_id = $this->dataPost['contract_id'];
        $chan_bao_hiem = isset($this->dataPost['chan_bao_hiem']) ? (int)$this->dataPost['chan_bao_hiem'] : 2;
        $typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code'] = $ma_cty;
        $code_contract_disbursement = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : "";
        if (empty($code_contract_disbursement)) {
            $resCodeContract = $this->initContractCode($contract['store']['id'], $typeProperty, $typeLoan);
            $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
            $code_contract_disbursement = $resCodeContract['code_contract'];
        }


        $contract['investor_code'] = isset($this->dataPost['investor_code']) ? $this->dataPost['investor_code'] : '';

        // $resCodeContract = $this->initContractCode($contract['store']['id'],$typeProperty,$typeLoan);
        // $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];

        $store = !empty($contract['store']) ? $contract['store'] : "";
        $receiver_infor = $contract['receiver_infor'];
        $receiver_infor['order_code'] = $code_contract_disbursement;
        $this->dataPost['receiver_infor'] = $receiver_infor;

        // $this->log_property($data);
        $percent_interest_investor = floatval($this->dataPost['percent_interest_investor']);
        $this->dataPost['status'] = 17;
        $this->dataPost['status_disbursement'] = 2;
        $this->dataPost['disbursement_date'] = $this->createdAt;

        $autoDisburseMent = $this->contract_model->getCodeAutoDisburseMent();
        $this->dataPost['max_code_auto_disbursement'] = !empty($autoDisburseMent['max_code_auto_disbursement']) ? $autoDisburseMent['max_code_auto_disbursement'] : "";
        $this->dataPost['code_auto_disbursement'] = !empty($autoDisburseMent['code_auto_disbursement']) ? $autoDisburseMent['code_auto_disbursement'] : "";
        $disbursement_date = date('Y-m-d H:i:s', $this->dataPost['disbursement_date']);
        unset($this->dataPost['contract_id']);
        unset($this->dataPost['percent_interest_investor']);
        $amount_money = $contract['loan_infor']['amount_money'];
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '1' && $contract['loan_infor']['amount_GIC'] > 0) {
            $gic = $this->gic_model->findOne(array("contract_id" => (string)$contract['_id']));
            if (empty($gic)) {
                $gic = array();
                if ($chan_bao_hiem == 2)
                    $gic = $this->insert_gic($contract, $code_contract_disbursement, $disbursement_date);
                //            $response = array(
                //  'status' => REST_Controller::HTTP_UNAUTHORIZED,
                //  'data' => $gic
                // );
                // $this->set_response($response, REST_Controller::HTTP_OK);
                // return;
                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'company_code' => $ma_cty

                    );
                    $this->gic_model->insert($dt_gic);
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'company_code' => $ma_cty

                    );
                    $this->gic_model->insert($dt_gic);
                }
            }

        }
        if (isset($contract['loan_infor']['amount_VBI'])) {
            if ($contract['loan_infor']['amount_VBI'] > 0) {
                $check_vbi = $this->vbi_model->findOne(["code_contract" => $contract['code_contract']]);
                $endDate = strtotime('+1 year', strtotime(date('m/d/Y', $this->createdAt)));
                if (empty($check_vbi)) {
                    $dt_vbi = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract' => $contract['code_contract']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status_vbi' => '1'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'endDate' => $endDate
                    , 'company_code' => $ma_cty

                    );


                    $code_contract = $contract['code_contract'];
                    $status_vbi1 = $contract['loan_infor']['maVBI_1'];
                    if (is_numeric($status_vbi1)) {
                        if ($status_vbi1 <= 6) {
                            $call_vbi = $this->insert_sxh_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi1);
                            if ($call_vbi->status != 200) {
                                $dt_vbi['type'] = 'VBI_SXH';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        } else {
                            $call_vbi = $this->insert_utv_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi1);
                            if ($call_vbi->status != 200) {
                                $dt_vbi['type'] = 'VBI_UTV';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        }
                    }
                    $status_vbi2 = $contract['loan_infor']['maVBI_2'];
                    if (is_numeric($status_vbi2)) {
                        if ($status_vbi2 <= 6) {
                            $call_vbi2 = $this->insert_sxh_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi2);
                            if ($call_vbi2->status != 200) {
                                $dt_vbi['type'] = 'VBI_SXH';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        } else {
                            $call_vbi2 = $this->insert_utv_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi2);
                            if ($call_vbi2->status != 200) {
                                $dt_vbi['type'] = 'VBI_UTV';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        }
                    }

                }
            }
        }
        //bh tnds
        if (!empty($contract['loan_infor']['bao_hiem_tnds'])) {
            if (!empty($contract['loan_infor']['bao_hiem_tnds']['type_tnds'])) {
                $check_tnds = $this->contract_tnds_model->findOne(["code_contract" => $contract['code_contract']]);

                if (empty($check_tnds)) {

                    if ($contract['loan_infor']['bao_hiem_tnds']['type_tnds'] == 'MIC_TNDS') {
                        $res = $this->call_mic_tnds($contract);
                        $type_tnds = 'MIC_TNDS';
                    } else {
                        $res = $this->call_vbi_tnds($contract);
                        $type_tnds = 'VBI_TNDS';
                    }

                    $data_tnds = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract' => $contract['code_contract']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'data' => $res
                    , 'type_tnds' => $type_tnds
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    , 'company_code' => $ma_cty
                    );

                    $this->contract_tnds_model->insert($data_tnds);
                }
            }
        }
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '2' && $contract['loan_infor']['amount_MIC'] > 0) {
            $type_mic = "MIC_TDCN";
            $mic_ck = $this->mic_model->findOne(array("contract_id" => (string)$contract['_id'], 'type_mic' => $type_mic));
            if (empty($mic_ck)) {
                $mic = array();
                if ($chan_bao_hiem == 2)
                    $mic = $this->insert_mic($contract, $contract['code_contract_disbursement'], date('d/m/Y'), $ma_cty);
                $this->log_mic($mic->request, $mic->response, $contract['code_contract_disbursement'], $type_mic);
                if ($mic->res != true) {
                    $dt_mic = array(
                        'type_mic' => $type_mic,
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status' => 'deactive'
                    , 'NGAY_HL' => $mic->NGAY_HL
                    , 'NGAY_KT' => $mic->NGAY_KT
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    , "response" => $mic->response
                    , "request" => $mic->request
                    , 'company_code' => $ma_cty
                    );
                    $this->mic_model->insert($dt_mic);
                } else {
                    $mic_data = $mic->data;
                    $dt_mic = array(
                        'type_mic' => $type_mic,
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'mic_gcn' => $mic_data->GCN
                    , 'mic_fee' => $mic_data->PHI
                    , 'NGAY_HL' => $mic->NGAY_HL
                    , 'NGAY_KT' => $mic->NGAY_KT
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status' => 'active'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    , 'company_code' => $ma_cty
                    );
                    $this->mic_model->insert($dt_mic);
                }
            }
        }
        //gic easy
        if (isset($contract['loan_infor']['code_GIC_easy']) && isset($contract['loan_infor']['amount_GIC_easy']) && $contract['loan_infor']['amount_GIC_easy'] > 0) {

            $gic_easy_ck = $this->gic_easy_model->findOne(array("contract_id" => (string)$contract['_id']));
            if (empty($gic_easy_ck)) {
                date_default_timezone_set('Asia/Ho_Chi_Minh');
                $gic = array();

                $gic = $this->insert_gic_easy($contract, $contract['code_contract_disbursement'], date('Y-m-d H:i:s', strtotime("+1 days")));
                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'company_code' => $ma_cty

                    );
                    $this->gic_easy_model->insert($dt_gic);
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'company_code' => $ma_cty

                    );
                    $this->gic_easy_model->insert($dt_gic);
                }
            }

        }
        //gic plt
        if (isset($contract['loan_infor']['code_GIC_plt']) && isset($contract['loan_infor']['amount_GIC_plt']) && in_array($contract['loan_infor']['code_GIC_plt'], array('COPPER', 'SILVER', 'GOLD'))) {

            $gic_plt = $this->gic_plt_model->findOne(array("contract_id" => (string)$contract['_id']));
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            if (empty($gic_plt)) {
                $gic = array();

                $gic = $this->insert_gic_plt($contract, $contract['code_contract_disbursement'], date('Y-m-d H:i:s', strtotime("+1 days")));
                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'company_code' => $ma_cty

                    );
                    $this->gic_plt_model->insert($dt_gic);
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'company_code' => $ma_cty

                    );
                    $this->gic_plt_model->insert($dt_gic);
                }
            }

        }
        //gic pti vta
        if (isset($contract['loan_infor']['bao_hiem_pti_vta']) && in_array($contract['loan_infor']['bao_hiem_pti_vta']['code_pti_vta'], array('G1', 'G2', 'G3')) && in_array($contract['loan_infor']['bao_hiem_pti_vta']['year_pti_vta'], array('1Y', '6M', '3M')) && $contract['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] > 0) {

            $pti_vta = $this->pti_vta_bn_model->findOne(array("code_contract" => $contract['code_contract']));
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            if (empty($pti_vta)) {
                $pti = array();

                $pti = $this->insert_pti_vta($contract, date('d-m-Y', strtotime("+1 days")), $contract['code_contract']);
                if ($pti->success != true) {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'code_contract' => $contract['code_contract']
                    , 'pti_code' => ""
                    , 'contract_info' => $contract
                    , 'pti_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'type_pti' => 'HD'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'company_code' => $ma_cty

                    );
                    $this->pti_vta_bn_model->insert($dt_pti);
                } else {
                    $pti_dt = $pti->data;
                    $request = $pti->request;
                    $NGAY_KT = $pti->NGAY_KT;
                    $NGAY_HL = $pti->NGAY_HL;
                    $number_item = $pti->number_item;
                    $type_pti = "PTI_VTA";
                    $code_pti_vta = $type_pti . '_' . date("dmY") . "_" . time();
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'code_pti_vta' => $code_pti_vta
                    , 'code_contract' => $contract['code_contract']
                    , 'pti_code' => !empty($request->so_hd) ? $request->so_hd : ''
                    , 'contract_info' => $contract
                    , 'so_xac_minh' => !empty($request->so_cmt) ? $request->so_cmt : ''
                    , 'request' => $request
                    , 'pti_info' => $pti_dt
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'number_item' => (int)$number_item
                    , 'NGAY_KT' => $NGAY_KT
                    , 'NGAY_HL' => $NGAY_HL
                    , 'status' => 1
                    , 'erro_info' => '-'
                    , 'type_pti' => 'HD'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'company_code' => $ma_cty

                    );
                    $this->pti_vta_bn_model->insert($dt_pti);
                }
            }

        }
        // PTI BH Tai Nan Con Nguoi
        if (
            isset($contract['loan_infor']['pti_bhtn']['goi']) && 
            isset($contract['loan_infor']['pti_bhtn']['phi']) &&
            isset($contract['loan_infor']['pti_bhtn']['price'])
        ) {
            $pti_vta = $this->pti_bhtn_model->findOne(array("code_contract" => $contract['code_contract']));
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            if (empty($pti_vta)) {
                $pti = $this->insert_pti_bhtn($contract);
                if ($pti->success != true) {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                        , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                        , 'code_contract' => $contract['code_contract']
                        , 'pti_request' => $pti->request
                        , 'pti_info' => $pti->info
                        , 'store' => $store
                        , 'status' => 'errors'
                        , 'created_at' => $this->createdAt
                        , 'created_by' => "superadmin"
                        , 'type' => "HD"
                    );
                } else {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                        , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                        , 'code_contract' => $contract['code_contract']
                        , 'pti_request' => $pti->request
                        , 'pti_info' => $pti->info
                        , 'store' => $store
                        , 'status' => 'success'
                        , 'created_at' => $this->createdAt
                        , 'created_by' => "superadmin"
                        , 'type' => "HD"
                    );
                }
                $this->pti_bhtn_model->insert($dt_pti);
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

        //Masoffer
        if (!empty($contract['customer_infor']['customer_phone_number'])) {
            $lead_masoffer = $this->lead_model->findOne(array("phone_number" => $contract['customer_infor']['customer_phone_number']));
            if ($lead_masoffer['utm_source'] == "masoffer") {
                $api_key = "9Tprs9wMJ4q2Q7lB";
                $transaction_id_masoffer = (string)$lead_masoffer['_id'];
                $click_id_masoffer = !empty($lead_masoffer['click_id_masoffer']) ? $lead_masoffer['click_id_masoffer'] : "";
                $sale_amount = $contract['loan_infor']['amount_loan'];

                $url = "https://s2s.riofintech.net/v1/tienngay/postback.json?api_key=$api_key&postback_type=forced_update&transaction_id=$transaction_id_masoffer&click_id=$click_id_masoffer&status_code=1&product_category_id=CPS&sale_amount=$sale_amount";

                $ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

                curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds

                $result = curl_exec($ch);

                curl_close($ch);

                echo $result;
            }
        }

        //Dinos
        if (!empty($contract['customer_infor']['customer_phone_number'])) {
            $leadData = $this->lead_model->find_one_check_phone($contract['customer_infor']['customer_phone_number']);
            if (!empty($leadData[0]['click_id_dinos']) && $leadData[0]['click_id_dinos'] != "") {
                $status = "approved";
                $this->api_dinos($leadData[0]['click_id_dinos'], $status);
            }
        }

        //Insert log
        $insertLog = array(
            "type" => "contract",
            "action" => "accountant_investors_disbursement",
            "contract_id" => $contract_id,
            "old" => $contract,
            "new" => $this->dataPost,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => "accountant_investors_disbursement",
            "contract_id" => $contract_id,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id = $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;

        $this->insert_log_file($insertLog, $contract_id);

        /**
         * ----------------------
         */

        $this->log_hs_model->insert($insertLog);

        //Insert file_manager_qlhs
        $check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract['store']['id'])));
        $area = '';
        if ($check_area['code_area'] == "Priority" || $check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
            $area = "MB";
            $user = $this->quan_ly_ho_so_mb();
        } else if ($check_area['code_area'] == "KV_MK") {
            $area = "MK";
            $user = $this->quan_ly_ho_so_mekong();
        } else {
            $area = "MN";
            $user = $this->quan_ly_ho_so_mn();
        }

        $file_manager = [
            "code_contract_disbursement_text" => $contract['code_contract_disbursement'],
            "file" => [
                "Thỏa thuận 3 bên",
                "Văn bản bàn giao tài sản",
                "Thông báo",
                "Đăng ký xe/Cà vẹt"
            ],
            "stores" => $contract['store']['id'],
            "status" => "1",
            "giay_to_khac" => "",
            "taisandikem" => "",
            "ghichu" => "",
            "area" => $area,
            "created_at" => $this->createdAt,
            "created_by" => $contract['created_by'],
        ];
        $contractId = $this->file_manager_model->insertReturnId($file_manager);

        $log = array(
            "type" => "fileReturn",
            "action" => "CVKD tạo mới YC",
            "fileReturn_id" => (string)$contractId,
            "fileReturn" => $file_manager,
            "created_at" => $this->createdAt,
            "created_by" => $contract['created_by']
        );
        $this->log_fileManager_model->insert($log);
        $fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId((string)$contractId)));
        $this->sendEmailApprove_qlhs($fileReturn, $user);
        if (!empty($user)) {
            foreach (array_unique($user) as $qlhs) {
                $data_approve = [
                    'action_id' => (string)$contractId,
                    'action' => 'FileReturn_create',
                    'note' => 'Mới',
                    'user_id' => (string)$qlhs,
                    'status' => 1, //1: new, 2 : read, 3: block,
                    'fileReturn_status' => 1,
                    'created_at' => $this->createdAt,
                    "created_by" => $contract['created_by']
                ];
                $this->borrowed_noti_model->insert($data_approve);
            }
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Giải ngân thành công!",
            "data" => $this->dataPost
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function accountant_investors_disbursement_nl_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //quyền chỉ view kế toán
        if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => array(
                    "message" => "No have access right"
                )
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $response = array();
        $groupRoles = $this->getGroupRole($this->id);
        if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
            // Check access right
            if (!in_array('5def15a268a3ff1204003ad6', $this->roleAccessRights)) {
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
        $contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id']), 'status' => array('$ne' => 17)), array("_id", "status", "code_contract", "code_contract_disbursement", "created_by", "store", "receiver_infor", "loan_infor", "customer_infor", "current_address", "houseHold_address", "property_infor", "megadoc"));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $chan_bao_hiem = isset($this->dataPost['chan_bao_hiem']) ? (int)$this->dataPost['chan_bao_hiem'] : 2;
        if (!empty($contract['status']) && !in_array($contract['status'], array(15, 10))) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Trạng thái hợp đồng không sãn sàng để giải ngân"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $merchant_id = !empty($this->dataPost['merchant_id']) ? $this->dataPost['merchant_id'] : "";
        $merchant_password = !empty($this->dataPost['merchant_password']) ? $this->dataPost['merchant_password'] : "";
        $receiver_email = !empty($this->dataPost['receiver_email']) ? $this->dataPost['receiver_email'] : "";
        $reason = !empty($this->dataPost['content_transfer']) ? $this->dataPost['content_transfer'] : "";
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $contract_id = $this->dataPost['contract_id'];
        $typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
        $code_contract_disbursement = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : "";
        if (empty($code_contract_disbursement)) {
            $resCodeContract = $this->initContractCode($contract['store']['id'], $typeProperty, $typeLoan);
            $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
            $code_contract_disbursement = $resCodeContract['code_contract'];
        }
        $store = !empty($contract['store']) ? $contract['store'] : "";
        $receiver_infor = $contract['receiver_infor'];
        $receiver_infor['order_code'] = $code_contract_disbursement;
        $this->dataPost['receiver_infor'] = $receiver_infor;
        $investorData = $this->investor_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['investor_id'])));
        //  var_dump($investorData); die;
        $contract['investor_code'] = isset($investorData['code']) ? $investorData['code'] : '';
        $percent_interest_investor = floatval($this->dataPost['percent_interest_investor']);
        $this->dataPost['status'] = 17;
        $this->dataPost['status_disbursement'] = 2;
        $this->dataPost['status_create_withdrawal_nl'] = "00";
        $this->dataPost['disbursement_date'] = $this->createdAt;
        $disbursement_date = date('Y-m-d H:i:s', $this->dataPost['disbursement_date_new']);


        $autoDisburseMent = $this->contract_model->getCodeAutoDisburseMent();
        $this->dataPost['max_code_auto_disbursement'] = !empty($autoDisburseMent['max_code_auto_disbursement']) ? $autoDisburseMent['max_code_auto_disbursement'] : "";
        $this->dataPost['code_auto_disbursement'] = !empty($autoDisburseMent['code_auto_disbursement']) ? $autoDisburseMent['code_auto_disbursement'] : "";
        // call ngân lượng giải ngân
        if ($contract['receiver_infor']['type_payout'] == 2) {
            $account_type = 3;
            $card_fullname = $contract['receiver_infor']['bank_account_holder'];
            $card_number = $contract['receiver_infor']['bank_account'];
            $bank_code = $contract['receiver_infor']['bank_id'];
            $branch_name = $contract['receiver_infor']['bank_branch'];
        }
        //ATM Card Number = 3
        if ($contract['receiver_infor']['type_payout'] == 3) {
            $account_type = 2;
            $card_fullname = $contract['receiver_infor']['atm_card_holder'];
            $card_number = $contract['receiver_infor']['atm_card_number'];
            $bank_code = $contract['receiver_infor']['bank_id'];
            $branch_name = "";
        }
        $nlcheckout = new NL_Withdraw($merchant_id, $merchant_password, $receiver_email);
        $nlcheckout->url_api = $this->config->item("NL_WITHDRAW_URL");
        $total_amount = $contract['loan_infor']['amount_loan'];
        $amount_money = $contract['loan_infor']['amount_money'];
        $ref_code = "macode_" . $contract['code_contract'] . "_" . time();
        $card_month = '';
        $card_year = '';

        unset($this->dataPost['contract_id']);
        unset($this->dataPost['percent_interest_investor']);
        unset($this->dataPost['merchant_id']);
        unset($this->dataPost['merchant_password']);
        unset($this->dataPost['receiver_email']);
        $nl_result = $nlcheckout->SetCashoutRequest($ref_code, $total_amount, $account_type, $bank_code, $card_fullname, $card_number, $card_month, $card_year, $branch_name, $reason);
        //Insert log
        $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", " ================= START ======================= ");
        $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", "[INPUT_WD]: ref_code :" . $ref_code . ",total_amount :" . $total_amount . ", account_type:" . $account_type . ",bank_code: " . $bank_code . ",card_fullname:" . $card_fullname . ",card_number:" . $card_number . ",card_month:" . $card_month . ",card_year:" . $card_year . ",branch_name:" . $branch_name . ",reason:" . $reason);
        $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", "[OUTPUT_WD]: nl_result :" . json_encode($nl_result));
        $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", " ================= END ======================= ");


        if ($nl_result->error_code == '00') {
            if (!empty($nl_result->transaction_status) && $nl_result->transaction_status == '00') {
                $this->dataPost['response_get_transaction_withdrawal_status_nl'] = $nl_result;
                $this->dataPost['status_create_withdrawal_nl'] = '00';
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
                    "new" => $this->dataPost,
                    "created_at" => $this->createdAt,
                    "created_by" => $this->uemail
                );
                /**
                 * Save log to json file
                 */

                $insertLogNew = [
                    "type" => "contract",
                    "action" => "accountant_investors_disbursement",
                    "contract_id" => $contract_id,
                    "created_at" => $this->createdAt,
                    "created_by" => $this->uemail
                ];
                $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
                $this->log_model->insert($insertLog);
                $insertLog['log_id'] = $log_id;

                $this->insert_log_file($insertLog, $contract_id);

                /**
                 * ----------------------
                 */

                $this->log_hs_model->insert($insertLog);

                //Insert file_manager_qlhs
                $check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract['store']['id'])));
                $area = '';
                if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
                    $area = "MB";
                    $user = $this->quan_ly_ho_so_mb();
                } else if ($check_area['code_area'] == "KV_MK") {
                    $area = "MK";
                    $user = $this->quan_ly_ho_so_mekong();
                } else {
                    $area = "MN";
                    $user = $this->quan_ly_ho_so_mn();
                }

                $file_manager = [
                    "code_contract_disbursement_text" => $contract['code_contract_disbursement'],
                    "file" => [
                        "Thỏa thuận 3 bên",
                        "Văn bản bàn giao tài sản",
                        "Thông báo",
                        "Đăng ký xe/Cà vẹt"
                    ],
                    "stores" => $contract['store']['id'],
                    "status" => "1",
                    "giay_to_khac" => "",
                    "taisandikem" => "",
                    "ghichu" => "",
                    "area" => $area,
                    "created_at" => $this->createdAt,
                    "created_by" => $contract['created_by'],
                ];
                $contractId = $this->file_manager_model->insertReturnId($file_manager);

                $log = array(
                    "type" => "fileReturn",
                    "action" => "CVKD tạo mới YC",
                    "fileReturn_id" => (string)$contractId,
                    "fileReturn" => $file_manager,
                    "created_at" => $this->createdAt,
                    "created_by" => $contract['created_by']
                );
                $this->log_fileManager_model->insert($log);
                $fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId((string)$contractId)));
                $this->sendEmailApprove_qlhs($fileReturn, $user);
                if (!empty($user)) {
                    foreach (array_unique($user) as $qlhs) {
                        $data_approve = [
                            'action_id' => (string)$contractId,
                            'action' => 'FileReturn_create',
                            'note' => 'Mới',
                            'user_id' => (string)$qlhs,
                            'status' => 1, //1: new, 2 : read, 3: block,
                            'fileReturn_status' => 1,
                            'created_at' => $this->createdAt,
                            "created_by" => $contract['created_by']
                        ];
                        $this->borrowed_noti_model->insert($data_approve);
                    }
                }

                //Masoffer
                if (!empty($contract['customer_infor']['customer_phone_number'])) {
                    $lead_masoffer = $this->lead_model->findOne(array("phone_number" => $contract['customer_infor']['customer_phone_number']));
                    if ($lead_masoffer['utm_source'] == "masoffer") {
                        $api_key = "9Tprs9wMJ4q2Q7lB";
                        $transaction_id_masoffer = (string)$lead_masoffer['_id'];
                        $click_id_masoffer = !empty($lead_masoffer['click_id_masoffer']) ? $lead_masoffer['click_id_masoffer'] : "";
                        $sale_amount = $contract['loan_infor']['amount_loan'];

                        $url = "https://s2s.riofintech.net/v1/tienngay/postback.json?api_key=$api_key&postback_type=forced_update&transaction_id=$transaction_id_masoffer&click_id=$click_id_masoffer&status_code=1&product_category_id=CPS&sale_amount=$sale_amount";

                        $ch = curl_init($url);

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

                        curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds

                        $result = curl_exec($ch);

                        curl_close($ch);

                        echo $result;
                    }
                }
                //Dinos
                if (!empty($contract['customer_infor']['customer_phone_number'])) {
                    $leadData = $this->lead_model->find_one_check_phone($contract['customer_infor']['customer_phone_number']);
                    if (!empty($leadData[0]['click_id_dinos']) && $leadData[0]['click_id_dinos'] != "") {
                        $status = "approved";
                        $this->api_dinos($leadData[0]['click_id_dinos'], $status);
                    }
                }

                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'message' => "Update contract success",
                    'code' => "00",
                    "data" => $this->dataPost
                );

            } else if (!empty($nl_result->transaction_status) && $nl_result->transaction_status == '01') {
                $this->contract_model->update(
                    array("_id" => new MongoDB\BSON\ObjectId($contract_id)),
                    array("fee.percent_interest_investor" => $percent_interest_investor)
                );
                $dataPost = array(
                    "status" => 9,
                    "status_create_withdrawal_nl" => '01',
                    "response_get_transaction_withdrawal_status_nl" => $nl_result,
                    "investor_infor" => $investorData,
                    "max_code_auto_disbursement" => !empty($autoDisburseMent['max_code_auto_disbursement']) ? $autoDisburseMent['max_code_auto_disbursement'] : "",
                    "code_auto_disbursement" => !empty($autoDisburseMent['code_auto_disbursement']) ? $autoDisburseMent['code_auto_disbursement'] : ""
                );
                $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'code' => "01",
                    'data' => "",
                    'message' => "Đã tạo lệnh giải ngân thành công nhưng đang chờ ngân lượng xử lý"
                );

            } else {
                $dataPost = array(
                    "status" => 10,
                    "status_create_withdrawal_nl" => '02',
                    "response_get_transaction_withdrawal_status_nl" => $nl_result,
                );
                $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => !empty($nl_result->error_message) ? $nl_result->error_message : "giải ngân không thành công 1"
                );

            }
        } else {
            $dataPost = array(
                "status_create_withdrawal_nl" => '03',
                "response_get_transaction_withdrawal_status_nl" => $nl_result
            );
            $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);
            $message = "giải ngân không thành công";
            if ($nl_result->error_code == '99') {
                $message = "Lỗi không xác định";
            }
            if ($nl_result->error_code == '01') {
                $message = "Merchant không được phép sử dụng phương thức này";
            }
            if ($nl_result->error_code == '99') {
                $message = "Lỗi không xác định";
            }
            if ($nl_result->error_code == '02') {
                $message = "Thông tin thẻ sai định dạng";
            }
            if ($nl_result->error_code == '03') {
                $message = "Thông tin merchant không chính xác";
            }
            if ($nl_result->error_code == '04') {
                $message = "Có lỗi trong quá trình kết nối.";
            }
            if ($nl_result->error_code == '05') {
                $message = "Số tiền không hợp lệ";
            }
            if ($nl_result->error_code == '06') {
                $message = "Tên chủ thẻ không hợp lệ";
            }
            if ($nl_result->error_code == '07') {
                $message = "Số tài khoản không hợp lệ";
            }
            if ($nl_result->error_code == '08') {
                $message = "Lỗi kết nối tới ngân hàng. Lỗi xảy ra khi ngân hàng đang bảo trì, nâng cấp mà không
                xuất phát từ merchant";
            }
            if ($nl_result->error_code == '09') {
                $message = "bank_code không hợp lệ";
            }
            if ($nl_result->error_code == '10') {
                $message = "Số dư tài khoản không đủ để thực hiện giao dịch";
            }
            if ($nl_result->error_code == '11') {
                $message = "Mã tham chiếu ( ref_code ) không hợp lệ";
            }
            if ($nl_result->error_code == '12') {
                $message = "Mã tham chiếu ( ref_code ) đã tồn tại";
            }
            if ($nl_result->error_code == '14') {
                $message = "Function không đúng";
            }
            if ($nl_result->error_code == '16') {
                $message = "receiver_email đang bị khóa hoặc phong tỏa không thể giao dịch";
            }
            if ($nl_result->error_code == '17') {
                $message = "account_type không hợp lệ";
            }
            if ($nl_result->error_code == '18') {
                $message = "Ngân hàng đang bảo trì";
            }

            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => !empty($message) ? $message : "giải ngân không thành công 2"
            );

        }
        $time_GIC_kv = "";
        //gic khoản vay
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '1' && $contract['loan_infor']['amount_GIC'] > 0) {
            $gic = $this->gic_model->findOne(array("contract_id" => (string)$contract['_id']));
            if (empty($gic)) {
                $time_GIC_kv .= time();
                $gic = array();
                if ($chan_bao_hiem == 2)
                    $gic = $this->insert_gic($contract, $code_contract_disbursement, $disbursement_date);
                $time_GIC_kv .= ' - ' . time();
                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $code_contract_disbursement
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty
                    );
                    $this->gic_model->insert($dt_gic);
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $code_contract_disbursement
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty
                    );
                    $this->gic_model->insert($dt_gic);
                }
            }

        }
        $time_MIC_kv = "";
        //mic khoản vay
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '2' && $contract['loan_infor']['amount_MIC'] > 0) {
            $type_mic = "MIC_TDCN";
            $mic_ck = $this->mic_model->findOne(array("contract_id" => (string)$contract['_id'], 'type_mic' => $type_mic));
            $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
            if (empty($mic_ck)) {
                $time_MIC_kv .= time();
                $mic = array();
                if ($chan_bao_hiem == 2)
                    $mic = $this->insert_mic($contract, $contract['code_contract_disbursement'], date('d/m/Y'), $ma_cty);
                $time_MIC_kv .= ' - ' . time();

                $this->log_mic($mic->request, $mic->response, $contract['code_contract_disbursement'], $type_mic);
                if ($mic->res != true) {
                    $dt_mic = array(
                        'type_mic' => $type_mic,
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status' => 'deactive'
                    , 'NGAY_HL' => $mic->NGAY_HL
                    , 'NGAY_KT' => $mic->NGAY_KT
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    , "response" => $mic->response
                    , "request" => $mic->request
                    , 'company_code' => $ma_cty

                    );
                    $this->mic_model->insert($dt_mic);

                } else {
                    $mic_data = $mic->data;
                    $dt_mic = array(
                        'type_mic' => $type_mic,
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'mic_gcn' => $mic_data->GCN
                    , 'mic_fee' => $mic_data->PHI
                    , 'NGAY_HL' => $mic->NGAY_HL
                    , 'NGAY_KT' => $mic->NGAY_KT
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status' => 'active'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    , 'company_code' => $ma_cty
                    );
                    $this->mic_model->insert($dt_mic);
                }
            }

        }
        //end mic khoan vay
        //gic easy
        $time_GIC_easy = "";
        if (isset($contract['loan_infor']['code_GIC_easy']) && isset($contract['loan_infor']['amount_GIC_easy']) && $contract['loan_infor']['amount_GIC_easy'] > 0) {

            $gic_ck_esay = $this->gic_easy_model->findOne(array("contract_id" => (string)$contract['_id']));
            if (empty($gic_ck_esay)) {
                $time_GIC_easy .= time();
                $gic = array();

                $gic = $this->insert_gic_easy($contract, $contract['code_contract_disbursement'], date('Y-m-d H:i:s', strtotime("+1 days")));
                $time_GIC_easy .= ' - ' . time();

                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_easy_model->insert($dt_gic);
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_easy_model->insert($dt_gic);
                }
            }

        }
        //end gic easy
        //gic plt
        $time_GIC_plt = "";
        if (isset($contract['loan_infor']['code_GIC_plt']) && isset($contract['loan_infor']['amount_GIC_plt']) && in_array($contract['loan_infor']['code_GIC_plt'], array('COPPER', 'SILVER', 'GOLD'))) {

            $gic_plt = $this->gic_plt_model->findOne(array("contract_id" => (string)$contract['_id']));

            if (empty($gic_plt)) {
                $time_GIC_plt .= time();
                $gic = array();

                $gic = $this->insert_gic_plt($contract, $contract['code_contract_disbursement'], date('Y-m-d H:i:s', strtotime("+1 days")));
                $time_GIC_plt .= ' - ' . time();

                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_plt_model->insert($dt_gic);
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_plt_model->insert($dt_gic);
                }
            }

        }


        if (isset($contract['loan_infor']['amount_VBI'])) {
            if ($contract['loan_infor']['amount_VBI'] > 0) {
                $check_vbi = $this->vbi_model->findOne(["code_contract" => $contract['code_contract']]);
                $endDate = strtotime('+1 year', strtotime(date('m/d/Y', $this->createdAt)));
                if (empty($check_vbi)) {
                    $dt_vbi = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract' => $contract['code_contract']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status_vbi' => '1'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'endDate' => $endDate
                    ,'company_code' => $ma_cty

                    );


                    $code_contract = $contract['code_contract'];
                    $status_vbi1 = $contract['loan_infor']['maVBI_1'];
                    if (is_numeric($status_vbi1)) {
                        if ($status_vbi1 <= 6) {
                            $call_vbi = $this->insert_sxh_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi1);
                            if ($call_vbi->status != 200) {
                                $dt_vbi['type'] = 'VBI_SXH';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        } else {
                            $call_vbi = $this->insert_utv_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi1);
                            if ($call_vbi->status != 200) {
                                $dt_vbi['type'] = 'VBI_UTV';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        }
                    }
                    $status_vbi2 = $contract['loan_infor']['maVBI_2'];
                    if (is_numeric($status_vbi2)) {
                        if ($status_vbi2 <= 6) {
                            $call_vbi2 = $this->insert_sxh_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi2);
                            if ($call_vbi2->status != 200) {
                                $dt_vbi['type'] = 'VBI_SXH';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        } else {
                            $call_vbi2 = $this->insert_utv_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi2);
                            if ($call_vbi2->status != 200) {
                                $dt_vbi['type'] = 'VBI_UTV';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        }
                    }

                }
            }
        }
        //bh tnds
        if (!empty($contract['loan_infor']['bao_hiem_tnds'])) {
            if (!empty($contract['loan_infor']['bao_hiem_tnds']['type_tnds'])) {
                $check_tnds = $this->contract_tnds_model->findOne(["code_contract" => $contract['code_contract']]);

                if (empty($check_tnds)) {

                    if ($contract['loan_infor']['bao_hiem_tnds']['type_tnds'] == 'MIC_TNDS') {
                        $res = $this->call_mic_tnds($contract);
                    } else {
                        $res = $this->call_vbi_tnds($contract);
                    }

                    $data_tnds = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract' => $contract['code_contract']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'data' => $res
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    ,'company_code' => $ma_cty
                    );
                    $this->contract_tnds_model->insert($data_tnds);
                }
            }
        }
        //gic pti vta
        if (isset($contract['loan_infor']['bao_hiem_pti_vta']) && in_array($contract['loan_infor']['bao_hiem_pti_vta']['code_pti_vta'], array('G1', 'G2', 'G3')) && in_array($contract['loan_infor']['bao_hiem_pti_vta']['year_pti_vta'], array('1Y', '6M', '3M')) && $contract['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] > 0) {

            $pti_vta = $this->pti_vta_bn_model->findOne(array("code_contract" => $contract['code_contract']));
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            if (empty($pti_vta)) {
                $pti = array();

                $pti = $this->insert_pti_vta($contract, date('d-m-Y', strtotime("+1 days")), $contract['code_contract']);
                if ($pti->success != true) {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'code_contract' => $contract['code_contract']
                    , 'pti_code' => ""
                    , 'contract_info' => $contract
                    , 'pti_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'type_pti' => 'HD'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->pti_vta_bn_model->insert($dt_pti);
                } else {
                    $pti_dt = $pti->data;
                    $request = $pti->request;
                    $NGAY_KT = $pti->NGAY_KT;
                    $NGAY_HL = $pti->NGAY_HL;
                    $number_item = $pti->number_item;
                    $type_pti = "PTI_VTA";
                    $code_pti_vta = $type_pti . '_' . date("dmY") . "_" . time();
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'code_pti_vta' => $code_pti_vta
                    , 'code_contract' => $contract['code_contract']
                    , 'pti_code' => !empty($request->so_hd) ? $request->so_hd : ''
                    , 'contract_info' => $contract
                    , 'so_xac_minh' => !empty($request->so_cmt) ? $request->so_cmt : ''
                    , 'request' => $request
                    , 'pti_info' => $pti_dt
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'NGAY_KT' => $NGAY_KT
                    , 'NGAY_HL' => $NGAY_HL
                    , 'number_item' => (int)$number_item
                    , 'status' => 1
                    , 'erro_info' => '-'
                    , 'type_pti' => 'HD'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->pti_vta_bn_model->insert($dt_pti);
                }
            }

        }
        // PTI BH Tai Nan Con Nguoi
        if (
            isset($contract['loan_infor']['pti_bhtn']['goi']) && 
            isset($contract['loan_infor']['pti_bhtn']['phi']) &&
            isset($contract['loan_infor']['pti_bhtn']['price'])
        ) {
            $pti_vta = $this->pti_bhtn_model->findOne(array("code_contract" => $contract['code_contract']));
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            if (empty($pti_vta)) {
                $pti = $this->insert_pti_bhtn($contract);
                if ($pti->success != true) {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                        , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                        , 'code_contract' => $contract['code_contract']
                        , 'pti_request' => $pti->request
                        , 'pti_info' => $pti->info
                        , 'store' => $store
                        , 'status' => 'errors'
                        , 'created_at' => $this->createdAt
                        , 'created_by' => "superadmin"
                        , 'type' => "HD"
                    );
                } else {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                        , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                        , 'code_contract' => $contract['code_contract']
                        , 'pti_request' => $pti->request
                        , 'pti_info' => $pti->info
                        , 'store' => $store
                        , 'status' => 'success'
                        , 'created_at' => $this->createdAt
                        , 'created_by' => "superadmin"
                        , 'type' => "HD"
                    );
                }
                $this->pti_bhtn_model->insert($dt_pti);
            }
        }
        //Insert log
        $this->WriteLog("BAOHIEM" . date("Ymd", time()) . ".txt", " ================= START ======================= ");
        $this->WriteLog("BAOHIEM" . date("Ymd", time()) . ".txt", "time_GIC_kv :" . $time_GIC_kv . ",time_GIC_easy :" . $time_GIC_easy . ", time_GIC_plt:" . $time_GIC_plt . ",time_MIC_kv: " . $time_MIC_kv . ",code_contract:" . $contract['code_contract']);
        $this->WriteLog("BAOHIEM" . date("Ymd", time()) . ".txt", " ================= END ======================= ");

        //end gic plt
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function accountant_investors_disbursement_nl_max_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //quyền chỉ view kế toán
        if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => array(
                    "message" => "No have access right"
                )
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $response = array();
        $groupRoles = $this->getGroupRole($this->id);
        if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
            // Check access right
            if (!in_array('5def15a268a3ff1204003ad6', $this->roleAccessRights)) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,

                    "message" => "No have access right"

                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        if (empty($this->dataPost['part'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,

                "message" => "Lần giải ngân không xác định "

            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        // Check old status
        $contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id']), 'status' => 17), array("_id", "status", "code_contract", "code_contract_disbursement", "created_by", "store", "receiver_infor", "loan_infor", "customer_infor", "current_address", "houseHold_address", "property_infor", "info_disbursement_max", "megadoc"));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!empty($contract['status']) && !in_array($contract['status'], array(17))) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Trạng thái hợp đồng không sãn sàng để giải ngân"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $chan_bao_hiem = isset($this->dataPost['chan_bao_hiem']) ? (int)$this->dataPost['chan_bao_hiem'] : 2;
        $merchant_id = !empty($this->dataPost['merchant_id']) ? $this->dataPost['merchant_id'] : "";
        $merchant_password = !empty($this->dataPost['merchant_password']) ? $this->dataPost['merchant_password'] : "";
        $receiver_email = !empty($this->dataPost['receiver_email']) ? $this->dataPost['receiver_email'] : "";

          $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
          $contract['company_code']=$ma_cty;
        $contract_id = $this->dataPost['contract_id'];
        $typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
        $code_contract_disbursement = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : "";
        if (empty($code_contract_disbursement)) {
            $resCodeContract = $this->initContractCode($contract['store']['id'], $typeProperty, $typeLoan);
            $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
            $code_contract_disbursement = $resCodeContract['code_contract'];
        }
        $store = !empty($contract['store']) ? $contract['store'] : "";
        $receiver_infor = $contract['receiver_infor'];
        $receiver_infor['order_code'] = $code_contract_disbursement;
        $this->dataPost['receiver_infor'] = $receiver_infor;
        $investorData = $this->investor_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['investor_id'])));
        //  var_dump($investorData); die;
        $contract['investor_code'] = isset($investorData['code']) ? $investorData['code'] : '';
        $percent_interest_investor = floatval($this->dataPost['percent_interest_investor']);
        // $this->dataPost['status'] = 17;
        // $this->dataPost['status_disbursement'] = 2;
        // $this->dataPost['status_create_withdrawal_nl'] = "00";
        // $this->dataPost['disbursement_date'] = $this->createdAt;
        // $disbursement_date = date('Y-m-d H:i:s', $this->dataPost['disbursement_date_new']);
        $part = $this->dataPost['part'];
        // call ngân lượng giải ngân
        $reason = !empty($this->dataPost['content_transfer']) ? $this->dataPost['content_transfer'] : "";
        if ($contract['receiver_infor']['type_payout'] == 2) {
            $account_type = 3;
            $card_fullname = $contract['receiver_infor']['bank_account_holder'];
            $card_number = $contract['receiver_infor']['bank_account'];
            $bank_code = $contract['receiver_infor']['bank_id'];
            $branch_name = $contract['receiver_infor']['bank_branch'];
        }
        //ATM Card Number = 3
        if ($contract['receiver_infor']['type_payout'] == 3) {
            $account_type = 2;
            $card_fullname = $contract['receiver_infor']['atm_card_holder'];
            $card_number = $contract['receiver_infor']['atm_card_number'];
            $bank_code = $contract['receiver_infor']['bank_id'];
            $branch_name = "";
        }
        $nlcheckout = new NL_Withdraw($merchant_id, $merchant_password, $receiver_email);
        $nlcheckout->url_api = $this->config->item("NL_WITHDRAW_URL");
        $total_amount = $contract['info_disbursement_max']['part_' . $part]['money'];
        $ref_code = 'part_' . $part . '_' . $contract['code_contract'] . "_" . time();
        $card_month = '';
        $card_year = '';
        $money_ck = isset(divide_amount_money($contract['loan_infor']['amount_loan'])['part_' . $part]['money']) ? divide_amount_money($contract['loan_infor']['amount_loan'])['part_' . $part]['money'] : 0;
        if ($money_ck != $total_amount) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Giải ngân không thành công 3"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        unset($this->dataPost['contract_id']);
        unset($this->dataPost['percent_interest_investor']);
        unset($this->dataPost['merchant_id']);
        unset($this->dataPost['merchant_password']);
        unset($this->dataPost['receiver_email']);
        $nl_result = $nlcheckout->SetCashoutRequest($ref_code, $total_amount, $account_type, $bank_code, $card_fullname, $card_number, $card_month, $card_year, $branch_name, $reason);
        //Insert log
        $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", " ================= START ======================= ");
        $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", "[INPUT_WD]: ref_code :" . $ref_code . ",total_amount :" . $total_amount . ", account_type:" . $account_type . ",bank_code: " . $bank_code . ",card_fullname:" . $card_fullname . ",card_number:" . $card_number . ",card_month:" . $card_month . ",card_year:" . $card_year . ",branch_name:" . $branch_name . ",reason:" . $reason);
        $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", "[OUTPUT_WD]: nl_result :" . json_encode($nl_result));
        $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", " ================= END ======================= ");


        if ($nl_result->error_code == '00') {
            if (!empty($nl_result->transaction_status) && $nl_result->transaction_status == '00') {
                $dataPost = array('info_disbursement_max.part_' . $part => array(
                    "status" => "2",
                    "money" => $total_amount,
                    "status_create_withdrawal_nl" => '01',
                    "response_get_transaction_withdrawal_status_nl" => $nl_result,
                ));
                $this->contract_model->update(
                    array("_id" => new MongoDB\BSON\ObjectId($contract_id)),
                    $dataPost
                );
                //Insert log
                $insertLog = array(
                    "type" => "contract",
                    "action" => "accountant_investors_disbursement_max_" . $part,
                    "contract_id" => $contract_id,
                    "old" => $contract,
                    "new" => $this->dataPost,
                    "created_at" => $this->createdAt,
                    "created_by" => $this->uemail
                );
                /**
                 * Save log to json file
                 */

                $insertLogNew = [
                    "type" => "contract",
                    "action" => "accountant_investors_disbursement_max_" . $part,
                    "contract_id" => $contract_id,
                    "created_at" => $this->createdAt,
                    "created_by" => $this->uemail
                ];
                $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
                $this->log_model->insert($insertLog);
                $insertLog['log_id'] = $log_id;

                $this->insert_log_file($insertLog, $contract_id);

                /**
                 * ----------------------
                 */

                $this->log_hs_model->insert($insertLog);

                //Insert file_manager_qlhs
                $check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract['store']['id'])));
                $area = '';
                if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
                    $area = "MB";
                    $user = $this->quan_ly_ho_so_mb();
                } else if ($check_area['code_area'] == "KV_MK") {
                    $area = "MK";
                    $user = $this->quan_ly_ho_so_mekong();
                } else {
                    $area = "MN";
                    $user = $this->quan_ly_ho_so_mn();
                }

                $file_manager = [
                    "code_contract_disbursement_text" => $contract['code_contract_disbursement'],
                    "file" => [
                        "Thỏa thuận 3 bên",
                        "Văn bản bàn giao tài sản",
                        "Thông báo",
                        "Đăng ký xe/Cà vẹt"
                    ],
                    "stores" => $contract['store']['id'],
                    "status" => "1",
                    "giay_to_khac" => "",
                    "taisandikem" => "",
                    "ghichu" => "",
                    "area" => $area,
                    "created_at" => $this->createdAt,
                    "created_by" => $contract['created_by'],
                ];
                $contractId = $this->file_manager_model->insertReturnId($file_manager);

                $log = array(
                    "type" => "fileReturn",
                    "action" => "CVKD tạo mới YC",
                    "fileReturn_id" => (string)$contractId,
                    "fileReturn" => $file_manager,
                    "created_at" => $this->createdAt,
                    "created_by" => $contract['created_by']
                );
                $this->log_fileManager_model->insert($log);
                $fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId((string)$contractId)));
                $this->sendEmailApprove_qlhs($fileReturn, $user);
                if (!empty($user)) {
                    foreach (array_unique($user) as $qlhs) {
                        $data_approve = [
                            'action_id' => (string)$contractId,
                            'action' => 'FileReturn_create',
                            'note' => 'Mới',
                            'user_id' => (string)$qlhs,
                            'status' => 1, //1: new, 2 : read, 3: block,
                            'fileReturn_status' => 1,
                            'created_at' => $this->createdAt,
                            "created_by" => $contract['created_by']
                        ];
                        $this->borrowed_noti_model->insert($data_approve);
                    }
                }

                //Masoffer
                if (!empty($contract['customer_infor']['customer_phone_number'])) {
                    $lead_masoffer = $this->lead_model->findOne(array("phone_number" => $contract['customer_infor']['customer_phone_number']));
                    if ($lead_masoffer['utm_source'] == "masoffer") {
                        $api_key = "9Tprs9wMJ4q2Q7lB";
                        $transaction_id_masoffer = (string)$lead_masoffer['_id'];
                        $click_id_masoffer = !empty($lead_masoffer['click_id_masoffer']) ? $lead_masoffer['click_id_masoffer'] : "";
                        $sale_amount = $contract['loan_infor']['amount_loan'];

                        $url = "https://s2s.riofintech.net/v1/tienngay/postback.json?api_key=$api_key&postback_type=forced_update&transaction_id=$transaction_id_masoffer&click_id=$click_id_masoffer&status_code=1&product_category_id=CPS&sale_amount=$sale_amount";

                        $ch = curl_init($url);

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

                        curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds

                        $result = curl_exec($ch);

                        curl_close($ch);

                        echo $result;
                    }
                }

                //Dinos
                if (!empty($contract['customer_infor']['customer_phone_number'])) {
                    $leadData = $this->lead_model->find_one_check_phone($contract['customer_infor']['customer_phone_number']);

                    if (!empty($leadData[0]['click_id_dinos']) && $leadData[0]['click_id_dinos'] != "") {
                        $status = "approved";
                        $this->api_dinos($leadData[0]['click_id_dinos'], $status);
                    }
                }



                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'message' => "Update contract success",
                    'code' => "00",
                    "data" => $this->dataPost
                );

            } else if (!empty($nl_result->transaction_status) && $nl_result->transaction_status == '01') {

                $dataPost = array('info_disbursement_max.part_' . $part => array(
                    "status" => "1",
                    "money" => $total_amount,
                    "status_create_withdrawal_nl" => '01',
                    "response_get_transaction_withdrawal_status_nl" => $nl_result

                ));
                $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'code' => "01",
                    'data' => "",
                    'message' => "Đã tạo lệnh giải ngân thành công nhưng đang chờ ngân lượng xử lý"
                );

            } else {
                $dataPost = array('info_disbursement_max.part_' . $part => array(
                    "status" => "1",
                    "money" => $total_amount,
                    "status_create_withdrawal_nl" => '02',
                    "response_get_transaction_withdrawal_status_nl" => $nl_result,
                ));
                $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => !empty($nl_result->error_message) ? $nl_result->error_message : "giải ngân không thành công 1"
                );

            }
        } else {

            $dataPost = array('info_disbursement_max.part_' . $part => array(
                "status" => "1",
                "money" => $total_amount,
                "status_create_withdrawal_nl" => '03',
                "response_get_transaction_withdrawal_status_nl" => $nl_result

            ));
            $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);
            $message = "giải ngân không thành công";
            if ($nl_result->error_code == '99') {
                $message = "Lỗi không xác định";
            }
            if ($nl_result->error_code == '01') {
                $message = "Merchant không được phép sử dụng phương thức này";
            }
            if ($nl_result->error_code == '99') {
                $message = "Lỗi không xác định";
            }
            if ($nl_result->error_code == '02') {
                $message = "Thông tin thẻ sai định dạng";
            }
            if ($nl_result->error_code == '03') {
                $message = "Thông tin merchant không chính xác";
            }
            if ($nl_result->error_code == '04') {
                $message = "Có lỗi trong quá trình kết nối.";
            }
            if ($nl_result->error_code == '05') {
                $message = "Số tiền không hợp lệ";
            }
            if ($nl_result->error_code == '06') {
                $message = "Tên chủ thẻ không hợp lệ";
            }
            if ($nl_result->error_code == '07') {
                $message = "Số tài khoản không hợp lệ";
            }
            if ($nl_result->error_code == '08') {
                $message = "Lỗi kết nối tới ngân hàng. Lỗi xảy ra khi ngân hàng đang bảo trì, nâng cấp mà không
                xuất phát từ merchant";
            }
            if ($nl_result->error_code == '09') {
                $message = "bank_code không hợp lệ";
            }
            if ($nl_result->error_code == '10') {
                $message = "Số dư tài khoản không đủ để thực hiện giao dịch";
            }
            if ($nl_result->error_code == '11') {
                $message = "Mã tham chiếu ( ref_code ) không hợp lệ";
            }
            if ($nl_result->error_code == '12') {
                $message = "Mã tham chiếu ( ref_code ) đã tồn tại";
            }
            if ($nl_result->error_code == '14') {
                $message = "Function không đúng";
            }
            if ($nl_result->error_code == '16') {
                $message = "receiver_email đang bị khóa hoặc phong tỏa không thể giao dịch";
            }
            if ($nl_result->error_code == '17') {
                $message = "account_type không hợp lệ";
            }
            if ($nl_result->error_code == '18') {
                $message = "Ngân hàng đang bảo trì";
            }

            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => !empty($message) ? $message : "giải ngân không thành công 2"
            );
            // $response = array(
            //  'status' => REST_Controller::HTTP_OK,
            //  'message' => !empty($message) ? $message : "giải ngân không thành công 2"
            // );

        }
        if (empty($response)) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Đã duyệt giải ngân, kiểm tra số tiền gửi đi nếu có sai sót sẽ giải ngân lại"
            );
        }
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function approve_accountant_investors_disbursement_nl_max_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //quyền chỉ view kế toán
        if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => array(
                    "message" => "No have access right"
                )
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $response = array();
        $groupRoles = $this->getGroupRole($this->id);
        if ($this->superadmin == false && !in_array('van-hanh', $groupRoles)) {
            // Check access right
            if (!in_array('5def15a268a3ff1204003ad6', $this->roleAccessRights)) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,

                    "message" => "No have access right"

                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }

        // Check old status
        $contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id']), 'status' => array('$ne' => 17)), array("_id", "status", "code_contract", "code_contract_disbursement", "created_by", "store", "receiver_infor", "loan_infor", "customer_infor", "current_address", "houseHold_address", "property_infor", "info_disbursement_max", "megadoc"));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!empty($contract['status']) && !in_array($contract['status'], array(15, 10))) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Trạng thái hợp đồng không sãn sàng để giải ngân"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $chan_bao_hiem = isset($this->dataPost['chan_bao_hiem']) ? (int)$this->dataPost['chan_bao_hiem'] : 2;
        $merchant_id = !empty($this->dataPost['merchant_id']) ? $this->dataPost['merchant_id'] : "";
        $merchant_password = !empty($this->dataPost['merchant_password']) ? $this->dataPost['merchant_password'] : "";
        $receiver_email = !empty($this->dataPost['receiver_email']) ? $this->dataPost['receiver_email'] : "";
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $contract_id = $this->dataPost['contract_id'];
        $typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
        $typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
        $code_contract_disbursement = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : "";
        if (empty($code_contract_disbursement)) {
            $resCodeContract = $this->initContractCode($contract['store']['id'], $typeProperty, $typeLoan);
            $this->dataPost['code_contract_disbursement'] = $resCodeContract['code_contract'];
            $code_contract_disbursement = $resCodeContract['code_contract'];
        }
        $store = !empty($contract['store']) ? $contract['store'] : "";
        $receiver_infor = $contract['receiver_infor'];
        $receiver_infor['order_code'] = $code_contract_disbursement;
        $this->dataPost['receiver_infor'] = $receiver_infor;
        $investorData = $this->investor_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['investor_id'])));
        //  var_dump($investorData); die;
        $contract['investor_code'] = isset($investorData['code']) ? $investorData['code'] : '';
        $percent_interest_investor = floatval($this->dataPost['percent_interest_investor']);
        $this->dataPost['status'] = 17;
        $this->dataPost['status_disbursement'] = 2;
        $this->dataPost['status_create_withdrawal_nl'] = "00";
        $this->dataPost['disbursement_date'] = $this->createdAt;
        $this->dataPost['status_disbursement_max'] = 2;
        $this->contract_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($contract_id)),
            $this->dataPost
        );
        // call ngân lượng giải ngân
        //var_dump($contract['info_disbursement_max']); die;
        if (!empty($contract['info_disbursement_max'])) {
            //var_dump($contract['info_disbursement_max']); die;
            foreach ($contract['info_disbursement_max'] as $key => $dis_max) {

                if ($key == 'total_part') continue;

                if (!isset($dis_max['money'])) continue;

                if (!isset($dis_max['status'])) continue;

                if (empty(($dis_max['status']) || $dis_max['status'] == 2)) continue;

                if (empty(($dis_max['money']))) continue;


                $reason = !empty($this->dataPost['content_transfer']) ? $this->dataPost['content_transfer'] : "";
                if ($contract['receiver_infor']['type_payout'] == 2) {
                    $account_type = 3;
                    $card_fullname = $contract['receiver_infor']['bank_account_holder'];
                    $card_number = $contract['receiver_infor']['bank_account'];
                    $bank_code = $contract['receiver_infor']['bank_id'];
                    $branch_name = $contract['receiver_infor']['bank_branch'];
                }
                //ATM Card Number = 3
                if ($contract['receiver_infor']['type_payout'] == 3) {
                    $account_type = 2;
                    $card_fullname = $contract['receiver_infor']['atm_card_holder'];
                    $card_number = $contract['receiver_infor']['atm_card_number'];
                    $bank_code = $contract['receiver_infor']['bank_id'];
                    $branch_name = "";
                }
                $nlcheckout = new NL_Withdraw($merchant_id, $merchant_password, $receiver_email);
                $nlcheckout->url_api = $this->config->item("NL_WITHDRAW_URL");
                $total_amount = $contract['info_disbursement_max'][$key]['money'];
                $amount_money = $contract['loan_infor']['amount_money'];
                $ref_code = $key . "_" . $contract['code_contract'] . "_" . time();
                $card_month = '';
                $card_year = '';
                $money_ck = isset(divide_amount_money($contract['loan_infor']['amount_loan'])[$key]['money']) ? divide_amount_money($contract['loan_infor']['amount_loan'])[$key]['money'] : 0;
                if ($money_ck != $total_amount) {
                    $response = array(
                        'status' => REST_Controller::HTTP_UNAUTHORIZED,
                        'message' => "Giải ngân không thành công 3"
                    );
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                }
                unset($this->dataPost['contract_id']);
                unset($this->dataPost['percent_interest_investor']);
                unset($this->dataPost['merchant_id']);
                unset($this->dataPost['merchant_password']);
                unset($this->dataPost['receiver_email']);
                $nl_result = $nlcheckout->SetCashoutRequest($ref_code, $total_amount, $account_type, $bank_code, $card_fullname, $card_number, $card_month, $card_year, $branch_name, $reason);
                //Insert log
                $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", " ================= START ======================= ");
                $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", "[INPUT_WD]: ref_code :" . $ref_code . ",total_amount :" . $total_amount . ", account_type:" . $account_type . ",bank_code: " . $bank_code . ",card_fullname:" . $card_fullname . ",card_number:" . $card_number . ",card_month:" . $card_month . ",card_year:" . $card_year . ",branch_name:" . $branch_name . ",reason:" . $reason);
                $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", "[OUTPUT_WD]: nl_result :" . json_encode($nl_result));
                $this->WriteLog("withdrawNganLuong" . date("Ymd", time()) . ".txt", " ================= END ======================= ");


                if ($nl_result->error_code == '00') {
                    if (!empty($nl_result->transaction_status) && $nl_result->transaction_status == '00') {
                        $dataPost = array('info_disbursement_max.' . $key => array(
                            "status" => "2",
                            "money" => $total_amount,
                            "status_create_withdrawal_nl" => '01',
                            "response_get_transaction_withdrawal_status_nl" => $nl_result,
                        ));


                        $this->contract_model->update(
                            array("_id" => new MongoDB\BSON\ObjectId($contract_id)),
                            array("fee.percent_interest_investor" => $percent_interest_investor)
                        );

                        $this->contract_model->update(
                            array("_id" => new MongoDB\BSON\ObjectId($contract_id)),
                            $dataPost
                        );
                        //Insert log
                        $insertLog = array(
                            "type" => "contract",
                            "action" => "accountant_investors_disbursement_max_" . $key,
                            "contract_id" => $contract_id,
                            "old" => $contract,
                            "new" => $this->dataPost,
                            "created_at" => $this->createdAt,
                            "created_by" => $this->uemail
                        );
                        /**
                         * Save log to json file
                         */

                        $insertLogNew = [
                            "type" => "contract",
                            "action" => "accountant_investors_disbursement_max_" . $key,
                            "contract_id" => $contract_id,
                            "created_at" => $this->createdAt,
                            "created_by" => $this->uemail
                        ];
                        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
                        $this->log_model->insert($insertLog);
                        $insertLog['log_id'] = $log_id;

                        $this->insert_log_file($insertLog, $contract_id);

                        /**
                         * ----------------------
                         */

                        $this->log_hs_model->insert($insertLog);

                        //Insert file_manager_qlhs
                        $check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract['store']['id'])));
                        $area = '';
                        if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
                            $area = "MB";
                            $user = $this->quan_ly_ho_so_mb();
                        } else if ($check_area['code_area'] == "KV_MK") {
                            $area = "MK";
                            $user = $this->quan_ly_ho_so_mekong();
                        } else {
                            $area = "MN";
                            $user = $this->quan_ly_ho_so_mn();
                        }

                        $file_manager = [
                            "code_contract_disbursement_text" => $contract['code_contract_disbursement'],
                            "file" => [
                                "Thỏa thuận 3 bên",
                                "Văn bản bàn giao tài sản",
                                "Thông báo",
                                "Đăng ký xe/Cà vẹt"
                            ],
                            "stores" => $contract['store']['id'],
                            "status" => "1",
                            "giay_to_khac" => "",
                            "taisandikem" => "",
                            "ghichu" => "",
                            "area" => $area,
                            "created_at" => $this->createdAt,
                            "created_by" => $contract['created_by'],
                        ];
                        $contractId = $this->file_manager_model->insertReturnId($file_manager);

                        $log = array(
                            "type" => "fileReturn",
                            "action" => "CVKD tạo mới YC",
                            "fileReturn_id" => (string)$contractId,
                            "fileReturn" => $file_manager,
                            "created_at" => $this->createdAt,
                            "created_by" => $contract['created_by']
                        );

                        //Dinos
                        if (!empty($contract['customer_infor']['customer_phone_number'])) {
                            $leadData = $this->lead_model->find_one_check_phone($contract['customer_infor']['customer_phone_number']);
                            if (!empty($leadData[0]['click_id_dinos']) && $leadData[0]['click_id_dinos'] != "") {
                                $status = "approved";
                                $this->api_dinos($leadData[0]['click_id_dinos'], $status);
                            }
                        }

                        $this->log_fileManager_model->insert($log);
                        $fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId((string)$contractId)));
                        $this->sendEmailApprove_qlhs($fileReturn, $user);
                        if (!empty($user)) {
                            foreach (array_unique($user) as $qlhs) {
                                $data_approve = [
                                    'action_id' => (string)$contractId,
                                    'action' => 'FileReturn_create',
                                    'note' => 'Mới',
                                    'user_id' => (string)$qlhs,
                                    'status' => 1, //1: new, 2 : read, 3: block,
                                    'fileReturn_status' => 1,
                                    'created_at' => $this->createdAt,
                                    "created_by" => $contract['created_by']
                                ];
                                $this->borrowed_noti_model->insert($data_approve);
                            }
                        }

                        $response = array(
                            'status' => REST_Controller::HTTP_OK,
                            'message' => "Đã giải ngân thành công",
                            'code' => "00",
                            "data" => $this->dataPost
                        );

                    } else if (!empty($nl_result->transaction_status) && $nl_result->transaction_status == '01') {
                        $this->contract_model->update(
                            array("_id" => new MongoDB\BSON\ObjectId($contract_id)),
                            array("fee.percent_interest_investor" => $percent_interest_investor)
                        );
                        $dataPost = array('info_disbursement_max.' . $key => array(
                            "status" => "1",
                            "money" => $total_amount,
                            "status_create_withdrawal_nl" => '01',
                            "response_get_transaction_withdrawal_status_nl" => $nl_result,
                            "investor_infor" => $investorData

                        ));
                        $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);

                        $response = array(
                            'status' => REST_Controller::HTTP_OK,
                            'code' => "01",
                            'data' => "",
                            'message' => "Đã tạo lệnh giải ngân thành công nhưng đang chờ ngân lượng xử lý"
                        );

                    } else {
                        $dataPost = array('info_disbursement_max.' . $key => array(
                            "status" => "1",
                            "money" => $total_amount,
                            "status_create_withdrawal_nl" => '02',
                            "response_get_transaction_withdrawal_status_nl" => $nl_result,
                        ));
                        $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);

                        $response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            'message' => !empty($nl_result->error_message) ? $nl_result->error_message : "giải ngân không thành công 1"
                        );

                    }
                } else {
                    $dataPost = array('info_disbursement_max.' . $key => array(
                        "status" => "1",
                        "money" => $total_amount,
                        "status_create_withdrawal_nl" => '03',
                        "response_get_transaction_withdrawal_status_nl" => $nl_result

                    ));

                    // $dataPost = array('info_disbursement_max.' . $key => array(
                    //  "status" => "2",
                    //  "money" => $total_amount,
                    //  "status_create_withdrawal_nl" => '03',
                    //  "response_get_transaction_withdrawal_status_nl" => $nl_result

                    // ));
                    $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($contract_id)), $dataPost);

                    $message = "giải ngân không thành công";
                    if ($nl_result->error_code == '99') {
                        $message = "Lỗi không xác định";
                    }
                    if ($nl_result->error_code == '01') {
                        $message = "Merchant không được phép sử dụng phương thức này";
                    }
                    if ($nl_result->error_code == '99') {
                        $message = "Lỗi không xác định";
                    }
                    if ($nl_result->error_code == '02') {
                        $message = "Thông tin thẻ sai định dạng";
                    }
                    if ($nl_result->error_code == '03') {
                        $message = "Thông tin merchant không chính xác";
                    }
                    if ($nl_result->error_code == '04') {
                        $message = "Có lỗi trong quá trình kết nối.";
                    }
                    if ($nl_result->error_code == '05') {
                        $message = "Số tiền không hợp lệ";
                    }
                    if ($nl_result->error_code == '06') {
                        $message = "Tên chủ thẻ không hợp lệ";
                    }
                    if ($nl_result->error_code == '07') {
                        $message = "Số tài khoản không hợp lệ";
                    }
                    if ($nl_result->error_code == '08') {
                        $message = "Lỗi kết nối tới ngân hàng. Lỗi xảy ra khi ngân hàng đang bảo trì, nâng cấp mà không
                xuất phát từ merchant";
                    }
                    if ($nl_result->error_code == '09') {
                        $message = "bank_code không hợp lệ";
                    }
                    if ($nl_result->error_code == '10') {
                        $message = "Số dư tài khoản không đủ để thực hiện giao dịch";
                    }
                    if ($nl_result->error_code == '11') {
                        $message = "Mã tham chiếu ( ref_code ) không hợp lệ";
                    }
                    if ($nl_result->error_code == '12') {
                        $message = "Mã tham chiếu ( ref_code ) đã tồn tại";
                    }
                    if ($nl_result->error_code == '14') {
                        $message = "Function không đúng";
                    }
                    if ($nl_result->error_code == '16') {
                        $message = "receiver_email đang bị khóa hoặc phong tỏa không thể giao dịch";
                    }
                    if ($nl_result->error_code == '17') {
                        $message = "account_type không hợp lệ";
                    }
                    if ($nl_result->error_code == '18') {
                        $message = "Ngân hàng đang bảo trì";
                    }

                    $response = array(
                        'status' => REST_Controller::HTTP_UNAUTHORIZED,
                        'message' => !empty($message) ? $message : "giải ngân không thành công 2"
                    );
                    // $response = array(
                    //  'status' => REST_Controller::HTTP_OK,
                    //  'message' => !empty($message) ? $message : "giải ngân không thành công 2"
                    // );

                }
            }

        }

        $time_GIC_kv = "";
        //gic khoản vay
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '1' && $contract['loan_infor']['amount_GIC'] > 0) {
            $gic = $this->gic_model->findOne(array("contract_id" => (string)$contract['_id']));
            if (empty($gic)) {
                $time_GIC_kv .= time();
                $gic = array();
                if ($chan_bao_hiem == 2)
                    $gic = $this->insert_gic($contract, $code_contract_disbursement, $disbursement_date);
                $time_GIC_kv .= ' - ' . time();
                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $code_contract_disbursement
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty
                    );
                    $this->gic_model->insert($dt_gic);
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $code_contract_disbursement
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty
                    );
                    $this->gic_model->insert($dt_gic);
                }
            }

        }
        $time_MIC_kv = "";
        //mic khoản vay
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '2' && $contract['loan_infor']['amount_MIC'] > 0) {
            $type_mic = "MIC_TDCN";
            $mic_ck = $this->mic_model->findOne(array("contract_id" => (string)$contract['_id'], 'type_mic' => $type_mic));

            if (empty($mic_ck)) {
                $time_MIC_kv .= time();
                $mic = array();
                if ($chan_bao_hiem == 2)
                    $mic = $this->insert_mic($contract, $contract['code_contract_disbursement'], date('d/m/Y'), $ma_cty);
                $time_MIC_kv .= ' - ' . time();

                $this->log_mic($mic->request, $mic->response, $contract['code_contract_disbursement'], $type_mic);
                if ($mic->res != true) {
                    $dt_mic = array(
                        'type_mic' => $type_mic,
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status' => 'deactive'
                    , 'NGAY_HL' => $mic->NGAY_HL
                    , 'NGAY_KT' => $mic->NGAY_KT
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    , "response" => $mic->response
                    , "request" => $mic->request
                    , 'company_code' => $ma_cty
                    );
                    $this->mic_model->insert($dt_mic);

                } else {
                    $mic_data = $mic->data;
                    $dt_mic = array(
                        'type_mic' => $type_mic,
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'mic_gcn' => $mic_data->GCN
                    , 'mic_fee' => $mic_data->PHI
                    , 'NGAY_HL' => $mic->NGAY_HL
                    , 'NGAY_KT' => $mic->NGAY_KT
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status' => 'active'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    , 'company_code' => $ma_cty
                    );
                    $this->mic_model->insert($dt_mic);
                }
            }

        }
        //end mic khoan vay

        //gic plt
        $time_GIC_plt = "";
        if (isset($contract['loan_infor']['code_GIC_plt']) && isset($contract['loan_infor']['amount_GIC_plt']) && in_array($contract['loan_infor']['code_GIC_plt'], array('COPPER', 'SILVER', 'GOLD'))) {

            $gic_plt = $this->gic_plt_model->findOne(array("contract_id" => (string)$contract['_id']));

            if (empty($gic_plt)) {
                $time_GIC_plt .= time();
                $gic = array();

                $gic = $this->insert_gic_plt($contract, $contract['code_contract_disbursement'], date('Y-m-d H:i:s', strtotime("+1 days")));
                $time_GIC_plt .= ' - ' . time();

                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_plt_model->insert($dt_gic);
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_plt_model->insert($dt_gic);
                }
            }

        }


        if (isset($contract['loan_infor']['amount_VBI'])) {
            if ($contract['loan_infor']['amount_VBI'] > 0) {
                $check_vbi = $this->vbi_model->findOne(["code_contract" => $contract['code_contract']]);
                $endDate = strtotime('+1 year', strtotime(date('m/d/Y', $this->createdAt)));
                if (empty($check_vbi)) {
                    $dt_vbi = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract' => $contract['code_contract']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'status_vbi' => '1'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    , 'endDate' => $endDate
                    ,'company_code' => $ma_cty

                    );


                    $code_contract = $contract['code_contract'];
                    $status_vbi1 = $contract['loan_infor']['maVBI_1'];
                    if (is_numeric($status_vbi1)) {
                        if ($status_vbi1 <= 6) {
                            $call_vbi = $this->insert_sxh_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi1);
                            if ($call_vbi->status != 200) {
                                $dt_vbi['type'] = 'VBI_SXH';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        } else {
                            $call_vbi = $this->insert_utv_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi1);
                            if ($call_vbi->status != 200) {
                                $dt_vbi['type'] = 'VBI_UTV';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        }
                    }
                    $status_vbi2 = $contract['loan_infor']['maVBI_2'];
                    if (is_numeric($status_vbi2)) {
                        if ($status_vbi2 <= 6) {
                            $call_vbi2 = $this->insert_sxh_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi2);
                            if ($call_vbi2->status != 200) {
                                $dt_vbi['type'] = 'VBI_SXH';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        } else {
                            $call_vbi2 = $this->insert_utv_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi2);
                            if ($call_vbi2->status != 200) {
                                $dt_vbi['type'] = 'VBI_UTV';
                                $this->vbi_model->insert($dt_vbi);
                            }
                        }
                    }

                }
            }
        }
        //bh tnds
        if (!empty($contract['loan_infor']['bao_hiem_tnds'])) {
            if (!empty($contract['loan_infor']['bao_hiem_tnds']['type_tnds'])) {
                $check_tnds = $this->contract_tnds_model->findOne(["code_contract" => $contract['code_contract']]);

                if (empty($check_tnds)) {

                    if ($contract['loan_infor']['bao_hiem_tnds']['type_tnds'] == 'MIC_TNDS') {
                        $res = $this->call_mic_tnds($contract);
                    } else {
                        $res = $this->call_vbi_tnds($contract);
                    }

                    $data_tnds = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract' => $contract['code_contract']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'data' => $res
                    , 'created_at' => $this->createdAt
                    , 'created_by' => $this->uemail
                    ,'company_code' => $ma_cty
                    );
                    $this->contract_tnds_model->insert($data_tnds);
                }
            }
        }
        //gic pti vta
        if (isset($contract['loan_infor']['bao_hiem_pti_vta']) && in_array($contract['loan_infor']['bao_hiem_pti_vta']['code_pti_vta'], array('G1', 'G2', 'G3')) && in_array($contract['loan_infor']['bao_hiem_pti_vta']['year_pti_vta'], array('1Y', '6M', '3M')) && $contract['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] > 0) {

            $pti_vta = $this->pti_vta_bn_model->findOne(array("code_contract" => $contract['code_contract']));
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            if (empty($pti_vta)) {
                $pti = array();

                $pti = $this->insert_pti_vta($contract, date('d-m-Y', strtotime("+1 days")), $contract['code_contract']);
                if ($pti->success != true) {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'code_contract' => $contract['code_contract']
                    , 'pti_code' => ""
                    , 'contract_info' => $contract
                    , 'pti_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'type_pti' => 'HD'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->pti_vta_bn_model->insert($dt_pti);
                } else {
                    $pti_dt = $pti->data;
                    $request = $pti->request;
                    $NGAY_KT = $pti->NGAY_KT;
                    $NGAY_HL = $pti->NGAY_HL;
                    $number_item = $pti->number_item;
                    $type_pti = "PTI_VTA";
                    $code_pti_vta = $type_pti . '_' . date("dmY") . "_" . time();
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'code_pti_vta' => $code_pti_vta
                    , 'code_contract' => $contract['code_contract']
                    , 'pti_code' => !empty($request->so_hd) ? $request->so_hd : ''
                    , 'contract_info' => $contract
                    , 'so_xac_minh' => !empty($request->so_cmt) ? $request->so_cmt : ''
                    , 'request' => $request
                    , 'pti_info' => $pti_dt
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'NGAY_KT' => $NGAY_KT
                    , 'NGAY_HL' => $NGAY_HL
                    , 'number_item' => (int)$number_item
                    , 'status' => 1
                    , 'erro_info' => '-'
                    , 'type_pti' => 'HD'
                    , 'created_at' => $this->createdAt
                    , 'created_by' => "superadmin"
                    ,'company_code' => $ma_cty

                    );
                    $this->pti_vta_bn_model->insert($dt_pti);
                }
            }

        }
        // PTI BH Tai Nan Con Nguoi
        if (
            isset($contract['loan_infor']['pti_bhtn']['goi']) && 
            isset($contract['loan_infor']['pti_bhtn']['phi']) &&
            isset($contract['loan_infor']['pti_bhtn']['price'])
        ) {
            $pti_vta = $this->pti_bhtn_model->findOne(array("code_contract" => $contract['code_contract']));
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            if (empty($pti_vta)) {
                $pti = $this->insert_pti_bhtn($contract);
                if ($pti->success != true) {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                        , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                        , 'code_contract' => $contract['code_contract']
                        , 'pti_request' => $pti->request
                        , 'pti_info' => $pti->info
                        , 'store' => $store
                        , 'status' => 'errors'
                        , 'created_at' => $this->createdAt
                        , 'created_by' => "superadmin"
                        , 'type' => "HD"
                    );
                } else {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                        , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                        , 'code_contract' => $contract['code_contract']
                        , 'pti_request' => $pti->request
                        , 'pti_info' => $pti->info
                        , 'store' => $store
                        , 'status' => 'success'
                        , 'created_at' => $this->createdAt
                        , 'created_by' => "superadmin"
                        , 'type' => "HD"
                    );
                }
                $this->pti_bhtn_model->insert($dt_pti);
            }
        }
        //Insert log
        $this->WriteLog("BAOHIEM" . date("Ymd", time()) . ".txt", " ================= START ======================= ");
        $this->WriteLog("BAOHIEM" . date("Ymd", time()) . ".txt", "time_GIC_kv :" . $time_GIC_kv . ", time_GIC_plt:" . $time_GIC_plt . ",time_MIC_kv: " . $time_MIC_kv . ",code_contract:" . $contract['code_contract']);
        $this->WriteLog("BAOHIEM" . date("Ymd", time()) . ".txt", " ================= END ======================= ");
        if (empty($response)) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Đã duyệt giải ngân, kiểm tra số tiền gửi đi nếu có sai sót sẽ giải ngân lại"
            );
        }
        //end gic plt
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_all_for_quickloan_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $type = 'vaynhanh';
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
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
        if (!empty($type)) {
            $condition['type'] = $type;
        }
        if (!empty($status)) {
            $condition['status'] = (int)$status;
        }
        if (!empty($condition)) {
            $contract = $this->contract_model->getQuickLoanByRole($condition);
        } else {
            $contract = $this->contract_model->find();
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_count_all_post()
    {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $this->dataPost = $this->input->post();
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $property = !empty($this->dataPost['property']) ? $this->dataPost['property'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        $type = !empty($this->dataPost['type_ct']) ? $this->dataPost['type_ct'] : "";
        $store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
        $asset_name = !empty($this->dataPost['asset_name']) ? $this->dataPost['asset_name'] : "";
        $customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";
        $type_contract = !empty($this->dataPost['type_contract']) ? $this->dataPost['type_contract'] : "";
        $search_htv = !empty($this->dataPost['search_htv']) ? $this->dataPost['search_htv'] : "";
        $search_status = !empty($this->dataPost['search_status']) ? $this->dataPost['search_status'] : "";
        $phone_number_relative = !empty($this->dataPost['phone_number_relative']) ? $this->dataPost['phone_number_relative'] : "";
        $fullname_relative = !empty($this->dataPost['fullname_relative']) ? $this->dataPost['fullname_relative'] : "";
        $type_contract_digital = !empty($this->dataPost['type_contract_digital']) ? $this->dataPost['type_contract_digital'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        if (in_array('quan-ly-khu-vuc', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
            $all = true;
        } else if (in_array('giao-dich-vien', $groupRoles)) {
            $condition['created_by'] = $this->uemail;
        }

        if ($all) {
            $stores = $this->getStores($this->id);
            if (empty($stores)) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'data' => array()
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            $condition['stores'] = $stores;
        }
        if (!empty($asset_name)) {
            $condition['asset_name'] = $asset_name;
        }
        if (!empty($search_status)) {
            $condition['search_status'] = $search_status;
        }

        if (!empty($property)) {
            $condition['property'] = $property;
        }
        if (!empty($type)) {
            $condition['type'] = $type;
        }
        if (!empty($type_contract)) {
            $condition['type_contract'] = $type_contract;
        }
        if (!empty($status)) {
            $condition['status'] = (int)$status;
        }
        if (!empty($code_contract)) {
            $condition['code_contract'] = $code_contract;
        }
        if (!empty($search_htv)) {
            $condition['search_htv'] = $search_htv;
        }
        // if (!empty($code_contract_disbursement)) {
        //  $code_contract_disbursement = explode(",", (string)$code_contract_disbursement);
        //  $condition['code_contract_disbursement'] = $code_contract_disbursement;
        // }
        if (!empty($code_contract_disbursement)) {

            $condition['code_contract_disbursement'] = $code_contract_disbursement;
        }
        if (!empty($phone_number_relative)) {
            $condition['phone_number_relative'] = $phone_number_relative;
        }
        if (!empty($fullname_relative)) {
            $condition['fullname_relative'] = $fullname_relative;
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = $customer_name;
        }
        if (!empty($customer_phone_number)) {
            $customer_phone_number = explode(",", (string)$customer_phone_number);
            $condition['customer_phone_number'] = $customer_phone_number;
        }
        if (!empty($type_contract_digital)) {
            $condition['type_contract_digital'] = $type_contract_digital;
        }
        if (!empty($store)) {
            $condition['store'] = $store;
            unset($condition['stores']);

        }
        if (!empty($customer_identify)) {
            $customer_identify = explode(",", (string)$customer_identify);
            $condition['customer_identify'] = $customer_identify;
        }
        if (!empty($condition)) {
            $contract = $this->contract_model->getCountContractByRole($condition);
        } else {
            $contract = $this->contract_model->countContract();
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_all_data_post()
    {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $this->dataPost = $this->input->post();
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $property = !empty($this->dataPost['property']) ? $this->dataPost['property'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        $customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $ngaygiaingan = !empty($this->dataPost['ngaygiaingan']) ? $this->dataPost['ngaygiaingan'] : "1";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $type_contract = !empty($this->dataPost['type_contract']) ? $this->dataPost['type_contract'] : "";
        $customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
        $type = !empty($this->dataPost['type_ct']) ? $this->dataPost['type_ct'] : "";
        $store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
        $asset_name = !empty($this->dataPost['asset_name']) ? $this->dataPost['asset_name'] : "";
        $search_htv = !empty($this->dataPost['search_htv']) ? $this->dataPost['search_htv'] : "";
        $is_export = !empty($this->dataPost['is_export']) ? $this->dataPost['is_export'] : "";
        $search_status = !empty($this->dataPost['search_status']) ? $this->dataPost['search_status'] : "";
        $phone_number_relative = !empty($this->dataPost['phone_number_relative']) ? $this->dataPost['phone_number_relative'] : "";
        $fullname_relative = !empty($this->dataPost['fullname_relative']) ? $this->dataPost['fullname_relative'] : "";
        $type_contract_digital = !empty($this->dataPost['type_contract_digital']) ? $this->dataPost['type_contract_digital'] : "";


        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        if (in_array('quan-ly-khu-vuc', $groupRoles) ||  in_array('cua-hang-truong', $groupRoles)) {
            $all = true;
        } else if (in_array('giao-dich-vien', $groupRoles)) {
            $condition['created_by'] = $this->uemail;
        }

        if ($all) {
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
        if (!empty($search_status)) {
            $condition['search_status'] = $search_status;
        }

        if (!empty($asset_name)) {
            $condition['asset_name'] = $asset_name;
        }
        if (!empty($property)) {
            $condition['property'] = $property;
        }
        if (!empty($type)) {
            $condition['type'] = $type;
        }
        if (!empty($status)) {
            $condition['status'] = (int)$status;
        }
        if (!empty($customer_identify)) {
            $customer_identify = explode(",", (string)$customer_identify);
            $condition['customer_identify'] = $customer_identify;
        }
        if (!empty($code_contract)) {
            $condition['code_contract'] = $code_contract;
        }
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = $code_contract_disbursement;
        }
        if (!empty($phone_number_relative)) {
            $condition['phone_number_relative'] = $phone_number_relative;
        }
        if (!empty($fullname_relative)) {
            $condition['fullname_relative'] = $fullname_relative;
        }

        // if (!empty($code_contract_disbursement)) {
        //  $code_contract_disbursement = explode(",", $code_contract_disbursement);
        //  $condition['code_contract_disbursement'] = $code_contract_disbursement;
        // }


        if (!empty($customer_name)) {
            $condition['customer_name'] = $customer_name;
        }
        // if (!empty($customer_phone_number)) {
        //  $condition['customer_phone_number'] = $customer_phone_number;
        // }

        if (!empty($customer_phone_number)) {
            $customer_phone_numbers = explode(",", (string)$customer_phone_number);
            $condition['customer_phone_number'] = $customer_phone_numbers;
        }
        if (!empty($ngaygiaingan)) {
            $condition['ngaygiaingan'] = $ngaygiaingan;
        }
        if (!empty($search_htv)) {
            $condition['search_htv'] = $search_htv;
        }
        if (!empty($is_export)) {
            $condition['is_export'] = $is_export;
        }

        if (!empty($store)) {
            $condition['store'] = $store;
            unset($condition['stores']);

        }
        if (!empty($type_contract)) {
            $condition['type_contract'] = $type_contract;
        }
        if (!empty($type_contract_digital)) {
            $condition['type_contract_digital'] = $type_contract_digital;
        }
        $per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
        $uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
        if (!empty($condition)) {
//          $so_luong_tong=0;
            if (!empty($this->dataPost['is_export'])) {
                $contract = $this->contract_model->getContractPaginationByRole($condition, $per_page, $uriSegment);

            }else{
            $contract = $this->contract_model->getContractPaginationByRole($condition, $per_page, $uriSegment);
//          $so_luong_tong = $this->contract_model->getCountContractByRole($condition);

//          $count = [];
//          if (!empty($condition['status'])) {
//              foreach (contract_status() as $k => $v) {
//                  if ($k == $condition['status']) {
//                      $count[$v] = $so_luong_tong;
//                  } else {
//                      $count[$v] = 0;
//                  }
//              }
//          } else {
//              foreach (contract_status() as $k => $v) {
//                  $condition['find_status'] = $k;
//                  $count[$v] = $this->contract_model->getCountByStatus($condition);
//              }
//          }
           }
        } else {
            $contract = $this->contract_model->findContractPagination($per_page, $uriSegment);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
//          'count' => $count
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_contract_by_login_user_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $contract = $this->contract_model->find_where(array("created_by" => $this->uemail));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_all_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $this->dataPost = $this->input->post();
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();

        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $property = !empty($this->dataPost['property']) ? $this->dataPost['property'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
            $all = true;
        } else if (in_array('giao-dich-vien', $groupRoles)) {
            $condition['created_by'] = $this->uemail;
        }
        if ($all) {
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
        if (!empty($property)) {
            $condition['property'] = $property;
        }
        if (!empty($status)) {
            $condition['status'] = (int)$status;
        }
        if (!empty($condition)) {
            $contract = $this->contract_model->getContractByRole($condition);
        } else {
            $contract = $this->contract_model->find();
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_one_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $groupRoles = $this->getGroupRole($this->id);
        $this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }

        /**
        * Get VPBank Vitual Account Number
        */
        if (!empty($contract['code_contract'])) {
            $vpbank = new VPBank();
            $assignVan = $vpbank->assignVan($contract['code_contract']);
            $contract['vpbank_van']["bank_name"] = isset($assignVan["bankName"]) ? $assignVan["bankName"] : "";
            $contract['vpbank_van']["master_account_name"] = isset($assignVan["masterAccountName"]) ? $assignVan["masterAccountName"] : "";
            $contract['vpbank_van']["van"] = isset($assignVan["van"]) ? $assignVan["van"] : "";
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'groupRoles' => $groupRoles
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_one_by_code_contract_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        $this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
        $contract = $this->contract_model->findOne(array("code_contract" => $this->dataPost['code_contract']));
        /**
        * Get VPBank Vitual Account Number
        */
        if (!empty($contract['code_contract'])) {
            $vpbank = new VPBank();
            $assignVan = $vpbank->assignVan($contract['code_contract']);
            $contract['vpbank_van']["bank_name"] = isset($assignVan["bankName"]) ? $assignVan["bankName"] : "";
            $contract['vpbank_van']["master_account_name"] = isset($assignVan["masterAccountName"]) ? $assignVan["masterAccountName"] : "";
            $contract['vpbank_van']["van"] = isset($assignVan["van"]) ? $assignVan["van"] : "";
        }

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,

        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function upload_image_contract_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id'] = $this->security->xss_clean($data['id']);
        $data['image_accurecy'] = $this->security->xss_clean($data['image_accurecy']);
        $dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
        if (empty($dataDB)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $data['image_accurecy']['expertise'] = (array)$dataDB['image_accurecy']['expertise'];
        $data['image_accurecy']['extension'] = (array)$dataDB['image_accurecy']['extension'];
        $this->contract_model->update(
            array("_id" => new \MongoDB\BSON\ObjectId($data['id'])),
            array("image_accurecy" => $data['image_accurecy'])
        );

        //Log update chứng từ
        $insertLog = array(
            "type" => "contract",
            "action" => "update_image",
            "contract_id" => (string)$this->dataPost['id'],
            "old" => $dataDB['image_accurecy'],
            "new" => $data['image_accurecy'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($insertLog);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "lưu thành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function upload_image_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id'] = $this->security->xss_clean($data['id']);
        $data['type_img'] = $this->security->xss_clean($data['type_img']);
        $data['file'] = $this->security->xss_clean($data['file']);

        if ($data['file']['size'] > 10000000) {
            return $this->pushJson('200', json_encode(array(
                'code' => '201',
                'msg' => 'Kích cỡ max là 10MB'
            )));
        }
        $acceptFormat = array("jpeg", "png", "jpg", "mp3", "mp4");
        if (in_array($data['file']['type'], $acceptFormat)) {
            return $this->pushJson('200', json_encode(array(
                'code' => '201',
                'msg' => 'Định dạng không cho phép',
                'type' => $data['file']['type']
            )));
        }
        $dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
        if (empty($dataDB)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $cfile = new CURLFile($data['file']["tmp_name"], $data['file']["type"], $data['file']["name"]);

        $push_upload = $this->pushUpload($cfile);
        if ($push_upload->code == 200) {
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
                array("image_accurecy." . $data['type_img'] => $dataDB['image_accurecy'][$data['type_img']])
            );
            //update tai san
//          $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
//          $asset = $this->asset_management_model->findOne(['asset_code' => $contract['asset_code']]);
//          $image = !empty($asset['image']) ? $asset['image'] : [];
//          $image_driver_license = !empty($contract['image_accurecy']['driver_license']) ? $contract['image_accurecy']['driver_license'] : [];
//          $image1 = [];
//          foreach ($image as $key => $value) {
//              $image1[$key] = $value;
//          }
//          $driver_license = [];
//          foreach ($image_driver_license as $k => $value) {
//              $driver_license[$k] = $value;
//          }
//          $this->asset_management_model->update(['asset_code' => $contract['asset_code']], ['image' => (object)array_merge($driver_license, $image1)]);
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

    public function get_image_accurecy_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id'] = $this->security->xss_clean($data['id']);
        $dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
        if (empty($dataDB)) {
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

    public function get_image_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id'] = $this->security->xss_clean($data['id']);
        $dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
        if (empty($dataDB)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $dataDB
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }


    public function delete_image_post()
    {
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
        if (empty($dataDB)) {
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
            array("image_accurecy." . $data['type_img'] => $arrImg)
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

    private function pushUpload($cfile)
    {
        $serviceUpload = $this->config->item("url_service_upload");
        // $curlFile  = new CURLFile($_FILES["avatar"]["tmp_name"],$_FILES["avatar"]["type"],$_FILES["avatar"]["name"]);
        $post = array('avatar' => $cfile);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $serviceUpload);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result1 = json_decode($result);
        return $result1;
    }

    public function checkNull()
    {
        $res = array(
            "status" => 1,
            "message" => ""
        );

        //Check null mục thông tin phong giao dich
        if (empty($this->dataPost['store']) || empty($this->dataPost['store']['id'])
            || empty($this->dataPost['store']['name'])
            || empty($this->dataPost['store']['address'])) {
            $res = array(
                "status" => 2,
                "message" => "Điền đầy đủ mục thông tin phòng giao dịch"
            );
        }

        //Check null mục thông tin chuyển khoản
        if (empty($this->dataPost['receiver_infor']) || empty($this->dataPost['receiver_infor']['type_payout'])
            || empty($this->dataPost['receiver_infor']['amount'])
            || empty($this->dataPost['receiver_infor']['bank_id'])) {
            $res = array(
                "status" => 2,
                "message" => "Điền đầy đủ mục thông tin chuyển khoản"
            );
        }
        if (!empty($this->dataPost['receiver_infor']['type_payout'])) {
            if ($this->dataPost['receiver_infor']['type_payout'] == 2 && (empty($this->dataPost['receiver_infor']['bank_account']) || empty($this->dataPost['receiver_infor']['bank_account_holder']) || empty($this->dataPost['receiver_infor']['bank_branch']))) {
                $res = array(
                    "status" => 2,
                    "message" => "Điền đầy đủ mục thông tin chuyển khoản"
                );
            }
            if ($this->dataPost['receiver_infor']['type_payout'] == 3 && (empty($this->dataPost['receiver_infor']['atm_card_number']) || empty($this->dataPost['receiver_infor']['atm_card_holder']))) {
                $res = array(
                    "status" => 2,
                    "message" => "Điền đầy đủ mục thông tin chuyển khoản"
                );
            }
        }

        //Check null mục thông tin khách hàng
        if (empty($this->dataPost['customer_infor']) || empty($this->dataPost['customer_infor']['customer_name'])
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
        if (empty($this->dataPost['current_address']) || empty($this->dataPost['current_address']['province'])
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
        if (empty($this->dataPost['houseHold_address']) || empty($this->dataPost['houseHold_address']['province'])
            || empty($this->dataPost['houseHold_address']['district'])
            || empty($this->dataPost['houseHold_address']['ward'])
            || empty($this->dataPost['houseHold_address']['address_household'])) {
            $res = array(
                "status" => 2,
                "message" => "Điền đầy đủ mục địa chỉ hộ khẩu"
            );
        }
        //Check null mục Thông tin việc làm

        if (empty($this->dataPost['job_infor']) || empty($this->dataPost['job_infor']['phone_number_company'])
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
                'data111' => $this->dataPost
            );

        }
        //Check null mục Thông tin người thân
        if (empty($this->dataPost['relative_infor']) || empty($this->dataPost['relative_infor']['type_relative_1'])
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
        if (empty($this->dataPost['loan_infor']) || empty($this->dataPost['loan_infor']['type_loan'])
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
        if (!empty($this->dataPost['property_infor'])) {
            foreach ($this->dataPost['property_infor'] as $item) {
                if (empty($item['value'])) {
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
        if (empty($this->dataPost['expertise_infor']) || empty($this->dataPost['expertise_infor']['expertise_file'])
            || empty($this->dataPost['expertise_infor']['expertise_field'])) {
            $res = array(
                "status" => 2,
                "message" => "Điền đầy đủ mục thông tin thẩm định",
            );
        }
        return $res;
    }

    private function checkSendEmailForNewCustomer($type = "")
    {
        $res = array(
            "status" => 1,
            "message" => "false"
        );
        if ($this->dataPost['customer_infor']['status_customer'] == 1) {
            $condition = array(
                '$or' => array(
                    array('email' => $this->dataPost['customer_infor']['customer_email']),
                    array('identify' => $this->dataPost['customer_infor']['customer_identify'])
                    // array('phone_number' => $this->dataPost['customer_infor']['customer_phone_number'])
                )
            );
            $count = $this->user_model->count($condition);
            if ($count == 0) {
                //Create account for customer
                $password_root = rand(100000, 999999);
                $hash_password = password_hash($password_root, PASSWORD_BCRYPT);
                $tokenActive = password_hash($hash_password, PASSWORD_BCRYPT);
                $urlActive = $this->config->item("cpanel_url") . '/user/activeAccount?token=' . $tokenActive;
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
                if (!empty($roleCustomer)) {
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
                    "message" => "Email hoặc CMND khách hàng đã tồn tại",
                    'data' => $count
                );
            }
        }
        return $res;
    }

    private function initNumberContractCode()
    {
        $maxNumber = $this->contract_model->getMaxNumberContract();
        $maxNumberContract = !empty($maxNumber[0]['number_contract']) ? (float)$maxNumber[0]['number_contract'] + 1 : 1;
        $res = array(
            "max_number_contract" => $maxNumberContract
        );
        return $res;
    }

    private function initContractCode($store_id, $typeProperty, $typeLoan)
    {
        $res = array(
            "code_contract" => "",
            // "max_number_contract" => ""
        );

        //HD_CAMCO_XEMAY_000001
        $store_info = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($store_id)));
        $code_province_store = !empty($store_info['code_province_store']) ? $store_info['code_province_store'] : "";
        $code_address_store = !empty($store_info['code_address_store']) ? $store_info['code_address_store'] : "";

        if ($typeLoan == 'CC') {
            $type_loan_property = $typeProperty;
        } else {
            $type_loan_property = "ĐK" . $typeProperty;
        }
        $disbursement_date = !empty($this->dataPost['disbursement_date']) ? (int)$this->dataPost['disbursement_date'] : "";

        if (!empty($disbursement_date)) {
            $mydate = getdate($disbursement_date);
        } else {
            $mydate = getdate(date("U"));
        }
        $number = '';
        $first_date = date('01-m-Y');
        $timestamp = strtotime($first_date);
        $time['start'] = $timestamp;
        $time['end'] = time();
        $count = $this->contract_model->countContractActivebyTime($time, $store_id);
        if ($count == 0) {
            $number = '01';
        } elseif ($count > 0 && ($count + 1) < 10) {
            $number = '0' . ($count + 1);
        } else {
            $number = $count + 1;
        }
        $year = substr($mydate['year'], -2);
        if (intval($mydate['mon']) < 10) {
            $mydate['mon'] = '0' . $mydate['mon'];
        }
        $codeContract = "HĐCC/" . $type_loan_property . '/' . $code_province_store . $code_address_store . '/' . $year . $mydate['mon'] . '/' . $number;
        $inforDB = $this->contract_model->findOne(array("code_contract_disbursement" => $codeContract));
        if (!empty($inforDB)) {
            while ($number <= 200) {
                $number++;
                $codeContract = "HĐCC/" . $type_loan_property . '/' . $code_province_store . $code_address_store . '/' . $year . $mydate['mon'] . '/' . $number;
                $contractDB = $this->contract_model->findOne(array("code_contract_disbursement" => $codeContract));
                if (empty($contractDB)) {
                    break;
                }
            }
        }

        $res = array(
            "code_contract" => $codeContract,
            // "max_number_contract" => $maxNumberContract
        );
        return $res;
    }

    public function contract_tempo_all_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $this->dataPost = $this->input->post();
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $condition = array();
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
        $contract = array();
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = $code_contract_disbursement;
        }
        if (!empty($code_contract)) {
            $condition['code_contract'] = $code_contract;
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = $customer_name;
        }
        $contract = $this->contract_model->getRemind_debt_first($condition, $per_page, $uriSegment);
        $condition['total'] = true;
        $total = $this->contract_model->getRemind_debt_first($condition, $per_page, $uriSegment);
        if (!empty($contract)) {
            foreach ($contract as $key => $c) {
                $c['investor_name'] = "";
                if (isset($c['investor_code'])) {
                    $investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
                    $c['investor_name'] = $investors['name'];
                }
                $cond = array(
                    'code_contract' => $c['code_contract'],
                    'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
                );
                $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                $c['detail'] = array();
                if (!empty($detail)) {
                    $total_paid = 0;
                    foreach ($detail as $de) {

                        $total_paid = $total_paid + $de['tien_tra_1_ky'];
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

                        $c['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
                    }
                }


            }
        }


        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'total' => $total
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }


    public function total_contract_tempo_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->dataPost = $this->input->post()['condition'];
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $store_id = !empty($this->dataPost['store_id']) ? $this->dataPost['store_id'] : "";
        $bucket = !empty($this->dataPost['bucket']) ? $this->dataPost['bucket'] : "";
        $investor_code = !empty($this->dataPost['investor_code']) ? $this->dataPost['investor_code'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "17";
        $status_disbursement = !empty($this->dataPost['status_disbursement']) ? $this->dataPost['status_disbursement'] : "";
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        if (!empty($status)) {
            $condition['status'] = $status;
        }
        if (!empty($status_disbursement)) {
            $condition['status_disbursement'] = $status_disbursement;
        }
        if (!empty($bucket)) {
            $condition['bucket'] = $bucket;
        }
        if (!empty($investor_code)) {
            $condition['investor_code'] = $investor_code;
        }
        if (!empty($store_id)) {
            $condition['store'] = $store_id;
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = $customer_name;
        }
        if (!empty($$customer_phone_number)) {
            $condition['$customer_phone_number'] = $customer_phone_number;
        }
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = $code_contract_disbursement;
        }
        $contract = array();
        if (!empty($condition)) {
            $contract = $this->contract_model->getTotalContractByTime(array(), $condition);
        } else {
            $contract = $this->contract_model->getTotalContractByTime(array(), $condition);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function contract_tempo_post()
    {
//      $flag = notify_token($this->flag_login);
//      if ($flag == false) return;
        // date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->dataPost = $this->input->post()['condition'];
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $store_id = !empty($this->dataPost['store_id']) ? $this->dataPost['store_id'] : "";
        $id_card = !empty($this->dataPost['id_card']) ? $this->dataPost['id_card'] : "";
        $bucket = !empty($this->dataPost['bucket']) ? $this->dataPost['bucket'] : "";
        $investor_code = !empty($this->dataPost['investor_code']) ? $this->dataPost['investor_code'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        $status_disbursement = !empty($this->dataPost['status_disbursement']) ? $this->dataPost['status_disbursement'] : "";
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract_disbursement'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
        $vung_mien = !empty($this->dataPost['vung_mien']) ? $this->dataPost['vung_mien'] : "";
        $van = !empty($this->dataPost['van']) ? $this->dataPost['van'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        if (!empty($status)) {
            $condition['status'] = $status;
        }
        if (!empty($status_disbursement)) {
            $condition['status_disbursement'] = $status_disbursement;
        }

        if (!empty($investor_code)) {
            $condition['investor_code'] = $investor_code;
        }
        if (!empty($store_id)) {
            $condition['store'] = $store_id;
        }

        if (!empty($id_card)) {
            $condition['id_card'] = $id_card;
        }
        if (!empty($bucket)) {
            $result = $this->getConditionBucket($bucket);
            $condition['fBucket'] = $result['fBucket'];
            $condition['tBucket'] = $result['tBucket'];
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = trim($customer_name);
        }
        if (!empty($customer_phone_number)) {
            $condition['customer_phone_number'] = trim($customer_phone_number);
        }
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = trim($code_contract_disbursement);
        }
        if (!empty($code_contract)) {
            $condition['code_contract'] = trim($code_contract);
        }
        if (!empty($vung_mien)) {
            $pgd = $this->getPgdToVungMien($vung_mien);
            $condition['store_vung'] = $pgd;
        }
        if (!empty($van)) {
            $vfcpayment = new VFCPayment();
            $getContractId = $vfcpayment->getAllContractsbyVan($van);
            if ($getContractId) {
                $condition['code_contracts'] = $getContractId;
            } else {
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'data' => array()
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }

        $groupRoles = $this->getGroupRole($this->id);

        $all = false;
        if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles) || in_array('giao-dich-vien', $groupRoles)) {
            $all = true;
        }
        if (!empty($code_contract_disbursement) || !empty($customer_name) || !empty($customer_phone_number)) {
            $all = false;
        }
        if ($all) {
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
        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

        $contract = array();
        $contract = $this->contract_model->getContractByTime(array(), $condition, $per_page, $uriSegment);
        $total = $this->contract_model->getContractByTimeAll(array(), $condition);


        if (!empty($contract)) {
            foreach ($contract as $key => $c) {
                $cond = array();
                $c['investor_name'] = "";
                if (isset($c['investor_code'])) {
                    $investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
                    $c['investor_name'] = $investors['name'];
                }
                if (isset($c['code_contract'])) {
                    $cond = array(
                        'code_contract' => $c['code_contract'],
                        'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
                    );
                }
                $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                $c['detail'] = array();
                if (!empty($detail)) {
                    $total_paid = 0;
                    $total_phi_phat_cham_tra = 0;
                    $total_da_thanh_toan = 0;
                    foreach ($detail as $de) {

                        $total_paid = $total_paid + $de['tien_tra_1_ky'];
                        $total_phi_phat_cham_tra += $de['penalty'];
                        $total_da_thanh_toan += $de['da_thanh_toan'];
                    }
                    $c['detail'] = $detail[0];
                    $c['detail']['total_paid'] = $total_paid;
                    $c['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
                    $c['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
                } else {
                    $condition_new = array(
                        'code_contract' => $c['code_contract'],
                        'status' => 1
                    );
                    $detail_new = $this->contract_tempo_model->getContract($condition_new);
                    if (!empty($detail_new)) {
                        $c['detail'] = $detail_new[0];

                        $c['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
                        $c['detail']['total_phi_phat_cham_tra'] = $detail_new[0]['penalty'];
                        $c['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'];
                    }
                }
                $tempo = $this->contract_tempo_model->find_where(['code_contract' => $c['code_contract'], 'status' => 1]);
                $c['lai_ki'] = $tempo[0];
                $so_ki_thanh_toan = $this->contract_tempo_model->count(['code_contract' => $c['code_contract'], 'status' => 2]);
                $c['so_ki_thanh_toan'] = $so_ki_thanh_toan;

            }

        }


        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'total' => $total
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function contract_tempo_by_user_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $condition['status'] = 17;
        $condition['status_disbursement'] = 3;
        $condition['created_by'] = $this->uemail;
//      $condition['created_by'] = "loannth@tienngay.vn";
        $contract = $this->contract_model->getContractByUser($condition);
        if (!empty($contract)) {
            foreach ($contract as $key => $c) {
                $cond = array();
                $c['investor_name'] = "";
                if (isset($c['investor_code'])) {
                    $investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
                    $c['investor_name'] = $investors['name'];
                }
                if (isset($c['code_contract'])) {
                    $cond = array(
                        'code_contract' => $c['code_contract'],
                        'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
                    );
                }
                $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                $c['detail'] = array();
                if (!empty($detail)) {
                    $total_paid = 0;
                    $total_phi_phat_cham_tra = 0;
                    $total_da_thanh_toan = 0;
                    foreach ($detail as $de) {
                        $total_paid = $total_paid + $de['tien_tra_1_ky'];
                        $total_phi_phat_cham_tra += $de['penalty'];
                        $total_da_thanh_toan += $de['da_thanh_toan'];
                    }
                    $c['detail'] = $detail[0];
                    $c['detail']['total_paid'] = $total_paid;
                    $c['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
                    $c['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
                } else {
                    $condition_new = array(
                        'code_contract' => $c['code_contract'],
                        'status' => 1
                    );
                    $detail_new = $this->contract_tempo_model->getContract($condition_new);
                    if (!empty($detail_new)) {
                        $c['detail'] = $detail_new[0];

                        $c['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
                        $c['detail']['total_phi_phat_cham_tra'] = $detail_new[0]['penalty'];
                        $c['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'];
                    }
                }
                $time = 0;
                if (!empty($c['detail']) && $c['detail']['status'] == 1) {
                    $current_day = strtotime(date('m/d/Y'));
                    $datetime = !empty($c['detail']['ngay_ky_tra']) ? intval($c['detail']['ngay_ky_tra']) : $current_day;
                    $time = intval(($current_day - $datetime) / (24 * 60 * 60));
                    if ($time <= 0) {
                        $c['bucket'] = 'B0';
                    } else if ($time >= 1 && $time <= 30) {
                        $c['bucket'] = 'B1';
                    } else if ($time > 30 && $time <= 60) {
                        $c['bucket'] = 'B2';
                    } else if ($time > 60 && $time <= 90) {
                        $c['bucket'] = 'B3';
                    } else if ($time > 90 && $time <= 120) {
                        $c['bucket'] = 'B4';
                    } else if ($time > 120 && $time <= 150) {
                        $c['bucket'] = 'B5';
                    } else if ($time > 150 && $time <= 180) {
                        $c['bucket'] = 'B6';
                    } else if ($time > 180 && $time <= 210) {
                        $c['bucket'] = 'B7';
                    } else if ($time > 210 && $time <= 270) {
                        $c['bucket'] = 'B8';
                    } else if ($time > 270 && $time <= 300) {
                        $c['bucket'] = 'B9';
                    } else if ($time > 300 && $time <= 330) {
                        $c['bucket'] = 'B10';
                    } else if ($time > 330 && $time <= 360) {
                        $c['bucket'] = 'B11';
                    } else {
                        $c['bucket'] = 'B12';
                    }
                } else if (!empty($c['detail']) && $c['status'] == 2) {
                    $c['bucket'] = 'B0';
                } else {
                    $c['bucket'] = '-';
                }
                $c['time'] = $time;
                if ($c['status'] == 19 || $c['status'] == 23)
                    $c['time'] = '-';
            }
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $contract,
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hiện tại bạn không có hợp đồng nào đang vay"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

    }

    // kiem tra trung bien so xe
    public function check_property_post()
    {
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

    private function checkProperty($infor)
    {
        $infor = str_replace('-', '', $infor);
        $infor = str_replace('.', '', $infor);
        $infor = str_replace(',', '', $infor);
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

    private function getUserGroupRole($GroupIds)
    {
        $arr = array();
        foreach ($GroupIds as $groupId) {
            $groups = $this->group_role_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($groupId)));
            foreach ($groups['users'] as $item) {
                $arr[] = key($item);
            }
        }
        $arr = array_unique($arr);
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

    private function getUserbyStores($storeId)
    {
        $roles = $this->role_model->find_where(array("status" => "active"));
        $roleAllUsers = array();
        if (count($roles) > 0) {
            foreach ($roles as $role) {
                if (!empty($role['stores']) && count($role['stores']) > 0) {
                    $arrStores = array();
                    foreach ($role['stores'] as $item) {
                        array_push($arrStores, key($item));
                    }
                    //Check userId in list key of $users
                    if (in_array($storeId, $arrStores) == TRUE) {
                        if (!empty($role['stores'])) {
                            //Push store
                            foreach ($role['users'] as $key => $item) {
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
    //  $groupRoles = $this->group_role_model->find_where(array("status" => "active"));
    //  $arr = array();
    //  foreach($groupRoles as $groupRole) {
    //      if(empty($groupRole['users'])) continue;
    //      foreach($groupRole['users'] as $item) {
    //          if(key($item) == $userId) {
    //              array_push($arr, $groupRole['slug']);
    //              continue;
    //          }
    //      }
    //  }
    //  return $arr;
    // }


    public function do_send_sms_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $this->dataPost = $this->input->post();
        if (empty($this->dataPost['contract_id'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Không tồn tại hợp đồng 1'
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $content = $this->security->xss_clean($this->dataPost['content']);
        $contract_id = $this->security->xss_clean($this->dataPost['contract_id']);
        $template = $this->security->xss_clean($this->dataPost['template']);

        $c = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($contract_id)));
        if (empty($c)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $phone_send_sms = (!empty($c['customer_infor']['customer_phone_number'])) ? $c['customer_infor']['customer_phone_number'] : "";
        $phone_send_sms = '0356119318';
        if (!empty($phone_send_sms)) {

            $con_sms = array(
                'id_contract' => $contract_id,
                'code_contract' => $c['code_contract'],
                'code_contract_disbursement' => $c['code_contract_disbursement'],
                'customer_name' => $c['customer_infor']['customer_name'],
                'phone_number' => $phone_send_sms,
                'content' => $content,
                "template" => $template,
                'response' => "",
                'status' => "",
                'type' => $type,
                'created_at' => $this->createdAt,
                'created_by' => $this->uemail
            );

            $arr_api = array(
                "template" => $template,
                "number" => $phone_send_sms,
                "content" => $content
            );
            $res = $this->push_api_sms('POST', json_encode($arr_api), "/sms");


            if (!empty($res)) {

                if (isset($res->sendTime)) {
                    $con_sms['status'] = "success";
                    $con_sms['response'] = $res;
                    $this->sms_model->insert($con_sms);
                    $response = array(
                        'status' => REST_Controller::HTTP_OK,
                        'message' => 'Send success',
                        "res" => $res
                    );
                } else {
                    $con_sms['status'] = "fail";
                    $con_sms['response'] = $res;
                    $this->sms_model->insert($con_sms);
                    $response = array(
                        'status' => REST_Controller::HTTP_UNAUTHORIZED,
                        'message' => 'Send fail',
                        "res" => $res,
                        'data' => json_encode($arr_api)
                    );


                }


            }

        }


        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function do_note_reminder_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        if (empty($this->dataPost['contract_id']) || empty($this->dataPost['result_reminder'])) {
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
        $contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])), array("_id", "result_reminder", "store"));

        if (empty($contract)) return;
        //Insert log
        $log = array(
            "type" => "contract",
            "action" => "note_reminder",
            "contract_id" => $this->dataPost['contract_id'],
            "old" => $contract,
            "new" => $this->dataPost,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
//      $this->log_model->insert($log);
        $this->log_call_debt_model->insert($log);
        //Update status contract
        $result_reminder = !empty($contract['result_reminder']) ? $contract['result_reminder'] : array();
        $note_reminder = array(
            "amount_payment_appointment" => $this->dataPost['amount_payment_appointment'],
            "payment_date" => $this->dataPost['payment_date'],
            "reminder" => $this->dataPost['result_reminder'],
            "note" => $this->dataPost['note'],
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
        foreach ($result_reminder as $key => $value) {
            array_push($dataPush, $value);
        }

        $arrUpdate = array(
            'result_reminder' => $dataPush,
            'reminder_now' => $this->dataPost['result_reminder']
        );
        $this->contract_model->update(array("_id" => $contract['_id']), $arrUpdate);

        // Update status bảng contract đã gán cho Call THN
        $store_contract = $this->store_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($contract['store']['id'])]);
        $area_contract = $this->area_model->findOne(array("code" => $store_contract['code_area']));
        $domain_contract = $area_contract['domain']->code;

        $array_tp_thn_mb_id = $this->get_id_tp_thn_mb();
        $array_tp_thn_mn_id = $this->get_id_tp_thn_mn();
        if ($domain_contract == "MB") {
            $id_user_receive_noti = $array_tp_thn_mb_id;
        } else if ($domain_contract == "MN") {
            $id_user_receive_noti = $array_tp_thn_mn_id;
        } else {
            $id_user_receive_noti = "";
        }
        $status_temp = "";
        $note = "Bạn nhận được yêu cầu chuyển Field cho hợp đồng";
        $groupRoles = $this->getGroupRole($this->id);
        $current_day = strtotime(date('m/d/Y'));
        $reminder_input = $this->dataPost['result_reminder'];
        $contract_debt_caller = $this->contract_debt_caller_model->findOne(['contract_id' => (string)$contract['_id']]);

        if (!empty($contract_debt_caller)) {
                if ($reminder_input == 37) {
                    $arrUpdateContractDebtCaller = [
                        'status' => (int)$reminder_input,
                        'reminder_now' => $reminder_input,
                        'result_reminder' => $dataPush,
                        'note' => $this->dataPost['note']
                    ];
                    $this->contract_debt_caller_model->update(array("contract_id" => (string)$contract['_id']), $arrUpdateContractDebtCaller);
//                  $this->push_notification_request_change_field($id_user_receive_noti, $status_temp, $contract_debt_caller, $note);
                    $this->log_debt_caller_model->insert(
                        [
                            'type' => 'reminder',
                            'action' => 'update',
                            'contract_id' => (string)$contract['_id'],
                            'old' => $contract_debt_caller,
                            'new' => $arrUpdateContractDebtCaller,
                            "created_at" => $this->createdAt,
                            "created_by" => $this->uemail
                        ]
                    );
                } else {
                    $arrUpdateContractDebtCaller = [
                        'status' => 36,
                        'reminder_now' => 36,
                        'result_reminder' => $dataPush,
                        'note' => $this->dataPost['note']
                    ];
                    $this->contract_debt_caller_model->update(array("contract_id" => (string)$contract['_id']), $arrUpdateContractDebtCaller);
                    $this->log_debt_caller_model->insert(
                        [
                            'type' => 'reminder',
                            'action' => 'update',
                            'contract_id' => (string)$contract['_id'],
                            'old' => $contract_debt_caller,
                            'new' => $arrUpdateContractDebtCaller,
                            "created_at" => $this->createdAt,
                            "created_by" => $this->uemail
                        ]
                    );
                }
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Note success'
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

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

    // Luồng duyệt hợp đồng
    public function approve_post()
    {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        if (empty($this->dataPost['contract_id']) || empty($this->dataPost['status'])) {
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
        $this->dataPost['reason'] = $this->security->xss_clean($this->dataPost['reason']);
        $this->dataPost['image_file'] = $this->security->xss_clean($this->dataPost['image_file']);
        $this->dataPost['exception'] = $this->security->xss_clean($this->dataPost['exception']);
        $this->dataPost['amount_money'] = $this->security->xss_clean($this->dataPost['amount_money']);
        $this->dataPost['type_loan'] = $this->security->xss_clean($this->dataPost['type_loan']);
        $this->dataPost['number_day_loan'] = $this->security->xss_clean($this->dataPost['number_day_loan']);
        $this->dataPost['error_code'] = !empty($this->dataPost['error_code']) ? $this->security->xss_clean($this->dataPost['error_code']) : "";
        $this->dataPost['code_contract_disbursement'] = !empty($this->dataPost['code_contract_disbursement']) ? $this->security->xss_clean($this->dataPost['code_contract_disbursement']) : "";
        $code_contract_disbursement_type = !empty($this->dataPost['code_contract_disbursement_type']) ? $this->security->xss_clean($this->dataPost['code_contract_disbursement_type']) : "";

        if (!empty($this->info['is_superadmin']) && $this->info['is_superadmin'] != 1) {
            // Check access right by status
            $isAccess = $this->checkApproveByAccessRight($this->roleAccessRights, $this->dataPost['status']);
            if ($isAccess == FALSE) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => 'Do not have access right'
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }

        // Check old status
        $contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])), array("_id", "status", "code_contract", "code_contract_disbursement", "created_by", "store", "customer_infor", "loan_infor", "receiver_infor", "note", 'check_not_approve', "reason"));
        $store = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($contract['store']['id'])));
        $area = $this->area_model->findOne(array("code" => $store['code_area']));

        //check trường hợp yêu cầu gia hạn hợp đồng
        if ($contract['status'] == '17' && (int)$this->dataPost['status'] == '21') {
            $condition = array(
                'code_contract' => $contract['code_contract']
            );
            $contractTempoData = $this->contract_tempo_model->getAll($condition);
            if (!empty($contractData)) {
                $last_key = count($contractData);
                foreach ($contractTempoData as $key => $contract_tempo) {
                    $current_day = strtotime(date('m/d/Y'));
                    $datetime = strtotime(date('m/d/Y'));
                    if ($key == $last_key - 1) {
                        $datetime = !empty($contract_tempo->ngay_ky_tra) ? intval($contract_tempo->ngay_ky_tra) : $current_day;
                    }
                    if ($current_day < $datetime) {
                        //chưa đến kỳ cuối chưa dk gia hạn
                        $response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            'message' => 'Hợp đồng chưa đến kỳ cuối lên chưa được gia hạn',
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    }
                }
            }
        }
        $isStatus = $this->checkStatusForApprove($contract['status'], (int)$this->dataPost['status']);

        if (empty($contract)) return;
        if ($isStatus == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Status is not compatible',
                "status1" => $contract['status'],
                "status2" => (int)$this->dataPost['status']
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
            "new" => $this->dataPost,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $groupRoles = $this->getGroupRole($this->id);

        //GDV gửi TP GD
        if ($contract['status'] == '17' && (int)$this->dataPost['status'] == '21') {
            $log['action'] = "cvkd_send_gh";
        }
        //THN gửi TP THN
        if ($contract['status'] == '17' && (int)$this->dataPost['status'] == '11') {
            $log['action'] = "cvkd_send_gh";
            $log['type_gh_cc'] = "THN";
        }
        //TP GD gửi ASM
        if ($contract['status'] == '17' && (int)$this->dataPost['status'] == '30') {
            $log['action'] = "cvkd_send_gh";
            $log['type_gh_cc'] = "TPGD";
        }
        if ($contract['status'] == '22' && (int)$this->dataPost['status'] == '21') {
            $log['action'] = "cvkd_send_gh";
        }
        if ($contract['status'] == '26' && (int)$this->dataPost['status'] == '25') {
            $log['action'] = "cvkd_send_gh";
        }
        if ($contract['status'] == '30' && (int)$this->dataPost['status'] == '29') {
            $log['action'] = "cvkd_send_gh";
        }
        if ($contract['status'] == '13' && (int)$this->dataPost['status'] == '11') {
            $log['action'] = "cvkd_send_gh";
            if (in_array('thu-hoi-no', $groupRoles)) {
                $log['type_gh_cc'] = "THN";
            }
        }
        if ($contract['status'] == '11' && (int)$this->dataPost['status'] == '25') {
            $log['action'] = "cvkd_send_gh";
            if (in_array('thu-hoi-no', $groupRoles)) {
                $log['type_gh_cc'] = "THN";
            }
        }
        if ($contract['status'] == '41' && (int)$this->dataPost['status'] == '30') {
            $log['action'] = "cvkd_send_gh";
            if (in_array('cua-hang-truong', $groupRoles)) {
                $log['type_gh_cc'] = "TPGD";
            }
        }
        if ($contract['status'] == '17' && (int)$this->dataPost['status'] == '23') {
            $log['action'] = "cvkd_send_cc";
        }
        if ($contract['status'] == '17' && (int)$this->dataPost['status'] == '32') {
            $log['action'] = "cvkd_send_cc";
            $log['type_gh_cc'] = "TPGD";
        }
        //THN gửi TP THN
        if ($contract['status'] == '17' && (int)$this->dataPost['status'] == '12') {
            $log['action'] = "cvkd_send_cc";
            $log['type_gh_cc'] = "THN";
        }
        if ($contract['status'] == '24' && (int)$this->dataPost['status'] == '23') {
            $log['action'] = "cvkd_send_cc";
        }
        if ($contract['status'] == '28' && (int)$this->dataPost['status'] == '27') {
            $log['action'] = "cvkd_send_cc";
        }
        if ($contract['status'] == '32' && (int)$this->dataPost['status'] == '31') {
            $log['action'] = "cvkd_send_cc";
        }
        if ($contract['status'] == '14' && (int)$this->dataPost['status'] == '12') {
            $log['action'] = "cvkd_send_cc";
            if (in_array('thu-hoi-no', $groupRoles)) {
                $log['type_gh_cc'] = "THN";
            }
        }
        if ($contract['status'] == '12' && (int)$this->dataPost['status'] == '27') {
            $log['action'] = "cvkd_send_cc";
            if (in_array('thu-hoi-no', $groupRoles)) {
                $log['type_gh_cc'] = "THN";
            }
        }
        if ($contract['status'] == '42' && (int)$this->dataPost['status'] == '32') {
            $log['action'] = "cvkd_send_cc";
            if (in_array('cua-hang-truong', $groupRoles)) {
                $log['type_gh_cc'] = "TPGD";
            }
        }

        /**
         * Save log to json file
         */

        $insertLogNew = [
            "type" => "contract",
            "action" => $log['action'],
            "contract_id" => $this->dataPost['contract_id'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail,
            "type_gh_cc" => !empty($log['type_gh_cc']) ? $log['type_gh_cc'] : ""
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($log);
        $log['log_id'] = $log_id;

        $this->insert_log_file($log, $this->dataPost['contract_id']);

        /**
         * ----------------------
         */

        $this->log_hs_model->insert($log);

        $status = (int)$this->dataPost['status'];

        //Update status contract
        if ($status == 6) {
            $this->dataPost['amount_money'] = $this->security->xss_clean($this->dataPost['amount_money']);
            $this->dataPost['amount_loan'] = $this->security->xss_clean($this->dataPost['amount_loan']);
            $this->dataPost['amount_GIC'] = $this->security->xss_clean($this->dataPost['amount_GIC']);
            $this->dataPost['amount_MIC'] = $this->security->xss_clean($this->dataPost['amount_MIC']);
            $this->dataPost['info_disbursement_max'] = $this->security->xss_clean($this->dataPost['info_disbursement_max']);
            $this->dataPost['status_disbursement_max'] = $this->security->xss_clean($this->dataPost['status_disbursement_max']);


            $arrUpdate = array(
                "status" => (int)$this->dataPost['status'],
                "note" => $this->dataPost['note'],
                "reason" => $this->dataPost['reason'],
                "loan_infor.amount_money" => $this->dataPost['amount_money'],
                "loan_infor.amount_GIC" => $this->dataPost['amount_GIC'],
                "loan_infor.amount_MIC" => $this->dataPost['amount_MIC'],
                "loan_infor.amount_loan" => $this->dataPost['amount_loan'],
                "receiver_infor.amount" => $this->dataPost['amount_money'],
                "expertise_infor.exception1_value" => $this->dataPost['exception1_value_detail'],
                "expertise_infor.exception2_value" => $this->dataPost['exception2_value_detail'],
                "expertise_infor.exception3_value" => $this->dataPost['exception3_value_detail'],
                "expertise_infor.exception4_value" => $this->dataPost['exception4_value_detail'],
                "expertise_infor.exception5_value" => $this->dataPost['exception5_value_detail'],
                "expertise_infor.exception6_value" => $this->dataPost['exception6_value_detail'],
                "expertise_infor.exception7_value" => $this->dataPost['exception7_value_detail'],
            );
            if ($this->dataPost['amount_loan'] > 300000000) {
                $arrUpdate['info_disbursement_max'] = divide_amount_money($this->dataPost['amount_loan']);
                $arrUpdate['status_disbursement_max'] = 1;
            }

        } else {
            $arrUpdate = array(
                "status" => (int)$this->dataPost['status'],
                "note" => $this->dataPost['note'],
                "reason" => $this->dataPost['reason'],
            );
        }
        if ($status == 8) {
            $arrUpdate['check_not_approve'] = true;
        }
        if ($status == 6) {
            $contractInfo = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])));
            // Tạo HĐ Điện tử Megadoc
            $is_store_apply_digital_contract = $this->check_store_create_contract_digital($contractInfo['store']['id']);
            if ($is_store_apply_digital_contract) {
                if (!empty($contractInfo['customer_infor']['type_contract_sign']) && $contractInfo['customer_infor']['type_contract_sign'] == 1) {
                    $ttbb = $this->create_contract_megadoc($contractInfo, $status);
                }
            }
        }

        if ($status == 15 && $code_contract_disbursement_type != 1) {
            $contractData = $this->contract_model->findOne(array("code_contract_disbursement" => trim($this->dataPost['code_contract_disbursement'])));
            if (!empty($contractData)) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => 'Mã hợp đồng đã tồn tại',
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            $arrUpdate['code_contract_disbursement'] = $this->dataPost['code_contract_disbursement'];

        }

        $this->contract_model->update(array("_id" => $contract['_id']), $arrUpdate);
        //$this->process_update_dashboard($contract['_id']);
        $note = '';
        $user_ids = array();
        $user_ids_approve = array();
        if ($status == 2 || $status == 21 || $status == 23 || $status == 11 || $status == 12 || $status == 30 || $status == 32) {
            if ($status == 2) {
                $note = 'Chờ phê duyệt';
            } else if ($status == 21 || $status == 11 || $status == 30) {
                $note = 'Chờ phê duyệt hợp đồng gia hạn';
            } else if ($status == 23 || $status == 12 || $status == 32) {
                $note = 'Chờ phê duyệt hợp đồng cơ cấu';
            }
            $allusers = $this->getUserbyStores($contract['store']['id']);
            if ($status == 11 || $status == 12) {
                $cht_id = array(
                    '60a5e0c05324a73f2e25d224'
                );

                if ($area['domain']['code'] == "MB") {
                    //trưởng phòng thu hồi nợ miền bắc
                    $cht_id = array(
                        '60a5e0c05324a73f2e25d224'
                    );

                }
                if ($area['domain']['code'] == "MN") {
                    //trưởng phòng thu hồi nợ miền nam
                    $cht_id = array(
                        '60a5e0d45324a73eba244ca6'
                    );
                }

                $user_ids_groups = $this->getUserGroupRole($cht_id);
                $arr = $user_ids_groups;
            } else if ($status == 30 || $status == 32) {
                //ASM

                $user_asm = $this->get_user_asm_by_store($contract['store']['id']);
                $user_ids = array_values($user_asm);
                $arr = $user_ids;
            } else {
                //trưởng PGD
                $cht_id = array(
                    '5de726c9d6612b6f2a617ef5'
                );
                $user_ids_groups = $this->getUserGroupRole($cht_id);
                $arr = array_intersect($allusers, $user_ids_groups);
            }


            $user_ids_approve = array_values($arr);

            $typeInterest = !empty($contract['loan_infor']['type_interest']) ? number_format($contract['loan_infor']['type_interest']) : "";
            $type_interest = "";
            if ($typeInterest == 1) {
                $type_interest = "Dư nợ giảm dần";
            } else if ($typeInterest == 2) {
                $type_interest = "Lãi hàng tháng, gốc cuối kỳ";
            }
            $data_send = array(
                'code' => "vfc_send_storeman",
                "customer_name" => $contract['customer_infor']['customer_name'],
                "code_contract" => $contract['code_contract'],
                "product" => $contract['loan_infor']['type_loan']['text'],
                "product_detail" => $contract['loan_infor']['name_property']['text'],
                "store_name" => $contract['store']['name'],
                "amount_money" => !empty($contract['loan_inffind_where_topup_pgdor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
                "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                "phone_store" => $store['phone'],
                "type_interest" => $type_interest
            );
            $check = $this->sendEmailApprove($user_ids_approve, $data_send, $status);
        } elseif ($status == 3 || (in_array((int)$contract['status'], [11, 13, 21, 25, 29, 30, 26, 33, 12, 14, 23, 24, 27, 28, 31, 32, 34]) && $status == 17)) {
            $type_gh_cc = " hợp đồng";
            if (in_array((int)$contract['status'], [11, 13, 21, 25, 29, 30, 26, 33])) {
                $type_gh_cc = " yêu cầu gia hạn";
            } else if (in_array((int)$contract['status'], [12, 14, 23, 24, 27, 28, 31, 32, 34])) {
                $type_gh_cc = " yêu cầu cơ cấu";
            }

            //hủy gic
            if (isset($contract['code_contract_disbursement']))
                $this->update_gic($contract['code_contract_disbursement']);


            //5de72198d6612b4076140606 super admin
            //5de726a8d6612b6f2b431749 Van hanh
            //5de726c9d6612b6f2a617ef5 CHT
            //5de726e4d6612b6f2c310c78 GDV
            //5de726fcd6612b77824963b9 Ke Toan
            //5def671dd6612b75532960c5 Hoi so

            if (in_array('cua-hang-truong', $groupRoles)) {
                $note = 'Trưởng PGD đã hủy' . $type_gh_cc;
                $note = 'Trưởng PGD không duyệt' . $type_gh_cc;
                $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
                $user_ids = array(
                    (string)$user_created['_id']
                );
                $cht_id = array(
                    '5de726c9d6612b6f2a617ef5'
                );
                $allusers = $this->getUserbyStores($contract['store']['id']);
                $user_ids_groups = $this->getUserGroupRole($cht_id);
                $arr = array_diff($allusers, $user_ids_groups);
                $user_ids = array_values($arr);

            } else {
                if ($this->superadmin || in_array('supper-admin', $groupRoles)) {
                    $note = 'Super admin đã hủy' . $type_gh_cc;
                } elseif (in_array('van-hanh', $groupRoles)) {
                    $note = 'Vận hành đã hủy' . $type_gh_cc;
                } elseif (in_array('ke-toan', $groupRoles)) {
                    $note = 'Kế toán đã hủy' . $type_gh_cc;
                } elseif (in_array('hoi-so', $groupRoles)) {
                    $note = 'Hội sở đã hủy' . $type_gh_cc;
                } elseif (in_array('tbp-thu-hoi-no', $groupRoles)) {
                    $note = 'TP thu hồi nợ đã hủy' . $type_gh_cc;
                } elseif (in_array('quan-ly-khu-vuc', $groupRoles)) {
                    $note = 'ASM đã hủy' . $type_gh_cc;
                }
                $cht_id = array(
                    '5de726c9d6612b6f2a617ef5'
                );
                $allusers = $this->getUserbyStores($contract['store']['id']);
                $user_ids_groups = $this->getUserGroupRole($cht_id);
                //5ea1b6abd6612b6dd20de539 thu hoi no
                $thn_id = array(
                    '5ea1b6abd6612b6dd20de539'
                );

                $user_thn = $this->getUserGroupRole($thn_id);
                $arr = array_merge($allusers, $user_ids_groups, $user_thn);
                $user_ids = array_values($arr);
                //$user_ids_approve = array_values($allusers);
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
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
                "API_KEY" => $this->config->item('API_KEY'),
                "email" => $contract['created_by'],
                "full_name" => !empty($user_created['full_name']) ? $user_created['full_name'] : ""
            );
            $this->user_model->send_Email($data_send);

            if ($status == 3){
                $leadData = $this->lead_model->find_one_check_phone($contract['customer_infor']['customer_phone_number']);
                if (!empty($leadData[0]['click_id_dinos']) && $leadData[0]['click_id_dinos'] != ""){
                    $status = "rejected";
                    $this->api_dinos($leadData[0]['click_id_dinos'], $status);
                }
            }


            // gửi email thông báo cho khách hang nếu hội sở hủy mới gửi
            if ($contract['status'] == 5) {
                $data_send1 = array(
                    'code' => "vfc_cancel_send_customer",
                    "customer_name" => $contract['customer_infor']['customer_name'],
                    "API_KEY" => $this->config->item('API_KEY'),
                    "email" => $contract['customer_infor']['customer_email'],
                    "phone_store" => $store['phone']
                );
                $this->user_model->send_Email($data_send1);

            }


        } elseif ($status == 4) {
            $note = 'Trưởng PGD không duyệt';
            $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
            $user_ids = array(
                (string)$user_created['_id']
            );
        } elseif ($status == 5 || $status == 25 || $status == 27) {
            if ($status == 5) {
                $note = 'Chờ hội sở duyệt';
            } else if ($status == 25) {
                $note = 'Chờ phê duyệt hợp đồng gia hạn';
            } else if ($status == 27) {
                $note = 'Chờ phê duyệt hợp đồng cơ cấu';
            }

            //approve
            $hoi_so_id = array(
                '5def671dd6612b75532960c5' // cho Hoi so duyet
            );
            $user_ids_approve = $this->getUserGroupRole($hoi_so_id);
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $qlkv = $this->getUserGroupRole(array('5ec74bd2d6612b3cc464e64a'));
            $ql = array_intersect($allusers, $qlkv);
            $user_ids = array_values($ql);
            $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
            array_push($user_ids, (string)$user_created['_id']);

            $check_not_approve = !empty($contract['check_not_approve']) ? $contract['check_not_approve'] : false;
            if ($check_not_approve == true) {
                // send email bổ sung đã bị hội sở hủy 1 lần
                $data_send = array(
                    'code' => "vfc_send_president_add",
                    "customer_name" => $contract['customer_infor']['customer_name'],
                    "code_contract" => $contract['code_contract'],
                    "store_name" => $contract['store']['name'],
                    "amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
                    "product" => $contract['loan_infor']['type_loan']['text'],
                    "product_detail" => $contract['loan_infor']['name_property']['text'],
                    "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                    "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : $note,
                    "phone_store" => $store['phone'],
                    "type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng, gốc cuối kì"
                );

                $this->sendEmailApprove($user_ids_approve, $data_send, $status);
                $this->sendEmailApprove($qlkv_approve, $data_send, $status);
            } else {
                // send email phê duyệt lần đầu
                $data_send = array(
                    'code' => "vfc_send_president",
                    "customer_name" => $contract['customer_infor']['customer_name'],
                    "code_contract" => $contract['code_contract'],
                    "store_name" => $contract['store']['name'],
                    "amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
                    "product" => $contract['loan_infor']['type_loan']['text'],
                    "product_detail" => $contract['loan_infor']['name_property']['text'],
                    "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                    "phone_store" => $store['phone'],
                    "type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng, gốc cuối kì"
                );
                $this->sendEmailApprove($user_ids_approve, $data_send, $status);
                $this->sendEmailApprove($qlkv_approve, $data_send, $status);
            }

        } elseif ($status == 6) {
            $note = 'Hội sở đã duyệt';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_ids_groups = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_ids_groups);
            $user_ids = array_values($arr);
            // push notification on ring
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
                "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : $note,
                "phone_store" => $store['phone']
            );

            $this->sendEmailApprove($user_ids, $data_send, $status);

        } elseif ($status == 7) {

            $note = 'Kế toán không duyệt';

            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_cht = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_cht);
            $user_ids = array_values($arr);
            //
            $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
            array_push($user_ids, (string)$user_created['_id']);
            // gửi email thông báo cho giao dich viên
            $data_send = array(
                'code' => "vfc_accounting_approved",
                "full_name" => !empty($user_created['full_name']) ? $user_created['full_name'] : "",
                "customer_name" => $contract['customer_infor']['customer_name'],
                "code_contract" => $contract['code_contract'],
                "product" => $contract['loan_infor']['type_loan']['text'],
                "product_detail" => $contract['loan_infor']['name_property']['text'],
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
                "API_KEY" => $this->config->item('API_KEY'),
                "email" => $contract['created_by'],
            );


            $this->user_model->send_Email($data_send);

        } elseif ($status == 8 || $status == 29 || $status == 31) {
            if ($status == 8) {
                $note = 'Yêu cầu bổ sung hồ sơ';
            } else if ($status == 29) {
                $note = 'Yêu cầu tạo phiếu thu gia hạn';
            } else if ($status == 31) {
                $note = 'Yêu cầu tạo phiếu thu cơ cấu';
            }
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $thn_id = array(
                '5ea1b6abd6612b6dd20de539'
            );

            $user_thn = $this->getUserGroupRole($thn_id);
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_ids_groups = $this->getUserGroupRole($cht_id);
            $qlkv = $this->getUserGroupRole(array('5ec74bd2d6612b3cc464e64a'));
            $cht = array_intersect($allusers, $user_ids_groups);
            $ql = array_intersect($allusers, $qlkv);
            if ($status == 8) {
                $user_ids_approve = array_values(array_merge($cht, $ql));
            } else {
                $user_ids_approve = array_values(array_merge($allusers, $user_thn));
            }

            $data_send = array(
                'code' => "vfc_president_not_approve",
                "customer_name" => $contract['customer_infor']['customer_name'],
                "code_contract" => $contract['code_contract'],
                "product" => $contract['loan_infor']['type_loan']['text'],
                "product_detail" => $contract['loan_infor']['name_property']['text'],
                "amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
                "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : $note,
                "phone_store" => $store['phone'],
                "type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng, gốc cuối kì",
                "created_by" => $contract['created_by']
            );
            $this->sendEmailApprove($user_ids_approve, $data_send, $status);
            // $this->sendEmailApprove($qlkv_approve,$data_send,$status);
        } elseif ($status == 15) {
            if ($status == 15) {
                $note = 'Giao dịch viên gửi yêu cầu giải ngân hợp đồng';
            }
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_cht = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_cht);
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
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
                "API_KEY" => $this->config->item('API_KEY'),
                "email" => $contract['created_by'],
                "full_name" => !empty($user_created['full_name']) ? $user_created['full_name'] : "",
                "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                "amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
            );
            $this->sendEmailApprove($user_ids_approve, $data_send, $status);
        } elseif ($status == 16) {
            $note = 'Kế toán đã tạo lệnh giải ngân';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_cht = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_cht);
            $user_ids = array_values($arr);
            //
            $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
            array_push($user_ids, (string)$user_created['_id']);
        } elseif ($status == 17 && !in_array((int)$contract['status'], [11, 13, 21, 25, 29, 30, 26, 33, 12, 14, 23, 24, 27, 28, 31, 32, 34])) {
            $note = 'Giải ngân thành công';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_cht = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_cht);
            $user_ids = array_values($arr);
            // user created
            $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
            array_push($user_ids, (string)$user_created['_id']);
            // ke toan
            $kt_id = array(
                '5de726fcd6612b77824963b9'
            );
            $kt_ids = $this->getUserGroupRole($kt_id);
            $user_ids = array_merge($user_ids, $kt_ids);
        } elseif ($status == 18) {
            $note = 'Giải ngân thất bại';
            $kt_id = array(
                '5de726fcd6612b77824963b9'
            );
            $user_ids = $this->getUserGroupRole($kt_id);
        } elseif ($status == 22 || $status == 24 || $status == 26 || $status == 28 || $status == 13 || $status == 14 || $status == 41 || $status == 42) {
            if ($status == 22) {
                $note = 'TPGD trả về hợp đồng gia hạn';
            } else if ($status == 24) {
                $note = 'TPGD trả về hợp đồng cơ cấu';
            } else if ($status == 26) {
                $note = 'Hội sở trả về hợp đồng gia hạn';
            } else if ($status == 28) {
                $note = 'Hội sở trả về hợp đồng cơ cấu';
            } else if ($status == 13) {
                $note = 'Thu hồi nợ trả về hợp đồng gia hạn';
            } else if ($status == 14) {
                $note = 'Thu hồi nợ trả về hợp đồng cơ cấu';
            } else if ($status == 41) {
                $note = 'ASM trả về hợp đồng gia hạn';
            } else if ($status == 42) {
                $note = 'ASM trả về hợp đồng cơ cấu';
            }
            $cht_id = array(
                '5de726e4d6612b6f2c310c78'
            );
            //5ea1b6abd6612b6dd20de539 thu hoi no
            $thn_id = array(
                '5ea1b6abd6612b6dd20de539'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_cht = $this->getUserGroupRole($cht_id);
            $user_thn = $this->getUserGroupRole($thn_id);
            $arr = array_merge($allusers, $user_cht, $user_thn);
            $user_ids = array_values($arr);
        } else {
            $note = $this->dataPost['note'];
        }
        //noti and firebase app
        if ($status == 3 || $status == 6 || $status == 17) {
            //$this->push_noti_app($status, $note, $contract['_id'], $contract['customer_infor']['customer_phone_number'], $contract['code_contract']);
        }
        $link_detail = 'pawn/detail?id=' . (string)$contract['_id'];
        if ($status == 29 || $status == 31) {
            $link_detail = 'accountant/view?id=' . (string)$contract['_id'];
        }
        if (isset($contract['code_contract'])) {
            $this->ap_dung_coupon_giam_bhkv($contract['code_contract']);
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
                    'detail' => $link_detail,
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
            if ($note == '') {
                $note = 'Chờ phê duyệt';
            }
            foreach ($user_ids_approve as $us) {
                if ($status == 2) {
                    $note = 'Chờ phê duyệt';
                } else if ($status == 21 || $status == 11 || $status == 25) {
                    $note = 'Chờ phê duyệt hợp đồng gia hạn';
                } else if ($status == 23 || $status == 12 || $status == 27) {
                    $note = 'Chờ phê duyệt hợp đồng cơ cấu';
                }

                $data_approve = [
                    'action_id' => (string)$contract['_id'],
                    'action' => 'contract',
                    'detail' => $link_detail,
                    'title' => $contract['customer_infor']['customer_name'] . ' - ' . $contract['store']['name'],
                    'note' => $note,
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
                'detail' => $link_detail,
                'title' => $contract['code_contract'],
                'note' => $contract['customer_infor']['customer_name'] . ' - ' . $contract['store']['name'],
                'users' => $user_ids_approve,
                'created_at' => $this->createdAt,
            );
            $dataSocket['approve'] = $dataUserApprove;
        }

        $dataContract = array(
            'status' => (int)$this->dataPost['status'],
            'action_id' => (string)$contract['_id'],
            'action' => 'contract',
            'detail' => $link_detail,
            'title' => $contract['code_contract'],
            'note' => $note,
            'users' => $user_ids,
            'created_at' => $this->createdAt,
        );
        $dataSocket['status'] = $dataContract;
        $this->transferSocket($dataSocket);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Approve success',
            'dataSocket' => $dataSocket
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function push_noti_app($status, $note, $contract_id, $phone_number, $code_contract)
    {
        $cond = [];
        $cond['type'] = '2';
        $cond['phone_number'] = (string)$phone_number;
        $user = $this->user_model->findOne($cond);
        if (!empty($user)) {
            $data_notification = [
                'action_id' => (string)$contract_id,
                'action' => 'contract',
                'note' => (string)$note,
                'user_id' => (string)$user['_id'],
                'status' => 1, //1: new, 2 : read, 3: block,
                'contract_status' => $status,
                'created_at' => $this->createdAt,
                "created_by" => $this->uemail
            ];
            $this->notification_app_model->insertReturnId($data_notification);
            $con = [];
            $con['user_id'] = new MongoDB\BSON\ObjectId((string)$user['_id']);
            $device = $this->device_model->find_where($con);
            if (!empty($device)) {
                $badge = $this->get_count_notification_user((string)$user["_id"]);
                $fcm = new Fcm();
                $to = [];
                foreach ($device as $de) {
                    $to[] = $de->device_token;
//                  array_push($to, $de->device_token);
                }
                $fcm->setTitle('Tiền Ngay Thông Báo');
                if ($status == 3) {
                    $fcm->setMessage("Hợp đồng $code_contract của bạn không được duyệt! ");
                } elseif ($status == 6) {
                    $fcm->setMessage("Hợp đồng $code_contract của bạn đã được duyệt! ");
                } elseif ($status == 1) {
                    $fcm->setMessage("Hợp đồng $code_contract của bạn đã được khởi tạo thành công!");
                } elseif ($status == 17) {
                    $fcm->setMessage("Hợp đồng $code_contract của bạn đã được giải ngân thành công!");
                }
                $message = $fcm->getMessage();
                $fcm->setType('contract');
                $fcm->setContractId((string)$contract_id);
                $data = $fcm->getData();
                $result = $fcm->sendMultiple($to, $message, $data);
            }
        }
    }

    public function update_gic($code_contract)
    {


        $count = $this->gic_model->count(array("code_contract_disbursement" => $code_contract));
        if ($count != 1) {

            return "";
        }
        $this->gic_model->update(
            array("code_contract_disbursement" => $code_contract),
            array("status" => "3")
        );
        $this->gic_easy_model->update(
            array("code_contract_disbursement" => $code_contract),
            array("status" => "3")
        );

        return "OK";
    }

    //vaynhanh
    public function approve_for_quickloan_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        if (empty($this->dataPost['contract_id']) || empty($this->dataPost['status'])) {
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

        if (!empty($this->info['is_superadmin']) && $this->info['is_superadmin'] != 1) {
            // Check access right by status
            $isAccess = $this->checkApproveByAccessRight($this->roleAccessRights, $this->dataPost['status']);
            if ($isAccess == FALSE) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => 'Do not have access right'
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        // Check old status
        $contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])), array("_id", "status", "code_contract", "created_by", "store", "customer_infor", "loan_infor", "receiver_infor", "note", 'check_not_approve'));
        $store = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($contract['store']['id'])));
        $isStatus = $this->checkStatusForApprove($contract['status'], $this->dataPost['status']);
        if (empty($contract)) return;
        if ($isStatus == false) {
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
            "new" => $this->dataPost,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($log);
        $this->log_hs_model->insert($log);
        $status = (int)$this->dataPost['status'];
        //Update status contract
        if ($status == 6) {
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
        } else {
            $arrUpdate = array(
                "status" => (int)$this->dataPost['status'],
                "note" => $this->dataPost['note']
            );
        }
        if ($status == 8) {
            $arrUpdate['check_not_approve'] = true;
        }

        $this->contract_model->update(array("_id" => $contract['_id']), $arrUpdate);
        $note = '';
        $user_ids = array();
        $user_ids_approve = array();
        if ($status == 2) {
            $note = 'Chờ phê duyệt';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_ids_groups = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_ids_groups);
            $user_ids_approve = array_values($arr);
            $data_send = array(
                'code' => "vfc_send_storeman",
                "customer_name" => $contract['customer_infor']['customer_name'],
                "code_contract" => $contract['code_contract'],
                "product" => $contract['loan_infor']['type_loan']['text'],
                "product_detail" => $contract['loan_infor']['name_property']['text'],
            );
            $check = $this->sendEmailApprove($user_ids_approve, $data_send, $status);
        } elseif ($status == 3) {

            $groupRoles = $this->getGroupRole($this->id);
            //5de72198d6612b4076140606 super admin
            //5de726a8d6612b6f2b431749 Van hanh
            //5de726c9d6612b6f2a617ef5 CHT
            //5de726e4d6612b6f2c310c78 GDV
            //5de726fcd6612b77824963b9 Ke Toan
            //5def671dd6612b75532960c5 Hoi so
            if (in_array('cua-hang-truong', $groupRoles)) {
                $note = 'Trưởng PGD đã hủy';
                $note = 'Trưởng PGD không duyệt';
                $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
                $user_ids = array(
                    (string)$user_created['_id']
                );
            } else {
                if ($this->superadmin || in_array('supper-admin', $groupRoles)) {
                    $note = 'Super admin đã hủy';
                } elseif (in_array('van-hanh', $groupRoles)) {
                    $note = 'Vận hành đã hủy';
                } elseif (in_array('ke-toan', $groupRoles)) {
                    $note = 'Kế toán đã hủy';
                } elseif (in_array('hoi-so', $groupRoles)) {
                    $note = 'Hội sở đã hủy';
                }
                $cht_id = array(
                    '5de726c9d6612b6f2a617ef5'
                );
                $allusers = $this->getUserbyStores($contract['store']['id']);
                $user_ids_groups = $this->getUserGroupRole($cht_id);
                $arr = array_intersect($allusers, $user_ids_groups);
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
                "product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : '',
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
                "API_KEY" => $this->config->item('API_KEY'),
                "email" => $contract['created_by'],
                "full_name" => !empty($user_created['full_name']) ? $user_created['full_name'] : ""
            );
            $this->user_model->send_Email($data_send);

            // gửi email thông báo cho khách hang nếu hội sở hủy mới gửi
            if ($contract['status'] == 5) {
                $data_send1 = array(
                    'code' => "vfc_cancel_send_customer",
                    "customer_name" => $contract['customer_infor']['customer_name'],
                    "API_KEY" => $this->config->item('API_KEY'),
                    "email" => $contract['customer_infor']['customer_email'],
                    "phone_store" => $store['phone']
                );
                $this->user_model->send_Email($data_send1);

            }

        } elseif ($status == 4) {
            $note = 'Trưởng PGD không duyệt';
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
            $check_not_approve = !empty($contract['check_not_approve']) ? $contract['check_not_approve'] : false;
            if ($check_not_approve == true) {
                // send email bổ sung đã bị hội sở hủy 1 lần
                $data_send = array(
                    'code' => "vfc_send_president_add",
                    "customer_name" => $contract['customer_infor']['customer_name'],
                    "code_contract" => $contract['code_contract'],
                    "store_name" => $contract['store']['name'],
                    "amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
                    "product" => $contract['loan_infor']['type_loan']['text'],
                    "product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : '',
                    "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                    "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : $note,
                    "type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng gốc cuối kì",
                    "phone_store" => $store['phone']
                );
                $this->sendEmailApprove($user_ids_approve, $data_send, $status);
            } else {
                // send email phê duyệt lần đầu
                $data_send = array(
                    'code' => "vfc_send_president",
                    "customer_name" => $contract['customer_infor']['customer_name'],
                    "code_contract" => $contract['code_contract'],
                    "store_name" => $contract['store']['name'],
                    "amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
                    "product" => $contract['loan_infor']['type_loan']['text'],
                    "product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : 'vaynhanh',
                    "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                    "phone_store" => $store['phone']
                );
                $this->sendEmailApprove($user_ids_approve, $data_send, $status);
            }
        } elseif ($status == 6) {
            $note = 'Hội sở đã duyệt';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_ids_groups = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_ids_groups);
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
                "product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : 'vaynhanh',
                "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : $note,
                "store_phone" => $store['phone']
            );

            $this->sendEmailApprove($user_ids, $data_send, $status);

        } elseif ($status == 7) {
            $note = 'Kế toán không duyệt';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_cht = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_cht);
            $user_ids = array_values($arr);
            //
            $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
            array_push($user_ids, (string)$user_created['_id']);
            // gửi email thông báo cho giao dich viên
            $data_send = array(
                'code' => "vfc_accounting_approved",
                "full_name" => !empty($user_created['full_name']) ? $user_created['full_name'] : "",
                "customer_name" => $contract['customer_infor']['customer_name'],
                "code_contract" => $contract['code_contract'],
                "product" => $contract['loan_infor']['type_loan']['text'],
                "product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : 'vaynhanh',
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
                "API_KEY" => $this->config->item('API_KEY'),
                "email" => $contract['created_by'],
            );
            $this->user_model->send_Email($data_send);

        } elseif ($status == 8) {
            $note = 'Yêu cầu bổ sung hồ sơ';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_ids_groups = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_ids_groups);
            $user_ids_approve = array_values($arr);
            $data_send = array(
                'code' => "vfc_president_not_approve",
                "customer_name" => $contract['customer_infor']['customer_name'],
                "code_contract" => $contract['code_contract'],
                "product" => $contract['loan_infor']['type_loan']['text'],
                "product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : 'vaynhanh',
                "amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
                "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : $note,
                "phone_store" => $store['phone']
            );
            $check = $this->sendEmailApprove($user_ids_approve, $data_send, $status);
        } elseif ($status == 15) {
            $note = 'Giao dịch viên gửi yêu cầu giải ngân';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_cht = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_cht);
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
                "bank_branch" => !empty($contract['receiver_infor']['bank_branch']) ? $contract['receiver_infor']['bank_branch'] : $contract['receiver_infor']['bank_name'],
                "store_name" => $contract['store']['name'],
                "customer_name" => $contract['customer_infor']['customer_name'],
                "code_contract" => $contract['code_contract'],
                "product" => $contract['loan_infor']['type_loan']['text'],
                "product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : 'vaynhanh',
                "note" => !empty($this->dataPost['note']) ? $this->dataPost['note'] : "",
                "API_KEY" => $this->config->item('API_KEY'),
                "email" => $contract['created_by'],
                "full_name" => !empty($user_created['full_name']) ? $user_created['full_name'] : "",
                "number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
                "amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
            );
            $this->sendEmailApprove($user_ids_approve, $data_send, $status);
        } elseif ($status == 16) {
            $note = 'Kế toán đã tạo lệnh giải ngân';
            $cht_id = array(
                '5de726c9d6612b6f2a617ef5'
            );
            $allusers = $this->getUserbyStores($contract['store']['id']);
            $user_cht = $this->getUserGroupRole($cht_id);
            $arr = array_intersect($allusers, $user_cht);
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
            $arr = array_intersect($allusers, $user_cht);
            $user_ids = array_values($arr);
            // user created
            $user_created = $this->user_model->findOne(array('email' => $contract['created_by']));
            array_push($user_ids, (string)$user_created['_id']);
            // ke toan
            $kt_id = array(
                '5de726fcd6612b77824963b9'
            );
            $kt_ids = $this->getUserGroupRole($kt_id);
            $user_ids = array_merge($user_ids, $kt_ids);
        } elseif ($status == 18) {
            $note = 'Giải ngân thất bại';
            $kt_id = array(
                '5de726fcd6612b77824963b9'
            );
            $user_ids = $this->getUserGroupRole($kt_id);
        } else {
            $note = $this->dataPost['note'];
        }

//      // oke
        $dataSocket = array();
        if (!empty($user_ids)) {
            $user_ids = array_values($user_ids);
            foreach ($user_ids as $u) {
                $data_notification = [
                    'action_id' => (string)$contract['_id'],
                    'action' => 'contract',
                    'title' => $contract['code_contract'],
                    'detail' => 'pawn/detail?id=' . (string)$contract['_id'],
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
                    'detail' => 'pawn/detail?id=' . (string)$contract['_id'],
                    'title' => $contract['customer_infor']['customer_name'] . ' - ' . $contract['store']['name'],
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
                'detail' => 'pawn/detail?id=' . (string)$contract['_id'],
                'title' => $contract['code_contract'],
                'note' => $contract['customer_infor']['customer_name'] . ' - ' . $contract['store']['name'],
                'users' => $user_ids_approve,
                'created_at' => $this->createdAt,
            );
            $dataSocket['approve'] = $dataUserApprove;
        }

        $dataContract = array(
            'status' => (int)$this->dataPost['status'],
            'action_id' => (string)$contract['_id'],
            'action' => 'contract',
            'detail' => 'pawn/detail?id=' . (string)$contract['_id'],
            'title' => $contract['code_contract'],
            'note' => $note,
            'users' => $user_ids,
            'created_at' => $this->createdAt,
        );
        $dataSocket['status'] = $dataContract;
//      $this->transferSocket($dataSocket);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Approve success',
            'data' => $dataSocket
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function sendEmailApprove($user_id, $data, $status)
    {

        foreach ($user_id as $key => $value) {
            if ($status == 2 || $status == 5 || $status == 6 || $status == 15 || $status == 7 || $status == 8) {
                $dataUser = $this->user_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($value)));
                $email = !empty($dataUser['email']) ? $dataUser['email'] : "";

                $full_name = !empty($dataUser['full_name']) ? $dataUser['full_name'] : "";
                if (!empty($email)) {
                    $data['email'] = $email;
                    $data['full_name'] = $full_name;
                    $data['API_KEY'] = $this->config->item('API_KEY');
                    $this->user_model->send_Email($data);


                }
            }
        }

        //gửi cho giao dịch viên
        if ($status == 8 && !empty($data['created_by'])) {
            if (!empty($data['created_by'])) {
                $data['email'] = $data['created_by'];
                $data['API_KEY'] = $this->config->item('API_KEY');
                // return $data;
                $this->user_model->send_Email($data);

            }
        }

        // status == 6 send email cho khachs hang (sau khi hội sở duyệt, gửi hồ sơ lại cho khach hang)
        if ($status == 6 && !empty($data['customer_email'])) {
            if (!empty($data['customer_email'])) {
                $data['code'] = 'vfc_president_aproved_send_customer';
                $data['email'] = $data['customer_email'];
                $data['API_KEY'] = $this->config->item('API_KEY');
                // return $data;


                $this->user_model->send_Email($data);

            }
        }

    }

    private function transferSocket($data)
    {
        $version = new Version2X($this->config->item('IP_SOCKET_SERVER'));
        $dataNotify['res'] = $data['status'];
        if (!empty($data['approve'])) {
            $dataApprove['res'] = $data['approve'];
        }
        try {
            $client = new Client($version);
            $client->initialize();
            $client->emit('notify_status', $dataNotify);
            if (!empty($dataApprove)) {
                $client->emit('notify_approve', $dataApprove);
            }
            $client->close();
        } catch (Exception $e) {

        }

    }

    private function checkApproveByAccessRight($roleAccessRights, $status)
    {
        $isAccess = false;
        //Status = 2 = Nhân viên bấm Gửi duyệt cho CHT = 5dedd24f68a3ff3100003649
        //Status = 3 = Hủy HĐ = 5db6b8c9d6612bceeb712375
        //Status = 4 = CHT từ chối = 5dedd2c868a3ff310000364a
        //Status = 5 = CHT duyệt = 5dedd2d868a3ff310000364b
        //Status = 6 = Hội sở duyệt = 5dedd2e668a3ff310000364c
        //Status = 7 = Kế toán từ chối = 5def401b68a3ff1204003adb
        //Status = 15 = GDV đã hoàn thiện hồ sơ và gửi lệnh giải ngân cho kế toán = 5dedd32468a3ff310000364d
        if ($status == 2 && in_array('5dedd24f68a3ff3100003649', $roleAccessRights) ||
            $status == 3 && in_array('5db6b8c9d6612bceeb712375', $roleAccessRights) ||
            $status == 4 && in_array('5dedd2c868a3ff310000364a', $roleAccessRights) ||
            $status == 5 && in_array('5dedd2d868a3ff310000364b', $roleAccessRights) ||
            $status == 6 && in_array('5dedd2e668a3ff310000364c', $roleAccessRights) ||
            $status == 7 && in_array('5def401b68a3ff1204003adb', $roleAccessRights) ||
            $status == 15 && in_array('5dedd32468a3ff310000364d', $roleAccessRights))
            $isAccess = true;
        return $isAccess;
    }

    private function checkStatusForApprove($oldStatus, $status)
    {
        $isCorrect = false;
        //Status = 21,25,29,11 = Hủy gia hạn
        //TPGD
        if ($oldStatus == 21 && $status == 17) $isCorrect = true;
        if ($oldStatus == 25 && $status == 17) $isCorrect = true;
        if ($oldStatus == 29 && $status == 17) $isCorrect = true;
        if ($oldStatus == 11 && $status == 17) $isCorrect = true;
        if ($oldStatus == 30 && $status == 17) $isCorrect = true;
        //Status = 23,27,31,12 = Hủy cơ cấu
        if ($oldStatus == 23 && $status == 17) $isCorrect = true;
        if ($oldStatus == 27 && $status == 17) $isCorrect = true;
        if ($oldStatus == 31 && $status == 17) $isCorrect = true;
        if ($oldStatus == 12 && $status == 17) $isCorrect = true;
        if ($oldStatus == 32 && $status == 17) $isCorrect = true;
        //TP GD gửi ASM duyệt gia hạn
        if ($oldStatus == 17 && $status == 30) $isCorrect = true;
        //TP GD gửi ASM duyệt cơ cấu
        if ($oldStatus == 17 && $status == 32) $isCorrect = true;
        //THN gửi TP THN duyệt gia hạn
        if ($oldStatus == 17 && $status == 11) $isCorrect = true;
        //THN gửi TP THN duyệt cơ cấu
        if ($oldStatus == 17 && $status == 12) $isCorrect = true;
        //TP THN duyệt gia hạn
        if ($oldStatus == 11 && $status == 29) $isCorrect = true;
        //TP THN duyệt cơ cấu
        if ($oldStatus == 12 && $status == 31) $isCorrect = true;
        //TP THN gửi HS duyệt CC
        if ($oldStatus == 12 && $status == 27) $isCorrect = true;
        //TP THN không duyệt CC
        if ($oldStatus == 12 && $status == 14) $isCorrect = true;
        //Status = 21 = Trưởng phòng giao dịch duyệt gia hạn sang THN
        if ($oldStatus == 21 && $status == 11) $isCorrect = true;
        //Status = 21 = Trưởng phòng giao dịch duyệt gia hạn sang kế toán
        if ($oldStatus == 21 && $status == 29) $isCorrect = true;
        //Status = 21 = Trưởng phòng giao dịch duyệt gia hạn sang hội sở
        if ($oldStatus == 21 && $status == 25) $isCorrect = true;
        //Status = 22 = CVKD gửi lại TPGD duyệt gia hạn
        if ($oldStatus == 22 && $status == 21) $isCorrect = true;
        //Status = 25 = Trưởng phòng thu hồi nợ duyệt gia hạn
        if ($oldStatus == 25 && $status == 11) $isCorrect = true;
        //Status = 26 = CVKH gửi HS duyệt gia hạn bị hủy
        if ($oldStatus == 26 && $status == 25) $isCorrect = true;
        //Status = 30 = CVKH gửi KT duyệt gia hạn bị hủy
        if ($oldStatus == 30 && $status == 29) $isCorrect = true;
        //Status = 23 = Trưởng phòng thu hồi nợ duyệt cơ cấu
        if ($oldStatus == 23 && $status == 12) $isCorrect = true;
        //Status = 11 = THN gửi kế toán duyệt gia hạn
        if ($oldStatus == 11 && $status == 29) $isCorrect = true;
        //Status = 11 = THN gửi hội sở duyệt gia hạn
        if ($oldStatus == 11 && $status == 25) $isCorrect = true;
        //Status = 11 = THN gửi hội sở duyệt gia hạn
        if ($oldStatus == 11 && $status == 13) $isCorrect = true;
        //Status = 13 = CVKD gửi thu hồi nợ duyệt gia hạn bị hủy
        if ($oldStatus == 13 && $status == 11) $isCorrect = true;
        //Status = 24 = CVKH gửi KT duyệt cơ cấu bị hủy
        if ($oldStatus == 24 && $status == 23) $isCorrect = true;
        //Status = 14 = CVKH gửi THN duyệt cơ cấu bị hủy
        if ($oldStatus == 14 && $status == 12) $isCorrect = true;
        //Status = 28 = CVKH gửi HS duyệt cơ cấu bị hủy
        if ($oldStatus == 28 && $status == 27) $isCorrect = true;
        //Status = 32 = CVKH gửi KT duyệt cơ cấu bị hủy
        if ($oldStatus == 32 && $status == 31) $isCorrect = true;
        //Status = 21 = Trưởng phòng giao dịch không duyệt gia hạn
        if ($oldStatus == 21 && $status == 22) $isCorrect = true;
        //Status = 23 = trưởng phòng giao dịch gửi duyệt cơ cấu sang kế toán
        if ($oldStatus == 23 && $status == 31) $isCorrect = true;
        //Status = 23 = trưởng phòng giao dịch duyệt cơ cấu
        if ($oldStatus == 23 && $status == 27) $isCorrect = true;
        //Status = 23 = trưởng phòng giao dịch không duyệt cơ cấu
        if ($oldStatus == 23 && $status == 24) $isCorrect = true;
        //Status = 21 = Giao dịch viên gửi yêu cầu gia hạn
        if ($oldStatus == 17 && $status == 21) $isCorrect = true;
        //Status = 23 = Giao dịch viên gửi yêu cầu cơ cấu
        if ($oldStatus == 17 && $status == 23) $isCorrect = true;
        //Status = 25 = Hội sở bấm duyệt cho gia hạn
        if ($oldStatus == 25 && $status == 29) $isCorrect = true;
        //Status = 22 = Hội sở bấm không duyệt cho gia han hop đông
        if ($oldStatus == 25 && $status == 26) $isCorrect = true;
        //Status = 27 = Hội sở bấm duyệt cơ cấu
        if ($oldStatus == 27 && $status == 31) $isCorrect = true;
        //Status = 27 = Hội sở bấm không duyệt cơ cấu
        if ($oldStatus == 27 && $status == 28) $isCorrect = true;
        //Status = 27 = Ban phê duyệt duyệt cơ cấu
        if ($oldStatus == 27 && $status == 34) $isCorrect = true;
        //Status = 21 = TPGD gửi ASM duyệt gia hạn
        if ($oldStatus == 21 && $status == 30) $isCorrect = true;
        //Status = 23 = TPGD gửi ASM duyệt cơ cấu
        if ($oldStatus == 23 && $status == 32) $isCorrect = true;
        //Status = 30 = ASM không duyệt gia hạn
        if ($oldStatus == 30 && $status == 41) $isCorrect = true;
        //Status = 32 = ASM bấm không duyệt cơ cấu
        if ($oldStatus == 32 && $status == 42) $isCorrect = true;
        //Status = 41 = GDV gửi ASM duyệt gia hạn
        if ($oldStatus == 41 && $status == 30) $isCorrect = true;
        //Status = 42 = GDV gửi ASM duyệt cơ cấu
        if ($oldStatus == 42 && $status == 32) $isCorrect = true;
        //Status = 30 11 = ASM gửi TP THN duyệt gia hạn
        if ($oldStatus == 30 && $status == 11) $isCorrect = true;
        //Status = 32 12 = ASM gửi TP THN duyệt cơ cấu
        if ($oldStatus == 32 && $status == 12) $isCorrect = true;
        //Status = 30 25 = ASM gửi TP GD duyệt gia hạn
        if ($oldStatus == 30 && $status == 25) $isCorrect = true;
        //Status = 32 27 = ASM gửi TP GD duyệt cơ cấu
        if ($oldStatus == 32 && $status == 27) $isCorrect = true;
        //Status = 30 29 = ASM gửi GDV tạo phiếu thu GH
        if ($oldStatus == 30 && $status == 29) $isCorrect = true;
        //Status = 32 31 = ASM gửi GDV tạo phiếu thu CC
        if ($oldStatus == 32 && $status == 31) $isCorrect = true;
        //Status = 30 41 = ASM không duyệt gia hạn
        if ($oldStatus == 30 && $status == 41) $isCorrect = true;
        //Status = 32 42 = ASM không duyệt cơ cấu
        if ($oldStatus == 32 && $status == 42) $isCorrect = true;
        //Status = 29 33 = KT duyệt gia hạn
        if ($oldStatus == 29 && $status == 33) $isCorrect = true;
        //Status = 31 34 = KT duyệt gia hạn
        if ($oldStatus == 31 && $status == 34) $isCorrect = true;
        //Status = 23 = Hợp đồng đã được gia hạn => old_status = 22 = chờ kế toán duyệt gia hạn
        if ($oldStatus == 22 && $status == 23) $isCorrect = true;
        //Status = 2 = Nhân viên bấm Gửi duyệt cho CHT => old_status = 1 = Mới tạo
        if ($oldStatus == 1 && $status == 2) $isCorrect = true;
        //Status = 4 = CHT từ chối => old_status = 2
        if ($oldStatus == 2 && $status == 4) $isCorrect = true;
        //Status = 2 = CHT từ chối => old_status = 4
        if ($oldStatus == 4 && $status == 2) $isCorrect = true;
        //Status = 5 = CHT duyệt => old_status = 2
         if ($oldStatus == 2 && $status == 5) $isCorrect = true;
        //Status = 6 = Hội sở duyệt => old_status = 5
        if ($oldStatus == 5 && $status == 6) $isCorrect = true;
        //Status = 8 = Hội sở bấm từ chối duyệt cho gia han hop đông  => old_status = 5
        if ($oldStatus == 5 && $status == 8) $isCorrect = true;
        //Status = 5 = Cửa hang trưởng gia hạn lần 2  => old_status = 8
        if ($oldStatus == 8 && $status == 5) $isCorrect = true;
        //Status = 3 = Cửa hang trưởng hủy hợp đồng
        if ($oldStatus == 8 && $status == 3) $isCorrect = true;
        //Status = 5 = Hội sở hủy => old_status = 3
        if ($oldStatus == 5 && $status == 3) $isCorrect = true;
        //Status = 3 = Hội sở hủy => old_status = 10
        if ($oldStatus == 10 && $status == 3) $isCorrect = true;

        //Status = 7 = Hội sở hủy => old_status = 10
        if ($oldStatus == 10 && $status == 7) $isCorrect = true;
        //Status = 7 = Kế toán từ chối => old_status = 6
        if ($oldStatus == 6 && $status == 7) $isCorrect = true;
        //Status = 15 = GDV đã hoàn thiện hồ sơ và gửi lệnh giải ngân cho kế toán
        //=> old_status = 6 = hội sở duyệt. Hoặc là
        //=> old_status = 7 = kế toán từ chối vì 1 lí do nào đó
        if (($oldStatus == 6 && $status == 15) || ($oldStatus == 7 && $status == 15) || ($oldStatus == 15 && $status == 7)) $isCorrect = true;
        //Status = 3 = Hủy HĐ
        //=> old_status = 2 = Nhân viên bấm Gửi duyệt cho CHT => CHT hủy HĐ
        //=> old_status = 5 = CHT duyệt và đưa lên hội sở => Hội sở hủy HĐ
        //=> old_status = 15 = GDV đã hoàn thiện hồ sơ và gửi lệnh giải ngân cho kế toán  => Kế toán hủy HĐ
        //=> old_status = 7 = Kế toán từ chối => GDV hoàn thiện LẠI hồ sơ nhưng ko đủ đk  => GDV hủy HĐ
        if ($status == 3 && ($oldStatus == 6 || $oldStatus == 2 || $oldStatus == 5 || $oldStatus == 15 || $oldStatus == 7)) $isCorrect = true;
        //ngân lượng=> hủy
        if ($oldStatus == 10 && $status == 3) $isCorrect = true;
        //chời giải ngân=> hủy
        if ($oldStatus == 15 && $status == 3) $isCorrect = true;
//      //cht duyet => cho asm duyet
//      if ($oldStatus == 2 && $status == 35) $isCorrect = true;
//      //asm duyet => cho hs duyet
//      if ($oldStatus == 35 && $status == 5) $isCorrect = true;
//      //asm duyet => đã duyệt
//      if ($oldStatus == 35 && $status == 6) $isCorrect = true;
//      // cho asm duyet => asm khong duyet
//      if ($oldStatus == 35 && $status == 36) $isCorrect = true;
//      // asm không duyet => asm cho duyet
//      if ($oldStatus == 36 && $status == 35) $isCorrect = true;
//      //asm khong duyet => cho cht duyet
//      if ($oldStatus == 36 && $status == 2) $isCorrect = true;
//      //hs khong duyet => cho asm duyet
//      if ($oldStatus == 8 && $status == 35) $isCorrect = true;
        return $isCorrect;
    }

    function slugify($text)
    {
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

    function vn_to_str($str)
    {
        $unicode = array(
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        );
        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }
        $str = str_replace(' ', '_', $str);
        return $str;
    }


    public function lai_phi_bang_ky_den_thoi_diem_dao_han_post()
    {
        //Lấy thông tin kì hiện tại
        $tempPlan = $this->temporary_plan_contract_model->find_where(array(
            "code_contract_disbursement" => "HĐCC/ĐKXM/HN494TC/2004/05"
        ));
        $tong_lai_phi_con_lai_den_thoi_diem_dao_han = 0;
        foreach ($tempPlan as $item) {
            $tien_lai_1ky_con_lai = !empty($item['tien_lai_1ky_con_lai']) ? $item['tien_lai_1ky_con_lai'] : 0;
            $tien_phi_1ky_con_lai = !empty($item['tien_phi_1ky_con_lai']) ? $item['tien_phi_1ky_con_lai'] : 0;
            $tong_lai_phi_con_lai_den_thoi_diem_dao_han = $tong_lai_phi_con_lai_den_thoi_diem_dao_han +
                $tien_lai_1ky_con_lai +
                $tien_phi_1ky_con_lai;
        }
        return $tong_lai_phi_con_lai_den_thoi_diem_dao_han;
    }

    public function log_gic($request, $data, $code_contract_disbursement, $type)
    {

        $dataInser = array(
            "type" => $type,
            "code_contract_disbursement" => $code_contract_disbursement,
            "res_data" => $data,
            "request_data" => $request,
            "created_at" => $this->createdAt
        );
        $this->log_gic_model->insert($dataInser);
    }


    public function log_mic($request, $response, $code_contract_disbursement, $type)
    {

        $dataInser = array(
            "type" => $type,
            "code_contract_disbursement" => $code_contract_disbursement,
            "response_data" => $response,
            "request_data" => $request,
            "created_at" => $this->createdAt
        );
        $this->log_mic_model->insert($dataInser);
    }

    function WriteLog($fileName, $data, $breakLine = true, $addTime = true)
    {

        $fp = fopen("log/" . $fileName, 'a');
        if ($fp) {
            if ($breakLine) {
                if ($addTime)
                    $line = date("H:i:s, d/m/Y:  ", time()) . $data . " \n";
                else
                    $line = $data . " \n";
            } else {
                if ($addTime)
                    $line = date("H:i:s, d/m/Y:  ", time()) . $data;
                else
                    $line = $data;
            }
            fwrite($fp, $line);
            fclose($fp);
        }
    }


    public function get_count_old_contract_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $property = !empty($this->dataPost['property']) ? $this->dataPost['property'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
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
        if (!empty($property)) {
            $condition['property'] = $property;
        }
        if (!empty($status)) {
            $condition['status'] = (int)$status;
        }
        if (!empty($code_contract)) {
            $condition['code_contract'] = $code_contract;
        }
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = $code_contract_disbursement;
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = $customer_name;
        }
        if (!empty($customer_phone_number)) {
            $condition['customer_phone_number'] = $customer_phone_number;
        }
        if (!empty($condition)) {
//          $contract = $this->contract_model->getCountContractByRole($condition);
            $contract = $this->contract_model->getCountOldContract($condition);
        } else {
            $contract = $this->contract_model->countOldContract();
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_all_old_data_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $property = !empty($this->dataPost['property']) ? $this->dataPost['property'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
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
        if (!empty($property)) {
            $condition['property'] = $property;
        }
        if (!empty($status)) {
            $condition['status'] = (int)$status;
        }
        if (!empty($code_contract)) {
            $condition['code_contract'] = $code_contract;
        }
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = $code_contract_disbursement;
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = $customer_name;
        }
        if (!empty($customer_phone_number)) {
            $condition['customer_phone_number'] = $customer_phone_number;
        }
        $per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
        $uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
        if (!empty($condition)) {
            $contract = $this->contract_model->getOldContractByRole($condition, $per_page, $uriSegment);
        } else {
            $contract = $this->contract_model->findOldContract($per_page, $uriSegment);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function spreadsheetFeeLoan($code_contract, $customer_info, $investor_code, $disbursement_date, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $insurrance)
    {
//      $amount_money  tong tien
//      $type_loan  hinh thuc vay
//      $number_day_loan  tong so ngay vay
//      $period_pay_interest   so ngay thuc te 1 ky
//      $type_interest   hinh thuc tra lai
//      $insurrance  bao hiem

        // get thông tin phí vay

        $contract = $this->contract_model->findOne(array("code_contract" => $code_contract));
        $code_contract_disbursement = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : "";
        $fee = array();
        // lai suat tu van
        $pham_tram_phi_tu_van = 0;
        // lai suat tham dinh
        $pham_tram_phi_tham_dinh = 0;
        // lai suat nha dau tu
        $lai_suat_ndt = 0;
        if (!empty($contract['fee'])) {
            $fee = $contract['fee'];
            if (empty($fee['percent_advisory'])) {
                $fee['percent_advisory'] = 0;
                $pham_tram_phi_tu_van = 0;
            } else {
                $pham_tram_phi_tu_van = floatval($fee['percent_advisory']) / 100;
            }
            if (empty($fee['percent_expertise'])) {
                $fee['percent_expertise'] = 0;
                $pham_tram_phi_tham_dinh = 0;
            } else {
                $pham_tram_phi_tham_dinh = floatval($fee['percent_expertise']) / 100;
            }
            if (empty($fee['percent_interest_customer'])) {
                $fee['percent_interest_customer'] = 0;
                $lai_suat_ndt = 0;
            } else {
                $lai_suat_ndt = floatval($fee['percent_interest_customer']) / 100;
            }
        }
        $tien_goc = (int)$amount_money;

        // số kỳ vay
        $so_ky_vay = (int)$number_day_loan / (int)$period_pay_interest;

        $lai_luy_ke = 0;
        //Hinh thức lãi dư nợ giảm dần
        if ($type_interest == 1) {
            //tiền trả 1 kỳ pow(2, -3)
            //$goc_lai_1_ky = ($tien_goc * $lai_suat_ndt) / (1 - pow((1 + $lai_suat_ndt), -$so_ky_vay));
            $goc_lai_1_ky = -pmt($lai_suat_ndt, $so_ky_vay, $tien_goc);
            // tong tien phai tra 1 ky
            $tien_tra_1_ky = $goc_lai_1_ky + ($pham_tram_phi_tu_van + $pham_tram_phi_tham_dinh) * $tien_goc;
            //tiền trả 1 kỳ làm tròn
            $round_tien_tra_1_ky = round($tien_tra_1_ky);

            //gốc còn lại
            $tien_goc_con = $tien_goc;
            //tong cac loai phi
//          $tong_phi_tu_van = 0;
//          $tong_phi_tham_dinh  = 0;
            //khoan vay 1 ky
            for ($i = 1; $i <= $so_ky_vay; $i++) {
                //kỳ trả
                $date_ky_tra = $disbursement_date + (intval($period_pay_interest) * 24 * 60 * 60 * $i) - 24 * 60 * 60;
                $ky_tra = $i;
                $current_plan = $i == 1 ? $i : 2;
                //lãi
                $lai_ky = $lai_suat_ndt * $tien_goc_con;
                // goc da tra
                $tien_goc_1ky = $goc_lai_1_ky - $lai_ky;
                // phi tu van quan ly
                $phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
                // phi tham dinh
                $phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
                //tổng phí lãi 1 kỳ
                $tong_phi_lai = $phi_tu_van + $phi_tham_dinh + $lai_ky;
                $lai_luy_ke = $lai_luy_ke + $tong_phi_lai;
                //tiền gốc
                //tiền gốc còn lại
                $tien_goc_con = $tien_goc_con - $tien_goc_1ky;
                //tiền tất toán
                $data_1ky = array(
                    'code_contract' => $code_contract,
                    'code_contract_disbursement' => $code_contract_disbursement,
                    'type' => $type_interest,
                    'current_plan' => $current_plan, // kỳ hiện tại phải đóng
                    'is_penalty' => false, // kỳ bị phat
                    'penalty' => 0,
                    'customer_infor' => $customer_info,
                    'investor_code' => $investor_code,
                    'ky_tra' => $ky_tra,
                    'ngay_ky_tra' => $date_ky_tra,
                    'tien_tra_1_ky' => $tien_tra_1_ky,
                    'round_tien_tra_1_ky' => $round_tien_tra_1_ky,
                    'tien_goc_1ky' => $tien_goc_1ky,
                    'tong_phi_lai' => $tong_phi_lai,
                    'phi_tu_van' => $phi_tu_van,
                    'phi_tham_dinh' => $phi_tham_dinh,
                    'lai_ky' => $lai_ky,
                    'lai_luy_ke' => $lai_luy_ke,
                    'tien_goc_con' => $tien_goc_con,
                    'da_thanh_toan' => 0,
                    'status' => 1, // 1: sap toi, 2: da dong, 3: qua han
                    'created_at' => $this->createdAt,

                    "tien_goc_1ky_phai_tra" => $tien_goc_1ky,
                    "tien_goc_1ky_da_tra" => 0,
                    "tien_goc_1ky_con_lai" => $tien_goc_1ky,

                    "tien_lai_1ky_phai_tra" => $lai_ky,
                    "tien_lai_1ky_da_tra" => 0,
                    "tien_lai_1ky_con_lai" => $lai_ky,

                    "tien_phi_1ky_phai_tra" => $phi_tu_van + $phi_tham_dinh,
                    "tien_phi_1ky_da_tra" => 0,
                    "tien_phi_1ky_con_lai" => $phi_tu_van + $phi_tham_dinh,

                    "fee_delay" => 0,
                    "fee_finish_contract" => 0,
                    "fee_extend" => 0

                );
                $this->contract_tempo_model->insert($data_1ky);
                //Insert log
                $insertLog = array(
                    "type" => "insert",
                    "new" => $data_1ky,
                    "created_at" => $this->createdAt
                );
                $this->log_contract_tempo_model->insert($insertLog);

            }
            return;
        } else {

            //hình thức lãi hàng tháng, gốc cuối kỳ
            //khoan vay 1 ky
            for ($i = 1; $i <= $so_ky_vay; $i++) {
                //kỳ trả
                $date_ky_tra = $disbursement_date + (intval($period_pay_interest) * 24 * 60 * 60 * $i) - 24 * 60 * 60;
                $ky_tra = $i;
                $current_plan = $i == 1 ? $i : 2;
                //lãi
                $lai_ky = round($lai_suat_ndt * $tien_goc);
                // goc da tra
                $tien_goc_1ky = $i == $so_ky_vay ? $tien_goc : 0;
                //tiền gốc còn lại
                $tien_goc_con = $i == $so_ky_vay ? 0 : $tien_goc;
                // phi tu van quan ly
                $phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
                // phi tham dinh
                $phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
                //tổng phí lãi 1 kỳ
                $tong_phi_lai = $phi_tu_van + $phi_tham_dinh + $lai_ky;
                $tien_tra_1_ky = $tien_goc_1ky + $tong_phi_lai;
                $lai_luy_ke = $lai_luy_ke + $tong_phi_lai;

                $data_1ky = array(
                    'code_contract' => $code_contract,
                    'code_contract_disbursement' => $code_contract_disbursement,
                    'customer_infor' => $customer_info,
                    'investor_code' => $investor_code,
                    'type' => $type_interest,
                    'current_plan' => $current_plan, // kỳ hiện tại phải đóng
                    'is_penalty' => false, // kỳ bị phat
                    'penalty' => 0,
                    'ky_tra' => $ky_tra,
                    'ngay_ky_tra' => $date_ky_tra,
                    'tien_tra_1_ky' => $tien_tra_1_ky,
                    'tien_goc_1ky' => $tien_goc_1ky,
                    'tien_goc_con' => $tien_goc_con,
                    'da_thanh_toan' => 0,
                    'phi_tu_van' => $phi_tu_van,
                    'phi_tham_dinh' => $phi_tham_dinh,
                    'lai_ky' => $lai_ky,
                    'lai_luy_ke' => $lai_luy_ke,
                    'status' => 1,  // 1: sap toi, 2: da dong, 3: qua han
                    'created_at' => $this->createdAt,

                    "tien_goc_1ky_phai_tra" => $tien_goc_1ky,
                    "tien_goc_1ky_da_tra" => 0,
                    "tien_goc_1ky_con_lai" => $tien_goc_1ky,

                    "tien_lai_1ky_phai_tra" => $lai_ky,
                    "tien_lai_1ky_da_tra" => 0,
                    "tien_lai_1ky_con_lai" => $lai_ky,

                    "tien_phi_1ky_phai_tra" => $phi_tu_van + $phi_tham_dinh,
                    "tien_phi_1ky_da_tra" => 0,
                    "tien_phi_1ky_con_lai" => $phi_tu_van + $phi_tham_dinh,

                    "fee_delay" => 0,
                    "fee_finish_contract" => 0,
                    "fee_extend" => 0

                );
                $this->contract_tempo_model->insert($data_1ky);
                //Insert log
                $insertLog = array(
                    "type" => "insert",
                    "new" => $data_1ky,
                    "created_at" => $this->createdAt
                );
                $this->log_contract_tempo_model->insert($insertLog);
            }
            return;
        }
    }

    private function generateFeeLoanbyMonth($code_contract, $customer_info, $investor_code, $disbursement_date, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $insurrance)
    {
//      $amount_money  tong tien
//      $type_loan  hinh thuc vay
//      $number_day_loan  tong so ngay vay
//      $period_pay_interest   so ngay thuc te 1 ky
//      $type_interest   hinh thuc tra lai
//      $insurrance  bao hiem

        // get thông tin phí vay

        $contract = $this->contract_model->findOne(array("code_contract" => $code_contract));
        $code_contract_disbursement = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : "";
        $fee = array();
        // lai suat tu van
        $pham_tram_phi_tu_van = 0;
        // lai suat tham dinh
        $pham_tram_phi_tham_dinh = 0;
        // lai suat nha dau tu
        $lai_suat_ndt = 0;
        if (!empty($contract['fee'])) {
            $fee = $contract['fee'];
            if (empty($fee['percent_advisory'])) {
                $fee['percent_advisory'] = 0;
                $pham_tram_phi_tu_van = 0;
            } else {
                $pham_tram_phi_tu_van = floatval($fee['percent_advisory']) / 100;
            }
            if (empty($fee['percent_expertise'])) {
                $fee['percent_expertise'] = 0;
                $pham_tram_phi_tham_dinh = 0;
            } else {
                $pham_tram_phi_tham_dinh = floatval($fee['percent_expertise']) / 100;
            }
            if (empty($fee['percent_interest_customer'])) {
                $fee['percent_interest_customer'] = 0;
                $lai_suat_ndt = 0;
            } else {
                $lai_suat_ndt = floatval($fee['percent_interest_customer']) / 100;
            }
        }
        $tien_goc = $amount_money;

        // số kỳ vay
        $so_ky_vay = (int)$number_day_loan / (int)$period_pay_interest;

        $lai_luy_ke_thang = 0;
        //Hinh thức lãi dư nợ giảm dần
        if ($type_interest == 1) {
            //tiền trả 1 kỳ pow(2, -3)
            //$goc_lai_1_ky = ($tien_goc * $lai_suat_ndt) / (1 - pow((1 + $lai_suat_ndt), -$so_ky_vay));
            $goc_lai_1_ky = -pmt($lai_suat_ndt, $so_ky_vay, $tien_goc);
            // tong tien phai tra 1 ky
            $tien_tra_1_ky = $goc_lai_1_ky + ($pham_tram_phi_tu_van + $pham_tram_phi_tham_dinh) * $tien_goc;
            //tiền trả 1 kỳ làm tròn
            $round_tien_tra_1_ky = round($tien_tra_1_ky);

            //gốc còn lại
            $tien_goc_con = $tien_goc;
            $tien_goc_con_thang = $tien_goc;
            //tong cac loai phi
//          $tong_phi_tu_van = 0;
//          $tong_phi_tham_dinh  = 0;
            //khoan vay 1 ky
            $tien_lai_chenh_lech = 0;
            $tien_goc_chenh_lech = 0;
            $tien_goc_1thang = 0;
            $so_ngay_lai_con_lai = 0;
            $du_no_lai_thang_truoc = 0;
            $du_no_phi_thang_truoc = 0;
            $tien_lai_1thang_hien_tai = 0;
            $tien_tra_1thang_thang_sau = 0;
            $tien_lai_1thang_thang_sau = 0;
            $tien_goc_1thang_thang_sau = 0;
            $tong_phi_lai_thang_sau = 0;
            $tong_phi_hien_tai = 0;
            $lai_con_lai_luy_ke = 0;
            $phi_con_lai_luy_ke = 0;
            $goc_con_lai_thang_hien_tai = 0;
            $goc_con_lai_chua_thu_thang_truoc = 0;
            $du_no_goc_thang_truoc = 0;
            for ($i = 0; $i <= $so_ky_vay; $i++) {
                //kỳ trả
                // phi tu van quan ly
                $lai_ky = $lai_suat_ndt * $tien_goc_con;
                // goc da tra
                $tien_goc_1ky = $goc_lai_1_ky - $lai_ky;
                // phi tu van quan ly
                $phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
                // phi tham dinh
                $phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
                //tổng phí lãi 1 kỳ
                $tong_phi_lai = $phi_tu_van + $phi_tham_dinh + $lai_ky;
                //tiền gốc

                $date_ky_tra = $disbursement_date + (intval($period_pay_interest) * 24 * 60 * 60 * $i) - 24 * 60 * 60;
                $ngay_ky_tra = date('Y-m-d', $date_ky_tra);
                $timestamp_ngay_ky_tra = strtotime($ngay_ky_tra);
                $day_last_month = date("Y-m-t", $timestamp_ngay_ky_tra);
                $last_date = strtotime($day_last_month);
                $timestamp_date = strtotime($ngay_ky_tra);
                $datediff = $last_date - $timestamp_date;
                $count = round($datediff / (60 * 60 * 24));
                $time = date('m/Y', $date_ky_tra);
                $month = date('m', $date_ky_tra);
                $year = date('Y', $date_ky_tra);
                // goc da tra
                if ($i == 0) {
                    $ngay_lai_thuc_te = $count;
                    $tien_tra_1thang_hien_tai = ($tien_tra_1_ky / (int)$period_pay_interest) * $count;
                    $tien_goc_1thang = ($tien_goc_1ky / (int)$period_pay_interest) * $count;
                    $tien_lai_1thang_hien_tai = ($lai_ky / (int)$period_pay_interest) * $count;
                    $round_tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai;
                    $tong_phi_lai_hien_tai = ($tong_phi_lai / (int)$period_pay_interest) * $count;
                    $tien_tra_1thang_thang_sau = $tien_tra_1_ky - $tien_tra_1thang_hien_tai;
                    $tien_lai_1thang_thang_sau = $lai_ky - $tien_lai_1thang_hien_tai;
                    $tien_goc_1thang_thang_sau = $tien_goc_1ky - $tien_goc_1thang;
                    $tong_phi_lai_thang_sau = $tong_phi_lai - $tong_phi_lai_hien_tai;
                    $tong_phi_hien_tai = $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $du_no_lai_thang_truoc = 0;
                    $du_no_phi_thang_truoc = 0;
                    $du_no_goc_thang_truoc = 0;
                    $lai_con_lai_luy_ke = $lai_con_lai_luy_ke + $tien_lai_1thang_hien_tai;
                    $phi_con_lai_luy_ke = $phi_con_lai_luy_ke + $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $goc_con_lai_thang_hien_tai = $tien_goc_1thang;
                    $goc_con_lai_chua_thu_thang_truoc = 0;
                } else if ($i == $so_ky_vay) {
                    $du_no_goc_thang_truoc = $tien_goc_1thang + $du_no_goc_thang_truoc;
                    $du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tong_phi_hien_tai;
                    $du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai;
                    $tien_tra_1thang_hien_tai = $tien_tra_1thang_thang_sau;
                    $tien_goc_1thang = $tien_goc_1thang_thang_sau;
                    $round_tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai;
                    $tien_lai_1thang_hien_tai = $tien_lai_1thang_thang_sau;
                    $tong_phi_lai_hien_tai = $tong_phi_lai_thang_sau;
                    $lai_con_lai_luy_ke = $lai_con_lai_luy_ke + $tien_lai_1thang_hien_tai;
                    $phi_con_lai_luy_ke = $phi_con_lai_luy_ke + $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $goc_con_lai_thang_hien_tai = $tien_goc_1thang;
                    $goc_con_lai_chua_thu_thang_truoc = $tien_goc;
                } else {
                    $du_no_goc_thang_truoc = $tien_goc_1thang + $du_no_goc_thang_truoc;
                    $du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai;
                    $du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tong_phi_hien_tai;
                    $ngay_lai_thuc_te = $count + $so_ngay_lai_con_lai;
                    $tien_tra_1thang_hien_tai = ($tien_tra_1_ky / (int)$period_pay_interest) * $count + $tien_tra_1thang_thang_sau;
                    $tien_goc_1thang = ($tien_goc_1ky / (int)$period_pay_interest) * $count + $tien_goc_1thang_thang_sau;
                    $tien_lai_1thang_hien_tai = ($lai_ky / (int)$period_pay_interest) * $count + $tien_lai_1thang_thang_sau;
                    $round_tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai;
                    $tong_phi_lai_hien_tai = ($tong_phi_lai / (int)$period_pay_interest) * $count + $tong_phi_lai_thang_sau;
                    $tien_tra_1thang_thang_sau = ($tien_tra_1_ky / (int)$period_pay_interest) * ((int)$period_pay_interest - $count);
                    $tien_lai_1thang_thang_sau = ($lai_ky / (int)$period_pay_interest) * ((int)$period_pay_interest - $count);
                    $tien_goc_1thang_thang_sau = ($tien_goc_1ky / (int)$period_pay_interest) * ((int)$period_pay_interest - $count);
                    $tong_phi_lai_thang_sau = ($tong_phi_lai / (int)$period_pay_interest) * ((int)$period_pay_interest - $count);
                    $tong_phi_hien_tai = $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $lai_con_lai_luy_ke = $lai_con_lai_luy_ke + $tien_lai_1thang_hien_tai;
                    $phi_con_lai_luy_ke = $phi_con_lai_luy_ke + $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $goc_con_lai_thang_hien_tai = $tien_goc_1thang;
                    $goc_con_lai_chua_thu_thang_truoc = $tien_goc;
                }

                //tiền gốc còn lại
                $tien_goc_con = $tien_goc_con - $tien_goc_1ky;
                $tien_goc_con_thang = $tien_goc_con_thang - $tien_goc_1thang;

                $lai_luy_ke_thang = $lai_luy_ke_thang + $tong_phi_lai_hien_tai;

                if ($i == $so_ky_vay) {
                    $data_1thang = array(
                        'code_contract' => $code_contract,
                        "code_contract_disbursement" => $code_contract_disbursement,
                        'type' => $type_interest,
                        'is_penalty' => false, // kỳ bị phat
                        'penalty' => 0,
                        'investor_code' => $investor_code,
                        'time_timestamp' => $date_ky_tra,
                        'time' => $time,
                        'month' => $month,
                        'year' => $year,
                        'count_date_interest' => $so_ngay_lai_con_lai,
                        'tien_tra_1thang' => $tien_tra_1thang_hien_tai,
                        'round_tien_tra_1thang' => $round_tien_tra_1thang_hien_tai,

                        'tien_goc_1thang' => $tien_goc_1thang,
                        'tien_lai_1thang' => $tien_lai_1thang_hien_tai,
                        'tien_phi_1thang' => $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai,

                        'tien_goc_1thang_da_tra' => 0,
                        'tien_lai_1thang_da_tra' => 0,
                        'tien_phi_1thang_da_tra' => 0,

                        'tien_goc_1thang_con_lai' => $tien_goc_1thang,
                        'tien_lai_1thang_con_lai' => $tien_lai_1thang_hien_tai,
                        'tien_phi_1thang_con_lai' => $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai,

                        'lai_con_lai_luy_ke' => $lai_con_lai_luy_ke,
                        'phi_con_lai_luy_ke' => $phi_con_lai_luy_ke,

                        'du_no_goc_thang_truoc' => $du_no_goc_thang_truoc,
                        'du_no_lai_thang_truoc' => $du_no_lai_thang_truoc,
                        'du_no_phi_thang_truoc' => $du_no_phi_thang_truoc,

                        'du_no_goc_thang_truoc_da_tra' => 0,
                        'du_no_lai_thang_truoc_da_tra' => 0,
                        'du_no_phi_thang_truoc_da_tra' => 0,

                        'goc_con_lai_thang_hien_tai' => $goc_con_lai_thang_hien_tai,
                        'tien_goc_con_lai_hop_dong' => $tien_goc_1thang,

                        'goc_con_lai_chua_thu_thang_truoc' => $goc_con_lai_chua_thu_thang_truoc,
                        'goc_con_lai_chua_thu_cuoi_thang_hien_tai' => (float)$contract['loan_infor']['amount_money'],

                        'tong_phi_lai_thang' => $tong_phi_lai_hien_tai,

                        'lai_luy_ke_thang' => $lai_luy_ke_thang,
                        'tien_goc_con_thang' => 0,
                        'created_at' => $this->createdAt,
                        'status' => 1,  // 1: sap toi, 2: da dong
                    );
                    if ($tien_tra_1thang_hien_tai != 0) {
                        $this->tempo_contract_accounting_model->insert($data_1thang);
                    }
                    return;
                } else {
                    //lãi
                    $data_1thang = array(
                        'code_contract' => $code_contract,
                        "code_contract_disbursement" => $code_contract_disbursement,
                        'type' => $type_interest,
                        'is_penalty' => false, // kỳ bị phat
                        'penalty' => 0,
                        'investor_code' => $investor_code,
                        'time_timestamp' => $date_ky_tra,
                        'time' => $time,
                        'month' => $month,
                        'year' => $year,
                        'count_date_interest' => $ngay_lai_thuc_te,
                        'tien_tra_1thang' => $tien_tra_1thang_hien_tai,
                        'round_tien_tra_1thang' => $round_tien_tra_1thang_hien_tai,

                        'tien_goc_1thang' => $tien_goc_1thang,
                        'tien_lai_1thang' => $tien_lai_1thang_hien_tai,
                        'tien_phi_1thang' => $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai,

                        'tien_goc_1thang_da_tra' => 0,
                        'tien_lai_1thang_da_tra' => 0,
                        'tien_phi_1thang_da_tra' => 0,

                        'tien_goc_1thang_con_lai' => $tien_goc_1thang,
                        'tien_lai_1thang_con_lai' => $tien_lai_1thang_hien_tai,
                        'tien_phi_1thang_con_lai' => $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai,

                        'du_no_goc_thang_truoc' => $du_no_goc_thang_truoc,
                        'du_no_lai_thang_truoc' => $du_no_lai_thang_truoc,
                        'du_no_phi_thang_truoc' => $du_no_phi_thang_truoc,

                        'du_no_goc_thang_truoc_da_tra' => 0,
                        'du_no_lai_thang_truoc_da_tra' => 0,
                        'du_no_phi_thang_truoc_da_tra' => 0,

                        'lai_con_lai_luy_ke' => $lai_con_lai_luy_ke,
                        'phi_con_lai_luy_ke' => $phi_con_lai_luy_ke,

                        'goc_con_lai_thang_hien_tai' => $goc_con_lai_thang_hien_tai,
                        'tien_goc_con_lai_hop_dong' => $tien_goc_1thang + $tien_goc_con_thang,
                        'goc_con_lai_chua_thu_thang_truoc' => $goc_con_lai_chua_thu_thang_truoc,
                        'goc_con_lai_chua_thu_cuoi_thang_hien_tai' => (float)$contract['loan_infor']['amount_money'],
                        'tong_phi_lai_thang' => $tong_phi_lai_hien_tai,
                        'lai_luy_ke_thang' => $lai_luy_ke_thang,
                        'tien_goc_con_thang' => $tien_goc_con_thang,
                        'created_at' => $this->createdAt,
                        'status' => 1, // 1: sap toi, 2: da dong
                    );
                    if ($tien_tra_1thang_hien_tai != 0) {
                        $this->tempo_contract_accounting_model->insert($data_1thang);
                    }
                    $so_ngay_lai_con_lai = (int)$period_pay_interest - $count;
                }
            }
            return;
        } else {

            //hình thức lãi hàng tháng, gốc cuối kỳ
            //khoan vay 1 ky
            $so_ngay_lai_con_lai = 0;
            $du_no_goc_thang_truoc = 0;
            $du_no_lai_thang_truoc = 0;
            $du_no_phi_thang_truoc = 0;
            $tien_lai_1thang_hien_tai = 0;
            $tien_phi_1thang_hien_tai = 0;
            $lai_con_lai_luy_ke = 0;
            $phi_con_lai_luy_ke = 0;
            $goc_con_lai_thang_hien_tai = 0;
            $tong_phi_hien_tai = 0;
            $goc_con_lai_chua_thu_thang_truoc = 0;
            $tien_goc_1thang = 0;
            for ($i = 0; $i <= $so_ky_vay; $i++) {
                //kỳ trả

                $date_ky_tra = $disbursement_date + (intval($period_pay_interest) * 24 * 60 * 60 * $i) - 24 * 60 * 60;
                $ngay_ky_tra = date('Y-m-d', $date_ky_tra);
                $timestamp_ngay_ky_tra = strtotime($ngay_ky_tra);
                $day_last_month = date("Y-m-t", $timestamp_ngay_ky_tra);
                $last_date = strtotime($day_last_month);
                $timestamp_date = strtotime($ngay_ky_tra);
                $datediff = $last_date - $timestamp_date;
                $count = round($datediff / (60 * 60 * 24));
                $time = date('m/Y', $date_ky_tra);
                $month = date('m', $date_ky_tra);
                $year = date('Y', $date_ky_tra);

                $lai_ky = round($lai_suat_ndt * $tien_goc);
                //tiền gốc còn lại
                $tien_goc_con = $i == $so_ky_vay ? 0 : $tien_goc;
                // phi tu van quan ly
                $phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
                // phi tham dinh
                $phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
                //tổng phí lãi 1 kỳ
                $tong_phi_lai = $phi_tu_van + $phi_tham_dinh + $lai_ky;

                $tien_tra_1_ky = $tong_phi_lai;


                // goc da tra
                if ($i == ($so_ky_vay - 1)) {
                    $du_no_goc_thang_truoc = 0;
                    $du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai;
                    $du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tien_phi_1thang_hien_tai;
                    $tien_goc_1thang = ($tien_goc / (int)$period_pay_interest) * $count;

                    $ngay_lai_thuc_te = $count + $so_ngay_lai_con_lai;
                    $tien_tra_1thang_hien_tai = ($tien_tra_1_ky / (int)$period_pay_interest) * $ngay_lai_thuc_te;
                    $tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai + $tien_goc_1thang;
                    $round_tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai;
                    $tien_lai_1thang_hien_tai = ($lai_ky / (int)$period_pay_interest) * $ngay_lai_thuc_te;
                    $tong_phi_lai_hien_tai = ($tong_phi_lai / (int)$period_pay_interest) * $ngay_lai_thuc_te;
                    $tien_phi_1thang_hien_tai = $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $tien_goc_con = $tien_goc_con - $tien_goc_1thang;
                    $lai_con_lai_luy_ke = $lai_con_lai_luy_ke + $tien_lai_1thang_hien_tai;
                    $phi_con_lai_luy_ke = $phi_con_lai_luy_ke + $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $goc_con_lai_thang_hien_tai = $tien_goc_1thang;
                    $tong_phi_hien_tai = $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $goc_con_lai_chua_thu_thang_truoc = $tien_goc;
                } else if ($i == $so_ky_vay) {
                    $du_no_goc_thang_truoc = $du_no_goc_thang_truoc + $tien_goc_1thang;
                    $du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai;
                    $du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tien_phi_1thang_hien_tai;
                    $tien_tra_1thang_hien_tai = ($tong_phi_lai / (int)$period_pay_interest) * $so_ngay_lai_con_lai;
                    $tien_goc_1thang = ($tien_goc / (int)$period_pay_interest) * $so_ngay_lai_con_lai;

                    $tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai + $tien_goc_1thang;
                    $round_tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai;
                    $tien_lai_1thang_hien_tai = ($lai_ky / (int)$period_pay_interest) * $so_ngay_lai_con_lai;
                    $tong_phi_lai_hien_tai = ($tong_phi_lai / (int)$period_pay_interest) * $so_ngay_lai_con_lai;
                    $tien_phi_1thang_hien_tai = $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $lai_con_lai_luy_ke = $lai_con_lai_luy_ke + $tien_lai_1thang_hien_tai;
                    $phi_con_lai_luy_ke = $phi_con_lai_luy_ke + $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $goc_con_lai_thang_hien_tai = $tien_goc_1thang;
                    $goc_con_lai_chua_thu_thang_truoc = $tien_goc;
                } else {
                    $du_no_goc_thang_truoc = 0;
                    $du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai;
                    $du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tien_phi_1thang_hien_tai;
                    $tien_goc_1thang = 0;
                    $ngay_lai_thuc_te = $count + $so_ngay_lai_con_lai;
                    $tien_tra_1thang_hien_tai = ($tien_tra_1_ky / (int)$period_pay_interest) * $ngay_lai_thuc_te;
                    $round_tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai;
                    $tien_lai_1thang_hien_tai = ($lai_ky / (int)$period_pay_interest) * $ngay_lai_thuc_te;
                    $tong_phi_lai_hien_tai = ($tong_phi_lai / (int)$period_pay_interest) * $ngay_lai_thuc_te;
                    $tien_phi_1thang_hien_tai = $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $lai_con_lai_luy_ke = $lai_con_lai_luy_ke + $tien_lai_1thang_hien_tai;
                    $phi_con_lai_luy_ke = $phi_con_lai_luy_ke + $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $goc_con_lai_thang_hien_tai = $tien_goc_1thang;
                    $tong_phi_hien_tai = $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai;
                    $goc_con_lai_chua_thu_thang_truoc = $tien_goc;
                }
                if ($i == 0) {
                    $du_no_goc_thang_truoc = 0;
                    $du_no_lai_thang_truoc = 0;
                    $du_no_phi_thang_truoc = 0;
                    $goc_con_lai_chua_thu_thang_truoc = 0;
                }
                if ($i == $so_ky_vay) {
                    $lai_luy_ke_thang = $lai_luy_ke_thang + $tong_phi_lai_hien_tai;
                    $data_1thang = array(
                        'code_contract' => $code_contract,
                        "code_contract_disbursement" => $code_contract_disbursement,
                        'type' => $type_interest,
                        'is_penalty' => false, // kỳ bị phat
                        'penalty' => 0,
                        'investor_code' => $investor_code,
                        'time_timestamp' => $date_ky_tra,
                        'time' => $time,
                        'month' => $month,
                        'year' => $year,
                        'count_date_interest' => $so_ngay_lai_con_lai,
                        'tien_tra_1thang' => $tien_tra_1thang_hien_tai,
                        'round_tien_tra_1thang' => $round_tien_tra_1thang_hien_tai,

                        'tien_goc_1thang' => $tien_goc_1thang,
                        'tien_lai_1thang' => $tien_lai_1thang_hien_tai,
                        'tien_phi_1thang' => $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai,

                        'tien_goc_1thang_da_tra' => 0,
                        'tien_lai_1thang_da_tra' => 0,
                        'tien_phi_1thang_da_tra' => 0,

                        'tien_goc_1thang_con_lai' => $tien_goc_1thang,
                        'tien_lai_1thang_con_lai' => $tien_lai_1thang_hien_tai,
                        'tien_phi_1thang_con_lai' => $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai,

                        'du_no_goc_thang_truoc' => $du_no_goc_thang_truoc,
                        'du_no_lai_thang_truoc' => $du_no_lai_thang_truoc,
                        'du_no_phi_thang_truoc' => $du_no_phi_thang_truoc,

                        'du_no_goc_thang_truoc_da_tra' => 0,
                        'du_no_lai_thang_truoc_da_tra' => 0,
                        'du_no_phi_thang_truoc_da_tra' => 0,

                        'lai_con_lai_luy_ke' => $lai_con_lai_luy_ke,
                        'phi_con_lai_luy_ke' => $phi_con_lai_luy_ke,

                        'goc_con_lai_thang_hien_tai' => $goc_con_lai_thang_hien_tai,
                        'tien_goc_con_lai_hop_dong' => $tien_goc_1thang + $tien_goc_con,
                        'goc_con_lai_chua_thu_thang_truoc' => $goc_con_lai_chua_thu_thang_truoc,
                        'goc_con_lai_chua_thu_cuoi_thang_hien_tai' => (float)$contract['loan_infor']['amount_money'],
                        'tong_phi_lai_thang' => $tong_phi_lai_hien_tai,
                        'lai_luy_ke_thang' => $lai_luy_ke_thang,
                        'tien_goc_con_thang' => $tien_goc_con,
                        'created_at' => $this->createdAt,
                        'status' => 1, // 1: sap toi, 2: da dong
                    );
                    if (!empty($tien_tra_1thang_hien_tai)) {
                        $this->tempo_contract_accounting_model->insert($data_1thang);
                    }
                    return;
                } else {
                    //lãi
                    $lai_luy_ke_thang = $lai_luy_ke_thang + $tong_phi_lai_hien_tai;

                    $data_1thang = array(
                        'code_contract' => $code_contract,
                        "code_contract_disbursement" => $code_contract_disbursement,
                        'type' => $type_interest,
                        'is_penalty' => false, // kỳ bị phat
                        'penalty' => 0,
                        'investor_code' => $investor_code,
                        'time_timestamp' => $date_ky_tra,
                        'time' => $time,
                        'month' => $month,
                        'year' => $year,
                        'count_date_interest' => $ngay_lai_thuc_te,
                        'tien_tra_1thang' => $tien_tra_1thang_hien_tai,
                        'round_tien_tra_1thang' => $round_tien_tra_1thang_hien_tai,
                        'tien_goc_1thang' => $tien_goc_1thang,
                        'tien_lai_1thang' => $tien_lai_1thang_hien_tai,
                        'tien_phi_1thang' => $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai,

                        'tien_goc_1thang_da_tra' => 0,
                        'tien_lai_1thang_da_tra' => 0,
                        'tien_phi_1thang_da_tra' => 0,

                        'tien_goc_1thang_con_lai' => $tien_goc_1thang,
                        'tien_lai_1thang_con_lai' => $tien_lai_1thang_hien_tai,
                        'tien_phi_1thang_con_lai' => $tong_phi_lai_hien_tai - $tien_lai_1thang_hien_tai,

                        'du_no_goc_thang_truoc' => $du_no_goc_thang_truoc,
                        'du_no_lai_thang_truoc' => $du_no_lai_thang_truoc,
                        'du_no_phi_thang_truoc' => $du_no_phi_thang_truoc,

                        'du_no_goc_thang_truoc_da_tra' => 0,
                        'du_no_lai_thang_truoc_da_tra' => 0,
                        'du_no_phi_thang_truoc_da_tra' => 0,

                        'lai_con_lai_luy_ke' => $lai_con_lai_luy_ke,
                        'phi_con_lai_luy_ke' => $phi_con_lai_luy_ke,

                        'goc_con_lai_thang_hien_tai' => $goc_con_lai_thang_hien_tai,
                        'tien_goc_con_lai_hop_dong' => $tien_goc_1thang + $tien_goc_con,
                        'goc_con_lai_chua_thu_thang_truoc' => $goc_con_lai_chua_thu_thang_truoc,
                        'goc_con_lai_chua_thu_cuoi_thang_hien_tai' => (float)$contract['loan_infor']['amount_money'],
                        'tong_phi_lai_thang' => $tong_phi_lai_hien_tai,
                        'lai_luy_ke_thang' => $lai_luy_ke_thang,
                        'tien_goc_con_thang' => $tien_goc_con,
                        'created_at' => $this->createdAt,
                        'status' => 1, // 1: sap toi, 2: da dong
                    );
                    if (!empty($tien_tra_1thang_hien_tai)) {
                        $this->tempo_contract_accounting_model->insert($data_1thang);
                    }
                    $so_ngay_lai_con_lai = (int)$period_pay_interest - $count;
                }
            }
            return;
        }
    }


    public function check_code_contract_post()
    {
        $conditions = $this->security->xss_clean($this->dataPost);
        if (empty($conditions)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại id"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            foreach ($conditions as $condition) {
                $contract = $this->contract_model->count(array("code_contract" => $condition));
                if ($contract == 0) {
                    $response = array(
                        'status' => REST_Controller::HTTP_UNAUTHORIZED,
                        'message' => "Không tồn tại id"
                    );
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                }
            }
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Ok",
                'cond' => $conditions
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }


    public function contract_tempo_by_user()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        $contract = $this->contract_model->getContractByTime(array());

        if (!empty($contract)) {
            foreach ($contract as $key => $c) {
                $cond = array();
                $c['investor_name'] = "";
                if (isset($c['investor_code'])) {
                    $investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
                    $c['investor_name'] = $investors['name'];
                }
                if (isset($c['code_contract'])) {
                    $cond = array(
                        'code_contract' => $c['code_contract'],
                        'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
                    );
                }
                $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                $c['detail'] = array();
                if (!empty($detail)) {
                    $total_paid = 0;
                    $total_phi_phat_cham_tra = 0;
                    $total_da_thanh_toan = 0;
                    foreach ($detail as $de) {

                        $total_paid = $total_paid + $de['tien_tra_1_ky'];
                        $total_phi_phat_cham_tra += $de['penalty'];
                        $total_da_thanh_toan += $de['da_thanh_toan'];
                    }
                    $c['detail'] = $detail[0];
                    $c['detail']['total_paid'] = $total_paid;
                    $c['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
                    $c['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
                } else {
                    $condition_new = array(
                        'code_contract' => $c['code_contract'],
                        'status' => 1
                    );
                    $detail_new = $this->contract_tempo_model->getContract($condition_new);
                    if (!empty($detail_new)) {
                        $c['detail'] = $detail_new[0];

                        $c['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
                        $c['detail']['total_phi_phat_cham_tra'] = $detail_new[0]['penalty'];
                        $c['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'];
                    }
                }

                $time = 0;
                if (!empty($c['detail']) && $c['detail']['status'] == 1) {
                    $current_day = strtotime(date('m/d/Y'));
                    $datetime = !empty($c['detail']['ngay_ky_tra']) ? intval($c['detail']['ngay_ky_tra']) : $current_day;
                    $time = intval(($current_day - $datetime) / (24 * 60 * 60));
                    if ($time <= 0) {
                        $c['bucket'] = 'B0';
                    } else if ($time >= 1 && $time <= 30) {
                        $c['bucket'] = 'B1';
                    } else if ($time > 30 && $time <= 60) {
                        $c['bucket'] = 'B2';
                    } else if ($time > 60 && $time <= 90) {
                        $c['bucket'] = 'B3';
                    } else if ($time > 90 && $time <= 120) {
                        $c['bucket'] = 'B4';
                    } else if ($time > 120 && $time <= 150) {
                        $c['bucket'] = 'B5';
                    } else if ($time > 150 && $time <= 180) {
                        $c['bucket'] = 'B6';
                    } else if ($time > 180 && $time <= 210) {
                        $c['bucket'] = 'B7';
                    } else if ($time > 210 && $time <= 270) {
                        $c['bucket'] = 'B8';
                    } else if ($time > 270 && $time <= 300) {
                        $c['bucket'] = 'B9';
                    } else if ($time > 300 && $time <= 330) {
                        $c['bucket'] = 'B10';
                    } else if ($time > 330 && $time <= 360) {
                        $c['bucket'] = 'B11';
                    } else {
                        $c['bucket'] = 'B12';
                    }
                } else if (!empty($c['detail']) && $c['status'] == 2) {
                    $c['bucket'] = 'B0';
                } else {
                    $c['bucket'] = '-';
                }
                $c['time'] = $time;
                if ($c['status'] == 19 || $c['status'] == 23)
                    $c['time'] = '-';
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function process_update_dashboard($id, $type = null)
    {
        $contract = new Contract_model();
        $contract_data = $contract->findOne(array("_id" => $id));
        $dash_board = new Dashboard_model();
        $dash_board_data = $dash_board->findOne(array('status' => 'active'));
        $update = array(
            "contract" => array(
                "contract_disbursed" => $dash_board_data['contract']['contract_disbursed'],
                "total_amount_money" => $dash_board_data['contract']['total_amount_money'],
                "contract_waiting_disbursement" => $dash_board_data['contract']['contract_waiting_disbursement'],
                "contract_pending" => $dash_board_data['contract']['contract_pending'],
                "contract_cancel" => $dash_board_data['contract']['contract_cancel'],
                "contract_total" => $dash_board_data['contract']['contract_total']
            ),
            "type_loan" => array(
                "oto" => $dash_board_data['type_loan']['oto'],
                "xm" => $dash_board_data['type_loan']['xm'],
                "car_registration" => $dash_board_data['type_loan']['car_registration'],
                "motorbike_registration" => $dash_board_data['type_loan']['motorbike_registration'],
            )
        );
        if ($type == "import") {
            $update['contract']['contract_total'] = (int)$dash_board_data['contract']['contract_total'] + 1;
        }
        if ($contract_data['status'] == 17 or $contract_data['status'] == 19 or $contract_data['status'] == 20 or $contract_data['status'] == 21 or $contract_data['status'] == 22 or $contract_data['status'] == 23) {
            $update['contract']['contract_disbursed'] = $dash_board_data['contract']['contract_disbursed'] + 1;
            $update['contract']['total_amount_money'] = $dash_board_data['contract']['total_amount_money'] + (int)$contract_data['loan_infor']['amount_money'];
            $update['contract']['contract_waiting_disbursement'] = $dash_board_data['contract']['contract_waiting_disbursement'] - 1;
        }
        if ($contract_data['status'] == 15) {
            $update['contract']['contract_waiting_disbursement'] = $dash_board_data['contract']['contract_waiting_disbursement'] + 1;
            $update['contract']['contract_pending'] = $dash_board_data['contract']['contract_pending'] - 1;
        }
        if ($contract_data['status'] == 5) {
            $update['contract']['contract_pending'] = $dash_board_data['contract']['contract_pending'] + 1;
        }
        if ($contract_data['status'] == 3) {
            $update['contract']['contract_cancel'] = $dash_board_data['contract']['contract_cancel'] + 1;
        }
        if ($contract_data['status'] == 1) {
            $update['contract']['contract_total'] = (int)$dash_board_data['contract']['contract_total'] + 1;
            //update type loan
            if ($contract_data['loan_infor']['type_loan']['id'] == "5da82ed2a104d435e3b8ae65" && $contract_data['loan_infor']['type_property']['code'] == "XM") {
                $update['type_loan']['xm'] = $dash_board_data['type_loan']['xm'] + 1;
            }
            if ($contract_data['loan_infor']['type_loan']['id'] == "5da82ed2a104d435e3b8ae65" && $contract_data['loan_infor']['type_property']['code'] == "OTO") {
                $update['type_loan']['oto'] = $dash_board_data['type_loan']['oto'] + 1;
            }
            if ($contract_data['loan_infor']['type_loan']['id'] == "5da82ee7a104d435e3b8ae66" && $contract_data['loan_infor']['type_property']['code'] == "OTO") {
                $update['type_loan']['car_registration'] = $dash_board_data['type_loan']['car_registration'] + 1;
            }
            if ($contract_data['loan_infor']['type_loan']['id'] == "5da82ee7a104d435e3b8ae66" && $contract_data['loan_infor']['type_property']['code'] == "OTO") {
                $update['type_loan']['motorbike_registration'] = $dash_board_data['type_loan']['motorbike_registration'] + 1;
            }
        }
        $dash_board_id = $dash_board_data['_id'];
        $result = $dash_board->update(array('_id' => $dash_board_id), $update);
        return $result;
    }

    public function get_callhistory()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $groupRoles = $this->getGroupRole($this->id);
        $this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'groupRoles' => $groupRoles
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function check_contract_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $condition = array();
        if (!empty($data['phone'])) {
            $condition['customer_phone_number'] = $data['phone'];
        }
        if (!empty($data['customer_identify'])) {
            $condition['customer_identify'] = $data['customer_identify'];
        }
        if (!empty($data['customer_identify_old'])) {
            $condition['customer_identify_old'] = $data['customer_identify_old'];
        }
        if (!empty($data['phone_number_relative_1'])) {
            $condition['phone_number_relative_1'] = $data['phone_number_relative_1'];
        }
        if (!empty($data['phone_number_relative_2'])) {
            $condition['phone_number_relative_2'] = $data['phone_number_relative_2'];
        }
        $contract = $this->contract_model->checkContract($condition);
        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }


    public function get_contract_check_involve_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();

        if (!empty($data['phone'])) {
            $condition = array();
            $condition['customer_phone_number'] = $data['phone'];
            $contract = $this->contract_model->checkContract($condition);
        }
        if (!empty($data['customer_identify'])) {
            $condition1 = array();
            $condition1['customer_identify'] = $data['customer_identify'];
            $contract1 = $this->contract_model->checkContract($condition1);
        }
        if (!empty($data['customer_identify_old'])) {
            $condition4 = array();
            $condition4['customer_identify_old'] = $data['customer_identify_old'];
            $contract4 = $this->contract_model->checkContract($condition4);
        }
        if (!empty($data['phone_number_relative_1'])) {
            $condition2 = array();
            $condition2['phone_number_relative_1'] = $data['phone_number_relative_1'];
            $contract2 = $this->contract_model->checkContract($condition2);
        }
        if (!empty($data['phone_number_relative_2'])) {
            $condition3 = array();
            $condition3['phone_number_relative_2'] = $data['phone_number_relative_2'];
            $contract3 = $this->contract_model->checkContract($condition3);
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data_phone' => !empty($contract) ? $contract : array(),
            'data_identify' => !empty($contract1) ? $contract1 : array(),
            'data_identify_old' => !empty($contract4) ? $contract4 : array(),
            'data_identify_relative_1' => !empty($contract2) ? $contract2 : array(),
            'data_identify_relative_2' => !empty($contract3) ? $contract3 : array(),
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function contract_involve_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }

        $condition = array();
        if (!empty($contract['customer_infor']['customer_phone_number'])) {
            $condition['customer_phone_number'] = $contract['customer_infor']['customer_phone_number'];
        }
        if (!empty($contract['customer_infor']['customer_identify'])) {
            $condition['customer_identify'] = $contract['customer_infor']['customer_identify'];
        }
        if (!empty($contract['customer_infor']['customer_citizen'])) {
            $condition['customer_citizen'] = $contract['customer_infor']['customer_citizen'];
        }
        if (!empty($contract['relative_infor']['phone_number_relative_1'])) {
            $condition['phone_number_relative_1'] = $contract['relative_infor']['phone_number_relative_1'];
        }
        if (!empty($contract['relative_infor']['phone_number_relative_2'])) {
            $condition['phone_number_relative_2'] = $contract['relative_infor']['phone_number_relative_2'];
        }
        $condition['status'] = 17;

        $contract = $this->contract_model->checkContractInvolve($condition);
        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function delete_old_transaction_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $transaction = new Transaction_model();
        $transaction->delete_all(array("note" => "import old transaction"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Xóa transaction cũ thành công',
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function delete_old_contract_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $created_at = $this->dataPost['created_at'];
        $code_contract = $this->dataPost['code_contract'];
        $contract = new Contract_model();
        $acount = new Tempo_contract_accounting_model();
        $tempo = new Contract_tempo_model();
        if (!empty($code_contract)) {
            $contract->delete(array("type" => "old_contract", 'code_contract' => $code_contract));
            $acount->delete(array("type_create" => "old_contract", 'code_contract' => $code_contract));
            $tempo->delete(array("type_create" => "old_contract", 'code_contract' => $code_contract));
        } elseif (!empty($created_at)) {
            $contract->delete_all(array("type" => "old_contract", 'created_at' => $created_at));
            $acount->delete_all(array("type_create" => "old_contract", 'created_at' => $created_at));
            $tempo->delete_all(array("type_create" => "old_contract", 'created_at' => $created_at));
        } else {
            $contract->delete_all(array("type" => "old_contract"));
            $acount->delete_all(array("type_create" => "old_contract"));
            $tempo->delete_all(array("type_create" => "old_contract"));
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Xóa hơp đồng cũ thành công',
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function delete_lai_ky_lai_thang_post()
    {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;

        $data_post = $this->input->post();
        $code_contract = $data_post['code_contract'];
        $acount = new Tempo_contract_accounting_model();
        $tempo = new Contract_tempo_model();
        $tran_extend = new Transaction_extend_model();
        if (empty($code_contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $contract_ck = $this->contract_model->findOne(array("code_contract" => $code_contract));
        if( isset($contract_ck['contract_lock']) && $contract_ck['contract_lock']=='lock')
        {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng đã khóa"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $acount->delete_all(array("code_contract" => $code_contract));
        $tempo->delete_all(array("code_contract" => $code_contract));
        $tran_extend->delete_all(array("code_contract" => $code_contract));
        if (isset($data_post['type_gh']) && $data_post['type_gh'] == 'origin') {
            $tran_extend->delete_all(array("code_contract_parent_gh" => $data_post['code_contract']));
        }
        $data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract' => $code_contract, 'type' => array('$in' => array(3, 4))));

        if (!empty($data_transaction)) {
            foreach ($data_transaction as $key => $value) {
                $transDB = $this->transaction_model->findOneAndUpdate(
                    array("_id" => $value['_id']),
                    array(
                        "so_tien_lai_da_tra" => 0,
                        "temporary_plan_contract_id" => '',
                        "so_tien_phi_da_tra" => 0,
                        "so_tien_goc_da_tra" => 0,
                        "phi_phat_sinh" => 0,
                        "tien_phi_phat_sinh_da_tra" => 0,
                        "fee_delay_pay" => array(),
                        "tien_thua_tat_toan" => 0,
                        "so_ngay_phat_sinh" => 0,
                        "so_tien_phat_sinh" => 0,
                        "tien_thua_thanh_toan" => 0,
                        "tien_thua_thanh_toan_con_lai" => 0,
                        "tien_thua_thanh_toan_da_tra" => 0,
                        "fee_finish_contract" => 0,
                        "so_tien_phi_cham_tra_da_tra" => 0,
                        "so_tien_phi_gia_han_da_tra" => 0,
                        "code_contract_parent_gh" => "",
                        "chia_mien_giam" => [],
                        "phai_tra_hop_dong" => [],
                        "tat_toan_phai_tra" => [],
                        "so_tien_goc_phai_tra_tat_toan" => 0,
                        "so_tien_lai_phai_tra_tat_toan" => 0,
                        "so_tien_phi_phai_tra_tat_toan" => 0,
                        "so_tien_phi_cham_tra_phai_tra" => 0,
                        "so_tien_phi_cham_tra_phai_tra_tat_toan" => 0,
                        "so_tien_phi_gia_han_phai_tra_tat_toan" => 0,
                        "so_tien_phi_phat_sinh_phai_tra_tat_toan" => 0,
                        "so_tien_thieu" => 0,
                        "so_tien_thieu_da_chuyen" => 0,
                        "so_tien_thieu_con_lai" => 0,
                        "con_lai_sau_thanh_toan" => [],
                        "goc_lai_phi_phai_tra"=> [],
                        "tong_tien_tat_toan"=> 0,
                        "ky_da_tt_gan_nhat" => 0,
                        "date_pay_tt" => 0,

                    )
                );
            }
        }
        if (isset($data_post['type_gh']) && $data_post['type_gh'] == 'origin') {
            $data_transaction_origin = $this->transaction_model->find_where_pay_all(array('code_contract_parent_gh' => $data_post['code_contract'], 'type' => array('$in' => array(3, 4))));

            if (!empty($data_transaction_origin)) {
                foreach ($data_transaction_origin as $key => $value) {
                    $transDB = $this->transaction_model->findOneAndUpdate(
                        array("_id" => $value['_id']),
                        array(

                            "so_tien_lai_da_tra" => 0,
                            "temporary_plan_contract_id" => '',
                            "so_tien_phi_da_tra" => 0,
                            "so_tien_goc_da_tra" => 0,
                            "phi_phat_sinh" => 0,
                            "tien_phi_phat_sinh_da_tra" => 0,
                            "fee_delay_pay" => array(),
                            "tien_thua_tat_toan" => 0,
                            "so_ngay_phat_sinh" => 0,
                            "so_tien_phat_sinh" => 0,
                            "tien_thua_thanh_toan" => 0,
                            "tien_thua_thanh_toan_con_lai" => 0,
                            "tien_thua_thanh_toan_da_tra" => 0,
                            "fee_finish_contract" => 0,
                            "so_tien_phi_cham_tra_da_tra" => 0,
                            "so_tien_phi_gia_han_da_tra" => 0,
                            "code_contract_parent_gh" => "",
                            "type_payment" => 1,
                            "chia_mien_giam" => [],
                            "phai_tra_hop_dong" => [],
                            "tat_toan_phai_tra" => [],
                            "so_tien_goc_phai_tra_tat_toan" => 0,
                            "so_tien_lai_phai_tra_tat_toan" => 0,
                            "so_tien_phi_phai_tra_tat_toan" => 0,
                            "so_tien_phi_cham_tra_phai_tra" => 0,
                            "so_tien_phi_cham_tra_phai_tra_tat_toan" => 0,
                            "so_tien_phi_gia_han_phai_tra_tat_toan" => 0,
                            "so_tien_phi_phat_sinh_phai_tra_tat_toan" => 0,
                            "so_tien_thieu" => 0,
                            "so_tien_thieu_da_chuyen" => 0,
                            "so_tien_thieu_con_lai" => 0,
                            "con_lai_sau_thanh_toan" => [],
                            "goc_lai_phi_phai_tra"=> [],
                            "tong_tien_tat_toan"=> 0,
                            "ky_da_tt_gan_nhat" => 0,
                            "date_pay_tt" => 0,

                        )
                    );
                }
            }
        }
        if (isset($data_post['type_cc']) && $data_post['type_cc'] == 'origin') {
            $data_transaction_origin = $this->transaction_model->find_where_pay_all(array('code_contract_parent_cc' => $data_post['code_contract'], 'type' => array('$in' => array(3, 4))));

            if (!empty($data_transaction_origin)) {
                foreach ($data_transaction_origin as $key => $value) {
                    $transDB = $this->transaction_model->findOneAndUpdate(
                        array("_id" => $value['_id']),
                        array(
                            //"code_contract" => $data_post['code_contract'],
                            "so_tien_lai_da_tra" => 0,
                            "temporary_plan_contract_id" => '',
                            "so_tien_phi_da_tra" => 0,
                            "so_tien_goc_da_tra" => 0,
                            "phi_phat_sinh" => 0,
                            "tien_phi_phat_sinh_da_tra" => 0,
                            "fee_delay_pay" => array(),
                            "tien_thua_tat_toan" => 0,
                            "so_ngay_phat_sinh" => 0,
                            "so_tien_phat_sinh" => 0,
                            "tien_thua_thanh_toan" => 0,
                            "tien_thua_thanh_toan_con_lai" => 0,
                            "tien_thua_thanh_toan_da_tra" => 0,
                            "fee_finish_contract" => 0,
                            "so_tien_phi_cham_tra_da_tra" => 0,
                            "so_tien_phi_gia_han_da_tra" => 0,
                            "so_tien_phi_cham_tra_phai_tra" => 0,
                            "code_contract_parent_cc" => "",
                            "type_payment" => 1,
                            "chia_mien_giam" => [],
                            "phai_tra_hop_dong" => [],
                            "tat_toan_phai_tra" => [],
                            "so_tien_goc_phai_tra_tat_toan" => 0,
                            "so_tien_lai_phai_tra_tat_toan" => 0,
                            "so_tien_phi_phai_tra_tat_toan" => 0,
                            "so_tien_phi_cham_tra_phai_tra_tat_toan" => 0,
                            "so_tien_phi_gia_han_phai_tra_tat_toan" => 0,
                            "so_tien_phi_phat_sinh_phai_tra_tat_toan" => 0,
                            "so_tien_thieu" => 0,
                            "so_tien_thieu_da_chuyen" => 0,
                            "so_tien_thieu_con_lai" => 0,
                            "con_lai_sau_thanh_toan" => [],
                            "goc_lai_phi_phai_tra"=> [],
                            "tong_tien_tat_toan"=> 0,
                            "ky_da_tt_gan_nhat" => 0,
                            "date_pay_tt" => 0,

                        )
                    );
                }
            }
        }
        $data = array(
            "status_disbursement" => 2,
            "status_run_fee_again" => 1,

        );
        $this->contract_model->update(
            array("code_contract" => $code_contract),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => $code_contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_all_by_status_active_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $this->dataPost = $this->input->post();
        $createBy = !empty($this->dataPost['created_by']) ? $this->dataPost['created_by'] : "";
        $condition = [];
        if (!empty($createBy)) {
            $condition['created_by'] = $createBy;
        }
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')

            );
        }
        $contracts = $this->contract_model->getContractByUser($condition);
        $response = [
            'status' => REST_Controller::HTTP_OK,
            'data' => $contracts
        ];
        $this->set_response($response, REST_Controller::HTTP_OK);
    }


    public function get_info_verify_identify_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $contract_id = $this->dataPost['contract_id'];
        $verify_identify = new Verify_identify_contract_model();
        $data = $verify_identify->findOne(array('contract_id' => $contract_id));
        $response = [
            'status' => REST_Controller::HTTP_OK,
            'data' => $data
        ];
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function restore_gic_kv_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id_contract'] = $this->security->xss_clean($data['id_contract']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
         $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $code_contract_disbursement = $contract['code_contract_disbursement'];
        unset($contract['image_accurecy']);
        $ck = true;
        //gic khoản vay
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '1' && $contract['loan_infor']['amount_GIC'] > 0) {

            $gic_data = $this->gic_model->findOne(array("contract_id" => (string)$contract['_id']));
            if (!empty($gic_data)) {
                $gic = $this->insert_gic($contract, $code_contract_disbursement, date('Y-m-d H:i:s'));
                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $code_contract_disbursement
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => "superadmin"
                    ,'company_code' => $ma_cty
                    );
                    $this->gic_model->update(array('_id' => $gic_data['_id']), $dt_gic);
                    $ck = false;
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $code_contract_disbursement
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => "superadmin"
                    ,'company_code' => $ma_cty
                    );
                    $this->gic_model->update(array('_id' => $gic_data['_id']), $dt_gic);
                }
            }

        }
        if (!$ck) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thất bại"
            );
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công"
            );
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function restore_mic_kv_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id_contract'] = $this->security->xss_clean($data['id_contract']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
         $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $code_contract_disbursement = $contract['code_contract_disbursement'];
        unset($contract['image_accurecy']);
        $ck = true;
        //mic khoản vay
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '2' && $contract['loan_infor']['amount_MIC'] > 0) {
            $type_mic = "MIC_TDCN";
            $mic_ck = $this->mic_model->findOne(array("contract_id" => (string)$contract['_id'], 'type_mic' => $type_mic));

            if (!empty($mic_ck)) {
                $mic = $this->insert_mic($contract, $contract['code_contract_disbursement'], date('d/m/Y'), $ma_cty);
                $this->log_mic($mic->request, $mic->response, $contract['code_contract_disbursement'], $type_mic);
                if ($mic->res != true) {
                    $dt_mic = array(
                        'type_mic' => $type_mic,
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'contract_info' => $contract
                    , 'status' => 'deactive'
                    , 'NGAY_HL' => $mic->NGAY_HL
                    , 'NGAY_KT' => $mic->NGAY_KT
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    , "response" => $mic->response
                    , "request" => $mic->request
                    , 'company_code' => $ma_cty
                    );
                    $this->mic_model->update(array('_id' => $mic_ck['_id']), $dt_mic);
                    $ck = false;

                } else {
                    $mic_data = $mic->data;
                    $dt_mic = array(
                        'type_mic' => $type_mic,
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'mic_gcn' => $mic_data->GCN
                    , 'mic_fee' => $mic_data->PHI
                    , 'NGAY_HL' => $mic->NGAY_HL
                    , 'NGAY_KT' => $mic->NGAY_KT
                    , 'contract_info' => $contract
                    , 'status' => 'active'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    , 'company_code' => $ma_cty
                    );
                    $this->mic_model->update(array('_id' => $mic_ck['_id']), $dt_mic);
                }
            }

        }
        if (!$ck) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thất bại"
            );
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công"
            );
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function restore_vbi_post()
    {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $data = $this->input->post();
        $data['id_contract'] = $this->security->xss_clean($data['id_contract']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $code_contract_disbursement = $contract['code_contract_disbursement'];
        unset($contract['image_accurecy']);
        $ck = true;
        if (isset($contract['loan_infor']['amount_VBI'])) {
            if ($contract['loan_infor']['amount_VBI'] > 0) {

                $check_vbi = $this->vbi_model->findOne(["code_contract" => $contract['code_contract']]);

                $endDate = strtotime('+1 year', strtotime(date('m/d/Y', $this->createdAt)));
                if (!empty($check_vbi)) {


                        $code_contract = $contract['code_contract'];
                        $status_vbi1 = $contract['loan_infor']['maVBI_1'];
                        if (is_numeric($status_vbi1)) {
                            if ($status_vbi1 <= 6) {
                                $call_vbi = $this->insert_sxh_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi1);
                                if ($call_vbi->status != 200) {
                                    $ck = false;
                                }
                            } else {


                                $call_vbi = $this->insert_utv_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi1);
                                if ($call_vbi->status != 200) {
                                    $ck = false;
                                }
                            }
                        }
                        $status_vbi2 = $contract['loan_infor']['maVBI_2'];
                        if (is_numeric($status_vbi2)) {
                            if ($status_vbi2 <= 6) {
                                $call_vbi2 = $this->insert_sxh_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi2);
                                if ($call_vbi2->status != 200) {
                                    $ck = false;
                                }
                            } else {
                                $call_vbi2 = $this->insert_utv_vbi($contract, $code_contract, $contract['disbursement_date'], $status_vbi2);
                                if ($call_vbi2->status != 200) {
                                    $ck = false;
                                }
                            }
                        }

                } else {
                    $ck = false;
                }
            }
        }
        if (!$ck) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thất bại"
            );
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công" . $status_vbi1
            );
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    //end mic khoan vay
    public function restore_gic_easy_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id_contract'] = $this->security->xss_clean($data['id_contract']);
        $data['date'] = $this->security->xss_clean($data['date'] . ' 12:00:00');
        // var_dump($data); die;
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $code_contract_disbursement = $contract['code_contract_disbursement'];
        unset($contract['image_accurecy']);
        $ck = false;
        //gic easy
        if (isset($contract['loan_infor']['code_GIC_easy']) && isset($contract['loan_infor']['amount_GIC_easy']) && $contract['loan_infor']['amount_GIC_easy'] > 0) {

            $gic_ck_esay = $this->gic_easy_model->findOne(array("contract_id" => (string)$contract['_id']));
            if (!empty($gic_ck_esay)) {

                $gic = $this->insert_gic_easy($contract, $contract['code_contract_disbursement'], $data['date']);


                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_easy_model->update(array('_id' => $gic_ck_esay['_id']), $dt_gic);
                    $ck = false;
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'store' => $store
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_easy_model->update(array('_id' => $gic_ck_esay['_id']), $dt_gic);
                    $ck = true;
                }
            }

        }
        if (!$ck) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thất bại",

            );
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công",

            );
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    //end gic easy
    public function restore_gic_plt_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id_contract'] = $this->security->xss_clean($data['id_contract']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));
        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $code_contract_disbursement = $contract['code_contract_disbursement'];
        unset($contract['image_accurecy']);
        $ck = false;
        //gic plt
        if (isset($contract['loan_infor']['code_GIC_plt']) && isset($contract['loan_infor']['amount_GIC_plt']) && in_array($contract['loan_infor']['code_GIC_plt'], array('COPPER', 'SILVER', 'GOLD'))) {

            $gic_plt = $this->gic_plt_model->findOne(array("contract_id" => (string)$contract['_id']));

            if (!empty($gic_plt)) {
                $gic = $this->insert_gic_plt($contract, $contract['code_contract_disbursement'], date('Y-m-d H:i:s'));

                if ($gic->success != true) {
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => ""
                    , 'gic_id' => ""
                    , 'contract_info' => $contract
                    , 'gic_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_plt_model->update(array('_id' => $gic_plt['_id']), $dt_gic);
                    $ck = false;
                } else {
                    $gic = $gic->data;
                    $dt_gic = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'gic_code' => $gic->thongTinChung_SoHopDong
                    , 'gic_id' => $gic->id
                    , 'contract_info' => $contract
                    , 'gic_info' => $gic
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'status' => '0'
                    , 'erro_info' => '-'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    ,'company_code' => $ma_cty

                    );
                    $this->gic_plt_model->update(array('_id' => $gic_plt['_id']), $dt_gic);
                    $ck = true;
                }
            }

        }

        if (!$ck) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thất bại",
                'data' => $contract
            );
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công",
                'data' => $contract
            );
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    /**
    * recall pti api
    */
    public function restore_pti_vta_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id_contract'] = $this->security->xss_clean($data['id_contract']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));
        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $code_contract = $contract['code_contract'];
        unset($contract['image_accurecy']);
        $ck = false;
        //gic pti vta
        if (isset($contract['loan_infor']['bao_hiem_pti_vta']) && in_array($contract['loan_infor']['bao_hiem_pti_vta']['code_pti_vta'], array('G1', 'G2', 'G3')) && in_array($contract['loan_infor']['bao_hiem_pti_vta']['year_pti_vta'], array('1Y', '6M', '3M')) && $contract['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] > 0) {

            $pti_vta = $this->pti_vta_bn_model->findOne(array("code_contract" => $contract['code_contract']));

            if (!empty($pti_vta)) {
                $pti = $this->insert_pti_vta($contract, date('d-m-Y', strtotime("+1 days")), $contract['code_contract']);
                if ($pti->success != true) {
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'code_contract' => $contract['code_contract']
                    , 'pti_code' => ""
                    , 'contract_info' => $contract
                    , 'pti_info' => array()
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'status' => '3'
                    , 'erro_info' => '-'
                    , 'type_pti' => 'HD'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    ,'company_code' => $ma_cty

                    );
                    $this->pti_vta_bn_model->update(array('_id' => $pti_vta['_id']), $dt_pti);
                    $ck = false;
                } else {
                    $pti_dt = $pti->data;
                    $request = $pti->request;
                    $NGAY_KT = $pti->NGAY_KT;
                    $NGAY_HL = $pti->NGAY_HL;
                    $number_item = $pti->number_item;
                    $type_pti = "PTI_VTA";
                    $code_pti_vta = $type_pti . '_' . date("dmY") . "_" . time();
                    $dt_pti = array(
                        'contract_id' => (string)$contract['_id']
                    , 'code_contract_disbursement' => $contract['code_contract_disbursement']
                    , 'code_pti_vta' => $code_pti_vta
                    , 'code_contract' => $contract['code_contract']
                    , 'pti_code' => !empty($request->so_hd) ? $request->so_hd : ''
                    , 'so_xac_minh' => !empty($request->so_cmt) ? $request->so_cmt : ''
                    , 'contract_info' => $contract
                    , 'request' => $request
                    , 'NGAY_KT' => $NGAY_KT
                    , 'NGAY_HL' => $NGAY_HL
                    , 'pti_info' => $pti_dt
                    , 'status_sms' => '0'
                    , 'status_email' => '0'
                    , 'number_item' => (int)$number_item
                    , 'status' => 1
                    , 'erro_info' => '-'
                    , 'type_pti' => 'HD'
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    ,'company_code' => $ma_cty

                    );
                    $this->pti_vta_bn_model->update(array('_id' => $pti_vta['_id']), $dt_pti);
                    $ck = true;
                }
            }

        }

        if (!$ck) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thất bại",
                'data' => $contract
            );
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công",
                'data' => $contract
            );
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function restore_tnds_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id_contract'] = $this->security->xss_clean($data['id_contract']);
        $contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
         $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $contract['company_code']=$ma_cty;
        $code_contract_disbursement = $contract['code_contract_disbursement'];
        unset($contract['image_accurecy']);
        $ck = false;
        //bh tnds
        if (!empty($contract['loan_infor']['bao_hiem_tnds'])) {
            if (!empty($contract['loan_infor']['bao_hiem_tnds']['type_tnds'])) {
                $check_tnds = $this->contract_tnds_model->findOne(["code_contract" => $contract['code_contract']]);

                if (!empty($check_tnds)) {

                        if ($contract['loan_infor']['bao_hiem_tnds']['type_tnds'] == 'MIC_TNDS') {
                            $res = $this->call_mic_tnds($contract);
                            $type_tnds = 'MIC_TNDS';
                        } else {
                            $res = $this->call_vbi_tnds($contract);
                            $type_tnds = 'VBI_TNDS';
                        }

                    $data_tnds = array(
                    'contract_id' => (string)$contract['_id']
                    , 'code_contract' => $contract['code_contract']
                    , 'contract_info' => $contract
                    , 'store' => $store
                    , 'data' => $res
                    , 'type_tnds' => $type_tnds
                    , 'updated_at' => $this->createdAt
                    , 'updated_by' => $this->uemail
                    , 'company_code' => $ma_cty
                    );

                    $this->contract_tnds_model->update(
                        ['_id'=>$check_tnds['_id']],
                        $data_tnds);
                    $ck = true;
                }
            }
        }

        if (!$ck) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Thất bại"
            );
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công"
            );
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function search_auto_complete_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $dataRes = $this->contract_model->find_where_select(array($data['name'] => new \MongoDB\BSON\Regex($data['value'])), array("_id", "customer_infor.customer_email", "customer_infor.customer_phone_number", "customer_infor.customer_identify", "customer_infor.customer_identify_old"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $dataRes
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function update_code_contract_disbursement_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //quyền chỉ view kế toán
        if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => array(
                    "message" => "No have access right"
                )
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $data = $this->input->post();

        $contractData = $this->contract_model->findOne(array("code_contract_disbursement" => trim($data['code_contract_disbursement'])));
        if (!empty($contractData)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Mã hợp đồng đã tồn tại',
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $dt_ck = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));
        if (empty($dt_ck)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'ID hợp đồng không tồn tại',
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($data['id'])), array("code_contract_disbursement" => trim($data['code_contract_disbursement'])));
        $insertLog = array(
            "type" => "contract",
            "action" => "update_code_contract_disbursement",
            "contract_id" => $data['id'],
            "old" => (isset($dt_ck['code_contract_disbursement'])) ? $dt_ck['code_contract_disbursement'] : '',
            "new" => $data['id'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        /**
         * Save log to json file
         */


        $insertLogNew = [
            "type" => "contract",
            "action" => "update_code_contract_disbursement",
            "contract_id" => $data['id'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        ];
        $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
        $this->log_model->insert($insertLog);
        $insertLog['log_id'] = $log_id;

        $this->insert_log_file($insertLog, $data['id']);

        /**
         * ----------------------
         */

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Update mã hợp đồng thành công',
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_data_dashboard_homepage_post()
    {
        $contract = new Contract_model();
        $lead = new Lead_model();
        $store = new Store_model();
        $data_dashboard = array();
        $data_dashboard['store']['all'] = $store->count(array("status" => "active"));
        $data_dashboard['contract']['total_amount_money'] = $contract->sum_where_total_amount(array("status" => array('$in' => array(17, 19, 20, 21, 22, 23)), "loan_infor.amount_money" => array('$exists' => true), "loan_infor.amount_money" => array('$ne' => "")), '$loan_infor.amount_money');
        $data_dashboard['lead']['trade_success'] = $contract->count(array("status" => array('$in' => array(17, 19, 20, 21, 22, 23))));
        $data_dashboard['customer']['total'] = $lead->count();
        $first_time = $lead->findOneASC();
        $current_day = intval(strtotime(date('m/d/Y')));
        $create_at = !empty($first_time["created_at"]) ? intval($first_time["created_at"]) : $current_day;
        $period = intval(($current_day - $create_at) / (24 * 60 * 60));
        $data_dashboard['customer']['average_day'] = round($data_dashboard['customer']['total'] / $period);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $data_dashboard
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function coppy_contract_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        $this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
        $this->dataPost['code'] = $this->security->xss_clean($this->dataPost['code']);

        $inforDB = $this->contract_model->findOne(array("code_contract" => $this->dataPost['code_contract']));
        if (empty($inforDB)) return;
        $contract = $inforDB;

        if (empty($this->dataPost['code'])){
            //Init mã hợp đồng
            $typeProperty = !empty($inforDB['loan_infor']['type_property']['code']) ? $inforDB['loan_infor']['type_property']['code'] : "";
            $typeLoan = !empty($inforDB['loan_infor']['type_loan']['code']) ? $inforDB['loan_infor']['type_loan']['code'] : "";
            $loanProduct = !empty($inforDB['loan_infor']['loan_product']['code']) ? $inforDB['loan_infor']['loan_product']['code'] : "";
            $loanProductText = !empty($inforDB['loan_infor']['loan_product']['text']) ? $inforDB['loan_infor']['loan_product']['text'] : "";
        $resCodeContract = $this->initAutoCodeContract($inforDB['store']['id'], $typeProperty, $typeLoan, $loanProduct, $loanProductText, $this->dataPost['customer_infor']['customer_phone_number']);
        $inforDB['code_contract_disbursement'] = $resCodeContract['code_contract'];
        $inforDB['receiver_infor']['order_code'] = $resCodeContract['code_contract'];

            //Init code_contract_number hợp đồng
        $resNumberCodeContract = $this->initNumberContractCode();
            //Insert contract model
        $inforDB['code_contract'] = "00000" . $resNumberCodeContract['max_number_contract'];
        $inforDB['number_contract'] = $resNumberCodeContract['max_number_contract'];
        }

        $inforDB['status'] = 0;
        $inforDB['created_at'] = $this->createdAt;
        $inforDB['created_by'] = $this->uemail;


        unset($inforDB['response_get_transaction_withdrawal_status_nl']);
        unset($inforDB['status_create_withdrawal_nl']);
        unset($inforDB['status_disbursement']);
        unset($inforDB['status_create_withdrawal_nl']);
        unset($inforDB['disbursement_date']);
        unset($inforDB['disbursement_date_new']);
        unset($inforDB['max_code_auto_disbursement']);
        unset($inforDB['code_auto_disbursement']);
        unset($inforDB['type_cc']);
        unset($inforDB['type_gh']);
        unset($inforDB['code_contract_child_cc']);
        unset($inforDB['structure_all']);
        unset($inforDB['extend_all']);
        unset($inforDB['code_contract_child_gh']);
        unset($inforDB['extend_date']);
        unset($inforDB['code_contract_parent_gh']);
        unset($inforDB['code_contract_parent_cc']);
        unset($inforDB['debt']);
        unset($inforDB['updated_at']);
        unset($inforDB['updated_by']);
        unset($inforDB['expire_date']);
        unset($inforDB['investor_code']);
        unset($inforDB['investor_infor']);
        unset($inforDB['import_update_contract']);
        unset($inforDB['total_debt_pay']);
        unset($inforDB['original_debt']);
        unset($inforDB['chan_bao_hiem']);
        unset($inforDB['reason']);
        unset($inforDB['asm']);
        unset($inforDB['bank_name_disbursement']);
        unset($inforDB['code_auto_disbursement']);
        unset($inforDB['code_transaction_bank_disbursement']);
        unset($inforDB['content_transfer_disbursement']);
        unset($inforDB['_id']);
        unset($inforDB['fee']);
        unset($inforDB['loan_infor']['code_coupon']);
        unset($inforDB['code_contract']);
        unset($inforDB['code_contract_disbursement']);
        unset($inforDB['loan_infor']['bao_hiem_pti_vta']);
        if (!empty($this->dataPost['code']) && $this->dataPost['code'] == 1){
            unset($inforDB['image_accurecy']);
        }


        $contractId = $this->contract_model->insertReturnId($inforDB);
        $insertLog = array(
            "type" => "contract",
            "action" => "coppy_contract",
            "contract_id" => $contractId,
            "old" => $contract,
            "new" => $inforDB,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($insertLog);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Extension contract success",
            'code_contract_new' => $inforDB['code_contract'],

        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;


    }

    private function initAutoCodeContract($store_id, $typeProperty, $typeLoan, $loanProduct, $loanProductText, $customer_phone_number)
    {
        $res = array(
            "code_contract" => "",
            // "max_number_contract" => ""
        );

        $store_info = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($store_id)));
        $code_province_store = !empty($store_info['province']['name']) ? $store_info['province']['name'] : "";
        $code_address_store = !empty($store_info['code_address_store']) ? $store_info['code_address_store'] : "";
        $code_address_store = trim(strtoupper($code_address_store));
        $short_name_province = "";
        $short_name_loan_product = "";
        $name_code_contract = "";
        $type_loan_property = "";
        $number = '';
        $CBNV = [8, 9, 15];
        $GCNQSDĐ = 13;
        $array_short_name_province = explode(" ", $code_province_store);
        foreach ($array_short_name_province as $short_name) {
            $short_name_province .= strtoupper($short_name[0]);
        }
        $loanProductTextUpper = strtoupper($loanProductText);
        $array_short_name_loan_product = explode(" ", $loanProductTextUpper);
        foreach ($array_short_name_loan_product as $short_name_product) {
            $short_name_loan_product .= $short_name_product[0];
        }

        if ($typeLoan == "TC") {
            $name_code_contract = "HĐTC";
        } else {
            $name_code_contract = "HĐCC";
        }
        if ($typeLoan == 'CC') {
            $type_loan_property = "CC" . $typeProperty;
        } elseif ($typeLoan == 'DKX') {
            $type_loan_property = "ĐK" . $typeProperty;
        } elseif ($typeLoan == 'TC') {
            if (in_array($loanProduct, $CBNV)) {
                $type_loan_property = 'VTC' . "CBNV";
            } elseif ($loanProduct == $GCNQSDĐ) {
                $type_loan_property = 'VTC' . "GCNQSDĐ";
            } else {
                $type_loan_property = 'VTC' . $short_name_loan_product;
            }
        }

        $current_year_code_contract = date("y");
        $current_month_code_contract = date("m");
        $current_month_year = $current_year_code_contract . $current_month_code_contract;

        if (!empty($current_month_year)) {
            $condition['code_month_year_contract'] = $current_month_year;
        }
        if (!empty($store_id)) {
            $condition['store_id'] = $store_id;
        }
        $count = $this->contract_model->get_count_by_month_year($condition);
        if ($count == 0) {
            $number = '01';
        } elseif ($count > 0 && ($count + 1) < 10) {
            $number = '0' . ($count + 1);
        } else {
            $number = $count + 1;
        }
        $endStringCodeContractD = $current_year_code_contract . $current_month_code_contract . '/' . $number;
        if (!empty($endStringCodeContractD)) {
            $condition['endCodeContractD'] = $endStringCodeContractD;
        }

        $checkEndStringCodeContractD = $this->contract_model->get_current_month_date_number_code_contract_d($condition);
        if (!empty($checkEndStringCodeContractD)) {
            while ($number <= 300) {
                if ($number > 0 && ($number + 1) < 10) {
                    $number = '0' . ($number + 1);
                } else {
                    $number = $number + 1;
                }
                $codeContract = $name_code_contract . '/' . $type_loan_property . '/' . $short_name_province . $code_address_store . '/' . $current_year_code_contract . $current_month_code_contract . '/' . $number;
                $endStringCodeContractD = $current_year_code_contract . $current_month_code_contract . '/' . $number;
                if (!empty($endStringCodeContractD)) {
                    $condition['endCodeContractD'] = $endStringCodeContractD;
                }
                $checkEndStringCodeContractD = $this->contract_model->get_current_month_date_number_code_contract_d($condition);
                if (empty($checkEndStringCodeContractD)) {
                    break;
                }
            }
        } else {
            $codeContract = $name_code_contract . '/' . $type_loan_property . '/' . $short_name_province . $code_address_store . '/' . $current_year_code_contract . $current_month_code_contract . '/' . $number;
        }

        //role tạo hđ sẽ có mã hợp đồng dạng TOPUP
        if (in_array($this->id, $this->leader_telesales())) {
            $codeContract = $codeContract.'/TAIVAY';
        }
        if (in_array($this->id, $this->topup())) {
            $codeContract = $codeContract.'/TOPUP';
        }
        if(in_array($this->id, $this->getGroupRole_gdv()) || in_array($this->id, $this->getGroupRole_cht())) {
        	$check_topup_pgd = $this->list_topup_model->findOne(['customer_phone_number' => $customer_phone_number]);
        	if (!empty($check_topup_pgd)){
				$codeContract = $codeContract.'/TOPUP';
			}
		}

        $codeContract = trim(strtoupper($codeContract));
        $res = array(
            "code_contract" => $codeContract,
            // "max_number_contract" => $maxNumberContract
        );
        return $res;
    }

    public function search_phone_post()
    {
//      $flag = notify_token($this->flag_login);
//      if ($flag == false) return;

        $data = $this->input->post();

        $condition['phone'] = $data['phone'];

        $result = $this->contract_model->select_excel($condition);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $result
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    private function initNumberAssetCode()
    {
        $maxNumber = $this->asset_management_model->getMaxNumberAsset();
        $maxNumberContract = !empty($maxNumber[0]['number_asset']) ? (int)$maxNumber[0]['number_asset'] + 1 : 1;
        return $maxNumberContract;
    }

    public function get_asset_manager($customer_name, $property, $loan_info, $image_accurecy)
    {
        $type = !empty($loan_info['type_property']) ? $loan_info['type_property']['code'] : '';
        $customerName = !empty($property[5]['value']) ? trim($property[5]['value']) : trim($customer_name);
        $bien_so_xe = !empty($property[2]['value']) ? $property[2]['value'] : '';
        $nhan_hieu = !empty($property[0]['value']) ? trim($property[0]['value']) : '';
        $model = !empty($property[1]['value']) ? trim($property[1]['value']) : '';
        $so_khung = !empty($property[3]['value']) ? strtoupper(trim($property[3]['value'])) : '';
        $so_may = !empty($property[4]['value']) ? strtoupper(trim($property[4]['value'])) : '';
        $dia_chi = !empty($property[6]['value']) ? (trim($property[6]['value'])) : '';
        $so_dang_ki = !empty($property[7]['value']) ? (trim($property[7]['value'])) : '';
        $ngay_cap = !empty($property[8]['value']) ? strtotime($property[8]['value']) : '';
        $number_asset = $this->initNumberAssetCode();
        $lengStr = strlen($number_asset);
        switch ($lengStr) {
            case 1:
                $asset_code = "0000000" . $number_asset;
                break;
            case 2:
                $asset_code = "000000" . $number_asset;
                break;
            case 3:
                $asset_code = "00000" . $number_asset;
                break;
            case 4:
                $asset_code = "0000" . $number_asset;
                break;
            case 5:
                $asset_code = "000" . $number_asset;
                break;
            case 6:
                $asset_code = "00" . $number_asset;
                break;
            case 7:
                $asset_code = "0" . $number_asset;
                break;
            default:
                $asset_code = (string)$number_asset;
        }
        if ($type == 'TC') {
            $param = [
                'customer_name' => trim($customerName),
                'number_asset' => $number_asset,
                'asset_code' => $asset_code,
                'type' => !empty($loan_info['type_property']) ? $loan_info['type_property']['code'] : '',
                'product' => !empty($loan_info['name_property']) ? $loan_info['name_property']['text'] : '',
                'image' => !empty($image_accurecy['driver_license']) ? $image_accurecy['driver_license'] : '',
                'status' => 'active',
                'created_at' => $this->createdAt,
                'created_by' => $this->uemail
            ];
            $asset_id = $this->asset_management_model->insertReturnId($param);
        } else {
            $asset = $this->asset_management_model->find_where(['so_khung' => $so_khung, 'so_may' => $so_may]);
            if (!empty($asset)) {
                $image = !empty($asset[0]['image']) ? $asset[0]['image'] : [];
                $image1 = [];
                foreach ($image as $key => $value) {
                    $image[$key] = $value;
                }
                $driver_license = [];
                foreach ($image_accurecy['driver_license'] as $k => $value) {
                    $driver_license[$k] = $value;
                }
                $this->asset_management_model->update(
                    ['_id' => $asset[0]['_id']],
                    [
                        'bien_so_xe' => strtoupper(trim(str_replace(array('.', '-', ' '), '', $bien_so_xe))),
                        'updated_at' => $this->createdAt,
                        'updated_by' => $this->uemail,
                        'image' => !empty($image_accurecy['driver_license']) ? (object)array_merge($driver_license, $image1) : $image,
                        'product' => !empty($loan_info['name_property']) ? $loan_info['name_property']['text'] : '',
                        'nhan_hieu' => $nhan_hieu,
                        'model' => $model,
                        'dia_chi' => $dia_chi,
                        "so_dang_ki" => $so_dang_ki,
                        'ngay_cap' => $ngay_cap,
                        'customer_name' => trim($customerName)
                    ]);
                $asset_id = $asset[0]['_id'];
            } else {
                $param = [
                    'customer_name' => trim($customerName),
                    'number_asset' => $number_asset,
                    'asset_code' => $asset_code,
                    'type' => !empty($loan_info['type_property']) ? $loan_info['type_property']['code'] : '',
                    'product' => !empty($loan_info['name_property']) ? $loan_info['name_property']['text'] : '',
                    'image' => !empty($image_accurecy['driver_license']) ? $image_accurecy['driver_license'] : '',
                    'bien_so_xe' => strtoupper(trim(str_replace(array('.', '-', ' '), '', $bien_so_xe))),
                    'nhan_hieu' => $nhan_hieu,
                    'model' => $model,
                    'so_khung' => $so_khung,
                    'so_may' => $so_may,
                    'dia_chi' => $dia_chi,
                    "so_dang_ki" => $so_dang_ki,
                    'ngay_cap' => $ngay_cap,
                    'status' => 'active',
                    'created_at' => $this->createdAt,
                    'created_by' => $this->uemail
                ];
                $asset_id = $this->asset_management_model->insertReturnId($param);
            }
        }
        return $asset_id;
    }

    public function contractCreate_post()
    {

        $data = $this->input->post();

        $condition['customer_identify'] = $data['customer_identify'];

        $contract = $this->contract_model->findOne_identify(array("customer_infor.customer_identify" => $data['customer_identify']));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng không tồn tại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);

    }

    public function cancel_ContractDay_post()
    {

        $contract = $this->contract_model->find_where_cancel(array("status" => 8));

        $time = strtotime('-10 day', strtotime(date('Y-m-d')));

        if (!empty($contract)) {
            foreach ($contract as $key => $value) {
                $data_log = $this->log_hs_model->find_where_in_cancel($value->code_contract);
                if (!empty($data_log)) {
                    foreach ($data_log as $item) {
                        if ($item['created_at'] < $time) {
                            $this->contract_model->update(array("code_contract" => $item["old"]['code_contract']), array("status" => 3));

                            $insertLog = array(
                                "type" => "contract_cron",
                                "action" => "updateStatus_cancel",
                                "code_contract" => $item["old"]['code_contract'],
                                "new" => [
                                    "status" => 3
                                ],
                                "created_at" => $this->createdAt
                            );
                            $this->log_model->insert($insertLog);
                        }
                    }
                }
            }
        }


        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update status contract success",
        );
        $this->set_response($response, REST_Controller::HTTP_OK);

    }

    public function contract_borrowing_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        if (in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
            $all = true;
        }
        if ($all) {
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

        $contract = $this->contract_model->find_where_mhd($condition);

        if (!empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $contract
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

    }

    public function contract_ksnb_post()
    {

        $contract = $this->contract_model->find_where_ksnb();

        if (!empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $contract
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

    }

    public function check_id_post()
    {
        $code_contract_disbursement = $this->security->xss_clean($this->dataPost['id']);

        $contract = $this->contract_model->findOne(array("code_contract_disbursement" => $code_contract_disbursement));

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function store_post()
    {
        $code_contract_disbursement = $this->security->xss_clean($this->dataPost['code_contract_disbursement_text']);

        $contract = $this->contract_model->find_where_qlhs(array("code_contract_disbursement" => $code_contract_disbursement));

        if (!empty($contract) && count($contract) > 1) {
            foreach ($contract as $value) {
                if ($value['status'] != 19) {
                    $arr = $value;
                    break;
                }
            }
        }
        if (count($contract) == 1) {
            $arr = $contract[0];
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $arr,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function getGroupRole_asm()
    {
        $groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'quan-ly-khu-vuc'));

        $arr = array();
        foreach ($groupRoles as $groupRole) {
            if (!empty($groupRole['users'])) {

                foreach ($groupRole['users'] as $value) {
                    foreach ($value as $key => $item) {
                        array_push($arr, $key);
                    }

                }
            }
        }
        return $arr;
    }

    public function log_reminder_post()
    {
        $data = $this->input->post();
        $id_contract = !empty($data['id_contract']) ? $data['id_contract'] : '';
        $logs = $this->log_call_debt_model->find_where(['contract_id' => $id_contract]);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "thành công",
            'data' => $logs
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function get_user_asm_by_store($id)
    {

        $roles = $this->role_model->findAsm();
        $data = [];
        $i = 0;
        foreach ($roles as $key => $role) {
            if (!empty($role['users']) && !empty($role['stores'])) {
                $data[$i]['users'] = $role['users'];
                $data[$i]['stores'] = $role['stores'];
                $i++;
            }
        }
        foreach ($data as $da) {
            foreach ($da['stores'] as $d) {
                $storeId = [];
                $storeName = [];
                foreach ($d as $k => $v) {
                    array_push($storeId, $k);
                    array_push($storeName, $v['name']);
                }
                if (in_array($id, $storeId) == true) {
                    $user_id = [];
                    foreach ($da['users'] as $d) {
                        foreach ($d as $k => $v) {
                            array_push($user_id, $k);
                        }
                    }
                }

            }
        }
        return $user_id;
    }

    public function get_user_by_store($store_id)
    {
        $roles = $this->role_model->find_where(['status' => 'active']);
        $data = [];
        $i = 0;
        foreach ($roles as $key => $role) {
            if (!empty($role['users']) && !empty($role['stores'])) {
                $data[$i]['users'] = $role['users'];
                $data[$i]['stores'] = $role['stores'];
                $i++;
            }
        }
        foreach ($data as $da) {
            foreach ($da['stores'] as $d) {
                $storeId = [];
                foreach ($d as $k => $v) {
                    array_push($storeId, $k);
                }
                if (in_array($store_id, $storeId) == true) {
                    if (count($da['stores']) > 1) {
                        continue;
                    }
                    $user_id = [];
                    foreach ($da['users'] as $ds) {
                        foreach ($ds as $k => $v) {
                            array_push($user_id, $k);
                        }
                    }
                }
            }
        }
        return $user_id;
    }

    public function call_mic_tnds($contract)
    {
        $ten_kh = "";
        $property = !empty($contract['property_infor']) ? $contract['property_infor'] : array();
        foreach ($property as $p) {
            if ($p['slug'] === 'ho-ten-chu-xe') {
                $ten_kh = $p['value'];
            }
        }
        $NGAY_HL = date('d/m/Y');
        $NGAY_KT = date('d/m/Y', strtotime("+1 year"));
        $code = $contract['code_contract'] ? $contract['code_contract'] : $contract['code_contract_disbursement'];
        $price = $contract['loan_infor']['bao_hiem_tnds']['price_tnds'] ? $contract['loan_infor']['bao_hiem_tnds']['price_tnds'] : 0;
        $loai_xe = $contract['loan_infor']['bao_hiem_tnds']['dung_tich_xe'] ? $contract['loan_infor']['bao_hiem_tnds']['dung_tich_xe'] : '';
        $bien_xe = $contract['property_infor'] ? $contract['property_infor'][2]['value'] : '';
        $muc_trach_nhiem = $contract['loan_infor']['bao_hiem_tnds']['muc_trach_nhiem'] ? $contract['loan_infor']['bao_hiem_tnds']['muc_trach_nhiem'] : '0';
        $cmt = $contract['customer_infor']['customer_identify'] ? $contract['customer_infor']['customer_identify'] : '';
        $ngay_sinh = $contract['customer_infor']['customer_BOD'] ? date('d/m/Y', strtotime($contract['customer_infor']['customer_BOD'])) : '';
        $phone = $contract['customer_infor']['customer_phone_number'] ? $contract['customer_infor']['customer_phone_number'] : '';
        $mail = $contract['customer_infor']['customer_email'] ? $contract['customer_infor']['customer_email'] : '';
        $address = $contract['houseHold_address']['address_household'] . ',' . $contract['houseHold_address']['ward_name'] . ',' . $contract['houseHold_address']['district_name'] . ',' . $contract['houseHold_address']['province_name'];
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
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
                    <DBHM>K</DBHM>
                    <TENM></TENM>
                    <CMTM></CMTM>
                    <NG_SINHM></NG_SINHM>                     
                    <MOBIM></MOBIM>
                    <EMAILM></EMAILM>
                    <DCHIM></DCHIM>
                    <MA_CTY>' . $ma_cty . '</MA_CTY>
                  </XMLINPUT>
            ]]>  </ns1:xmlinput>
                  </ns1:ws_GCN_TRA>
            ';
        $wsdl_url = $this->config->item("API_MIC") . '?wsdl';
        $params = new SoapVar($originalXML, XSD_ANYXML);
        $this->soapClient = new SoapClient($wsdl_url, array('soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => false));
        $this->soapClient->__setLocation($this->config->item("API_MIC"));
        $result = $this->soapClient->ws_GCN_TRA($params);
        $xml = simplexml_load_string($result->ws_GCN_TRAResult);
        $this->log_mic_tnds($originalXML, $xml, 'MIC_TNDS', $code);
        $response = [
            'request' => $originalXML,
            'response' => $xml,
            'NGAY_HL' => $NGAY_HL,
            'NGAY_KT' => $NGAY_KT

        ];
        return json_decode(json_encode($response));
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

    public function call_vbi_tnds($contract)
    {
        $ten_kh = "";
        $property = !empty($contract['property_infor']) ? $contract['property_infor'] : array();
        foreach ($property as $p) {
            if ($p['slug'] === 'ho-ten-chu-xe') {
                $ten_kh = $p['value'];
            }
        }
        if ($contract['customer_infor']['customer_BOD'] == '1') {
            $gioi_tinh = 'nam';
        } else {
            $gioi_tinh = 'nu';
        }
        $property = $this->main_property_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($contract['loan_infor']['name_property']['id'])]);
        $address = $contract['houseHold_address']['address_household'] . ',' . $contract['houseHold_address']['ward_name'] . ',' . $contract['houseHold_address']['district_name'] . ',' . $contract['houseHold_address']['province_name'];
        $NGAY_HL = date('d/m/Y');
        $NGAY_KT = date('d/m/Y', strtotime("+1 year"));
        $param = [
            'vbi_api_common_key' => $this->config->item("vbi_api_common_key"),
            'doi_tac' => $this->config->item("doi_tac"),
            'bieu_phi' => 'VBI_CU',
            "nhom" => "xc.2.1",
            "so_id_dtac" => "0",
            "so_id_vbi" => "0",
            "nv" => "xc.2.1",
            "nsd" => $this->config->item("nsd_vbi_tnds"),
            "TEN" => $ten_kh ? $ten_kh : '',
            "DCHI" => $address,
            "doi_tuong" => "cn",
            "ngay_sinh" => $contract['customer_infor']['customer_BOD'] ? date('d/m/Y', strtotime($contract['customer_infor']['customer_BOD'])) : '',
            "gioi_tinh" => $gioi_tinh ? $gioi_tinh : 'nam',
            "cmt" => $contract['customer_infor']['customer_identify'] ? $contract['customer_infor']['customer_identify'] : '',
            "moi_qh" => '',
            "d_thoai" => $contract['customer_infor']['customer_phone_number'] ? $contract['customer_infor']['customer_phone_number'] : '',
            "fax" => '',
            "dai_dien" => "",
            "cvu_dai_dien" => "",
            "email" => $contract['customer_infor']['customer_email'] ? $contract['customer_infor']['customer_email'] : '',
            "TVV" => "",
            "MST" => "",
            "bien_xe" => $contract['property_infor'] ? $contract['property_infor'][2]['value'] : '',
            "so_khung" => "",
            "so_may" => "",
            "noi_nhan" => $address,
            "hang_xe" => $contract['loan_infor']['bao_hiem_tnds']['hang_xe'] ? $contract['loan_infor']['bao_hiem_tnds']['hang_xe'] : '',
            "hieu_xe" => $contract['loan_infor']['bao_hiem_tnds']['hieu_xe'] ? $contract['loan_infor']['bao_hiem_tnds']['hieu_xe'] : '',
            "nhom_xe" => $contract['loan_infor']['bao_hiem_tnds']['nhom_xe'] ? $contract['loan_infor']['bao_hiem_tnds']['nhom_xe'] : '',
            "nam_sx" => $property['year_property'] ? $property['year_property'] : '2020',
            "loai_xe" => "",
            "so_cho" => '4',
            "ttai" => '1',
            "md_sd" => "K",
            "dkbs" => "",
            'trang_thai' => 'D',
            "gtri_xe" => $property['price'] ? number_format($property['price']) : '500,000,000',
            "list_dk" => [
                0 => [
                    "tien_bh" => '',
                    "loai" => "BN",
                    "lh_nv" => "XC.2.1",
                    "mien_thuong" => "",
                    "ktru" => "",
                    "ngay_hl" => $NGAY_HL,
                    "ngay_kt" => $NGAY_KT,
                    "tl_phi_yc" => '0'

                ],
            ],
        ];
        $vbi = new Vbi_tnds_oto();
        $result = $vbi->tao_don($param);
        $this->log_vbi_tnds_model->insert(
            [
                'type' => 'tao_don',
                'request' => $param,
                'response' => $result,
                'created_at' => $this->createdAt,
                'created_by' => $this->uemail
            ]
        );
        $response = [
            'request' => $param,
            'response' => $result,
            'NGAY_HL' => $NGAY_HL,
            'NGAY_KT' => $NGAY_KT

        ];
        return $response;
    }

    public function test_post()
    {
        $id = $_POST['id'];
        $contract = $this->contract_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        $this->call_vbi_tnds($contract);
    }

    function vn_to_str_khong_dau($str)
    {
        $unicode = array(
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        );
        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }
        $str = str_replace(' ', ' ', $str);

        return $str;

    }

    public function contract_tempo_new_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        // date_default_timezone_set('Asia/Ho_Chi_Minh');
        $data = $this->input->post();
        $store = !empty($data['store_id']) ? $data['store_id'] : "";
        $status = !empty($data['status']) ? $data['status'] : "";
        $start = !empty($data['start']) ? $data['start'] : "";
        $end = !empty($data['end']) ? $data['end'] : "";
        $fdebt = isset($data['fdebt']) ? $data['fdebt'] : "";
        $tdebt = isset($data['tdebt']) ? $data['tdebt'] : "";
        $customer_name = !empty($data['customer_name']) ? $data['customer_name'] : "";
        $customer_phone_number = !empty($data['customer_phone_number']) ? $data['customer_phone_number'] : "";
        $code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : "";
        $code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";
        $type_loan = !empty($data['type_loan']) ? $data['type_loan'] : "";
        $type_property = !empty($data['type_property']) ? $data['type_property'] : "";
        $amount_money = !empty($data['amount_money']) ? $data['amount_money'] : 0;
        $chan_bao_hiem = !empty($data['chan_bao_hiem']) ? $data['chan_bao_hiem'] : '';

        $amount_money = !empty($data['amount_money']) ? $data['amount_money'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        if (($fdebt != "") && ($tdebt != '')) {
            $condition['fdebt'] = (int)$fdebt;
            $condition['tdebt'] = (int)$tdebt;
        }
        if (!empty($status)) {
            $condition['status'] = $status;
        }

        if (!empty($customer_name)) {
            $condition['customer_name'] = trim($customer_name);
        }
        if (!empty($customer_phone_number)) {
            $condition['customer_phone_number'] = trim($customer_phone_number);
        }
        if (!empty($amount_money)) {
            $condition['amount_money'] = (int)$amount_money;
        }
        if (!empty($chan_bao_hiem)) {
            $condition['chan_bao_hiem'] = $chan_bao_hiem;
        }

        if (!empty($amount_money)) {
            $condition['amount_money'] = trim($amount_money);
        }
        if (!empty($type_loan)) {
            $condition['type_loan'] = trim($type_loan);
        }
        if (!empty($type_property)) {
            $condition['type_property'] = trim($type_property);
        }
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        if (in_array('quan-ly-khu-vuc', $groupRoles) || in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
            $all = true;
        }

        $storefind = [];
        $storeAll = $this->store_model->find();
        foreach ($storeAll as $s) {
            array_push($storefind, (string)$s['_id']);
        }
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = trim($code_contract_disbursement);
            $condition['stores'] = array_unique($storefind);
        } elseif (!empty($customer_name)) {
            $condition['customer_name'] = trim($customer_name);
            $condition['stores'] = array_unique($storefind);
        } elseif (!empty($code_contract)) {
            $condition['code_contract'] = trim($code_contract);
            $condition['stores'] = array_unique($storefind);
        } else {
            $condition['stores'] = [];
            if (!empty($store)) {
                $condition['stores'] = [$store];
            } else {
                if ($all) {
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
                } else {
                    $condition['stores'] = array_unique($storefind);
                }

            }
        }

        $per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
        $uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
        $contract = $this->contract_model->getContractUser($condition, $per_page, $uriSegment);
        $total = $this->contract_model->getTotalContractByUser($condition);

        $tien_vay = 0;
        $tien_bao_hiem = 0;
        if (!empty($contract)) {
            foreach ($contract as $key => $c) {
                $tempo = $this->contract_tempo_model->find_where(['code_contract' => $c['code_contract'], 'status' => 1]);
                if (!empty($tempo)) {
                    $c['tempo'] = $tempo[0];
                }


                $tien_vay += (int)$c['loan_infor']['amount_money'];
                $tien_bao_hiem += (int)$c['loan_infor']['amount_money'] - (int)$c['loan_infor']['amount_loan'];


            }
        }
        $tong_tien_vay = 0;
        $tong_tien_goc = 0;
        $tong_hd_qua_han = 0;
        $tong_hd_can_nhac_no = 0;
        $tien_vay = $this->contract_model->sum_where_total_amount($condition, '$loan_infor.amount_money');
        $tien_bao_hiem = $this->contract_model->sum_where_total_amount($condition, '$loan_infor.amount_money') - $this->contract_model->sum_where_total_amount($condition, '$loan_infor.amount_loan');
        $contract_all = $this->contract_model->getAllContractByUser($condition);
        foreach ($contract_all as $value) {
            if ($value->status == 17 || $value->status == 19) {
                $tong_tien_vay += (int)$value->loan_infor->amount_money;
            }
            if ($value->status == 17) {
                if ($value->debt->so_ngay_cham_tra > 0) {
                    $tong_hd_qua_han += 1;
                }
                if ($value->debt->so_ngay_cham_tra >= -5 && $value->debt->so_ngay_cham_tra <= 3) {
                    $tong_hd_can_nhac_no += 1;
                }
                $tong_tien_goc += (int)$value->original_debt->du_no_goc_con_lai;
            }
        }

        $tong_tien_cho_vay_va_goc['tong_tien_vay'] = $tong_tien_vay;
        $tong_tien_cho_vay_va_goc['tong_tien_goc_con_lai'] = $tong_tien_goc;
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'total' => $total,
            'data' => $contract,
            'tong_tien_cho_vay_va_goc' => $tong_tien_cho_vay_va_goc,
            'tong_hd_qua_han' => $tong_hd_qua_han,
            'tong_hd_can_nhac_no' => $tong_hd_can_nhac_no,
            'tien_vay' => $tien_vay,
            'tien_bao_hiem' => $tien_bao_hiem,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function not_reminder_post()
    {
        $data = $this->input->post();
        $id_contract = !empty($data['id_contract']) ? $data['id_contract'] : '';
        $date_pay = !empty($data['date_pay']) ? $data['date_pay'] : '';
        $money_pay = !empty($data['money_pay']) ? $data['money_pay'] : '';
        $note = !empty($data['note']) ? $data['note'] : '';

        if (empty($date_pay) && empty($money_pay) && empty($note)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không có dữ liệu gửi đi"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $log = array(
            "type" => "contract",
            "action" => "note_reminder",
            "contract_id" => $id_contract,
            "new" => [
                'note' => $note,
                "payment_date" => $date_pay,
                "amount_payment_appointment" => $money_pay,
                "contract_id" => $id_contract
            ],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_call_debt_model->insert($log);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Thêm thành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function check_store_user_post()
    {
        $data = $this->input->post();
        $id_user = !empty($data['id']) ? $data['id'] : '';
        $groupRoles = $this->getGroupRole($id_user);
        $all = false;
        if (in_array('quan-ly-khu-vuc', $groupRoles) || in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
            $all = true;
        }

        if ($all) {
            $stores = $this->getStores($id_user);
            if (empty($stores)) {
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'data' => array()
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => array_unique($stores)
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function log_reminder_contract_post()
    {
        $data = $this->input->post();
        $id_contract = !empty($data['id_contract']) ? $data['id_contract'] : '';
        $logs = $this->log_call_debt_model->find_where(['contract_id' => $id_contract]);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "thành công",
            'data' => $logs
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function contract_tempo_all_thn_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $this->dataPost = isset($this->input->post()['condition']) ? $this->input->post()['condition'] : $this->input->post();
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $condition = array();
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        $contract = array();
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = $code_contract_disbursement;
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = $customer_name;
        }
        $contract = $this->contract_model->getRemind_debt_first_thn($condition);
        if (!empty($contract)) {
            foreach ($contract as $key => $c) {
                $c['investor_name'] = "";
                if (isset($c['investor_code'])) {
                    $investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
                    $c['investor_name'] = $investors['name'];
                }
                $cond = array(
                    'code_contract' => $c['code_contract'],
                    'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
                );
                $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                $c['detail'] = array();
                if (!empty($detail)) {
                    $total_paid = 0;
                    foreach ($detail as $de) {

                        $total_paid = $total_paid + $de['tien_tra_1_ky'];
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

                        $c['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
                    }
                }
                //     $time=0;
                //     $c['time']=0;
                //          if (!empty($c['detail']) && $c['detail']['status'] == 1) {
                //  $current_day = strtotime(date('m/d/Y'));
                //  $datetime = !empty($c['detail']['ngay_ky_tra']) ? intval($c['detail']['ngay_ky_tra']) : $current_day;
                //  $time = intval(($current_day - $datetime) / (24 * 60 * 60));
                //              $c['time']=$time;
                //             if ($time <-5) {

                // unset($contract[$key]);
                //     }
                //   }


            }
        }


        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function getConditionBucket($bucket)
    {
        $condition = [];
        if ($bucket == 'B0') {
            $condition['fBucket'] = -40;
            $condition['tBucket'] = 0;
        } elseif ($bucket == 'B1') {
            $condition['fBucket'] = 1;
            $condition['tBucket'] = 30;
        } elseif ($bucket == 'B2') {
            $condition['fBucket'] = 31;
            $condition['tBucket'] = 60;
        } elseif ($bucket == 'B3') {
            $condition['fBucket'] = 61;
            $condition['tBucket'] = 90;
        } elseif ($bucket == 'B4') {
            $condition['fBucket'] = 91;
            $condition['tBucket'] = 120;
        } elseif ($bucket == 'B5') {
            $condition['fBucket'] = 121;
            $condition['tBucket'] = 150;
        } elseif ($bucket == 'B6') {
            $condition['fBucket'] = 151;
            $condition['tBucket'] = 180;
        } elseif ($bucket == 'B7') {
            $condition['fBucket'] = 181;
            $condition['tBucket'] = 360;
        } elseif ($bucket == 'B8') {
            $condition['fBucket'] = 361;
            $condition['tBucket'] = 10000;
        }
        return $condition;
    }

    public function count_contract_tempo_post()
    {
//      $flag = notify_token($this->flag_login);
//      if ($flag == false) return;
        // date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->dataPost = $this->input->post()['condition'];
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $store_id = !empty($this->dataPost['store_id']) ? $this->dataPost['store_id'] : "";
        $id_card = !empty($this->dataPost['id_card']) ? $this->dataPost['id_card'] : "";
        $bucket = !empty($this->dataPost['bucket']) ? $this->dataPost['bucket'] : "";
        $investor_code = !empty($this->dataPost['investor_code']) ? $this->dataPost['investor_code'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "17";
        $status_disbursement = !empty($this->dataPost['status_disbursement']) ? $this->dataPost['status_disbursement'] : "";
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract_disbursement'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
        $vung_mien = !empty($this->dataPost['vung_mien']) ? $this->dataPost['vung_mien'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        if (!empty($status)) {
            $condition['status'] = $status;
        }
        if (!empty($status_disbursement)) {
            $condition['status_disbursement'] = $status_disbursement;
        }

        if (!empty($investor_code)) {
            $condition['investor_code'] = $investor_code;
        }
        if (!empty($store_id)) {
            $condition['store'] = $store_id;
        }

        if (!empty($id_card)) {
            $condition['id_card'] = $id_card;
        }
        if (!empty($bucket)) {
            $result = $this->getConditionBucket($bucket);
            $condition['fBucket'] = $result['fBucket'];
            $condition['tBucket'] = $result['tBucket'];
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = trim($customer_name);
        }
        if (!empty($customer_phone_number)) {
            $condition['customer_phone_number'] = trim($customer_phone_number);
        }
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = trim($code_contract_disbursement);
        }
        if (!empty($code_contract)) {
            $condition['code_contract'] = trim($code_contract);
        }
        if (!empty($vung_mien)) {
            $pgd = $this->getPgdToVungMien($vung_mien);
            $condition['store_vung'] = $pgd;
        }
        $groupRoles = $this->getGroupRole($this->id);

        $all = false;
        if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles) || in_array('giao-dich-vien', $groupRoles)) {
            $all = true;
        }
        if (!empty($code_contract_disbursement) || !empty($customer_name) || !empty($customer_phone_number)) {
            $all = false;
        }
        if ($all) {
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
        $total = $this->contract_model->getContractByTimeAllExcel(array(), $condition);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'total' => $total
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function contract_tempo_excel_post()
    {
//      $flag = notify_token($this->flag_login);
//      if ($flag == false) return;
        // date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->dataPost = $this->input->post()['condition'];
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $store_id = !empty($this->dataPost['store_id']) ? $this->dataPost['store_id'] : "";
        $id_card = !empty($this->dataPost['id_card']) ? $this->dataPost['id_card'] : "";
        $bucket = !empty($this->dataPost['bucket']) ? $this->dataPost['bucket'] : "";
        $investor_code = !empty($this->dataPost['investor_code']) ? $this->dataPost['investor_code'] : "";
        $status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "17";
        $status_disbursement = !empty($this->dataPost['status_disbursement']) ? $this->dataPost['status_disbursement'] : "";
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
        $customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
        $code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract_disbursement'] : "";
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
        $vung_mien = !empty($this->dataPost['vung_mien']) ? $this->dataPost['vung_mien'] : "";
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        if (!empty($status)) {
            $condition['status'] = $status;
        }
        if (!empty($status_disbursement)) {
            $condition['status_disbursement'] = $status_disbursement;
        }

        if (!empty($investor_code)) {
            $condition['investor_code'] = $investor_code;
        }
        if (!empty($store_id)) {
            $condition['store'] = $store_id;
        }

        if (!empty($id_card)) {
            $condition['id_card'] = $id_card;
        }
        if (!empty($bucket)) {
            $result = $this->getConditionBucket($bucket);
            $condition['fBucket'] = $result['fBucket'];
            $condition['tBucket'] = $result['tBucket'];
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = trim($customer_name);
        }
        if (!empty($customer_phone_number)) {
            $condition['customer_phone_number'] = trim($customer_phone_number);
        }
        if (!empty($code_contract_disbursement)) {
            $condition['code_contract_disbursement'] = trim($code_contract_disbursement);
        }
        if (!empty($code_contract)) {
            $condition['code_contract'] = trim($code_contract);
        }
        if (!empty($vung_mien)) {
            $pgd = $this->getPgdToVungMien($vung_mien);
            $condition['store_vung'] = $pgd;
        }
        $groupRoles = $this->getGroupRole($this->id);

        $all = false;
        if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles) || in_array('giao-dich-vien', $groupRoles)) {
            $all = true;
        }
        if (!empty($code_contract_disbursement) || !empty($customer_name) || !empty($customer_phone_number)) {
            $all = false;
        }
        if ($all) {
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
        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 1000;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

        $contract = array();
        $contract = $this->contract_model->getContractByTimeExcel(array(), $condition, $per_page, $uriSegment);
        if (!empty($contract)) {
            foreach ($contract as $key => $c) {
                $tempo = $this->contract_tempo_model->find_where(['code_contract' => $c['code_contract'], 'status' => 1]);
                $c['lai_ki'] = $tempo[0];
                $so_ki_thanh_toan = $this->contract_tempo_model->count(['code_contract' => $c['code_contract'], 'status' => 2]);
                $c['so_ki_thanh_toan'] = $so_ki_thanh_toan;
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_all_list_bh_post()
    {
        $this->dataPost = $this->input->post();

        $contract = $this->contract_model->find_one_select(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])), array("_id", "status", "code_contract", "code_contract_disbursement", "created_by", "store", "receiver_infor", "loan_infor", "customer_infor", "current_address", "houseHold_address", "property_infor"));

        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $code_contract_disbursement = $contract['code_contract_disbursement'];
        $list_bh = array();
        $time_GIC_kv = "";
        //gic khoản vay
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '1' && $contract['loan_infor']['amount_GIC'] > 0) {
            $gic = $this->gic_model->findOne(array("code_contract_disbursement" => $code_contract_disbursement));
            if (empty($gic)) {

            } else {
                $data = [
                    'ten_bao_hiem' => 'GIC khoản vay',
                    'trang_thai_bao_hiem' => $gic['status'],
                    'nguoi_cap_nhat' => $gic['updated_by'],
                    'ngay_cap_nhat' => $gic['created_at'],

                ];
                array_push($list_bh, $data);
            }
        }


        $time_MIC_kv = "";
        //mic khoản vay
        if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '2' && $contract['loan_infor']['amount_MIC'] > 0) {
            $type_mic = "MIC_TDCN";
            $mic_ck = $this->mic_model->findOne(array("code_contract_disbursement" => $contract['code_contract_disbursement'], 'type_mic' => $type_mic));

            if (empty($mic_ck)) {

            } else {
                $data = [
                    'ten_bao_hiem' => 'MIC khoản vay',
                    'trang_thai_bao_hiem' => $mic_ck['status'],
                    'nguoi_cap_nhat' => $mic_ck['updated_by'],
                    'ngay_cap_nhat' => $mic_ck['created_at'],

                ];
                array_push($list_bh, $data);
            }
        }


        //end mic khoan vay
        //gic easy
        $time_GIC_easy = "";
        if (isset($contract['loan_infor']['code_GIC_easy']) && isset($contract['loan_infor']['amount_GIC_easy']) && $contract['loan_infor']['amount_GIC_easy'] > 0) {

            $gic_ck_esay = $this->gic_easy_model->findOne(array("code_contract_disbursement" => $contract['code_contract_disbursement']));
            if (empty($gic_ck_esay)) {

            } else {
                $data = [
                    'ten_bao_hiem' => 'GIC EASY',
                    'trang_thai_bao_hiem' => $gic_ck_esay['status'],
                    'nguoi_cap_nhat' => $gic_ck_esay['updated_by'],
                    'ngay_cap_nhat' => $gic_ck_esay['created_at'],

                ];
                array_push($list_bh, $data);
            }
        }


        //end gic easy
        //gic plt
        $time_GIC_plt = "";
        if (isset($contract['loan_infor']['code_GIC_plt']) && isset($contract['loan_infor']['amount_GIC_plt']) && in_array($contract['loan_infor']['code_GIC_plt'], array('COPPER', 'SILVER', 'GOLD'))) {

            $gic_plt = $this->gic_plt_model->findOne(array("code_contract_disbursement" => $contract['code_contract_disbursement']));

            if (empty($gic_plt)) {

            } else {
                $data = [
                    'ten_bao_hiem' => 'GIC Phúc Lộc Thọ',
                    'trang_thai_bao_hiem' => $gic_plt['status'],
                    'nguoi_cap_nhat' => $gic_plt['updated_by'],
                    'ngay_cap_nhat' => $gic_plt['created_at'],

                ];
                array_push($list_bh, $data);
            }
        }


        if (isset($contract['loan_infor']['amount_VBI'])) {
            if ($contract['loan_infor']['amount_VBI'] > 0) {
                $check_vbi = $this->vbi_model->findOne(["code_contract" => $contract['code_contract']]);

                $endDate = strtotime('+1 year', strtotime(date('m/d/Y', $this->createdAt)));

                if (empty($check_vbi)) {

                } else {
                    $data = [
                        'ten_bao_hiem' => 'VBI SXH, UTV',
                        'trang_thai_bao_hiem' => $check_vbi['status_vbi'],
                        'nguoi_cap_nhat' => $check_vbi['updated_by'],
                        'ngay_cap_nhat' => $check_vbi['created_at'],

                    ];
                    array_push($list_bh, $data);
                }
            }
        }
        //bh tnds
        if (!empty($contract['loan_infor']['bao_hiem_tnds'])) {
            if (!empty($contract['loan_infor']['bao_hiem_tnds']['type_tnds'])) {
                $check_tnds = $this->contract_tnds_model->findOne(["code_contract" => $contract['code_contract']]);
                if (empty($check_tnds)) {

                } else {
                    $data = [
                        'ten_bao_hiem' => 'TNDS',
                        'trang_thai_bao_hiem' => $check_tnds['data'],
                        'nguoi_cap_nhat' => $check_tnds['updated_by'],
                        'ngay_cap_nhat' => $check_tnds['created_at'],

                    ];
                    array_push($list_bh, $data);
                }
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $list_bh,
            'count' => count($list_bh),
            'contractInfor' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;

    }

    public function update_by_id_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;


        $data = $this->input->post();
        $id_contract = !empty($data['id_contract']) ? $data['id_contract'] : "";

        $count = $this->contract_model->count(array("_id" => new MongoDB\BSON\ObjectId($id_contract)));
        if ($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if (isset($data['status'])) {
            $data['status'] = (int)$data['status'];
        }
        unset($data['id_contract']);
        unset($data['type']);
        //$data
        $this->contract_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id_contract)),
            $data
        );

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update contract success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function getPgdToVungMien($vung_mien)
    {
        $data = [];
        $stores = $this->store_model->find_where(['code_area' => $vung_mien]);
        if (count($stores) > 0) {
            foreach ($stores as $store) {
                array_push($data, (string)$store['_id']);
            }
        }
        return $data;
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

    public function store_file_manager_post()
    {
        $data = $this->input->post();

        $code_contract_disbursement = !empty($data['code_contract_disbursement_text']) ? $data['code_contract_disbursement_text'] : "";

        $contract = $this->contract_model->findOne(array("code_contract_disbursement" => $code_contract_disbursement[0]));

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function update_coupon_bhkv_post()
    {
        $data = $this->input->post();
        $id_contract=isset($data['id_contract']) ? $data['id_contract'] : '';
        $code_coupon=isset($data['code_coupon_bhkv']) ? $data['code_coupon_bhkv'] : '';
        $image_file=isset($data['image_file']) ? $data['image_file'] : '';
        $approve_note=isset($data['approve_note']) ? $data['approve_note'] : '';
        $contract = $this->contract_model->findOne(['_id' =>new MongoDB\BSON\ObjectId($id_contract)]);
        if (empty($contract)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
        }
        $created_at = !empty($contract['created_at']) ? (int)$contract['created_at'] : $this->createdAt;
        $data_coupon_DB = $this->coupon_bhkv_model->findOne(['code' =>  $code_coupon]);
        if (!empty($data_coupon_DB)) {

            $percent_reduction = 0;
            $percent_reduction=isset($data_coupon_DB['percent_reduction']) ? $data_coupon_DB['percent_reduction'] : 0;
            $amount_bhkv = 0;
            if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '1' && $contract['loan_infor']['amount_GIC'] > 0) {
                $amount_bhkv = (int)$contract['loan_infor']['amount_GIC'];

            }
            if ($contract['loan_infor']['insurrance_contract'] == '1' && $contract['loan_infor']['loan_insurance'] == '2' && $contract['loan_infor']['amount_MIC'] > 0) {
                $amount_bhkv = (int)$contract['loan_infor']['amount_MIC'];

            }
            if ($code_coupon != "" && $percent_reduction > 0 && $amount_bhkv > 0) {
                $tien_giam_tru_bhkv = $amount_bhkv * ($percent_reduction / 100);
                if ($tien_giam_tru_bhkv > 0) {
                    if (!in_array($contract['status'], [11, 12, 13, 14]))
                        $this->contract_model->update(
                            array("_id" => $contract['_id']),
                            [
                                "code_coupon_bhkv" => $code_coupon,
                                "tien_giam_tru_bhkv" => (int)$tien_giam_tru_bhkv,
                                
                                "approve_coupon_bhkv" => [
                                    'image_file'=>(array)$image_file,
                                    'created_by'=>$this->uemail,
                                    'created_at'=>$this->createdAt,
                                    'approve_note'=>$approve_note,
                                     ],
                            ]
                        );
                }
            } 
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công"
            );
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại mã giảm giá",
                'data'=>$code_coupon
            );
        }
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function push_notification_request_change_field($id_users, $status, $contract, $note)
    {
        foreach ($id_users as $id_user) {
            if (!empty($id_user)) {
            $data_notification = [
                'action_id' => (string)$contract['_id'],
                'action' => 'request_to_field',
                'detail' => "DebtCall/list_contract_debt_to_field?code_contract_disbursement=".$contract['code_contract_disbursement'],
                'title' => $contract['customer_name'] . ' - ' . $contract['store_name'],
                'note' => $note,
                'user_id' => $id_user,
                'status' => 1, //1: new, 2 : read, 3: block,
                'status_contract' => $status,
                'type_notification' => 1, //1: thông báo miễn giảm,
                'created_at' => $this->createdAt,
                "created_by" => $this->uemail
            ];
            $code_contract_disbursement = $contract['code_contract_disbursement'];
            $customer_name = $contract['customer_name'];

            $this->notification_model->insertReturnId($data_notification);
            $device = $this->device_model->find_where(['user_id' => $id_user]);

            if (!empty($device) && $id_user == $device[0]['user_id']) {
                $fcm = new Fcm();
                $to = [];
                foreach ($device as $de) {
                    $to[] = $de->device_token;
                }
                $badge = $this->get_count_notification($id_user);
                $click_action = 'http://localhost/tienngay/cpanel.tienngay/DebtCall/list_contract_debt_to_field?code_contract_disbursement='.$contract['code_contract_disbursement'];
//              $click_action = 'https://sandboxcpanel.tienngay.vn/DebtCall/list_contract_call?code_contract_disbursement='.$contract['code_contract_disbursement'];
//              $click_action = 'https://cpanel.tienngay.vn/DebtCall/list_contract_call?code_contract_disbursement='.$contract['code_contract_disbursement'];

                $fcm->setTitle('Yêu cầu chuyển hợp đồng sang Field!');
                $fcm->setMessage("HĐ: $code_contract_disbursement, KH: $customer_name");
                $fcm->setClickAction($click_action);
                $fcm->setBadge($badge);
                $message = $fcm->getMessage();
                $result = $fcm->sendToTopicCpanel($to, $message, $message);
                }
            }
        }
    }

    private function get_count_notification($user_id)
    {
        $condition = [];
        $condition['user_id'] = (string)$user_id;
        $condition['type_notification'] = 1;
        $condition['status'] = 1;
        $unRead = $this->notification_model->get_count_notification_user($condition);
        return $unRead;
    }

    private function get_id_tp_thn_mb()
    {
        $data_role = $this->role_model->findOne(['slug' => 'tbp-thn-mien-bac']);
        $array_user_id = [];
        if (!empty($data_role)) {
            foreach ($data_role['users'] as $key => $role) {
                foreach ($role as $key1 => $item) {
                    array_push($array_user_id, $key1);
                }
            }
        }
        return $array_user_id;
    }

    private function get_id_tp_thn_mn()
    {
        $data_role = $this->role_model->findOne(['slug' => 'tbp-thn-mien-nam']);
        $array_user_id = [];
        if (!empty($data_role)) {
            foreach ($data_role['users'] as $key => $role) {
                foreach ($role as $key1 => $item) {
                    array_push($array_user_id, $key1);
                }
            }
        }
        return $array_user_id;
    }

    public function find_contract_by_customer_info_post()
    {
        $data = $this->input->post();
        if(empty($data["customer_info"])) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => []
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $fieldValue = trim($data["customer_info"]);
        $conditions = [
            ["customer_infor.customer_phone_number" => ['$eq' => "$fieldValue"]],
            ["customer_infor.customer_identify" => ['$eq' => "$fieldValue"]],
            ["customer_infor.customer_identify_old" => ['$eq' => "$fieldValue"]],
            ["customer_infor.passport_number" => ['$eq' => "$fieldValue"]],
        ];
        $selectOption = [
            "_id",
            "customer_infor.customer_name",
            "customer_infor.customer_email",
            "customer_infor.customer_phone_number",
            "customer_infor.customer_identify",
            "customer_infor.customer_identify_old",
            "code_contract_disbursement",
            "code_contract",
            "customer_infor.passport_number",
            "status",
        ];
        $dataRes = $this->contract_model->find_where_or($conditions, $selectOption);
        if (empty($dataRes)) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => [],
                'customerInfo' => $customerInfo,
                'conditions' => $conditions
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        foreach ($dataRes as  $value) {
            $condition = array(
                'code_contract' => $value['code_contract']
            );
            $termInfo = $this->contract_tempo_model->getAll($condition);
            $value["data"] = $termInfo;
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $dataRes
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function find_contract_by_contract_info_post()
    {
        $data = $this->input->post();
        if(empty($data["contract_info"])) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => []
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $fieldValue = trim($data["contract_info"]);
        $conditions = [
            ["code_contract_disbursement" => ['$eq' => "$fieldValue"]],
            ["code_contract" => ['$eq' => "$fieldValue"]],
            
        ];
        $selectOption = [
            "_id",
            "customer_infor.customer_name",
            "customer_infor.customer_email",
            "customer_infor.customer_phone_number",
            "customer_infor.customer_identify",
            "customer_infor.customer_identify_old",
            "code_contract_disbursement",
            "code_contract",
            "customer_infor.passport_number",
            "status",
        ];
        $dataRes = $this->contract_model->find_where_or($conditions, $selectOption);
        if (empty($dataRes)) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => [],
                'customerInfo' => $customerInfo,
                'conditions' => $conditions
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        foreach ($dataRes as  $value) {
            $condition = array(
                'code_contract' => $value['code_contract']
            );
            $termInfo = $this->contract_tempo_model->getAll($condition);
            $value["data"] = $termInfo;
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $dataRes
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function sendEmail($dataPost)
    {
        $email_template = $this->email_template_model->findOne(array('code' => $dataPost['code'], 'status' => 'active'));

        $domain = $this->config->item('sendgrid_domain');
        // var_dump($email_template); die;

        $from = 'support@tienngay.vn';
        $from_name = $email_template['from_name'];
        $subject = $email_template['subject'];
        $message = $this->getEmailStr($email_template['message'], $dataPost);
        $status = 'active';
        $data = array(
            "code" => $dataPost['code'],
            "from" => $from,
            "from_name" => $from_name,
            "to" => $dataPost['email'],
            "subject" => $subject,
            "email_domain" => $domain,
            "status" => $status,
            "message" => $message,
//          "device" => $this->agent->browser() . ';' . $this->agent->platform(),
//          "ipaddress" => getIpAddress(),
            "created_at" => (int)$this->createdAt
        );

        //var_dump('expression');

        $this->email_history_model->insert($data);
        return;


    }

    public function getEmailStr($emailTemplate, $filter)
    {
        foreach ($filter as $key => $value) {
            $emailTemplate = str_replace("{" . $key . "}", $value, $emailTemplate);
        }
        return $emailTemplate;
    }

    public function exportContract_bucket_post(){

        $this->dataPost = $this->input->post();
        $condition = [];

        $store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";

        $start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
        $end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

        if (!empty($start) && !empty($end)) {
            $condition['start'] = strtotime(trim($start) . ' 00:00:00');
            $condition['end'] = strtotime(trim($end) . ' 23:59:59');
        }

        if (!empty($store)){
            $condition['store'] = $store;

            $list_user = $this->getUserbyStores_name($store);

            if(!empty($list_user)){
                $condition['follow_contract'] = $list_user;
            }

        }

        $per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
        $uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

        $contract = $this->contract_model->export_xadan($condition,$per_page , $uriSegment);

        if (!empty($start) && !empty($end)) {
            $condition_lead = array(
                '$gte' => strtotime(trim($start) . ' 00:00:00'),
                '$lte' => strtotime(trim($end) . ' 23:59:59'),
            );
        }
        $contract1 = new Contract_model();

        $stores = [];
        array_push($stores, $store);

        $total_du_no_dang_cho_vay_contract = $contract1->sum_where_total(['store.id'=>array('$in'=> $stores),'disbursement_date'=> $condition_lead,'status' => array('$in' => list_array_trang_thai_dang_vay())],'$debt.tong_tien_goc_con');
        $total_du_no_dang_cho_vay_pgd_follow = $contract1->sum_where_total(['follow_contract'=> ['$in' => $list_user] ,'disbursement_date'=> $condition_lead,'status' => array('$in' => list_array_trang_thai_dang_vay())],'$debt.tong_tien_goc_con');

        $total_du_no_qua_han_t10_contract = $contract1->sum_where_total(['store.id'=>array('$in'=> $stores),'status' => array('$in' => list_array_trang_thai_dang_vay()),'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_lead],'$debt.tong_tien_goc_con');
        $total_du_no_qua_han_t10_pgd_follow = $contract1->sum_where_total(['follow_contract'=> ['$in' => $list_user],'status' => array('$in' => list_array_trang_thai_dang_vay()),'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_lead],'$debt.tong_tien_goc_con');

        $total_du_no_trong_t10_contract = $contract1->sum_where_total(['store.id'=>array('$in'=> $stores),'status' => array('$in' => list_array_trang_thai_dang_vay()),'debt.so_ngay_cham_tra'=>[ '$lt' => 10],'disbursement_date'=>$condition_lead],'$debt.tong_tien_goc_con');
        $total_du_no_trong_t10_pgd_follow = $contract1->sum_where_total(['follow_contract'=> ['$in' => $list_user],'status' => array('$in' => list_array_trang_thai_dang_vay()),'debt.so_ngay_cham_tra'=>['$lt' => 10],'disbursement_date'=>$condition_lead],'$debt.tong_tien_goc_con');


        $total_du_no_dang_cho_vay = $total_du_no_dang_cho_vay_contract + $total_du_no_dang_cho_vay_pgd_follow;
        $total_du_no_qua_han_t10 = $total_du_no_qua_han_t10_contract + $total_du_no_qua_han_t10_pgd_follow;
        $total_du_no_trong_t10 = $total_du_no_trong_t10_contract + $total_du_no_trong_t10_pgd_follow;
        if (empty($start)){

            $total_du_no_dang_cho_vay_contract = $contract1->sum_where_total(['store.id'=>array('$in'=> $stores),'status' => array('$in' => list_array_trang_thai_dang_vay())],'$debt.tong_tien_goc_con');
            $total_du_no_dang_cho_vay_pgd_follow = $contract1->sum_where_total(['follow_contract'=> ['$in' => $list_user] ,'disbursement_date'=> $condition_lead,'status' => array('$in' => list_array_trang_thai_dang_vay())],'$debt.tong_tien_goc_con');

            $total_du_no_qua_han_t10_contract = $contract1->sum_where_total(['store.id'=>array('$in'=> $stores),'status' => array('$in' => list_array_trang_thai_dang_vay()),'debt.so_ngay_cham_tra'=>['$gte'=>10]],'$debt.tong_tien_goc_con');
            $total_du_no_qua_han_t10_pgd_follow = $contract1->sum_where_total(['follow_contract'=> ['$in' => $list_user],'status' => array('$in' => list_array_trang_thai_dang_vay()),'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_lead],'$debt.tong_tien_goc_con');

            $total_du_no_trong_t10_contract = $contract1->sum_where_total(['store.id'=>array('$in'=> $stores),'status' => array('$in' => list_array_trang_thai_dang_vay()),'debt.so_ngay_cham_tra'=>['$lt' => 10],'disbursement_date'=>$condition_lead],'$debt.tong_tien_goc_con');
            $total_du_no_trong_t10_pgd_follow = $contract1->sum_where_total(['follow_contract'=> ['$in' => $list_user],'status' => array('$in' => list_array_trang_thai_dang_vay()),'debt.so_ngay_cham_tra'=>['$lt' => 10],'disbursement_date'=>$condition_lead],'$debt.tong_tien_goc_con');


            $total_du_no_dang_cho_vay = $total_du_no_dang_cho_vay_contract + $total_du_no_dang_cho_vay_pgd_follow;
            $total_du_no_qua_han_t10 = $total_du_no_qua_han_t10_contract + $total_du_no_qua_han_t10_pgd_follow;
            $total_du_no_trong_t10 = $total_du_no_trong_t10_contract + $total_du_no_trong_t10_pgd_follow;
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'total_du_no_dang_cho_vay' => !empty($total_du_no_dang_cho_vay) ? $total_du_no_dang_cho_vay : 0,
            'total_du_no_qua_han_t10' => !empty($total_du_no_qua_han_t10) ? $total_du_no_qua_han_t10 : 0,
            'total_du_no_trong_t10' => !empty($total_du_no_trong_t10) ? $total_du_no_trong_t10 : 0
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;

    }

    public function exportContract_bucket_count_post(){

        $this->dataPost = $this->input->post();
        $condition = [];

        $store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";

        $start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
        $end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

        if (!empty($start) && !empty($end)) {
            $condition['start'] = strtotime(trim($start) . ' 00:00:00');
            $condition['end'] = strtotime(trim($end) . ' 23:59:59');
        }

        if (!empty($store)){
            $condition['store'] = $store;

            $list_user = $this->getUserbyStores_name($store);

            if(!empty($list_user)){
                $condition['follow_contract'] = $list_user;
            }
        }

        $count = $this->contract_model->export_xadan_count($condition);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => !empty($count) ? $count : 0
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;

    }

    private function getUserbyStores_name($store_id)
    {
        $roles = $this->role_model->find_where(['status' => 'active']);
        $data = [];
        $i = 0;
        foreach ($roles as $key => $role) {
            if (!empty($role['users']) && !empty($role['stores'])) {
                $data[$i]['users'] = $role['users'];
                $data[$i]['stores'] = $role['stores'];
                $i++;
            }
        }
        foreach ($data as $da) {
            foreach ($da['stores'] as $d) {
                $storeId = [];
                foreach ($d as $k => $v) {
                    array_push($storeId, $k);
                }
                if (in_array($store_id, $storeId) == true) {
                    if (count($da['stores']) > 1) {
                        continue;
                    }
                    $user_id = [];
                    foreach ($da['users'] as $ds) {
                        foreach ($ds as $k => $v) {
                            array_push($user_id, $v->email);
                        }
                    }
                }
            }
        }
        return $user_id;
    }

    public function exportContract_bucket_excel_post(){

        $this->dataPost = $this->input->post();
        $condition = [];

        $store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";

        $start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
        $end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

        if (!empty($start) && !empty($end)) {
            $condition['start'] = strtotime(trim($start) . ' 00:00:00');
            $condition['end'] = strtotime(trim($end) . ' 23:59:59');
        }

        if (!empty($store)){
            $condition['store'] = $store;

            $list_user = $this->getUserbyStores_name($store);

            if(!empty($list_user)){
                $condition['follow_contract'] = $list_user;
            }

        }

        $contract = $this->contract_model->export_xadan_excel($condition);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;


    }

    public function quan_ly_ho_so_mb()
    {
        $roles = $this->role_model->find_where(['status' => 'active']);
        $data = [];
        foreach ($roles as $key => $role) {
            if (!empty($role['users']) && ($role['slug'] == "qlhs-mien-bac")) {
                foreach ($role['users'] as $key1 => $user) {
                    foreach ($user as $key2 => $item) {
                        array_push($data, $key2);

                    }
                }
            }
        }
        return $data;
    }

    public function quan_ly_ho_so_mn()
    {
        $roles = $this->role_model->find_where(['status' => 'active']);
        $data = [];

        foreach ($roles as $key => $role) {
            if (!empty($role['users']) && ($role['slug'] == "qlhs-mien-nam")) {
                foreach ($role['users'] as $key1 => $user) {

                    foreach ($user as $key2 => $item) {

                        array_push($data, $key2);

                    }
                }
            }
        }
        return $data;
    }

    public function quan_ly_ho_so_mekong()
    {
        $roles = $this->role_model->find_where(['status' => 'active']);
        $data = [];

        foreach ($roles as $key => $role) {
            if (!empty($role['users']) && ($role['slug'] == "qlhs-mekong")) {
                foreach ($role['users'] as $key1 => $user) {

                    foreach ($user as $key2 => $item) {

                        array_push($data, $key2);

                    }
                }
            }
        }
        return $data;
    }

    private function sendEmailApprove_qlhs($fileReturn, $user_qlhs)
    {
        $status_text = "";
        $id = $fileReturn['_id'];

        if ($fileReturn['status'] == "1") {
            $status_text = "Mới";
        } elseif ($fileReturn['status'] == "2") {
            $status_text = "Hủy yêu cầu";
        } elseif ($fileReturn['status'] == "3") {
            $status_text = "YC gửi HS giải ngân";
        } elseif ($fileReturn['status'] == "4") {
            $status_text = "QLHS YC bổ sung";
        } elseif ($fileReturn['status'] == "5") {
            $status_text = "Đã XN YC gửi HS";
        } elseif ($fileReturn['status'] == "6") {
            $status_text = "Hoàn tất lưu kho";
        } elseif ($fileReturn['status'] == "7") {
            $status_text = "QLHS chưa nhận HS";
        } elseif ($fileReturn['status'] == "8") {
            $status_text = "YC trả HS sau tất toán";
        } elseif ($fileReturn['status'] == "9") {
            $status_text = "QLHS đã xác nhận YC trả HS";
        } elseif ($fileReturn['status'] == "10") {
            $status_text = "YC bổ sung HS";
        } elseif ($fileReturn['status'] == "11") {
            $status_text = "Đã trả HS sau tất toán";
        } elseif ($fileReturn['status'] == "13") {
            $status_text = "Trả về yêu cầu";
        }
        $data = array(
            'code' => "vfc_send_email_qlhs",
            'code_contract_disbursement' => $fileReturn['code_contract_disbursement_text'],
            'status' => $status_text,
            'url' => "https://cpanel.tienngay.vn/file_manager/detail?id=$id"
        );

        foreach ($user_qlhs as $item) {
            $email_user = $this->getGroupRole_email($item);
            foreach ($email_user as $value) {
                $data['email'] = "$value";
                $data['API_KEY'] = $this->config->item('API_KEY');
                $this->user_model->send_Email($data);
//              $this->sendEmail($data);
            }

        }
        return;
    }

    private function getGroupRole_email($userId)
    {
        $groupRoles = $this->group_role_model->find_where(array("status" => "active"));
        $arr = array();
        foreach ($groupRoles as $groupRole) {
            if (empty($groupRole['users'])) continue;
            foreach ($groupRole['users'] as $item) {
                if (key($item) == $userId) {
                    array_push($arr, $item[key($item)]['email']);
                    continue;
                }
            }
        }
        return array_unique($arr);
    }

    public function update_status_contract_event_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $contractOne = $this->contract_model->find_one_select(['_id' => new \MongoDB\BSON\ObjectId($data['contract_id'])], ['_id', 'status', 'code_contract', 'code_contract_disbursement']);
        $array_update = [
            'status' => (int)$data['status_contract'],
            'updated_at_change' => $this->createdAt,
            'updated_by_change' => $this->uemail
        ];
        if (!empty($contractOne)) {
            $this->contract_model->update(
                ['_id' => $contractOne['_id']],
                $array_update
            );
        }
        $array_update['note'] = "Tool Fix trạng thái HĐ theo YC IT Support PGD thay vì sửa db!";
        $log_ksnb = array(
            "type" => "contract_ksnb",
            "action" => "update",
            "contract_id" => (string)$data['contract_id'],
            "old" => $contractOne,
            "new" => $array_update,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_ksnb_model->insert($log_ksnb);

        $response = [
            'status' => REST_Controller::HTTP_OK,
            'message' => "Cập nhật thành công!"
        ];
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function check_status_mhd_post(){

        $code_contract_disbursement = $this->security->xss_clean($this->dataPost['code_contract_disbursement_text']);

        $contract = $this->contract_model->findOne(array("code_contract_disbursement" => $code_contract_disbursement));

        if (empty($contract)){
            return;
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;

    }

    public function update_customer_resource_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $contractOne = $this->contract_model->find_one_select(['_id' => new \MongoDB\BSON\ObjectId($data['id_contract'])], ['_id', 'customer_infor', 'code_contract', 'code_contract_disbursement']);
        $array_update = [
            'customer_infor.customer_resources' => $data['customer_resources'],
            'updated_at_change' => $this->createdAt,
            'updated_by_change' => $this->uemail
        ];
        if (!empty($contractOne)) {
            $this->contract_model->update(
                ['_id' => $contractOne['_id']],
                $array_update
            );
        }
        $log_new = [
            'customer_resource' => $data['customer_resources'],
            'note' => $data['note_change_source'],
            'code_contract_disbursement' => $contractOne['code_contract_disbursement']
        ];
        $log_ksnb = array(
            "type" => "fix_db",
            "action" => "update",
            "contract_id" => (string)$data['id_contract'],
            "old" => $contractOne,
            "new" => $log_new,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_ksnb_model->insert($log_ksnb);
        $response = [
            'status' => REST_Controller::HTTP_OK,
            'message' => "Cập nhật thành công!"
        ];
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_log_change_source_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
        $log = $this->log_ksnb_model->getLogsChangeSource(array("contract_id" => $this->dataPost['contract_id']));
        if(empty($log)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Log is not exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $log
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }


    private function api_dinos($click_id, $status){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.dinos.vn/api/v1/post_back_campaign_redirect?click_id=$click_id&status=$status",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

    }

    public function check_store_next_pay($storeId)
    {
        $role = $this->role_model->findOne(['slug' => 'doi-tac-nextpay']);
        $store_np = [];
        foreach ($role['stores'] as $st) {
            foreach ($st as $k => $v) {
                array_push($store_np, $k);
            }
        }
        if (in_array($storeId, $store_np)) {
            return true;
        } else {
            return false;
        }
    }

    public function leader_telesales()
    {
        $roles = $this->role_model->find_where(['status' => 'active']);
        $data = [];
        foreach ($roles as $key => $role) {
            if (!empty($role['users']) && ($role['slug'] == "leader-telesales")) {
                foreach ($role['users'] as $key1 => $user) {
                    foreach ($user as $key2 => $item) {
                        array_push($data, $key2);

                    }
                }
            }
        }
        return $data;
    }
    public function topup()
    {
        $roles = $this->role_model->find_where(['status' => 'active']);
        $data = [];
        foreach ($roles as $key => $role) {
            if (!empty($role['users']) && ($role['slug'] == "topup")) {
                foreach ($role['users'] as $key1 => $user) {
                    foreach ($user as $key2 => $item) {
                        array_push($data, $key2);

                    }
                }
            }
        }
        return $data;
    }

    /**
     * update field code_contract_disbursement in table contract and temporary_plan_contract
     * @param string | $id_contract
     * @param string | $code_contract ma phieu ghi
     * @param string | $new_code_contract_disbursement ma hop dong
     * @param string | $note_edit_code_contract_disbursement ghi chu
     * @return array | message response
     */
    public function edit_code_contract_d_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag ==  false) return;
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $id_contract = $this->security->xss_clean($this->dataPost['id_contract']);
        $code_contract = $this->security->xss_clean($this->dataPost['code_contract']);
        $new_code_contract_disbursement = $this->security->xss_clean(trim($this->dataPost['new_code_contract_disbursement']));
        $note_edit_code_contract_disbursement = $this->security->xss_clean($this->dataPost['note_edit_code_contract_disbursement']);

        $contractDBById = $this->contract_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_contract)]);
        if (empty($contractDBById)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'ID hợp đồng không tồn tại!'
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $contractData = $this->contract_model->findOne(['code_contract_disbursement' => trim($new_code_contract_disbursement)]);
        if (!empty($contractData)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Mã hợp đồng đã tồn tại!'
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        // update mã HĐ code_contract_disbursement mới
        $this->contract_model->update(
            [
                "_id" => new MongoDB\BSON\ObjectId($contractDBById['_id'])
            ],[
                "code_contract_disbursement" => trim($new_code_contract_disbursement)
            ]
        );

        // update mã HĐ code_contract_disbursement trong bảng lãi kỳ
        $temporaryData = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract]);
        if (!empty($temporaryData)) {
            foreach ($temporaryData as $temporaryDatum) {
                $this->temporary_plan_contract_model->update(
                    [
                        "_id" => new \MongoDB\BSON\ObjectId($temporaryDatum['_id'])
                    ],[
                        "code_contract_disbursement" => trim($new_code_contract_disbursement),
                        "node_edit_code_contract_d" => "yes_". date("d/m/Y H:i:s",$this->createdAt) // 1: update
                    ]
                );
            }
        }
        $log_new = array(
            "note" => $note_edit_code_contract_disbursement . " \"codeContractPrevious: " . $contractDBById['code_contract_disbursement'] . "\"",
            "code_contract_disbursement" => trim($new_code_contract_disbursement)
        );

        $insertLog = array(
            "type" => "contract",
            "action" => "update_code_contract_disbursement",
            "contract_id" => (string)$contractDBById['_id'],
            "old" => $contractDBById['code_contract_disbursement'],
            "new" => $log_new,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($insertLog);
        $this->log_ksnb_model->insert($insertLog);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Cập nhật mã hợp đồng thành công!'
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

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
            $send_email = true;
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

    public function cron_create_file_megadoc_post()
    {
//       $flag = notify_token($this->flag_login);
//       if ($flag == false) return;
        $data = $this->input->post();
        $code_contract = $data['code_contract'];
        $status = $data['status'];
        $document_type = $data['document_type'];
        $contractInfo = $this->contract_model->findOne(['code_contract' => $code_contract]);
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
        // Tạo file docx contract
        if ($document_type == "docx") {
            $create_docx_file = $this->create_contract_docx_file($contractInfo);
            echo "Created a DOCX file!";
        } elseif ($document_type == "pdf") {
            $convert_pdf = $this->execute_convert_docx($conditon_check_file);
            echo "Created a PDF file!";
        }
        echo "Created file!";
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
                if ($data['status_approve'] == 6) {
                    // => Tạo file docx thechap_tcv_template
                    if (file_exists('assets/file/file_megadoc/thechap_tcv_template.docx')) {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/thechap_tcv_template.docx');
                        $templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
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
                        $templateProcessor->setValue('bank_account', $data['receiver_infor']['bank_account'] ?? '');
                        $templateProcessor->setValue('bank_branch', $data['receiver_infor']['bank_branch'] ?? '');
                        $templateProcessor->setValue('bank_name', $bank_name_nganluong ?? '');
                        $templateProcessor->setValue('store_representative', $store['representative'] ?? '');
                        $templateProcessor->setValue('amount_money', number_format($data['loan_infor']['amount_money']) ?? '');
                        $templateProcessor->setValue('amount_loan', number_format($data['loan_infor']['amount_loan']) ?? '');
                        $templateProcessor->setValue('number_day_loan', $data['loan_infor']['number_day_loan'] / 30 ?? '');
                        $templateProcessor->setValue('type_interest', $type_interest ?? '');
                        $templateProcessor->setValue('chi_phi_vay_thang', $chiphivaythang ?? '');
                        $templateProcessor->setValue('percent_interest', $data['fee']['percent_interest_customer'] ?? '');
                        $templateProcessor->setValue('advisory_fee', $data['fee']['percent_advisory'] ?? '');
                        $templateProcessor->setValue('another_fee', '');
                        $templateProcessor->setValue('tai_khoan_dinh_danh', $data['vpbank_van']['van'] ?? '');
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

                        if ($is_bh_tnnv_gic_mic == 1) {
                            $templateProcessor->setImageValue('check_gic_mic', array(
                                'path' => 'assets/file/file_megadoc/checked.png',
                                'width' => $with_img_checkbox
                            ));
                        } else {
                            $templateProcessor->setImageValue('check_gic_mic', array(
                                'path' => 'assets/file/file_megadoc/uncheck.png',
                                'width' => $with_img_checkbox
                            ));
                        }
                        if ($is_bh_pti_vta == 1) {
                            $templateProcessor->setImageValue('check_pti', array(
                                'path' => 'assets/file/file_megadoc/checked.png',
                                'width' => $with_img_checkbox
                            ));
                        } else {
                            $templateProcessor->setImageValue('check_pti', array(
                                'path' => 'assets/file/file_megadoc/uncheck.png',
                                'width' => $with_img_checkbox
                            ));
                        }
                        $templateProcessor->saveAs(APPPATH . '/file_megadoc/thechap_tcv_' . $code_contract . '.docx');
                    }
                } elseif ($data['status_approve'] == 15) {
                    // => Tạo file docx bbbg_thechap_tcv_template
                    if (file_exists('assets/file/file_megadoc/bbbg_thechap_tcv_template.docx')) {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/bbbg_thechap_tcv_template.docx');
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
                        $templateProcessor->saveAs(APPPATH . '/file_megadoc/bbbg_thechap_tcv_' . $code_contract . '.docx');
                    }
                } elseif ($data['status_approve'] == 19) {
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
                }
                return true;
            } elseif (($data['loan_infor']['type_loan']['code'] == 'CC' || $data['loan_infor']['type_loan']['code'] == 'DKX') && $data['loan_infor']['type_property']['code'] != "TC") {
                if ($data['status_approve'] == 6) {
                    // tạo file docx thỏa thuận ba bên chovay_tcv_template
                    if (file_exists('assets/file/file_megadoc/chovay_tcv_template.docx')) {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/chovay_tcv_template.docx');
                        $templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
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
                        $templateProcessor->setValue('bank_account', $data['receiver_infor']['bank_account'] ?? '');
                        $templateProcessor->setValue('bank_branch', $data['receiver_infor']['bank_branch'] ?? '');
                        $templateProcessor->setValue('bank_name', $bank_name_nganluong ?? '');
                        $templateProcessor->setValue('store_representative', $store['representative'] ?? '');
                        $templateProcessor->setValue('amount_money', number_format($data['loan_infor']['amount_money']) ?? '');
                        $templateProcessor->setValue('amount_loan', number_format($data['loan_infor']['amount_loan']) ?? '');
                        $templateProcessor->setValue('number_day_loan', $data['loan_infor']['number_day_loan'] / 30 ?? '');
                        $templateProcessor->setValue('type_interest', $type_interest ?? '');
                        $templateProcessor->setValue('chi_phi_vay_thang', $chiphivaythang ?? '');
                        $templateProcessor->setValue('percent_interest', $data['fee']['percent_interest_customer'] ?? '');
                        $templateProcessor->setValue('advisory_fee', $data['fee']['percent_advisory'] ?? '');
                        $templateProcessor->setValue('another_fee', '');
                        $templateProcessor->setValue('tai_khoan_dinh_danh', $data['vpbank_van']['van'] ?? '');
                        $templateProcessor->setValue('type_property', $data['loan_infor']['name_property']['text'] ?? '');
                        $templateProcessor->setValue('so_khung', $sokhung ?? '');
                        $templateProcessor->setValue('bks', $bienkiemsoat ?? '');
                        $templateProcessor->setValue('somay', $somay ?? '');
                        $templateProcessor->setValue('ngaydangky', $ngaycapdangkyxe);
                        $templateProcessor->setValue('sodangky', $sodangky ?? '');

                        if ($is_bh_tnnv_gic_mic == 1) {
                            $templateProcessor->setImageValue('check_gic_mic', array(
                                'path' => 'assets/file/file_megadoc/checked.png',
                                'width' => $with_img_checkbox
                            ));
                        } else {
                            $templateProcessor->setImageValue('check_gic_mic', array(
                                'path' => 'assets/file/file_megadoc/uncheck.png',
                                'width' => $with_img_checkbox
                            ));
                        }
                        if ($is_bh_pti_vta == 1) {
                            $templateProcessor->setImageValue('check_pti', array(
                                'path' => 'assets/file/file_megadoc/checked.png',
                                'width' => $with_img_checkbox
                            ));
                        } else {
                            $templateProcessor->setImageValue('check_pti', array(
                                'path' => 'assets/file/file_megadoc/uncheck.png',
                                'width' => $with_img_checkbox
                            ));
                        }
                        if ($gic_easy_20 == 1 || $gic_easy_40 == 1 || $gic_easy_70 == 1) {
                            $templateProcessor->setImageValue('check_easy', array(
                                'path' => 'assets/file/file_megadoc/checked.png',
                                'width' => $with_img_checkbox
                            ));
                            $templateProcessor->setValue('goi_gic', $goi_gic ?? '');
                        } else {
                            $templateProcessor->setImageValue('check_easy', array(
                                'path' => 'assets/file/file_megadoc/uncheck.png',
                                'width' => $with_img_checkbox
                            ));
                            $templateProcessor->setValue('goi_gic', $goi_gic ?? '');
                        }

                        $templateProcessor->saveAs(APPPATH . '/file_megadoc/chovay_tcv_' . $code_contract . '.docx');
                    }
                } elseif ($data['status_approve'] == 15) {
                    // bbbg_chovay_tcv_template
                    if (file_exists('assets/file/file_megadoc/bbbg_chovay_tcv_template.docx')) {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/bbbg_chovay_tcv_template.docx');
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
                        $templateProcessor->setValue('current_time', $current_time ?? '');
                        $templateProcessor->setValue('current_hours', $current_hours ?? '');
                        $templateProcessor->setValue('current_minutes', $current_minutes ?? '');
                        $templateProcessor->setValue('date_sign_ttbb', $date_sign_ttbb ?? '');
                        $templateProcessor->saveAs(APPPATH . '/file_megadoc/bbbg_chovay_tcv_' . $code_contract . '.docx');
                    }
                } elseif ($data['status_approve'] == 16) {
                    // bbbg_chovay_tcv_tl_template
                    if (file_exists('assets/file/file_megadoc/thongbao_tcv_template.docx')) {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/thongbao_tcv_template.docx');
                        $templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
                        $templateProcessor->setValue('disbursement_date', $disbursement_date ?? '');
                        $templateProcessor->setValue('day', $day ?? '');
                        $templateProcessor->setValue('mon', $mon ?? '');
                        $templateProcessor->setValue('year', $year ?? '');
                        $templateProcessor->setValue('bks', $bienkiemsoat ?? '');
                        $templateProcessor->setValue('so_khung', $sokhung ?? '');
                        $templateProcessor->setValue('somay', $somay ?? '');
                        $templateProcessor->setValue('ngaydangky', $ngaycapdangkyxe);
                        $templateProcessor->setValue('sodangky', $sodangky ?? '');
                        $templateProcessor->setValue('nhan_hieu', $nhanhieu ?? '');
                        $templateProcessor->setValue('model', $model ?? '');
                        $templateProcessor->setValue('chuxe', $chuxe ?? '');
                        $templateProcessor->saveAs(APPPATH . '/file_megadoc/thongbao_tcv_' . $code_contract . '.docx');
                    }
                } elseif ($data['status_approve'] == 19) {
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
                }
                return true;
            }
        } elseif ($company_code == "TCVĐB") {
            if ($data['loan_infor']['type_property']['code'] == 'NĐ') {
                    if ($data['status_approve'] == 6) {
                        // tạo file docx thỏa thuận ba bên thechap_tcvdb_template
                        if (file_exists('assets/file/file_megadoc/thechap_tcvdb_template.docx')) {
                            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/thechap_tcvdb_template.docx');
                            $templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
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
                            $templateProcessor->setValue('bank_account', $data['receiver_infor']['bank_account'] ?? '');
                            $templateProcessor->setValue('bank_branch', $data['receiver_infor']['bank_branch'] ?? '');
                            $templateProcessor->setValue('bank_name', $bank_name_nganluong ?? '');
                            $templateProcessor->setValue('store_representative', $store['representative'] ?? '');
                            $templateProcessor->setValue('amount_money', number_format($data['loan_infor']['amount_money']) ?? '');
                            $templateProcessor->setValue('amount_loan', number_format($data['loan_infor']['amount_loan']) ?? '');
                            $templateProcessor->setValue('number_day_loan', $data['loan_infor']['number_day_loan'] / 30 ?? '');
                            $templateProcessor->setValue('type_interest', $type_interest ?? '');
                            $templateProcessor->setValue('chi_phi_vay_thang', $chiphivaythang ?? '');
                            $templateProcessor->setValue('percent_interest', $data['fee']['percent_interest_customer'] ?? '');
                            $templateProcessor->setValue('advisory_fee', $data['fee']['percent_advisory'] ?? '');
                            $templateProcessor->setValue('another_fee', '');
                            $templateProcessor->setValue('tai_khoan_dinh_danh', $data['vpbank_van']['van'] ?? '');
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
                            $templateProcessor->setValue('current_time', $current_time ?? '');

                            if ($is_bh_tnnv_gic_mic == 1) {
                                $templateProcessor->setImageValue('check_gic_mic', array(
                                    'path' => 'assets/file/file_megadoc/checked.png',
                                    'width' => $with_img_checkbox
                                ));
                            } else {
                                $templateProcessor->setImageValue('check_gic_mic', array(
                                    'path' => 'assets/file/file_megadoc/uncheck.png',
                                    'width' => $with_img_checkbox
                                ));
                            }
                            if ($is_bh_pti_vta == 1) {
                                $templateProcessor->setImageValue('check_pti', array(
                                    'path' => 'assets/file/file_megadoc/checked.png',
                                    'width' => $with_img_checkbox
                                ));
                            } else {
                                $templateProcessor->setImageValue('check_pti', array(
                                    'path' => 'assets/file/file_megadoc/uncheck.png',
                                    'width' => $with_img_checkbox
                                ));
                            }
                            $templateProcessor->saveAs(APPPATH . '/file_megadoc/thechap_tcvdb_' . $code_contract . '.docx');
                        }
                    } elseif ($data['status_approve'] == 15) {
                        // => Tạo file docx bbbg_thechap_tcvdb_template
                        if (file_exists('assets/file/file_megadoc/bbbg_thechap_tcvdb_template.docx')) {
                            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/bbbg_thechap_tcvdb_template.docx');
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
                            $templateProcessor->saveAs(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_' . $code_contract . '.docx');
                        }
                    } elseif ($data['status_approve'] == 19) {
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
                    }
                return true;
            } elseif (($data['loan_infor']['type_loan']['code'] == 'CC' || $data['loan_infor']['type_loan']['code'] == 'DKX') && $data['loan_infor']['type_property']['code'] != "TC") {
                if ($data['status_approve'] == 6) {
                    // tạo file docx thỏa thuận ba bên chovay_tcvdb_template
                    if (file_exists('assets/file/file_megadoc/chovay_tcvdb_template.docx')) {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/chovay_tcvdb_template.docx');
                        $templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
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
                        $templateProcessor->setValue('bank_account', $data['receiver_infor']['bank_account'] ?? '');
                        $templateProcessor->setValue('bank_branch', $data['receiver_infor']['bank_branch'] ?? '');
                        $templateProcessor->setValue('bank_name', $bank_name_nganluong ?? '');
                        $templateProcessor->setValue('store_representative', $store_representative ?? '');
                        $templateProcessor->setValue('amount_money', number_format($data['loan_infor']['amount_money']) ?? '');
                        $templateProcessor->setValue('amount_loan', number_format($data['loan_infor']['amount_loan']) ?? '');
                        $templateProcessor->setValue('number_day_loan', $data['loan_infor']['number_day_loan'] / 30 ?? '');
                        $templateProcessor->setValue('type_interest', $type_interest ?? '');
                        $templateProcessor->setValue('chi_phi_vay_thang', $chiphivaythang ?? '');
                        $templateProcessor->setValue('percent_interest', $data['fee']['percent_interest_customer'] ?? '');
                        $templateProcessor->setValue('advisory_fee', $data['fee']['percent_advisory'] ?? '');
                        $templateProcessor->setValue('another_fee', '');
                        $templateProcessor->setValue('tai_khoan_dinh_danh', $data['vpbank_van']['van'] ?? '');
                        $templateProcessor->setValue('type_property', $data['loan_infor']['type_property']['text'] ?? '');
                        $templateProcessor->setValue('so_khung', $sokhung ?? '');
                        $templateProcessor->setValue('bks', $bienkiemsoat ?? '');
                        $templateProcessor->setValue('somay', $somay ?? '');
                        $templateProcessor->setValue('ngaydangky', $ngaycapdangkyxe);
                        $templateProcessor->setValue('sodangky', $sodangky ?? '');

                        if ($is_bh_tnnv_gic_mic == 1) {
                            $templateProcessor->setImageValue('check_gic_mic', array(
                                'path' => 'assets/file/file_megadoc/checked.png',
                                'width' => $with_img_checkbox
                            ));
                        } else {
                            $templateProcessor->setImageValue('check_gic_mic', array(
                                'path' => 'assets/file/file_megadoc/uncheck.png',
                                'width' => $with_img_checkbox
                            ));
                        }
                        if ($is_bh_pti_vta == 1) {
                            $templateProcessor->setImageValue('check_pti', array(
                                'path' => 'assets/file/file_megadoc/checked.png',
                                'width' => $with_img_checkbox
                            ));
                        } else {
                            $templateProcessor->setImageValue('check_pti', array(
                                'path' => 'assets/file/file_megadoc/uncheck.png',
                                'width' => $with_img_checkbox
                            ));
                        }
                        if ($gic_easy_20 == 1 || $gic_easy_40 == 1 || $gic_easy_70 == 1) {
                            $templateProcessor->setImageValue('check_easy', array(
                                'path' => 'assets/file/file_megadoc/checked.png',
                                'width' => $with_img_checkbox
                            ));
                            $templateProcessor->setValue('goi_gic', $goi_gic ?? '');
                        } else {
                            $templateProcessor->setImageValue('check_easy', array(
                                'path' => 'assets/file/file_megadoc/uncheck.png',
                                'width' => $with_img_checkbox
                            ));
                            $templateProcessor->setValue('goi_gic', $goi_gic ?? '');
                        }
                        $templateProcessor->saveAs(APPPATH . '/file_megadoc/chovay_tcvdb_' . $code_contract . '.docx');
                    }
                } elseif ($data['status_approve'] == 15) {
                    // bbbg_chovay_tcvdb_template
                    if (file_exists('assets/file/file_megadoc/bbbg_chovay_tcvdb_template.docx')) {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/bbbg_chovay_tcvdb_template.docx');
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
                        $templateProcessor->setValue('current_time', $current_time ?? '');
                        $templateProcessor->setValue('current_hours', $current_hours ?? '');
                        $templateProcessor->setValue('current_minutes', $current_minutes ?? '');
                        $templateProcessor->setValue('date_sign_ttbb', $date_sign_ttbb ?? '');
                        $templateProcessor->saveAs(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_' . $code_contract . '.docx');
                    }
                } elseif ($data['status_approve'] == 16) {
                    // bbbg_chovay_tcvdb_tl_template
                    if (file_exists('assets/file/file_megadoc/thongbao_tcvdb_template.docx')) {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('assets/file/file_megadoc/thongbao_tcvdb_template.docx');
                        $templateProcessor->setValue('mahopdong', $data['code_contract_disbursement'] ?? '');
                        $templateProcessor->setValue('disbursement_date', $disbursement_date ?? '');
                        $templateProcessor->setValue('day', $day ?? '');
                        $templateProcessor->setValue('mon', $mon ?? '');
                        $templateProcessor->setValue('year', $year ?? '');
                        $templateProcessor->setValue('bks', $bienkiemsoat ?? '');
                        $templateProcessor->setValue('so_khung', $sokhung ?? '');
                        $templateProcessor->setValue('somay', $somay ?? '');
                        $templateProcessor->setValue('ngaydangky', $ngaycapdangkyxe);
                        $templateProcessor->setValue('sodangky', $sodangky ?? '');
                        $templateProcessor->setValue('nhan_hieu', $nhanhieu ?? '');
                        $templateProcessor->setValue('model', $model ?? '');
                        $templateProcessor->setValue('chuxe', $chuxe ?? '');
                        $templateProcessor->saveAs(APPPATH . '/file_megadoc/thongbao_tcvdb_' . $code_contract . '.docx');
                    }
                } elseif ($data['status_approve'] == 19) {
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
                }
                return true;
            }
        }
        return false;
    }

    private function execute_convert_docx($condition)
    {
        $CI = &get_instance();
        $CI->load->config('config');
        $keyApiConvertIo = $CI->config->item('MGD_KEYCONVERTIO');
        try {
            $result = $this->convert_docx_to_pdf_enterprice($keyApiConvertIo, $condition);
//          $result = $this->convert_docx_to_pdf_enterprice("c2f26e774d6a27dfac78e8c1ceeffa15", $condition);
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

    /**
     * Lấy trạng thái hợp đồng megadoc
     */
    public function get_status_megadoc_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $searchkey = $this->security->xss_clean($this->dataPost['searchkey']);
        $code_contract = $this->security->xss_clean($this->dataPost['code_contract']);
        $contractInfo = $this->contract_model->find_one_select(array('code_contract' => $code_contract), array("_id", "store.id"));
        $company_code = $this->check_store_tcv_dong_bac($contractInfo['store']['id']);
        if (!empty($company_code)) {
            $response_megadoc = $this->megadoc->status_contract($searchkey, $company_code);
            $response_megadoc_decode = json_decode(json_decode($response_megadoc, true));
            $response_megadoc_decode[0]->status_convert = status_contract_megadoc($response_megadoc_decode[0]->Status);
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Mã công ty đang trống!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $response_lms = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $response_megadoc_decode[0]
        );
        $this->set_response($response_lms, REST_Controller::HTTP_OK);
        return;
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


    /**
     * Hủy hợp đồng điện tử Megadoc
     */
    public function cancel_contract_megadoc_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag === false) return;
        $fkey = $this->security->xss_clean($this->dataPost['fkey']);
        $contract_no = $this->security->xss_clean($this->dataPost['contract_no']);
        $reason_cancel_megadoc = $this->security->xss_clean($this->dataPost['reason_cancel_megadoc']);
        $dataSend = array();
        if (!empty($fkey)) {
            $dataSend['code_contract'] = $fkey;
        }
        if (!empty($contract_no)) {
            $dataSend['code_contract_disbursement'] = $contract_no;
        }
        if (!empty($reason_cancel_megadoc)) {
            $dataSend['reason_cancel_contract'] = $reason_cancel_megadoc;
        }
        $contractInfo = $this->contract_model->find_one_select(array('code_contract' => $fkey), array("_id", "store.id"));
        $company_code = $this->check_store_tcv_dong_bac($contractInfo['store']['id']);
        $response_megadoc = $this->megadoc->cancel_contract($dataSend, $company_code);
        $response_megadoc_decode = json_decode(json_decode($response_megadoc, true));
        $action = "Hủy hợp đồng Megadoc";
        $this->log_megadoc($dataSend, $response_megadoc_decode, $dataSend['code_contract'], $action);
        if (!empty($response_megadoc_decode[0]->Success) && $response_megadoc_decode[0]->Success == 1) {
            $response_lms = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => $response_megadoc_decode[0]->Message
            );
            $this->set_response($response_lms, REST_Controller::HTTP_OK);
            return;
        } else {
            $response_lms = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Có lỗi trong quá trình hủy hợp đồng Megadoc!"
            );
            $this->set_response($response_lms, REST_Controller::HTTP_OK);
            return;
        }
    }


    /**
     * @param $contractInfo
     * @param $status
     * Gửi thông tin contract sang Megadoc tạo HĐ điện tử
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
                $sms_tcv_ttbb = $this->config->item("sms_tcv_ttbb");
                $sms_tcv_bbbgt = $this->config->item("sms_tcv_bbbgt");
                $sms_tcv_tb = $this->config->item("sms_tcv_tb");
                if ($status == 6) {
                    $content = $sms_tcv_ttbb . $link_tra_cuu_megadoc . $res_megadoc->SearchKey;
                } elseif ($status == 15) {
                    $content = $sms_tcv_bbbgt . $link_tra_cuu_megadoc . $res_megadoc->SearchKey;
                } elseif ($status == 16) {
                    $content = $sms_tcv_tb . $link_tra_cuu_megadoc . $res_megadoc->SearchKey;
                }
                if ($company_code == "TCV") {
                    //remove file docx
                    if ($contractInfo['loan_infor']['type_property']['code'] == 'NĐ') {
                        if ($status == 6) {
                            unlink(APPPATH . '/file_megadoc/thechap_tcv_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/thechap_tcv_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 15) {
                            unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 19) {
                            unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_tl_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_tl_' . $contractInfo['code_contract'] . '.pdf');
                        }
                    } elseif (($contractInfo['loan_infor']['type_loan']['code'] == 'CC' || $contractInfo['loan_infor']['type_loan']['code'] == 'DKX') && $contractInfo['loan_infor']['type_property']['code'] != "TC") {
                        if ($status == 6) {
                            unlink(APPPATH . '/file_megadoc/chovay_tcv_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/chovay_tcv_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 15) {
                            unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 16) {
                            unlink(APPPATH . '/file_megadoc/thongbao_tcv_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/thongbao_tcv_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 19) {
                            unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_tl_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_tl_' . $contractInfo['code_contract'] . '.pdf');
                        }
                    }
                } elseif ($company_code == "TCVĐB") {
                    if ($contractInfo['loan_infor']['type_property']['code'] == 'NĐ') {
                        if ($status == 6) {
                            unlink(APPPATH . '/file_megadoc/thechap_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/thechap_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 15) {
                            unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 19) {
                            unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_tl_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_tl_' . $contractInfo['code_contract'] . '.pdf');
                        }
                    } elseif (($contractInfo['loan_infor']['type_loan']['code'] == 'CC' || $contractInfo['loan_infor']['type_loan']['code'] == 'DKX') && $contractInfo['loan_infor']['type_property']['code'] != "TC") {
                        if ($status == 6) {
                            unlink(APPPATH . '/file_megadoc/chovay_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/chovay_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 15) {
                            unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 16) {
                            unlink(APPPATH . '/file_megadoc/thongbao_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/thongbao_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                        } elseif ($status == 19) {
                            unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_tl_' . $contractInfo['code_contract'] . '.docx');
                            unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_tl_' . $contractInfo['code_contract'] . '.pdf');
                        }
                    }
                }
                //status_email = 2 - Gửi thông báo ký số qua tin nhắn SMS
                if (!empty($contractInfo['customer_infor']['status_email']) && $contractInfo['customer_infor']['status_email'] == 2) {
                    //chuan bi gui SMS ky so cho khach hang
                    $template = "";
                    $template = "60b72056a51b0a227bf4526b";
                    $type_sms = "ky_so";
                    if ($status == 6) {
                        $type_document = 'ttbb';
                    } elseif ($status == 15) {
                        $type_document = 'bbbgt';
                    } elseif ($status == 16) {
                        $type_document = 'tb';
                    } elseif ($status == 19) {
                        $type_document = 'bbbgs';
                    }
                    // insert sms content to database
                    $id_sms = $this->insert_sms_megadoc($contractInfo, $template, $content, $res_megadoc->SearchKey, $res_megadoc->FKey, $type_sms, $type_document);
                    // send sms to customer
                    $data_send_api_sms = array(
                        "template" => $template,
                        "number" => $contractInfo['customer_infor']['customer_phone_number'],
                        "content" => $content
                    );
                    $res_sms = $this->push_api_sms('POST', json_encode($data_send_api_sms), "/sms/direct");
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

                if ($status == 6) {
                    $arrUpdate = array(
                        'megadoc.ttbb.searchkey' => $res_megadoc->SearchKey,
                        'megadoc.ttbb.fkey' => $res_megadoc->FKey,
                        'megadoc.ttbb.status' => 1,
                        'megadoc.ttbb.type_doc' => "ttbb",
                        'megadoc.ttbb.code_contract' => $contractInfo['code_contract'],
                        'megadoc.ttbb.contract_no' => $metadata['ContractNo'],

                    );
                } elseif ($status == 15) {
                    $arrUpdate = array(
                        'megadoc.bbbg_before_sign.searchkey' => $res_megadoc->SearchKey,
                        'megadoc.bbbg_before_sign.fkey' => $res_megadoc->FKey,
                        'megadoc.bbbg_before_sign.status' => 1,
                        'megadoc.bbbg_before_sign.type_doc' => "bbbg_truoc",
                        'megadoc.bbbg_before_sign.code_contract' => $contractInfo['code_contract'],
                        'megadoc.bbbg_before_sign.contract_no' => $metadata['ContractNo'],
                    );
                } elseif ($status == 16) {
                    $arrUpdate = array(
                        'megadoc.tb.searchkey' => $res_megadoc->SearchKey,
                        'megadoc.tb.fkey' => $res_megadoc->FKey,
                        'megadoc.tb.status' => 1,
                        'megadoc.tb.type_doc' => "tb",
                        'megadoc.tb.code_contract' => $contractInfo['code_contract'],
                        'megadoc.tb.contract_no' => $metadata['ContractNo'],
                    );
                }
                $this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);

                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'message' => "Tạo hợp đồng điện tử Megadoc thành công!",
                );
                return $response;
            } else {
                if (!empty($res_megadoc->ErrorMessage)) {
                    $message = $res_megadoc->ErrorMessage;
                } else {
                    $message = status_contract_megadoc_response($res_megadoc);
                }
                // 99 trạng thái gọi sang đối tác ko thành công
                if ($status == 6) {
                    $arrUpdate = array(
                        'megadoc.ttbb.status' => 99,
                        'megadoc.ttbb.type_doc' => "ttbb",
                        'megadoc.ttbb.code_contract' => $contractInfo['code_contract'],
                        'megadoc.ttbb.contract_no' => $metadata['ContractNo'],

                    );
                } elseif ($status == 15) {
                    $arrUpdate = array(
                        'megadoc.bbbg_before_sign.status' => 99,
                        'megadoc.bbbg_before_sign.type_doc' => "bbbg_truoc",
                        'megadoc.bbbg_before_sign.code_contract' => $contractInfo['code_contract'],
                        'megadoc.bbbg_before_sign.contract_no' => $metadata['ContractNo'],
                    );
                } elseif ($status == 16) {
                    $arrUpdate = array(
                        'megadoc.tb.status' => 99,
                        'megadoc.tb.type_doc' => "tb",
                        'megadoc.tb.code_contract' => $contractInfo['code_contract'],
                        'megadoc.tb.contract_no' => $metadata['ContractNo'],
                    );
                }
                $this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);
                $response = array(
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => $message,
                );
                return $response;
            }
        } else {
            if ($status == 6) {
                $arrUpdate = array(
                    'megadoc.ttbb.status' => 99,
                    'megadoc.ttbb.type_doc' => "ttbb",
                    'megadoc.ttbb.code_contract' => $contractInfo['code_contract'],
                    'megadoc.ttbb.contract_no' => $metadata['ContractNo'],

                );
            } elseif ($status == 15) {
                $arrUpdate = array(
                    'megadoc.bbbg_before_sign.status' => 99,
                    'megadoc.bbbg_before_sign.type_doc' => "bbbg_truoc",
                    'megadoc.bbbg_before_sign.code_contract' => $contractInfo['code_contract'],
                    'megadoc.bbbg_before_sign.contract_no' => $metadata['ContractNo'],
                );
            } elseif ($status == 16) {
                $arrUpdate = array(
                    'megadoc.tb.status' => 99,
                    'megadoc.tb.type_doc' => "tb",
                    'megadoc.tb.code_contract' => $contractInfo['code_contract'],
                    'megadoc.tb.contract_no' => $metadata['ContractNo'],
                );
            }
            $this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Không kết nối được tới Megadoc!",
            );
            return $response;
        }
    }


    /**
     * Tạo lại hợp đồng megadoc tương ứng
     */
    public function resend_file_to_megadoc_post()
    {
        $contract_id = !empty($this->security->xss_clean($this->dataPost['contract_id'])) ? $this->security->xss_clean($this->dataPost['contract_id']) : '';
        $status_approve = !empty($this->security->xss_clean($this->dataPost['status_approve'])) ? $this->security->xss_clean($this->dataPost['status_approve']) : '';
        $create_type = !empty($this->security->xss_clean($this->dataPost['create_type'])) ? $this->security->xss_clean($this->dataPost['create_type']) : '';
        if ($create_type == "one") {
            $create_type = 0;
        } else {
            $create_type = (int)$create_type;
        }
        $contractInfo = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract_id)));
        $check_store_create_contract = $this->check_store_create_contract_digital($contractInfo['store']['id']);
        if ($check_store_create_contract) {
            if (!empty($contractInfo['customer_infor']['type_contract_sign']) && $contractInfo['customer_infor']['type_contract_sign'] == 1) {
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
                $conditon_check_file['status_approve'] = $status_approve;
                $contractInfo['status_approve'] = $status_approve;

                // Tạo file docx thỏa thuận ba bên và biên bản bàn giao
                $create_docx_file = $this->create_contract_docx_file($contractInfo);
                $convert_pdf = $this->execute_convert_docx($conditon_check_file);
                // Tạo metadata và filecontent sendApi
                $metadata = $this->create_metadata_megadoc($contractInfo, $create_type);
                $filecontent = $this->check_path_file_contract($conditon_check_file);
                $dataSend = array(
                    'metadata' => json_encode($metadata),
                    'filecontent' => $filecontent
                );
                $check_company_send = $company_code;
                $megadoc = new DigitalContractMegadoc();
                $res_megadoc = $megadoc->create_contract($dataSend, $check_company_send);
                $action_log = "create_contract";
                $this->log_megadoc(json_encode($dataSend), $res_megadoc, $contractInfo['code_contract'], $action_log);
                if (!empty($res_megadoc)) {
                    if (!empty($res_megadoc->Success) && $res_megadoc->Success == true) {
                        $content ="";
                        $link_tra_cuu_megadoc = $this->config->item("link_tra_cuu_megadoc");
                        $sms_tcv_ttbb = $this->config->item("sms_tcv_ttbb");
                        $sms_tcv_bbbgt = $this->config->item("sms_tcv_bbbgt");
                        $sms_tcv_tb = $this->config->item("sms_tcv_tb");
                        if ($status_approve == 6) {
                            $content = $sms_tcv_ttbb . $link_tra_cuu_megadoc . $res_megadoc->SearchKey;
                        } elseif ($status_approve == 15 || $status_approve == 19) {
                            $content = $sms_tcv_bbbgt . $link_tra_cuu_megadoc . $res_megadoc->SearchKey;
                        } elseif ($status_approve == 16) {
                            $content = $sms_tcv_tb . $link_tra_cuu_megadoc . $res_megadoc->SearchKey;
                        }
                        if ($company_code == "TCV") {
                            // remove file created
                            if ($contractInfo['loan_infor']['type_property']['code'] == 'NĐ') {
                                if ($status_approve == 6) {
                                    unlink(APPPATH . '/file_megadoc/thechap_tcv_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/thechap_tcv_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 15) {
                                    unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 19) {
                                    unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_tl_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_tl_' . $contractInfo['code_contract'] . '.pdf');
                                }
                            } elseif (($contractInfo['loan_infor']['type_loan']['code'] == 'CC' || $contractInfo['loan_infor']['type_loan']['code'] == 'DKX') && $contractInfo['loan_infor']['type_property']['code'] != "TC") {
                                if ($status_approve == 6) {
                                    unlink(APPPATH . '/file_megadoc/chovay_tcv_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/chovay_tcv_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 15) {
                                    unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 16) {
                                    unlink(APPPATH . '/file_megadoc/thongbao_tcv_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/thongbao_tcv_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 19) {
                                    unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_tl_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_tl_' . $contractInfo['code_contract'] . '.pdf');
                                }
                            }
                        } elseif ($company_code == "TCVĐB") {
                            if ($contractInfo['loan_infor']['type_property']['code'] == 'NĐ') {
                                if ($status_approve == 6) {
                                    unlink(APPPATH . '/file_megadoc/thechap_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/thechap_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 15) {
                                    unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 19) {
                                    unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_tl_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_tl_' . $contractInfo['code_contract'] . '.pdf');
                                }
                            } elseif (($contractInfo['loan_infor']['type_loan']['code'] == 'CC' || $contractInfo['loan_infor']['type_loan']['code'] == 'DKX') && $contractInfo['loan_infor']['type_property']['code'] != "TC") {
                                if ($status_approve == 6) {
                                    unlink(APPPATH . '/file_megadoc/chovay_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/chovay_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 15) {
                                    unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 16) {
                                    unlink(APPPATH . '/file_megadoc/thongbao_tcvdb_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/thongbao_tcvdb_' . $contractInfo['code_contract'] . '.pdf');
                                } elseif ($status_approve == 19) {
                                    unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_tl_' . $contractInfo['code_contract'] . '.docx');
                                    unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_tl_' . $contractInfo['code_contract'] . '.pdf');
                                }
                            }
                        }
                        //status_email = 2 - email KH khong co hoac khong con truy cap duoc, nen can gui SMS cho KH nhan link ky so
                        if (!empty($contractInfo['customer_infor']['status_email']) && $contractInfo['customer_infor']['status_email'] == 2) {
                            //chuan bi gui SMS ky so cho khach hang
                            $template = "";
                            $template = "60b72056a51b0a227bf4526b";
                            $type_sms = "ky_so";
                            if ($status_approve == 6) {
                                $type_document = 'ttbb';
                            } elseif ($status_approve == 15) {
                                $type_document = 'bbbgt';
                            } elseif ($status_approve == 16) {
                                $type_document = 'tb';
                            } elseif ($status_approve == 19) {
                                $type_document = 'bbbgs';
                            }
                            // insert sms content to database
                            $id_sms = $this->insert_sms_megadoc($contractInfo, $template, $content, $res_megadoc->SearchKey, $res_megadoc->FKey, $type_sms, $type_document);
                            // send sms to customer
                            $data_send_api_sms = array(
                                "template" => $template,
                                "number" => $contractInfo['customer_infor']['customer_phone_number'],
                                "content" => $content
                            );
                            $res_sms = $this->push_api_sms('POST', json_encode($data_send_api_sms), "/sms/direct");
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
                        if ($status_approve == 6) {
                            $arrUpdate = array(
                                'megadoc.ttbb.searchkey' => $res_megadoc->SearchKey,
                                'megadoc.ttbb.fkey' => $res_megadoc->FKey,
                                'megadoc.ttbb.status' => 1,
                                'megadoc.ttbb.type_doc' => "ttbb",
                                'megadoc.ttbb.code_contract' => $contractInfo['code_contract'],
                                'megadoc.ttbb.contract_no' => $metadata['ContractNo'],
                            );
                        } elseif ($status_approve == 15) {
                            $arrUpdate = array(
                                'megadoc.bbbg_before_sign.searchkey' => $res_megadoc->SearchKey,
                                'megadoc.bbbg_before_sign.fkey' => $res_megadoc->FKey,
                                'megadoc.bbbg_before_sign.status' => 1,
                                'megadoc.bbbg_before_sign.type_doc' => "bbbg_truoc",
                                'megadoc.bbbg_before_sign.code_contract' => $contractInfo['code_contract'],
                                'megadoc.bbbg_before_sign.contract_no' => $metadata['ContractNo'],
                            );
                        } elseif ($status_approve == 16) {
                            $arrUpdate = array(
                                'megadoc.tb.searchkey' => $res_megadoc->SearchKey,
                                'megadoc.tb.fkey' => $res_megadoc->FKey,
                                'megadoc.tb.status' => 1,
                                'megadoc.tb.type_doc' => "tb",
                                'megadoc.tb.code_contract' => $contractInfo['code_contract'],
                                'megadoc.tb.contract_no' => $metadata['ContractNo'],
                            );
                        } elseif ($status_approve == 19) {
                            $arrUpdate = array(
                                'megadoc.bbbg_after_sign.searchkey' => $res_megadoc->SearchKey,
                                'megadoc.bbbg_after_sign.fkey' => $res_megadoc->FKey,
                                'megadoc.bbbg_after_sign.status' => 1,
                                'megadoc.bbbg_after_sign.type_doc' => "bbbg_sau",
                                'megadoc.bbbg_after_sign.code_contract' => $contractInfo['code_contract'],
                                'megadoc.bbbg_after_sign.contract_no' => $metadata['ContractNo'],
                            );
                        }
                        $this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);

                        $response = array(
                            'status' => REST_Controller::HTTP_OK,
                            'message' => "Cập nhật hợp đồng điện tử Megadoc thành công!",
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    } else {
                        if (!empty($res_megadoc->ErrorMessage)) {
                            $message = $res_megadoc->ErrorMessage;
                        } else {
                            $message = status_contract_megadoc_response($res_megadoc);
                        }
                        // 99 trạng thái gọi sang đối tác ko thành công
                        if ($status_approve == 6) {
                            $arrUpdate = array(
                                'megadoc.ttbb.status' => 99,
                                'megadoc.ttbb.type_doc' => "ttbb",
                                'megadoc.ttbb.code_contract' => $contractInfo['code_contract'],
                                'megadoc.ttbb.contract_no' => $metadata['ContractNo'],
                            );
                        } elseif ($status_approve == 15) {
                            $arrUpdate = array(
                                'megadoc.bbbg_before_sign.status' => 99,
                                'megadoc.bbbg_before_sign.type_doc' => "bbbg_truoc",
                                'megadoc.bbbg_before_sign.code_contract' => $contractInfo['code_contract'],
                                'megadoc.bbbg_before_sign.contract_no' => $metadata['ContractNo'],
                            );
                        } elseif ($status_approve == 16) {
                            $arrUpdate = array(
                                'megadoc.tb.status' => 99,
                                'megadoc.tb.type_doc' => "tb",
                                'megadoc.tb.code_contract' => $contractInfo['code_contract'],
                                'megadoc.tb.contract_no' => $metadata['ContractNo'],
                            );
                        } elseif ($status_approve == 19) {
                            $arrUpdate = array(
                                'megadoc.bbbg_after_sign.status' => 99,
                                'megadoc.bbbg_after_sign.type_doc' => "bbbg_sau",
                                'megadoc.bbbg_after_sign.code_contract' => $contractInfo['code_contract'],
                                'megadoc.bbbg_after_sign.contract_no' => $metadata['ContractNo'],
                            );
                        }
                        $this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);
                        $response = array(
                            'status' => REST_Controller::HTTP_BAD_REQUEST,
                            'message' => $message,
                        );
                        $this->set_response($response, REST_Controller::HTTP_OK);
                        return;
                    }
                } else {
                    // 99 trạng thái gọi sang đối tác ko thành công
                    if ($status_approve == 6) {
                        $arrUpdate = array(
                            'megadoc.ttbb.status' => 99,
                            'megadoc.ttbb.type_doc' => "ttbb",
                            'megadoc.ttbb.code_contract' => $contractInfo['code_contract'],
                            'megadoc.ttbb.contract_no' => $metadata['ContractNo'],
                        );
                    } elseif ($status_approve == 15) {
                        $arrUpdate = array(
                            'megadoc.bbbg_before_sign.status' => 99,
                            'megadoc.bbbg_before_sign.type_doc' => "bbbg_truoc",
                            'megadoc.bbbg_before_sign.code_contract' => $contractInfo['code_contract'],
                            'megadoc.bbbg_before_sign.contract_no' => $metadata['ContractNo'],
                        );
                    } elseif ($status_approve == 16) {
                        $arrUpdate = array(
                            'megadoc.tb.status' => 99,
                            'megadoc.tb.type_doc' => "tb",
                            'megadoc.tb.code_contract' => $contractInfo['code_contract'],
                            'megadoc.tb.contract_no' => $metadata['ContractNo'],
                        );
                    } elseif ($status_approve == 19) {
                        $arrUpdate = array(
                            'megadoc.bbbg_after_sign.status' => 99,
                            'megadoc.bbbg_after_sign.type_doc' => "bbbg_sau",
                            'megadoc.bbbg_after_sign.code_contract' => $contractInfo['code_contract'],
                            'megadoc.bbbg_after_sign.contract_no' => $metadata['ContractNo'],
                        );
                    }
                    $this->contract_model->update(array("_id" => $contractInfo['_id']), $arrUpdate);
                    $response = array(
                        'status' => REST_Controller::HTTP_BAD_REQUEST,
                        'message' => "Không kết nối được tới Megadoc!",
                    );
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                }
            }
        }
    }

    public function download_file_megadoc_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $searchkey = !empty($this->security->xss_clean($this->dataPost['searchkey'])) ? $this->security->xss_clean(trim($this->dataPost['searchkey'])) : "";
        $file_type = !empty($this->security->xss_clean($this->dataPost['file_type'])) ? $this->security->xss_clean(trim($this->dataPost['file_type'])) : "";
        $code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ? $this->security->xss_clean(trim($this->dataPost['code_contract'])) : "";
        $contractInfo = $this->contract_model->find_one_select(array('code_contract' => $code_contract), array("_id", "store.id"));
        $company_code = $this->check_store_tcv_dong_bac($contractInfo['store']['id']);
        $response_megadoc = $this->megadoc->download_file($searchkey, $company_code);
        file_put_contents('assets/file/file_megadoc_download/' . $code_contract . '_' .$file_type . '.pdf', $response_megadoc);
        $path = 'assets/file/file_megadoc_download/' . $code_contract . '_' .$file_type . '.pdf';
        $url = base_url() . 'assets/file/file_megadoc_download/' . $code_contract . '_' .$file_type . '.pdf';
        $file_name = $code_contract . '_' .$file_type . '.pdf';
        chown($path, "apache");
        chmod($path, 777);

        if (!empty($response_megadoc)) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Lấy link file thành công!",
                'data' => $url
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tải file thất bại!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

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

    // Lấy id các PGD có áp dụng hợp đồng điện tử
    public function get_store_megadoc_post()
    {
        $role = $this->role_model->findOne(['slug' => 'hop-dong-dien-tu']);
        $id_store_megadoc = array();
        if (!empty($role)) {
            foreach ($role['stores'] as $store) {
                foreach ($store as $key => $item) {
                    array_push($id_store_megadoc, $key);
                }
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $id_store_megadoc
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return ;
    }

    //Check trạng thái các văn bản Megadoc trước khi gửi yêu cầu giải ngân
    public function check_ttbb_megadoc_post()
    {
        $code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ? $this->security->xss_clean($this->dataPost['code_contract']) : '';
        $contract = $this->contract_model->findOne(array("code_contract" => $code_contract));
        $is_ttbb_digital = false;
        $is_bbbg_digital = false;
        $is_tb_digital = false;
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $check_megadoc_contract = $this->megadoc->status_contract($contract['megadoc']['ttbb']['searchkey'], $ma_cty);
        $check_megadoc_contract_bbbg = $this->megadoc->status_contract($contract['megadoc']['bbbg_before_sign']['searchkey'], $ma_cty);
        $check_megadoc_contract_tb = $this->megadoc->status_contract($contract['megadoc']['tb']['searchkey'], $ma_cty);
        $array_check_megadoc_contract = json_decode(json_decode($check_megadoc_contract, true), true);
        $array_check_megadoc_contract_bbbg = json_decode(json_decode($check_megadoc_contract_bbbg, true), true);
        $array_check_megadoc_contract_tb = json_decode(json_decode($check_megadoc_contract_tb, true), true);
        if (!empty($array_check_megadoc_contract) && $array_check_megadoc_contract[0]['Status'] == 3) {
            $is_ttbb_digital = true;
        } else {
            $is_ttbb_digital = false;
        }
        if (!empty($array_check_megadoc_contract_bbbg) && $array_check_megadoc_contract_bbbg[0]['Status'] == 3 ) {
            $is_bbbg_digital = true;
        } else {
            $is_bbbg_digital = false;
        }
        if (!empty($array_check_megadoc_contract_tb) && in_array($array_check_megadoc_contract_tb[0]['Status'], [2,3])) {
            $is_tb_digital = true;
        } else {
            $is_tb_digital = false;
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'is_ttbb_digital' => $is_ttbb_digital,
            'is_bbbg_digital' => $is_bbbg_digital,
            'is_tb_digital' => $is_tb_digital
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    // Check TTBB điện tử đã đủ 02 chữ ký hay chưa
    private function check_ttbb_megadoc_finish($code_contract)
    {
        $contract = $this->contract_model->findOne(array("code_contract" => $code_contract));
        $is_ttbb_digital = false;
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $check_megadoc_contract = $this->megadoc->status_contract($contract['megadoc']['ttbb']['searchkey'], $ma_cty);
        $array_check_megadoc_contract = json_decode(json_decode($check_megadoc_contract, true), true);
        if (!empty($array_check_megadoc_contract) && $array_check_megadoc_contract[0]['Status'] == 3) {
            $is_ttbb_digital = true;
        } else {
            $is_ttbb_digital = false;
        }

        return $is_ttbb_digital;
    }

    // Check BBBG điện tử đã đủ 02 chữ ký hay chưa
    private function check_bbbgt_megadoc_finish($code_contract)
    {
        $contract = $this->contract_model->findOne(array("code_contract" => $code_contract));
        $is_bbbgt_digital = false;
        $ma_cty = $this->check_store_tcv_dong_bac($contract['store']['id']);
        $check_megadoc_bbbgt = $this->megadoc->status_contract($contract['megadoc']['bbbg_before_sign']['searchkey'], $ma_cty);
        $array_check_megadoc_bbbgt = json_decode(json_decode($check_megadoc_bbbgt, true), true);
        if (!empty($array_check_megadoc_bbbgt) && $array_check_megadoc_bbbgt[0]['Status'] == 3) {
            $is_bbbgt_digital = true;
        } else {
            $is_bbbgt_digital = false;
        }

        return $is_bbbgt_digital;
    }

    /**
     *get infor from megadoc to send SMS to customer
     */
    public function get_verify_code_post()
    {
        $data = json_decode($this->security->xss_clean($this->input->raw_input_stream));
        if (!empty($data)) {
            if (!empty($data->Fkey)) {
                $data_send['fkey'] = $data->Fkey;
            } else {
                $response = array(
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => "Fkey is empty!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!empty($data->ContractNo)) {
                $data_send['contract_no'] = $data->ContractNo;
            } else {
                $response = array(
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => "ContractNo is empty!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!empty($data->SearchKey)) {
                $data_send['search_key'] = $data->SearchKey;
            } else {
                $response = array(
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => "SearchKey is empty!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!empty($data->CusPhone)) {
                $data_send['customer_phone'] = $data->CusPhone;
            } else {
                $response = array(
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => "CusPhone is empty!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!empty($data->PinCode)) {
                $data_send['pin_code'] = $data->PinCode;
            } else {
                $response = array(
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => "PinCode is empty!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!empty($data->ExpiredDate)) {
                $data_send['expired_date'] = $data->ExpiredDate;
            } else {
                $response = array(
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => "ExpiredDate is empty!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Data is empty!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $content = "";
        $template = "";
        $template = "60b72056a51b0a227bf4526b";
        $type_sms = "mxt";
        $code_contract = str_replace("_bbbgtruoc","", $data_send['fkey']);
        $code_contract = str_replace("_tb","", $code_contract);
        $code_contract = str_replace("_bbbgsau","", $code_contract);
        $code_contract_disbursement = str_replace("_bgtruoc", "", $data_send['contract_no']);
        $code_contract_disbursement = str_replace("_tb", "", $code_contract_disbursement);
        $code_contract_disbursement = str_replace("_bgssau", "", $code_contract_disbursement);
        $content = "TienvaNgay: Ma xac thuc cua QK de hoan thanh viec ky Hop Dong " . $code_contract_disbursement . " la: " . $data_send['pin_code'] . ", hieu luc 10 phut. Lien he 19006907 de duoc ho tro.";
        $contractInfo = $this->contract_model->findOne(['code_contract' => $code_contract]);
        if (!empty($contractInfo)) {
            // insert sms content to database
            $type_document = '';
            if (!empty($contractInfo['megadoc']['ttbb']['searchkey']) && $contractInfo['megadoc']['ttbb']['searchkey'] == $data_send['search_key']) {
                $type_document = 'ttbb';
            }
            if (!empty($contractInfo['megadoc']['bbbg_before_sign']['searchkey']) && $contractInfo['megadoc']['bbbg_before_sign']['searchkey'] == $data_send['search_key']) {
                $type_document = 'bbbgt';
            }
            if (!empty($contractInfo['megadoc']['tb']['searchkey']) && $contractInfo['megadoc']['tb']['searchkey'] == $data_send['search_key']) {
                $type_document = 'tb';
            }
            if (!empty($contractInfo['megadoc']['bbbg_after_sign']['searchkey']) && $contractInfo['megadoc']['bbbg_after_sign']['searchkey'] == $data_send['search_key']) {
                $type_document = 'bbbgs';
            }

            $id_sms = $this->insert_sms_megadoc($contractInfo, $template, $content, $data_send['search_key'], $data_send['fkey'], $type_sms, $type_document);
        }
        $code_contract_db = !empty($contractInfo['code_contract']) ? $contractInfo['code_contract'] : $code_contract;
        $code_contract_disbursement_db = !empty($contractInfo['code_contract_disbursement']) ? $contractInfo['code_contract_disbursement'] : $code_contract_disbursement;
        $customer_phone_db = !empty($contractInfo['customer_infor']['customer_phone_number']) ? $contractInfo['customer_infor']['customer_phone_number'] : $data_send['customer_phone'];
        $data_send_api_sms = array(
            "template" => $template,
            "number" => $customer_phone_db,
            "content" => $content
        );
        // send sms to customer
        if (!empty($contractInfo)) {
            $res_sms = $this->push_api_sms('POST', json_encode($data_send_api_sms), "/sms/direct");
            $this->log_megadoc($data_send_api_sms, $res_sms, $code_contract_db, 'mxt');
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
                    $response = array(
                        'status' => REST_Controller::HTTP_OK,
                        'message' => "Send PinCode success!"
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
                    $response = array(
                        'status' => REST_Controller::HTTP_BAD_REQUEST,
                        'message' => "Send PinCode failed!"
                    );
                }
            }
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Contract is not exist!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
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

    public function test_send_sms_post()
    {
        $data = $this->input->post();
        $code_contract = $data['code_contract'];
        $contractInfo = $this->contract_model->findOne(['code_contract' => $code_contract]);
        $sms_not_send = $this->sms_megadoc_model->find_where(['status' => "fail", 'code_contract' => $code_contract]);
        $content = "";
        $template = "60b72056a51b0a227bf4526b";
        $content = "TienvaNgay: Ma xac thuc cua QK de hoan thanh viec ky Hop Dong 47 la: 92698034, hieu luc 10 phut Lien he 19006907 de duoc ho tro";

        // insert sms content to database
        $id_sms = $this->insert_sms_megadoc($contractInfo, $template, $content, $searchKey = '', $fKey = '', $type = '');
        // send sms to customer
        $data_send_api_sms = array(
            "template" => $template,
            "number" => $contractInfo['customer_infor']['customer_phone_number'],
            "content" => $content
        );

        $res_sms = $this->push_api_sms('POST', json_encode($data_send_api_sms), "/sms/direct");
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
                echo 'sended!';
            } else {
                $sms_update['status'] = 'fail';
                $sms_update['response'] = $res_sms;
                $sms_update['send_time'] = time();
                $this->sms_megadoc_model->update(
                    [
                        '_id' => $id_sms
                    ], $sms_update
                );
                echo "fail";
            }
        }
    }

    public function get_sms_megadoc_fail_post()
    {
        $code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ? $this->security->xss_clean($this->dataPost['code_contract']) : '';
        if (!empty($code_contract)) {
            $sms_fail = $this->sms_megadoc_model->findOne(['code_contract' => $code_contract, 'status' => 'fail']);
        }
        if (!empty($sms_fail)) {
            $response = [
                'status' => REST_Controller::HTTP_OK,
                'data' => $sms_fail
            ];
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $response = [
                'status' => REST_Controller::HTTP_OK,
                'data' => ''
            ];
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }

    public function resend_sms_megadoc_post()
    {
        $data = $this->input->post();
        $content ="";
        $template = "";
        $code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";
        $sms_id = !empty($data['sms_id']) ? $data['sms_id'] : "";
        $sms_db = $this->sms_megadoc_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($sms_id)]);
        $template = "60b72056a51b0a227bf4526b";
        if (!empty($sms_db)) {
            $data_send_api_sms = array(
                "template" => $template,
                "number" => $sms_db['customer_phone'],
                "content" => $sms_db['content']
            );
            $res_sms = $this->push_api_sms('POST', json_encode($data_send_api_sms), "/sms/direct");
            $this->log_megadoc($data_send_api_sms, $res_sms, $code_contract, 'ky-so');
            if (!empty($res_sms)) {
                if (isset($res_sms->sendTime)) {
                    $sms_update['status'] = 'success';
                    $sms_update['response'] = $res_sms;
                    $sms_update['send_time'] = time();
                    $this->sms_megadoc_model->update(
                        [
                            '_id' => $sms_db['_id']
                        ], $sms_update
                    );
                    $response = [
                        'status' => REST_Controller::HTTP_OK,
                        'message' => 'Gửi lại SMS thành công!',
                    ];
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                } else {
                    $sms_update['status'] = 'fail';
                    $sms_update['response'] = $res_sms;
                    $sms_update['send_time'] = time();
                    $this->sms_megadoc_model->update(
                        [
                            '_id' => $sms_db['_id']
                        ], $sms_update
                    );
                    $response = [
                        'status' => REST_Controller::HTTP_BAD_REQUEST,
                        'message' => 'Gửi lại SMS thất bại!',
                    ];
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                }
            } else {
                $response = [
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Gửi lại SMS thất bại!',
                ];
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        } else {
            $response = [
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => 'không tồn tại dữ liệu SMS!',
            ];
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }


    public function insert_log_file($value, $contract_id){

        $fp = fopen($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json', "a");

        if(!empty($fp)){

            $arrayData = $this->readFileJson($contract_id);

            if(empty($arrayData)){
                $arrayData = [];
            }
            array_push($arrayData, $value);

            $this->saveFileJson($arrayData, $contract_id);

        }
    }
    /**
     *get reduced profit with contract apply code_coupon
     */
    public function get_interest_end_period_by_coupon_post()
    {
        $data = $this->input->post();
        $code_coupon = !empty($this->security->xss_clean($this->dataPost['code_coupon'])) ?  $this->security->xss_clean($this->dataPost['code_coupon']) : '';
        $code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ?  $this->security->xss_clean($this->dataPost['code_contract']) : '';
        $date_pay = !empty($this->security->xss_clean($this->dataPost['date_pay'])) ?  $this->security->xss_clean($this->dataPost['date_pay']) : '';
        $coupon_infor = $this->coupon_model->findOne(['code' => $code_coupon]);
        $temporary_plan_contracts = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract]);
        $tempo_not_pay = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
        $current_day = !empty($date_pay) ? $date_pay : strtotime(date('Y-m-d') . ' 23:59:59');
        $date_pay_tempo = !empty($tempo_not_pay[0]['ngay_ky_tra']) ? intval($tempo_not_pay[0]['ngay_ky_tra']) : $current_day;
        $time = intval(($current_day - strtotime(date('Y-m-d', $date_pay_tempo) . ' 23:59:59')) / (24 * 60 * 60));
        $is_payment_slow = false;
        $reduced_profit = 0;
        if ($time > 0) {
            $is_payment_slow = true;
        }
        if (!empty($coupon_infor)) {
            if (!empty($temporary_plan_contracts)) {
                // giam lai 03 thang dau
                if (isset($coupon_infor['is_reduction_interest']) && $coupon_infor['is_reduction_interest'] == "active") {
                    foreach ($temporary_plan_contracts as $key => $tempo) {
                        if ($tempo['ky_tra'] == 1 || $tempo['ky_tra'] == 2 || $tempo['ky_tra'] == 3) {
                            $reduced_profit += $tempo['lai_ky'];
                        }
                    }
                // giam lai 01 thang dau
                } elseif (isset($coupon_infor['down_interest_on_month']) && $coupon_infor['down_interest_on_month'] == "active") {
                    foreach ($temporary_plan_contracts as $key1 => $tempo1) {
                        if ($tempo1['ky_tra'] == 1) {
                            $reduced_profit += $tempo1['lai_ky'];
                        }
                    }
                }
                foreach ($temporary_plan_contracts as $temporary) {
                    if ($temporary['so_ngay_cham_tra'] > 0) {
                        $is_payment_slow = true;
                        break;
                    }
                }
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => !$is_payment_slow ? $reduced_profit : 0,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    /**
     *get reduced profit with contract apply code_coupon for calculator payment
     */
    public function get_interest_end_period_by_coupon_calculator_post()
    {
        $data = $this->input->post();
        $code_coupon = !empty($this->security->xss_clean($this->dataPost['code_coupon'])) ?  $this->security->xss_clean($this->dataPost['code_coupon']) : '';
        $code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ?  $this->security->xss_clean($this->dataPost['code_contract']) : '';
        $date_pay = !empty($this->security->xss_clean($this->dataPost['date_pay'])) ?  $this->security->xss_clean($this->dataPost['date_pay']) : '';
        $coupon_infor = $this->coupon_model->findOne(['code' => $code_coupon]);
        $temporary_plan_contracts = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract]);
        $tempo_not_pay = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
        $current_day = !empty($date_pay) ? strtotime($date_pay) : strtotime(date('Y-m-d') . ' 23:59:59');
        $date_pay_tempo = !empty($tempo_not_pay[0]['ngay_ky_tra']) ? intval($tempo_not_pay[0]['ngay_ky_tra']) : $current_day;
        $time = intval(($current_day - strtotime(date('Y-m-d', $date_pay_tempo) . ' 23:59:59')) / (24 * 60 * 60));
        $is_payment_slow = false;
        $reduced_profit = 0;
        if ($time > 0) {
            $is_payment_slow = true;
        }
        if (!empty($coupon_infor)) {
            if (!empty($temporary_plan_contracts)) {
                // giam lai 03 thang dau
                if (isset($coupon_infor['is_reduction_interest']) && $coupon_infor['is_reduction_interest'] == "active") {
                    foreach ($temporary_plan_contracts as $key => $tempo) {
                        if ($tempo['ky_tra'] == 1 || $tempo['ky_tra'] == 2 || $tempo['ky_tra'] == 3) {
                            $reduced_profit += $tempo['lai_ky'];
                        }
                    }
                    // giam lai 01 thang dau
                } elseif (isset($coupon_infor['down_interest_on_month']) && $coupon_infor['down_interest_on_month'] == "active") {
                    foreach ($temporary_plan_contracts as $key1 => $tempo1) {
                        if ($tempo1['ky_tra'] == 1) {
                            $reduced_profit += $tempo1['lai_ky'];
                        }
                    }
                }
                foreach ($temporary_plan_contracts as $temporary) {
                    if ($temporary['so_ngay_cham_tra'] > 0) {
                        $is_payment_slow = true;
                        break;
                    }
                }
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => !$is_payment_slow ? $reduced_profit : 0,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    function saveFileJson($arrayData , $contract_id){
        $dataJson = json_encode($arrayData);
        file_put_contents($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json',$dataJson);
    }
    function readFileJson($contract_id){
        $data = file_get_contents($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json');
        return json_decode($data,true);
    }

    function cron_contract_log_post(){

        $contract_id = $this->contract_model->find_id_contract();

        foreach ($contract_id as $value){

            $where_logs = $this->log_model->find_where(['contract_id'=>(string)$value['_id']]);

            if (!empty($where_logs)){
                foreach ($where_logs as $item){
                    //Insert log
                    $insertLog = array(
                        "type" => "contract",
                        "action" => !empty($item['action']) ? $item['action'] : '' ,
                        "contract_id" => !empty($item['contract_id']) ? $item['contract_id'] : '',
                        "old" => !empty($item['old']) ? $item['old'] : '',
                        "new" => !empty($item['new']) ? $item['new'] : '',
                        "created_at" => !empty($item['created_at']) ? $item['created_at'] : '',
                        "created_by" => !empty($item['created_by']) ? $item['created_by'] : '',
                        'type_gh_cc' => !empty($item['type_gh_cc']) ? $item['type_gh_cc'] : '',
                    );
                    /**
                     * Save log to json file
                     */

                    $insertLogNew = [
                        "type" => "contract",
                        "action" => !empty($item['action']) ? $item['action'] : '',
                        "contract_id" => !empty($item['contract_id']) ? $item['contract_id'] : '',
                        "created_at" => !empty($item['created_at']) ? $item['created_at'] : '',
                        "created_by" => !empty($item['created_by']) ? $item['created_by'] : '',
                        'type_gh_cc' => !empty($item['type_gh_cc']) ? $item['type_gh_cc'] : '',
                    ];
                    $log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
                    $insertLog['log_id'] = $log_id;

                    $this->insert_log_file($insertLog, $item['contract_id']);

                    /**
                     * ----------------------
                     */



                }
            }

        }

    }

    public function checkContract_post(){

        $customer_identify = !empty($this->security->xss_clean($this->dataPost['customer_identify'])) ? $this->security->xss_clean($this->dataPost['customer_identify']) : '';

        if ($customer_identify == ""){
            $response = [
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số chứng minh thư không được để trống"
            ];
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        if (!preg_match("/^[0-9]{9,12}$/", $customer_identify)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số chứng minh thư không đúng định dạng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $dataContract = $this->contract_model->findOne_copy($customer_identify);

        if (!empty($dataContract)){
            $response = [
                'status' => REST_Controller::HTTP_OK,
                'code_contract' => $dataContract[0]['code_contract']
            ];
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $response = [
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại HĐ với chứng minh thư trên"
            ];
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }


    }

    // Cron tạo BBBG TS trước khi ký TTBB
    public function cron_create_bbbg_digital_post()
    {
        $contractInfo = $this->contract_model->find_where(array('customer_infor.type_contract_sign' => '1', 'status' => 6, 'megadoc.ttbb.status' => array('$in' => [0,1,2,3])));
        if (!empty($contractInfo)) {
            foreach ($contractInfo as $contract) {
                // Check TTBB điện tử đã đủ chữ ký hay chưa
                $ttbb = $this->check_ttbb_megadoc_finish($contract['code_contract']);
                // Check BBBG điện tử đã tồn tại chưa
                $exists_bbbg = false;
                if (!empty($contract['megadoc']['bbbg_before_sign']['status'] && in_array($contract['megadoc']['bbbg_before_sign']['status'], [0,1,2,3,7,99]))) {
                    $exists_bbbg = true;
                }
                // Nếu TTBB đã đủ chữ ký và chưa tồn tại BBBG thì sinh ra BBBG
                if ( $ttbb && (!$exists_bbbg) ) {
                    $status = 15; // trang thai sinh ra BBBGTS truoc
                    echo $contract['code_contract_disbursement'];
                    $bbbg = $this->create_contract_megadoc($contract, $status);
                }
            }
        }
        echo " DONE";
    }

    // Cron tạo Văn bản Thông báo
    public function cron_create_tb_digital_post()
    {
        $contractInfo = $this->contract_model->find_where(array('customer_infor.type_contract_sign' => '1', 'status' => 6, 'megadoc.bbbg_before_sign.status' => array('$in' => [0,1,2,3]), 'loan_infor.type_property.code' => array('$nin' => ['NĐ'])));
        if (!empty($contractInfo)) {
            foreach ($contractInfo as $contract) {
                // Check BBBGT điện tử đã đủ chữ ký hay chưa
                $bbbgt = $this->check_bbbgt_megadoc_finish($contract['code_contract']);
                // Check Thông báo điện tử đã tồn tại chưa
                $exists_tb = false;
                if (!empty($contract['megadoc']['tb']['status'] && in_array($contract['megadoc']['tb']['status'], [0,1,2,3,7,99]))) {
                    $exists_tb = true;
                }
                // Nếu BBBG đã đủ chữ ký và chưa tồn tại TB thì sinh ra TB
                if ( $bbbgt && (!$exists_tb) ) {
                    $status = 16; // trang thai sinh ra văn bản Thông báo
                    echo $contract['code_contract_disbursement'];
                    $tb = $this->create_contract_megadoc($contract, $status);
                }
            }
        }
        echo " DONE";
    }

    public function sync_status_megadoc_post()
    {
        $contract_id = !empty($this->security->xss_clean($this->dataPost['id_contract'])) ? $this->security->xss_clean($this->dataPost['id_contract']) : '';
        $contractDb = $this->contract_model->find_one_select(array('_id' => new MongoDB\BSON\ObjectId($contract_id)), array('_id', 'megadoc', 'code_contract', 'status'));
        $search_key_ttbb = '';
        $search_key_bbbgt = '';
        $search_key_tb = '';
        $search_key_bbbgs = '';
        $status_ttbb = '';
        $status_bbbgt = '';
        $status_tb = '';
        $status_bbbgs = '';
        $contract_id_db = !empty($contractDb['_id']) ? (string)$contractDb['_id'] : '';
        $code_contract_db = !empty($contractDb['code_contract']) ? (string)$contractDb['code_contract'] : '';
        $status_contract = !empty($contractDb['status']) ? $contractDb['status'] : '';
        if (!empty($contractDb)) {
            $ma_cty = $this->check_store_tcv_dong_bac($contractDb['store']['id']);
            $search_key_ttbb = !empty($contractDb['megadoc']['ttbb']['searchkey']) ? $contractDb['megadoc']['ttbb']['searchkey'] : '';
            $search_key_bbbgt = !empty($contractDb['megadoc']['bbbg_before_sign']['searchkey']) ? $contractDb['megadoc']['bbbg_before_sign']['searchkey'] : '';
            $search_key_tb = !empty($contractDb['megadoc']['tb']['searchkey']) ? $contractDb['megadoc']['tb']['searchkey'] : '';
            $search_key_bbbgs = !empty($contractDb['megadoc']['bbbg_after_sign']['searchkey']) ? $contractDb['megadoc']['bbbg_after_sign']['searchkey'] : '';
            // Gọi API lấy status megadoc ttbb
            if (!empty($search_key_ttbb) && !empty($ma_cty)) {
                $ttbb_infor = $this->megadoc->status_contract($search_key_ttbb, $ma_cty);
                $ttbb_infor_decode = json_decode(json_decode($ttbb_infor, true), true);
                $status_ttbb = !empty($ttbb_infor_decode[0]['Status']) ? $ttbb_infor_decode[0]['Status'] : '';
            }
            // Gọi API lấy status megadoc bbbg trước
            if (!empty($search_key_bbbgt) && !empty($ma_cty)) {
                $bbbgt_infor = $this->megadoc->status_contract($search_key_bbbgt, $ma_cty);
                $bbbgt_infor_decode = json_decode(json_decode($bbbgt_infor, true), true);
                $status_bbbgt = !empty($bbbgt_infor_decode[0]['Status']) ? $bbbgt_infor_decode[0]['Status'] : '';
            }
            // Gọi API lấy status megadoc thông báo
            if (!empty($search_key_tb) && !empty($ma_cty)) {
                $tb_infor = $this->megadoc->status_contract($search_key_tb, $ma_cty);
                $tb_infor_decode = json_decode(json_decode($tb_infor, true), true);
                $status_tb = !empty($tb_infor_decode[0]['Status']) ? $tb_infor_decode[0]['Status'] : '';
            }
            // Gọi API lấy status megadoc bbbg sau
            if (!empty($search_key_bbbgs) && !empty($ma_cty)) {
                $bbbgs_infor = $this->megadoc->status_contract($search_key_bbbgs, $ma_cty);
                $bbbgs_infor_decode = json_decode(json_decode($bbbgs_infor, true), true);
                $status_bbbgs = !empty($bbbgs_infor_decode[0]['Status']) ? $bbbgs_infor_decode[0]['Status'] : '';
            }
            //update status hợp đồng Megadoc vào db tienngay
            if (!empty($status_ttbb) && in_array($status_ttbb, [0,1,2,3,7])) {
                $this->contract_model->update(
                    array('_id' => $contractDb['_id']),
                    array('megadoc.ttbb.status' => (int)$status_ttbb)
                );
            }
            if (!empty($status_bbbgt) && in_array($status_bbbgt, [0,1,2,3,7])) {
                $this->contract_model->update(
                    array('_id' => $contractDb['_id']),
                    array('megadoc.bbbg_before_sign.status' => (int)$status_bbbgt)
                );
            }
            if (!empty($status_tb) && in_array($status_tb, [0,1,2,3,7])) {
                $this->contract_model->update(
                    array('_id' => $contractDb['_id']),
                    array('megadoc.tb.status' => (int)$status_tb)
                );
            }
            if (!empty($status_bbbgs) && in_array($status_bbbgs, [0,1,2,3,7])) {
                $this->contract_model->update(
                    array('_id' => $contractDb['_id']),
                    array('megadoc.bbbg_after_sign.status' => (int)$status_bbbgs)
                );
            }
        }
        $array_megadoc = array(
            'contract_id' => $contract_id_db,
            'code_contract' => $code_contract_db,
            'status_contract' => $status_contract,
            'status_ttbb' => !empty($status_ttbb) ? $status_ttbb : '',
            'status_bbbgt' => !empty($status_bbbgt) ? $status_bbbgt : '',
            'status_tb' => !empty($status_tb) ? $status_tb : '',
            'status_bbbgs' => !empty($status_bbbgs) ? $status_bbbgs : '',
            'searchkey_ttbb' => !empty($search_key_ttbb) ? $search_key_ttbb : '',
            'searchkey_bbbgt' => !empty($search_key_bbbgt) ? $search_key_bbbgt : '',
            'searchkey_tb' => !empty($search_key_tb) ? $search_key_tb : '',
            'searchkey_bbbgs' => !empty($search_key_bbbgs) ? $search_key_bbbgs : ''
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $array_megadoc
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return ;
    }

    /**
     * Tạo lại hợp đồng megadoc tương ứng
     */
    public function create_document_megadoc_test_post()
    {
        $code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ? $this->security->xss_clean($this->dataPost['code_contract']) : '';
        $status_approve = !empty($this->security->xss_clean($this->dataPost['status_approve'])) ? $this->security->xss_clean($this->dataPost['status_approve']) : '';
        $create_type = !empty($this->security->xss_clean($this->dataPost['create_type'])) ? $this->security->xss_clean($this->dataPost['create_type']) : '';
        if ($create_type == "one") {
            $create_type = 0;
        } else {
            $create_type = (int)$create_type;
        }
        $contractInfo = $this->contract_model->findOne(array("code_contract" => $code_contract));
        $check_store_create_contract = $this->check_store_create_contract_digital($contractInfo['store']['id']);
        if ($check_store_create_contract) {
            if (!empty($contractInfo['customer_infor']['type_contract_sign']) && $contractInfo['customer_infor']['type_contract_sign'] == 1) {
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
                $conditon_check_file['status_approve'] = $status_approve;
                $contractInfo['status_approve'] = $status_approve;
                // Tạo file docx thỏa thuận ba bên và biên bản bàn giao
                $create_docx_file = $this->create_contract_docx_file($contractInfo);
            }
        }
        echo "DONE";
    }

    /**
    * Setup data before call api PTI
    * @param Contract $data
    * @return Object
    */
    public function insert_pti_bhtn($data)
    {
        log_message('info', 'insert_pti_bhtn ' . json_encode($data));
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $codeContract = !empty($data['code_contract']) ? $data['code_contract'] : '';
        $fullname = !empty($data['customer_infor']['customer_name']) ? $data['customer_infor']['customer_name'] : '';
        $birthday = !empty($data['customer_infor']['customer_BOD']) ? $data['customer_infor']['customer_BOD'] : '';
        $cmt = !empty($data['customer_infor']['customer_identify']) ? $data['customer_infor']['customer_identify'] : '';
        $email = !empty($data['customer_infor']['customer_email']) ? $data['customer_infor']['customer_email'] : '';
        $phone = !empty($data['customer_infor']['customer_phone_number']) ? $data['customer_infor']['customer_phone_number'] : '';
        $address = !empty($data['current_address']) ? $data['current_address']['ward_name'] . ' - ' . $data['current_address']['district_name'] . ' - ' . $data['current_address']['province_name'] : '';
        $goi = !empty($data['loan_infor']['pti_bhtn']['goi']) ? $data['loan_infor']['pti_bhtn']['goi'] : '';
        $price = !empty($data['loan_infor']['pti_bhtn']['price']) ? $data['loan_infor']['pti_bhtn']['price'] : '';
        $phi = !empty($data['loan_infor']['pti_bhtn']['phi']) ? $data['loan_infor']['pti_bhtn']['phi'] : '';
        $ngay_kt_old = $this->pti_bhtn_model->findNgayKTByCCCD($cmt);
        if ($ngay_kt_old && strtotime($ngay_kt_old) > strtotime(date("d-m-Y"))) {
            $ngayHL = date('d-m-Y', strtotime($ngay_kt_old . ' + 1 day'));
        } else {
            $ngayHL = date("d-m-Y", strtotime('tomorrow'));
        }
        $ngayKT = date('d-m-Y', strtotime($ngayHL . ' +1 year -1 day'));
        $ngaySinh = date('d-m-Y', strtotime($birthday));
        $dt_pti = [
            "code_contract" => $codeContract,
            "ten" => mb_strtoupper($fullname, 'UTF-8'),
            "dchi" => $address,
            "so_cmt" => $cmt,
            "phone" => $phone,
            "ngay_sinh" => $ngaySinh,
            "tien_bh" => $price,
            "phi" => $phi,
            "email" => $email,
            "ttoan" => $phi,
            "ngay_hl" => $ngayHL,
            "ngay_kt" => $ngayKT,
            "goi" => $goi,
        ];

        $baohiem = new BaoHiemPTI();
        $res = $baohiem->call_bhtn_api($dt_pti);
        log_message('info', 'insert_pti_bhtn res' . json_encode($res));
        if (!empty($res)) {
            if ($res['status'] == 200) {
                $dt_re = array(
                    'info' => $res["data"],
                    'success' => true,
                    'request' => $dt_pti,
                );
                return (object)$dt_re;

            } else {
                $dt_re = array(
                    'info' => $res["data"],
                    'success' => false,
                    'request' => $dt_pti,
                );
                return (object)$dt_re;
            }
        } else {
            $dt_re = array(
                'info' => [
                    'message' => 'Kết nối thất bại!',
                ],
                'success' => false,
                'request' => $dt_pti,
            );
            return (object)$dt_re;
        }
    }

	public function getGroupRole_gdv()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'giao-dich-vien'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						array_push($arr, $key);
					}

				}
			}
		}
		return $arr;
	}
	public function getGroupRole_cht()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'cua-hang-truong'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						array_push($arr, $key);
					}

				}
			}
		}
		return $arr;
	}
}


?>
