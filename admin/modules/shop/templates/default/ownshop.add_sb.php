<?php defined('In33hao') or exit('Access Invalid!');?>

<div class="page">
	<div class="fixed-bar">
		<div class="item-title"><a class="back" href="index.php?act=ownshop&op=list" title="返回列表"><i class="fa fa-arrow-circle-o-left"></i></a>
			<div class="subject">
				<h3>自营店铺 - <?php echo $lang['nc_edit'];?>“水币增加”</h3>
				<h5>商城自营店铺相关设置与管理</h5>
			</div>
		</div>
	</div>
	<!-- 操作说明 -->
	<div class="explanation" id="explanation">
		<div class="title" id="checkZoom"><i class="fa fa-lightbulb-o"></i>
			<h4 title="<?php echo $lang['nc_prompts_title'];?>"><?php echo $lang['nc_prompts'];?></h4>
			<span id="explanationZoom" title="<?php echo $lang['nc_prompts_span'];?>"></span> </div>
		<ul>
			<li>可以为店铺增加初始水币额度</li>
			<li>可以为店铺增加已有水币额度</li>
		</ul>
	</div>

	<form id="store_form" method="post">
		<input type="hidden" name="form_submit" value="ok" />
		<input type="hidden" name="store_id" value="<?php echo $output['storeArray']['store_id']; ?>" />
		<div class="ncap-form-default">
			<dl class="row">
				<dt class="tit">
					<label for="sb_num"><em>*</em>水币值</label>
				</dt>
				<dd class="opt">
					<input type="text" value="" id="sb_num" name="sb_num" class="input-txt" />
					<span class="err"></span>
					<p class="notic">已有水币：<?php echo $output['storeArray']['water_fee']; ?></p>
				</dd>
			</dl>
			<div class="bot">
				<a href="JavaScript:void(0);"  id="submitBtn" class="ncap-btn-big ncap-btn-green"><?php echo $lang['nc_submit'];?></a>
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
$(function(){
	$('#submitBtn').click(function(){
		//短信验证(预留)
		

		//提交数据
		$("#store_form").submit();
	})
})
</script>