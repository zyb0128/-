<?php 

defined('In33hao') or exit('Access Invalid!');
class store_sb_logModel extends Model {
	public function __construct() {
		parent::__construct('store_sb_log');
	}


	/**
	 * 水币列表
	 * @param array $condition
	 * @param string $field
	 * @param string $order
	 * @param number $page
	 * @param string $limit
	 * @return array
	 */
	public function getSbLogList($condition, $field = '*', $page = 0, $order = 'log_add_time desc', $limit = '') {
		return $this->where($condition)->field($field)->order($order)->page($page)->limit($limit)->select();
	}
}