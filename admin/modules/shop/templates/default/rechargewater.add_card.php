<?php defined('In33hao') or exit('Access Invalid!');?>
<div class="page">
	<div class="fixed-bar">
		<div class="item-title"><a class="back" href="<?php echo urlAdminShop('rechargewater', 'index'); ?>" title="返回平台充值卡列表"><i class="fa fa-arrow-circle-o-left"></i></a>
			<div class="subject">
				<h3>平台水币充值 - 新增</h3>
				<h5>商城水币充值设置生成及用户充值使用明细</h5>
			</div>
		</div>
	</div>

	<form method="post" enctype="multipart/form-data" name="form_add" id="form_add">
		<input type="hidden" name="form_submit" value="ok" />
		<div class="ncap-form-default">
			<dl class="row">
				<dt class="tit">
					<label><em>*</em>活动名称</label>
				</dt>
				<dd class="opt">
					<input class="input-txt" type="text" name="title" />
					<span class="err"></span>
					<p class="notic"></p>
				</dd>
			</dl>
			<dl class="row">
				<dt class="tit">
					<label><em>*</em>面额(元)</label>
				</dt>
				<dd class="opt">
					<input class="input-txt" type="text" placeholder="面额" name="denomination[]" />
					<input class="input-txt" type="text" placeholder="活动面额" name="activity_denomination[]" />
					<div style="height: 25px"></div>
					<input class="input-txt" type="text" placeholder="面额" name="denomination[]" />
					<input class="input-txt" type="text" placeholder="活动面额" name="activity_denomination[]" />
					<div style="height: 25px"></div>
					<input class="input-txt" type="text" placeholder="面额" name="denomination[]" />
					<input class="input-txt" type="text" placeholder="活动面额" name="activity_denomination[]" />
					<div style="height: 25px"></div>
					<input class="input-txt" type="text" placeholder="面额" name="denomination[]" />
					<input class="input-txt" type="text" placeholder="活动面额" name="activity_denomination[]" />
					<div style="height: 25px"></div>
					<input class="input-txt" type="text" placeholder="面额" name="denomination[]" />
					<input class="input-txt" type="text" placeholder="活动面额" name="activity_denomination[]" />
					<span class="err"></span>
					<p class="notic">请输入面额，面额不可超过1000;活动面额可超过面额</p>
				</dd>
			</dl>
			<dl class="row">
				<dt class="tit">
					<label><em>*</em>活动开始时间</label>
				</dt>
				<dd class="opt">
					<input class="input-txt" type="text" name="starttime" id="starttime" />
					<span class="err"></span>
					<p class="notic"></p>
				</dd>
			</dl>
			<dl class="row">
				<dt class="tit">
					<label><em>*</em>活动结束时间</label>
				</dt>
				<dd class="opt">
					<input class="input-txt" type="text" name="endtime" id="endtime" />
					<span class="err"></span>
					<p class="notic"></p>
				</dd>
			</dl>
			<div class="bot"><a href="JavaScript:void(0);" class="ncap-btn-big ncap-btn-green" id="submitBtn"><span><?php echo $lang['nc_submit'];?></span></a></div>
		</div>
	</form>
</div>

<script type="text/javascript">
	$(function(){
		$("#submitBtn").click(function(){
			$("#form_add").submit();
		});
	});
	$(document).ready(function(){
		$("#starttime").datepicker({dateFormat: 'yy-mm-dd'});
		$("#endtime").datepicker({dateFormat: 'yy-mm-dd'});
	})
</script>