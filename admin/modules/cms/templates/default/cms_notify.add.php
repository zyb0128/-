<?php defined('In33hao') or exit('Access Invalid!');?>

<div class="page">
	<div class="fixed-bar">
		<div class="item-title"><a class="back" href="index.php?act=cms_notify&op=notifyList" title="返回公告列表"><i class="fa fa-arrow-circle-o-left"></i></a>
			<div class="subject">
				<h3><?php echo $lang['nc_cms_notify_manage'];?> -  新增公告页</h3>
				<h5><?php echo $lang['nc_cms_notify_manage_subhead'];?></h5>
			</div>
		</div>
	</div>
	<form id="add_form" method="post" enctype="multipart/form-data" action="index.php?act=cms_notify&op=cms_notify_seva">
		<input name="notify_id" type="hidden" value="" />
		<div class="ncap-form-default">
			<dl class="row">
				<dt class="tit">
					<label for="special_title"><em>*</em><?php echo $lang['cms_text_title'];?></label>
				</dt>
				<dd class="opt">
					<input id="special_title" name="notify_title" class="input-txt" type="text" value=""/>
					<span class="err"></span>
					<p class="notic"><?php echo $lang['cms_special_title_explain'];?></p>
				</dd>
			</dl>
			 
			<dl class="row">
				<dt class="tit">
					<label for="special_title"><em>*</em>内容</label>
				</dt>
				<dd class="opt">
					<?php showEditor('g_body','','100%','480px','visibility:hidden;',"false",'');?>
				</dd>
			</dl>
			<div class="bot">
				<a nctype="btn_special_insert_goods" href="JavaScript:void(0);" class="ncap-btn-big ncap-btn-green" id="submit">提交</a>
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		$("#submit").click(function(){
			$("#add_form").submit();
		});
	})
</script>
