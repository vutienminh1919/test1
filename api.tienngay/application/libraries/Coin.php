<?php
/**
 * Created by PhpStorm.
 * User: levan
 * Date: 4/22/2018
 * Time: 11:09 AM
 */

class Coin{
    public function __construct(){
        $this->CI =& get_instance();
    }
    private $CI, $data, $receipt;
    public function receipt($coin, $hash){
        switch ($coin) {
            case 'btc':
                $url = $this->CI->config->item('api').$coin.'/getInfoTransaction/'.$hash.'/1';
                $this->receipt = $this->crashTransactionDetailSHA256(json_decode(file_get_contents($url)));
                return $this->receipt;
                break;
            case 'bch':
                $url = $this->CI->config->item('api').$coin.'/getInfoTransaction/'.$hash.'/1';
                $this->receipt = $this->crashTransactionDetailSHA256(json_decode(file_get_contents($url)));
                return $this->receipt;
                break;
            case 'eth':
                $url = $this->CI->config->item('api').$coin.'/getInfoTransaction/'.$hash.'/1';
//                var_dump(json_decode(file_get_contents($url)));
                $this->receipt = $this->crashTransactionDetailETH(json_decode(file_get_contents($url)));
                return $this->receipt;
                break;
            case 'ltc':
                $url = $this->CI->config->item('api').$coin.'/getInfoTransaction/'.$hash.'/1';
                $this->receipt = $this->crashTransactionDetailSHA256(json_decode(file_get_contents($url)));
                return $this->receipt;
                break;
            case 'xrp':
                $url = $this->CI->config->item('api').$coin.'/getInfoTransaction/'.$hash.'/1';
                $this->receipt = $this->crashTransactionDetailXRP(json_decode(file_get_contents($url)));
                return $this->receipt;
                break;
            case 'egt':
                $url = $this->CI->config->item('api').'token/getInfoTransaction/'.$hash.'/1';
                $this->receipt = $this->crashTransactionDetailETH(json_decode(file_get_contents($url)));
                return $this->receipt;
                break;
            case 'vme':
                $url = $this->CI->config->item('api').'token/getInfoTransaction/'.$hash.'/1';
                $this->receipt = $this->crashTransactionDetailETH(json_decode(file_get_contents($url)));
                return $this->receipt;
                break;
            default:
                break;
        }
    }
    private function crashTransactionDetailSHA256($receipt){
        if (empty($receipt->fee)){
            $this->data['txid'] = !empty($receipt->txid) ? $receipt->txid : "";
            $this->data['address'] = !empty($receipt->details[0]->address) ? $receipt->details[0]->address : "";
        }else{
            $this->data['txid'] = !empty($receipt->txid) ? $receipt->txid : "";
            $this->data['address'] = !empty($receipt->details[1]->address) ? $receipt->details[1]->address : "";
        }
        return $this->data;
    }
    private function crashTransactionDetailETH($receipt){
        $this->data['txid'] = !empty($receipt->result->hash) ? $receipt->result->hash: "";
        $this->data['address'] = !empty($receipt->result->to) ? $receipt->result->to : "";
        return $this->data;
    }

    private function crashTransactionDetailXRP($receipt){
        $this->data['txid'] = !empty($receipt->transactions->id) ? $receipt->transactions->id : "";
        $this->data['address'] = !empty($receipt->transactions->specification->source->address) ? $receipt->transactions->specification->source->address : "";
        return $this->data;
    }
    public function checkSendCoin($coin, $data) {
        $coin = strtolower($coin);
        $data = json_decode($data);
        switch ($coin) {
            case 'btc':
                $isSuccess = false;
                if(!empty($data) && !empty($data->status) && !empty($data->txid) && $data->status === 'success' && $data->txid !== false) {
                    $isSuccess = true;
                }
                return array(
                    'code'=> 200,
                    'is_success' => $isSuccess,
                    'trans_id' => !empty($data->txid) ? $data->txid : ""
                );
                break;
            case 'bch':
                $isSuccess = false;
                if(!empty($data) && !empty($data->status) && !empty($data->txid) && $data->status === 'success' && $data->txid !== false) {
                    $isSuccess = true;
                }
                return array(
                    'code'=> 200,
                    'is_success' => $isSuccess,
                    'trans_id' => !empty($data->txid) ? $data->txid : ""
                );
                break;
            case 'eth':
                $isSuccess = false;
                if(!empty($data) && $data->success && $data->unlock && $data->receipt != 'null' && $data->receipt != null) {
                    $isSuccess = true;
                }
                return array(
                    'code'=> 200,
                    'is_success' => $isSuccess,
                    'trans_id' => !empty($data->receipt) ? $data->receipt : ""
                );
                break;
            case 'ltc':
                $isSuccess = false;
                if(!empty($data) && !empty($data->txid) && $data->status === 'success') {
                    $isSuccess = true;
                }
                return array(
                    'code'=> 200,
                    'is_success' => $isSuccess,
                    'trans_id' => !empty($data->txid) ? $data->txid : ""
                );
                break;
            case 'usdt':
                $isSuccess = false;
                if(!empty($data) && !empty($data->txid) && (int)$data->confirmations >= 3) {
                    $isSuccess = true;
                }
                return array(
                    'code'=> 200,
                    'is_success' => $isSuccess,
                    'trans_id' => !empty($data->txid) ? $data->txid : ""
                );
                break;
            case 'xrp':
                $isSuccess = false;
                if(!empty($data) && !empty($data->payments) && $data->payments->resultCode === 'tesSUCCESS') {
                    $isSuccess = true;
                }
                return array(
                    'code'=> 200,
                    'is_success' => $isSuccess,
                    'trans_id' => !empty($data->id) ? $data->id : ""
                );
                break;
            case 'egt':
                $isSuccess = false;
                if(!empty($data) && $data->success && $data->unlock && $data->receipt != 'null' && $data->receipt != null) {
                    $isSuccess = true;
                }
                return array(
                    'code'=> 200,
                    'is_success' => $isSuccess,
                    'trans_id' => !empty($data->receipt) ? $data->receipt : ""
                );
                break;
            case 'vme':
                $isSuccess = false;
                if(!empty($data) && $data->success && $data->unlock && $data->receipt != 'null' && $data->receipt != null) {
                    $isSuccess = true;
                }
                return array(
                    'code'=> 200,
                    'is_success' => $isSuccess,
                    'trans_id' => !empty($data->receipt) ? $data->receipt : ""
                );
                break;
            default:
                break;
        }
    }
    
