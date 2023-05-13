<?php

namespace app\api\controller;

use think\facade\Config;
use app\common\controller\Frontend;
use app\common\model\ChatModel;
use OpenAI;
use think\facade\Db;

class Chat extends Frontend
{
    protected $noNeedLogin = ['index', 'sendMsg', 'sendCompletions'];

    public function initialize()
    {
        parent::initialize();
    }

    //保存消息
    public function save($data){
        $chat_model = new ChatModel;
        $da = $chat_model->save( $data);

        return $da;
    }

    //获取update_time 最小的 key
    public function getKey(){

        $md =  Db::table('ba_apikey');
        $re = $md->where('status',1)->order('update_time','asc')->limit(1)->select();

        if(count($re) < 1){
            return '';
        }

        $data = $re[0];

        $re2 = Db::table('ba_apikey')->where('id', $data['id'])->inc('cost_num')->update([
            'update_time'=>time()
        ]);


        return $data;
    }



    



    public function sendMsg(){

        // 检查当前缓冲区级别
        if (ob_get_level() > 0) {
            // 如果有缓冲区，则清空缓冲区
            ob_end_clean(); 
        }
        // header('Content-Type: text/event-stream');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        // 重点，必须要加
        header('X-Accel-Buffering: no');


        $content = $this->request->post('message');
        $uid = $this->request->post('uid');
        $name = $this->request->post('username');
        $num = $this->request->post('num');
        $token = $this->request->post('token');

        $endContent = $content[count($content) -1];  //获取最后一条
        $apiurl = get_sys_config('proxy').'/v1';
        $model  = get_sys_config('open_model');


        $youke_num = get_sys_config('youke_num'); //获取游客对话次数

        
        $count     = Db::table('ba_chat')->where('token',$token)->count(); 
        $userCount = Db::table('ba_chat')->where('username',$name)->count(); 
    

        // var_dump("kkkk",$count);
        // exit;
        if(strlen($endContent['content']) > 2000){
            echo '内容太长,请重试';
            exit;
        }

        //游客过滤

        if($uid == 0){
            // if( strlen($token) <= 10 ||  $name == '游客959' || $name == '游客undefined' || $count  != $userCount){
            //     echo '白嫖网站，请访问：https://ai.goodwpd.com';
            //     exit;
            // }
    
            if($uid == 0 && $count > $youke_num ){
                echo '对话次数不足';
                exit;
            }
        }

    
        $udata = [
            'uid'=> $uid,
            'username'=> $name,
            'messages'=> $endContent['content'],
            'num'=> $num,
            'token'=> $token,
            'model'=> $model,
            'cate' => 'chat',
            'ip'=> $this->request->ip(),
        ];

        $this->save($udata);

    

        if($uid != 0){
            Db::table('ba_user')
            ->where('id', $uid)
            ->dec('num', 1)
            ->update();
        }

        $getdata = $this->getKey();

        if($getdata == ''){
            echo '没有可用的key';
            exit;
        }


        $yourApiKey =  $getdata['apikey'];

        $client = OpenAI::factory()
            ->withApiKey($yourApiKey)
            ->withOrganization('') // default: null
            ->withBaseUri($apiurl) // default: api.openai.com/v1
            ->withHttpClient($client = new \GuzzleHttp\Client([])) // default: HTTP client found using PSR-18 HTTP Client Discovery
            ->withHttpHeader('X-My-Header', 'foo')
            ->withQueryParam('my-param', 'bar')
            // ->withStreamHandler(fn (RequestInterface $request): ResponseInterface => $client->send($request, [
            //     'stream' => true // Allows to provide a custom stream handler for the http client.
            // ]))
            ->make();
            $stream = $client->chat()->createStreamed([
                'model' =>  $model,
                'messages' => $content,
            ]);

            $content = '';
            
            foreach($stream as $response){

                //var_dump("ddd===",$response);

                $backdata = $response->choices[0]->toArray();
                if(isset($backdata['delta']['content'])){
                    //echo "1111";
                    $content .= $backdata['delta']['content'];
                    // echo $content;
                    // echo "222";
                    echo $backdata['delta']['content'];
                    flush();
                }

            }

            $udata = [
                'uid'=> 0,
                'username'=> 'AI机器人',
                'messages'=> $content,
                'token'=> 0,
                'cate' => 'chat',
                'model'=> $model,
            ];
    
            $this->save($udata);
            //return $response->toArray();

    }


    public function sendCompletions(){

        // 检查当前缓冲区级别
        if (ob_get_level() > 0) {
            // 如果有缓冲区，则清空缓冲区
            ob_end_clean(); 
        }
        // header('Content-Type: text/event-stream');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        // 重点，必须要加
        header('X-Accel-Buffering: no');


        $content = $this->request->post('message');
        $uid = $this->request->post('uid');
        $name = $this->request->post('username');
        $num = $this->request->post('num');
        $token = $this->request->post('token');

       
        $apiurl = get_sys_config('proxy').'/v1';
        $model  = get_sys_config('open_model');


        $youke_num = get_sys_config('youke_num'); //获取游客对话次数

        
        $count = Db::table('ba_chat')->where('token',$token)->count(); 
    

        // var_dump("kkkk",$count);
        // exit;
        if(strlen($content) > 2000){
            echo '内容太长,请重试';
            exit;
        }

        $content = '梅花易数是算命的一种方式,假如你是掌握梅花易数知识的专家，当前用随机数字起卦,'. $content. ',请详细解释当前的卦象';



        $udata = [
            'uid'=> $uid,
            'username'=> $name,
            'messages'=> $content,
            'num'=> $num,
            'token'=> $token,
            'model'=> $model,
            'cate' => 'fate',
            'ip'=> $this->request->ip(),
        ];

        $this->save($udata);

        // if( strlen($token) <= 10 ||  $name == '游客959' || $name == '游客undefined'){
        //     echo '白嫖网站，请访问：https://ai.goodwpd.com';
        //     exit;
        // }

        if($uid == 0 && $count > $youke_num ){
            echo '对话次数不足';
            exit;
        }


        if($uid != 0){
            Db::table('ba_user')
            ->where('id', $uid)
            ->dec('num', 1)
            ->update();
        }

        $getdata = $this->getKey();
        if($getdata == ''){
            echo '没有可用的key';
            exit;
        }


        $yourApiKey =  $getdata['apikey'];

        $client = OpenAI::factory()
            ->withApiKey($yourApiKey)
            ->withOrganization('') // default: null
            ->withBaseUri($apiurl) // default: api.openai.com/v1
            ->withHttpClient($client = new \GuzzleHttp\Client([])) // default: HTTP client found using PSR-18 HTTP Client Discovery
            ->withHttpHeader('X-My-Header', 'foo')
            ->withQueryParam('my-param', 'bar')
            ->make();
        

            $stream = $client->completions()->createStreamed([
                'model' => 'text-davinci-003',
                'prompt' => $content,
                'max_tokens' => 2000,
                'temperature' => 0.8,
            ]);
        

            $content = '';
            foreach($stream as $response){

                // var_dump($response->choices[0]->text);
                echo $response->choices[0]->text;
                flush();
            }

            $udata = [
                'uid'=> 0,
                'username'=> 'AI机器人',
                'messages'=> $content,
                'token'=> 0,
                'cate' => 'fate',
                'model'=> $model,
            ];
    
            $this->save($udata);
            //return $response->toArray();

    }


 
   



}