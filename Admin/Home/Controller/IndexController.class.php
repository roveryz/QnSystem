<?php

namespace Home\Controller;

use Think\Controller;
use Think\Model;
use Think\Exception;

class IndexController extends Controller {
	public function index() {
		$this->redirect ( 'login' );
	}
	
	/**
	 * 登录页面
	 */
	public function login() {
		$this->display (); // 显示View/Index/login.html
		if (session ( '?name' )) {
			$this->redirect ( 'qnlist' );
		} else {
			if (isset ( $_POST ['login-username'] ) && isset ( $_POST ['login-password'] )) {
				
				// 获取表单数据
				$username = $_POST ['login-username'];
				$userpwd = $_POST ['login-password'];
				
				// 实例化基础模型类
				$User = M ( 'User' );
				
				// 构造查询条件
				$condition ['user_name'] = $username;
				$condition ['user_pwd'] = $userpwd;
				$condition ['user_level'] = 2; // 管理员用户
				$condition ['user_state'] = 1; // 激活状态
				
				$result = $User->where ( $condition )->count ();
				
				if ($result > 0) {
					session_start ();
					session ( 'name', $_POST ['login-username'] ); // session存储，记录登录状态
					$this->redirect ( 'qnlist' );
				} else {
					$this->error ( "用户名/密码错误" );
				}
			}
		}
	}
	
	/**
	 * 显示问卷列表页面
	 */
	public function qnlist() {
		if (session ( '?name' )) {
			$Qn = M ( 'Qn' ); // 实例化qn对象
			$count = $Qn->count (); // 查询满足要求的总记录数
			
			$page_size = 5; // 每页显示的记录数
			$page = new \Org\Util\Page ( $count, $page_size ); // 实例化
			
			/* 美化 */
			$page->setConfig ( 'header', '篇记录' );
			$page->setConfig ( 'prev', "<" );
			$page->setConfig ( 'next', ">" );
			$page->setConfig ( 'first', "<<" );
			$page->setConfig ( 'last', ">>" );
			
			$page_show = $page->show (); // 分页输出
			                             
			// 分页数据查询，注意limit方法的参数要使用Page类的属性
			$limit = $page->firstRow . ',' . $page->listRows;
			$qn_list = $Qn->order ( 'qn_id DESC' )->limit ( $limit )->select ();
			
			for($i = 0; $i < 5; $i ++) {
				$qn_content [$i] = $this->getQsContent ( $qn_list [$i] ['qn_id'] );
			}
			$this->assign ( 'qn_list', $qn_list ); // 赋值数据集
			$this->assign ( 'qn_content', $qn_content );
			$this->assign ( 'page', $page_show ); // 赋值分页输出
			$this->assign ( 'title', '问卷列表' );
			$this->display ();
		} else {
			$this->redirect ( 'login' );
		}
	}
	
	/**
	 * 显示创建新问卷页面
	 */
	public function qncreate() {
		if (session ( '?name' )) {
			$this->assign ( 'title', '创建新问卷' );
			$this->display ();
		} else {
			$this->redirect ( 'login' );
		}
	}
	public function handleQnCreate() {
		$json = $_POST ['json'];
		$flag = 0;
		if (! empty ( $json )) {
			
			$qn = json_decode ( $json );
			
			$Qn = M ( 'qn' );
			$Qs = M ( 'qs' );
			$Choice = M ( 'choice' );
			
			$Qn->qn_name = $qn [0];
			$Qn->qn_create = date ( 'Y-m-d' );
			$Qn->qn_state = "未发布";
			
			$qnId = $Qn->add ();
			if (! empty ( $qnId )) {
				for($i = 0; $i < count ( $qn [1] ); $i ++) {
					
					$Qs->qn_id = $qnId;
					$Qs->qs_content = $qn [1] [$i] [0];
					$Qs->qs_style = $qn [1] [$i] [2];
					$Qs->qs_needans = $qn [1] [$i] [1];
					
					$qsId = $Qs->add ();
					
					if (! empty ( $qsId ) && $qn [1] [$i] [2] != "开放") {
						for($j = 0; $j < count ( $qn [1] [$i] [3] ); $j ++) {
							
							$Choice->qs_id = $qsId;
							$Choice->choice_content = $qn [1] [$i] [3] [$j];
							if ($choiceId = $Choice->add ()) {
								// ok
							}
						}
					}
				}
			}
		}
		$this->ajaxReturn ( "创建成功" );
	}
	
