/*
Navicat MySQL Data Transfer

Source Server         : 192.168.1.222
Source Server Version : 50537
Source Host           : 192.168.1.222:3306
Source Database       : mall_db

Target Server Type    : MYSQL
Target Server Version : 50537
File Encoding         : 65001

Date: 2014-12-12 11:29:30
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `address`
-- ----------------------------
CREATE  TABLE IF NOT EXISTS `address` (
  `id` bigint(21) unsigned NOT NULL AUTO_INCREMENT,
  `uid` bigint(21) NOT NULL COMMENT '用户ID',
  `telephone` char(11) NOT NULL COMMENT '用户手机号',
  `name` varchar(50) NOT NULL COMMENT '收货人',
  `receive_tel` char(11) NOT NULL COMMENT '收货人电话',
  `province` varchar(20) NOT NULL COMMENT '省信息',
  `city` varchar(50) NOT NULL COMMENT '市信息',
  `area` varchar(50) NOT NULL COMMENT '地区信息',
  `detail` text NOT NULL COMMENT '地址详细信息',
  `postcode` char(7) NOT NULL COMMENT '邮编',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认 1,默认 0,非默认',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1,正常，0删除',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `index_uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='收货地址表';

-- ----------------------------
-- Table structure for `admin_logs`
-- ----------------------------
CREATE  TABLE IF NOT EXISTS `admin_logs` (
  `id` bigint(21) unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL COMMENT '数据表名称',
  `operate_type` char(6) NOT NULL COMMENT '操作类型:insert,update,delete',
  `operate_name` varchar(100) NOT NULL COMMENT '操作人名称',
  `from` text NOT NULL COMMENT '操作前原数值',
  `to` text NOT NULL COMMENT '操作后数值',
  `explain` varchar(255) DEFAULT '' COMMENT '说明信息',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='管理员操作流水表';


-- ----------------------------
-- Table structure for `advert`
-- ----------------------------

CREATE  TABLE IF NOT EXISTS `advert` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `img` varchar(255) NOT NULL COMMENT '图片地址',
  `src` varchar(255) NOT NULL COMMENT '连接地址',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态 0 无效 1 有效',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for `category`
-- ----------------------------

CREATE  TABLE IF NOT EXISTS `category` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `category_name` varchar(255) NOT NULL COMMENT '分类名称',
  `desc` text COMMENT '描述',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`category_id`),
  KEY `category_name_index` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='商品分类表';

-- ----------------------------
-- Table structure for `goods`
-- ----------------------------

CREATE  TABLE IF NOT EXISTS `goods` (
  `goods_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL COMMENT '商品分类ID',
  `goods_name` varchar(255) NOT NULL COMMENT '商品名称',
  `title` varchar(255) DEFAULT NULL COMMENT '副标题',
  `desc` text COMMENT '商品描述',
  `picture` varchar(350) DEFAULT NULL COMMENT '商品图片',
  `price` decimal(10,2) NOT NULL COMMENT '商品价格',
  `promot` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_promot` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否上架，1为上架，0为下架',
  `num` int(11) NOT NULL DEFAULT '0',
  `max_num` int(11) NOT NULL DEFAULT '1' COMMENT '允许最大购买数量',
  `display_url` varchar(255) DEFAULT NULL COMMENT '商品展示URL地址',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`goods_id`),
  KEY `category_id_index` (`category_id`),
  KEY `goods_name_index` (`goods_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='商品表';

-- ----------------------------
-- Table structure for `orders`
-- ----------------------------

CREATE  TABLE IF NOT EXISTS `orders` (
  `order_sn` varchar(64) NOT NULL COMMENT '订单号',
  `uid` bigint(21) unsigned NOT NULL COMMENT '用户UID',
  `telephone` char(11) NOT NULL DEFAULT '' COMMENT '用户手机号',
  `goods_id` int(10) unsigned NOT NULL COMMENT '商品主键ID',
  `goods_name` varchar(255) NOT NULL COMMENT '商品名称',
  `title` varchar(255) DEFAULT NULL COMMENT '副标题',
  `desc` text COMMENT '详细描述',
  `picture` varchar(255) DEFAULT NULL COMMENT '图片',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `promot` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品促销价格',
  `is_promot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否促销，1为是促销，0为不促销',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '数量',
  `total_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品总价',
  `real_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品实际总价',
  `buyer_name` varchar(50) NOT NULL COMMENT '收货人',
  `buyer_telephone` char(11) NOT NULL COMMENT '用户手机号',
  `buyer_address` text NOT NULL COMMENT '地址详细信息',
  `buyer_postcode` char(7) NOT NULL COMMENT '邮编',
  `pay_type` tinyint(2) DEFAULT '0' COMMENT '付款途径：1支付宝，2银联',
  `trad` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '运费',
  `other` varchar(255) NOT NULL DEFAULT '' COMMENT '备注信息',
  `status` tinyint(1) unsigned NOT NULL COMMENT '订单状态，1未付款，2未发货，3已发货，4已关闭，5已删除',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '修改时间',
  KEY `order_sn_index` (`order_sn`),
  KEY `uid_index` (`uid`),
  KEY `goods_id_index` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单表';