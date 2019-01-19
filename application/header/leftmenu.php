<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/26
 * Time: 16:04
 */
$leftmenu = [
    [
        'navName' => '系统设置',
        'navChild' => [
//            [
//                'navName'=>'小程序参数',
//                'url'=>'System/wApp'
//            ],
            [
                'navName' => '微信账号',
                'url' => 'System/wAccount'
            ],
//            [
//                'navName' => '平台设置',
//                'url' => 'System/headerConfig'
//            ],
//            [
//                'navName'=>'用户管理',
//                'url'=>'System/userList'
//            ],
//            [
//                'navName'=>'角色名称',
//                'url'=>'System/roleList'
//            ],
//            [
//                'navName'=>'权限管理',
//                'url'=>'System/accessNode'
//            ]
        ]
    ],
    [
        'navName' => '账号管理',
        'navChild' => [
            [
                'navName' => '团长列表',
                'url' => 'Leader/index'
            ],
            [
                'navName' => '团长申请记录',
                'url' => 'Leader/applyList'
            ],
            [
                'navName' => '团长提现记录',
                'url' => 'Leader/withdrawList'
            ],
        ]
    ],
    [
        'navName' => '营销活动',
        'navChild' => [
            [
                'navName' => '优惠券',
                'url' => 'Coupon/index'
            ],
        ]
    ],
    [
        'navName' => '商城管理',
        'navChild' => [
            [
                'navName' => '待配货产品',
                'url' => 'Product/readyMatchProduct'
            ],
            [
                'navName' => '待配送产品',
                'url' => 'Product/shippingProduct'
            ],
//            [
//                'navName' => '订单列表',
//                'url' => 'Product/shippingProduct'
//            ],

        ]
    ],
    [
        'navName' => '产品库',
        'navChild' => [
            [
                'navName' => '产品列表',
                'url' => 'Product/index'
            ],
            [
                'navName' => '产品分类',
                'url' => 'Product/cateIndex'
            ],
            [
                'navName' => '产品单位',
                'url' => 'Product/unitIndex'
            ],
            [
                'navName' => '产品标签',
                'url' => 'Product/tagList'
            ],
        ]
    ],
//    [
//        'navName' => '数据统计',
//        'navChild' => [
//            [
//                'navName' => '产品销量',
//                'url' => 'Sale/productCount'
//            ]
//        ]
//    ],
];