	/**
	 * 显示修改问卷页面
	 * 修改前置要求：
	 * 1.已发布问卷，复制创建新问卷然后修改,已有记录不删掉也不复制到新问卷
	 *
	 * @param unknown $qn_id        	
	 */
	public function qnmodify() {
		if (session ( '?name' )) {
			$qn_id = $_GET ['qn_id'];
			$Qn = M ( 'qn' );
			$condition ['qn_id'] = $qn_id;
			$qn = $Qn->where ( $condition )->find ();
			$qn_state = $qn ['qn_state'];
			$qn_count = $qn ['qn_count'];
			
			if ($qn_state == "发布中" && intval ( $qn_count ) > 0) {
				echo "<script>alert('问卷发布中，无法修改，将快速创建一份相同的新问卷')</script>";
				$qn_name = $qn ['qn_name'];
				$html = $this->getQnHtml ( $qn_id );
				$this->assign ( 'qn_id', $qn_id );
				$this->assign ( 'qn_name', $qn_name );
				$this->assign ( 'html', $html );
				$this->display ( 'quickCreate' );
			} else if ($qn_state == "已结束") {
				echo "<script>alert('问卷已结束，无法修改，将快速创建一份相同的新问卷')</script>";
				$qn_name = $qn ['qn_name'];
				$html = $this->getQnHtml ( $qn_id );
				$this->assign ( 'qn_id', $qn_id );
				$this->assign ( 'qn_name', $qn_name );
				$this->assign ( 'html', $html );
				$this->display ( 'quickCreate' );
			} else { // "未发布"
				if ($qn_state == "发布中") {
					echo "<script>alert('问卷处于发布状态但目前无人回答，问卷状态将调整为未发布并转到修改页面')</script>";
					$data ['qn_start'] = null;
					$data ['qn_state'] = "未发布";
					$result = $Qn->where ( $condition )->save ( $data );
				}
				$qn_name = $Qn->where ( $condition )->find ()['qn_name'];
				$html = $this->getQnHtml ( $qn_id );
				$this->assign ( 'qn_id', $qn_id );
				$this->assign ( 'qn_name', $qn_name );
				$this->assign ( 'html', $html );
				$this->assign ( 'qn_create', $qn ['qn_create'] );
				$this->assign ( 'title', '修改问卷' );
				$this->display ();
			}
		} else {
			$this->redirect ( 'login' );
		}
	}
	public function handleQnModify() {
		$json = $_POST ['json'];
		$flag = 0;
		if (! empty ( $json )) {
			
			$qn = json_decode ( $json );
			
			$Qn = M ( 'qn' );
			$QnRecord = M ( 'qn_record' );
			$Qs = M ( 'qs' );
			$Ans = M ( 'ans' );
			$Choice = M ( 'choice' );
			$ChoiceRecord = M ( 'choice_record' );
			
			// 通过Qn(qn_id)找Qs，然后找choice ，删choicerecord->choice->ans->qs->qnrecord->qn
			// select qs
			$qn_id = $_POST ['qn_id'];
			$qn_condition ['qn_id'] = $qn_id; // condition of qn_id
			$qs_list = $Qs->where ( $qn_condition )->select (); // qs list
			
			for($i = 0; $i < count ( $qs_list ); $i ++) { // 对于每个qs
				$qs_id = $qs_list [$i] ['qs_id'];
				
				// select choice
				$qs_condition ['qs_id'] = $qs_id; // condition of qs_id
				$choice_list = $Choice->where ( $qs_condition )->select ();
				
				// for each choice, first delete the record, then delete the choice
				for($j = 0; $j < count ( $choice_list ); $j ++) {
					$choice_id = $choice_list [$j] ['choice_id'];
					$choice_condition ['choice_id'] = $choice_id; // condition of choice_id
					
					/* delete the choice */
					if ($Choice->where ( $choice_condition )->count () > 0) {
						if (! ($Choice->where ( $choice_condition )->delete ())) {
							$this->error ( "删除choice " . $choice_id . "失败" );
						}
					}
				}
				
				/* delete qs */
				if ($Qs->where ( $qs_condition )->count () > 0) {
					if (! ($Qs->where ( $qs_condition )->delete ())) {
						$this->error ( "删除qs " . $qs_id . "失败" );
					}
				}
			}
			
			// delete qn
			if (! ($Qn->where ( $qn_condition )->delete ())) {
				$this->error ( "删除qn " . $qn_id . "失败" );
			}
			
			$Qn->qn_id = $qn_id;
			$Qn->qn_name = $qn [0];
			$Qn->qn_create = $_POST ['qn_create'];
			$Qn->qn_last_modify = date ( 'Y-m-d' );
			$Qn->qn_state = "未发布";
			
			$qnId = $Qn->add ();
			if (! empty ( $qnId )) {
				for($i = 0; $i < count ( $qn [1] ); $i ++) {
					
					$Qs->qn_id = $qnId;
					$Qs->qs_content = $qn [1] [$i] [0];
					$Qs->qs_style = $qn [1] [$i] [2];
					$Qs->qs_needans = $qn [1] [$i] [1];
					
					$qsId = $Qs->add ();
					
					if (! empty ( $qsId ) && $qn [1] [$i] [2] != "开放") {
						for($j = 0; $j < count ( $qn [1] [$i] [3] ); $j ++) {
							
							$Choice->qs_id = $qsId;
							$Choice->choice_content = $qn [1] [$i] [3] [$j];
							if ($choiceId = $Choice->add ()) {
								// ok
							}
						}
					}
				}
			}
		}
		$this->ajaxReturn ( "修改成功" );
	}
	
