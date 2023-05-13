<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 卡密列管理
 *
 */
class Carmi extends Backend
{
    /**
     * Carmi模型对象
     * @var \app\admin\model\Carmi
     */
    protected $model = null;
    
    protected $preExcludeFields = ['id'];

    protected $withJoinTable = ['user'];

    protected $quickSearchField = ['id'];

    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\admin\model\Carmi;
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        // 如果是select则转发到select方法,若select未重写,其实还是继续执行index
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);
        $res->visible(['user' => ['username']]);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */

     public function pladd(){

        if($this->request->isPost()){

            $addnum = $this->request->post('addnum');
            $num = $this->request->post('num');
            $day = $this->request->post('day');

            $list = [];

            for($index = 0; $index < $addnum; $index++){
    
                 array_push($list, [
                     'key' => md5(uniqid()),
                     'num' => $num,
                     'day' => $day,
                     'status' => 0,
                     'add_time'=>time(),
                 ]);
            }

            // $list = [
            //     ['name'=>'thinkphp','email'=>'thinkphp@qq.com'],
            //     ['name'=>'onethink','email'=>'onethink@qq.com']
            // ];
            $this->model->saveAll($list);

            $this->success('新增成功');
        }

    }
}