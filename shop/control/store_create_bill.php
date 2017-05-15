<?php 
defined('In33hao') or exit('Access Invalid!');
class store_create_billControl extends BaseSellerControl {

	public function __construct() {
		parent::__construct();
	}



	public function indexOp(){
		
		//生成结算
		$this->_create_bill();
	}

	private function _create_bill() {
		$this->_model_store = Model('store');
		$this->_model_store_ext = Model('store_extend');
		$this->_model_bill = Model('bill');
		$this->_model_order = Model('order');
		$this->_model_store_cost = Model('store_cost');
		$this->_model_vr_bill = Model('vr_bill');
		$this->_model_vr_order = Model('vr_order');
	
		//更新订单商品佣金值
		// $this->_order_commis_rate_update();
	
		//实物订单结算
		$this->_real_order();
	
		//虚拟订单结算
		// $this->_vr_order();
	
	}

	/**
	 * 生成上月账单[实物订单]
	 * 考虑到老版本，判断 一下有没有ID为1的店铺，如果没有，则向表中插入一条ID:1的记录。
	 * 从店铺扩展表中得取所有店铺结算周期设置，循环逐个生成每个店铺结算单。
	 * 如果值为0，则还是按月结算流程，如果值大于0，则按 X天周期结算。
	 */
	private function _real_order() {
	
		$store_list = $this->_model_store_ext->getStoreExendList(array('store_id'=>$_SESSION['seller_id']));
		$start_time = $this->_get_start_date($store_list[0]['store_id']);

		if ($start_time !== 0) {
			if ($store_list[0]['bill_cycle'] > 0) {

				$this->_create_bill_cycle_by_day($start_time, $store_list);
			} else {

				$this->_create_bill_cycle_by_month($start_time, $store_list);
			}
		}
	}

	


	/**
	 * 取得结算开始时间
	 * 从order_bill表中取该店铺结算单中最大的ob_end_date作为本次结算开始时间
	 * 如果未找到结算单，则查询该店铺订单表(已经完成状态)和店铺费用表，把里面时间较早的那个作为本次结算开始时间
	 * @param int $store_id
	 */
	private function _get_start_date($store_id) {
		$bill_info = $this->_model_bill->getOrderBillInfo(array('ob_store_id'=>$store_id),'max(ob_end_date) as stime');
		$start_unixtime = 0;
		if ($bill_info['stime']){
			$start_unixtime = $bill_info['stime']+1;
		} else {
			$condition = array();
			$condition['order_state'] = ORDER_STATE_SUCCESS;
			$condition['store_id'] = $store_id;
			$condition['finnshed_time'] = array('gt',0);
			$order_info = $this->_model_order->getOrderInfo($condition,array(),'min(finnshed_time) as stime');
			$condition = array();
			$condition['cost_store_id'] = $store_id;
			$condition['cost_state'] = 0;
			$condition['cost_time'] = array('gt',0);
			$cost_info = $this->_model_store_cost->getStoreCostInfo($condition,'min(cost_time) as stime');
			if ($order_info['stime']) {
				if ($cost_info['stime']) {
					$start_unixtime = $order_info['stime'] < $cost_info['stime'] ? $order_info['stime'] : $cost_info['stime'];
				} else {
					$start_unixtime = $order_info['stime'];
				}
			} else {
				if ($cost_info['stime']) {
					$start_unixtime = $cost_info['stime'];
				}
			}
			if ($start_unixtime) {
				$start_unixtime = strtotime(date('Y-m-d 00:00:00', $start_unixtime));
			}
		}
		return $start_unixtime;
	}


	/**
	 * 结算周期为X天结算
	 * @param unknown $start_time
	 * @param unknown $store_info
	 */
	private function _create_bill_cycle_by_day($start_unixtime,$store_info) {
		$i = $store_info[0]['bill_cycle']-1;

		$start_unixtime = strtotime(date('Y-m-d 00:00:00', $start_unixtime));
		$current_time = strtotime(date('Y-m-d 00:00:00',TIMESTAMP));

		if (($time = strtotime('-'.$i.' day',$start_unixtime)) < $current_time) {





			$first_day_unixtime = strtotime(date('Y-m-d 00:00:00', $start_unixtime));    //开始那天0时unix时间戳
			$last_day_unixtime = strtotime(date('Y-m-d 23:59:59', $time)); //结束那天最后一秒时unix时间戳
			$data = array();
			$data['os_start_date'] = $first_day_unixtime;
			$data['os_end_date'] = $last_day_unixtime;
	
			try {
				$this->_model_order->beginTransaction();
				//生成单个店铺订单出账单
				$data = array();
				$data['ob_store_id'] = $store_info[0]['store_id'];
				$data['ob_start_date'] = $first_day_unixtime;
				$data['ob_end_date'] = $last_day_unixtime;

				$this->_create_real_order_bill($data);
	
				$this->_model_order->commit();
			} catch (Exception $e) {
				$this->log('实物账单:'.$e->getMessage());
				$this->_model_order->rollback();
			}
			$start_unixtime = strtotime(date('Y-m-d 00:00:00', $last_day_unixtime+86400));
		}
	}

