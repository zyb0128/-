<?php
/**
 * APP访问接口集合
 */



defined('In33hao') or exit('Access Invalid!');
header("Access-Control-Allow-Origin:*");

class mobileApiControl extends BaseGoodsControl {
	public function __construct() {
		parent::__construct ();
	}

	/**
	 * [postLoginUser 用户登录]
	 * @return [json]         [数据]
	 */
	public function postLoginUserOp(){	
		$model_member   = Model('member');
		$login_info = array();
		$login_info['user_name'] = $_POST['user_mobile'];
		$login_code = $_POST['cms_code'];  //来源提交
		$now = time();

		$Fcode['smc_mobile'] = $login_info['user_name'];
		$Fcode['smc_type'] = '1';
		$code = Model('smc') -> getSmcInfo($Fcode);

		if ($code['smc_munber'] != $login_code) {
			echo json_encode(array('code'=>'2000','msg'=>'验证码错误'));
			exit;
		}

		if ($now - $code['smc_addtime'] > 300) {
			echo json_encode(array('code'=>'2000','msg'=>'验证码过期'));
			exit;
		}

		$member_info = $model_member->login($login_info);
		
		if(isset($member_info['error'])) {
			echo json_encode(array('code'=>'2000','msg'=>$member_info['error']));
			exit;
		}

		$model_member->createSession($member_info, true);
		if (empty($member_info)) {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
		}else{
			//写入token
			$addData['member_id'] = $FindData['member_id'] = $member_info['member_id'];
			$FindToken = Model('mb_user_token') -> getMbUserTokenInfo($FindData);
			if (empty($FindToken)) {
				$addData['token'] = $token = $this -> getAskToken($login_info['user_name'],$login_code);
				$addData['member_name'] = $member_info['member_name'];
				$addData['login_time'] = time();
				$addData['client_type'] = 'APP';
				Model('mb_user_token') -> addMbUserToken($addData);
			}else{
				$upData['token'] = $token = $this -> getAskToken($login_info['user_name'],$login_code);
				$upData['login_time'] = time();
				Model('mb_user_token') -> updateMemberToken($upData,$member_info['member_id']);
			}
			$member_info['token'] = $token;
			$member_info['token_addtime'] = time();
			$member_info['avatar_url'] = getMemberAvatar($member_info['member_avatar']);

			echo json_encode(array('code'=>'1000','msg'=>'有效用户','data'=>$member_info));
		}
	}


	/**
	 * [getUserSeeGoodsList 用户可以看到的产品]
	 * @return [type] [description]
	 */
	public function getUserSeeGoodsListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$from_company = $_GET['fcy']; //所属的子公司
		$StoreOnlineList = array();
		$StoreOnlineList['back_company'] = $from_company;
		$storeList = Model('store') -> getStoreOnlineList($StoreOnlineList);

		$Goods_Find_Info = [];
		foreach ($storeList as $key => $slt) {
			$findGoods['store_id'] = $slt['store_id'];
			$goods = Model('goods') -> getGoodsList($findGoods);
			foreach ($goods as $ky => $gd) {
				$Goods_Find_Info[$ky]['goodsId']= $gd['goods_id'];
				$Goods_Find_Info[$ky]['goodsCommonid']= $gd['goods_commonid'];
				$Goods_Find_Info[$ky]['goodsName']= $gd['goods_name'];
				$Goods_Find_Info[$ky]['storeName']= $gd['store_name'];
				$Goods_Find_Info[$ky]['goodsJingle']= $gd['goods_jingle'];
				$Goods_Find_Info[$ky]['goodsPrice']= $gd['goods_price'];
				$Goods_Find_Info[$ky]['goodsPromotionPrice']= $gd['goods_promotion_price'];
				$Goods_Find_Info[$ky]['goodsMarketprice']= $gd['goods_marketprice'];
				$Goods_Find_Info[$ky]['goodsStorage']= $gd['goods_storage'];
				$Goods_Find_Info[$ky]['goodsImg']= cthumb($gd['goods_image'],60,$gd['store_id']);
			}
		}

