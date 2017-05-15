<?php

defined('In33hao') or exit('Access Invalid!');

class smcModel extends Model {

	public function __construct() {
		parent::__construct('smc');
	}

	public function addSmc($data){
		return $this->insert($data);
	}

	 /**
	 * 取单条信息
	 * @param unknown $condition
	 */
	public function getSmcInfo($condition) {
		return $this->where($condition)->find();
	}

	/**
	 * 编辑
	 * @param unknown $data
	 * @param unknown $condition
	 */
	public function editSmc($data,$condition) {
		return $this->where($condition)->update($data);
	}
}
