<?php
namespace app\common\model;

use think\Model;
class admin extends Model
{
    //自动完成[新增和修改时都会执行]
    protected $auto =[
    ];
    //新增时自动验证
    protected $insert=[
    ];
    //修改时自动验证
    protected $update=[
    ];
}
