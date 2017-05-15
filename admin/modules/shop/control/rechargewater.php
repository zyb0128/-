<?php 
/**
* 水币充值设置
*/

defined('In33hao') or exit('Access Invalid!');
class rechargewaterControl extends SystemControl{
	
	public function __construct(){
		parent::__construct();
	}

	public function indexOp(){

		Tpl::setDirquna('shop');
		Tpl::showpage('rechargewater.index');
	}


	public function add_cardOp(){
		if (!chksubmit()) {
			Tpl::setDirquna('shop');
			Tpl::showpage('rechargewater.add_card');
			return;
		}
		$admin = $this -> getAdminInfo();

		$arr = array_combine(array_filter($_POST['denomination']),array_filter($_POST['activity_denomination']));

		$data['activityTitle'] = $_POST['title'];
		$data['activityDenomination'] 	= serialize($arr);
		$data['activityStarttime'] = strtotime($_POST['starttime']);
		$data['activityEndtime'] = strtotime($_POST['endtime']);
		$data['activityAddid'] = $admin['id'];
		$data['activityAddtime'] = time();

		$add = Model('rechargecard') -> addRechargewater($data);
		if ($add) {
			$msg = "操作成功";
			showMessage($msg, urlAdminShop('rechargewater', 'index'));
		}else{
			showMessage('参数错误', '', 'html', 'error');
		}
	}

	public function index_xmlOp(){
		$model = Model('rechargecard');
		$condition = ' 1=1 ';
		$list = $model->getRechargeWaterList($condition);

		$data = array();
		$data['now_page'] = $model->shownowpage();
		$data['total_num'] = $model->gettotalnum();

		
		foreach ($list as $key => $water) {
			$i = array();

			 $isUsed = $water['state'] == 1 && $water['member_id'] > 0 && $water['tsused'] > 0;
			$i['operation'] = $isUsed ? '--' : <<<EOB
<a class="btn green confirm-del-on-click" href="javascript:;" data-href="index.php?act=rechargecard&op=del_card&id={$water['activityId']}"><i class="fa fa-trash"></i>删除</a>
EOB;
			$i['title'] = $water['activityTitle'];
			$I['batchflag'] = $water['activityTitle'];

			//拼接活动内容
			// $Denomination = '';
			$den = unserialize($water['activityDenomination']);
			foreach ($den as $key => $adn) {
				$Denomination .= "充值".$key."送".$adn. " "/n/r;
				var_dump($Denomination);
			}
			// $i['denomination'] = $Denomination;
			$i['denomination'] = $water['activityTitle'];



			$data['list'][$water['id']] = $i;
		}
		
		
		// var_dump($data);
		echo Tpl::flexigridXML($data);
        		exit;
	}
}