	/**
	 * 快速创建相同问卷
	 *
	 * @param unknown $qn_id        	
	 */
	public function quickCreate() {
		if (session ( '?name' )) {
			$qn_id = $_GET ['qn_id'];
			$Qn = M ( 'qn' );
			$condition ['qn_id'] = $qn_id;
			$qn_name = $Qn->where ( $condition )->find ()['qn_name'];
			$html = $this->getQnHtml ( $qn_id );
			$this->assign ( 'qn_id', $qn_id );
			$this->assign ( 'qn_name', $qn_name );
			$this->assign ( 'html', $html );
			$this->assign ( 'title', '快速创建问卷' );
			$this->display ();
		} else {
			$this->redirect ( 'login' );
		}
	}
	public function getQnHtml($qn_id) {
		$Qs = M ( 'qs' );
		$Choice = M ( 'choice' );
		$html = "";
		
		$qs_condition ['qn_id'] = $qn_id;
		$qs_list = $Qs->order ( 'qs_id ASC' )->where ( $qs_condition )->select ();
		
		for($i = 0; $i < count ( $qs_list ); $i ++) {
			$qs_id = $qs_list [$i] ['qs_id'];
			$qs_style = $qs_list [$i] ['qs_style'];
			$html = $html . '<div name="qs" class="card card-alt"><div class="card-main"><div class="card-inner"><div class="form-group form-group-label form-group-alt"><label class="floating-label"><span name="qs_num">1</span>此处输入题目内容</label> <input class="form-control" name="qs_content" type="text" value="' . $qs_list [$i] ['qs_content'] . '"></div><table><tr><td><input type="checkbox" checked="' . ($qs_list [$i] ['qs_needans'] == '1' ? "checked" : "") . '" name="ifneed" /></td><td>必答题</td></tr></table><input type="hidden" name="qs_style" value="' . $qs_style . '" /><p>';
			if ($qs_style != "开放") {
				$html = $html . '<table border="0" width="100%">';
				
				$choice_condition ['qs_id'] = $qs_id;
				$choice_list = $Choice->order ( 'choice_id ASC' )->where ( $choice_condition )->select ();
				
				for($j = 0; $j < count ( $choice_list ); $j ++) {
					$html = $html . '<tr><td><input type="' . ($qs_style == "单选" ? 'radio' : 'checkbox') . '" /></td><td><div class="col-lg-16 col-md-24 col-sm-32"><div class="form-group form-group-label form-group-alt"><label class="floating-label">此处输入选项内容</label><input class="form-control" name="choice_content" type="text" value="' . $choice_list [$j] ['choice_content'] . '"></div></div></td><td><a onclick="'.(($qs_style=='单选')?'addRadioChoice(this)':'addCheckboxChoice(this)').'"><span class="icon icon-add"></span></a></td><td><a onclick="deleteChoice(this)"><span class="icon icon-delete"></span></a></td></tr>';
				}
				$html = $html . '</table>';
			}
			$html = $html . '</div><div class="card-action"><ul class="nav nav-list pull-left"><li><a onclick="addRadioQs(this)"><span class="icon icon-add"></span>&nbsp;单选题</a></li><li><a onclick="addCheckboxQs(this)"><span class="icon icon-add"></span>&nbsp;多选题</a></li><li><a onclick="addOpenQs(this)"><span class="icon icon-add"></span>&nbsp;开放题</a></li><li><a onclick="deleteQs(this)"><span class="icon icon-delete"></span></a></li></ul></div></div></div>';
		}
		
		return $html;
	}
	