		echo json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$Goods_Find_Info));
	}


	/**
	 * [getUserSeeGoodsInfoOp 用户可以看到的商品详情]
	 * @return [type] [description]
	 */
	public function getUserSeeGoodsInfoOp(){
		$goods_id =  intval($_GET['goods_id']);
		$user_id = $_GET['user_id']; //用户ID

		$Verify = $this -> VerifyUser($user_id);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}	
	}


	/**
	 * [getGoodsCart 加入购物车]
	 * @return [type] [description]
	 */
	public function getGoodsCartOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$goodsid = $_GET['goodsId']; //商品ID
		$pay_num = $_GET['payNum']; //购买数量

		$goods = Model('goods') -> getGoodsDetail($goodsid);

		//数据组装
		$add_cart = array();
		$add_cart['goods_id'] = $goodsid;
		$add_cart['buyer_id'] = $Verify['info']['member_id'];
		$add_cart['store_id'] = $goods['goods_info']['store_id'];
		$add_cart['store_name'] = $goods['goods_info']['store_name'];
		$add_cart['goods_name'] = $goods['goods_info']['goods_name'];
		$add_cart['goods_price'] = $goods['goods_info']['goods_price'];
		$add_cart['goods_image'] = $goods['goods_info']['goods_image'];
		$add_cart['bl_id'] = 0;
		$add = Model('cart') -> addCart($add_cart,'db',$pay_num);
		if ($add) {
			echo json_encode(array('code'=>'1000','msg'=>'操作成功'));
		}else{
			echo json_encode(array('code'=>'2000','msg'=>'操作失败'));
		}
	}

	/**
	 * [getGoodsCartListOp 购物车列表]
	 * @return [type] [description]
	 */
	public function getGoodsCartListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$new_list_info = array();

		$find['buyer_id'] = $Verify['info']['member_id'];
		$list = Model('cart') -> listCart('db',$find);
		foreach ($list as $key => $value) {
			$list[$key]['goods_image'] = cthumb($value['goods_image'],60,$value['store_id']);
			$new_list_info[] = $value['store_id'] ;
		}
		$new_list_info = array_unique($new_list_info);
		
		foreach ($new_list_info as $key => $nid) {
			$finddata['store_id'] = $nid;
			$list_info_cart[] = Model('cart') -> listCart('db',$finddata);

		}

		echo json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$list_info_cart));
	}

	/**
	 * [getGoodsCartCountOp 查询购物车数量]
	 * @return [type] [description]
	 */
	public function getGoodsCartCountOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$mun = Model('cart') -> countCartByMemberId($Verify['info']['member_id']);
		exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$mun)));
	}


	/**
	 * [getGoodsCartPlusOp 购物车列表修改商品数量]
	 * @return [type] [description]
	 */
	public function getGoodsCartPlusOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$cart_id    = intval(abs($_GET['cart_id']));
		$quantity   = intval(abs($_GET['quantity']));

		if(empty($cart_id) || empty($quantity)) {
			exit(json_encode(array( 'code'=>'2000','msg'=>'无参数' )));
		}

		$model_cart = Model('cart');
		$model_goods= Model('goods');
		$logic_buy_1 = logic('buy_1');

		//存放返回信息
		$return = array();

		$cart_info = $model_cart->getCartInfo(array('cart_id'=>$cart_id,'buyer_id'=>$Verify['info']['member_id']));

		//普通商品
		$goods_id = intval($cart_info['goods_id']);
		$goods_info = $logic_buy_1->getGoodsOnlineInfo($goods_id,$quantity);
		if(empty($goods_info)) {
			$return['state'] = 'invalid';
			$return['msg'] = '商品已被下架';
			$return['subtotal'] = 0;
			QueueClient::push('delCart', array('buyer_id'=>$Verify['info']['member_id'],'cart_ids'=>array($cart_id)));
			exit(json_encode(array('code'=>'2000','msg'=>$return['msg'])));
		}

		//抢购
		$logic_buy_1->getGroupbuyInfo($goods_info);

		//限时折扣
		$logic_buy_1->getXianshiInfo($goods_info,$quantity);

		$quantity = $goods_info['goods_num'];

		if(intval($goods_info['goods_storage']) < $quantity) {
			$return['state'] = 'shortage';
			$return['msg'] = '库存不足';
			$return['goods_num'] = $goods_info['goods_storage'];
			$return['goods_price'] = $goods_info['goods_price'];
			$return['subtotal'] = $goods_info['goods_price'] * intval($goods_info['goods_storage']);
			$model_cart->editCart(array('goods_num'=>$goods_info['goods_storage']),array('cart_id'=>$cart_id,'buyer_id'=>$Verify['info']['member_id']));
			exit(json_encode(array('code'=>'2000','msg'=>$return['msg'])));
		}

		$data = array();
		$data['goods_num'] = $quantity;
		$data['goods_price'] = $goods_info['goods_price'];
		$update = $model_cart->editCart($data,array('cart_id'=>$cart_id,'buyer_id'=>$Verify['info']['member_id']));
		if ($update) {
			$return = array();
			$return['state'] = 'true';
			$return['subtotal'] = $goods_info['goods_price'] * $quantity;
			$return['goods_price'] = $goods_info['goods_price'];
			$return['goods_num'] = $quantity;
		} else {
			$return = array('msg'=>Language::get('cart_update_buy_fail','UTF-8'));
		}
		exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$return)));
	}


	/**
	 * [getGoodsCartDelOp 删除购物车商品]
	 * @return [type] [description]
	 */
	public function getGoodsCartDelOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$cart_id    = intval(abs($_GET['cart_id']));
		$model_cart = Model('cart');

		$del = $model_cart -> delCart('db',array('cart_id'=>$cart_id));
		if ($del) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功')));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
		}
	}




	/**
	 * [getGoodsCartPayStep1Op 购物车结算] 第一步
	 * @return [type] [description]
	 */
	public function getGoodsCartPayStep1Op(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		//数据组装
		$data = array();
		if ($_GET['from'] == 'cart') {
			$data['ifcart'] = '1';
			$data['ifchain'] = '';
			//购物车信息组装
			$cart_ids = $_GET['PayData'];
			$cart_ids = explode(',',$cart_ids);
			foreach ($cart_ids as $key => $value) {
				$o = Model('cart') -> getCartInfo(array('cart_id'=>$value));
				$cart_id[] = $o['cart_id']."|".$o['goods_num'];

				//重新组合购买商品信息和数量
				$goods = Model('goods') -> getGoodsInfo(array('goods_id'=>$o['goods_id']));
				$goods['goods_image'] = cthumb($goods['goods_image'],60,$goods['store_id']);
				$goods['goods_paynum'] = $o['goods_num'];
				$goods_info[] = $goods;
			}
			$data['cart_id'] = $cart_id;

		}else{
			$data['cart_id'] = array($_GET['goods_id']."|".$_GET['quantity']);
			//重新组合购买商品信息和数量
			$goods = Model('goods') -> getGoodsInfo(array('goods_id'=>$_GET['goods_id']));
			$goods['goods_image'] = cthumb($goods['goods_image'],60,$goods['store_id']);
			$goods['goods_paynum'] = $_GET['quantity'];
			$goods_info[] = $goods;
		}

		$logic_buy = Logic('buy');
		$result = $logic_buy->buyStep1($data['cart_id'], $data['ifcart'], $Verify['info']['member_id'], '');

		$result['data']['cart_list'] = $goods_info;
		if (!$result['state']) {
			exit(json_encode(array('code'=>'2000','msg'=>'结算失败','data'=>$result)));
		} else {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$result['data'],'from'=>$_GET['from'])));
		}
	}

	/**
	 * [postGoodsCartPayStep2Op 购物车结算] 第二步
	 * @return [type] [description]
	 */
	public function postGoodsCartPayStep2Op(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$logic_buy = logic('buy');
		$from = $_POST['from'];
		$pay_name = $_POST['payName']; //支付方式
		$buy_city_id = $_POST['cityId'];
		$address_id = $_POST['addressId'];
		// $payType = $_POST['payType'];	

		$data = array();
		if ($from == 'cart') {
			$data['ifcart'] = '1';
			//购物车信息组装
			$cart_ids = $_POST['PayData'];
			
			// $cart_ids = explode(',',$cart_ids);
			foreach ($cart_ids as $key => $value) {
				$o = Model('cart') -> getCartInfo(array('cart_id'=>$value));
				$cart_id[] = $o['cart_id']."|".$o['goods_num'];
				$goods_id[] = $o['goods_id']."|".$o['goods_num'];
			}
			$data['cart_id'] = $cart_id;
			$data['goods_id'] = $goods_id;
		}else {
			$data['cart_id'] = array($_POST['goods_id']."|".$_POST['quantity']);
			$data['goods_id'] = $_POST['goods_id']."|".$_POST['quantity'];
		}
		$data['pay_name'] = $pay_name;
		$data['address_id'] = $address_id;
		$data['buy_city_id'] = $buy_city_id;
		$data['vat_hash'] = $logic_buy -> buyEncrypt('deny_vat',$Verify['info']['member_id']);
		$data['offpay_hash_batch'] = 'WecUkaIDnwh9ZDdh1eZNdbAEA3bUyet6mlybXbThIqs_Fph8mixeUlUgdNsMcKvwhXcTsHq-2vqCw25Xlv2kQdF' ;
		$data['offpay_hash'] = $logic_buy -> buyEncrypt('deny_offpay',$Verify['info']['member_id']);
		$result = $logic_buy->buyStep2($data, $Verify['info']['member_id'], $Verify['info']['member_name'], $Verify['info']['member_email']);

		if (!$result['state']) {
			exit(json_encode(array('code'=>'2000','msg'=>'结算失败')));
		} else {
			if ($pay_name == 'offline') {
				//货到付款结算
				$model_order = Model('order');
				$update['payment_code'] = $pay_name ;
				$update['order_state'] = 20;
				$UpEidt = $model_order -> editOrder($update,array('pay_sn'=>$result['data']['pay_sn']));
				if ($UpEidt) {
					exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$pay_name)));
				}else{
					exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
				}
			}else{
				$result['data']['userwater'] = $Verify['info']['water_fee'];
				$result['data']['useravailable'] = $Verify['info']['available_predeposit'];
				exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$result['data'])));
			}
		}
	}


	/**
	 * [getGoodsCartPayStep3Op 购物车结算] 第三步
	 * @return [type] [description]
	 */
	public function postGoodsCartPayStep3Op(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$paySn = $_POST['paySn'];    //订单编号
		$payType = $_POST['payType'];	//支付方式
		$paypwd = $_POST['paypwd'];  //用户支付密码

		$logic_buy_1 = Logic('buy_1');
		$model_order = Model('order');
		$listOrd = $model_order -> getOrderList(array('pay_sn'=>$paySn));

		foreach ($listOrd as $key => $ord) {
			if ($ord['order_state'] == '20') {
				exit(json_encode(array('code'=>'2000','msg'=>'该订单已支付过')));
			}
		}
		
		if ($Verify['info']['member_paypwd'] == '' || $Verify['info']['member_paypwd'] != md5($paypwd)) {
			exit(json_encode(array('code'=>'2000','msg'=>'无效的支付密码')));
		}


		if ($payType == 'sbpay') {
			$pay = $logic_buy_1 -> sbPay($listOrd, $post, $Verify['info']);
		}else{
			//余额付款(pd)
			$pay = $logic_buy_1 -> pdPay($listOrd, $post, $Verify['info']);
		}
		
		if ($pay[0]['order_state'] == '20') {
			exit(json_encode(array('code'=>'1000','msg'=>'支付成功')));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'支付失败')));
		}

	}




	/********************************个人中心*******************************************/

	/**
	 * [getUserGoodsOrderListOp 用户已下订单]
	 * @return [type] [description]
	 */
	public function getUserGoodsOrderListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$find_ord = array();
		$find_ord['buyer_id'] = $Verify['info']['member_id'];
		if ($_GET['state'] > 0) {
			$find_ord['order_state'] = $_GET['state'];
		}
		$list = Model('order') -> getOrderList($find_ord,'','',' order_id desc ','',array('order_goods'));
		$list = array_values($list);

		foreach ($list as $key => $value) {
			$img = $value['extend_order_goods'];
			foreach ($img as $ky => $g) {
				$list[$key]['extend_order_goods'][$ky]['goods_image'] = cthumb($g['goods_image'],60,$g['store_id']);
			}
		}

		if (!empty($list)) {
			// $list
			// echo json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>'0'));
			echo json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$list));
		}else{
			$empty = array();
			echo json_encode(array('code'=>'1000','msg'=>'无结果','data'=>$empty));
		}
	}


	/**
	 * [getUserGoodsOrderInfoOp 用户已下订单信息]
	 * @return [type] [description]
	 */
	public function getUserGoodsOrderInfoOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$order_id = $_GET['oid'];
		$logic_order = logic('order');
		$result = $logic_order->getMemberOrderInfo($order_id,$Verify['info']['member_id']);

		$result['data']['order_info']['payment_time']= date('Y-m-d H:i:s',$result['data']['order_info']['payment_time']);
		$result['data']['order_info']['finnshed_time']= date('Y-m-d H:i:s',$result['data']['order_info']['finnshed_time']);
		$result['data']['order_info']['add_time']= date('Y-m-d H:i:s',$result['data']['order_info']['add_time']);
		$result['data']['order_info']['extend_order_common']['shipping_time']= date('Y-m-d H:i:s',$result['data']['order_info']['extend_order_common']['shipping_time']);
		$result['data']['order_info']['daddress_info'] = $result['data']['daddress_info'];

		echo json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$result['data']));
	}

	/**
	 * [getUserPolicyListOp 商家给用户的政策列表]
	 * @return [type] [description]
	 */
	public function getUserPolicyListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$model_member_seller = Model('member_seller');
		$model_privilege = Model('privilege');
		$model_signed = Model('signed');

		$find_list['buyer_id'] = $Verify['info']['member_id'];

		$list = $model_member_seller -> getMemberSellerList($find_list,'*',20);
		foreach ($list as $key => $val) {
			$find_privilege['seller_id'] = $val['seller_id'];
			$find_privilege['privilege_vip_type'] = $val['vip_id'];
			$find_privilege['share'] = 1;
			$list[$key]['privilegelist'] = $model_privilege -> getPrivilegeList($find_privilege);
			foreach ($list[$key]['privilegelist'] as $ky => $g) {
				$find = $model_signed -> findSigned(array('user_id'=>$Verify['info']['member_id'],'seller_id'=>$g['seller_id'],'pid'=>$g['id']));
				$list[$key]['privilegelist'][$ky]['privilege_val'] = unserialize($list[$key]['privilegelist'][$ky]['privilege_val']);
				if (!empty($find)) {
					$list[$key]['privilegelist'][$ky]['play'] = '1';
				}
			}
		}
		exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$list[0]['privilegelist'])));
	}


	/**
	 * [getUserPolicyInfoOp 政策详情]
	 * @return [type] [description]
	 */
	public function getUserPolicyInfoOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$policyID = $_GET['pid'] ;
		$policyInfo = Model('privilege') -> getPrivilegeInfo(array('id'=>$policyID)) ;
		$play = Model('signed') -> findSigned(array('user_id'=>$Verify['info']['member_id'],'pid'=>$policyID,'seller_id'=>$policyInfo['seller_id']));

		//数据格式化
		$policyInfo['privilege_valid_starttime'] = date('Y-m-d H:i:s' , $policyInfo['privilege_valid_starttime']);
		$policyInfo['privilege_valid_endtime'] = date('Y-m-d H:i:s' , $policyInfo['privilege_valid_endtime']);
		$policyInfo['privilege_cerat_time'] = date('Y-m-d H:i:s' , $policyInfo['privilege_cerat_time']);	
		$policyval = unserialize($policyInfo['privilege_val']);

		$datat = array();
		foreach ($policyval as $key => $pal) {
			$datats =array();
			$datats['num'] = $key;
			$datats['per'] = $pal;
			$datat[] = $datats;
		}
		$policyInfo['privilege_arr'] = $datat;

		if (!empty($play)) {
			$policyInfo['play'] = '1';
			$policyInfo['playtime'] = date('Y-m-d H:i:s' , $play['lottime']);
		}
		if (!empty($policyInfo)) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$policyInfo)));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'无效政策')));
		}
	}


	/**
	 * [getUserPolicyGoOp 政策签约]
	 * @return [type] [description]
	 */
	public function getUserPolicyGoOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$seller_id = $_GET['seller_id'];
		$pid = $_GET['pid'];
		$pwd = $_GET['pwd'];
		$now = time();

		$Fcode['smc_mobile'] = $Verify['info']['member_mobile'];
		$Fcode['smc_type'] = '2';
		$code = Model('smc') -> getSmcInfo($Fcode);

		if ($code['smc_munber'] != $pwd) {
			echo json_encode(array('code'=>'2000','msg'=>'验证码错误'));
			exit;
		}

		if ($now - $code['smc_addtime'] > 300) {
			echo json_encode(array('code'=>'2000','msg'=>'验证码过期'));
			exit;
		}

		$model_signed = Model('signed');
		$data['user_id'] = $Verify['info']['member_id'];
		$data['seller_id'] = $seller_id;
		$data['pid'] = $pid;
		$data['lottime'] = time();
		$add = $model_signed -> addSigned($data);

		if ($add) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功')));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
		}
	}


	/**
	 * [getUserSchoolListOp 水来学院]
	 * @return [type] [description]
	 */
	public function getUserSchoolListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$list = Model('cms_notify') -> listNotify(array('cms_type'=>'1'));
		exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$list)));
	}


	/**
	 * [getUserMessageListOp 消息列表]
	 * @return [type] [description]
	 */
	public function getUserMessageListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$list = Model('cms_notify') -> listNotify(array('cms_type'=>'2'));
		exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$list)));
	}	


	/**
	 * [getUserSchoolInfoOp 水来学院信息详情]
	 * @return [type] [description]
	 */
	public function getUserSchoolInfoOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$policyID = $_GET['pid'] ;
		$info = Model('cms_notify') -> findNotify(array('id'=>$policyID));
		if (!empty($info)) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$info)));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'无效政策')));
		}
	}


	/**
	 * [getUserAddressList 我的收货地址列表]
	 * @return [type] [description]
	 */
	public function getUserAddressListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$find_address = array();
		$find_address['member_id'] = $Verify['info']['member_id'];
		$list = Model('address') -> getAddressList($find_address);
		if (!empty($list)) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$list)));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'无地址')));
		}
	}


	/**
	 * [getUserAddressDefault 设置默认地址]
	 * @return [type] [description]
	 */
	public function getUserAddressDefaultOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$addressId = $_GET['addressId'];
		$common['member_id'] = $Verify['info']['member_id'];
		$common['address_id'] = $addressId;
		$updata['is_default'] = '1';

		$dateUp = Model('address') -> editAddress($updata,$common);
		if (!empty($dateUp)) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功')));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
		}
	}


	/**
	 * [getUserAddressAdditionOp 新增收货地址]
	 * @return [type] [description]
	 */
	public function postUserAddressAdditionOp(){ 
		$token = $_POST['token'];
		$mobile = $_POST['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$true_name = $_POST['name'];
		$area_id = $_POST['areaId'];
		$city_id = $_POST['cityId'];
		$area_info = $_POST['province']." ".$_POST['city']." ".$_POST['area'];
		$address = $_POST['address'];
		$mob_phone = $_POST['phone'];

		$addDate = array();
		$addDate['member_id'] = $Verify['info']['member_id'];
		$addDate['true_name'] = $true_name;
		$addDate['area_id'] = $area_id;
		$addDate['city_id'] = $city_id;
		$addDate['area_info'] = $area_info;
		$addDate['address'] = $address;
		$addDate['mob_phone'] = $mob_phone;

		$add = Model('address') -> addAddress($addDate);
		if ($add) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功')));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
		}
	}


	/**
	 * [postUserAddressEditOp 编辑收货地址]
	 * @return [type] [description]
	 */
	public function postUserAddressEditOp(){
		$token = $_POST['token'];
		$mobile = $_POST['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$true_name = $_POST['name'];
		$area_id = $_POST['areaId'];
		$city_id = $_POST['cityId'];
		$area_info = $_POST['province']." ".$_POST['city']." ".$_POST['area'];
		$address = $_POST['address'];
		$mob_phone = $_POST['phone'];

		$addDate = array();
		$addDate['member_id'] = $Verify['info']['member_id'];
		$addDate['true_name'] = $true_name;
		$addDate['area_id'] = $area_id;
		$addDate['city_id'] = $city_id;
		$addDate['area_info'] = $area_info;
		$addDate['address'] = $address;
		$addDate['mob_phone'] = $mob_phone;

		$common['address_id'] = $_POST['aid'];

		$edit = Model('address') -> editAddress($addDate,$common);
		if ($edit) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功')));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
		}
	}


	/**
	 * [getUserAddressDelOp 删除收货地址]
	 * @return [type] [description]
	 */
	public function getUserAddressDelOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$common['address_id'] = $_GET['aid'];

		$del = Model('address') -> delAddress($common);
		if ($del) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功')));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
		}
	}


	/**
	 * [getUserWatercoinActivityListOp 用户水币充值活动]
	 * @return [type] [description]
	 */
	public function getUserWatercoinActivityListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		if ($Verify['info']['from_company'] > 0) {
			$now = time();
			$condition = " `activityStarttime` < '{$now}'  and  `activityEndtime` > '{$now}'  and  `activityAddid` = '{$Verify['info']['from_company']}' ";
			$list = Model('rechargecard') -> getRechargeWaterList($condition);

			$info = array();
			foreach ($list as $key => $reg) {
				$activityDenomination = unserialize($reg['activityDenomination']);
				foreach ($activityDenomination as $ky => $ve) {
					$arr = array();
					$arr['criterion'] = $ky;
					$arr['append'] = $ve;
					$arr['character'] = "冲".$ky."送".$ve;
					$info[] = $arr;
				}
			}

			exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$info)));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'该用户没有获得活动信息')));
		}
		
	}








	/**
	 * [getUserWatercoinLogListOp 用户水币交易记录]
	 * @return [type] [description]
	 */
	public function getUserWatercoinLogListOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$model_predeposit = Model('predeposit');
		$data =[];

		$raglist = [];
		//查询充值记录
		$recharge_log = $model_predeposit -> getPdRechargeSbList(array('pdr_member_id'=>$Verify['info']['member_id'],'pdr_payment_state'=>1),'','*',' pdr_add_time DESC');
		foreach ($recharge_log as $key => $rag) {

			$raglist[$key]['dece'] = $rag['pdr_payment_name'];
			$raglist[$key]['addtime'] = date('Y-m-d H:i:s',$rag['pdr_add_time']);
			$raglist[$key]['time'] = $rag['pdr_add_time'];
			$raglist[$key]['fee'] = '+' .$rag['pdr_amount'];
		}

		$epglist = [];
		//查询消费记录
		$expend_log = $model_predeposit -> getPdLogList(" `lg_member_id` = '{$Verify['info']['member_id']}' and `lg_type` like '%sb%' ",'','*',' lg_add_time DESC ');
		foreach ($expend_log as $key => $epg) {
			$decr = explode('，',$epg['lg_desc']);

			$epglist[$key]['dece'] = '购买商品';
			$epglist[$key]['dece2'] = $decr[1];
			$epglist[$key]['order_sn'] = $decr[2];
			$epglist[$key]['addtime'] = date('Y-m-d H:i:s',$epg['lg_add_time']);
			$epglist[$key]['time'] = $epg['lg_add_time'];
			$fee = $epg['lg_av_amount'];
			if ($fee > 0) {
				$epglist[$key]['fee'] = '+' .$fee;
			}else{
				$epglist[$key]['fee'] = $fee;
			}
			
		}
		$data = array_values(array_merge($raglist,$epglist));


		exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$data)));
	}


	/**
	 * [getUserWatercoinAddOp 用户充值水币]
	 * @return [type] [description]
	 */
	public function getUserWatercoinAddOp(){ 
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$pdr_amount = abs(floatval($_GET['amount']));
		if ($pdr_amount <= 0) {
			exit(json_encode(array('code'=>'2000','msg'=>'无效金额')));
		}
		$model_pdr = Model('predeposit');
		$data = array();
		$data['pdr_sn'] = $pay_sn = $model_pdr->makeSn();
		$data['pdr_member_id'] = $Verify['info']['member_id'];
		$data['pdr_member_name'] = $Verify['info']['member_name'];
		$data['pdr_amount'] = $pdr_amount;
		$data['pdr_amount_fj'] = abs(floatval($_GET['append']));
		$data['pdr_add_time'] = TIMESTAMP;
		$insert = $model_pdr->addPdRechargeSb($data);

		if ($insert) {
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$data)));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
		}
	}


	/**
	 * [getUserOrderAbrogateOp 用户取消订单]
	 * @return [type] [description]
	 */
	public function getUserOrderAbrogateOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}

		$FindOrd['order_sn'] = $_GET['osn'];
		$FindOrd['buyer_id'] = $Verify['info']['member_id'];

		$OrderInfo = Model('order') -> getOrderInfo($FindOrd);
		$model_order = Model('order');

		$msg = '用户取消';
		if ($OrderInfo['order_type'] != 2 && $OrderInfo['payment_code'] != 'offline') {
			//更新订单信息
			$update_order = array('order_state'=>0);
			$cancel_condition['order_id'] = $OrderInfo['order_id'];

			$update = $model_order->editOrder($update_order,$cancel_condition);
			if (!$update) {
				throw new Exception('保存失败');
			}

			//添加订单日志
			$data = array();
			$data['order_id'] = $OrderInfo['order_id'];
			$data['log_role'] = 'buyer';
			$data['log_msg'] = '取消了订单';
			$data['log_user'] = $Verify['info']['member_name'];
			if ($msg) {
				$data['log_msg'] .= ' ( '.$msg.' )';
			}
			$data['log_orderstate'] = 0;
			$model_order->addOrderLog($data);
			exit(json_encode(array('code'=>'1000','msg'=>'操作成功')));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'操作失败')));
		}
		
	}
	


	/**
	 * [getLoginSnsOp 短信验证]
	 * @return [type] [description]
	 */
	public function getLoginSnsOp(){
		$mobile = $_GET['mobile'];
		$code = rand('1000','9999');
		$sms = "您的验证码是：".$code.", 在5分钟内有效。【水来了】";
		$get = $this -> sendMessage($mobile,$sms);
		if ($get) {
			$smc['smc_mobile'] = $mobile;
			$smc['smc_type'] = '1';
			$Find = Model('smc') -> getSmcInfo($smc);
			if (empty($Find)) {
				$smc['smc_munber'] = $code;
				$smc['smc_addtime'] = time();
				Model('smc') -> addSmc($smc);
			}else{
				$smc['smc_munber'] = $code;
				$smc['smc_addtime'] = time();
				Model('smc') -> editSmc($smc,array('smc_mobile'=>$mobile));
			}

			exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$code)));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'发送失败')));
		}
	}

	/**
	 * [getPolicySnsOp 政策短信验证]
	 * @return [type] [description]
	 */
	public function getPolicySnsOp(){
		$mobile = $_GET['mobile']; //用户ID

		$code = rand('1000','9999');
		$sms = "您的政策签约验证码是：".$code.", 在5分钟内有效。【水来了】";
		$get = $this -> sendMessage($mobile,$sms);
		if ($get) {
			$smc['smc_mobile'] = $mobile;
			$smc['smc_type'] = '2';
			$Find = Model('smc') -> getSmcInfo($smc);
			if (empty($Find)) {
				$smc['smc_munber'] = $code;
				$smc['smc_addtime'] = time();
				Model('smc') -> addSmc($smc);
			}else{
				$smc['smc_munber'] = $code;
				$smc['smc_addtime'] = time();
				Model('smc') -> editSmc($smc,array('smc_mobile'=>$mobile));
			}

			exit(json_encode(array('code'=>'1000','msg'=>'操作成功','data'=>$code)));
		}else{
			exit(json_encode(array('code'=>'2000','msg'=>'发送失败')));
		}
	}



	/**
	 * [VerifyUser 用户验证]
	 * @param [type] $mobile [用户手机号码]
	 * @param [type] $token  [description]
	 */
	public function VerifyUser($mobile,$token){
		$find_user['member_mobile'] = $mobile;
		$userInfo = Model('member') -> getMemberInfo($find_user);
		$now = time();

		$result =array();
		if (empty($userInfo)) {
			$result['state'] = '1';
		}else{	
			$FindToken['token'] = $token;
			$FindToken['member_id'] = $userInfo['member_id'];
			$token_info = Model('mb_user_token') -> getMbUserTokenInfo($FindToken);

			if (!empty($token_info)) {
				if ($now - $userInfo['member_login_time'] > 259200) {
					$result['state'] = '1';
				}else{
					if ($now - $token_info['login_time'] > 259200) {
						$result['state'] = '1';
					}else{
						$result['state'] = '2';
						$result['info'] = $userInfo;
					}
				}
			}else{
				$result['state'] = '1';
			}
		}
		return $result;
	}


	public static  function  sendMessage($phone,$code){
		$curlPost ='userid=3112&account=欣佳&password=XINJIAGOU123&mobile='.$phone.'&content='.$code.'&sendTime=&extno=&action=send';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://211.147.242.161:8888/sms.aspx');
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$curlPost);
		$data =trim(curl_exec($ch));
		curl_close($ch);
		if(preg_match('~(.*?)<message>ok<\/message>(.*?)~',$data)){
			return true;
		}else{
			return false ;
		}
	}


	/**
	 * [getAskToken Token生成]
	 * @param  [type] $mobile [手机号]
	 * @param  [type] $code   [短息号码]
	 * @return [type]         [description]
	 */
	public function getAskToken($mobile,$code){
		$now = time();
		$token = $mobile.$code.$now.'zyb';
		$token = hash('md5',$token);
		
		return $token;
	}

	/**
	 * [getVersionOp 移动端版本查询]
	 * @return [type] [description]
	 */
	public function getVersionAndroidOp(){
		$data = json_decode(file_get_contents("VersionAndroid.json"));
		$Android = $data->Android;
		$shu = strnatcmp($Android,$_GET['Android']);
		if ( $shu > 0 ) {
			$msg = $data->AndroidUrl;
			exit(json_encode(array('code'=>'1000','msg'=>'发现新版本','data'=>$msg)));
		} else {
			exit(json_encode(array('code'=>'2000','msg'=>'您已是最新版本')));
		}
		

	}

	/**
	 * [getVersionOp 移动端版本查询]
	 * @return [type] [description]
	 */
	public function getVersionIosOp(){
		$data = json_decode(file_get_contents("VersionIOS.json"));
		$ios = $data->IOS;
		$shu = strnatcmp($ios,$_GET['IOS']);
		if ( $shu > 0 ) {
			$msg = $data->IOSUrl;
			exit(json_encode(array('code'=>'1000','msg'=>'发现新版本','data'=>$msg)));
		} else {
			exit(json_encode(array('code'=>'2000','msg'=>'您已是最新版本')));
		}
	}


	public function getUserInfoOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}else{
			$pay_sn = $_GET['pay_sn'];
			if (!empty($pay_sn)) {
				$order_info = Model('order') -> getOrderList(array('pay_sn'=>$pay_sn));
				$pay_fee = 0;
				$order_sn = array();
				foreach ($order_info as $key => $ord) {
					$pay_fee += $ord['order_amount'];
					$order_sn[] = $ord['order_sn'];
				}
				$Verify['info']['pay_fee'] = $pay_fee;
				$Verify['info']['order_sn'] = $order_sn;
			}

			$Verify['info']['member_passwd'] = '';
			$Verify['info']['member_paypwd'] = '';
			exit(json_encode(array('code'=>'1000','msg'=>'有效用户','data'=>$Verify['info'])));
		}
	}


	/**
	 * [getUserForbackWaterfeeOp 查询用户已返水币金额]
	 * @return [type] [description]
	 */
	public function getUserForbackWaterfeeOp(){
		$token = $_GET['token'];
		$mobile = $_GET['mobile']; //用户ID
		$Verify = $this -> VerifyUser($mobile,$token);
		if ($Verify['state'] == '1') {
			echo json_encode(array('code'=>'2000','msg'=>'无效用户'));
			exit;
		}else{
			//查询已返利金额
			$sb_recharge = Model('predeposit') -> getPdRechargeSbList(array('pdr_member_id'=>$Verify['info']['member_id'],'pdr_payment_code'=>3,'pdr_payment_state'=>1));
			$forback = 0;
			foreach ($sb_recharge as $key => $sre) {
				$forback += $sre['pdr_amount'];
			}
			$forbackWaterfee = (string)$forback;
			exit(json_encode(array('code'=>'1000','msg'=>'有效用户','data'=>$forbackWaterfee)));
		}
	}

}