    public function sendCrypto($symbol, $from, $to, $amount, $password, $priv ) {
        switch ($symbol) {
            case 'BTC':
                $url = $this->CI->config->item('api').'btc/send';
                $postdata = http_build_query(
                    array(
                        'from' => $from,
                        'to' => $to,
                        'amount' => $amount,
                        'password' => $password,
                        'priv' => $priv,
                        'API_KEY' => $this->CI->config->item('API_KEY'),
                        'fee' => $this->CI->config->item('FEE_BTC_BLOCKCHAIN')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            case 'USDT':
                $url = $this->CI->config->item('api').'usdt/send';
                $postdata = http_build_query(
                    array(
                        'from' => $from,
                        'to' => $to,
                        'amount' => $amount,
                        'password' => $password,
                        'priv' => $priv,
                        'API_KEY' => $this->CI->config->item('API_KEY'),
                        'fee' => $this->CI->config->item('FEE_USDT_BLOCKCHAIN')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            case 'BCH':
                $url = $this->CI->config->item('api').'bch/send';
                $postdata = http_build_query(
                    array(
                        'from' => $from,
                        'to' => $to,
                        'amount' => $amount,
                        'password' => $password,
                        'priv' => $priv,
                        'API_KEY' => $this->CI->config->item('API_KEY'),
                        'fee' => $this->CI->config->item('FEE_BCH_BLOCKCHAIN')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            case 'ETH':
                $url = $this->CI->config->item('api').'eth/send';
                $postdata = http_build_query(
                    array(
                        'from_input' => $from,
                        'to_input' => $to,
                        'value_input' => $amount,
                        'password' => $password,
                        'API_KEY' => $this->CI->config->item('API_KEY'),
                        'gas_input' => $this->CI->config->item('GAS_ETH_BLOCKCHAIN')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            case 'LTC':
                $url = $this->CI->config->item('api').'ltc/send';
                $postdata = http_build_query(
                    array(
                        'from' => $from,
                        'to' => $to,
                        'amount' => $amount,
                        'password' => $password,
                        'priv' => $priv,
                        'API_KEY' => $this->CI->config->item('API_KEY'),
                        'fee' => $this->CI->config->item('FEE_LTC_BLOCKCHAIN')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            case 'XRP':
                $url = $this->CI->config->item('api').'xrp/send';
                $postdata = http_build_query(
                    array(
                        'sender' => $from,
                        'destination' => $to,
                        'amount' => $amount,
                        'password' => $password,
                        'secret' => $priv,
                        'API_KEY' => $this->CI->config->item('API_KEY')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            case 'EGT':
                $url = $this->CI->config->item('api').'token/send';
                $postdata = http_build_query(
                    array(
                        'from_input' => $from,
                        'to_input' => $to,
                        'value_input' => (string)$amount,
                        'password' => $password,
                        'contract_input' => $this->CI->config->item('EGATE_CONTRACT_ADDRESS'),
                        'API_KEY' => $this->CI->config->item('API_KEY'),
                        'gas_input' => $this->CI->config->item('GAS_EGT_BLOCKCHAIN')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            case 'VME':
                $url = $this->CI->config->item('api').'token/send';
                $postdata = http_build_query(
                    array(
                        'from_input' => $from,
                        'to_input' => $to,
                        'value_input' => (string)$amount * $this->CI->config->item('VME_MULTI'),
                        'password' => $password,
                        'contract_input' => $this->CI->config->item('VME_CONTRACT_ADDRESS'),
                        'API_KEY' => $this->CI->config->item('API_KEY'),
                        'gas_input' => $this->CI->config->item('GAS_VME_BLOCKCHAIN')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            case 'BGC':
                $url = $this->CI->config->item('api').'token/send';
                $postdata = http_build_query(
                    array(
                        'from_input' => $from,
                        'to_input' => $to,
                        'value_input' => (string)$amount * $this->CI->config->item('BGC_MULTI'),
                        'password' => $password,
                        'contract_input' => $this->CI->config->item('BGC_CONTRACT_ADDRESS'),
                        'API_KEY' => $this->CI->config->item('API_KEY'),
                        'gas_input' => $this->CI->config->item('GAS_BGC_BLOCKCHAIN')
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context  = stream_context_create($opts);
                return file_get_contents( $url, false, $context);
                break;
            default:
                break;
        }
    }
}