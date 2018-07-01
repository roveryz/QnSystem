<?php

namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller {
	public function index() {
		$this->redirect ( 'qnlist' );
	}
	/**
	 * 传入qn_id和qn_name,获取qs并输出
	 *
	 * @param unknown $qn_id        	
	 * @param unknown $qn_name        	
	 */
	public function qn() {
		// echo var_dump ( json_decode ( '[34,[["72","开放","12121"],["73","开放",""],["74","单选","93"],["75","单选"],["76","多选","97","98"],["77","多选"]]]' ) );
		$qn_id = $_GET ['id'];
		$Qn = M ( 'qn' );
		$condition ['qn_id'] = $qn_id;
		$qn = $Qn->where ( $condition )->find ();
		$html = $this->getQnHtml ( $qn_id );
		$this->assign ( 'qn_id', $qn_id );
		$this->assign ( 'qn_name', $qn ['qn_name'] );
		$this->assign ( 'html', $html );
		$this->display ();
	}
	public function getQnHtml($qn_id) {
		$Qs = M ( 'qs' );
		$condition ['qn_id'] = $qn_id;
		$qs_list = $Qs->order ( 'qs_id ASC' )->where ( $condition )->select ();
		$html = "";
		
		for($i = 0; $i < count ( $qs_list ); $i ++) {
			$qs_style = $qs_list [$i] ['qs_style'];
			$html = $html . "<div name='qs' class='card card-alt'><div class='card-main' ><div class='card-inner' name='" . $qs_style . "' id='" . $qs_list [$i] ['qs_needans'] . "'><p class='card-heading text-alt'>" . ($i + 1) . (($qs_list [$i] ['qs_needans'] == '1') ? "<span style='color:red'>*</span>" : "") . ". " . $qs_list [$i] ['qs_content'] . "<hr>"; // qs card title
			$qs_id = $qs_list [$i] ['qs_id'];
			if ($qs_style == '开放') {
				// text area
				$html = $html . "<div class='form-group form-group-label left:15px'><label class='floating-label'>请在此作答</label><textarea class='form-control' id='textarea' name='" . $qs_id . "' rows='5'></textarea></div>";
			} else { // radio and checkbox
				$Choice = M ( 'choice' );
				$qs_id_condition ['qs_id'] = $qs_id;
				$choice_list = $Choice->order ( 'choice_id ASC' )->where ( $qs_id_condition )->select ();
				
				for($j = 0; $j < count ( $choice_list ); $j ++) {
					$choice_id = $choice_list [$j] ['choice_id'];
					$choice_content = $choice_list [$j] ['choice_content'];
					if ($qs_style == "单选") {
						$html = $html . "<input type='radio' name='" . $qs_id . "' value='" . $choice_id . "'/>" . $choice_content . "<br>";
					} else if ($qs_style == "多选") {
						$html = $html . "<input type='checkbox' name='" . $qs_id . "' value='" . $choice_id . "'/>" . $choice_content . "<br>";
					}
				}
			}
			$html = $html . "<p></div></div></div>"; // end qs card
		}
		return $html;
	}
	public function handleQnPost() {
		// qn = array(
		// qn[0] = qn_id
		// qn[1] = qs_array(
		// qn[1][0] = qs1(
		// qn[1][0][1] = qs_id
		// qn[1][0][2] = qs_style
		// qn[1][0][3] = choice_id/choice_id_list(qn[1][0][3]/qn[1][0][4]/...)/ans
		$json = $_POST ['json'];
		
		if (! empty ( $json )) {
			
			$qn = json_decode ( $json );
			
			$Qn = M ( 'qn' );
			$QnRecord = M ( 'qn_record' );
			$Qs = M ( 'qs' );
			$Ans = M ( 'ans' );
			$Choice = M ( 'choice' );
			$ChoiceRecord = M ( 'choice_record' );
			
			// qn
			$qn_condition ['qn_id'] = $qn [0];
			$qn_count = $Qn->where ( $qn_condition )->find ()['qn_count'] + 1;
			$qn_data ['qn_count'] = $qn_count;
			$result = $Qn->where ( $qn_condition )->save ( $qn_data );
			
			// qn_record
			$qn_record_data ['qn_id'] = $qn [0];
			$qn_record_data ['date'] = date ( 'Y-m-d' );
			$qn_record_data ['user_id'] = get_client_ip();
			if (! ($qn_record_id = $QnRecord->add ( $qn_record_data ))) {
				$this->error ( "数据写入错误" );
			}
			
			// qs (qs, choice , choice record, ans)
			$qs_list = $qn [1];
			for($i = 0; $i < count ( $qs_list ); $i ++) {
				// 处理每一个qs
				// 单选和多选会出现没有[2],多选会出现[2][3][4],开放没做的话是''
				$qs_id = $qs_list [$i] [0];
				$qs_style = $qs_list [$i] [1];
				
				$qs_condition ['qs_id'] = $qs_id;
				
				if ($qs_style == "单选") {
					if (isset ( $qs_list [$i] [2] )) {
						$choice_id = $qs_list [$i] [2];
						// refresh qs table
						$qs_data ['qs_count'] = $Qs->where ( $qs_condition )->find ()['qs_count'] + 1;
						$result = $Qs->where ( $qs_condition )->save ( $qs_data );
						// refresh choice table
						$choice_condition ['choice_id'] = $choice_id;
						$choice_data ['choice_count'] = $Choice->where ( $choice_condition )->find ()['choice_count'] + 1;
						$choice_data ['choice_percentage'] = (($choice_data ['choice_count']) / $qs_data ['qs_count']) * 100;
						$result = $Choice->where ( $choice_condition )->save ( $choice_data );
						// 更新其他选项的百分比
						$choice_list = $Choice->where($qs_condition)->select();
						for($k = 0; $k < count($choice_list); $k++){
							$choice_condition ['choice_id'] = $choice_list[$k]['choice_id'];
							$choice_data = null;
							$choice_data ['choice_percentage'] = (($choice_list[$k] ['choice_count']) / $qs_data ['qs_count']) * 100;
							$result = $Choice->where ( $choice_condition )->save ( $choice_data );
						}
						
						// refresh choice_record table
						$choice_record_data ['user_id'] = get_client_ip();
						$choice_record_data ['choice_id'] = $choice_id;
						if (! ($choice_record_id = $ChoiceRecord->add ( $choice_record_data ))) {
							$this->error ( "数据写入错误" );
						}
					}
				} else if ($qs_style == "多选") {
					if (isset ( $qs_list [$i] [2] )) {
						// refresh qs table
						$qs_data ['qs_count'] = $Qs->where ( $qs_condition )->find ()['qs_count'] + 1;
						$result = $Qs->where ( $qs_condition )->save ( $qs_data );
						// refresh choice table
						// 循环
						for($j = 2; $j < count ( $qs_list [$i] ); $j ++) {
							$choice_id = $qs_list [$i] [$j];
							$choice_condition ['choice_id'] = $choice_id;
							$choice_data ['choice_count'] = $Choice->where ( $choice_condition )->find ()['choice_count'] + 1;
							$choice_data ['choice_percentage'] = (($choice_data ['choice_count']) / $qs_data ['qs_count']) * 100;
							$result = $Choice->where ( $choice_condition )->save ( $choice_data );
						
							// 更新其他选项的百分比
							$choice_list = $Choice->where($qs_condition)->select();
							for($k = 0; $k < count($choice_list); $k++){
								$choice_condition ['choice_id'] = $choice_list[$k]['choice_id'];
								$choice_data = null;
								$choice_data ['choice_percentage'] = (($choice_list[$k] ['choice_count']) / $qs_data ['qs_count']) * 100;
								$result = $Choice->where ( $choice_condition )->save ( $choice_data );
							}
							
							
							// refresh choice_record table
							$choice_record_data ['user_id'] = get_client_ip();
							$choice_record_data ['choice_id'] = $choice_id;
							if (! ($choice_record_id = $ChoiceRecord->add ( $choice_record_data ))) {
								$this->error ( "数据写入错误" );
							}
						}
					}
				} 

				else { // ans
					if ($qs_list [$i] [2] != "") {
						$ans = $qs_list [$i] [2];
						// refresh qs table
						$qs_data ['qs_count'] = $Qs->where ( $qs_condition )->find ()['qs_count'] + 1;
						$result = $Qs->where ( $qs_condition )->save ( $qs_data );
						// refresh ans table
						$ans_data ['ans_content'] = $ans;
						$ans_data ['user_id'] = get_client_ip();
						$ans_data ['qs_id'] = $qs_id;
						if (! ($ans_id = $Ans->add ( $ans_data ))) {
							$this->error ( "数据写入错误" );
						}
					}
				}
			}
		}
		$this->ajaxReturn ( "提交成功" );
	}
	public function qnlist() {
		$Qn = M ( 'qn' );
		$condition ['qn_state'] = "发布中";
		$qn_list = $Qn->where ( $condition )->select ();
		$this->assign ( 'qn_list', $qn_list );
		$this->display ();
	}
	public function login() {
	}
}