<?php

namespace app\api\controller;

use ba\Captcha;
use think\Exception;
use app\common\model\User;
use modules\sms\Sms as smsLib;
use app\common\controller\Frontend;
use think\facade\Event;

class Sms extends Frontend
{
    protected $noNeedLogin = ['send'];

    public function initialize()
    {
        parent::initialize();
    }

    public function send()
    {
        $mobile       = $this->request->post("mobile");
        $templateCode = $this->request->post("template_code");
        if (!$mobile) {
            $this->error(__('Mobile format error'));
        }
        if (!$templateCode) {
            $this->error(__('Parameter error'));
        }

        // 检查频繁发送
        $captcha = (new Captcha())->getCaptchaData($mobile . $templateCode);
        if ($captcha && time() - $captcha['createtime'] < 60) {
            $this->error(__('Frequent SMS sending'));
        }

        // 检查号码占用
        $userInfo = User::where('mobile', $mobile)->find();
        if ($templateCode == 'user_register' && $userInfo) {
            $this->error(__('Mobile number has been registered, please log in directly'));
        } elseif ($templateCode == 'user_change_mobile' && $userInfo) {
            $this->error(__('The mobile number has been occupied'));
        } elseif (in_array($templateCode, ['user_retrieve_pwd', 'user_mobile_verify']) && !$userInfo) {
            $this->error(__('Mobile number not registered'));
        }

        // 通过手机号验证账户
        if ($templateCode == 'user_mobile_verify') {
            if (!$this->auth->isLogin()) {
                $this->error(__('Please login first'));
            }
            if ($this->auth->mobile != $mobile) {
                $this->error(__('Please use the account registration mobile to send the verification code'));
            }
            // 验证账户密码
            $password = $this->request->post('password');
            if ($this->auth->password != encrypt_password($password, $this->auth->salt)) {
                $this->error(__('Password error'));
            }
        }

        // 监听短信模板分析完成
        Event::listen('TemplateAnalysisAfter', function ($templateData) use ($mobile, $templateCode) {
            // 存储验证码
            if (array_key_exists('code', $templateData['variables'])) {
                (new Captcha())->create($mobile . $templateCode, $templateData['variables']['code']);
            }
            if (array_key_exists('alnum', $templateData['variables'])) {
                (new Captcha())->create($mobile . $templateCode, $templateData['variables']['alnum']);
            }
        });

        try {
            smsLib::send($templateCode, $mobile);
        } catch (Exception $e) {
            if (!env('APP_DEBUG', false)) {
                $this->error(__('Failed to send SMS. Please contact the website administrator'));
            } else {
                // throw new Exception($e->getMessage());
                $this->error(__($e->getMessage()));
            }
        }
        $this->success(__('SMS sent successfully'));
    }
}