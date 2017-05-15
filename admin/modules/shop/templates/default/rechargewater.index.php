<?php defined('In33hao') or exit('Access Invalid!');?>
<div class="page">
	<div class="fixed-bar">
		<div class="item-title">
			<div class="subject">
				<h3>水币充值优惠政策</h3>
				<h5>商城水币充值设置生成及用户充值使用明细</h5>
			</div>
			<ul class="tab-base nc-row">
				<li><a href="JavaScript:void(0);" class="current">列表</a></li>
				<li><a href="<?php echo urlAdminShop('rechargewater', 'log_list'); ?>">明细</a></li>
			</ul>
		</div>
	</div>
	<!-- 操作说明 -->
	<div class="explanation" id="explanation">
		<div class="title" id="checkZoom"><i class="fa fa-lightbulb-o"></i>
			<h4 title="<?php echo $lang['nc_prompts_title'];?>"><?php echo $lang['nc_prompts'];?></h4>
			<span id="explanationZoom" title="<?php echo $lang['nc_prompts_span'];?>"></span> </div>
		<ul>
			<li>平台发布水币充值优惠政策，用户可在会员中心通过选择对应充值金额的形式对其充值水币进行充值。</li>
			<li>在活动期间内的不能被删除。</li>
		</ul>
	</div>
	
	<!-- 数据列表 -->
	<div id="flexigrid"></div>
	

</div>
<script>

$(function() {
		var flexUrl = 'index.php?act=rechargewater&op=index_xml';

		$("#flexigrid").flexigrid({
				url: flexUrl,
				colModel: [
						{display: '操作', name: 'operation', width: 60, sortable: false, align: 'center', className: 'handle-s'},
						{display: '活动名称', name: 'title', width: 250, sortable: false, align: 'left'},
						{display: '批次标识', name: 'batchflag', width: 80, sortable: false, align: 'left'},
						{display: '充值数量(元)', name: 'denomination', width: 80, sortable: 1, align: 'left'},
						{display: '发布管理员', name: 'admin_name', width: 80, sortable: false, align: 'left'},
						{display: '发布时间', name: 'tscreated', width: 128, sortable: 1, align: 'left'},
						{display: '领取人', name: 'member_name', width: 90, sortable: false, align: 'left'},
						{display: '领取时间', name: 'tsused', width: 128, sortable: 1, align: 'left'}
				],
				buttons: [
						{
								display: '<i class="fa fa-plus"></i>新增水币充值活动',
								name: 'add',
								bclass: 'add',
								title: '添加新活动到列表',
								onpress: function() {
										location.href = '<?php echo urlAdminShop('rechargewater', 'add_card'); ?>';
								}
						},
						{
								display: '<i class="fa fa-file-excel-o"></i>导出数据',
								name: 'csv',
								bclass: 'csv',
								title: '将选定行数据导出Excel文件',
								onpress: function() {
										var ids = [];
										$('.trSelected[data-id]').each(function() {
												ids.push($(this).attr('data-id'));
										});
										if (ids.length == 0 && !confirm('您确定要下载本次搜索的全部数据吗？')) {
												return false;
										}
										var qs = $("#flexigrid").flexSimpleSearchQueryString();
										location.href = qs+'&act=rechargecard&op=export_step1&ids=' + ids.join(',');
								}
						},
						{
								display: '<i class="fa fa-trash"></i>批量删除',
								name: 'del',
								bclass: 'del',
								title: '将选定行数据批量删除',
								onpress: function() {
										var ids = [];
										$('.trSelected[data-id]').each(function() {
												ids.push($(this).attr('data-id'));
										});
										if (ids.length < 1 || !confirm('确定删除?')) {
												return false;
										}

										var href = '<?php echo urlAdminShop('rechargecard', 'del_card', array(
												'id' => '__ids__',
										)); ?>'.replace('__ids__', ids.join(','));

										$.getJSON(href, function(d) {
												if (d && d.result) {
														$("#flexigrid").flexReload();
												} else {
														alert(d && d.message || '操作失败！');
												}
										});
								}
						}
				],
				searchitems: [
						{display: '活动名称', name: 'sn', isdefault: true}
				],
				sortname: "id",
				sortorder: "desc",
				title: '水币充值活动列表'
		});

		// 高级搜索提交
		$('#ncsubmit').click(function(){
				$("#flexigrid").flexOptions({url: flexUrl + '&' + $("#formSearch").serialize(),query:'',qtype:''}).flexReload();
		});

		// 高级搜索重置
		$('#ncreset').click(function(){
				$("#flexigrid").flexOptions({url: flexUrl}).flexReload();
				$("#formSearch")[0].reset();
		});

		$("input[data-dp='1']").datepicker({dateFormat: 'yy-mm-dd'});

});

$('a[data-href]').live('click', function() {
		if ($(this).hasClass('confirm-del-on-click') && !confirm('确定删除?')) {
				return false;
		}

		$.getJSON($(this).attr('data-href'), function(d) {
				if (d && d.result) {
						$("#flexigrid").flexReload();
				} else {
						alert(d && d.message || '操作失败！');
				}
		});
});

</script>
