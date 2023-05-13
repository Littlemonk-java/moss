<?php

namespace app\admin\model;

use think\Model;

/**
 * Carmi
 */
class Carmi extends Model
{
    // 表名
    protected $name = 'Carmi';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;


    public function user()
    {
        return $this->belongsTo(\app\admin\model\User::class, 'user_id', 'id');
    }
}