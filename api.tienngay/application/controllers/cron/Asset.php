<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Asset extends CI_Controller
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
		$this->load->model('device_model');
		$this->load->helper('lead_helper');
		$this->load->model('vbi_model');
		$this->load->model('log_hs_model');
		$this->load->model('log_call_debt_model');
		$this->load->model('asset_management_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$this->load->model('contract_tempo_model');
		$this->load->model('contract_debt_model');
	}

	public function asset_manager()
	{
		$contracts = $this->contract_debt_model->asset_contract();
		foreach ($contracts as $contract) {
			if ($contract['loan_infor']['type_property']['code'] == 'NĐ') {
				$thua_dat_so = !empty($contract['property_infor'][1]['value']) ? $contract['property_infor'][1]['value'] : '';
				$property = !empty($contract['property_infor']) ? $contract['property_infor'] : '';
				$loan_info = !empty($contract['loan_infor']) ? $contract['loan_infor'] : '';
				$created_by = !empty($contract['created_by']) ? $contract['created_by'] : $contract['updated_by'];
				$customer_name = !empty($contract['customer_infor']['customer_name']) ? $contract['customer_infor']['customer_name'] : '';
				$image_accurecy = !empty($contract['image_accurecy']) ? $contract['image_accurecy'] : '';

				if ($contract['status'] != 1) {
					if ( $this->check_asset_nhadat($thua_dat_so) ) {
						$asset_id = $this->get_asset_manager($customer_name, $property, $loan_info, $image_accurecy, $created_by);
						$asset = $this->asset_management_model->findOne(['_id' => $asset_id]);
						$this->contract_debt_model->update(['_id' => $contract['_id']], ['asset_code' => $asset['asset_code']]);
					} else {
						$asset = $this->asset_management_model->findOne(['thua_dat_so' => $thua_dat_so]);
						$this->contract_debt_model->update(['_id' => $contract['_id']], ['asset_code' => $asset['asset_code']]);
					}
				}
			} else {
				$so_khung = !empty($contract['property_infor'][3]['value']) ? $contract['property_infor'][3]['value'] : '';
				$so_may = !empty($contract['property_infor'][4]['value']) ? $contract['property_infor'][4]['value'] : '';
				$image_accurecy = !empty($contract['image_accurecy']) ? $contract['image_accurecy'] : '';
				$customer_name = !empty($contract['customer_infor']['customer_name']) ? $contract['customer_infor']['customer_name'] : '';
				$property = !empty($contract['property_infor']) ? $contract['property_infor'] : '';
				$loan_info = !empty($contract['loan_infor']) ? $contract['loan_infor'] : '';
				$created_by = !empty($contract['created_by']) ? $contract['created_by'] : $contract['updated_by'];
				if ($contract['loan_infor']['type_property']['code'] == 'TC') {
					if (!empty($image_accurecy['driver_license'])) {
						$asset_id = $this->get_asset_manager($customer_name, $property, $loan_info, $image_accurecy, $created_by);
						$asset = $this->asset_management_model->findOne(['_id' => $asset_id]);
						$this->contract_debt_model->update(['_id' => $contract['_id']], ['asset_code' => $asset['asset_code']]);
					}
				} else {
					if (empty($image_accurecy['driver_license']) && $contract['status'] == 1) {
						continue;
					}
					if (!empty($so_khung) && !empty($so_may)) {
						$result = $this->check_asset($so_khung, $so_may);
						if ($result === false) {
							continue;
						}
						$asset_id = $this->get_asset_manager($customer_name, $property, $loan_info, $image_accurecy, $created_by);
						$asset = $this->asset_management_model->findOne(['_id' => $asset_id]);
						$this->contract_debt_model->update(['_id' => $contract['_id']], ['asset_code' => $asset['asset_code']]);
					}
				}
			}
		}
		return 'ok';
	}

	private function initNumberAssetCode()
	{
		$maxNumber = $this->asset_management_model->getMaxNumberAsset();
		$maxNumberContract = !empty($maxNumber[0]['number_asset']) ? (int)$maxNumber[0]['number_asset'] + 1 : 1;
		return $maxNumberContract;
	}

	public function get_asset_manager($customer_name, $property, $loan_info, $image_accurecy, $createby)
	{
		foreach ($property as $value) {
			if ($value['slug'] == 'nhan-hieu') {
				$nhan_hieu = $value['value'];
			}
			if ($value['slug'] == 'model') {
				$model = $value['value'];
			}
			if ($value['slug'] == 'bien-so-xe') {
				$bien_so_xe = $value['value'];
			}
			if ($value['slug'] == 'so-khung') {
				$so_khung = strtoupper(trim($value['value']));
			}
			if ($value['slug'] == 'so-may') {
				$so_may = strtoupper(trim($value['value']));
			}
			if ($value['slug'] == 'ho-ten-chu-xe') {
				$customerName = $value['value'];
			}
			if ($value['slug'] == 'dia-chi-dang-ky') {
				$dia_chi = $value['value'];
			}
			if ($value['slug'] == 'so-dang-ky') {
				$so_dang_ki = $value['value'];
			}
			if ($value['slug'] == 'ngay-cap') {
				$ngay_cap = $value['value'];
			}
			if ($value['slug'] == 'ngay-cap-dang-ky') {
				$ngay_cap_dang_ki = $value['value'];
			}
			// Nhà đất
			if ($value['slug'] == 'thua-dat-so') {
				$thua_dat_so = $value['value'];
			}
			if ($value['slug'] == 'to-ban-do-so') {
				$to_ban_do_so = $value['value'];
			}
			if ($value['slug'] == 'dia-chi-thua-dat') {
				$dia_chi_thua_dat = $value['value'];
			}
			if ($value['slug'] == 'dien-tich-m2') {
				$dien_tich = $value['value'];
			}
			if ($value['slug'] == 'hinh-thuc-su-dung-rieng-m2') {
				$hinh_thuc_su_dung_rieng = $value['value'];
			}
			if ($value['slug'] == 'hinh-thuc-su-dung-chung-m2') {
				$hinh_thuc_su_dung_chung = $value['value'];
			}
			if ($value['slug'] == 'muc-dich-su-dung') {
				$muc_dich_su_dung = $value['value'];
			}
			if ($value['slug'] == 'thoi-han-su-dung') {
				$thoi_han_su_dung = $value['value'];
			}
			if ($value['slug'] == 'nha-o-neu-co') {
				$nha_o_neu_co = $value['value'];
			}
			if ($value['slug'] == 'giay-chung-nhan-so') {
				$giay_chung_nhan_so = $value['value'];
			}
			if ($value['slug'] == 'noi-cap') {
				$noi_cap = $value['value'];
			}
			if ($value['slug'] == 'ngay-cap') {
				$ngay_cap = $value['value'];
			}
			if ($value['slug'] == 'so-vao-so') {
				$so_vao_so = $value['value'];
			}
		}
		$type = !empty($loan_info['type_property']) ? $loan_info['type_property']['code'] : '';
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
		if ($type == 'NĐ') {
			$param = [
				'customer_name' => !empty($customerName) ? $customerName : $customer_name,
				'number_asset' => $number_asset,
				'asset_code' => $asset_code,
				'type' => $type,
				'product' => !empty($loan_info['name_property']) ? $loan_info['name_property']['text'] : '',
				'thua_dat_so' => !empty($thua_dat_so) ? $thua_dat_so : '',
				'to_ban_do_so' => !empty($to_ban_do_so) ? $to_ban_do_so : '',
				'dia_chi_thua_dat' => !empty($dia_chi_thua_dat) ? $dia_chi_thua_dat : '',
				'dien_tich' => !empty($dien_tich) ? $dien_tich : '',
				'hinh_thuc_su_dung_rieng' => !empty($hinh_thuc_su_dung_rieng) ? $hinh_thuc_su_dung_rieng : '',
				'hinh_thuc_su_dung_chung' => !empty($hinh_thuc_su_dung_chung) ? $hinh_thuc_su_dung_chung : '',
				'muc_dich_su_dung' => !empty($muc_dich_su_dung) ? $muc_dich_su_dung : '',
				'thoi_han_su_dung' => !empty($thoi_han_su_dung) ? $thoi_han_su_dung : '',
				'nha_o_neu_co' => !empty($nha_o_neu_co) ? $nha_o_neu_co : '',
				'giay_chung_nhan_so' => !empty($giay_chung_nhan_so) ? $giay_chung_nhan_so : '',
				'noi_cap' => !empty($noi_cap) ? $noi_cap : '',
				'ngay_cap' => strtotime($ngay_cap),
				'so_vao_so' => !empty($so_vao_so) ? $so_vao_so : '',
				'status' => 'active',
				'created_at' => $this->createdAt,
				'created_by' => $createby
			];
			$asset_id = $this->asset_management_model->insertReturnId($param);
		} else if ($type == 'TC') {
			$param = [
				'customer_name' => !empty($customerName) ? $customerName : $customer_name,
				'number_asset' => $number_asset,
				'asset_code' => $asset_code,
				'type' => $type,
				'product' => !empty($loan_info['name_property']) ? $loan_info['name_property']['text'] : '',
				'image' => !empty($image_accurecy['driver_license']) ? $image_accurecy['driver_license'] : '',
				'status' => 'active',
				'created_at' => $this->createdAt,
				'created_by' => $createby
			];
			$asset_id = $this->asset_management_model->insertReturnId($param);
		} else {
			$asset = $this->asset_management_model->findOne(['so_khung' => $so_khung, 'so_may' => $so_may, 'type' => $type]);
			if (!empty($asset)) {
				$image = !empty($asset['image']) ? $asset['image'] : [];
				$image1 = [];
				foreach ($image as $key => $value) {
					$image[$key] = $value;
				}
				$driver_license = [];
				foreach ($image_accurecy['driver_license'] as $k => $value) {
					$driver_license[$k] = $value;
				}
				if (!empty($ngay_cap) && empty($ngay_cap_dang_ki)) {
					$ngayCap = strtotime($ngay_cap);
				} elseif (empty($ngay_cap) && !empty($ngay_cap_dang_ki)) {
					if (strtotime($ngay_cap_dang_ki) === false) {
						$ngayCap = $ngay_cap_dang_ki;
					} else {
						$ngay_cap = strtotime($ngay_cap_dang_ki);
					}
				} else {
					$ngayCap = $asset['ngay_cap'];
				}
				$this->asset_management_model->update(
					['_id' => $asset['_id']],
					[
						'bien_so_xe' => !empty($bien_so_xe) ? strtoupper(trim(str_replace(array('.', '-', ' '), '', $bien_so_xe))) : $asset['bien_so_xe'],
						'updated_at' => $this->createdAt,
						'updated_by' => $createby,
						'image' => !empty($image_accurecy['driver_license']) ? (object)array_merge($driver_license, $image1) : $image,
						'product' => !empty($loan_info['name_property']) ? $loan_info['name_property']['text'] : '',
						'nhan_hieu' => !empty($nhan_hieu) ? $nhan_hieu : $asset['nhan_hieu'],
						'model' => !empty($model) ? $model : $asset['model'],
						'dia_chi' => !empty($dia_chi) ? $dia_chi : $asset['dia_chi'],
						"so_dang_ki" => !empty($so_dang_ki) ? $so_dang_ki : $asset['so_dang_ki'],
						'ngay_cap' => $ngayCap,
						'customer_name' => !empty($customerName) ? $customerName : $customer_name
					]);
				$asset_id = $asset['_id'];
			} else {
				if (!empty($ngay_cap) && empty($ngay_cap_dang_ki)) {
					$ngayCap = $ngay_cap;
				} elseif (empty($ngay_cap) && !empty($ngay_cap_dang_ki)) {
					$ngayCap = $ngay_cap_dang_ki;
				} else {
					$ngayCap = '';
				}
				$param = [
					'customer_name' => !empty($customerName) ? $customerName : $customer_name,
					'number_asset' => $number_asset,
					'asset_code' => $asset_code,
					'type' => $type,
					'product' => !empty($loan_info['name_property']) ? $loan_info['name_property']['text'] : '',
					'image' => !empty($image_accurecy['driver_license']) ? $image_accurecy['driver_license'] : '',
					'bien_so_xe' => !empty($bien_so_xe) ? strtoupper(trim(str_replace(array('.', '-', ' '), '', $bien_so_xe))) : '',
					'nhan_hieu' => !empty($nhan_hieu) ? $nhan_hieu : '',
					'model' => !empty($model) ? $model : '',
					'so_khung' => !empty($so_khung) ? $so_khung : '',
					'so_may' => !empty($so_may) ? $so_may : '',
					'dia_chi' => !empty($dia_chi) ? $dia_chi : '',
					"so_dang_ki" => !empty($so_dang_ki) ? $so_dang_ki : '',
					'ngay_cap' => $ngayCap,
					'status' => 'active',
					'created_at' => $this->createdAt,
					'created_by' => $createby
				];
				$asset_id = $this->asset_management_model->insertReturnId($param);
			}
		}
		return $asset_id;
	}

	public function asset_manager_test()
	{
		$contracts = $this->contract_debt_model->asset_contract_limit();
		foreach ($contracts as $contract) {
			$so_khung = !empty($contract['property_infor'][3]['value']) ? strtoupper(trim($contract['property_infor'][3]['value'])) : '';
			$so_may = !empty($contract['property_infor'][4]['value']) ? strtoupper(trim($contract['property_infor'][4]['value'])) : '';
			if (!empty($so_khung) && !empty($so_may)) {
				$customer_name = !empty($contract['customer_infor']['customer_name']) ? $contract['customer_infor']['customer_name'] : '';
				$property = !empty($contract['property_infor']) ? $contract['property_infor'] : '';
				$loan_info = !empty($contract['loan_infor']) ? $contract['loan_infor'] : '';
				$image_accurecy = !empty($contract['image_accurecy']) ? $contract['image_accurecy'] : '';
				$created_by = !empty($contract['created_by']) ? $contract['created_by'] : $contract['updated_by'];
				$result = $this->check_asset($so_khung, $so_may);
				if ($result === false) {
					continue;
				}
				$asset_id = $this->get_asset_manager($customer_name, $property, $loan_info, $image_accurecy, $created_by);
				$asset = $this->asset_management_model->findOne(['_id' => $asset_id]);
				$this->contract_debt_model->update(['_id' => $contract['_id']], ['asset_code' => $asset['asset_code']]);
			}
		}
		return 'ok';
	}

	public function asset_manager_update()
	{
		$contracts = $this->contract_debt_model->asset_contract_update();
		foreach ($contracts as $contract) {
			$so_khung = !empty($contract['property_infor'][3]['value']) ? strtoupper(trim($contract['property_infor'][3]['value'])) : '';
			$so_may = !empty($contract['property_infor'][4]['value']) ? strtoupper(trim($contract['property_infor'][4]['value'])) : '';
			if (!empty($so_khung) && !empty($so_may)) {
				$customer_name = !empty($contract['customer_infor']['customer_name']) ? $contract['customer_infor']['customer_name'] : '';
				$property = !empty($contract['property_infor']) ? $contract['property_infor'] : '';
				$loan_info = !empty($contract['loan_infor']) ? $contract['loan_infor'] : '';
				$image_accurecy = !empty($contract['image_accurecy']) ? $contract['image_accurecy'] : '';
				$created_by = !empty($contract['created_by']) ? $contract['created_by'] : $contract['updated_by'];
				$result = $this->check_asset($so_khung, $so_may);
				if ($result === false) {
					continue;
				}
				$asset_id = $this->get_asset_manager($customer_name, $property, $loan_info, $image_accurecy, $created_by);
				$asset = $this->asset_management_model->findOne(['_id' => $asset_id]);
				$this->contract_debt_model->update(['_id' => $contract['_id']], ['asset_code' => $asset['asset_code']]);
			}
		}
		return 'ok';
	}

	public function check_asset($so_khung, $so_may)
	{
		$asset1 = $this->asset_management_model->findOne(['so_khung' => strtoupper($so_khung)]);
		$asset2 = $this->asset_management_model->findOne(['so_may' => strtoupper($so_may)]);
		if (!empty($asset1) && !empty($asset2)) {
			if ($asset1['asset_code'] == $asset2['asset_code']) {
				return true;
			} else {
				return false;
			}
		} elseif (empty($asset1) && empty($asset2)) {
			return true;
		} else {
			return false;
		}
	}

	public function check_asset_nhadat($thua_dat_so) {
		$asset = $this->asset_management_model->findOne(['thua_dat_so' => $thua_dat_so]);
		if ( empty($asset) ) {
			return true;
		}
		return false;
	}
}
