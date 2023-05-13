<?php

namespace app\api\controller;

use think\facade\Config;
use app\common\controller\Frontend;
use app\common\model\CarmiModel;
use app\common\model\User;
use think\facade\Db;


class Carmi extends Frontend
{
    protected $noNeedLogin = [];

    public function initialize()
    {
        parent::initialize();
    }

    //
    public function index(){
    
        if($this->request->isPost()){

             $key = $this->request->post('key');
             $uid = $this->request->post('uid');

             $info = CarmiModel::where('key', $key)->where('status', 0)->find();

             if($info == NULL){
                $this->success('key不存在',"", 2);
             }

             $userInfo = User::where('id',$uid)->find();

             if($userInfo == NULL){
                $this->success('用户不存在',"", 2);
             }


             //获得 增加天数后的 时间戳
             $day = time(); 

             if(time() < $userInfo['end_time']){
                $day = $userInfo['end_time'] + 86400*$info['day'];
             }else{
                $day = time() + 86400*$info['day']; 
             }

             // 启动事务
             Db::startTrans();
             try {
                Db::table('ba_user')->where('id',$userInfo['id'])->update([
                     'num' => $userInfo['num'] + $info['num'],
                     'end_time' => $day
                ]);

                Db::table('ba_carmi')->where('id', $info['id'])->update([
                    'status' => 1,
                    'user_id' => $userInfo['id'],
                    'add_time' => time(),
                ]);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }

            $this->success('卡密充值成功',"", 1);
        }

       
    }

}