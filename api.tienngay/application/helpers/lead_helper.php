<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
if (!function_exists('get_goi_vbi')) {
	function get_goi_vbi($status = null)
	{
		$result = '';
		$leadstatus = [
			1 => 'SXH_VFC_GOI1_CANHAN',
			2 => 'SXH_VFC_GOI2_CANHAN',
			3 => 'SXH_VFC_GOI3_CANHAN',
			4 => 'SXH_VFC_GOI1_GIADINH',
			5 => 'SXH_VFC_GOI2_GIADINH',
			6 => 'SXH_VFC_GOI3_GIADINH',
			7 => 'UNG_THU_VU_1',
			8 => 'UNG_THU_VU_2',
			9 => 'UNG_THU_VU_3',
			10 => 'UNG_THU_VU_1',
			11 => 'UNG_THU_VU_2',
			12 => 'UNG_THU_VU_3',

		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('status_transaction')) {
	function status_transaction($status = null)
	{
		$leadstatus = [
			'new' => 'Mới',
			'1' => 'Thành công',
			'2' => 'Chờ xác nhận',
			'3' => 'Đã hủy',
			'4' => 'Chưa gửi duyệt PT hợp đồng',
			'10' => 'Chưa gửi duyệt',
			'11' => 'Kế toán trả về',
			"5" => 'Chờ nạp tiền về công ty'
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}
function divide_amount_money($amount_money)
{
	$money = $amount_money;
	$max = 300000000;
	$part = 0;
	$arr_money = array();
	if ($amount_money > $max) {
		$part = ceil($amount_money / $max);
		$arr_money['total_part'] = $part;
		for ($i = 1; $i <= $part; $i++) {
			if ($money >= $max) {
				$arr_money['part_' . $i] = array('money' => $max, "status" => 1);
				$money = $money - $max;
			} else if ($money < $max && $money > 0) {
				$arr_money['part_' . $i] = array('money' => $money, "status" => 1);
				$money = 0;
			}
		}
		return $arr_money;
	} else {
		return $amount_money;
	}
}

function days_in_month($month, $year)
{
// calculate number of days in a month
	return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}

function pmt($rate_per_period, $number_of_payments, $present_value, $future_value = 0, $type = 0)
{

	if ($rate_per_period != 0) {
		// Interest rate exists
		$q = pow(1 + $rate_per_period, $number_of_payments);
		return -($rate_per_period * ($future_value + ($q * $present_value))) / ((-1 + $q) * (1 + $rate_per_period * ($type)));

	} else if ($number_of_payments != 0) {
		// No interest rate, but number of payments exists
		return -($future_value + $present_value) / $number_of_payments;
	}

	return 0;
}

// Define a function that converts array to xml.
function arrayToXml($array, $rootElement = null, $xml = null)
{
	$_xml = $xml;

	// If there is no Root Element then insert root
	if ($_xml === null) {
		$_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
	}

	// Visit all key value pair
	foreach ($array as $k => $v) {

		// If there is nested array then
		if (is_array($v)) {

			// Call function for nested array
			arrayToXml($v, $k, $_xml->addChild($k));
		} else {

			// Simply add child element.
			$_xml->addChild($k, $v);
		}
	}

	return $_xml->asXML();
}

if (!function_exists('convert_zero_phone')) {
	function convert_zero_phone($number)
	{
		if (!empty($number) && strlen($number) > 10) {
			if ($number[0] . $number[1] == '84')
				$number = substr($number, 2, strlen($number) - 1);
		}
		if (!empty($number) && strlen($number) > 10) {
			if ($number[0] . $number[1]. $number[2] == '+84')
				$number = substr($number, 3, strlen($number) - 1);
		}
		if (!empty($number)) {
			if ($number[0] == 'o' || $number[0] == 'O')
				$number = substr($number, 1, strlen($number) - 1);
		}
		if (!empty($number)) {
			if ($number[0] != 0)
				$number = '0' . $number;
		}
		return $number;
	}
}
if (!function_exists('contract_status')) {
	function contract_status($status = null)
	{
		$result = '';
		$leadstatus = [
			1 => "Mới",
			2 => "Chờ trưởng PGD duyệt",
			3 => "Đã hủy",
			4 => "Trưởng PGD không duyệt",
			5 => "Chờ hội sở duyệt",
			6 => "Đã duyệt",
			7 => "Kế toán không duyệt",
			8 => "Hội sở không duyệt",
			9 => "Chờ ngân lượng xử lý",
			10 => "Giải ngân ngân lượng thất bại",
			11 => "Chờ TP thu hồi nợ duyệt gia hạn",
			12 => "Chờ TP thu hồi nợ duyệt cơ cấu",
			13 => "TP thu hồi nợ không duyệt gia hạn",
			14 => "TP thu hồi nợ không duyệt cơ cấu",
			15 => "Chờ giải ngân",
			16 => "Đã tạo lệnh giải ngân thành công",
			17 => "Đang vay",
			18 => "Giải ngân thất bại",
			19 => "Đã tất toán",
			20 => "Đã quá hạn",
			21 => "Chờ trưởng PGD duyệt gia hạn",
			22 => "Trưởng PGD không duyệt gia hạn",
			23 => "Chờ trưởng PGD duyệt cơ cấu",
			24 => "Trưởng PGD không duyệt cơ cấu",
			25 => "Chờ hội sở duyệt gia hạn",
			26 => "Hội sở không duyệt gia hạn",
			27 => "Chờ hội sở duyệt cơ cấu",
			28 => "Hội sở không duyệt cơ cấu",
			29 => "Chờ tạo phiếu thu gia hạn",
			30 => "Chờ ASM duyệt gia hạn",
			31 => "Chờ tạo phiếu thu cơ cấu",
			32 => "Chờ ASM duyệt cơ cấu",
			33 => "Đã gia hạn",
			34 => "Đã cơ cấu",
			35 => "Chờ ASM duyệt",
			36 => "ASM không duyệt",
			37 => "Chờ thanh lý",
			38 => "Chờ CEO duyệt thanh lý",
			39 => "Chờ TP THN xác nhận thanh lý",
			40 => "Đã thanh lý",
			41 => "ASM không duyệt gia hạn",
			42 => "ASM không duyệt cơ cấu",
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
function dem_ngay($ngay1 = "Y-m-d", $ngay2 = "Y-m-d")
{
	$first_date = strtotime($ngay1);
	$second_date = strtotime($ngay2);
	$datediff = abs($first_date - $second_date);
	return floor($datediff / (60 * 60 * 24)) + 1;
}

if (!function_exists('status_job')) {
	function status_job($status = null)
	{
		$leadstatus = [
			1 => 'Nhân viên văn phòng',
			2 => 'Nhân viên kinh doanh',
			3 => 'Bán hàng',
			4 => 'Kế toán-Kiểm toán',
			5 => 'Tư vấn',
			6 => 'Kỹ thuật',
			7 => 'Quản trị kinh doanh',
			8 => 'Xây dựng',
			9 => 'Marketing-PR',
			10 => 'Điện-Điện tử-Điện lạnh',
			11 => 'Cơ khí-Chế tạo',
			12 => 'Khách sạn-Nhà hàng',
			13 => 'Kiến trúc-TK nội thất',
			14 => 'Nhân sự',
			15 => 'Kho vận-Vật tư',
			16 => 'Thực phẩm-Đồ uống',
			17 => 'Dịch vụ',
			18 => 'Thư ký-Trợ lý',
			19 => 'Ngân hàng',
			20 => 'Thiết kế-Mỹ thuật',
			21 => 'Tài chính-Đầu tư',
			22 => 'Biên-Phiên dịch',
			23 => 'Vận tải',
			24 => 'Tiếp thị-Quảng cáo',
			25 => 'Ngoại thương-Xuất nhập khẩu',
			26 => 'IT phần mềm',
			27 => 'Ô tô - Xe máy',
			28 => 'KD bất động sản',
			29 => 'IT phần cứng/mạng',
			30 => 'Y tế-Dược',
			31 => 'Kỹ thuật ứng dụng',
			32 => 'Thiết kế đồ hoạ web',
			33 => 'Dệt may - Da giày',
			34 => 'Giáo dục-Đào tạo',
			35 => 'Điện tử viễn thông',
			36 => 'Thương mại điện tử',
			37 => 'Spa-Mỹ phẩm-Trang sức',
			38 => 'Công nghiệp',
			39 => 'Thời trang',
			40 => 'Báo chí-Truyền hình',
			41 => 'Hoá học-Sinh học',
			42 => 'Ngành nghề khác',
			43 => 'In ấn-Xuất bản',
			44 => 'Du lịch',
			45 => 'Pháp lý-Luật',
			46 => 'Hoạch định-Dự án',
			47 => 'Quan hệ đối ngoại',
			48 => 'Nông-Lâm-Ngư nghiệp',
			49 => 'An ninh-Bảo vệ',
			50 => 'Bảo hiểm',
			51 => 'Tổ chức sự kiện-Quà tặng',
			52 => 'Bưu chính',
			53 => 'Dầu khí-Hóa chất',
			54 => 'Nghệ thuật - Điện ảnh',
			55 => 'Công nghệ cao',
			56 => 'Hàng gia dụng',
			57 => 'Hàng hải',
			58 => 'Chứng khoán- Vàng',
			59 => 'Hàng không',
			60 => 'Thủ công mỹ nghệ',
			61 => 'Gammer',
			62 => 'Tài xế tự do',
			63 => 'Kinh doanh tự do',
			64 => 'Tài xế công nghệ',
			65 => 'Tài xế Grap',
			66 => 'Tài xế Be',
			67 => 'Tài xế Goviet',
			68 => 'Tài xế HeyU',
			69 => 'Tài xế FastGo',
			70 => 'Tài xế MyGo',
			71 => 'Shipper tự do',
			72 => 'Shipper công nghệ'
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('note_renewal')) {
	function note_renewal($status = null)
	{
		$leadstatus = [
			1 => 'C_CAB - KH chưa nghe máy, gọi lại sau',
			2 => 'C_HUP - Đang nói chuyện cúp máy ngang',
			3 => 'C_NAB - Số không liên lạc được, số không tồn tại, số không đúng',
			4 => 'C_NCP - Không phải số KH/ Số người thân',
			5 => 'C_NKP - Không nhấc máy/máy bận/gọi vào hộp thư thoại',
			6 => 'C_PA - Đã thanh toán',
			7 => 'C_PTP - Hứa thanh toán',
			8 => 'C_RTP - Từ chối thanh toán',
			9 => 'C_WFP - Khách hàng đã thanh toán đang chờ Phòng Kế toán kiểm tra',
			10 => 'C_DIF - KH khó khăn kinh tế',
			11 => 'C_HPR - Gia đình bận rộn (đám cưới, đám tang, về quê, công tác, nghỉ mát)',
			12 => 'C_IGN1 - Khách hàng chưa nhận khoản vay',
			13 => 'C_IGN2 - Khách hàng báo đã hủy hợp đồng',
			14 => 'C_IGN3 - Đang chờ điều chỉnh hóa đơn',
			15 => 'C_IGN4 - Đang chờ gửi ảnh nộp tiền xác nhận khoản treo',
			16 => 'C_IGN5 - Đang chờ giảm phí phạt, đóng hợp đồng',
			17 => 'C_IGN6 - Khách hàng đã thanh toán trước khi nhân viên được phân hợp đồng',
			18 => 'C_LEM - Để lại lời nhắn cho người thân',
			19 => 'C_LEM_O - Để lại lời nhắn cho người khác',
			20 => 'C_CGI - KH đi tù/nghĩa vụ/cai nghiện/tâm thần',
			21 => 'C_LST - Mất việc làm, phá sản, làm ăn thua lỗ',
			22 => 'C_WAS - Chờ thu nhập, trợ cấp của người thân',
			23 => 'C_MCW - KH bị bệnh, tai nạn',
			24 => 'C_CTI - Thiên tai, mất mùa, bão lũ…',
			25 => 'C_RMA - Người thân ốm đau, tai nạn',
			26 => 'C_CNZ - KH không sử dụng zalo',
			27 => 'C_CNF - KH không sử dụng facebook',
			28 => 'C_SZC - Đã gửi thông báo qua zalo cho KH',
			29 => 'C_SFC - Đã gửi thông báo qua facebook cho KH',
			30 => 'C_CBPZF - Khách hàng đã chặn điện thoại, zalo, facebook',
			31 => 'C_BED - Khách hàng bị người môi giới lừa đứng tên vay',
			32 => 'C_BRP - Xe đã được thu hồi',
			33 => 'C_CSO - Khách hàng đứng tên vay cho người khác',
			34 => 'C_DIE - Khách hàng chết, gia đình gặp khó khăn kinh tế',
			35 => 'C_GSF - Nghi ngờ gian lận: Sai địa chỉ, CMND giả, KH ko biết về khoản vay…',
			36 => 'C_TER - KH thanh lý hợp đồng',
			37 => 'C_CFF - Call không còn khả năng tác động và đủ điều kiện chuyển qua Thực địa',
			38 => 'F_BED - Khách hàng bị người môi giới lừa đứng tên vay',
			39 => 'F_BRP - Xe đã được thu hồi',
			40 => 'F_CSO - Khách hàng đứng tên vay cho người khác',
			41 => 'F_DIE - Khách hàng chết, gia đình gặp khó khăn kinh tế',
			42 => 'F_DIF - KH khó khăn kinh tế',
			43 => 'F_HOS - Nhà đã bán',
			44 => 'F_NAH1 - Không gặp được Khách hàng, đã để lại thư báo;',
			45 => 'F_NAH2 - Khách hàng bỏ nhà đi, thỉnh thoảng mới về, đã để lại thư báo',
			46 => 'F_NFH - Không tìm thấy nhà; Nhà đã bị giải tỏa.',
			47 => 'F_NIW - Công ty không hợp tác, không xác định được rõ Khách hàng còn làm hay không',
			48 => 'F_OBT - Đã thu được tiền',
			49 => 'F_RENT - KH thuê nhà trọ nhưng đã dọn đi (có thể gặp gia đình)',
			50 => 'F_SOB - Xe đã bán',
			51 => 'F_WAU - Khách hàng bỏ trốn, gia đình còn ở tại địa phương',
			52 => 'F_WET - Khách hàng bỏ trốn, không gặp được gia đình',
			53 => 'F_GSF - Nghi ngờ gian lận: Sai địa chỉ, CMND giả, KH ko biết về khoản vay…',
			54 => 'F_HPR - Gia đình bận rộn (đám cưới, đám tang, về quê, công tác, nghỉ mát)',
			55 => 'F_IGN1 - Khách hàng chưa nhận khoản vay',
			56 => 'F_IGN2 - Khách hàng báo đã hủy hợp đồng',
			57 => 'F_IGN3 - Đang chờ điều chỉnh hóa đơn',
			58 => 'F_IGN4 - Đang chờ gửi ảnh nộp tiền xác nhận khoản treo',
			59 => 'F_IGN5 - Đang chờ giảm phí phạt, đóng hợp đồng',
			60 => 'F_IGN6 - Khách hàng đã thanh toán trước khi nhân viên được phân hợp đồng',
			61 => 'F_LEM - Để lại lời nhắn cho người thân',
			62 => 'F_LEM_O - Để lại lời nhắn cho người khác',
			63 => 'F_PA - Đã thanh toán',
			64 => 'F_PTP - Hứa thanh toán',
			65 => 'F_RTP - Từ chối thanh toán',
			66 => 'F_WFP - Khách hàng đã thanh toán đang chờ Phòng Kế toán kiểm tra',
			67 => 'F_NLA - KH chưa từng ở địa chỉ được cung cấp',
			68 => 'F_CGI - KH đi tù/nghĩa vụ/cai nghiện/tâm thần',
			69 => 'F_LST - Mất việc làm, phá sản, làm ăn thua lỗ',
			70 => 'F_WAS - Chờ thu nhập, trợ cấp của người thân',
			71 => 'F_MCW - KH bị bệnh, tai nạn',
			72 => 'F_CTI - Thiên tai, mất mùa, bão lũ…',
			73 => 'F_RMA - Người thân ốm đau, tai nạn',
			74 => 'F_TER - KH thanh lý hợp đồng',
			75 => 'F_FFA - Thực địa không còn khả năng tác động và đủ điều kiện chuyển cho Pháp lý'
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('bucket')) {
	function bucket($status = null)
	{
		$leadstatus = [
			'B0' => 'B0',
			'B1' => 'B1',
			'B2' => 'B2',
			'B3' => 'B3',
			'B4' => 'B4',
			'B5' => 'B5',
			'B6' => 'B6',
			'B7' => 'B7',
			'B8' => 'B8',

		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('get_bucket')) {
	function get_bucket($time = 0)
	{
		if ($time < 0) {
			$bucket = 'B0';
		} else if ($time == 0 ) {
			$bucket = 'B0';
		} else if ($time >= 1 && $time <= 9) {
			$bucket = 'B1';
		} else if ($time >= 10 && $time <= 30) {
			$bucket = 'B1';
		} else if ($time >= 31 && $time <= 60) {
			$bucket = 'B2';
		} else if ($time >= 61 && $time <= 90) {
			$bucket = 'B3';
		} else if ($time >= 91 && $time <= 120) {
			$bucket = 'B4';
		} else if ($time >= 121 && $time <= 150) {
			$bucket = 'B5';
		} else if ($time >= 151 && $time <= 180) {
			$bucket = 'B6';
		}else if ($time >= 181 && $time <= 360) {
			$bucket = 'B7';
		} else {
			$bucket = 'B8';
		}
		return $bucket;
	}
}
if (!function_exists('gic_easy')) {
	function gic_easy($status = null)
	{
		$leadstatus = [
			'348000+a00e87b0-b9d8-465a-abc4-e92e44e86f33' => 'GIC_EASY_20',
			'398000+b32bf4ab-bc52-4a3b-8982-3a9ada09345c' => 'GIC_EASY_40',
			'598000+f358acf6-d53e-4aa6-898d-d5d4c14785f3' => 'GIC_EASY_70'
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}
if (!function_exists('money_gic_plt')) {
	function money_gic_plt($status = null)
	{
		$leadstatus = [
			'COPPER' => 199000,
			'SILVER' => 299000,
			'GOLD' => 499000
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}
if (!function_exists('recoding_status')) {
	function recoding_status($status = null)
	{
		$leadstatus = [
			'NO_USER_RESPONSE' => 'Không nghe máy',
			'USER_BUSY' => 'User bận',
			'NORMAL_CLEARING' => 'Thành công',
			'CALL_REJECTED' => 'Từ chối',
			'ORIGINATOR_CANCEL' => 'Người gọi dừng',
			'NO_ANSWER' => 'Cuộc gọi nhỡ'
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}
if (!function_exists('reason')) {
	function reason($status = null, $hide = false)
	{
		$leadstatus = [
			1 => 'Xe đang trả góp',
			2 => 'Nhu cầu vay nhiều hơn',
			3 => 'Xe không chính chủ',
			4 => 'Không có HK hoặc không làm việc tại nội thành HN',
			5 => 'Không có đăng ký xe bản gốc',
			6 => 'Không thuộc sản phẩm hỗ trợ',
			7 => 'Không có xe máy/ô tô',
			8 => 'Không muốn giữ đăng ký xe',
			9 => 'Không có nhu cầu vay nữa',
			10 => 'Nhầm máy',
			11 => 'Chê lãi cao',
			12 => 'Không thuộc khu vực hỗ trợ',
			13 => 'Thuê bao',
			14 => 'Không chứng minh được thu nhập',
			15 => 'Không nghe máy nhiều lần',
			16 => 'Đời xe không hỗ trợ',
			17 => 'Đã vay bên tổ chức khác',
			18 => 'Hẹn đến PGD',
			19 => 'Khách hẹn qua nhà hỗ trợ trực tiếp',
			20 => 'Thiếu giấy tờ',
			21 => 'Đã liên hệ lại và thay đổi trạng thái',
			22 => 'Gọi lại lần 1',
			23 => 'Gọi lại lần 2',
			24 => 'Gọi lại lần 3',
			25 => 'Gọi lại lần 4',
			26 => 'Gọi lại lần 5',
			27 => 'Gọi lại lần 6',
			28 => 'Không muốn thẩm định nhà/ công ty',
			29 => 'Xe KCC- không nhận lương CK',
			30 => 'Xe KCC-không có KT1',
			31 => 'Dư nợ cao',
			32 => 'Đã ra PGD',
			33 => 'Đeo bám quá lâu mà vẫn đang suy nghĩ khoản vay',
			34 => 'Sai số (Số ĐT báo không đúng) ',
			35 => 'Trùng hồ sơ',
			36 => 'Tham khảo, chưa muốn vay ',
			37 => 'Không khai báo thông tin',
			38 => 'Nhu cầu vay ít hơn',
			39 => 'Không đồng ý thời hạn vay',
			40 => 'Không đồng ý giữ xe',
			41 => 'Không đủ tuổi quy định',
			42 => 'Không có bản gốc CMT/ CCCD/Hộ chiếu',
			43 => 'Không có SHK/ giấy tờ chứng minh nơi ở',
			44 => 'Không có giấy tờ chứng minh sở hữu xe',
			45 => 'Không có đăng kiểm xe',
			46 => 'Lý do khác',
			47 => 'Xe KCC- HCM',
			48 => 'Xe KCC - Mekong',
			49 => 'Sim không chính chủ',
			50 => 'Xe KCC- HN'


		];
		if ($hide) {
			$remove = [1, 3, 4, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 31, 32, 33];
			foreach ($remove as $k => $v) {
				unset($leadstatus[$v]);
			}
		}
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('region')) {
	function region($status = null)
	{
		$leadstatus = [
			'VHN' => 'Vùng Hà Nội',
			'VDB' => 'Vùng Đông Bắc',
			'VTB' => 'Vùng Tây Bắc',
			'VMT1' => 'Miền trung 1: Thanh Hoá - Hà Tĩnh',
			'VMT2' => 'Miền trung 2: Quảng Bình- Phú Yên',
			'VMT3' => 'Miền trung 3: Tây Nguyên',
			'VHCM' => 'Vùng HCM',
			'VMD' => 'Vùng Miền Đông',
			'VMC' => 'Vùng Mê Công',
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}
if (!function_exists('domain')) {
	function domain($status = null)
	{
		$leadstatus = [
			'MB' => 'Miền Bắc',
			'MT' => 'Miền Trung',
			'MN' => 'Miền Nam',

		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}
if (!function_exists('lead_status')) {
	function lead_status($status = null, $hide = false)
	{
		$leadstatus = [
			1 => 'Mới',
			2 => 'Đồng ý vay',
			3 => 'Chưa liên lạc được',
			4 => 'Không đủ điều kiện vay',
			5 => 'Đang suy nghĩ',
			6 => 'Đang chờ duyệt hồ sơ',
			7 => 'Chưa tư vấn được',
			8 => 'Đã ký HĐ',
			9 => 'Đã ra PGD',
			10 => 'Chờ gọi lại lần 1',
			11 => 'Chờ gọi lại lần 2',
			12 => 'Chờ gọi lại lần 3',
			13 => 'Chờ gọi lại lần 4',
			14 => 'Chờ gọi lại lần 5',
			15 => 'Chờ gọi lại lần 6',
			16 => 'Chờ gọi lại lần 7',
			17 => 'Chờ gọi lại lần 8',
			18 => 'Chờ gọi lại lần 9',
			19 => 'Hủy',
			30 => 'Phòng giao dịch tạo'

		];
		if ($hide) {
			$remove = [3, 4, 7];
			foreach ($remove as $k => $v) {
				unset($leadstatus[$v]);
			}
		}
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('lead_source')) {
	function lead_source($status = null)
	{
		$leadstatus = [
			1 => 'Facebook',
			2 => 'Google',
			3 => 'Zalo',
			4 => 'Tiktok',
			5 => 'Cốc côc',
			6 => 'Website'
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('lead_nguon')) {
	function lead_nguon($status = null)
	{
		$leadstatus = [
			1 => 'Digital',
			2 => 'TLS Tự kiếm',
			3 => 'Tổng đài',
			4 => 'Giới thiệu',
			5 => 'Đối tác',
			6 => 'Fanpage',
			7 => 'Nguồn khác',
			12 => 'Nguồn App Mobile',
			11 => 'KH giới thiệu KH',
			'VM' => 'VM',
			'trandata' => 'trandata',
			'TS' => 'TS',
			'VPS' => 'VPS',
			'MB' => 'MB',
			14 => "Tool FB",
			15 => "Tiktok",
			16 => 'Remarketing',
			'Homedy' => 'Homedy'

		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('status_pgd')) {
	function status_pgd($status = null)
	{
		$leadstatus = [
			1 => 'Mới',
			2 => 'Ko thuộc khu vực hỗ trợ',
			3 => 'Ko thuộc sản phẩm hỗ trợ',
			4 => 'Ko cung cấp được SHK/KT3',
			5 => 'Ko cung cấp được thông tin thu nhập',
			6 => 'Ko cung cấp được ĐKX/HĐMB',
			7 => 'Ko đồng ý giữ ĐKX/HĐMB',
			8 => 'Trả về CSKH',
			9 => 'Thuê bao/Không nghe máy',
			10 => 'Khách hết nhu cầu',
			11 => 'Khách chê lãi cao',
			12 => 'Khách từ chối vì duyệt thấp',
			13 => 'Xe ko chính chủ',
			14 => 'Tài sản giá trị thấp',
			15 => 'Đeo bám',
			16 => 'Hủy',
			17 => 'Đang xử lý',
			18 => 'Đã giải ngân',
			19 => 'Chờ gọi lại lần 1',
			20 => 'Chờ gọi lại lần 2',
			21 => 'Chờ gọi lại lần 3',
			22 => 'Chờ gọi lại lần 4',
			23 => 'Chờ gọi lại lần 5',
			24 => 'Chờ gọi lại lần 6',
			25 => 'Chờ gọi lại lần 7',
			26 => 'Chờ gọi lại lần 8',
			27 => 'Chờ gọi lại lần 9',
			28 => 'Đang suy nghĩ',
			29 => 'Trùng PGD tự kiếm',
			30 => 'Chờ KH bổ sung hồ sơ',
			31 => 'KH hẹn ra PGD',
			32 => 'KH thuộc khu vực cách ly/phong toả'

		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
function encrypt($data, $password = "df15bfa89439693e")
{
	$en_text = base64_encode($data);
	return $en_text;
}

if (!function_exists('lead_area')) {
	function lead_area($status = null)
	{
		$leadstatus = [
			1 => 'Hà Nội',
			2 => 'HCMC'

		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('lead_type_finance')) {
	function lead_type_finance($status = null)
	{
		$lead_type_finance = [
			1 => "Cầm cố ô tô",
			2 => "Cầm cố xe máy",
			3 => "Đăng kí ô tô",
			4 => "Đăng kí xe máy",
			5 => "Đăng kí bảo hiểm",
			6 => "Kinh doanh Online",
			7 => "Ứng tiền cho tài xế công nghệ",
			8 => "Sổ đỏ",
			9 => "Sổ hồng, hợp đồng mua bán căn hộ",
		];
		if ($status === null) return $lead_type_finance;
		foreach ($lead_type_finance as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}

if (!function_exists('lead_obj')) {
	function lead_obj($status = null)
	{
		$lead_obj = [
			1 => "Cá nhân",
			2 => "Doanh nghiệp",
			3 => "Chủ hộ kinh doanh"
		];
		if ($status === null) return $lead_obj;
		foreach ($lead_obj as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $lead_obj;
	}
}
function stars($phone)
{
	$times = strlen(trim(substr($phone, 4, 5)));
	$star = '';
	for ($i = 0; $i < $times; $i++) {
		$star .= '*';
	}
	return $star;
}

function hide_phone($phone)
{
	$result = str_replace(substr($phone, 4, 4), stars($phone), $phone);
	return $result;
}

if (!function_exists('loan_time')) {
	function loan_time($status = null)
	{
		$loan_time = [
			1 => "1 Tháng",
			3 => "3 Tháng",
			6 => "6 Tháng",
			9 => "9 Tháng",
			12 => "12 Tháng",

		];
		if ($status === null) return $loan_time;
		foreach ($loan_time as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
if (!function_exists('type_repay')) {
	function type_repay($status = null)
	{
		$type_repay = [
			1 => "Dư nợ giảm dần",
			2 => "Lãi hàng tháng,gốc cuối kì"
		];
		if ($status === null) return $type_repay;
		foreach ($type_repay as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}

function count_values($array, $colum1 = "", $value1 = "", $colum2 = "", $value2 = "", $colum3 = "", $value3 = "")
{
	$count = 0;
	foreach ($array as $v) {
		if (empty($v[$colum1]))
			$v[$colum1] = '';
		if (empty($v[$colum2]))
			$v[$colum2] = '';
		if (empty($v[$colum3]))
			$v[$colum3] = '';

		if ($colum1 == '') {
			$count++;
		} else if ($colum2 == '') {
			if ($v[$colum1] == $value1)
				$count++;
		} else if ($colum3 == '') {
			if ($v[$colum1] == $value1 && $v[$colum2] == $value2)
				$count++;
		} else {
			if ($v[$colum1] == $value1 && $v[$colum2] == $value2 && $v[$colum3] == $value3)
				$count++;
		}
	}
	return $count;
}

function sum_values($array, $colum1 = "", $value1 = "", $colum2 = "", $value2 = "", $colum3 = "", $value3 = "", $col_sum = "")
{
	$sum = 0;
	foreach ($array as $v) {
		if (empty($v[$colum1]))
			$v[$colum1] = 0;
		if (empty($v[$colum2]))
			$v[$colum2] = 0;
		if (empty($v[$colum3]))
			$v[$colum3] = 0;
		if (empty($v[$col_sum]))
			$v[$col_sum] = 0;
		if ($colum1 == '') {
			$sum++;
		} else if ($colum2 == '') {
			if ($v[$colum1] == $value1)
				$sum += $v[$col_sum];
		} else if ($colum3 == '') {
			if ($v[$colum1] == $value1 && $v[$colum2] == $value2)
				$sum += (int)$v[$col_sum];
		} else {
			if ($v[$colum1] == $value1 && $v[$colum2] == $value2 && $v[$colum3] == $value3)
				$sum += (int)$v[$col_sum];
		}
	}
	return $sum;
}

function sum_values_transaction($array, $colum1 = "", $value1 = "", $colum2 = "", $value2 = "", $colum3 = "", $value3 = "", $col_sum = "")
{
	$sum = 0;
	foreach ($array as $v) {
		if (empty($v[$colum1]['id']))
			$v[$colum1]['id'] = 0;
		if (empty($v[$colum2]))
			$v[$colum2] = 0;
		if (empty($v[$colum3]))
			$v[$colum3] = 0;
		if (empty($v[$col_sum]))
			$v[$col_sum] = 0;
		if ($colum1 == '') {
			$sum++;
		} else if ($colum2 == '') {
			if ($v[$colum1]['id'] == $value1)
				$sum += $v[$col_sum];
		} else if ($colum3 == '') {
			if ($v[$colum1]['id'] == $value1 && $v[$colum2] == $value2)
				$sum += (int)$v[$col_sum];
		} else {
			if ($v[$colum1]['id'] == $value1 && $v[$colum2] == $value2 && $v[$colum3] == $value3)
				$sum += (int)$v[$col_sum];
		}
	}
	return $sum;
}


function unique_multidim_array($array, $key, $key1)
{
	$temp_array = array();
	$i = 0;
	$key_array = array();

	foreach ($array as $val) {
		if (!in_array($val[$key][$key1], $key_array) && $val != '') {
			$key_array[$i] = $val[$key][$key1];
			$temp_array[$i] = $val;
		}
		$i++;
	}
	return $temp_array;
}

function get_value_Url($url, $parameter_name)
{
	$parts = parse_url($url);
	if (isset($parts['query'])) {
		parse_str($parts['query'], $query);
		if (isset($query[$parameter_name])) {
			return $query[$parameter_name];
		} else {
			return "";
		}
	} else {
		return "";
	}
}

function get_values($array, $colum1 = "", $value1 = "", $colum2 = "", $value2 = "", $colum3 = "", $value3 = "", $find = "", $all = true, $sum = false)
{
	$n = 0;
	$count = ($sum) ? 0 : [];
	foreach ($array as $key => $v) {

		if (empty($v[$colum1]))
			$v[$colum1] = '';
		if (empty($v[$colum2]))
			$v[$colum2] = '';
		if (empty($v[$colum3]))
			$v[$colum3] = '';
		if (empty($v[$find]))
			$v[$find] = '';
		if (!$sum) {
			if ($all) {
				if ($colum3 != "") {

					if ($v[$colum1] === $value1 && $v[$colum2] === $value2 && $v[$colum3] === $value3) {
						$count += [$n => $v[$find]];
						$n++;
					}
				} else
					if ($colum2 != "") {

						if ($v[$colum1] === $value1 && $v[$colum2] === $value2) {
							$count += [$n => $v[$find]];
							$n++;
						}
					} else {

						if ($v[$colum1] === $value1) {
							$count += [$n => $v[$find]];
							$n++;
						}
					}
			} else {
				if ($colum3 != "") {
					if ($v[$colum1] === $value1 && $v[$colum2] === $value2 && $v[$colum3] === $value3)
						$count = $v[$find];
				} else
					if ($colum2 != "") {
						if ($v[$colum1] === $value1 && $v[$colum2] === $value2)
							$count = $v[$find];
					} else {
						if ($v[$colum1] === $value1)
							$count = $v[$find];
					}
			}
		} else {
			if ($colum3 != "") {
				if ($v[$colum1] === $value1 && $v[$colum2] === $value2 && $v[$colum3] === $value3)
					$count += $v[$find];
			} else
				if ($colum2 != "") {
					if ($v[$colum1] === $value1 && $v[$colum2] === $value2)
						$count += $v[$find];
				} else {
					if ($v[$colum1] === $value1)
						$count += $v[$find];
				}

		}

	}
	return $count;
}

function get_values_transaction($array, $colum1 = "", $value1 = "", $colum2 = "", $value2 = "", $colum3 = "", $value3 = "", $find = "", $all = true, $sum = false)
{
	$n = 0;
	$count = ($sum) ? 0 : [];
	foreach ($array as $key => $v) {

		if (empty($v[$colum1]))
			$v[$colum1] = '';
		if (empty($v[$colum2]['name']))
			$v[$colum2]['name'] = '';
		if (empty($v[$colum3]))
			$v[$colum3] = '';
		if (empty($v[$find]))
			$v[$find] = '';
		if (!$sum) {
			if ($all) {
				if ($colum3 != "") {

					if ($v[$colum1] === $value1 && $v[$colum2]['name'] === $value2 && $v[$colum3] === $value3) {
						$count += [$n => $v[$find]];
						$n++;
					}
				} else
					if ($colum2 != "") {

						if ($v[$colum1] === $value1 && $v[$colum2]['name'] === $value2) {
							$count += [$n => $v[$find]];
							$n++;
						}
					} else {

						if ($v[$colum1] === $value1) {
							$count += [$n => $v[$find]];
							$n++;
						}
					}
			} else {
				if ($colum3 != "") {
					if ($v[$colum1] === $value1 && $v[$colum2]['name'] === $value2 && $v[$colum3] === $value3)
						$count = $v[$find];
				} else
					if ($colum2 != "") {
						if ($v[$colum1] === $value1 && $v[$colum2]['name'] === $value2)
							$count = $v[$find];
					} else {
						if ($v[$colum1] === $value1)
							$count = $v[$find];
					}
			}
		} else {
			if ($colum3 != "") {
				if ($v[$colum1] === $value1 && $v[$colum2]['name'] === $value2 && $v[$colum3] === $value3)
					$count += $v[$find];
			} else
				if ($colum2 != "") {
					if ($v[$colum1] === $value1 && $v[$colum2]['name'] === $value2)
						$count += $v[$find];
				} else {
					if ($v[$colum1] === $value1)
						$count += $v[$find];
				}

		}

	}
	return $count;
}

function get_list_term($data, $utm_campaigns, $utm_source)
{
	$arr_term = [];
	$i = 0;
	foreach ($data as $key => $value) {
		if ($value['utm_campaign'] == $utm_campaigns && $value['utm_source'] == $utm_source) {
			$i++;
			$arr_term += [$i => $value];
		}

	}
	return $arr_term;
}

function gen_html_QC($arr_return_QC_nguon, $arr_return_QC_SOU, $arr_return_QC_CAM)
{
	$n = 1;
	$o = 1;
	$y = 1;
	$html = '';
	if (!empty($arr_return_QC_nguon))
		foreach ($arr_return_QC_nguon as $key => $value) {

			$html .= '<tr data-id="' . $n++ . '" data-parent="0">';
			$html .= '<td></td>';
			$html .= '<td data-column="name"></td><td>';
			$html .= ($value['source'] == "") ? "Khác" : $value['source'] . '</td>';
			$html .= '<td></td>';
			$html .= '<td></td>';
			$html .= '<td l class="table-active" align="center">' . $value['total_lead_all'] . '</td>';
			$html .= '<td lq align="center">' . $value['total_lead_qualified'] . '</td><td lql align="center">';
			$html .= ($value['total_lead_all'] == '0') ? 0 : round((($value['total_lead_qualified'] / $value['total_lead_all']) * 100), 2) . '';
			$html .= '%</td><td c class="table-warning" align="center">' . $value['total_contract_disbursement'] . '</td><td 4 class="table-warning" align="center">';
			$html .= ($value['total_lead_all'] == '0') ? 0 : round((($value['total_contract_disbursement'] / (int)$value['total_lead_all']) * 100), 2) . '';
			$html .= '%</td> <td 1  align="center">';
			$html .= '' . ($value['total_lead_qualified'] == 0) ? 0 : round((($value['total_contract_disbursement'] / $value['total_lead_qualified']) * 100), 2) . '';
			$html .= '%</td><td 2 style="background-color: #ffe0b3;" align="center" >' . number_format($value['total_debt']) . '</td><td 3 style="background-color: #ffe0b3;" align="center">';
			$html .= ($value['total_contract_disbursement'] == 0) ? 0 : number_format((int)(($value['total_debt'] / $value['total_contract_disbursement']))) . '';
			$html .= '</td></tr>';
			$o = $n;
			$y = $n;
			if ($value['source'] == 'Digital') {
				foreach ($arr_return_QC_SOU as $key1 => $value1) {

					$a = $n;
					$html .= '<tr data-id="' . (int)$n++ . '"  data-parent="' . (int)($y - 1) . '">';
					$html .= '<td></td>';
					$html .= '<td data-column="name"> </td>';
					$html .= '<td></td><td><span class="size_col_mkt">';
					$html .= ($value1['utm_source'] == "") ? "Khác" : $value1['utm_source'] . '</span></td>';
					$html .= '<td></td>';
					$html .= '<td class="table-active" align="center">' . $value1['total_lead_all'] . '</td>';
					$html .= '<td align="center">' . $value1['total_lead_qualified'] . '</td><td align="center">';
					$html .= ($value1['total_lead_all'] == 0) ? 0 : round((($value1['total_lead_qualified'] / $value1['total_lead_all']) * 100), 2) . '';
					$html .= '%</td><td class="table-warning" align="center">' . $value1['total_contract_disbursement'] . '</td><td class="table-warning" align="center">';
					$html .= ($value1['total_lead_all'] == 0) ? 0 : round((($value1['total_contract_disbursement'] / (int)$value1['total_lead_all']) * 100), 2) . '';
					$html .= '%</td> <td 1  align="center">';
					$html .= ($value1['total_lead_qualified'] == 0) ? 0 : round((($value1['total_contract_disbursement'] / $value1['total_lead_qualified']) * 100), 2) . '';
					$html .= '%</td><td style="background-color: #ffe0b3;" align="center">' . number_format($value1['total_debt']) . '</td><td style="background-color: #ffe0b3;" align="center">';
					$html .= ($value1['total_contract_disbursement'] == 0) ? 0 : number_format((int)(($value1['total_debt'] / $value1['total_contract_disbursement']))) . '';
					$html .= '</td> ';
					$html .= '</tr>';

					if (count($arr_return_QC_CAM) > 0) {

						foreach ($arr_return_QC_CAM as $key2 => $value2) {
							if ($value2['utm_source'] == $value1['utm_source']) {
								//$o=$n-1;
								// $o= $y+1;
								$html .= '<tr  data-id="' . $n++ . '" data-parent="' . $a . '" >';
								$html .= '<td></td>';
								$html .= '<td></td>';
								$html .= '<td></td>';
								$html .= '<td></td>';
								$html .= '<td><span class="size_col_mkt">' . $value2['utm_campaign'] . '</span></td>';
								$html .= '<td class="table-active" align="center">' . $value2['total_lead_all'] . '</td>';
								$html .= '<td align="center">' . $value2['total_lead_qualified'] . '</td><td align="center">';
								$html .= ($value2['total_lead_all'] == 0) ? 0 : round((($value2['total_lead_qualified'] / $value2['total_lead_all']) * 100), 2) . '';
								$html .= '%</td><td class="table-warning" align="center">' . $value2['total_contract_disbursement'] . '</td><td class="table-warning" align="center">';
								$html .= ($value2['total_lead_all'] == 0) ? 0 : round((($value2['total_contract_disbursement'] / (int)$value2['total_lead_all']) * 100), 2) . '';
								$html .= '%</td> <td 1  align="center">';
								$html .= ($value2['total_lead_qualified'] == 0) ? 0 : round((($value2['total_contract_disbursement'] / $value2['total_lead_qualified']) * 100), 2) . '';
								$html .= '%</td><td style="background-color: #ffe0b3;" align="center">' . number_format($value2['total_debt']) . '</td><td style="background-color: #ffe0b3;" align="center">';
								$html .= ($value2['total_contract_disbursement'] == 0) ? 0 : number_format((int)(($value2['total_debt'] / $value2['total_contract_disbursement']))) . '';
								$html .= '</td> ';
								$html .= '</tr>';
							}

						}
					}
				}

			}
		}
	//dd($html);
	return $html;

}

function gen_html_lead_cancel($arr_return_QC_SOU, $arr_return_QC_CAM)
{
	$n = 1;
	$o = 1;
	$y = 1;
	$html = '';
	if (!empty($arr_return_QC_SOU))
		foreach ($arr_return_QC_SOU as $key => $value) {

			$html .= '<tr data-id="' . $n++ . '" data-parent="0">';
			$html .= '<td data-column="name"></td><td>';
			$html .= ($value['utm_source'] == "") ? "Khác" : $value['utm_source'] . '</td>';
			$html .= '<td></td>';
			$html .= '<td l class="table-active" align="center">' . $value['lead_cancel'] . '</td>';
			$html .= '<td lql align="center">';
			$html .= ($value['total_lead_cancel'] == '0') ? 0 : (int)(($value['lead_cancel'] / $value['total_lead_cancel']) * 100);
			$html .= '%</td><td 4 class="table-warning" align="center">';
			$html .= ($value['total_lead'] == '0') ? 0 : (int)(($value['lead_cancel'] / (int)$value['total_lead']) * 100) . '';
			$html .= '%</td>';

			$html .= '</tr>';
			$o = $n;
			$y = $n;

			foreach ($arr_return_QC_CAM as $key1 => $value1) {
				if ($value['utm_source'] == $value1['utm_source']) {
					$a = $n;

					$html .= '<tr data-id="' . (int)$n++ . '"  data-parent="' . (int)($y - 1) . '">';
					$html .= '<td data-column="name"> </td>';
					$html .= '<td></td><td>';
					$html .= ($value1['utm_campaign'] == "") ? "Khác" : $value1['utm_campaign'] . '</td>';
					$html .= '<td l class="table-active" align="center">' . $value1['lead_cancel'] . '</td>';
					$html .= '<td lql align="center">';
					$html .= ($value1['total_lead_cancel'] == '0') ? 0 : (int)(($value1['lead_cancel'] / $value1['total_lead_cancel']) * 100);
					$html .= '%</td><td 4 class="table-warning" align="center">';
					$html .= ($value1['total_lead'] == '0') ? 0 : (int)(($value1['lead_cancel'] / (int)$value1['total_lead']) * 100) . '';
					$html .= '%</td>';
					$html .= '</tr>';
				}


			}


		}
	//dd($html);
	return $html;

}

function gen_html_lead_cancel_q($arr_return_QC_SOU, $arr_return_QC_CAM)
{
	$n = 1;
	$o = 1;
	$y = 1;
	$html = '';
	if (!empty($arr_return_QC_SOU))
		foreach ($arr_return_QC_SOU as $key => $value) {

			$html .= '<tr data-id="' . $n++ . '" data-parent="0">';
			$html .= '<td data-column="name"></td><td>';
			$html .= ($value['utm_source'] == "") ? "Khác" : $value['utm_source'] . '</td>';
			$html .= '<td></td>';
			$html .= '<td l class="table-active" align="center">' . $value['lead_cancel'] . '</td>';
			$html .= '<td lql align="center">';
			$html .= ($value['total_lead_cancel'] == '0') ? 0 : (int)(($value['lead_cancel'] / $value['total_lead_cancel']) * 100);
			$html .= '%</td><td 4 class="table-warning" align="center">';
			$html .= ($value['total_lead'] == '0') ? 0 : (int)(($value['lead_cancel'] / (int)$value['total_lead']) * 100) . '';
			$html .= '%</td>';

			$html .= '</tr>';
			$o = $n;
			$y = $n;

			foreach ($arr_return_QC_CAM as $key1 => $value1) {

				if ($value['utm_source'] == $value1['utm_source']) {
					$a = $n;

					$html .= '<tr data-id="' . (int)$n++ . '"  data-parent="' . (int)($y - 1) . '">';
					$html .= '<td data-column="name"> </td>';
					$html .= '<td></td><td>';
					$html .= ($value1['utm_campaign'] == "") ? "Khác" : $value1['utm_campaign'] . '</td>';
					$html .= '<td l class="table-active" align="center">' . $value1['lead_cancel'] . '</td>';
					$html .= '<td lql align="center">';
					$html .= ($value1['total_lead_cancel'] == '0') ? 0 : (int)(($value1['lead_cancel'] / $value1['total_lead_cancel']) * 100);
					$html .= '%</td><td 4 class="table-warning" align="center">';
					$html .= ($value1['total_lead'] == '0') ? 0 : (int)(($value1['lead_cancel'] / (int)$value1['total_lead']) * 100) . '';
					$html .= '%</td>';
					$html .= '</tr>';
				}


			}


		}
	//dd($html);
	return $html;

}

function gen_html_lead_reason($arr_data_reason)
{
	$n = 1;
	$html = '';
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {

			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name">' . $n++ . '</td><td>';
			$html .= ($value['utm_source'] == "") ? "-" : $value['utm_source'] . '</td><td>';
			$html .= ($value['utm_campaign'] == "") ? "-" : $value['utm_campaign'] . '</td><td>';
			$html .= ($value['reason'] == "") ? "Khác" : $value['reason'] . '</td>';
			$html .= '<td l class="table-active" align="center">' . $value['lead_cancel'] . '</td>';

			$html .= '<td lql align="center">';
			$html .= ($value['total_lead'] == '0') ? 0 : (int)(($value['lead_cancel'] / $value['total_lead']) * 100);
			$html .= '%</td><td 4 class="table-warning" align="center">';
			$html .= ($value['total_lead_cancel'] == '0') ? 0 : (int)(($value['lead_cancel'] / (int)$value['total_lead_cancel']) * 100) . '';
			$html .= '%</td>';

			$html .= '</tr>';
		}
	return $html;
}

function gen_html_lead_reason_q($arr_data_reason)
{
	$n = 1;
	$html = '';
	foreach ($arr_data_reason as $key => $value) {

		$html .= '<tr data-parent="0">';
		$html .= '<td data-column="name">' . $n++ . '</td><td>';
		$html .= ($value['utm_source'] == "") ? "-" : $value['utm_source'] . '</td><td>';
		$html .= ($value['utm_campaign'] == "") ? "-" : $value['utm_campaign'] . '</td><td>';
		$html .= ($value['reason'] == "") ? "Khác" : $value['reason'] . '</td>';
		$html .= '<td l class="table-active" align="center">' . $value['lead_cancel'] . '</td>';
		$html .= '<td lql align="center">';
		$html .= ($value['total_lead'] == '0') ? 0 : (int)(($value['lead_cancel'] / $value['total_lead']) * 100);
		$html .= '%</td><td 4 class="table-warning" align="center">';
		$html .= ($value['total_lead_cancel'] == '0') ? 0 : (int)(($value['lead_cancel'] / (int)$value['total_lead_cancel']) * 100) . '';
		$html .= '%</td>';

		$html .= '</tr>';
	}
	return $html;
}

function gen_html_report_lead_reason($arr_data_reason)
{
	$n = 1;
	$html = '';
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {

			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name">' . $n++ . '</td><td>';
			$html .= ($value['reason'] == "") ? "Khác" : $value['reason'] . '</td>';
			$html .= '<td l class="table-active" align="center">' . $value['lead_cancel'] . '</td>';
			$html .= '<td lql align="center">';
			$html .= '</td><td 4 class="table-warning" align="center">';
			$html .= ($value['total_lead_cancel'] == '0') ? 0 : (int)(($value['lead_cancel'] / (int)$value['total_lead_cancel']) * 100) . '';
			$html .= '%</td>';

			$html .= '</tr>';
		}
	return $html;
}

function gen_html_tsl_daily($arr_data_reason)
{
	$n = 1;
	$html = '';
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {

			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name">' . $n++ . '</td><td>';
			$html .= ($value['cskh'] == "") ? "Khác" : $value['cskh'] . '</td>';
			$html .= '<td l class="table-active" align="center">' . $value['kh_new'] . '</td>';
			$html .= '<td lql align="center">';
			$html .= $value['kh_old'] . '</td><td 4 class="table-warning" align="center">';
			$html .= $value['qualifined'] . '</td>';
			$html .= '<td 4 class="table-warning" align="center">' . $value['total_call'] . '</td>';

			$html .= '</tr>';
		}
	return $html;
}

function gen_html_lead_call_statistics_daily($arr_data_reason)
{
	$n = 1;
	$html = '';
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {

			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name">' . $value['daily'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['call'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['call_in'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['call_out'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['call_internal'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['call_ok'] . '</td>';
			$html .= '<td 4 class="table-warning" align="center">' . $value['time'] . '</td>';
			$html .= '<td 4 class="table-warning" align="center">' . $value['time_call_in'] . '</td>';
			$html .= '<td 4 class="table-warning" align="center">' . $value['time_call_out'] . '</td>';
			$html .= '<td 4 class="table-warning" align="center">' . $value['time_call_internal'] . '</td>';

			$html .= '</tr>';
		}
	return $html;
}

function gen_html_lead_daily($arr_data_reason)
{
	$n = 1;
	$html = '';
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {

			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name">' . $value['daily'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['cham_soc_tiep'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['chua_nghe_may'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['chuyen_ve_pgd'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['lead_cham_soc_tiep'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['lead_chua_nghe_may'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['lead_huy'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['lead_ton'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['lead_pgd_divide_xu_ly'] . '%</td>';
			$html .= '<td class="table-warning" align="center">' . $value['lead_digital'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['lead_tongdai_add_ngoai'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['total_call'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['trung_binh_tts_ngay'] . '</td>';


			$html .= '</tr>';
		}
	return $html;
}

function gen_html_recording($arr_data_recording)
{
	$n = 0;
	$html = '';
	if (!empty($arr_data_recording))
		foreach ($arr_data_recording as $key => $value) {
			$from_email = (!empty($value['fromUser']['email'])) ? $value['fromUser']['email'] : "";
			$to_email = (!empty($value['toUser']['email'])) ? $value['toUser']['email'] : "";
			$from_ext = (!empty($value['fromUser']['ext'])) ? $value['fromUser']['ext'] : "";
			$to_ext = (!empty($value['toUser']['ext'])) ? $value['toUser']['ext'] : "";
			$code = (!empty($value['code'])) ? "'" . $value['code'] . "'" : "";
			$n++;
			$html .= '<tr>';
			$html .= '<td>' . $n . '</td>';

			$html .= '<td  align="center">' . $value['direction'] . '</td>';
			$html .= '<td  align="center">' . $from_email . '<br>' . $to_email . '</td>';
			$html .= '<td  align="center">' . hide_phone($value['fromNumber']) . '<br>Nhánh:' . $from_ext . '</td>';
			$html .= '<td  align="center">' . hide_phone($value['toNumber']) . '<br>Nhánh:' . $to_ext . '</td>';
			$html .= '<td  align="center">' . $value['hangupCause'] . '</td>';
			$html .= '<td  align="center">Tổng time:' . $value['duration'] . '<br>Tổng time tư vấn: ' . $value['billDuration'] . '</td>';
			$html .= '<td  align="center">';
			if (!empty($value['billDuration'])) {
				$html .= '<a href="javascript:void(0)" class="btn btn-info callmodal" onclick="showModalRecoding(' . $code . ')" > <i class="fa fa-play" aria-hidden="true"  ></i>
                             Recording
                            
                        </a>';
			}
			$html .= '</td>';

			$html .= '</tr>';
		}
	return $html;
}

function gen_html_lead_history($arr_data_lead_history, $group_role, $group_role_tls)
{
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	$key = 0;
	$html = "";
	if (!empty($arr_data_lead_history)) {

		for ($i=0; $i< count($arr_data_lead_history); $i++){

			$created_at = (!empty($arr_data_lead_history[$i]["lead_data"]["updated_at"])) ? date("d/m/Y H:i:s", $arr_data_lead_history[$i]["lead_data"]["updated_at"]) : date("d/m/Y H:i:s", $arr_data_lead_history[$i]["lead_data"]["created_at"]);
			$update_by = (!empty($arr_data_lead_history[$i]["lead_data"]["updated_by"])) ? $arr_data_lead_history[$i]["lead_data"]["updated_by"] : "";
			$old_status = (!empty($arr_data_lead_history[$i]["old_data"]["status_sale"])) ? lead_status($arr_data_lead_history[$i]["old_data"]["status_sale"]) : "";
			$new_status = (!empty($arr_data_lead_history[$i]["lead_data"]["status_sale"])) ? lead_status($arr_data_lead_history[$i]["lead_data"]["status_sale"]) : "";
			$reason_cancel = (!empty($arr_data_lead_history[$i]["lead_data"]["reason_cancel"])) ? reason($arr_data_lead_history[$i]["lead_data"]["reason_cancel"]) : "";
			$tls_note = (!empty($arr_data_lead_history[$i]["lead_data"]["tls_note"])) ? ($arr_data_lead_history[$i]["lead_data"]["tls_note"]) : "";
			$fullname = (!empty($arr_data_lead_history[$i]["lead_data"]["fullname"])) ? ($arr_data_lead_history[$i]["lead_data"]["fullname"]) : "";

			for ($j = $i+1; $j<count($arr_data_lead_history); $j++){
				if ((($arr_data_lead_history[$j]["old_data"]["status_sale"]) == ($arr_data_lead_history[$j]["lead_data"]["status_sale"])) &&  ($arr_data_lead_history[$i]["lead_data"]["updated_by"] == $arr_data_lead_history[$i]["old_data"]["updated_by"])){
					$tls_note = (!empty($arr_data_lead_history[$j]["lead_data"]["tls_note"])) ? ($arr_data_lead_history[$j]["lead_data"]["tls_note"]) : "";
				} else {
					break;
				}

			}

			if (($old_status == $new_status) && ($arr_data_lead_history[$i]["lead_data"]["updated_by"] == $arr_data_lead_history[$i]["old_data"]["updated_by"])){
				continue;
			}
			if (in_array($update_by, $group_role) && !in_array($update_by, $group_role_tls)){
				continue;
			}

			$html .= '<tr>';
			$html .= '<td>' . ++$key . '</td>';
			$html .= '<td>' . $created_at . '</td>';
			$html .= '<td>' . $update_by . '</td>';
			$html .= '<td>' . $fullname . '</td>';
			$html .= '<td>' . $old_status . '</td>';
			$html .= '<td>' . $new_status . '</td>';
			$html .= '<td>' . $reason_cancel . '</td>';
			$html .= '<td style="white-space: normal">' . $tls_note . '</td>';
			$html .= '</tr>';

		}
	}
	return $html;
}

function gen_html_caculator_charge_settlement($arr_data_reason)
{
	$n = 1;
	$html = '';
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {

			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name"></td>';
			$html .= '<td data-column="name">' . $value['tien_goc_con'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['laiPhiDenThoiDiemDaoHan'] . '</td>';
			$html .= '<td class="table-warning" align="center"></td>';
			$html .= '<td class="table-warning" align="center">' . $value['lai_ky'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['phi_tu_van'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['phi_tham_dinh'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['phi_thanh_toan_truoc_han'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['tien_phi_phat_tra_cham'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['day_debt'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . $value['so_ngay_da_vay_hop_dong'] . '</td>';


			$html .= '</tr>';
		}
	return $html;
}

function gen_html_caculator_monthly_fee($arr_data_reason)
{
	$n = 1;
	$html = '';
	$tien_tra_1_ky = 0;
	$phai_tra = 0;
	$lai_ky = 0;
	$phi_tu_van = 0;
	$phi_tham_dinh = 0;
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {
			$tien_tra_1_ky += (float)$value['tien_tra_1_ky'];
			$phai_tra += (float)$value['tien_tra_1_ky'] - (float)$value['tien_goc_1ky'];
			$lai_ky += (float)$value['lai_ky'];
			$phi_tu_van += (float)$value['phi_tu_van'];
			$phi_tham_dinh += (float)$value['phi_tham_dinh'];

			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name"></td>';
			$html .= '<td data-column="name">' . $value['ky_tra'] . '</td>';
			$html .= '<td data-column="name">' . date('d/m/Y', $value['ngay_ky_tra']) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format(round($value['tien_tra_1_ky'])) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format(round((float)$value['tien_tra_1_ky'] - (float)$value['tien_goc_1ky'])) . '</td>';

			$html .= '<td class="table-warning" align="center">' . number_format(round($value['lai_ky'])) . '</td>';

			$html .= '<td class="table-warning" align="center">' . number_format(round($value['phi_tham_dinh'])) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format(round($value['phi_tu_van'])) . '</td>';
			$html .= '</tr>';
			$html .= '</tr>';

		}
	$html .= '<tr  data-parent="0">';
	$html .= '<td data-column="name"><b></b></td>';
	$html .= '<td data-column="name"><b></b></td>';
	$html .= '<td data-column="name"><b>TỔNG</b></td>';
	$html .= '<td class="table-warning" align="center">' . number_format(round($tien_tra_1_ky)) . '</td>';
	$html .= '<td class="table-warning" align="center">' . number_format(round($phai_tra)) . '</td>';

	$html .= '<td class="table-warning" align="center">' . number_format(round($lai_ky)) . '</td>';

	$html .= '<td class="table-warning" align="center">' . number_format(round($phi_tham_dinh)) . '</td>';
	$html .= '<td class="table-warning" align="center">' . number_format(round($phi_tu_van)) . '</td>';
	$html .= '</tr>';
	return $html;
}

function gen_html_caculator_loan($arr_data_reason, $phi_tat_toan = 0)
{
	$n = 1;
	$html = '';
	$tien_tra_1_ky = 0;
	$phai_tra = 0;
	$lai_ky = 0;
	$phi_tu_van = 0;
	$phi_tham_dinh = 0;
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {
			$n = count($arr_data_reason);
			$tien_tra_1_ky += (float)$value['tien_tra_1_ky'];
			$phai_tra += (float)$value['tien_tra_1_ky'] - (float)$value['tien_goc_1ky'];
			$lai_ky += (float)$value['lai_ky'];
			$tien_goc_1ky += (float)$value['tien_goc_1ky'];
			$tien_phi += (float)$value['phi_tu_van'] + (float)$value['phi_tham_dinh'];
			$tien_goc_lai += (float)$value['tien_goc_1ky'] + (float)$value['lai_ky'];
			$so_ngay += (int)$value['so_ngay'];

			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name">' . $value['ky_tra'] . '</td>';
			$html .= '<td data-column="name">' . date('d/m/Y', $value['ngay_ky_tra']) . '</td>';
			$html .= '<td data-column="name">' . $value['so_ngay'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format(round($value['lai_ky'])) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format(round((float)$value['phi_tu_van'] + (float)$value['phi_tham_dinh'])) . '</td>';

			$html .= '<td class="table-warning" align="center">' . number_format(round((float)$value['tien_goc_1ky'] + (float)$value['lai_ky'])) . '</td>';

			$html .= '<td class="table-warning" align="center">' . number_format(round($value['tien_goc_1ky'])) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format(round($value['tien_tra_1_ky'])) . '</td>';
			// if($key==1)
			// {
			// $html.='<td rowspan="'.$n.'">'.number_format($phi_tat_toan).'</td>';
			// }
			$html .= '</tr>';
			$html .= '</tr>';

		}
	$html .= '<tr  data-parent="0">';
	$html .= '<td data-column="name"><b>TỔNG</b></td>';
	$html .= '<td data-column="name"><b></b></td>';
	$html .= '<td data-column="name"><b>' . $so_ngay . '</td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format(round($lai_ky)) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format(round($tien_phi)) . '</b></td>';

	$html .= '<td class="table-warning" align="center"><b>' . number_format(round($tien_goc_lai)) . '</b></td>';

	$html .= '<td class="table-warning" align="center"><b>' . number_format(round($tien_goc_1ky)) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format(round($tien_tra_1_ky)) . '</b></td>';
	// $html.='<td data-column="name"><b></b></td>';
	$html .= '</tr>';
	return $html;
}

function gen_html_report_debt_group_pgd($arr_data_reason)
{
	$n = 1;
	$html = '';
	$total_du_no_giai_ngan = 0;
	$total_du_no_dang_cho_vay = 0;
	$total_du_no_nhom1 = 0;
	$total_du_no_nhom2 = 0;
	$total_du_no_nhom3 = 0;
	$total_du_no_nhom4 = 0;
	$total_du_no_nhom5 = 0;
	$total_du_no_xau = 0;
	$n = 0;
	$tyle_nhom1_giai_ngan = 0;
	$tyle_nhom1_dang_cho_vay = 0;
	$tyle_nhom2_giai_ngan = 0;
	$tyle_nhom2_dang_cho_vay = 0;
	$tyle_nhom3_giai_ngan = 0;
	$tyle_nhom3_dang_cho_vay = 0;
	$tyle_nhom4_giai_ngan = 0;
	$tyle_nhom4_dang_cho_vay = 0;
	$tyle_nhom5_giai_ngan = 0;
	$tyle_nhom5_dang_cho_vay = 0;
	$tyle_noxau_giai_ngan = 0;
	$tyle_noxau_dang_cho_vay = 0;
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {
			$n++;
			$total_du_no_giai_ngan += $value['total_du_no_giai_ngan'];
			$total_du_no_dang_cho_vay += $value['total_du_no_dang_cho_vay'];
			$total_du_no_nhom1 += $value['total_du_no_nhom1'];
			$total_du_no_nhom2 += $value['total_du_no_nhom2'];
			$total_du_no_nhom3 += $value['total_du_no_nhom3'];
			$total_du_no_nhom4 += $value['total_du_no_nhom4'];
			$total_du_no_nhom5 += $value['total_du_no_nhom5'];
			$total_du_no_xau += $value['total_du_no_xau'];
			$tyle_nhom1_giai_ngan += $value['tyle_nhom1_giai_ngan'];
			$tyle_nhom1_dang_cho_vay += $value['tyle_nhom1_dang_cho_vay'];
			$tyle_nhom2_giai_ngan += $value['tyle_nhom2_giai_ngan'];
			$tyle_nhom2_dang_cho_vay += $value['tyle_nhom2_dang_cho_vay'];
			$tyle_nhom3_giai_ngan += $value['tyle_nhom3_giai_ngan'];
			$tyle_nhom3_dang_cho_vay += $value['tyle_nhom3_dang_cho_vay'];
			$tyle_nhom4_giai_ngan += $value['tyle_nhom4_giai_ngan'];
			$tyle_nhom4_dang_cho_vay += $value['tyle_nhom4_dang_cho_vay'];
			$tyle_nhom5_giai_ngan += $value['tyle_nhom5_giai_ngan'];
			$tyle_nhom5_dang_cho_vay += $value['tyle_nhom5_dang_cho_vay'];
			$tyle_noxau_giai_ngan += $value['tyle_noxau_giai_ngan'];
			$tyle_noxau_dang_cho_vay += $value['tyle_noxau_dang_cho_vay'];
		}
	$tyle_nhom1_giai_ngan = ($n > 0) ? round($tyle_nhom1_giai_ngan / $n) : 0;
	$tyle_nhom1_dang_cho_vay = ($n > 0) ? round($tyle_nhom1_dang_cho_vay / $n) : 0;
	$tyle_nhom2_giai_ngan = ($n > 0) ? round($tyle_nhom2_giai_ngan / $n) : 0;
	$tyle_nhom2_dang_cho_vay = ($n > 0) ? round($tyle_nhom2_dang_cho_vay / $n) : 0;
	$tyle_nhom3_giai_ngan = ($n > 0) ? round($tyle_nhom3_giai_ngan / $n) : 0;
	$tyle_nhom3_dang_cho_vay = ($n > 0) ? round($tyle_nhom3_dang_cho_vay / $n) : 0;
	$tyle_nhom4_giai_ngan = ($n > 0) ? round($tyle_nhom4_giai_ngan / $n) : 0;
	$tyle_nhom4_dang_cho_vay = ($n > 0) ? round($tyle_nhom4_dang_cho_vay / $n) : 0;
	$tyle_nhom5_giai_ngan = ($n > 0) ? round($tyle_nhom5_giai_ngan / $n) : 0;
	$tyle_nhom5_dang_cho_vay = ($n > 0) ? round($tyle_nhom5_dang_cho_vay / $n) : 0;
	$tyle_noxau_giai_ngan = ($n > 0) ? round($tyle_noxau_giai_ngan / $n) : 0;
	$tyle_noxau_dang_cho_vay = ($n > 0) ? round($tyle_noxau_dang_cho_vay / $n) : 0;
	$html .= '</tr>';
	$html .= '<tr  data-parent="0">';
	$html .= '<td data-column="name"><b>TỔNG</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format($total_du_no_giai_ngan) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format($total_du_no_dang_cho_vay) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format($total_du_no_nhom1) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format($total_du_no_nhom2) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format($total_du_no_nhom3) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format($total_du_no_nhom4) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format($total_du_no_nhom5) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . number_format($total_du_no_xau) . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom1_giai_ngan . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom1_dang_cho_vay . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom2_giai_ngan . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom2_dang_cho_vay . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom3_giai_ngan . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom3_dang_cho_vay . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom4_giai_ngan . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom4_dang_cho_vay . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom5_giai_ngan . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_nhom5_dang_cho_vay . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_noxau_giai_ngan . '</b></td>';
	$html .= '<td class="table-warning" align="center"><b>' . $tyle_noxau_dang_cho_vay . '</b></td>';


	$html .= '</tr>';
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {
			$n++;
			$total_du_no_giai_ngan += $value['total_du_no_giai_ngan'];
			$total_du_no_dang_cho_vay += $value['total_du_no_dang_cho_vay'];
			$total_du_no_nhom1 += $value['total_du_no_nhom1'];
			$total_du_no_nhom2 += $value['total_du_no_nhom2'];
			$total_du_no_nhom3 += $value['total_du_no_nhom3'];
			$total_du_no_nhom4 += $value['total_du_no_nhom4'];
			$total_du_no_nhom5 += $value['total_du_no_nhom5'];
			$total_du_no_xau += $value['total_du_no_xau'];
			$tyle_nhom1_giai_ngan += $value['tyle_nhom1_giai_ngan'];
			$tyle_nhom1_dang_cho_vay += $value['tyle_nhom1_dang_cho_vay'];
			$tyle_nhom2_giai_ngan += $value['tyle_nhom2_giai_ngan'];
			$tyle_nhom2_dang_cho_vay += $value['tyle_nhom2_dang_cho_vay'];
			$tyle_nhom3_giai_ngan += $value['tyle_nhom3_giai_ngan'];
			$tyle_nhom3_dang_cho_vay += $value['tyle_nhom3_dang_cho_vay'];
			$tyle_nhom4_giai_ngan += $value['tyle_nhom4_giai_ngan'];
			$tyle_nhom4_dang_cho_vay += $value['tyle_nhom4_dang_cho_vay'];
			$tyle_nhom5_giai_ngan += $value['tyle_nhom5_giai_ngan'];
			$tyle_nhom5_dang_cho_vay += $value['tyle_nhom5_dang_cho_vay'];
			$tyle_noxau_giai_ngan += $value['tyle_noxau_giai_ngan'];
			$tyle_noxau_dang_cho_vay += $value['tyle_noxau_dang_cho_vay'];
			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name">' . $value['pgd'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format($value['total_du_no_giai_ngan']) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format($value['total_du_no_dang_cho_vay']) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format($value['total_du_no_nhom1']) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format($value['total_du_no_nhom2']) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format($value['total_du_no_nhom3']) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format($value['total_du_no_nhom4']) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format($value['total_du_no_nhom5']) . '</td>';
			$html .= '<td class="table-warning" align="center">' . number_format($value['total_du_no_xau']) . '%</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom1_giai_ngan'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom1_dang_cho_vay'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom2_giai_ngan'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom2_dang_cho_vay'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom3_giai_ngan'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom3_dang_cho_vay'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom4_giai_ngan'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom4_dang_cho_vay'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom5_giai_ngan'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_nhom5_dang_cho_vay'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_noxau_giai_ngan'] . '</td>';
			$html .= '<td class="table-warning" align="center">' . (int)$value['tyle_noxau_dang_cho_vay'] . '</td>';


		}

	return $html;
}

function gen_html_report_lead_reason_cancel_daily($arr_data_reason)
{
	$n = 1;
	$html = '';
	$sum_lead_cancel = 0;
	$sum_lead_cancel_percent = 0;
	if (!empty($arr_data_reason))
		foreach ($arr_data_reason as $key => $value) {
			$html .= '<tr  data-parent="0">';
			$html .= '<td data-column="name">' . $value['daily'] . '</td>';
			$html .= '<td >';
			$html .= ($value['reason'] == "") ? "Khác" : $value['reason'] . '</td>';
			$html .= '<td l class="table-active" align="center">' . $value['lead_cancel'] . '</td>';
			$html .= '<td l class="table-active">' . "" . '</td>';
			$html .= '<td 4 class="table-warning" align="center">';
			$html .= ($value['total_lead_cancel'] == '0') ? 0 : round((($value['lead_cancel'] / $value['total_lead_cancel']) * 100), 1) . '';
			$html .= '%</td>';
			$html .= '</tr>';

			$sum_lead_cancel += $value['lead_cancel'];
			$sum_lead_cancel_percent += round((($value['lead_cancel'] / $value['total_lead_cancel']) * 100), 1);
		}
	$html .= '<tr data-parent="0">';
	$html .= '<td data-column="name">' . "" . '</td>';
	$html .= '<td data-column="name">' . "<strong>Tổng</strong>" . '</td>';
	$html .= '<td data-column="name" align="center">' . $sum_lead_cancel . '</td>';
	$html .= '<td data-column="name">' . "" . '</td>';
	$html .= '<td data-column="name" align="center">' . $sum_lead_cancel_percent . "%" . '</td>';
	$html .= '</tr>';

	return $html;
}

function gen_html_debt_history($logs)
{
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	$html = "";
	if (!empty($logs)) {
		foreach ($logs as $key => $value) {
			$code_contract = (!empty($value["code_contract"])) ? $value["code_contract"] : "";
			$result_reminder = (!empty($value['new']["result_reminder"])) ? note_renewal($value['new']["result_reminder"]) : "";
			$payment_date = (!empty($value['new']["payment_date"])) ? date('d/m/Y', strtotime($value['new']["payment_date"])) : "";
			$note = (!empty($value['new']["note"])) ? ($value['new']["note"]) : "";
			$created_at = (!empty($value["created_at"])) ? date('d/m/Y', $value["created_at"]) : "";
			$created_by = (!empty($value["created_by"])) ? ($value["created_by"]) : "";

			$html .= '<tr>';
			$html .= '<td>' . ++$key . '</td>';
			$html .= '<td>' . $code_contract . '</td>';
			$html .= '<td>' . $result_reminder . '</td>';
			$html .= '<td>' . $payment_date . '</td>';
			$html .= '<td>' . $note . '</td>';
			$html .= '<td>' . $created_at . '</td>';
			$html .= '<td>' . $created_by . '</td>';
			$html .= '</tr>';
		}
	}
	return $html;
}

if (!function_exists('status_debt_recovery')) {
	function status_debt_recovery($status = null)
	{
		$debt_recovery = array(
			1 => "Đã thu tiền",
			2 => "Hứa thanh toán",
			3 => "Chưa viếng thăm",
			4 => "Đã thu hồi xe",
			5 => "Tiếp tục tác động",
			6 => "Mất khả năng thanh toán"
		);
		if ($status === null) return $debt_recovery;
		foreach ($debt_recovery as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}

if (!function_exists('meet_relatives')) {
	function meet_relatives($status = null)
	{
		$debt_recovery = array(
			1 => 'Khách Hàng',
			2 => 'Vợ Khách Hàng',
			3 => 'Chồng Khách Hàng',
			4 => 'Con Khách Hàng',
			5 => 'Bố Khách Hàng',
			6 => 'Mẹ Khách Hàng',
			7 => 'Anh/Em trai Khách Hàng',
			8 => 'Chị/Em gái Khách Hàng',
			9 => 'Bác/Cậu/Chú/Cô/Dì Khách Hàng',
			10 => 'Người khác',
			11 => 'Không gặp ai'
		);
		if ($status === null) return $debt_recovery;
		foreach ($debt_recovery as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}

if (!function_exists('reason')) {
	function reason($status = null)
	{
		$leadstatus = [
			1 => 'Xe đang trả góp',
			2 => 'Nhu cầu vay nhiều hơn',
			3 => 'Xe không chính chủ',
			4 => 'Không có HK hoặc không làm việc tại nội thành HN',
			5 => 'Không có đăng ký xe bản gốc',
			6 => 'Không thuộc sản phẩm hỗ trợ',
			7 => 'Không có xe máy/ô tô',
			8 => 'Không muốn giữ đăng ký xe',
			9 => 'Không có nhu cầu vay nữa',
			10 => 'Nhầm máy',
			11 => 'Chê lãi cao',
			12 => 'Không thuộc khu vực hỗ trợ',
			13 => 'Thuê bao',
			14 => 'Không chứng minh được thu nhập',
			15 => 'Không nghe máy nhiều lần',
			16 => 'Đời xe không hỗ trợ',
			17 => 'Đã vay bên tổ chức khác',
			18 => 'Hẹn đến PGD',
			19 => 'Khách hẹn qua nhà hỗ trợ trực tiếp',
			20 => 'Thiếu giấy tờ',
			21 => 'Đã liên hệ lại và thay đổi trạng thái',
			22 => 'Gọi lại lần 1',
			23 => 'Gọi lại lần 2',
			24 => 'Gọi lại lần 3',
			25 => 'Gọi lại lần 4',
			26 => 'Gọi lại lần 5',
			27 => 'Gọi lại lần 6',
			28 => 'Không muốn thẩm định nhà/ công ty',
			29 => 'Xe KCC- không nhận lương CK',
			30 => 'Xe KCC-không có KT1',
			31 => 'Dư nợ cao',
			32 => 'Đã ra PGD',
			33 => 'Đeo bám quá lâu mà vẫn đang suy nghĩ khoản vay',
			34 => 'Sai số (Số ĐT báo không đúng) ',
			35 => 'Trùng hồ sơ',
			36 => 'Tham khảo, chưa muốn vay ',
			37 => 'Không khai báo thông tin',
			38 => 'Nhu cầu vay ít hơn',
			39 => 'Không đồng ý thời hạn vay',
			40 => 'Không đồng ý giữ xe',
			41 => 'Không đủ tuổi quy định',
			42 => 'Không có bản gốc CMT/ CCCD/Hộ chiếu',
			43 => 'Không có SHK/ giấy tờ chứng minh nơi ở',
			44 => 'Không có giấy tờ chứng minh sở hữu xe',
			45 => 'Không có đăng kiểm xe',
			46 => 'Lý do khác',
			47 => 'Xe KCC- HCM',
			48 => 'Xe KCC - Mekong',
			49 => 'Sim không chính chủ'
		];

		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}

if (!function_exists('code_reason')) {
	function code_reason($status = null)
	{
		$leadstatus = [
			1 => 'Không thuộc khu vực hỗ trợ',
			2 => 'Không thuộc sản phẩm hỗ trợ',
			3 => 'Không cung cấp được SHK/KT3',
			4 => 'Không cung cấp được thông tin thu nhập',
			5 => 'Khách từ chối vì duyệt thấp',
			6 => 'Xe Không chính chủ',
			7 => 'Tài sản giá trị thấp',
			8 => 'Trùng hồ sơ',
			9 => 'KH có dư nợ cao',
			10 => 'KH tư cách kém',
			11 => 'Thuê bao/ Gọi nhiều lần nhưng không nghe máy',
			12 => 'Khách không đồng ý tham gia BHKV',
			13 => 'Kh không có xe',
			14 => 'Khách chê lãi cao',
			15 => 'Không cung cấp được ĐKX/HĐMB',
			16 => 'Khách hết nhu cầu',
			17 => 'Không đồng ý giữ ĐKX/HĐMB',
			18 => 'Sai số điện thoại',
			19 => 'KH hẹn nhiều lần nhưng không đến PGD',
			20 => 'Chuyển PGD khác hỗ trợ',
			21 => 'Không chứng minh được thu nhập',
			22 => 'KH chê thủ tục rườm rà',
			23 => 'Không nghe máy nhiều lần',
			24 => 'Khách Hàng không đủ điều kiện làm hồ sơ',

		];

		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}


function gen_html_call_history($arr_data_debt_history)
{
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	$html = "";
	if (!empty($arr_data_debt_history)) {
		foreach ($arr_data_debt_history as $key => $value) {
			$code_contract = (!empty($value["code_contract"])) ? $value["code_contract"] : "";
			$people = (!empty($value["people"])) ? meet_relatives($value["people"]) : "";
			$status_debt = (!empty($value["status_debt"])) ? note_renewal($value["status_debt"]) : "";
			$address = (!empty($value["destination"])) ? ($value["destination"]) : "";
			$evaluate = (!empty($value["evaluate"])) ? status_debt_recovery($value["evaluate"]) : "";
			$time_recovery = (!empty($value["time_recovery"])) ? date('d/m/Y', $value["time_recovery"]) : "";
			$money = (!empty($value["amount_received"])) ? number_format($value["amount_received"]) : "";
			$note = (!empty($value["note"])) ? ($value["note"]) : "";
			$created_at = (!empty($value["created_at"])) ? date('d/m/Y', $value["created_at"]) : "";
			$created_by = (!empty($value["created_by"])) ? ($value["created_by"]) : "";

			$html .= '<tr>';
			$html .= '<td>' . ++$key . '</td>';
			$html .= '<td>' . $code_contract . '</td>';
			$html .= '<td>' . $people . '</td>';
			$html .= '<td>' . $status_debt . '</td>';
			$html .= '<td>' . $address . '</td>';
			$html .= '<td>' . $evaluate . '</td>';
			$html .= '<td>' . $time_recovery . '</td>';
			$html .= '<td>' . $money . '</td>';
			$html .= '<td>' . $note . '</td>';
			$html .= '<td>' . $created_at . '</td>';
			$html .= '<td>' . $created_by . '</td>';
			$html .= '</tr>';
		}
	}
	return $html;
}

function list_array_trang_thai_dang_vay() {
	return [
		11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42
	];
}

function list_array_trang_thai_dang_vay_gh_cc() {
	return [
		11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,33,34
	];
}

if (!function_exists('type_transaction')) {
	function type_transaction($status = null)
	{
		$leadstatus = [
			'1' => 'Thanh toán tiện ích',
			'2' => 'Phí phạt hợp đồng',
			'3' => 'Tất toán',
			'4' => 'Thanh toán',
			'6' => 'Thanh toán nhà đầu tư',
			'7' => 'Thanh toán HeyU',
			'8' => 'Bảo hiểm Xe máy - MIC_TNDS',
			'9' => 'Chưa thu hồi',
			'10' => 'Bảo hiểm Ô tô - VBI_TNDS',
			'11' => 'Bảo hiểm VBI_Ung thư vú',
			'12' => 'Bảo hiểm VBI_Sốt xuất huyết',
			'13' => 'Bảo hiểm GIC EASY',
			'14' => 'Bảo hiểm GIC Phúc Lộc Thọ',
			'15' => 'Bảo hiểm PTI Vững Tâm An',
			'16' => 'PTI - Bảo Hiểm Tai Nạn Con Người',

		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}
if (!function_exists('type_payment')) {
	function type_payment($status = null)
	{
		$leadstatus = [
			'1' => 'Thanh toán lãi kỳ',
			'2' => 'Gia hạn',
			'3' => 'Cơ cấu',
			'4' => 'Thanh toán hợp đồng đã thanh lý tài sản',

		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}
if (!function_exists('status_contract_debt_to_field')) {
	function status_contract_debt_to_field($status = null)
	{
		$contract_debt = [
			1 => "Mới",
			2 => "Đã duyệt",
			3 => "Đà từ chối",
			4 => "Đã xóa",
			5 => "Đã xóa", //data old
			36 => "Đã gọi khách hàng",
			37 => "Đã chuyển yêu cầu sang Field",
			279 => "Chờ TP THN duyệt Field",
		];
		if ($status === null) return $contract_debt;
		foreach ($contract_debt as $key => $item) {

			if ($key == $status) {
				$result = $item;
			}

		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}

function gen_html_contract_debt_history($contract_log)
{
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	$key = 0;
	$html = "";
	if (!empty($contract_log)) {
		foreach ($contract_log as $value) {
			$created_at = (!empty($value["created_at"])) ? date("d/m/Y H:i:s", $value["created_at"]) : "";
			$created_by = (!empty($value["created_by"])) ? $value["created_by"] : "";
			$code_contract_disbursement = (!empty($value['old']["code_contract_disbursement"])) ? $value['old']["code_contract_disbursement"] : "";
			$customer_name = (!empty($value['old']["customer_name"])) ? $value['old']["customer_name"] : "";
			$old_status = (!empty($value["old"]["status"])) ? status_contract_debt_to_field($value["old"]["status"]) : "";
			$new_status = (!empty($value["new"]["status"])) ? status_contract_debt_to_field($value["new"]["status"]) : "";
			$old_note = (!empty($value['old']["note"])) ? $value['old']["note"] : "";
			$new_note = (!empty($value['new']["note"])) ? $value['new']["note"] : "";

			$html .= '<tr>';
			$html .= '<td>' . ++$key . '</td>';
			$html .= '<td>' . $created_at . '</td>';
			$html .= '<td>' . $created_by . '</td>';
			$html .= '<td>' . $code_contract_disbursement . '</td>';
			$html .= '<td>' . $customer_name . '</td>';
			$html .= '<td>' . $new_status . '</td>';
			$html .= '<td>' . $new_note . '</td>';
			$html .= '</tr>';
		}
	}
	return $html;
}

if (!function_exists('status_contract_field')) {
	function status_contract_field($status = null)
	{
		$contract_debt = [
			1 => "Mới",
			2 => "Đã duyệt",
			3 => "Đà từ chối",
			4 => "Đã xóa",
			279 => "Chờ TP THN duyệt Call",
		];
		if ($status === null) return $contract_debt;
		foreach ($contract_debt as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}

function gen_html_contract_field_history($contract_log)
{
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	$key = 0;
	$html = "";
	if (!empty($contract_log)) {
		foreach ($contract_log as $value) {
			$created_at = (!empty($value["created_at"])) ? date("d/m/Y H:i:s", $value["created_at"]) : "";
			$created_by = (!empty($value["created_by"])) ? $value["created_by"] : "";
			$code_contract_disbursement = (!empty($value['old']["code_contract_disbursement"])) ? $value['old']["code_contract_disbursement"] : "";
			$customer_name = (!empty($value['old']["customer_name"])) ? $value['old']["customer_name"] : "";
			$old_status = (!empty($value["old"]["status"])) ? status_contract_field($value["old"]["status"]) : "";
			$new_status = (!empty($value["new"]["status"])) ? status_contract_field($value["new"]["status"]) : "";
			$old_note = (!empty($value['old']["note"])) ? $value['old']["note"] : "";
			$new_note = (!empty($value['new']["note"])) ? $value['new']["note"] : "";

			$html .= '<tr>';
			$html .= '<td>' . ++$key . '</td>';
			$html .= '<td>' . $created_at . '</td>';
			$html .= '<td>' . $created_by . '</td>';
			$html .= '<td>' . $code_contract_disbursement . '</td>';
			$html .= '<td>' . $customer_name . '</td>';
			$html .= '<td>' . $new_status . '</td>';
			$html .= '<td>' . $new_note . '</td>';
			$html .= '</tr>';
		}
	}
	return $html;
}
function array_sort($array, $on, $order = SORT_ASC)
{
	$new_array = array();
	$sortable_array = array();

	if (count($array) > 0) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if ($k2 == $on) {
						$sortable_array[$k] = $v2;
					}
				}
			} else {
				$sortable_array[$k] = $v;
			}
		}

		switch ($order) {
			case SORT_ASC:
				asort($sortable_array);
				break;
			case SORT_DESC:
				arsort($sortable_array);
				break;
		}

		foreach ($sortable_array as $k => $v) {
			$new_array[$k] = $array[$k];
		}
	}

	return $new_array;
}

function convert_number_to_words($so)
{

	$ChuSo = [" không ", " một ", " hai ", " ba ", " bốn ", " năm ", " sáu ", " bảy ", " tám ", " chín "];
	$Tien = ["", " nghìn", " triệu", " tỷ", " nghìn tỷ", " triệu tỷ"];
	$mangso = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
	if ($so == 0) return ' ' + $mangso[0];
	$chuoi = "";
	$hauto = "";
	do {
		$ty = $so % 1000000000;
		$so = floor($so / 1000000000);
		if ($so > 0) {
			$chuoi = dochangtrieu($ty, true) . $hauto . $chuoi . " đồng";
		} else {
			$chuoi = dochangtrieu($ty, false) . $hauto . $chuoi . " đồng";
		}
		$hauto = " tỷ";
	} while ($so > 0);
	return str_replace('đồng đồng', 'đồng', ucfirst(trim($chuoi)));
}

function dochangtrieu($so, $daydu)
{
	$chuoi = "";
	$trieu = floor($so / 1000000);
	$so = $so % 1000000;
	if ($trieu > 0) {
		$chuoi = docblock($trieu, $daydu) . " triệu";
		$daydu = true;
	}
	$nghin = floor($so / 1000);
	$so = $so % 1000;
	if ($nghin > 0) {
		$chuoi .= docblock($nghin, $daydu) . " nghìn";
		$daydu = true;
	}
	if ($so > 0) {
		$chuoi .= docblock($so, $daydu);
	}
	return $chuoi;
}

function docblock($so, $daydu)
{
	$ChuSo = [" không ", " một ", " hai ", " ba ", " bốn ", " năm ", " sáu ", " bảy ", " tám ", " chín "];
	$Tien = ["", " nghìn", " triệu", " tỷ", " nghìn tỷ", " triệu tỷ"];
	$mangso = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
	$chuoi = "";
	$tram = floor($so / 100);
	$so = $so % 100;
	if ($daydu || $tram > 0) {
		$chuoi = " " . $mangso[$tram] . " trăm";
		$chuoi .= dochangchuc($so, true);
	} else {
		$chuoi = dochangchuc($so, false);
	}
	return $chuoi;
}

function dochangchuc($so, $daydu)
{
	$ChuSo = [" không ", " một ", " hai ", " ba ", " bốn ", " năm ", " sáu ", " bảy ", " tám ", " chín "];
	$Tien = ["", " nghìn", " triệu", " tỷ", " nghìn tỷ", " triệu tỷ"];
	$mangso = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
	$chuoi = "";
	$chuc = floor($so / 10);
	$donvi = $so % 10;
	if ($chuc > 1) {
		$chuoi = " " . $mangso[$chuc] . " mươi";
		if ($donvi == 1) {
			$chuoi .= " mốt";
		}
	} else if ($chuc == 1) {
		$chuoi = " mười";
		if ($donvi == 1) {
			$chuoi .= " một";
		}
	} else if ($daydu && $donvi > 0) {
		$chuoi = " lẻ";
	}
	if ($donvi == 5 && $chuc >= 1) {
		$chuoi .= " lăm";
	} else if ($donvi > 1 || ($donvi == 1 && $chuc == 0)) {
		$chuoi .= " " . $mangso[$donvi];
	}
	return $chuoi;
}

if (!function_exists('status_contract_megadoc_response')) {
	function status_contract_megadoc_response($status = null)
	{
		$contract_megadoc = [
			"FILE_INVALID" => "File không hơp lệ",
			"FILE_SIZE_INVALID" => "Vượt quá kích thước cho phép",
			"FKEY_DUPLICATE" => "Trùng fkey (mã phiếu ghi)",
			"CONTRACT_NO_DUPLICATE" => "Trùng số hợp đồng megadoc!",
			"DEPT_CODE_INVALID" => "Mã phòng ban không đúng",
			"PROTOTYPE_INVALID" => "Mã hợp đồng không đúng",
			"CONTRACT_NOT_FOUND" => "Hợp đồng không tồn tại",
			"DATE_INVALID" => "Ngày không đúng định dạng",
			"UNKNOW_ERROR" => "Lỗi không xác định",
			"ERROR_DATA_INPUT" => "Lỗi dữ liệu đầu vào",
			"CONTRACT_PROCESSING" => "Hợp đồng đang được xử lý",
			"CONTRACT_CANCEL" => "Hợp đồng đã bị hủy không thể cập nhật",
			"CONTRACT_COMPLETED" => "Hợp đồng đã hoàn thành không thể cập nhật",
			"METADATA_INVALID" => "Chuỗi json metadata không hợp lệ",
		];
		if ($status === null) return $contract_megadoc;
		foreach ($contract_megadoc as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		$result = (empty($result)) ? $status : $result;
		return $result;
	}
}

if (!function_exists('status_contract_megadoc')) {
	function status_contract_megadoc($status = null) {
		$status_megadoc = [
			'0' => "Hợp đồng tạo mới",
			'1' => "Đã gửi",
			'2' => "Hợp đồng có một chữ ký",
			'3' => "Hợp đồng hoàn thành",
			'7' => "Hợp đồng đã hủy",
		];

		if ($status === null) return $status_megadoc;
		foreach ($status_megadoc as $key => $item) {
			if ($status == $key) {
				$result = $item;
			}
		}
		return $result;
	}

	if (!function_exists('status_property')) {
	function status_property($status = null)
	{
		$type_property = array(
			1 => 'Chờ duyệt',
			2 => 'Đã duyệt',
			3 => 'Hủy'
		);
		if ($status === null) return $type_property;
		foreach ($type_property as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
	if (!function_exists('lead_missed_call')) {
	function lead_missed_call($status = null)
	{
		$leadstatus = [
			1 => 'bấm phím(khiếu nại)',
			2 => 'bấm phím(không phản hồi)',
			3 => 'không kết nối được ',
		];
		if ($status === null) return $leadstatus;
		foreach ($leadstatus as $key => $item) {
			if ($key == $status) {
				$result = $item;
			}
		}
		return $result;
	}
}
}
}



