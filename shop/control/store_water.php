<?php 

defined('In33hao') or exit('Access Invalid!');

class store_waterControl extends BaseSellerControl {

	public function __construct() {
		parent::__construct();
	}


	public function indexOp(){
		$list = Model('store_sb_log') -> getSbLogList(array('log_store_id'=>$_SESSION['store_id']),'*',20);
		foreach ($list as $key => $g) {
			$list[$key]['log_type'] = $this -> orderPaymentName($g['log_type']);
		}

		Tpl::output('return_list',$list);
        		Tpl::output('show_page',Model('store_sb_log')->showpage());
		self::profile_menu('water','index');
		Tpl::showpage('store_water.index');
	}








	/**
	 * 取得订单支付类型文字输出形式
	 *
	 * @param array $payment_code
	 * @return string
	 */
	function orderPaymentName($payment_code) {
		return str_replace(array('sb_pay','sb_back','sb_rebate','sb_top'),array('水币支付(客户)','水币退款(客户)','水币返利(商家)','水币充值(平台)'),$payment_code);
	}

	/**
	 * 小导航
	 *
	 * @param string    $menu_type  导航类型
	 * @param string    $menu_key   当前导航的menu_key
	 * @return
	 */
	private function profile_menu($menu_type,$menu_key='') {
		$menu_array = array();
		switch ($menu_type) {
			case 'water':
				$menu_array = array(
					array('menu_key'=>'index','menu_name'=>'水币流水记录',  'menu_url'=>'index.php?act=store_water&op=index')
				);
				break;
		}
		Tpl::output('member_menu',$menu_array);
		Tpl::output('menu_key',$menu_key);
	}
}