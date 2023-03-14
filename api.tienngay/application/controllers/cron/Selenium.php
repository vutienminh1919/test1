<?php

require_once('application/vendor/autoload.php');
require_once APPPATH . 'libraries/BaoHiemPTI.php';

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class Selenium extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->CI = &get_instance();
		$this->CI->load->config('config');
		$this->load->model('bank_transaction_model');
		$this->load->model('contract_model');
		$this->load->model('transaction_model');
		$this->load->model('user_model');
		$this->load->model('payment_model');
		$this->load->model('generate_model');
		$this->load->model('allocation_model');
		$this->load->model('store_model');
		$this->load->model('pti_vta_bn_model');
		$this->load->model('log_pti_model');
	}

	public function test() {
		$test = $this->getPtiInsurance('PTI37');
		echo "<pre>";
		var_dump($test);
		echo "</pre>";
		die;
	}

	public function vietcombank() {
		$host = $this->CI->config->item('selenium_url');
		$url = $this->CI->config->item('url_vcb');
		$username = $this->CI->config->item('username_vcb');
		$password = $this->CI->config->item('password_vcb');
		$capabilities = DesiredCapabilities::chrome();
		$option = new \Facebook\WebDriver\Chrome\ChromeOptions();
		$option->addArguments(['--disable-dev-shm-usage']);
		$capabilities->setCapability(\Facebook\WebDriver\Chrome\ChromeOptions::CAPABILITY, $option);
		$driver = RemoteWebDriver::create($host, $capabilities);

		// Captcha
		$driver->get($url);
		$imgCap = $driver->findElement(WebDriverBy::xpath('/html/body/div/div/main/div/div/div/div/section/div/div/form/div[5]/div[2]/div/img'));
		$imgId = $imgCap->getAttribute('id');
		$driver->executeScript("
			const imgCap = document.querySelector('#". $imgId ."');
			const canvasCap = document.createElement('canvas');
			const ctxCap = canvasCap.getContext('2d');
			// Set width and height
			canvasCap.width = imgCap.width;
			canvasCap.height = imgCap.height;
			// Draw the image
			ctxCap.drawImage(imgCap, 0, 0);
			let dataCap = canvasCap.toDataURL('image/jpeg');
			console.log(dataCap);
			let elCap = document.getElementsByClassName('logoText font-family-bold text-primary')[0];
			elCap.textContent = dataCap;
		");
		$baseCap = $driver->findElement(WebDriverBy::xpath('/html/body/div/div/main/div/div/div/div/section/div/div/div[1]/div[3]'))->getText();
		$baseCap = $baseCap;
		$this->base64_to_jpeg($baseCap, __DIR__.'/captcha.jpg');

		$captcha_id = $this->captchaIn();
		sleep(2);
		$captcha_res = 'CAPCHA_NOT_READY';
		while ($captcha_res == 'CAPCHA_NOT_READY') {
			$captcha_res = $this->captchaRes($captcha_id);
			var_dump('Captcha: '. $captcha_res);
			sleep(2);
		}

		// Login
		$driver->findElement(WebDriverBy::xpath('/html/body/div/div/main/div/div/div/div/section/div/div/form/div[3]/input'))->sendKeys($username);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/div/main/div/div/div/div/section/div/div/form/div[4]/input'))->sendKeys($password);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/div/main/div/div/div/div/section/div/div/form/div[5]/div[1]/input'))->sendKeys($captcha_res);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/div/main/div/div/div/div/section/div/div/form/div[7]/input'))->click();
		sleep(2);

		// TabMenu
		$driver->findElement(WebDriverBy::xpath('/html/body/div/header/div[1]/section/div/div/div[2]/div[1]/section/div/nav/ul/li[1]/a'))->click();
		sleep(2);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/main/div/form/div[3]/div/div/div[1]/div[2]/div/table/tbody/tr/td[1]/a'))->click();
		sleep(2);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/main/div/form/div[3]/div/div/div/div[4]/div[2]/div[2]/div[1]/input[1]'))->sendKeys(date('d/m/Y'));
		$driver->findElement(WebDriverBy::xpath('/html/body/div/main/div/form/div[3]/div/div/div/div[4]/div[2]/div[2]/div[1]/input[2]'))->sendKeys(date('d/m/Y'));
		$driver->findElement(WebDriverBy::xpath('/html/body/div/main/div/form/div[3]/div/div/div/div[4]/div[2]/div[2]/div[1]/a'))->click();
		sleep(10);

		// Lấy dữ liệu
		$table = $driver->findElement(WebDriverBy::xpath('/html/body/div/main/div/form/div[3]/div/div/div/div[4]/div[2]/div[2]/div[4]/table/tbody'));
		$tableTr = $table->findElements(WebDriverBy::tagName('tr'));
		foreach ($tableTr as $itemTr) {
			$bank = 'VCB';
			$tableTd = $itemTr->findElements(WebDriverBy::tagName('td'));
			$date = isset($tableTd[0]) ? $this->convertTime($tableTd[0]->getText(), $bank) : '';
			$code = isset($tableTd[1]) ? $tableTd[1]->getText() : '';
			$money = isset($tableTd[3]) ? $this->convertNumber($tableTd[3]->getText()) : '';
			$content = isset($tableTd[4]) ? $tableTd[4]->getText() : '';

			/** Quét gạch nợ */
			$contractCode = $this->getContractCode($content);
			if ($contractCode) {
				print_r("Contract code: ". $contractCode."\r\n");
				$checkTranBank = $this->bank_transaction_model->findOne([
					'bank' => $bank,
					'money' => $money,
					'content' => $content,
					'date' => [
						'$gte' => (int) strtotime('today midnight')
					]
				]);
				$checkTran = $this->transaction_model->findOne([
					'code_transaction_bank' => $code,
				]);
				if ( !$checkTran && !$checkTranBank ) {
					$transaction_id = $this->createTransaction($contractCode, $code, $money, $date, $content, $bank);
					print_r("Transaction id: ". $transaction_id."\r\n");
					if ($transaction_id) {
						$result_payment = $this->payment_all($contractCode);
						if ($result_payment) {
							$data = [
								'date' => $date,
								'code' => $code,
								'content' => $content,
								'money' => $money,
								'bank' => $bank,
								'contract_code' => $contractCode,
								'transaction_code' => $transaction_id,
								'status' => true,
								'create_at' => time(),
							];
							$this->bank_transaction_model->insert($data);
						} else {
							$data = [
								'date' => $date,
								'code' => $code,
								'content' => $content,
								'money' => $money,
								'bank' => $bank,
								'contract_code' => $contractCode,
								'transaction_code' => $transaction_id,
								'status' => false,
								'create_at' => time(),
							];
							$this->bank_transaction_model->insert($data);
							// Đẩy phiếu thu về chưa gửi duyệt
							$this->transaction_model->update([
								'_id' => new \MongoDB\BSON\ObjectId($transaction_id)
							], [
								'status' => 4
							]);
						}
					}
				}
			} else {
				if ( $this->getContractCodeFalse($content) ) {
					$checkTranBank = $this->bank_transaction_model->findOne([
						'code' => $code,
						'bank' => $bank
					]);
					if (!$checkTranBank) {
						$data = [
							'date' => $date,
							'code' => $code,
							'content' => $content,
							'money' => $money,
							'bank' => $bank,
							'status' => false,
							'create_at' => time(),
						];
						$this->bank_transaction_model->insert($data);
					}
				}
			}

			/** Quét bảo hiểm PTI */
			$ptiInsurance = $this->getPtiInsurance($content);
			if ($ptiInsurance) {
				print_r("PTI code: ". $ptiInsurance['number_item']."\r\n");
				$checkTranBank = $this->bank_transaction_model->findOne([
					'code' => $code,
					'bank' => $bank
				]);
				if ( !$checkTranBank ) {
					if ($money >= $this->convertNumber($ptiInsurance['price'])) {
						$NGAY_HL = date('d-m-Y', strtotime("+1 days"));
						$pti_vta = $this->insert_pti_vta($ptiInsurance['data_origin'], $NGAY_HL, $ptiInsurance['type_pti'], $ptiInsurance['code_pti_vta'], $ptiInsurance['number_item']);
						if ($pti_vta->success == true) {
							print_r("PTI success: ". $ptiInsurance['number_item']."\r\n");
							$pti = $pti_vta->data;
							$request = $pti_vta->request;
							$NGAY_KT = $pti_vta->NGAY_KT;
							$this->pti_vta_bn_model->update([
								'type_pti' => 'WEB',
								'code_pti_vta' => $ptiInsurance['code_pti_vta']
							], [
								'request' => $request,
								'NGAY_KT' => $NGAY_KT,
								'NGAY_HL' => $NGAY_HL,
								'pti_info' => $pti,
								'money_tranfer' => $money,
								'status' => 1,
								'customer_info' => [
									'customer_name' =>!empty($request->ten) ? $request->ten : '',
									'customer_phone' =>!empty($request->phone) ? $request->phone : '',
									'card' =>!empty($request->so_cmt) ? $request->so_cmt : '',
									'email' => !empty($request->email) ? $request->email : '',
									'birthday' =>!empty($request->ngay_sinh) ? $request->ngay_sinh : ''
								]
							]);
						}
					} else {
						print_r("PTI false: ". $ptiInsurance['number_item']."\r\n");
						$this->pti_vta_bn_model->update([
							'type_pti' => 'WEB',
							'code_pti_vta' => $ptiInsurance['code_pti_vta']
						], [
							'status' => 2,
							'money_tranfer' => $money,
						]);
						$this->transaction_model->update([
							'code' => $ptiInsurance['receipt_code']
						], [
							'status' => 1,
							'bank' => $bank,
							'code_transaction_bank' => $code,
							'approve_note' => $content,
							'approved_at' => time(),
							'approved_by' => 'system'
						]);
					}

					$data = [
						'date' => $date,
						'code' => $code,
						'content' => $content,
						'money' => $money,
						'bank' => $bank,
						'create_at' => time(),
					];
					$this->bank_transaction_model->insert($data);
				}
			}
		}
		print_r("Done");

		$driver->quit();
	}

	public function techcombank() {
		// Setup
		$host = $this->CI->config->item('selenium_url');
		$url = $this->CI->config->item('url_tcb');
		if (isset($_GET['tcvdb']) && $_GET['tcvdb'] == '1') {
			$username = $this->CI->config->item('username_tcb_db');
			$password = $this->CI->config->item('password_tcb_db');
			$numberTCB = $this->CI->config->item('number_tcb_db');
		} else {
			$username = $this->CI->config->item('username_tcb');
			$password = $this->CI->config->item('password_tcb');
			$numberTCB = $this->CI->config->item('number_tcb');
		}
		$capabilities = DesiredCapabilities::chrome();
		$option = new \Facebook\WebDriver\Chrome\ChromeOptions();
		$option->addArguments(['--disable-dev-shm-usage']);
		$capabilities->setCapability(\Facebook\WebDriver\Chrome\ChromeOptions::CAPABILITY, $option);
		$driver = RemoteWebDriver::create($host, $capabilities);

		// Login
		$driver->get($url);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/form/div[3]/div[2]/div[2]/div[1]/div[1]/div[2]/input'))->sendKeys($username);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/form/div[3]/div[2]/div[2]/div[1]/div[1]/div[4]/input'))->sendKeys($password);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/form/div[3]/div[2]/div[2]/div[1]/div[1]/div[9]/input[1]'))->click();
		sleep(3);

		// Chọn menu
		$driver->findElement(WebDriverBy::xpath('/html/body/div/table/tbody/tr[2]/td/table/tbody/tr/td[1]/div[3]/ul[1]/li/span'))->click();
		sleep(2);
		$driver->findElement(WebDriverBy::xpath('/html/body/div/table/tbody/tr[2]/td/table/tbody/tr/td[1]/div[3]/ul[1]/li/ul/li[1]/a'))->click();
		sleep(2);

		// Filter
		$driver->findElement(WebDriverBy::xpath('/html/body/div[1]/table/tbody/tr[2]/td/table/tbody/tr/td[2]/div[3]/div[2]/form[1]/div[4]/table[1]/tbody/tr[2]/td/table/tbody/tr[1]/td[4]/input'))->sendKeys($numberTCB);
		$driver->findElement(WebDriverBy::xpath('/html/body/div[1]/table/tbody/tr[2]/td/table/tbody/tr/td[2]/div[3]/div[2]/form[1]/div[4]/table[2]/tbody/tr/td[2]/a'))->click();
		sleep(10);

		// Lấy dữ liệu
		$table = $driver->findElement(WebDriverBy::xpath('/html/body/div[1]/table/tbody/tr[2]/td/table/tbody/tr/td[2]/div[3]/div/form/div/table/tbody/tr[2]/td[2]/div[2]/div/table/tbody'));
		$tableTr = $table->findElements(WebDriverBy::tagName('tr'));
		foreach ($tableTr as $itemTr) {
			$bank = 'TCB';
			$tableTd = $itemTr->findElements(WebDriverBy::tagName('td'));
			$date = isset($tableTd[0]) ? $this->convertTime($tableTd[0]->getText(), $bank) : '';
			$code = isset($tableTd[1]) ? $tableTd[1]->getText() : '';
			$content = isset($tableTd[3]) ? $tableTd[3]->getText() : '';
			$money = isset($tableTd[5]) ? $this->convertNumber($tableTd[5]->getText()) : '';

			$contractCode = $this->getContractCode($content);
			if ($contractCode) {
				print_r("Contract code: ". $contractCode."\r\n");
				$checkTranBank = $this->bank_transaction_model->findOne([
					'code' => $code,
					'bank' => $bank
				]);
				$checkTran = $this->transaction_model->findOne([
					'code_transaction_bank' => $code,
				]);
				if ( !$checkTran && !$checkTranBank ) {
					$transaction_id = $this->createTransaction($contractCode, $code, $money, $date, $content, $bank);
					print_r("Transaction id: ". $transaction_id."\r\n");
					if ($transaction_id) {
						$result_payment = $this->payment_all($contractCode);
						if ($result_payment) {
							$data = [
								'date' => $date,
								'code' => $code,
								'content' => $content,
								'money' => $money,
								'bank' => $bank,
								'contract_code' => $contractCode,
								'transaction_code' => $transaction_id,
								'status' => true,
								'create_at' => time(),
							];
							$this->bank_transaction_model->insert($data);
						} else {
							$data = [
								'date' => $date,
								'code' => $code,
								'content' => $content,
								'money' => $money,
								'bank' => $bank,
								'contract_code' => $contractCode,
								'transaction_code' => $transaction_id,
								'status' => false,
								'create_at' => time(),
							];
							$this->bank_transaction_model->insert($data);
							// Đẩy phiếu thu về chưa gửi duyệt
							$this->transaction_model->update([
								'_id' => new \MongoDB\BSON\ObjectId($transaction_id)
							], [
								'status' => 4
							]);
						}
					}
				}
			} else {
				if ( $this->getContractCodeFalse($content) ) {
					$checkTranBank = $this->bank_transaction_model->findOne([
						'code' => $code,
						'bank' => $bank
					]);
					if (!$checkTranBank) {
						$data = [
							'date' => $date,
							'code' => $code,
							'content' => $content,
							'money' => $money,
							'bank' => $bank,
							'status' => false,
							'create_at' => time(),
						];
						$this->bank_transaction_model->insert($data);
					}
				}
			}

			/** Quét bảo hiểm PTI */
			$ptiInsurance = $this->getPtiInsurance($content);
			if ($ptiInsurance) {
				print_r("PTI code: ". $ptiInsurance['number_item']."\r\n");
				$checkTranBank = $this->bank_transaction_model->findOne([
					'code' => $code,
					'bank' => $bank
				]);
				if ( !$checkTranBank ) {
					if ($money >= $this->convertNumber($ptiInsurance['price'])) {
						$NGAY_HL = date('d-m-Y', strtotime("+1 days"));
						$pti_vta = $this->insert_pti_vta($ptiInsurance['data_origin'], $NGAY_HL, $ptiInsurance['type_pti'], $ptiInsurance['code_pti_vta'], $ptiInsurance['number_item']);
						if ($pti_vta->success == true) {
							print_r("PTI success: ". $ptiInsurance['number_item']."\r\n");
							$pti = $pti_vta->data;
							$request = $pti_vta->request;
							$NGAY_KT = $pti_vta->NGAY_KT;
							$this->pti_vta_bn_model->update([
								'type_pti' => 'WEB',
								'code_pti_vta' => $ptiInsurance['code_pti_vta']
							], [
								'request' => $request,
								'NGAY_KT' => $NGAY_KT,
								'NGAY_HL' => $NGAY_HL,
								'pti_info' => $pti,
								'money_tranfer' => $money,
								'status' => 1,
								'customer_info' => [
									'customer_name' =>!empty($request->ten) ? $request->ten : '',
									'customer_phone' =>!empty($request->phone) ? $request->phone : '',
									'card' =>!empty($request->so_cmt) ? $request->so_cmt : '',
									'email' => !empty($request->email) ? $request->email : '',
									'birthday' =>!empty($request->ngay_sinh) ? $request->ngay_sinh : ''
								]
							]);
							$this->transaction_model->update([
								'code' => $ptiInsurance['receipt_code']
							], [
								'status' => 1,
								'bank' => $bank,
								'code_transaction_bank' => $code,
								'approve_note' => $content,
								'approved_at' => time(),
								'approved_by' => 'system'
							]);
						}
					} else {
						print_r("PTI false: ". $ptiInsurance['number_item']."\r\n");
						$this->pti_vta_bn_model->update([
							'type_pti' => 'WEB',
							'code_pti_vta' => $ptiInsurance['code_pti_vta']
						], [
							'status' => 2,
							'money_tranfer' => $money,
						]);
					}

					$data = [
						'date' => $date,
						'code' => $code,
						'content' => $content,
						'money' => $money,
						'bank' => $bank,
						'create_at' => time(),
					];
					$this->bank_transaction_model->insert($data);
				}
			}

			/** Quét đối soát giao dịch Momo*/
			$this->insertMomoTransactionReconciliation ($bank, $code, $content, $money, $date);
		}
		print_r("Done");

		$driver->quit();
	}

	public function getPtiInsurance($string) {
		$string = strtoupper($string);
		if ( preg_match('/PTI(\d+)/', $string, $matches) ) {
			if ( isset($matches[1]) ) {
				if ( isset($matches[1]) && preg_match('/0*(\d+)/', $matches[1], $number) ) {
					if (isset($number[1])) {
						return $this->pti_vta_bn_model->findOne([
							'type_pti' => "WEB",
							'status' => 10,
							'number_item' => (int) $number[1]
						]);
					}
				}
			}
		}
		return false;
	}

	public function insert_pti_vta($data, $NGAY_HL,$type,$code, $number_item) {
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$fullname = !empty($data['fullname']) ? $data['fullname'] : '';
		$cmt = !empty($data['cmt']) ? $data['cmt'] : '';
		$relationship = !empty($data['relationship']) ? $data['relationship'] : '';
		$address = !empty($data['address']) ? $data['address'] : '';
		$id_pgd = !empty($data['id_pgd']) ? $data['id_pgd'] : '';
		$obj = !empty($data['obj']) ? $data['obj'] : '';
		$phone = !empty($data['phone']) ? $data['phone'] : '';
		$email = !empty($data['email']) ? $data['email'] : '';
		$birthday = !empty($data['birthday']) ? $data['birthday'] : '';
		$fullname_another = !empty($data['fullname_another']) ? $data['fullname_another'] : '';
		$birthday_another = !empty($data['birthday_another']) ? $data['birthday_another'] : '';
		$email_another = !empty($data['email_another']) ? $data['email_another'] : '';
		$cmt_another = !empty($data['cmt_another']) ? $data['cmt_another'] : '';
		$phone_another = !empty($data['phone_another']) ? $data['phone_another'] : '';
		$sel_ql = !empty($data['sel_ql']) ? $data['sel_ql'] : '';
		$sel_year = !empty($data['sel_year']) ? $data['sel_year'] : '';
		$price = !empty($data['price']) ? $data['price'] : 0;
		$btendn=$fullname;
		$bdiachidn=$address;
		$bemaildn=$email;
		$bphonedn=$phone;
		$bmathue=$cmt;
		$NgayYeuCauBh = $NGAY_HL;
		$NgayHieuLucBaoHiem = $NGAY_HL;
		if($sel_year=="1Y")
		{
			$so_thang_bh=12;
		}else if($sel_year=="6M")
		{
			$so_thang_bh=6;
		}else if($sel_year=="3M")
		{
			$so_thang_bh=3;
		}
		$NgayHieuLucBaoHiemDen = date('d-m-Y', strtotime($NgayHieuLucBaoHiem . ' + '.$so_thang_bh.' month'));

		$customer_name = (!empty($data['ten_kh'])) ? $data['ten_kh'] : '';
		$customer_BOD = (!empty($data['ngay_sinh'])) ? date("Y-m-d",$data['ngay_sinh']) : '';
		$customer_identify = (!empty($data['cmt'])) ? $data['cmt'] : '';
		$so_hd='TN'.str_pad((string)$number_item,7, '0', STR_PAD_LEFT).'/041/CN.1.14/'.date('Y');
		if($obj=='banthan')
		{
			$ten=$fullname;
			$ngay_sinh=$birthday;
			$email=$email;
			$phone=$phone;
			$so_cmt=$cmt;
		}else{
			$ten=$fullname_another;
			$ngay_sinh=$birthday_another;
			$email=$email_another;
			$phone=$phone_another;
			$so_cmt=$cmt_another;
		}

		if($sel_ql=="G1")
		{
			$ba1='20,000,000';
			$ba2='20,000,000';
			$ba3='30,000,000';
			$ba4='2,000,000';
			$ba5='2,000,000';
		}else if($sel_ql=="G2")
		{
			$ba1='40,000,000';
			$ba2='40,000,000';
			$ba3='60,000,000';
			$ba4='4,000,000';
			$ba5='4,000,000';
		}else if($sel_ql=="G3")
		{
			$ba1='60,000,000';
			$ba2='60,000,000';
			$ba3='90,000,000';
			$ba4='6,000,000';
			$ba5='6,000,000';
		}
		$dt_pti = array(
			'so_hd' => $so_hd
		, 'btendn' => $btendn
		, 'bdiachidn' => $bdiachidn
		, 'bemaildn' => $bemaildn
		, 'bphonedn' => $bphonedn
		, 'bmathue' => $bmathue
		, 'quan_he' => $relationship
		, 'ten' => $ten
		, 'ngay_sinh' => date('d-m-Y', strtotime($ngay_sinh))
		, 'so_cmt' => $so_cmt
		, 'email' => $email
		, 'phone' => $phone
		, 'phi_bh' => number_format($price)
		, 'so_thang_bh' => $so_thang_bh
		, 'ngay_hl' => $NgayHieuLucBaoHiem
		, 'ngay_kt' => $NgayHieuLucBaoHiemDen
		, 'ngay_in' => date('d/m/Y')
		, 'so_gcn' => $so_hd
		, 'ba1' => $ba1
		, 'ba2' => $ba2
		, "ba3" => $ba3
		, "ba4" => $ba4
		, 'ba5' => $ba5
		);
		// return  $province;
		$baohiem = new BaoHiemPTI();
		$res = $baohiem->call_api($dt_pti);

		$this->log_pti(json_encode($dt_pti), $res, $type, $code);
		if (!empty($res)) {
			if ($res['code']=="000") {

				$dt_re = array(
					'message' => 'Thành công',
					'data' => $res,
					'number_item'=>$number_item,
					'success' => true,
					'request'=>$dt_pti,
					'NGAY_KT'=>$NgayHieuLucBaoHiemDen
				);
				return json_decode(json_encode($dt_re));

			} else {
				$dt_re = array(
					'message' => 'Không thành công',
					'success' => false
				);
				return json_decode(json_encode($dt_re));
			}
		} else {

			$dt_re = array(
				'message' => "Kết nối đến PTI bị lỗi !",
				'success' => false
			);
			return json_decode(json_encode($dt_re));
		}
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

	public function getContractCode($string) {
		$string = strtoupper($string);

		if ( preg_match('/VFC00000(\d+)/', $string, $matches) ) {
			if ( isset($matches[1]) && preg_match('/0*(\d+)/', $matches[1], $number) ) {
				if (isset($number[1])) {
					return $this->getContractMaPhieuGhi($number[1])['code_contract'] ?? false;
				}
			}
		} else if ( preg_match('/VFC([0-9]{12})/', $string, $matches) ) {
			if ( isset($matches[1]) ) {
				return $this->getUserContract($matches[1])['code_contract'] ?? false;
			}
		} else if ( preg_match('/VFC([0-9]{9})/', $string, $matches) ) {
			if ( isset($matches[1]) ) {
				return $this->getUserContract($matches[1])['code_contract'] ?? false;
			}
		} else if ( preg_match('/VFC([0-9]{1,8})/', $string, $matches) ) {
			if ( isset($matches[1]) ) {
				if ( isset($matches[1]) && preg_match('/0*(\d+)/', $matches[1], $number) ) {
					if (isset($number[1])) {
						return $this->getContractMaPhieuGhi($number[1])['code_contract'] ?? false;
					}
				}
			}
		}

		return false;
	}

	public function getContractCodeFalse ($string) {
		$string = strtoupper($string);
		if ( preg_match('/VFC(\d+)/', $string, $matches) ) {
			if ( isset($matches[1]) ) {
				print_r("Contract code False: " . $matches[1] . "\r\n");
				return $matches[1] ?? false;
			}
		}
		return false;
	}

	public function getContractMaPhieuGhi($string) {
		return $this->contract_model->findOne([
			'code_contract' => '00000'.$string,
			'status' => [
				'$in' => list_array_trang_thai_dang_vay()
			]
		]);
	}

	public function getUserContract($string) {
		$listContract = $this->contract_model->find_where_sort_debt($string);
		return $this->filterContract($listContract);
	}

	public function filterContract($listContract) {
		// Lấy HD quá hạn
		foreach ($listContract as $contract) {
			if ( isset($contract['debt']['is_qua_han']) && $contract['debt']['is_qua_han'] == 1 ) {
				return $contract;
			}
		}
		// Lấy hợp đồng có phí phạt hoặc còn nợ
		foreach ($listContract as $contract) {
			$arr_data=[
				'date_pay' => time(),
				'id_contract' => (string) $contract['_id'],
				'code_contract' => $contract['code_contract']
			];
			$contractDB = $this->payment_model->get_payment($arr_data);
			if (isset($contractDB['contract']['penalty_pay']) && $contractDB['contract']['penalty_pay'] > 0) {
				return $contract;
			}
			if (isset($contractDB['tien_con_no']) && $contractDB['tien_con_no'] > 0) {
				print_r("Tiền còn nợ: ".$contractDB['tien_con_no']. "\r\n");
				return $contract;
			}
		}
		// Hợp đồng đang vay
		foreach ($listContract as $contract) {
			return $contract;
		}
		return null;
	}

	public function convertNumber($num) {
		return (int) str_replace(',', '', $num);
	}

	public function convertTime($time, $bank) {
		if ($bank == 'TCB') {
			list($day, $month, $year) = explode('/', $time);
		} else if ($bank == 'VCB') {
			list($year, $month, $day) = explode('-', $time);
		}
		return mktime(0, 0, 0, $month, $day, $year);
	}

	public function createTransaction($codeContract, $codeBank, $money, $date, $content, $bank) {
		$contract = $this->contract_model->findOne([
			'code_contract' => $codeContract
		]);

		if ( !empty($contract) ) {
			//Insert data
			$code = $this->transaction_model->getNextTranCode($contract['code_contract']);
			$data_transaction = [
				'code_contract' => $contract['code_contract'] ?? '',
				'code_contract_disbursement' => $contract['code_contract_disbursement'] ?? '',
				'customer_name' => $contract['customer_name'] ?? '',
				'total' => $money ?? 0,
				'code' => $code,
				'type' => 4,
				'payment_method' => 2,
				'store' => $contract['store'] ?? '',
				'date_pay' => time(),
				'status' => 1,
				'customer_bill_phone' => $contract['customer_infor']['customer_phone_number'] ?? '',
				'customer_bill_name' => $contract['customer_infor']['customer_name'] ?? '',
				'note' => $content,
				'bank' => $bank,
				'code_transaction_bank' => $codeBank,
				'type_payment' => 1,
				'created_by' => 'system',
				'created_at' => time()
			];
			$data = $this->transaction_model->insertReturnId($data_transaction);
			return $data;
		}

		return null;
	}

	public function getToken() {
		$token = $this->user_model->findOne([
			'status' => 'active',
			'is_superadmin' => 1
		]);

		return $token['token_web'];
	}

	public function payment_all($codeContract)
	{

		$contractData = $this->contract_model->findOne([
			'code_contract' => $codeContract
		]);

		if ($contractData) {
			print_r('Contract_id: '.$contractData['_id']."\r\n");
			$id_contract = $contractData['_id'];

			$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId( $id_contract) ,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42]] ));
			if (empty($dataDB)) {
				print_r("Không tồn tại hợp đồng hoặc hợp đồng GH/CC"."\r\n");
				return false;
			}
			$data_delete = array(
				"code_contract" =>  $dataDB['code_contract'],
			);
			$result_delete_and_update = $this->payment_model->delete_lai_ky_lai_thang($data_delete);
			if (!empty($result_delete_and_update['status']) && $result_delete_and_update['status'] == 200) {


				$data_generate = array(
					"code_contract" => $dataDB['code_contract'],
					"investor_code" =>$dataDB['investor_code'],
					"disbursement_date" => $dataDB['disbursement_date']
				);
				$result_generate = $this->generate_model->processGenerate($data_generate);

				if (!empty($result_generate['status']) && $result_generate['status'] == 200) {

					$result  = $this->allocation_model->payment_all_contract($data_generate);
				}else{
					print_r("Khởi tạo thông tin chưa thành công"."\r\n");
					return false;

				}
			}else{
				print_r("Xóa thông tin chưa thành công"."\r\n");
				return false;
			}

			print_r("Gạch thành công: ". $dataDB['code_contract']."\r\n");
			return true;
		}
		return false;
	}

	public function captchaIn() {
		$captcha_url = $this->CI->config->item('captcha_url');
		$captcha_key = $this->CI->config->item('captcha_key');
		$fileName = 'captcha.jpg';
		$filePath = __DIR__ .'/'. $fileName;
		//curl
		$ch = curl_init();
		$data = array(
			'key' => $captcha_key, 
			'file' => new \CurlFile($filePath, 'image/jpeg', $fileName), 
			'method' => 'post'
		);
		curl_setopt($ch, CURLOPT_URL, $captcha_url.'in.php');
		//curl_setopt($ch, CURLOPT_HEADER , true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: multipart/form-data;',
		]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$content = curl_exec($ch);
		curl_close($ch);

		$content = explode('|', $content);
		if ( isset($content[1]) ) {
			$content = $content[1];
		}

		return $content;
	}

	public function captchaRes($id) {
		$captcha_url = $this->CI->config->item('captcha_url');
		$captcha_key = $this->CI->config->item('captcha_key');
		$captcha_url = $captcha_url.'res.php?key='. $captcha_key.'&id='. $id .'&action=get&json=1';
		$opts = array('http' =>
			array(
				'method' => 'GET',
			)
		);
		$context = stream_context_create($opts);
		$result = file_get_contents($captcha_url, false, $context);

		$decodeResponse = json_decode($result, true);
		if ( isset($decodeResponse['request']) ) {
			$decodeResponse = $decodeResponse['request'];
		}
		return $decodeResponse;
	}

	public function apiPost($token, $url, $data=array()) {
		$urlPost = $this->CI->config->item('base_url');
		$request_headers = array(
			"Content-type:" . 'application/x-www-form-urlencoded',
			"Authorization: " . $token
		);
		$data['type'] = 1;
		$postdata = http_build_query($data);
		$opts = array('http' =>
			array(
				'method' => 'POST',
				'header' => $request_headers,
				'content' => $postdata
			)
		);
		$context = stream_context_create($opts);
		$result = file_get_contents($urlPost, false, $context);
		$decodeResponse = json_decode($result);
		return $decodeResponse;
	}

	function base64_to_jpeg($base64_string, $output_file) {
		// open the output file for writing
		$ifp = fopen( $output_file, 'wb' ); 
		// split the string on commas
		// $data[ 0 ] == "data:image/png;base64"
		// $data[ 1 ] == <actual base64 string>
		$data = explode( ',', $base64_string );

		// we could add validation here with ensuring count( $data ) > 1
		fwrite( $ifp, base64_decode( $data[ 1 ] ) );

		// clean up the file resource
		fclose( $ifp ); 

		return $output_file; 
	}

	/**
	* Quét giao dịch chuyển khoản từ phía Momo
	*@param $bank: Tên ngân hàng
	*@param $code: Mã giao dịch
	*@param $content: Nội dung giao dịch
	*@param $money: Tiền giao dịch
	*@param $date: Ngày giao dịch
	*@return boolean
	*/
	protected function insertMomoTransactionReconciliation ($bank, $code, $content, $money, $date) {
		$dataSave = [
			'type' => "momo_reconciliation",
			'date' => $date,
			'code' => $code,
			'content' => $content,
			'money' => $money,
			'bank' => $bank,
			'create_at' => time(),
		];
		$string = strtoupper($content);
		if ( preg_match('/MT([0-9]{6})/', $string, $matches)) {
			if ( isset($matches[1]) ) {
				$this->bank_transaction_model->insert($dataSave);
				return true;
			}
		}
		return false;
	}

}
