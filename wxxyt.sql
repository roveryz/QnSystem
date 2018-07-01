/*
Navicat MySQL Data Transfer

Source Server         : mydb
Source Server Version : 50617
Source Host           : localhost:3306
Source Database       : wxxyt

Target Server Type    : MYSQL
Target Server Version : 50617
File Encoding         : 65001

Date: 2015-07-14 20:13:22
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `sse_ans`
-- ----------------------------
DROP TABLE IF EXISTS `sse_ans`;
CREATE TABLE `sse_ans` (
  `ans_id` int(20) NOT NULL AUTO_INCREMENT,
  `ans_content` varchar(255) CHARACTER SET utf8 DEFAULT '',
  `qs_id` int(20) NOT NULL,
  `user_id` int(20) NOT NULL,
  PRIMARY KEY (`ans_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of sse_ans
-- ----------------------------
INSERT INTO `sse_ans` VALUES ('10', '111', '17', '0');
INSERT INTO `sse_ans` VALUES ('11', '222', '17', '0');

-- ----------------------------
-- Table structure for `sse_choice`
-- ----------------------------
DROP TABLE IF EXISTS `sse_choice`;
CREATE TABLE `sse_choice` (
  `choice_id` int(20) NOT NULL AUTO_INCREMENT,
  `qs_id` int(20) NOT NULL,
  `choice_content` varchar(140) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `choice_weight` int(10) DEFAULT NULL,
  `choice_count` int(10) NOT NULL DEFAULT '0',
  `choice_percentage` float(5,2) DEFAULT '0.00',
  PRIMARY KEY (`choice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of sse_choice
-- ----------------------------
INSERT INTO `sse_choice` VALUES ('15', '13', '选项1~', null, '1', '50.00');
INSERT INTO `sse_choice` VALUES ('16', '13', '选项2~', null, '1', '50.00');
INSERT INTO `sse_choice` VALUES ('17', '14', '1212', null, '0', '0.00');
INSERT INTO `sse_choice` VALUES ('18', '14', '1212', null, '0', '0.00');
INSERT INTO `sse_choice` VALUES ('19', '15', '必答', null, '2', '100.00');
INSERT INTO `sse_choice` VALUES ('20', '15', '必答就是必答', null, '1', '50.00');
INSERT INTO `sse_choice` VALUES ('21', '16', '111', null, '0', '0.00');

-- ----------------------------
-- Table structure for `sse_choice_record`
-- ----------------------------
DROP TABLE IF EXISTS `sse_choice_record`;
CREATE TABLE `sse_choice_record` (
  `choice_record_id` int(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(20) NOT NULL,
  `choice_id` int(20) NOT NULL,
  PRIMARY KEY (`choice_record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of sse_choice_record
-- ----------------------------
INSERT INTO `sse_choice_record` VALUES ('21', '0', '15');
INSERT INTO `sse_choice_record` VALUES ('22', '0', '19');
INSERT INTO `sse_choice_record` VALUES ('23', '0', '16');
INSERT INTO `sse_choice_record` VALUES ('24', '0', '19');
INSERT INTO `sse_choice_record` VALUES ('25', '0', '20');

-- ----------------------------
-- Table structure for `sse_qn`
-- ----------------------------
DROP TABLE IF EXISTS `sse_qn`;
CREATE TABLE `sse_qn` (
  `qn_id` int(20) NOT NULL AUTO_INCREMENT,
  `qn_name` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `qn_create` date NOT NULL,
  `qn_start` date DEFAULT NULL,
  `qn_end` date DEFAULT NULL,
  `qn_state` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '0',
  `qn_count` int(20) DEFAULT '0',
  `qn_last_modify` date DEFAULT NULL,
  PRIMARY KEY (`qn_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of sse_qn
-- ----------------------------
INSERT INTO `sse_qn` VALUES ('3', '我是一个测试问卷', '2015-07-14', '2015-07-14', null, '发布中', '2', null);

-- ----------------------------
-- Table structure for `sse_qn_record`
-- ----------------------------
DROP TABLE IF EXISTS `sse_qn_record`;
CREATE TABLE `sse_qn_record` (
  `record_id` int(20) NOT NULL AUTO_INCREMENT,
  `qn_id` int(20) NOT NULL,
  `date` date NOT NULL,
  `user_id` int(20) NOT NULL,
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of sse_qn_record
-- ----------------------------
INSERT INTO `sse_qn_record` VALUES ('12', '3', '2015-07-14', '0');
INSERT INTO `sse_qn_record` VALUES ('13', '3', '2015-07-14', '0');

-- ----------------------------
-- Table structure for `sse_qs`
-- ----------------------------
DROP TABLE IF EXISTS `sse_qs`;
CREATE TABLE `sse_qs` (
  `qs_id` int(20) NOT NULL AUTO_INCREMENT,
  `qn_id` int(20) NOT NULL,
  `qs_content` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `qs_style` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '单选',
  `qs_weight` int(10) DEFAULT '0',
  `qs_needans` tinyint(3) NOT NULL DEFAULT '0',
  `qs_count` int(10) DEFAULT '0',
  PRIMARY KEY (`qs_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of sse_qs
-- ----------------------------
INSERT INTO `sse_qs` VALUES ('13', '3', '这是个必答的单选题', '单选', '0', '1', '2');
INSERT INTO `sse_qs` VALUES ('14', '3', '非必答的单选题', '单选', '0', '0', '0');
INSERT INTO `sse_qs` VALUES ('15', '3', '必答的多选', '多选', '0', '1', '2');
INSERT INTO `sse_qs` VALUES ('16', '3', '非必答的多选', '多选', '0', '0', '0');
INSERT INTO `sse_qs` VALUES ('17', '3', '必答的开放', '开放', '0', '1', '2');
INSERT INTO `sse_qs` VALUES ('18', '3', '非必答的论述', '开放', '0', '0', '0');

-- ----------------------------
-- Table structure for `sse_user`
-- ----------------------------
DROP TABLE IF EXISTS `sse_user`;
CREATE TABLE `sse_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `open_id` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `user_name` varchar(50) CHARACTER SET utf8 DEFAULT '',
  `user_level` tinyint(3) NOT NULL,
  `user_state` tinyint(3) NOT NULL,
  `user_pwd` varchar(16) CHARACTER SET utf8 DEFAULT '',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of sse_user
-- ----------------------------
INSERT INTO `sse_user` VALUES ('1', '123', 'zhang', '1', '1', '123');
INSERT INTO `sse_user` VALUES ('2', '124', 'who', '2', '1', 'who');