	/**
	 * 结算周期为月结
	 * @param unknown $start_time
	 * @param unknown $store_info
	 */
	private function _create_bill_cycle_by_month($start_unixtime,$store_info) {

		$i = 1;
		$start_unixtime = strtotime(date('Y-m-01 00:00:00', $start_unixtime));
		$current_time = strtotime(date('Y-m-01 00:00:01',TIMESTAMP));

		if (($time = strtotime('-1 month',$current_time)) >= $start_unixtime) {
			if (date('Ym',$start_unixtime) == date('Ym',$time)) {
				//如果两个月份相等检查库是里否存在
				$order_statis = Model()->cls()->table('bill_create')->where(array('os_month'=>date('Ym',$start_unixtime),'store_id'=>$store_info[0]['store_id'],'os_type'=>0))->find();
				if ($order_statis) {
					break;
				}
			}
			//该月第一天0时unix时间戳
			$first_day_unixtime = strtotime(date('Y-m-01 00:00:00', $time));
			//该月最后一天最后一秒时unix时间戳
			$last_day_unixtime = strtotime(date('Y-m-01 23:59:59', $time)." +1 month -1 day");
			$os_month = date('Ym',$first_day_unixtime);
			// var_dump($store_info);
			try {
				$this->_model_order->beginTransaction();
				//生成单个店铺月订单出账单
				$data = array();

				$data['ob_store_id'] = $store_info[0]['store_id'];
				$data['ob_start_date'] = $first_day_unixtime;
				$data['ob_end_date'] = $last_day_unixtime;
				// var_dump($data);exit;
				$this->_create_real_order_bill($data);
	
				$data = array();
				$data['os_month'] = $os_month;
				$data['os_type'] = 0;
				$data['store_id'] = $store_info['store_id'];
				Model()->cls()->table('bill_create')->insert($data);
	
				$this->_model_order->commit();
			} catch (Exception $e) {
				$this->log('实物账单:'.$e->getMessage());
				$this->_model_order->rollback();
			}
		}
	}

	/**
	 * 生成单个店铺订单出账单[实物订单]
	 *
	 * @param int $data
	 */
	private function _create_real_order_bill($data){
		$data_bill['ob_start_date'] = $data['ob_start_date'];
		$data_bill['ob_end_date'] = $data['ob_end_date'];
		$data_bill['ob_state'] = 0;
		$data_bill['ob_store_id'] = $data['ob_store_id'];
		
		if (!$this->_model_bill->getOrderBillInfo(array('ob_store_id'=>$data['ob_store_id'],'ob_start_date'=>$data['ob_start_date']))) {
			$insert = $this->_model_bill->addOrderBill($data_bill);
			if (!$insert) {
				throw new Exception('生成账单失败');
			}
			//对已生成空账单进行销量、退单、佣金统计
			$data_bill['ob_id'] = $insert;
			$update = $this->_calc_real_order_bill($data_bill);
			if (!$update){
				throw new Exception('更新账单失败');
			}

			// 发送店铺消息
			$param = array();
			$param['code'] = 'store_bill_affirm';
			$param['store_id'] = $data_bill['ob_store_id'];
			$param['param'] = array(
					'state_time' => date('Y-m-d H:i:s', $data_bill['ob_start_date']),
					'end_time' => date('Y-m-d H:i:s', $data_bill['ob_end_date']),
					'bill_no' => $data_bill['ob_id']
			);
			QueueClient::push('sendStoreMsg', $param);
		}
	}


