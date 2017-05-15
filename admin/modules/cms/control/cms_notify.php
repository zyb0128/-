<?php 
/**
 * 
 */

defined('In33hao') or exit('Access Invalid!');
class cms_notifyControl extends SystemControl{
	public function __construct(){
		parent::__construct();
		Language::read('cms');
	}

	public function indexOp(){
		$this -> notifyListOp();
	}

	public function notifyListOp(){




		Tpl::setDirquna('cms');
		Tpl::showpage("cms_notify.list");		
	}

	/**
	 * [cms_notify_addOp ]
	 * @return [type] [description]
	 */
	public function cms_notify_addOp(){
		
		Tpl::setDirquna('cms');
		Tpl::showpage("cms_notify.add");
	}


	public function cms_notify_sevaOp(){
		$title = $_POST['notify_title'];
		$body = $_POST['g_body'];
		$newid = $_POST['notify_id'];

		$model_notify = Model('cms_notify');

		$add_data = array();
		$add_data['title'] = $title;
		$add_data['content'] = htmlspecialchars_decode($_POST['g_body'], ENT_QUOTES);
		if (empty($newid)) {
			//
			$add_data['addtime'] = time();
			$add = $model_notify -> addNotify($add_data);

			if ($add) {
				showMessage('OK','');
			}else{
				showMessage('NO','','error');
			}

		}else{
			//编辑
		}
	}



	public function cms_notify_xmlOp(){
		$model_notify = Model('cms_notify');
		$page = intval($_POST['rp']);
		if ($page < 1) {
			$page = 15;
		}
		
		$condition = array();
        		$list = $model_notify->listNotify($condition, $page);

        		$out_list = array();
        		$fields_array = array('special_title','special_type_text','special_image','special_state');
		foreach ($list as $k => $v){
			$out_array = getFlexigridArray(array(),$fields_array,$v);
			$out_array['special_image'] = '<a href="javascript:;" class="pic-thumb-tip" onmouseout="toolTip()" onmouseover="toolTip(\'<img src='.
			($v['special_image'] ? getCMSSpecialImageUrl($v['special_image']) : ADMIN_TEMPLATES_URL . '/images/preview.png').
			'>\')"><i class="fa fa-picture-o"></i></a>';
			$out_array['special_state'] = $special_state_list[$v['special_state']];
			$operation = '';
			$operation .= '<a href="javascript:;" class="btn red" onclick="fg_operation_del('.$v['special_id'].');"><i class="fa fa-trash-o">删除</i></a>';
			$operation .= '<a href="javascript:;" class="btn red" onclick="fg_operation_edit('.$v['special_id'].');"><i class="fa fa-trash-o">编辑</i></a>';

			$operation .= '<span class="btn"><em><i class="fa fa-cog"></i>设置<i class="arrow"></i></em><ul>';
			if($v['special_state'] == '2') {
				$operation .= '<li><a href="'.$v['special_link'].'" target="_blank">查看专题页面</a></li>';
			} else {
				$operation .= '<li><a href="index.php?act=cms_special&op=cms_special_detail&special_id='.$v['special_id'].'" target="_blank">预览专题页面</a></li>';
			}
			$operation .= '<li><a href="index.php?act=cms_special&op=cms_special_edit&special_id='.$v['special_id'].'">编辑专题内容</a></li>';
			$operation .= '</ul></span>';
			$out_array['operation'] = $operation;
			$out_list[$v['special_id']] = $out_array;
		}

        		$data = array();
		$data['now_page'] = $model_notify->shownowpage();
		$data['total_num'] = $model_notify->gettotalnum();
		$data['list'] = $out_list;
		echo Tpl::flexigridXML($data);exit();

	}
}