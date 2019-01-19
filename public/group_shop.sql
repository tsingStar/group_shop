/*
 Navicat Premium Data Transfer

 Source Server         : ybt
 Source Server Type    : MySQL
 Source Server Version : 50553
 Source Host           : 39.104.115.195:3306
 Source Schema         : group_shop

 Target Server Type    : MySQL
 Target Server Version : 50553
 File Encoding         : 65001

 Date: 17/01/2019 16:23:33
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ts_admins
-- ----------------------------
DROP TABLE IF EXISTS `ts_admins`;
CREATE TABLE `ts_admins`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uname` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '管理员用户名',
  `password` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '登陆密码',
  `encrypt` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '加密字符串',
  `name` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '管理员名称',
  `role_id` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '角色id',
  `login_ip` int(11) NOT NULL COMMENT '最近登陆ip',
  `login_time` int(11) NOT NULL COMMENT '登陆时间',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT 1 COMMENT '账号是否可用',
  `describe` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_apply_leader_record
-- ----------------------------
DROP TABLE IF EXISTS `ts_apply_leader_record`;
CREATE TABLE `ts_apply_leader_record`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '团长申请id',
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '团长姓名',
  `telephone` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '联系方式',
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '所在地址',
  `lat` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '纬度',
  `lng` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '经度',
  `residential` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '小区名称',
  `neighbours` int(11) NOT NULL COMMENT '小区户数',
  `have_group` tinyint(1) NOT NULL COMMENT '是否有自己的邻居群 0 1',
  `have_sale` tinyint(1) NOT NULL COMMENT '是否有销售经验 0 1',
  `work_time` tinyint(4) NOT NULL COMMENT '业务时间  0 每天 1 周末 2 空余时间',
  `other` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '其他',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '处理状态 0 未处理 1 已处理 2 已拒绝',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  `remarks` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 131 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '团长申请表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_bank_info
-- ----------------------------
DROP TABLE IF EXISTS `ts_bank_info`;
CREATE TABLE `ts_bank_info`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `bank_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '银行卡号',
  `bank_code` varchar(4) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '银行编号',
  `bank_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '银行名称',
  `true_name` varchar(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '姓名',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `header_id`(`header_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_cate
-- ----------------------------
DROP TABLE IF EXISTS `ts_cate`;
CREATE TABLE `ts_cate`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品分类id',
  `cate_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '分类名称',
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '商品分类表' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_commission_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_commission_log`;
CREATE TABLE `ts_commission_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `leader_id` int(11) NOT NULL,
  `header_id` int(11) NOT NULL,
  `order_det_id` int(11) NOT NULL COMMENT '订单详情id',
  `commission` decimal(10, 2) NOT NULL COMMENT '团长佣金',
  `header_money` decimal(10, 2) NOT NULL COMMENT '城主金额',
  `day` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '处理状态 0 未处理 1 已处理',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 38 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_coupon
-- ----------------------------
DROP TABLE IF EXISTS `ts_coupon`;
CREATE TABLE `ts_coupon`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户优惠券id',
  `header_id` int(11) NULL DEFAULT NULL COMMENT '城主id',
  `limit_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '最低使用金额',
  `coupon_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '优惠券金额',
  `out_time` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '失效时间',
  `total_num` int(11) NULL DEFAULT 0 COMMENT '优惠券总数量 ',
  `spread_num` int(11) NULL DEFAULT 0 COMMENT '发放数量',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_header
-- ----------------------------
DROP TABLE IF EXISTS `ts_header`;
CREATE TABLE `ts_header`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '城主id',
  `open_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `name` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户名',
  `password` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '登陆密码',
  `telephone` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '手机号',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `rate` decimal(5, 4) NULL DEFAULT 0.0000 COMMENT '城主提现费率',
  `enable` tinyint(1) NOT NULL DEFAULT 1 COMMENT '账号是否可用 0 1',
  `head_image` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '城主头像',
  `nick_name` varchar(5) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '城主昵称',
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '城主地址',
  `profession` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '职业',
  `is_leader` tinyint(1) NOT NULL COMMENT '是否做过社区团购团长 0 1',
  `have_group` tinyint(1) NOT NULL COMMENT '是否有自己的团队 0 无 1 有',
  `other` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '其他',
  `amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '城主总营业额',
  `amount_able` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '可用余额',
  `withdraw` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '总提现金额',
  `amount_lock` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '当前冻结余额',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ts_header
-- ----------------------------
INSERT INTO `ts_header` VALUES (1, '', 'header', 'e10adc3949ba59abbe56e057f20f883e', '13695397227', 0, 1547088082, 0.6000, 1, 'https://www.ybt9.com/upload/20180921/dc5fdd97f99cbebfe9acc1c88e3eade8.jpg', '', '', '', 0, 0, NULL, 164.19, 104.19, 60.00, 0.00);
INSERT INTO `ts_header` VALUES (2, '', 'ceshi', '1fdb7184e697ab9355a3f1438ddc6ef9', '', 0, 0, 0.0000, 1, NULL, '', '', '', 0, 0, NULL, 0.00, 0.00, 0.00, 0.00);

-- ----------------------------
-- Table structure for ts_header_access
-- ----------------------------
DROP TABLE IF EXISTS `ts_header_access`;
CREATE TABLE `ts_header_access`  (
  `role_id` int(11) NOT NULL COMMENT '角色id',
  `node_url` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '可访问url',
  PRIMARY KEY (`role_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_header_employee
-- ----------------------------
DROP TABLE IF EXISTS `ts_header_employee`;
CREATE TABLE `ts_header_employee`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '城主下雇员id',
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `employee_name` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '雇员名称',
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '登录密码',
  `login_ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '登录ip',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_header_money_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_header_money_log`;
CREATE TABLE `ts_header_money_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `type` tinyint(1) NOT NULL COMMENT '余额变动类型 1 当日结算 2 提现 3 退款金额冲减 4 微信手续费 5 退款手续费返还 6 优惠券积分抵扣 7 红包抵扣 ',
  `pre_amount` decimal(10, 2) NOT NULL COMMENT '变动前金额',
  `money` decimal(10, 2) NOT NULL COMMENT '变动金额',
  `after_amount` decimal(10, 2) NOT NULL COMMENT '变动后金额',
  `order_no` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `header_id`(`header_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 200 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '城主余额变动明细' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_header_role
-- ----------------------------
DROP TABLE IF EXISTS `ts_header_role`;
CREATE TABLE `ts_header_role`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '城主雇员角色id',
  `role_name` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '角色名称',
  `header_id` int(11) NULL DEFAULT NULL COMMENT '城主id',
  `pid` int(11) NULL DEFAULT NULL COMMENT '角色识别',
  `remarks` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '角色备注',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_header_role_user
-- ----------------------------
DROP TABLE IF EXISTS `ts_header_role_user`;
CREATE TABLE `ts_header_role_user`  (
  `role_id` int(11) NOT NULL COMMENT '角色id',
  `user_id` int(11) NULL DEFAULT NULL COMMENT '用户id',
  PRIMARY KEY (`role_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '城主雇员角色用户关联表' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_image
-- ----------------------------
DROP TABLE IF EXISTS `ts_image`;
CREATE TABLE `ts_image`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品海报id',
  `header_id` int(11) NULL DEFAULT NULL COMMENT '城主id',
  `image_url` varchar(225) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '图片地址',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  `md5` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '文件散列值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 110 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '商品预热图片库' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_leader_money_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_leader_money_log`;
CREATE TABLE `ts_leader_money_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `leader_id` int(11) NOT NULL COMMENT '团长id',
  `type` tinyint(1) NOT NULL COMMENT '余额变动类型 1 团购结算  2 提现  3 退款冲减  4 微信手续费 5 退款手续费返还',
  `pre_amount` decimal(10, 2) NOT NULL COMMENT '操作前金额',
  `money` decimal(10, 2) NOT NULL COMMENT '变动金额',
  `after_amount` decimal(10, 2) NOT NULL COMMENT '操作后金额',
  `order_no` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `leader_id`(`leader_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1081 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '团长余额变动明细' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_menu
-- ----------------------------
DROP TABLE IF EXISTS `ts_menu`;
CREATE TABLE `ts_menu`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '节点名称',
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块',
  `display` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示 0 不显示 1 显示',
  `describe` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '节点描述',
  `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '上级目录',
  `level` int(11) NOT NULL COMMENT '目录等级',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10050 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ts_menu
-- ----------------------------
INSERT INTO `ts_menu` VALUES (1, '平台管理', 'admin', 1, '平台管理', 0, 1, 0, 1532942841);
INSERT INTO `ts_menu` VALUES (2, '系统管理', 'admin/Role', 1, '目录管理', 1, 2, 0, 1532942929);
INSERT INTO `ts_menu` VALUES (3, '角色管理', 'admin/Role/roleList', 1, '', 2, 3, 0, 0);
INSERT INTO `ts_menu` VALUES (5, '管理员列表', 'admin/Admin/adminList', 1, '', 2, 3, 0, 0);
INSERT INTO `ts_menu` VALUES (6, '目录管理', 'admin/Menu', 1, '目录管理', 1, 2, 0, 1520573832);
INSERT INTO `ts_menu` VALUES (7, '目录列表', 'admin/Menu/menuList', 1, '', 6, 3, 0, 0);
INSERT INTO `ts_menu` VALUES (10019, '商品管理', 'admin/Product', 1, '', 1, 2, 0, 1532566121);
INSERT INTO `ts_menu` VALUES (10020, '商品分类', 'admin/Category/index', 1, '平台商品分类管理', 10019, 3, 0, 1521427938);
INSERT INTO `ts_menu` VALUES (10013, '平台设置', 'admin/Site/index', 1, '', 1, 2, 0, 1523497124);
INSERT INTO `ts_menu` VALUES (10027, '订单管理', 'admin/Order', 1, '', 1, 2, 1522050858, 1532566148);
INSERT INTO `ts_menu` VALUES (10024, '商品库', 'admin/Product/index', 1, '', 10019, 3, 1521441329, 1521441329);
INSERT INTO `ts_menu` VALUES (10028, '全部订单', 'admin/Order/orderList', 1, '', 10027, 3, 1522050909, 1522050909);
INSERT INTO `ts_menu` VALUES (10029, '当日订单', 'admin/Order/todayList', 1, '', 10027, 3, 1522050944, 1522050944);
INSERT INTO `ts_menu` VALUES (10043, '轮播图设置', 'admin/Site/swiperList', 1, '轮播设置', 10013, 3, 1532569813, 1532569813);
INSERT INTO `ts_menu` VALUES (10045, '商品属性', 'admin/Product/productProp', 1, '商品属性设置', 10019, 3, 1532919444, 1532919444);
INSERT INTO `ts_menu` VALUES (10046, '部门管理', 'admin/Role/department', 1, '部门管理', 2, 3, 1532943038, 1532943038);
INSERT INTO `ts_menu` VALUES (10047, '用户反馈管理', 'admin/FeedBack', 1, '', 1, 2, 1532943250, 1532943250);
INSERT INTO `ts_menu` VALUES (10048, '我的供应', 'admin/FeedBack/mySupply', 1, '', 10047, 3, 1532955504, 1532955542);
INSERT INTO `ts_menu` VALUES (10036, '客户管理', 'admin/Member/', 1, '', 1, 2, 1530583906, 1532943187);
INSERT INTO `ts_menu` VALUES (10037, '会员列表', 'admin/Member/index', 1, '', 10036, 3, 1530583969, 1530584385);
INSERT INTO `ts_menu` VALUES (10038, '会员账户变动记录', 'admin/Member/accountList', 1, '', 10036, 3, 1530584249, 1530584249);
INSERT INTO `ts_menu` VALUES (10049, '我的订购', 'admin/FeedBack/myBuy', 1, '', 10047, 3, 1532955598, 1532955598);
INSERT INTO `ts_menu` VALUES (10040, '用户注册协议', 'admin/Site/register', 1, '', 10013, 3, 1530927355, 1530927355);
INSERT INTO `ts_menu` VALUES (10041, '关于我们', 'admin/Site/about_us', 1, '', 10013, 3, 1530929968, 1530929968);
INSERT INTO `ts_menu` VALUES (10042, '联系方式', 'admin/Site/contact_us', 1, '', 10013, 3, 1530930948, 1530930948);

-- ----------------------------
-- Table structure for ts_order
-- ----------------------------
DROP TABLE IF EXISTS `ts_order`;
CREATE TABLE `ts_order`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '订单编号',
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `leader_id` int(11) NOT NULL COMMENT '团长id',
  `user_id` int(11) NOT NULL COMMENT '团员id',
  `pay_time` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '1' COMMENT '订单支付时间',
  `pick_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送地址或自提点信息',
  `order_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单状态 0 待支付 1已支付 2 已取消',
  `telephone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '联系电话',
  `order_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
  `coupon_id` int(11) NOT NULL DEFAULT 0 COMMENT '优惠券id',
  `coupon_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '优惠券金额',
  `score` int(10) NOT NULL DEFAULT 0 COMMENT '使用积分数量',
  `score_money` decimal(10, 2) NOT NULL COMMENT '积分抵用金额',
  `pay_money` decimal(10, 2) NOT NULL COMMENT '订单实际支付金额',
  `refund_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '退款总金额',
  `pay_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '订单支付单号',
  `remarks` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '订单备注',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  `transaction_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '微信订单号',
  `send_num` int(11) NOT NULL DEFAULT 0 COMMENT '短信通知数量',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 34 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '团员订单' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_order_det
-- ----------------------------
DROP TABLE IF EXISTS `ts_order_det`;
CREATE TABLE `ts_order_det`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `leader_id` int(11) NOT NULL COMMENT '团长id',
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL COMMENT '商品id',
  `order_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '订单编号',
  `product_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商品名称',
  `buy_num` int(11) NOT NULL DEFAULT 0 COMMENT '购买数量',
  `purchase_price` decimal(10, 2) NOT NULL COMMENT '商品进货价',
  `market_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '市场价',
  `sale_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '团购价格',
  `commission` decimal(10, 2) NOT NULL COMMENT '佣金比例',
  `unit` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '单位',
  `attr` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '规格',
  `one_num` decimal(10, 2) NOT NULL COMMENT '每份数量',
  `back_num` int(1) NOT NULL DEFAULT 0 COMMENT '退货数量',
  `is_comp` tinyint(1) NOT NULL DEFAULT 0 COMMENT '佣金是否已结算 0 未结算 1 已结算',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品状态 0 待支付 1 待配送 2 待自提 3 已完成 4 退款/退货 5 已取消',
  `is_get` tinyint(1) NOT NULL COMMENT '商品是否已收获 0 未配送 1 待收货 2 已收货',
  `is_match` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品是否已配货 0 未处理 1已处理',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 44 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_order_refund
-- ----------------------------
DROP TABLE IF EXISTS `ts_order_refund`;
CREATE TABLE `ts_order_refund`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申请退款id',
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `leader_id` int(11) NOT NULL COMMENT '团长',
  `user_id` int(11) NOT NULL COMMENT '团员id',
  `product_id` int(11) NOT NULL COMMENT '团购商品id',
  `refund_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退款订单号',
  `order_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '订单编号',
  `order_det_id` int(11) NOT NULL COMMENT '订单详情id',
  `num` int(11) NOT NULL DEFAULT 0 COMMENT '申请退款数量',
  `product_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商品名称',
  `market_price` decimal(10, 2) NOT NULL COMMENT '市场价格',
  `sale_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '商品团购价',
  `refund_money` decimal(10, 2) NOT NULL COMMENT '退款金额',
  `reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '退款说明',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '申请状态 0 未处理 1 同意 2 拒绝',
  `refuse_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '拒绝原因',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 86 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_payment
-- ----------------------------
DROP TABLE IF EXISTS `ts_payment`;
CREATE TABLE `ts_payment`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '支付订单id',
  `order_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '平台订单编号',
  `pay_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '订单支付单号',
  `pay_money` decimal(10, 2) NOT NULL,
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付状态 0未支付 1 已支付 2 已取消',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 28 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '订单支付关联' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_product
-- ----------------------------
DROP TABLE IF EXISTS `ts_product`;
CREATE TABLE `ts_product`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品id',
  `product_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '商品名称',
  `ord` int(10) NOT NULL DEFAULT 10 COMMENT '商品排序序号',
  `stock` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '商品库存数量',
  `unit` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商品单位',
  `cate_id` int(10) NOT NULL DEFAULT 0 COMMENT '商品分类id',
  `attr` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商品规格',
  `is_sec` tinyint(1) NULL DEFAULT 0 COMMENT '是否为秒杀商品 0 否 1 是',
  `is_hot` tinyint(1) NULL DEFAULT 0 COMMENT '是否为热销商品 0 否 1 是',
  `desc` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商品描述',
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `product_detail` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商品详情图',
  `tag_name` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商品标签',
  `self_limit` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '个人限购',
  `one_num` decimal(10, 2) NOT NULL COMMENT '每份数量',
  `purchase_price` decimal(10, 2) NOT NULL COMMENT '进货价',
  `sale_price` decimal(10, 2) NOT NULL COMMENT '销售价',
  `market_price` decimal(10, 2) NOT NULL COMMENT '市场价',
  `commission` decimal(10, 2) NOT NULL COMMENT '佣金比例',
  `ratioOfMargin` decimal(10, 2) NOT NULL COMMENT '毛利率',
  `limit` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商品限量',
  `is_up` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品状态 0 下架 1 上架',
  `start_time` varchar(22) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '开售时间',
  `down_time` varchar(22) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '自动下架时间',
  `total_sell` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '商品总销量',
  `current_sell` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '当前日期总销量',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 392 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_product_sale_attr_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_product_sale_attr_log`;
CREATE TABLE `ts_product_sale_attr_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT '商品id',
  `tag_name` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商品标签',
  `self_limit` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '个人限购',
  `one_num` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '每份数量',
  `purchase_price` decimal(10, 2) NOT NULL COMMENT '进货价',
  `sale_price` decimal(10, 2) NOT NULL COMMENT '销售价',
  `market_price` decimal(10, 2) NOT NULL COMMENT '市场价',
  `commission` decimal(10, 2) NOT NULL COMMENT '佣金比例',
  `ratioOfMargin` decimal(10, 2) NOT NULL COMMENT '毛利率',
  `limit` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商品限量',
  `start_time` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '开售时间',
  `down_time` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '下架时间',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 48 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_product_stock_record
-- ----------------------------
DROP TABLE IF EXISTS `ts_product_stock_record`;
CREATE TABLE `ts_product_stock_record`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '进货id',
  `product_id` int(11) NOT NULL COMMENT '库存商品id',
  `purchase_price` decimal(10, 2) NOT NULL COMMENT '商品进价',
  `market_price` decimal(10, 2) NOT NULL COMMENT '市场售价',
  `num` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '出入库数量',
  `type` tinyint(1) NULL DEFAULT 1 COMMENT '出入库类型 1 入库 2 进库',
  `stock_before` decimal(10, 2) NULL DEFAULT NULL COMMENT '操作前库存',
  `stock_after` decimal(10, 2) NULL DEFAULT NULL COMMENT '操作后库存',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '库存商品出入库记录' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_product_swiper
-- ----------------------------
DROP TABLE IF EXISTS `ts_product_swiper`;
CREATE TABLE `ts_product_swiper`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT '商品id',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '展示类型 1 图片 2 小视频',
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '资源地址',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1000 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_product_tag
-- ----------------------------
DROP TABLE IF EXISTS `ts_product_tag`;
CREATE TABLE `ts_product_tag`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `header_id` int(11) NULL DEFAULT NULL COMMENT '城主id',
  `tag_name` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '标签名称',
  `create_time` int(11) NOT NULL COMMENT '加入时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_refund_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_refund_log`;
CREATE TABLE `ts_refund_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `trade_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `refund_id` int(11) NOT NULL,
  `total_money` decimal(10, 2) NOT NULL,
  `refund_money` decimal(10, 2) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `refund_desc` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_role
-- ----------------------------
DROP TABLE IF EXISTS `ts_role`;
CREATE TABLE `ts_role`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '角色名称',
  `listorder` int(11) NULL DEFAULT 0 COMMENT '角色排序',
  `describe` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  `node_id` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '可访问节点',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 11 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ts_role
-- ----------------------------
INSERT INTO `ts_role` VALUES (1, '超级管理员', 1, NULL, 1520558349, 1520558349, 'all');
INSERT INTO `ts_role` VALUES (5, '平台管理员', 0, '平台日常管理', 1520577110, 1520579691, '');
INSERT INTO `ts_role` VALUES (9, '操作员', 0, '', 1520907251, 1520907251, '1,2,3,4,5,6,7,10013,10014,10016,10017,10018,10019,10020,10021,10023');
INSERT INTO `ts_role` VALUES (10, '测试', 0, '', 1520907642, 1520907642, '1,2,3,4,5,6,7,10013,10014,10016,10017');
INSERT INTO `ts_role` VALUES (8, '测试账号', 0, '', 1520907193, 1520908139, '1,2,3,10013,10014,10016');

-- ----------------------------
-- Table structure for ts_unit
-- ----------------------------
DROP TABLE IF EXISTS `ts_unit`;
CREATE TABLE `ts_unit`  (
  `id` int(3) NOT NULL AUTO_INCREMENT COMMENT '商品单位id',
  `unit_name` varchar(2) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '单位名称',
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `unit_header_id`(`header_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '商品单位列表' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_user
-- ----------------------------
DROP TABLE IF EXISTS `ts_user`;
CREATE TABLE `ts_user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `open_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户open_id',
  `user_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户昵称',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户头像',
  `role_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '用户角色 1 团员 2 团长 ',
  `create_time` int(11) NOT NULL COMMENT '注册时间',
  `update_time` int(11) NOT NULL,
  `gender` int(11) NOT NULL COMMENT '用户性别 0 未知 1 男 2 女',
  `city` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '城市',
  `province` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '省份',
  `country` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '国家',
  `header_id` int(11) NOT NULL DEFAULT 0 COMMENT '城主id',
  `amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '团长总营业额',
  `amount_able` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '可用余额',
  `withdraw` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '总提现金额',
  `amount_lock` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '当前冻结余额',
  `telephone` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '手机号',
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '地址',
  `residential` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '小区名称',
  `neighbours` int(11) NOT NULL COMMENT '小区户数',
  `have_group` tinyint(4) NULL DEFAULT NULL COMMENT '是否有自己的邻居群',
  `have_sale` tinyint(4) NULL DEFAULT NULL COMMENT '是否有销售经验',
  `work_time` tinyint(4) NULL DEFAULT NULL COMMENT '业务时间  0 每天 1 周末 2 空余时间',
  `lat` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '纬度',
  `lng` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '经度',
  `is_able` tinyint(1) NULL DEFAULT 1 COMMENT '团长状态是否可用 0 否 1 是',
  `score` int(11) NOT NULL DEFAULT 0 COMMENT '积分',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 22623 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_user_coupon
-- ----------------------------
DROP TABLE IF EXISTS `ts_user_coupon`;
CREATE TABLE `ts_user_coupon`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户优惠券id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `coupon_id` int(11) NOT NULL COMMENT '平台优惠券id',
  `limit_money` decimal(10, 2) NOT NULL COMMENT '最低使用金额',
  `coupon_money` decimal(10, 2) NOT NULL COMMENT '优惠券金额',
  `use_time` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '使用时间',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '使用状态 0 未使用 1 已使用 2 已过期',
  `out_time` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '失效时间',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4769 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_user_red_pack
-- ----------------------------
DROP TABLE IF EXISTS `ts_user_red_pack`;
CREATE TABLE `ts_user_red_pack`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `order_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '订单编号',
  `red_pack` decimal(5, 2) NOT NULL DEFAULT 0.00 COMMENT '红包金额',
  `status` int(1) NOT NULL COMMENT '分享获得状态 0 未获得 1 获得',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_user_score_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_user_score_log`;
CREATE TABLE `ts_user_score_log`  (
  `score_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用戶id',
  `pre_score` int(10) NOT NULL DEFAULT 0,
  `score` int(10) NOT NULL COMMENT '积分数量',
  `after_score` int(10) NOT NULL DEFAULT 0,
  `type` tinyint(1) NOT NULL COMMENT '积分类型 1 增加 2 减少',
  `desc` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '使用描述',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`score_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_user_sign_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_user_sign_log`;
CREATE TABLE `ts_user_sign_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `today` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '签到日期',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_waccount
-- ----------------------------
DROP TABLE IF EXISTS `ts_waccount`;
CREATE TABLE `ts_waccount`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `header_id` int(11) NOT NULL COMMENT '城主id',
  `open_id` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '微信open_id',
  `user_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户名',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是管理 0 否 1 是',
  `create_time` int(11) NULL DEFAULT NULL,
  `update_time` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ts_web_login_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_web_login_log`;
CREATE TABLE `ts_web_login_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块名',
  `controller` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '控制器',
  `action` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '方法',
  `data` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '请求参数',
  `create_time` int(11) NOT NULL COMMENT '生成时间',
  `update_time` int(11) NULL DEFAULT NULL,
  `login_ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '登陆ip',
  `admin_id` int(11) NULL DEFAULT NULL COMMENT '用户操作id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 56 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_web_opera_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_web_opera_log`;
CREATE TABLE `ts_web_opera_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块名',
  `controller` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '控制器',
  `action` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '方法',
  `data` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '请求参数',
  `create_time` int(11) NOT NULL COMMENT '生成时间',
  `update_time` int(11) NULL DEFAULT NULL,
  `login_ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '登陆ip',
  `admin_id` int(11) NULL DEFAULT NULL COMMENT '用户操作id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 211 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ts_withdraw_log
-- ----------------------------
DROP TABLE IF EXISTS `ts_withdraw_log`;
CREATE TABLE `ts_withdraw_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` tinyint(4) NOT NULL DEFAULT 0 COMMENT '提现角色 1城主 2团长',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `amount` decimal(10, 2) NOT NULL COMMENT '提现总金额',
  `fee` decimal(10, 2) NOT NULL COMMENT '提现手续费',
  `money` decimal(10, 2) NOT NULL COMMENT '到账金额',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '提现处理状态 0 未处理 1 已处理',
  `order_no` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `withdraw_time` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '提现到账日期',
  `reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '提现异常原因',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 210 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '提现申请日志表' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