	/**
	 * 显示关于页面
	 */
	public function about() {
		if (session ( '?name' )) {
			$this->assign ( 'title', '关于' );
			$this->display ();
		} else {
			$this->redirect ( 'login' );
		}
	}
	
	/**
	 * 退出功能
	 */
	public function logout() {
		// 结束session
		// 返回登录页面
		session ( null );
		$this->redirect ( 'login' );
	}
	
	/**
	 * 根据qn_id获取相关Qs数组(包括choice)
	 *
	 * @param unknown $qn_id        	
	 */
	public function getQsContent($qn_id) {
		// 从数据库获取题目
		$Qs = M ( 'Qs' );
		$condition ['qn_id'] = $qn_id;
		$qs_array = $Qs->where ( $condition )->select ();
		$count = count ( $qs_array );
		// 获取题目相关choice
		for($i = 0; $i < $count; $i ++) {
			// 单选多选的输出
			if ($qs_array [$i] ['qs_style'] != "开放") {
				$qs_content = $qs_content . ($i + 1) . ". " . $qs_array [$i] ['qs_content'] . '(' . ($qs_array [$i] ['qs_needans'] == 0 ? '非必答题' : '必答题') . ',已有' . $qs_array [$i] ['qs_count'] . '人回答' . ')<br/>' . $this->getChoiceContent ( $qs_array [$i] ['qs_id'], $qs_array [$i] ['qs_style'] );
			} else {
				// 开放题的输出
				$qs_content = $qs_content . ($i + 1) . ". " . $qs_array [$i] ['qs_content'] . '(' . ($qs_array [$i] ['qs_needans'] == 0 ? '非必答题' : '必答题') . ',已有' . $qs_array [$i] ['qs_count'] . '人回答' . ')<br/>' . $this->getAnsContent ( $qs_array [$i] ['qs_id'], $qs_array [$i] ['qs_style'] );
			}
		}
		return $qs_content;
		// 返回字符串
	}
	
	/**
	 * 根据qs_id获取相关的Choice组
	 *
	 * @param unknown $qs_id        	
	 */
	public function getChoiceContent($qs_id, $qs_style) {
		// 从数据库获取数据
		$Choice = M ( 'Choice' );
		$condition ['qs_id'] = $qs_id;
		$choice_array = $Choice->where ( $condition )->select ();
		$count = count ( $choice_array );
		
		for($i = 0; $i < $count; $i ++) {
			if ($qs_style == "单选") {
				$choice_content = $choice_content . "<input type='radio'/>" . $choice_array [$i] ['choice_content'] . "(" . $choice_array [$i] ['choice_percentage'] . "%)<br/>";
			} else {
				
				$choice_content = $choice_content . "<input type='checkbox'/>" . $choice_array [$i] ['choice_content'] . "(" . $choice_array [$i] ['choice_percentage'] . "%)<br/>";
			}
		}
		return $choice_content;
	}
	
	/**
	 * 获取开放题结果
	 *
	 * @param unknown $qs_id        	
	 * @return string
	 */
	public function getAnsContent($qs_id) {
		$Ans = M ( 'Ans' );
		$condition ['qs_id'] = $qs_id;
		$ans_array = $Ans->where ( $condition )->select ();
		$count = count ( $ans_array );
		for($i = 0; $i < $count; $i ++) {
			$ans_content = $ans_content . "[" . ($i + 1) . "]  " . $ans_array [$i] ['ans_content'] . "<br/>";
		}
		return $ans_content;
	}
	
