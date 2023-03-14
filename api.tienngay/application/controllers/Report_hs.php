<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Report_hs extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model("report_kpi_model");
		$this->load->model("contract_model");
		$this->load->model("lead_model");
		$this->load->model("main_property_model");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->flag_login = 1;
		$this->superadmin = false;
		$this->dataPost = $this->input->post();
		unset($this->dataPost['type']);

	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;


	public function report_hs_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : date('Y-m-01');
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : date('Y-m-d');
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'$gte' => strtotime(trim($start) . ' 00:00:00'),
				'$lte' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		$contract = new Contract_model();

		//Tổng hồ sơ
		$data_report['total_hs']['LTN71'] = $contract->count(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]), 'created_at' => $condition]);
		$data_report['total_hs']['TC494'] = $contract->count(['store.name' => array('$in' => ["494 Trần Cung"]), 'created_at' => $condition]);
		$data_report['total_hs']['VP26'] = $contract->count(['store.name' => array('$in' => ["26 Vạn Phúc"]), 'created_at' => $condition]);
		$data_report['total_hs']['XĐ264'] = $contract->count(['store.name' => array('$in' => ["264 Xã Đàn"]), 'created_at' => $condition]);
		$data_report['total_hs']['PHI28'] = $contract->count(['store.name' => array('$in' => ["28 Phan Huy Ích"]), 'created_at' => $condition]);
		$data_report['total_hs']['LN44'] = $contract->count(['store.name' => array('$in' => ["44 Lĩnh Nam"]), 'created_at' => $condition]);
		$data_report['total_hs']['MĐ01'] = $contract->count(['store.name' => array('$in' => ["01 Mỹ Đình"]), 'created_at' => $condition]);
		$data_report['total_hs']['LT48'] = $contract->count(['store.name' => array('$in' => ["48 La Thành"]), 'created_at' => $condition]);
		$data_report['total_hs']['PTT310'] = $contract->count(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]), 'created_at' => $condition]);
		$data_report['total_hs']['NTH30'] = $contract->count(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]), 'created_at' => $condition]);
		$data_report['total_hs']['NT81'] = $contract->count(['store.name' => array('$in' => ["81 Nguyễn Trãi"]), 'created_at' => $condition]);
		$data_report['total_hs']['XĐ518'] = $contract->count(['store.name' => array('$in' => ["518 Xã Đàn"]), 'created_at' => $condition]);
		$data_report['total_hs']['HĐ79'] = $contract->count(['store.name' => array('$in' => ["79 Hưng Đạo"]), 'created_at' => $condition]);
		$data_report['total_hs']['NGT281'] = $contract->count(['store.name' => array('$in' => ["281 Ngô Gia Tự"]), 'created_at' => $condition]);
		$data_report['total_hs']['GP901'] = $contract->count(['store.name' => array('$in' => ["901 Giải Phóng"]), 'created_at' => $condition]);

		$data_report['total_hs']['NS316'] = $contract->count(['store.name' => array('$in' => ["316 Nguyễn Sơn"]), 'created_at' => $condition]);
		$data_report['total_hs']['NVK550'] = $contract->count(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]), 'created_at' => $condition]);
		$data_report['total_hs']['PĐL138'] = $contract->count(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]), 'created_at' => $condition]);
		$data_report['total_hs']['BT286'] = $contract->count(['store.name' => array('$in' => ["286 Bình Tiên"]), 'created_at' => $condition]);
		$data_report['total_hs']['AC267'] = $contract->count(['store.name' => array('$in' => ["267 Âu Cơ"]), 'created_at' => $condition]);
		$data_report['total_hs']['HB131'] = $contract->count(['store.name' => array('$in' => ["131 Hiệp Bình"]), 'created_at' => $condition]);
		$data_report['total_hs']['CMT8'] = $contract->count(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]), 'created_at' => $condition]);
		$data_report['total_hs']['LBH81'] = $contract->count(['store.name' => array('$in' => ["81 Liêu Bình Hương"]), 'created_at' => $condition]);
		$data_report['total_hs']['ĐXH28'] = $contract->count(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]), 'created_at' => $condition]);
		$data_report['total_hs']['NAN246'] = $contract->count(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]), 'created_at' => $condition]);
		$data_report['total_hs']['LVV133'] = $contract->count(['store.name' => array('$in' => ["133 Lê Văn Việt"]), 'created_at' => $condition]);
		$data_report['total_hs']['LVK662'] = $contract->count(['store.name' => array('$in' => ["662 Lê Văn Khương"]), 'created_at' => $condition]);
		$data_report['total_hs']['PVH21A'] = $contract->count(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]), 'created_at' => $condition]);


		$data_report['total_hs']['Đ26T3'] = $contract->count(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]), 'created_at' => $condition]);
		$data_report['total_hs']['THĐ1797'] = $contract->count(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]), 'created_at' => $condition]);
		$data_report['total_hs']['Đ304308'] = $contract->count(['store.name' => array('$in' => ["308 Đường 30/4"]), 'created_at' => $condition]);

		//Tổng giải ngân
		$data_report['sum_tgn']['LTN71'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"71 Lê Thanh Nghị",'created_at' => $condition ], '$loan_infor.amount_money');
		$data_report['sum_tgn']['TC494'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"494 Trần Cung",'created_at' => $condition ], '$loan_infor.amount_money');
		$data_report['sum_tgn']['VP26'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"26 Vạn Phúc",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['XĐ264'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"264 Xã Đàn",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['PHI28'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"28 Phan Huy Ích",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['LN44'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"44 Lĩnh Nam",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['MĐ01'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"01 Mỹ Đình",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['LT48'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"48 La Thành",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['PTT310'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"310 Phan Trọng Tuệ",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['NTH30'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"30 Nguyễn Thái Học",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['NT81'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"81 Nguyễn Trãi",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['XĐ518'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"518 Xã Đàn",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['HĐ79'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"79 Hưng Đạo",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['NGT281'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"281 Ngô Gia Tự",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['GP901'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"901 Giải Phóng",'created_at' => $condition], '$loan_infor.amount_money');

		$data_report['sum_tgn']['NS316'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"316 Nguyễn Sơn",'created_at' => $condition ], '$loan_infor.amount_money');
		$data_report['sum_tgn']['NVK550'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"550 Nguyễn Văn Khối",'created_at' => $condition ], '$loan_infor.amount_money');
		$data_report['sum_tgn']['PĐL138'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"138 Phan Đăng Lưu",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['BT286'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"286 Bình Tiên",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['AC267'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"267 Âu Cơ",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['HB131'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"131 Hiệp Bình",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['CMT8'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"412 Cách Mạng Tháng 8",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['LBH81'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"81 Liêu Bình Hương",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['ĐXH28'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"28 Đỗ Xuân Hợp",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['NAN246'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"246 Nguyễn An Ninh",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['LVV133'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"133 Lê Văn Việt",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['LVK662'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"662 Lê Văn Khương",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['PVH21A'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"2/1A Phan Văn Hớn",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['Đ26T3'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"63 Đường 26 tháng 3",'created_at' => $condition], '$loan_infor.amount_money');


		$data_report['sum_tgn']['THĐ1797'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"1797 Trần Hưng Đạo",'created_at' => $condition], '$loan_infor.amount_money');
		$data_report['sum_tgn']['Đ304308'] = $contract->sum_where_total_amount(["status"=>17, "store.name"=>"308 Đường 30/4",'created_at' => $condition], '$loan_infor.amount_money');
		//Tổng trả về
		$data_report['total_return']['LTN71'] = $contract->count(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]), "status"=>8 ,'created_at' => $condition]);
		$data_report['total_return']['TC494'] = $contract->count(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['VP26'] = $contract->count(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['XĐ264'] = $contract->count(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['PHI28'] = $contract->count(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['LN44'] = $contract->count(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['MĐ01'] = $contract->count(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['LT48'] = $contract->count(['store.name' => array('$in' => ["48 La Thành"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['PTT310'] = $contract->count(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['NTH30'] = $contract->count(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['NT81'] = $contract->count(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['XĐ518'] = $contract->count(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['HĐ79'] = $contract->count(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['NGT281'] = $contract->count(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_return']['GP901'] = $contract->count(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>8 , 'created_at' => $condition]);

		$data_report['total_hs']['NS316'] = $contract->count(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['NVK550'] = $contract->count(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['PĐL138'] = $contract->count(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['BT286'] = $contract->count(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['AC267'] = $contract->count(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['HB131'] = $contract->count(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['CMT8'] = $contract->count(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['LBH81'] = $contract->count(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['ĐXH28'] = $contract->count(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['NAN246'] = $contract->count(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['LVV133'] = $contract->count(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['LVK662'] = $contract->count(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['PVH21A'] = $contract->count(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['Đ26T3'] = $contract->count(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>8 , 'created_at' => $condition]);

		$data_report['total_hs']['THĐ1797'] = $contract->count(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>8 , 'created_at' => $condition]);
		$data_report['total_hs']['Đ304308'] = $contract->count(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>8 , 'created_at' => $condition]);

		//Tổng hủy
		$data_report['total_cancel']['LTN71'] = $contract->count(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]), "status"=>3 ,'created_at' => $condition]);
		$data_report['total_cancel']['TC494'] = $contract->count(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['VP26'] = $contract->count(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['XĐ264'] = $contract->count(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['PHI28'] = $contract->count(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['LN44'] = $contract->count(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['MĐ01'] = $contract->count(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['LT48'] = $contract->count(['store.name' => array('$in' => ["48 La Thành"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['PTT310'] = $contract->count(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['NTH30'] = $contract->count(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['NT81'] = $contract->count(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['XĐ518'] = $contract->count(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['HĐ79'] = $contract->count(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['NGT281'] = $contract->count(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_cancel']['GP901'] = $contract->count(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>3 , 'created_at' => $condition]);

		$data_report['total_hs']['NS316'] = $contract->count(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['NVK550'] = $contract->count(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['PĐL138'] = $contract->count(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['BT286'] = $contract->count(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['AC267'] = $contract->count(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['HB131'] = $contract->count(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['CMT8'] = $contract->count(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['LBH81'] = $contract->count(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['ĐXH28'] = $contract->count(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['NAN246'] = $contract->count(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['LVV133'] = $contract->count(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['LVK662'] = $contract->count(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['PVH21A'] = $contract->count(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>3 , 'created_at' => $condition]);

		$data_report['total_hs']['Đ26T3'] = $contract->count(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['THĐ1797'] = $contract->count(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>3 , 'created_at' => $condition]);
		$data_report['total_hs']['Đ304308'] = $contract->count(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>3 , 'created_at' => $condition]);

		//Tổng phê duyệt
		$data_report['total_approval']['LTN71'] = $contract->count(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]), 'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) ,'created_at' => $condition]);
		$data_report['total_approval']['TC494'] = $contract->count(['store.name' => array('$in' => ["494 Trần Cung"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['VP26'] = $contract->count(['store.name' => array('$in' => ["26 Vạn Phúc"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['XĐ264'] = $contract->count(['store.name' => array('$in' => ["264 Xã Đàn"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['PHI28'] = $contract->count(['store.name' => array('$in' => ["28 Phan Huy Ích"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['LN44'] = $contract->count(['store.name' => array('$in' => ["44 Lĩnh Nam"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['MĐ01'] = $contract->count(['store.name' => array('$in' => ["01 Mỹ Đình"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['LT48'] = $contract->count(['store.name' => array('$in' => ["48 La Thành"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['PTT310'] = $contract->count(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['NTH30'] = $contract->count(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['NT81'] = $contract->count(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['XĐ518'] = $contract->count(['store.name' => array('$in' => ["518 Xã Đàn"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['HĐ79'] = $contract->count(['store.name' => array('$in' => ["79 Hưng Đạo"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['NGT281'] = $contract->count(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_approval']['GP901'] = $contract->count(['store.name' => array('$in' => ["901 Giải Phóng"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);

		$data_report['total_hs']['NS316'] = $contract->count(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['NVK550'] = $contract->count(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['PĐL138'] = $contract->count(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['BT286'] = $contract->count(['store.name' => array('$in' => ["286 Bình Tiên"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['AC267'] = $contract->count(['store.name' => array('$in' => ["267 Âu Cơ"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['HB131'] = $contract->count(['store.name' => array('$in' => ["131 Hiệp Bình"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['CMT8'] = $contract->count(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['LBH81'] = $contract->count(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['ĐXH28'] = $contract->count(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['NAN246'] = $contract->count(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['LVV133'] = $contract->count(['store.name' => array('$in' => ["133 Lê Văn Việt"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['LVK662'] = $contract->count(['store.name' => array('$in' => ["662 Lê Văn Khương"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['PVH21A'] = $contract->count(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['Đ26T3'] = $contract->count(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);

		$data_report['total_hs']['THĐ1797'] = $contract->count(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		$data_report['total_hs']['Đ304308'] = $contract->count(['store.name' => array('$in' => ["308 Đường 30/4"]),'status' => array('$in' => [6,7,9,10,15,16,17,18,19]) , 'created_at' => $condition]);
		//Tổng ngoại lệ E1
		$countE1 = 0;
		$data_report['exception_e1']['LTN71'] = $contract->find_where(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['LTN71'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['LTN71'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['TC494'] = $contract->find_where(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['TC494'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['TC494'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['VP26'] = $contract->find_where(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['VP26'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['VP26'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['XĐ264'] = $contract->find_where(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['XĐ264'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['XĐ264'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['PHI28'] = $contract->find_where(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['PHI28'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['PHI28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['LN44'] = $contract->find_where(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['LN44'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['LN44'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['MĐ01'] = $contract->find_where(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['MĐ01'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['MĐ01'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['LT48'] = $contract->find_where(['store.name' => array('$in' => ["48 La Thành"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['LT48'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['LT48'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['PTT310'] = $contract->find_where(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['PTT310'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['PTT310'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['NTH30'] = $contract->find_where(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['NTH30'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['NTH30'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['NT81'] = $contract->find_where(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['NT81'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['NT81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['XĐ518'] = $contract->find_where(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['XĐ518'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['XĐ518'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['HĐ79'] = $contract->find_where(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['HĐ79'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['HĐ79'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['NGT281'] = $contract->find_where(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['NGT281'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['NGT281'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['GP901'] = $contract->find_where(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['GP901'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['GP901'] = $countE1;



		$countE1 = 0;
		$data_report['exception_e1']['NS316'] = $contract->find_where(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['NS316'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['NS316'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['NVK550'] = $contract->find_where(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['NVK550'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['NVK550'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['PĐL138'] = $contract->find_where(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['PĐL138'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['PĐL138'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['BT286'] = $contract->find_where(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['BT286'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['BT286'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['AC267'] = $contract->find_where(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['AC267'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['AC267'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['HB131'] = $contract->find_where(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['HB131'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['HB131'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['CMT8'] = $contract->find_where(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['CMT8'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['CMT8'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['LBH81'] = $contract->find_where(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['LBH81'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['LBH81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['ĐXH28'] = $contract->find_where(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['ĐXH28'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['ĐXH28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['NAN246'] = $contract->find_where(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['NAN246'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['NAN246'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['LVV133'] = $contract->find_where(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['LVV133'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['LVV133'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['LVK662'] = $contract->find_where(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['LVK662'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['LVK662'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['PVH21A'] = $contract->find_where(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['PVH21A'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['PVH21A'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['Đ26T3'] = $contract->find_where(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['Đ26T3'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['Đ26T3'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['THĐ1797'] = $contract->find_where(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['THĐ1797'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['THĐ1797'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['Đ304308'] = $contract->find_where(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['Đ304308'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e1']['Đ304308'] = $countE1;

		//Tổng ngoại lệ E2
		$countE1 = 0;
		$data_report['exception_e2']['LTN71'] = $contract->find_where(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['LTN71'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['LTN71'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['TC494'] = $contract->find_where(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['TC494'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['TC494'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['VP26'] = $contract->find_where(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['VP26'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['VP26'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['XĐ264'] = $contract->find_where(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['XĐ264'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['XĐ264'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['PHI28'] = $contract->find_where(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['PHI28'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['PHI28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e1']['LN44'] = $contract->find_where(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e1']['LN44'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['LN44'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['MĐ01'] = $contract->find_where(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['MĐ01'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['MĐ01'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['LT48'] = $contract->find_where(['store.name' => array('$in' => ["48 La Thành"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['LT48'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['LT48'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['PTT310'] = $contract->find_where(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['PTT310'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['PTT310'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['NTH30'] = $contract->find_where(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['NTH30'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['NTH30'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['NT81'] = $contract->find_where(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['NT81'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['NT81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['XĐ518'] = $contract->find_where(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['XĐ518'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['XĐ518'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['HĐ79'] = $contract->find_where(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['HĐ79'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['HĐ79'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['NGT281'] = $contract->find_where(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['NGT281'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['NGT281'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['GP901'] = $contract->find_where(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['GP901'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['GP901'] = $countE1;




		$countE1 = 0;
		$data_report['exception_e2']['NS316'] = $contract->find_where(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['NS316'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['NS316'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['NVK550'] = $contract->find_where(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['NVK550'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['NVK550'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['PĐL138'] = $contract->find_where(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['PĐL138'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['PĐL138'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['BT286'] = $contract->find_where(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['BT286'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['BT286'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['AC267'] = $contract->find_where(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['AC267'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['AC267'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['HB131'] = $contract->find_where(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['HB131'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['HB131'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['CMT8'] = $contract->find_where(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['CMT8'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['CMT8'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['LBH81'] = $contract->find_where(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['LBH81'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['LBH81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['ĐXH28'] = $contract->find_where(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['ĐXH28'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['ĐXH28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['NAN246'] = $contract->find_where(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['NAN246'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['NAN246'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['LVV133'] = $contract->find_where(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['LVV133'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['LVV133'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['LVK662'] = $contract->find_where(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['LVK662'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['LVK662'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['PVH21A'] = $contract->find_where(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['PVH21A'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['PVH21A'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['Đ26T3'] = $contract->find_where(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['Đ26T3'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['Đ26T3'] = $countE1;


		$countE1 = 0;
		$data_report['exception_e2']['THĐ1797'] = $contract->find_where(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['THĐ1797'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['THĐ1797'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e2']['Đ304308'] = $contract->find_where(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e2']['Đ304308'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e2']['Đ304308'] = $countE1;
		//Tổng ngoại lệ E3
		$countE1 = 0;
		$data_report['exception_e3']['LTN71'] = $contract->find_where(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['LTN71'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['LTN71'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['TC494'] = $contract->find_where(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['TC494'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['TC494'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['VP26'] = $contract->find_where(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['VP26'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['VP26'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['XĐ264'] = $contract->find_where(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['XĐ264'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['XĐ264'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['PHI28'] = $contract->find_where(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['PHI28'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['PHI28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['LN44'] = $contract->find_where(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['LN44'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['LN44'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['MĐ01'] = $contract->find_where(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['MĐ01'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['MĐ01'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['LT48'] = $contract->find_where(['store.name' => array('$in' => ["48 La Thành"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['LT48'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['LT48'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['PTT310'] = $contract->find_where(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['PTT310'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['PTT310'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['NTH30'] = $contract->find_where(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['NTH30'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['NTH30'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['NT81'] = $contract->find_where(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['NT81'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['NT81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['XĐ518'] = $contract->find_where(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['XĐ518'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['XĐ518'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['HĐ79'] = $contract->find_where(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['HĐ79'] as $value){
			if ($value['expertise_infor']['exception2_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['HĐ79'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['NGT281'] = $contract->find_where(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['NGT281'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['NGT281'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['GP901'] = $contract->find_where(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['GP901'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['GP901'] = $countE1;



		$countE1 = 0;
		$data_report['exception_e3']['NS316'] = $contract->find_where(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['NS316'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['NS316'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['NVK550'] = $contract->find_where(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['NVK550'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['NVK550'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['PĐL138'] = $contract->find_where(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['PĐL138'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['PĐL138'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['BT286'] = $contract->find_where(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['BT286'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['BT286'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['AC267'] = $contract->find_where(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['AC267'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['AC267'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['HB131'] = $contract->find_where(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['HB131'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['HB131'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['CMT8'] = $contract->find_where(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['CMT8'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['CMT8'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['LBH81'] = $contract->find_where(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['LBH81'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['LBH81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['ĐXH28'] = $contract->find_where(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['ĐXH28'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['ĐXH28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['NAN246'] = $contract->find_where(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['NAN246'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['NAN246'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['LVV133'] = $contract->find_where(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['LVV133'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['LVV133'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['LVK662'] = $contract->find_where(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['LVK662'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['LVK662'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['PVH21A'] = $contract->find_where(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['PVH21A'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['PVH21A'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['Đ26T3'] = $contract->find_where(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['Đ26T3'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['Đ26T3'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['THĐ1797'] = $contract->find_where(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['THĐ1797'] as $value){
			if ($value['expertise_infor']['exception3_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['THĐ1797'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e3']['Đ304308'] = $contract->find_where(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e3']['Đ304308'] as $value){
			if ($value['expertise_infor']['exception1_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e3']['Đ304308'] = $countE1;
		//Tỏng ngoại lệ E4
		$countE1 = 0;
		$data_report['exception_e4']['LTN71'] = $contract->find_where(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['LTN71'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['LTN71'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['TC494'] = $contract->find_where(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['TC494'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['TC494'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['VP26'] = $contract->find_where(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['VP26'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['VP26'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['XĐ264'] = $contract->find_where(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['XĐ264'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['XĐ264'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['PHI28'] = $contract->find_where(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['PHI28'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['PHI28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['LN44'] = $contract->find_where(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['LN44'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['LN44'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['MĐ01'] = $contract->find_where(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['MĐ01'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['MĐ01'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['LT48'] = $contract->find_where(['store.name' => array('$in' => ["48 La Thành"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['LT48'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['LT48'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['PTT310'] = $contract->find_where(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['PTT310'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['PTT310'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['NTH30'] = $contract->find_where(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['NTH30'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['NTH30'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['NT81'] = $contract->find_where(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['NT81'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['NT81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['XĐ518'] = $contract->find_where(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['XĐ518'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['XĐ518'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['HĐ79'] = $contract->find_where(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['HĐ79'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['HĐ79'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['NGT281'] = $contract->find_where(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['NGT281'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['NGT281'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['GP901'] = $contract->find_where(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['GP901'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['GP901'] = $countE1;




		$countE1 = 0;
		$data_report['exception_e4']['NS316'] = $contract->find_where(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['NS316'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['NS316'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['NVK550'] = $contract->find_where(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['NVK550'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['NVK550'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['PĐL138'] = $contract->find_where(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['PĐL138'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['PĐL138'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['BT286'] = $contract->find_where(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['BT286'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['BT286'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['AC267'] = $contract->find_where(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['AC267'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['AC267'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['HB131'] = $contract->find_where(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['HB131'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['HB131'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['CMT8'] = $contract->find_where(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['CMT8'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['CMT8'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['LBH81'] = $contract->find_where(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['LBH81'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['LBH81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['ĐXH28'] = $contract->find_where(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['ĐXH28'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['ĐXH28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['NAN246'] = $contract->find_where(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['NAN246'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['NAN246'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['LVV133'] = $contract->find_where(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['LVV133'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['LVV133'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['LVK662'] = $contract->find_where(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['LVK662'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['LVK662'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['PVH21A'] = $contract->find_where(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['PVH21A'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['PVH21A'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['Đ26T3'] = $contract->find_where(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['Đ26T3'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['Đ26T3'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['THĐ1797'] = $contract->find_where(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['THĐ1797'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['THĐ1797'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e4']['Đ304308'] = $contract->find_where(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e4']['Đ304308'] as $value){
			if ($value['expertise_infor']['exception4_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e4']['Đ304308'] = $countE1;
		//Tổng ngoại lệ E5
		$countE1 = 0;
		$data_report['exception_e5']['LTN71'] = $contract->find_where(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['LTN71'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['LTN71'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['TC494'] = $contract->find_where(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['TC494'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['TC494'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['VP26'] = $contract->find_where(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['VP26'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['VP26'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['XĐ264'] = $contract->find_where(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['XĐ264'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['XĐ264'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['PHI28'] = $contract->find_where(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['PHI28'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['PHI28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['LN44'] = $contract->find_where(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['LN44'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['LN44'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['MĐ01'] = $contract->find_where(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['MĐ01'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['MĐ01'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['LT48'] = $contract->find_where(['store.name' => array('$in' => ["48 La Thành"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['LT48'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['LT48'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['PTT310'] = $contract->find_where(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['PTT310'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['PTT310'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['NTH30'] = $contract->find_where(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['NTH30'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['NTH30'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['NT81'] = $contract->find_where(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['NT81'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['NT81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['XĐ518'] = $contract->find_where(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['XĐ518'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['XĐ518'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['HĐ79'] = $contract->find_where(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['HĐ79'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['HĐ79'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['NGT281'] = $contract->find_where(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['NGT281'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['NGT281'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['GP901'] = $contract->find_where(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['GP901'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['GP901'] = $countE1;




		$countE1 = 0;
		$data_report['exception_e5']['NS316'] = $contract->find_where(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['NS316'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['NS316'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['NVK550'] = $contract->find_where(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['NVK550'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['NVK550'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['PĐL138'] = $contract->find_where(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['PĐL138'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['PĐL138'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['BT286'] = $contract->find_where(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['BT286'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['BT286'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['AC267'] = $contract->find_where(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['AC267'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['AC267'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['HB131'] = $contract->find_where(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['HB131'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['HB131'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['CMT8'] = $contract->find_where(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['CMT8'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['CMT8'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['LBH81'] = $contract->find_where(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['LBH81'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['LBH81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['ĐXH28'] = $contract->find_where(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['ĐXH28'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['ĐXH28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['NAN246'] = $contract->find_where(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['NAN246'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['NAN246'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['LVV133'] = $contract->find_where(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['LVV133'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['LVV133'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['LVK662'] = $contract->find_where(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['LVK662'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['LVK662'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['PVH21A'] = $contract->find_where(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['PVH21A'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['PVH21A'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['Đ26T3'] = $contract->find_where(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['Đ26T3'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['Đ26T3'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['THĐ1797'] = $contract->find_where(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['THĐ1797'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['THĐ1797'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e5']['Đ304308'] = $contract->find_where(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e5']['Đ304308'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e5']['Đ304308'] = $countE1;
		//Tổng ngoại lệ E6
		$countE1 = 0;
		$data_report['exception_e6']['LTN71'] = $contract->find_where(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['LTN71'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['LTN71'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['TC494'] = $contract->find_where(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['TC494'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['TC494'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['VP26'] = $contract->find_where(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['VP26'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['VP26'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['XĐ264'] = $contract->find_where(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['XĐ264'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['XĐ264'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['PHI28'] = $contract->find_where(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['PHI28'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['PHI28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['LN44'] = $contract->find_where(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['LN44'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['LN44'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['MĐ01'] = $contract->find_where(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['MĐ01'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['MĐ01'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['LT48'] = $contract->find_where(['store.name' => array('$in' => ["48 La Thành"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['LT48'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['LT48'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['PTT310'] = $contract->find_where(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['PTT310'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['PTT310'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['NTH30'] = $contract->find_where(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['NTH30'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['NTH30'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['NT81'] = $contract->find_where(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['NT81'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['NT81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['XĐ518'] = $contract->find_where(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['XĐ518'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['XĐ518'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['HĐ79'] = $contract->find_where(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['HĐ79'] as $value){
			if ($value['expertise_infor']['exception5_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['HĐ79'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['NGT281'] = $contract->find_where(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['NGT281'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['NGT281'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['GP901'] = $contract->find_where(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['GP901'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['GP901'] = $countE1;




		$countE1 = 0;
		$data_report['exception_e6']['NS316'] = $contract->find_where(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['NS316'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['NS316'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['NVK550'] = $contract->find_where(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['NVK550'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['NVK550'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['PĐL138'] = $contract->find_where(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['PĐL138'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['PĐL138'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['BT286'] = $contract->find_where(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['BT286'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['BT286'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['AC267'] = $contract->find_where(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['AC267'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['AC267'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['HB131'] = $contract->find_where(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['HB131'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['HB131'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['CMT8'] = $contract->find_where(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['CMT8'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['CMT8'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['LBH81'] = $contract->find_where(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['LBH81'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['LBH81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['ĐXH28'] = $contract->find_where(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['ĐXH28'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['ĐXH28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['NAN246'] = $contract->find_where(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['NAN246'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['NAN246'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['LVV133'] = $contract->find_where(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['LVV133'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['LVV133'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['LVK662'] = $contract->find_where(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['LVK662'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['LVK662'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['PVH21A'] = $contract->find_where(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['PVH21A'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['PVH21A'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['Đ26T3'] = $contract->find_where(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['Đ26T3'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['Đ26T3'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['THĐ1797'] = $contract->find_where(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['THĐ1797'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['THĐ1797'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e6']['Đ304308'] = $contract->find_where(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e6']['Đ304308'] as $value){
			if ($value['expertise_infor']['exception6_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e6']['Đ304308'] = $countE1;
		//Tổng ngoại lệ E7
		$countE1 = 0;
		$data_report['exception_e7']['LTN71'] = $contract->find_where(['store.name' => array('$in' => ["71 Lê Thanh Nghị"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['LTN71'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['LTN71'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['TC494'] = $contract->find_where(['store.name' => array('$in' => ["494 Trần Cung"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['TC494'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['TC494'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['VP26'] = $contract->find_where(['store.name' => array('$in' => ["26 Vạn Phúc"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['VP26'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['VP26'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['XĐ264'] = $contract->find_where(['store.name' => array('$in' => ["264 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['XĐ264'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['XĐ264'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['PHI28'] = $contract->find_where(['store.name' => array('$in' => ["28 Phan Huy Ích"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['PHI28'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['PHI28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['LN44'] = $contract->find_where(['store.name' => array('$in' => ["44 Lĩnh Nam"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['LN44'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['LN44'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['MĐ01'] = $contract->find_where(['store.name' => array('$in' => ["01 Mỹ Đình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['MĐ01'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['MĐ01'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['LT48'] = $contract->find_where(['store.name' => array('$in' => ["48 La Thành"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['LT48'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['LT48'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['PTT310'] = $contract->find_where(['store.name' => array('$in' => ["310 Phan Trọng Tuệ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['PTT310'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['PTT310'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['NTH30'] = $contract->find_where(['store.name' => array('$in' => ["30 Nguyễn Thái Học"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['NTH30'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['NTH30'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['NT81'] = $contract->find_where(['store.name' => array('$in' => ["81 Nguyễn Trãi"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['NT81'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['NT81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['XĐ518'] = $contract->find_where(['store.name' => array('$in' => ["518 Xã Đàn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['XĐ518'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['XĐ518'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['HĐ79'] = $contract->find_where(['store.name' => array('$in' => ["79 Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['HĐ79'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['HĐ79'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['NGT281'] = $contract->find_where(['store.name' => array('$in' => ["281 Ngô Gia Tự"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['NGT281'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['NGT281'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['GP901'] = $contract->find_where(['store.name' => array('$in' => ["901 Giải Phóng"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['GP901'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['GP901'] = $countE1;




		$countE1 = 0;
		$data_report['exception_e7']['NS316'] = $contract->find_where(['store.name' => array('$in' => ["316 Nguyễn Sơn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['NS316'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['NS316'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['NVK550'] = $contract->find_where(['store.name' => array('$in' => ["550 Nguyễn Văn Khối"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['NVK550'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['NVK550'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['PĐL138'] = $contract->find_where(['store.name' => array('$in' => ["138 Phan Đăng Lưu"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['PĐL138'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['PĐL138'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['BT286'] = $contract->find_where(['store.name' => array('$in' => ["286 Bình Tiên"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['BT286'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['BT286'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['AC267'] = $contract->find_where(['store.name' => array('$in' => ["267 Âu Cơ"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['AC267'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['AC267'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['HB131'] = $contract->find_where(['store.name' => array('$in' => ["131 Hiệp Bình"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['HB131'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['HB131'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['CMT8'] = $contract->find_where(['store.name' => array('$in' => ["412 Cách Mạng Tháng 8"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['CMT8'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['CMT8'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['LBH81'] = $contract->find_where(['store.name' => array('$in' => ["81 Liêu Bình Hương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['LBH81'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['LBH81'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['ĐXH28'] = $contract->find_where(['store.name' => array('$in' => ["28 Đỗ Xuân Hợp"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['ĐXH28'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['ĐXH28'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['NAN246'] = $contract->find_where(['store.name' => array('$in' => ["246 Nguyễn An Ninh"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['NAN246'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['NAN246'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['LVV133'] = $contract->find_where(['store.name' => array('$in' => ["133 Lê Văn Việt"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['LVV133'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['LVV133'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['LVK662'] = $contract->find_where(['store.name' => array('$in' => ["662 Lê Văn Khương"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['LVK662'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['LVK662'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['PVH21A'] = $contract->find_where(['store.name' => array('$in' => ["2/1A Phan Văn Hớn"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['PVH21A'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['PVH21A'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['Đ26T3'] = $contract->find_where(['store.name' => array('$in' => ["63 Đường 26 tháng 3"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['Đ26T3'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['Đ26T3'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['THĐ1797'] = $contract->find_where(['store.name' => array('$in' => ["1797 Trần Hưng Đạo"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['THĐ1797'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['THĐ1797'] = $countE1;

		$countE1 = 0;
		$data_report['exception_e7']['Đ304308'] = $contract->find_where(['store.name' => array('$in' => ["308 Đường 30/4"]),"status"=>6,'created_at' => $condition]);
		foreach ($data_report['exception_e7']['Đ304308'] as $value){
			if ($value['expertise_infor']['exception7_value'] != ""){
				$countE1++;
			}
		}
		$data_report['exception_e7']['Đ304308'] = $countE1;
		//Giảm khoản vay




		if ($data_report) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $data_report,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function main_property_post(){

		$get_all = $this->main_property_model->find();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $get_all,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

}
