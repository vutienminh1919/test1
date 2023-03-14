<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Gic_plt_bn extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('gic_plt_bn_model');
		$this->load->model('log_gic_model');
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("transaction_model");
		$this->load->model("city_gic_model");
		$this->load->model("config_gic_model");
		 $this->load->helper('lead_helper');
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

	public function insert_gic_plt_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$id_pgd = !empty($data['id_pgd']) ? $data['id_pgd'] : '';
		$effective_time = !empty($data['effective_time']) ? $this->security->xss_clean($data['effective_time']) : '';
		
	
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

        $ma_cty = $this->check_store_tcv_dong_bac($id_pgd);
        $data['company_code']=$ma_cty;
		$store = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_pgd)]);
		$type_gic = "GIC_PLT_BN";
	
			$NGAY_HL = date('Y-m-d H:i:s', strtotime("+1 days"));
			$NGAY_KT ="";
		
		$code = $type_gic.'_' . date("dmY") . "_" . time();
		$gic_plt = $this->insert_gic_plt($data, $NGAY_HL, $NGAY_KT,$type_gic,$code);
	
		if ($gic_plt->success != true) {
			$mes = $gic_plt->message;
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $mes
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$gic = $gic_plt->data;
			$dt_gic=[
                      'soHopDong' => $gic->thongTinChung_SoHopDong,
				      'StatusCode' => "CONTRACT_STATUS_APPROVED",
				      'StatusId' => "566E72CE-FB1A-456E-B337-B968AE47F0CC"
						];
			$res = $this->push_api_gci('ChangeProductDetailStatus', '', json_encode($dt_gic));
			if(!empty($res->success) && $res->success==true)
			{
			$dt_gic = array(
				'type_gic' => $type_gic
				, 'gic_code' => $gic->thongTinChung_SoHopDong
				, 'gic_id' => $gic->id
				, 'gic_info' => $gic,
				'NGAY_HL' => $NGAY_HL,
				'NGAY_KT' => $NGAY_KT,
				'price' => $data['price'],
				'customer_info' => [
					'customer_name' => !empty($data['ten_kh']) ? $this->security->xss_clean($data['ten_kh']) : '',
					'customer_phone' => !empty($data['phone']) ? $this->security->xss_clean((string)$data['phone']) : '',
					'card' => !empty($data['cmt']) ? $this->security->xss_clean($data['cmt']) : '',
					'email' => !empty($data['mail']) ? $this->security->xss_clean($data['mail']) : '',
					'birthday' => !empty($data['ngay_sinh']) ? $this->security->xss_clean(($data['ngay_sinh'])) : '',
					'address' => !empty($data['address']) ? $this->security->xss_clean(($data['address'])) : '',
				],
				'request'=>$data,
				'response'=>$res,
				'store' => [
					'id' => (string)$store['_id'],
					'name' => $store['name']
				],
				'status' => 10,
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail
				,'company_code' => $ma_cty
			);
			$this->gic_plt_bn_model->insert($dt_gic);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Bán bảo hiểm thành công!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		   }else{
		   	    $response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Bán bảo hiểm không thành công!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;

		   }
		}

	}



	public function insert_gic_plt($data, $NGAY_HL, $NGAY_KT,$type,$code)
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$branch_id_gic = $this->config->item("branch_id_gic");
		$city = $this->city_gic_model->findOne(array('code' => 'GIC'));
		$config = $this->config_gic_model->findOne(array('code' => 'TN_BHPLT'));
		$NoiDungBaoHiem_SoHdTinDungKv = (!empty($code_contract)) ? $code_contract : "";
		$TyLeKhoanVay = (!empty($config['TyLeKhoanVay'])) ? $config['TyLeKhoanVay'] : 0;
		$LoaiNguoiThuHuongId = (!empty($config['LoaiNguoiThuHuongId'])) ? $config['LoaiNguoiThuHuongId'] : '';
		$code_gic = (!empty($config['code'])) ? $config['code'] : "";
		$GiaTriKhoanVay = (!empty($data['price'])) ? $data['price'] : 0;

		$NgayYeuCauBh = $NGAY_HL;
		$NgayHieuLucBaoHiem = $NGAY_HL;
		$NgayHieuLucBaoHiemDen = date('Y-m-d H:i:s', strtotime($NgayHieuLucBaoHiem . ' + 1 year'));
       $company_code = !empty($data['company_code']) ? $data['company_code'] : '';
		$customer_name = (!empty($data['ten_kh'])) ? $data['ten_kh'] : '';
		$customer_BOD = (!empty($data['ngay_sinh'])) ? date("Y-m-d",$data['ngay_sinh']) : '';
		$customer_identify = (!empty($data['cmt'])) ? $data['cmt'] : '';
	
		
		$current_address = (!empty($data['address'])) ? $data['address'] : '.....';
		
		$province_current = (!empty($data['province_name'])) ? $data['province_name'] : '';
		$district_current = (!empty($data['district_name'])) ? $data['district_name'] : '';
		$customer_phone_number = (!empty($data['phone'])) ? $data['phone'] : '';
		$customer_email = (!empty($data['mail'])) ? $data['mail'] : '';
		$customer_gender = (!empty($data['gender'])) ? $data['gender'] : '1';
		$customer_gender = ($customer_gender == '1') ? 'dbb6424f-3890-4108-a094-3a17884885f3' : '27541417-9bf3-4b96-8bd2-edb4b8cf352a';
		$ProvinceId = '5c3b316f-91ad-46fc-a26c-8331be3b7739';
		$DistrictId = '5c3b316f-91ad-46fc-a26c-8331be3b7739';
		$ProvinceId_current = '5c3b316f-91ad-46fc-a26c-8331be3b7739';
		$DistrictId_current = '5c3b316f-91ad-46fc-a26c-8331be3b7739';

		$amount_GIC_plt = (!empty($data['code_GIC_plt'])) ? money_gic_plt($data['code_GIC_plt']) : 0;
		$code_GIC_plt = (!empty($data['code_GIC_plt'])) ? $data['code_GIC_plt'] : "";
		if (!empty($city['city'])) {
			foreach ($city['city'] as $key => $value) {

			
				if (slugify($value['name']) == slugify($province_current)) {
					$ProvinceId_current = $value['id'];
				}
			}
		}
		$name = "";
		if (!empty($city['district'])) {

			foreach ($city['district'] as $key => $value) {
				$name =slugify(str_replace("Huyện ", "", $value->name));
				$name = slugify(str_replace("thi-xa-", "", $name));
				$name = slugify(str_replace("thanh-pho-", "", $name));
				$name =slugify(str_replace("quan-", "", $name));

				
				if (slugify($name) == slugify($district_current)) {
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
				$this->log_gic('Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 1', $r, $type, $code);
				$dt_re = array(
					'message' => "Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 1",
					'success' => false
				);
				return json_decode(json_encode($dt_re));
			}

		} else {
			$this->log_gic('Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 2', $r, $type, $code);
			$dt_re = array(
				'message' => "Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 2",
				'success' => false
			);
			return json_decode(json_encode($dt_re));
		}
		if ($code_GIC_plt == "" || !in_array($code_GIC_plt, array("SILVER", "COPPER", "GOLD", FALSE))) {
			$this->log_gic('Kết nối đến GIC bị lỗi, không lấy được ID GIC PLT ! 3', $r, $type, $code);
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
		// 		'message'=>$dt_gic,
		// 		'success'=>false
		// 	);
		// 	return  json_decode(json_encode($dt_re));
		$res = $this->push_api_gci('SaveProductDetail_Code', '', json_encode($dt_gic));
		$type_gic = $code_gic;
		$this->log_gic(json_encode($dt_gic), $res, $type, $code);
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
   public function log_gic($request, $data, $code, $type)
	{

		$dataInser = array(
			"type" => $type,
			"code" => $code,
			"res_data" => $data,
			"request_data" => $request,
			"created_at" => $this->createdAt
		);
		$this->log_gic_model->insert($dataInser);
	}
	public function get_price_gic_plt_post()
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

	public function get_list_gic_plt_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$tab = !empty($data['tab']) ? $data['tab'] : 'gic_plt';
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';

		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : '';
		$customer_phone = !empty($data['customer_phone']) ? $this->security->xss_clean($data['customer_phone']) : '';
		$code = !empty($data['code']) ? $this->security->xss_clean($data['code']) : '';
		$code_gic_plt = !empty($data['code_gic_plt']) ? $this->security->xss_clean($data['code_gic_plt']) : '';
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
		if (!empty($code_gic_plt)) {
			$condition['code_gic_plt'] = $code_gic_plt;
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
		if ($tab == 'gic_plt') {
			$result = $this->gic_plt_bn_model->get_list_gic_plt($condition, $per_page, $uriSegment);
			$total = $this->gic_plt_bn_model->get_count_gic_plt($condition);
		} else {
			$result = $this->transaction_model->list_transaction_gic_plt($condition, $per_page, $uriSegment);
			$total = $this->transaction_model->total_list_transaction_gic_plt($condition);
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

	public function statistical_gic_plt_post()
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
			$data = $this->get_gic_store_post($condition);
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

	public function get_gic_store_post($condition)
	{
		$data = [];
		$mics = $this->gic_plt_bn_model->get_list_gic_store($condition);
		if (!empty($mics)) {
			$sum_price = 0;
			foreach ($mics as $mic) {
				$sum_price += $mic['price'];
			}
			$data['price'] = !empty($sum_price) ? ($sum_price) : 0;
			$data['total_transaction'] = count($mics);
		}
		return $data;

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

	public function detail_gic_plt_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$data = $this->gic_plt_bn_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
			'message' => "thành công!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
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

	public function get_gic_plt_accounting_transfe_post()
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
		$mic = $this->gic_plt_bn_model->get_gic_plt_accounting_transfe($condition);
		$total_money = 0;
		foreach ($mic as $value) {
			$total_money += (int)$value['price'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic,
			"total_money" => $total_money
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
			$mic = $this->gic_plt_bn_model->findOne(['gic_code' => $value]);
			$total += (int)$mic['price'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'total' => number_format($total) . " VND",
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function create_transaction_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code_gic = !empty($data['code']) ? $data['code'] : '';
		$store_id = !empty($data['store']) ? $data['store'] : '';
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
		if (empty($code_gic)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không có dữ liệu gửi sang kế toán"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$code = "PT_" . date('dmY') . '_' . uniqid();
		$money = 0;
		foreach ($code_gic as $value) {
			$gic_plt = $this->gic_plt_bn_model->findOne(['gic_code' => $value]);
			$this->gic_plt_bn_model->update(['_id' => $gic_plt['_id']], ['receipt_code' => $code, 'status' => 2]);
			$money += (int)$gic_plt['price'];
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
			'type' => 14,
			'status' => 2,
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
		$mic = $this->gic_plt_bn_model->find_where(['receipt_code' => $code]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic
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
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'date' => $NGAY_KT,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
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

	public function find_by_select($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$where['customer_info.customer_phone'] = $condition['lead_phone'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->select(['status'])
			->get($this->collection);
	}

}
