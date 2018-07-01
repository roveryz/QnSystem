<?php
/**
  * wechat php test
  */

// define your token
define ( "TOKEN", "weixin" );
$wechatObj = new wechatCallbackapiTest (); // 创建实例对象
$wechatObj->valid (); // 调用类的valid()方法执行接口验证，接口设置成功后将其注释掉。
class wechatCallbackapiTest {
	
	// 用于申请 成为开发者 时向微信发送验证信息。
	public function valid() {
		$echoStr = $_GET ["echostr"];
		
		// valid signature , option
		if ($this->checkSignature ()) {
			header('content-type:text');
			echo $echoStr;
			exit ();
		}
	}
	
	// 处理并回复用户发送过来的消息，也是用的最多的一个函数，几乎所有的功能都在这里实现。
	public function responseMsg() {
		// 接收微信公众平台发送过来的用户消息，该消息数据结构为XML，不是php默认的识别数据类型，因此这里用了$GLOBALS['HTTP_RAW_POST_DATA']来接收，同时赋值给了$postStr
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];
		
		// 判断$postStr是否为空，如果不为空（接收到了数据），就继续执行下面的语句;如果为空，则跳转到与之相对应的else语句。
		if (! empty ( $postStr )) {			
			
			$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );// 使用simplexml_load_string() 函数将接收到的XML消息数据载入对象$postObj中。这个严谨的写法后面还得加个判断是否载入成功的条件语句，不过不写也没事。
			$fromUsername = $postObj->FromUserName;// 将对象$postObj中的发送消息用户的OPENID赋值给$fromUsername变量
			$toUsername = $postObj->ToUserName;// 将对象$postObj中的公众账号的ID赋值给$toUsername变量
			$keyword = trim ( $postObj->Content );// trim() 函数从字符串的两端删除空白字符和其他预定义字符，这里就可以得到用户输入的关键词
			$time = time ();// time() 函数返回当前时间的 Unix 时间戳，即自从 Unix 纪元（格林威治时间 1970 年 1 月 1 日 00:00:00）到当前时间的秒数。
			$textTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            <FuncFlag>0</FuncFlag>
                        </xml>";// 存放微信输出内容的模板
			// 判断$keyword是否为空，不为空则继续执行下面的语句;如果为空，则跳转到与之相对应的else语句，即 echo "Input something...";
			if (! empty ( $keyword )) {
				$msgType = "text";// 消息类型是文本类型
				$contentStr = "http://www.baidu.com";// 回复的消息内容
				$resultStr = sprintf ( $textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr );// 使用sprintf() 函数将格式化的数据写入到变量中去;
				/*
				 * $fromUsername, $toUsername, $time, $msgType, $contentStr 分别顺序替换模板里“%s”位置，也即是“$resultStr”这个变量最后实际为：
				 * <xml>
				 * <ToUserName><![CDATA[$toUsername]]></ToUserName>
				 * <FromUserName><![CDATA[$fromUsername]]></FromUserName>
				 * <CreateTime>$time</CreateTime>
				 * <MsgType><![CDATA[$msgType]]></MsgType>
				 * <Content><![CDATA[$contentStr]]></Content>
				 * <FuncFlag>0</FuncFlag>      //位0x0001被标志时，星标刚收到的消息。
				 * </xml>
				 * */
				
				echo $resultStr;// //把回复的消息输出
			} else {
				echo "Input something...";
			}
		} else {
			echo "";
			exit ();
		}
	}
	
	// 开发者通过检验signature对请求进行校验（下面有校验方式）。若确认此次GET请求来自微信服务器，请求原样返回echostr参数内容，则接入生效，否则接入失败。
	// signature结合了开发者填写的token参数和请求中的timestamp参数、nonce参数。
	// 加密/校验流程：
	// 1. 将token、timestamp、nonce三个参数进行字典序排序
	// 2. 将三个参数字符串拼接成一个字符串进行sha1加密
	// 3. 开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
	private function checkSignature() {
		$signature = $_GET ["signature"];
		$timestamp = $_GET ["timestamp"];
		$nonce = $_GET ["nonce"];
		
		$token = TOKEN;
		$tmpArr = array (
				$token,
				$timestamp,
				$nonce 
		);
		sort ( $tmpArr );
		$tmpStr = implode ( $tmpArr );
		$tmpStr = sha1 ( $tmpStr );
		
		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
	}
}

?>