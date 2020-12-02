<?php
/**
 * FunAdmin
 * ============================================================================
 * 版权所有 2017-2028 FunAdmin，并保留所有权利。
 * 网站地址: https://www.FunAdmin.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2019/11/7
 */

return [
    'display' => [
        'title' => '是否启用:',
        'type' => 'radio',
        'rule' => 'required',
        'content' => [
            '1' => '启用',
            '0' => '不启用'
        ],
        'value'   => 1,
        'msg'     => '',
        'tips'     => '',
        'ok'      => '',

    ],
    'rewrite'=>[
        'title'=>'路由',
        'type'=>'array',
        'rule'=>'',
        'content' => [

        ],
        'value'=>[
            'database/backend/index'=>'database/backend/index/index/index',
            'database/backend/index'=>'database/backend/index/index/index',
        ],
        'tips'     => '',
        'ok'      => '',
    ]
];