	/**
	 * 发布问卷
	 *
	 * @param unknown $qn_id        	
	 */
	public function outputQn() {
		$Qn = M ( 'qn' );
		$condition ['qn_id'] = $_GET ['qn_id'];
		$data ['qn_state'] = "发布中";
		$data ['qn_start'] = date ( "Y-m-d" );
		$data ['qn_end'] = null;
		$result = $Qn->where ( $condition )->save ( $data );
		if ($result !== false) {
			$this->redirect ( 'qnlist' );
		} else {
			$this->error ( "发布失败" );
		}
	}
	
	/**
	 * 结束问卷
	 *
	 * @param unknown $qn_id        	
	 */
	public function closeQn() {
		$Qn = M ( 'qn' );
		$condition ['qn_id'] = $_GET ['qn_id'];
		if ($Qn->where ( $condition )->find ()['qn_state'] == "发布中") {
			$data ['qn_state'] = "已结束";
			$data ['qn_end'] = date ( "Y-m-d" );
			$result = $Qn->where ( $condition )->save ( $data );
			if ($result !== false) {
				$this->redirect ( 'qnlist' );
			} else {
				$this->error ( "结束失败" );
			}
		} else {
			$this->redirect ( 'qnlist' );
		}
	}
	
	/**
	 * 删除一个问卷
	 * 先根据qn_id查找相关问卷题目，再根据每一个qs_id查找相关选项，先删选项记录，然后删选项，然后删ans，然后删qs，然后删qn_record,然后删qn
	 */
	public function deleteQn() {
		$Qn = M ( 'qn' );
		$QnRecord = M ( 'qn_record' );
		$Qs = M ( 'qs' );
		$Ans = M ( 'ans' );
		$Choice = M ( 'choice' );
		$ChoiceRecord = M ( 'choice_record' );
		
		// 通过Qn(qn_id)找Qs，然后找choice ，删choicerecord->choice->ans->qs->qnrecord->qn
		// select qs
		$qn_id = $_GET ['qn_id'];
		$qn_condition ['qn_id'] = $qn_id; // condition of qn_id
		$qs_list = $Qs->where ( $qn_condition )->select (); // qs list
		
		for($i = 0; $i < count ( $qs_list ); $i ++) { // 对于每个qs
			$qs_id = $qs_list [$i] ['qs_id'];
			
			// select choice
			$qs_condition ['qs_id'] = $qs_id; // condition of qs_id
			$choice_list = $Choice->where ( $qs_condition )->select ();
			
			// for each choice, first delete the record, then delete the choice
			for($j = 0; $j < count ( $choice_list ); $j ++) {
				$choice_id = $choice_list [$j] ['choice_id'];
				$choice_condition ['choice_id'] = $choice_id; // condition of choice_id
				
				/* delete the choice record */
				if ($ChoiceRecord->where ( $choice_condition )->count () > 0) {
					if (! ($ChoiceRecord->where ( $choice_condition )->delete ())) {
						$this->error ( "删除choice_record of choice " . $choice_id . "失败" );
					}
				}
				
				/* delete the choice */
				if ($Choice->where ( $choice_condition )->count () > 0) {
					if (! ($Choice->where ( $choice_condition )->delete ())) {
						$this->error ( "删除choice " . $choice_id . "失败" );
					}
				}
			}
			
			/* delete ans record */
			if ($Ans->where ( $qs_condition )->count () > 0) {
				if (! ($Ans->where ( $qs_condition )->delete ())) {
					$this->error ( "删除ans of qs " . $qs_id . "失败" );
				}
			}
			
			/* delete qs */
			if ($Qs->where ( $qs_condition )->count () > 0) {
				if (! ($Qs->where ( $qs_condition )->delete ())) {
					$this->error ( "删除qs " . $qs_id . "失败" );
				}
			}
		}
		// delete qn record
		if ($QnRecord->where ( $qn_condition )->count () > 0) {
			if (! ($QnRecord->where ( $qn_condition )->delete ())) {
				$this->error ( "删除qn _reocord of qn " . $qn_id . "失败" );
			}
		}
		
		// delete qn
		if (! ($Qn->where ( $qn_condition )->delete ())) {
			$this->error ( "删除qn " . $qn_id . "失败" );
		}
		
		$this->redirect ( 'qnlist' );
	}
}