<?php

/*
测试环境参数如下：

organizerId: 00010019a82fe171
iv: 00010019a82c89d8
key: 1afe4c18a8ebe4cf2da7c8406497f71a
token: C9B7FD71A35FE5B8E7FBBC6AF0E9F93A



正式环境参数如下：

organizerId: 00010019a83066bc
iv: 00010019a82c68e7
key: 0133d78d450460d543d4856e9a3341ee
token: DFB175A8A73F3476A55B4CBB35C29348


*/
/**
 * @desc 与电子券平台交互的辅助类
 * @package e-buy
 * @link http://www.e-buychina.com/
 * @createtime 2015/11/20
 * @author Harry
 */
class CouponHelper
{
    //电子券平台URL
    const COUPON_URL = 'http://pizza.e-pointchina.com.cn/shop_pizza_test/shop_api/interface'; //生产环境

    const organizerId = '00010019a83066bc'; //组织者ID(正式用)

    const Token = 'DFB175A8A73F3476A55B4CBB35C29348';  //MD5签名(正式用)
    const Key   = '0133d78d450460d543d4856e9a3341ee';  //AES加密(正式用)
    const iv    = '00010019a82c68e7';

    const REGISTER_TERMINAL_MSG     = '01'; //注册终端
    const REQUEST_CREATE_COUPON_MSG = '10'; //发码请求
    const CREATE_COUPON_NOTIFY_MSG  = '30'; //发送成功通知

    /**
     * [registerTerminal 注册终端]
     * @return [type] [description]
     */
    public static function registerTerminal() {
        //获取配置的host和notify_url

        // $host = 'c7.pizzahut.com.cn';
        $host = '42.159.243.48';
        $notify_url = '';

        //AES加密
        $messageBody = utf8_encode(sprintf('<Message><host>%s</host><notifyUrl>%s</notifyUrl></Message>', $host, $notify_url));
        // echo $messageBody;
        // exit;
        $message = self::encryptAES($messageBody);

        $data = array();
        list($usec, $sec) = explode(' ', microtime());
        $time = (string)round($usec * 1000 + round($sec * 1000));
        $data['post'] = [
            'msgtype'     => 'request_msg',
            'format'      => 'xml',
            'version'     => '1.0',
            'timestamp'   => $time,
            'organizerId' => self::organizerId,
            // 'sign'        => $sign,
            'method'      => self::REGISTER_TERMINAL_MSG,
            'message'     => $message
        ];
        // print_r($data);
        // exit;
        // MD5签名
        $sign = self::createSign($data['post']);
        $data['post']['sign'] = $sign;

        $url = self::COUPON_URL;
        $data = $data['post'];

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */ }

        $result = new SimpleXMLElement($result);
        if ($result->resultCode != '000000') {
            // 错误

        } else {
            // self::terminalId = $result['terminalId'];
            echo json_encode($result);
            self::requestDraw($result->terminalId);
        }



    }

    public static function requestDraw ($terminalId) {
        // $terminalId = self::terminalId;
        $transCode = $terminalId.date('Ymd').substr(md5(time()), 0, 8);

        $userType = 0;
        $userNumber = $_GET['id'];
        $utm_source = 'PHDI_CAMP_LIULIAN_FLIGHT';
        $couponType = $_GET['couponType'];
        $type = $_GET['type'];
        $channel = $_GET['channel'];
        $date = date('Y-m-d');
        $callBackUrl = urlencode("http://c7.pizzahut.com.cn/flight/luck.php?action=callback&type={$type}&id={$userNumber}&channel={$channel}&date={$date}");

        $messageBody = utf8_encode(sprintf('<Message>
            <terminalId>%s</terminalId>
            <transCode>%s</transCode>
            <userType>%s</userType>
            <userNumber>%s</userNumber>
            <utm_source>%s</utm_source>
            <couponType>%s</couponType>
            <callBackUrl>%s</callBackUrl>
            </Message>',
            $terminalId, $transCode, $userType, $userNumber, $utm_source, $couponType, $callBackUrl));
        // echo $messageBody;
        // exit;
        // header( 'X-terminalId:'.$terminalId);
        // header( 'X-transCode:'.$transCode);
        // header( 'X-userType:'.$userType);
        // header( 'X-userNumber:'.$userNumber);
        // header( 'X-utm_source:'.$utm_source);
        // header( 'X-couponType:'.$couponType);
        header( 'X-callBackUrl: '.$callBackUrl);
        $message = self::encryptAES($messageBody);

        $data = array();
        list($usec, $sec) = explode(' ', microtime());
        $time = (string)round($usec * 1000 + round($sec * 1000));
        $data['post'] = [
            'msgtype'     => 'redirect_msg',
            'format'      => 'xml',
            'version'     => '1.0',
            'timestamp'   => $time,
            'organizerId' => self::organizerId,
            // 'sign'        => $sign,
            'method'      => '68',
            'message'     => $message
        ];
        // print_r($data);
        // exit;
        // MD5签名
        $sign = self::createSign($data['post']);
        $data['post']['sign'] = $sign;

        $url = self::COUPON_URL;
        $data = $data['post'];

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );

        // echo $url.'?'.http_build_query($data);
        header('Location: '.$url.'?'.http_build_query($data));
        /*$context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { }
        var_dump($result);

        $result = new SimpleXMLElement($result);
        echo json_encode($result);*/
        // if ($result['resultCode'] != '000000') {
        //     // 错误

        // } else {
        //     self::terminalId = $result['terminalId'];
        // }

    }

    /**
     * [requestCreateCoupon 发码请求]
     * @return [type] [description]
     */
    public static function requestCreateCoupon($userType, $userNumber, $activity_id, $coupon_id) {
        //判断是否主服务器
        $is_master_server = Yii::app()->params['is_master_server'];
        if (!$is_master_server) {
            $data = array();
            $data['post'] = [
                'msgtype'     => 'create_coupon_msg',
                'userType'    => $userType,
                'userNumber'  => $userNumber,
                'activity_id' => $activity_id,
                'coupon_id'   => $coupon_id
            ];

            $master_server_url = Yii::app()->params['master_server_url'];

            Yii::log(sprintf('is_master_server=%s, send create-coupon-request to master-server:%s', $is_master_server, $master_server_url), 'info', 'CouponHelper');

            $res = Common::sendHttp($master_server_url, $data);
            // print_r($output);
            // exit;
            if (!$res['ok']) {
                Yii::log('error, failed to send create-coupon-request to master-server', 'error', 'CouponHelper');
                return false;
            }

            $json_data = json_decode($res['body'], true);
            if (!$json_data) {
                Yii::log('error, failed to receive create-coupon-request response from master-server', 'error', 'CouponHelper');
                return false;
            }

            $output = [
                'ok'        => 1,
                'body'      => $json_data['d']['body'],
                'transCode' => $json_data['d']['transCode']
            ];

            return $output;
        }

        Yii::log(sprintf('is_master_server=%s', $is_master_server), 'info', 'CouponHelper');

        $key = 'system::terminalId';
        $terminalId = Yii::app()->queue->get($key);
        if (!$terminalId) {
            //从数据库中读取终端ID
            $res = GiftSystemConfig::getInstance()->getConfigValue('terminal_id');
            if ($res) {
                $terminalId = $res['config_value'];
                Yii::app()->queue->set($key, $terminalId);
            }
            else {
                Yii::log('error, get terminal_id from cache and db failed.', 'error', 'CouponHelper');
                return false;
            }
        }

        //生成交易流水号
        $transCode = Helper::signTransCode();

        //AES加密
        $messageBody = utf8_encode(sprintf('<Message><terminalId>%s</terminalId><activityId>%s</activityId><couponId>%s</couponId><transCode>%s</transCode><userType>%s</userType><userNumber>%s</userNumber></Message>',
            $terminalId, $activity_id, $coupon_id, $transCode, $userType, $userNumber));
        $message = self::encryptAES($messageBody);

        $data = array();
        $data['post'] = [
            'msgtype'     => 'request_msg',
            'format'      => 'xml',
            'version'     => '1.0',
            'organizerId' => self::organizerId,
            'timestamp'   => Helper::getMillisecond(),
            // 'sign'        => $sign,
            'method'      => self::REQUEST_CREATE_COUPON_MSG,
            'message'     => $message
        ];
        // print_r($data);
        // exit;
        // MD5签名
        $sign = self::createSign($data['post']);
        $data['post']['sign'] = $sign;

        $output = Common::sendHttp(self::COUPON_URL, $data);
        $output['transCode'] = $transCode;
        return $output;
    }


    /**
     * [sendCreateCouponNotify 发送成功通知消息]
     * @param  [type] $transCode [description]
     * @return [type]            [description]
     */
    public static function sendCreateCouponNotify($transCode) {
        $key = 'system::terminalId';
        $terminalId = Yii::app()->queue->get($key);
        if (!$terminalId) {
            //从数据库中读取终端ID
            $res = GiftSystemConfig::getInstance()->getConfigValue('terminal_id');
            if ($res) {
                $terminalId = $res['config_value'];
                Yii::app()->queue->set($key, $terminalId);
            }
            else {
                Yii::log('error, get terminal_id from cache and db failed.', 'error', 'CouponHelper');
                return false;
            }
        }

        //AES加密
        $messageBody = utf8_encode(sprintf('<Message><terminalId>%s</terminalId><transCode>%s</transCode></Message>',
            $terminalId, $transCode));
        $message = self::encryptAES($messageBody);

        $data = array();
        $data['post'] = [
            'msgtype'     => 'notify_msg',
            'format'      => 'xml',
            'version'     => '1.0',
            'organizerId' => self::organizerId,
            'timestamp'   => Helper::getMillisecond(),
            // 'sign'        => $sign,
            'method'      => self::CREATE_COUPON_NOTIFY_MSG,
            'message'     => $message
        ];
        // print_r($data);
        // exit;
        // MD5签名
        $sign = self::createSign($data['post']);
        $data['post']['sign'] = $sign;

        $output = Common::sendHttp(self::COUPON_URL, $data);
        $output['transCode'] = $transCode;
        return $output;
    }

    /**
     * [createSign MD5签名]
     * @param  [type] $packageParams [description]
     * @return [type]                [description]
     */
    public static function createSign($packageParams)
    {
        $signPars = '';
        ksort($packageParams);
        foreach($packageParams as $k=> $v) {
            $signPars = $signPars.$k.$v;
        }

        $signPars = self::Token.$signPars.self::Token;
        // echo $signPars;
        // exit;
        $sign = md5($signPars);
        $sign = strtoupper($sign);
        // echo $sign;
        // exit;
        return $sign;
    }

    /**
     * [encryptAES AES（CBC/PKCS5Padding）加密]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function encryptAES($input)
    {
        $size = mcrypt_get_block_size('rijndael-128', 'cbc');
        $input = self::pkcs5_pad($input, $size);

        $td = mcrypt_module_open('rijndael-128', '', 'cbc', '');
        mcrypt_generic_init($td, self::Key, self::iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = bin2hex($data);
        return $data;
    }

    /**
     * [decryptAES AES（CBC/PKCS5Padding）解密]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function decryptAES($input)
    {
        $td = mcrypt_module_open('rijndael-128', '', 'cbc', '');
        $input = self::hexToStr($input);
        mcrypt_generic_init($td, self::Key, self::iv);
        $orig_data = mdecrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        $data = self::pkcs5_unpad($orig_data);
        return $data;
    }

    /**
     * [pkcs5_pad description]
     * @param  [type] $text      [description]
     * @param  [type] $blocksize [description]
     * @return [type]            [description]
     */
    public static function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * [pkcs5_unpad description]
     * @param  [type] $text [description]
     * @return [type]       [description]
     */
    public static function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }

    /**
     * [hexToStr 16进制的转为2进制字符串]
     * @param  [type] $hex [description]
     * @return [type]      [description]
     */
    public static function hexToStr($hex) {
        $bin = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2)
        {
            $bin .= chr(hexdec($hex[$i].$hex[$i+1]));
        }

        return $bin;
    }


}