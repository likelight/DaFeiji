<?php

/**
 * @desc AES加解密类
 * @package e-buy
 * @link http://www.e-buychina.com/
 * @createtime 2015/11/20
 * @author Harry
 */
class AESHelper
{
    const Key   = '1afe4c18a8ebe4cf2da7c8406497f71a';  //AES加密(正式用)
    const iv    = '00010019a82c89d8';

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
    public static function registerTerminal() {
        //获取配置的host和notify_url
        $notify_url = '';

        //AES加密
        $messageBody = utf8_encode(sprintf('<Message><host>%s</host><notifyUrl>%s</notifyUrl></Message>', $host, $notify_url));
        // echo $messageBody;
        // exit;
        $message = self::encryptAES($messageBody);

        $data = array();
        $data['post'] = [
            'msgtype'     => 'request_msg',
            'format'      => 'xml',
            'version'     => '1.0',
            'organizerId' => self::organizerId,
            'timestamp'   => Helper::getMillisecond(),
            // 'sign'        => $sign,
            'method'      => self::REGISTER_TERMINAL_MSG,
            'message'     => $message
        ];
        // print_r($data);
        // exit;
        // MD5签名
        $sign = self::createSign($data['post']);
        $data['post']['sign'] = $sign;

        $output = Common::sendHttp(self::COUPON_URL, $data);
        return $output;
    }


}