	/**
	 * 计算某月内，某店铺的销量，退单量，佣金[实物订单]
	 *
	 * @param array $data_bill
	 */
	private function _calc_real_order_bill($data_bill){
	
		$order_condition = array();
		$order_condition['order_state'] = ORDER_STATE_SUCCESS;
		$order_condition['store_id'] = $data_bill['ob_store_id'];
		$order_condition['finnshed_time'] = array('between',"{$data_bill['ob_start_date']},{$data_bill['ob_end_date']}");


		
		$update = array();
	
		//订单金额
		$fields = 'sum(order_amount) as order_amount,sum(rpt_amount) as rpt_amount,sum(shipping_fee) as shipping_amount,min(store_name) as store_name,sum(sb_amount) as sb_amount';
		$order_info =  $this->_model_order->getOrderInfo($order_condition,array(),$fields);
		$update['ob_order_totals'] = floatval($order_info['order_amount']);
	
		//红包
		$update['ob_rpt_amount'] = floatval($order_info['rpt_amount']);

		//水币
		$update['ob_sb_amount'] = floatval($order_info['sb_amount']);
	
		//运费
		$update['ob_shipping_totals'] = floatval($order_info['shipping_amount']);
		//店铺名字
		$store_info = $this->_model_store->getStoreInfoByID($data_bill['ob_store_id']);
		$update['ob_store_name'] = $store_info['store_name'];
	
		//佣金金额
		$order_info =  $this->_model_order->getOrderInfo($order_condition,array(),'count(DISTINCT order_id) as count');
		$order_count = $order_info['count'];
		$commis_rate_totals_array = array();
		//分批计算佣金，最后取总和
		for ($i = 0; $i <= $order_count; $i = $i + 300){
			$order_list = $this->_model_order->getOrderList($order_condition,'','order_id','',"{$i},300");
			$order_id_array = array();
			foreach ($order_list as $order_info) {
				$order_id_array[] = $order_info['order_id'];
			}
			if (!empty($order_id_array)){
				$order_goods_condition = array();
				$order_goods_condition['order_id'] = array('in',$order_id_array);
				$field = 'SUM(ROUND(goods_pay_price*commis_rate/100,2)) as commis_amount';
				$order_goods_info = $this->_model_order->getOrderGoodsInfo($order_goods_condition,$field);
				$commis_rate_totals_array[] = $order_goods_info['commis_amount'];
			}else{
				$commis_rate_totals_array[] = 0;
			}
		}
		// $update['ob_commis_totals'] = floatval(array_sum($commis_rate_totals_array));  
		$update['ob_commis_totals'] = 0; //去掉佣金
	
		//退款总额
		$model_refund = Model('refund_return');
		$refund_condition = array();
		$refund_condition['seller_state'] = 2;
		$refund_condition['store_id'] = $data_bill['ob_store_id'];
		$refund_condition['goods_id'] = array('gt',0);
		$refund_condition['admin_time'] = array(array('egt',$data_bill['ob_start_date']),array('elt',$data_bill['ob_end_date']),'and');
		$refund_info = $model_refund->getRefundReturnInfo($refund_condition,'sum(refund_amount) as refund_amount,sum(rpt_amount) as rpt_amount');
		$update['ob_order_return_totals'] = floatval($refund_info['refund_amount']);
	
		//全部退款时的红包
		$update['ob_rf_rpt_amount'] = floatval($refund_info['rpt_amount']);
	
		//退款佣金
		$refund  =  $model_refund->getRefundReturnInfo($refund_condition,'sum(ROUND(refund_amount*commis_rate/100,2)) as amount');
		if ($refund) {
			$update['ob_commis_return_totals'] = floatval($refund['amount']);
		} else {
			$update['ob_commis_return_totals'] = 0;
		}
	
		//店铺活动费用
		$model_store_cost = Model('store_cost');
		$cost_condition = array();
		$cost_condition['cost_store_id'] = $data_bill['ob_store_id'];
		$cost_condition['cost_state'] = 0;
		$cost_condition['cost_time'] = array(array('egt',$data_bill['ob_start_date']),array('elt',$data_bill['ob_end_date']),'and');
		$cost_info = $model_store_cost->getStoreCostInfo($cost_condition,'sum(cost_price) as cost_amount');
		$update['ob_store_cost_totals'] = floatval($cost_info['cost_amount']);
	
		//已经被取消的预定订单但未退还定金金额
		$model_order_book = Model('order_book');
		$condition = array();
		$condition['book_store_id'] = $data_bill['ob_store_id'];
		$condition['book_cancel_time'] = array('between',"{$data_bill['ob_start_date']},{$data_bill['ob_end_date']}");
		$order_book_info = $model_order_book->getOrderBookInfo($condition,'sum(book_real_pay) as pay_amount');
		$update['ob_order_book_totals'] = floatval($order_book_info['pay_amount']);
	
		//本期应结
		$update['ob_result_totals'] = $update['ob_order_totals'] + $update['ob_rpt_amount'] + $update['ob_order_book_totals'] - $update['ob_order_return_totals'] -
		$update['ob_commis_totals'] + $update['ob_commis_return_totals']- $update['ob_rf_rpt_amount'] - $update['ob_store_cost_totals'] - $update['ob_sb_amount'];
		$update['ob_store_cost_totals'] ;
		$update['ob_create_date'] = TIMESTAMP;
		$update['ob_state'] = 1;
		$update['os_month'] = date('Ym',$data_bill['ob_end_date']+1);
		return $this->_model_bill->editOrderBill($update,array('ob_id'=>$data_bill['ob_id']));
	}




	/**
	 * 记录系统日志
	 *
	 * @param $lang 日志语言包
	 * @param $state 1成功0失败null不出现成功失败提示
	 * @param $admin_name
	 * @param $admin_id
	 */
	protected final function log($lang = '', $state = 1, $admin_name = '', $admin_id = 0) {
		if (!C('sys_log') || !is_string($lang)) return;
		if ($admin_name == ''){
			$admin = unserialize(decrypt(cookie('sys_key'),MD5_KEY));
			$admin_name = $admin['name'];
			$admin_id = $admin['id'];
		}
		$data = array();
		if (is_null($state)){
			$state = null;
		}else{
			$state = $state ? '' : L('nc_fail');
		}
		$data['content']    = $lang.$state;
		$data['admin_name'] = $admin_name;
		$data['createtime'] = TIMESTAMP;
		$data['admin_id']   = $admin_id;
		$data['ip']         = getIp();
		$data['url']        = $_REQUEST['act'].'&'.$_REQUEST['op'];
		return Model('admin_log')->insert($data);
	}



























}