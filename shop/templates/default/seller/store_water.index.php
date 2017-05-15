<?php defined('In33hao') or exit('Access Invalid!');?>
<link rel="stylesheet" type="text/css" href="<?php echo RESOURCE_SITE_URL;?>/js/jquery-ui/themes/ui-lightness/jquery.ui.css"  />
<div class="tabmenu">
	<?php include template('layout/submenu');?>
</div>

<table class="ncsc-default-table">
	<thead>
		<tr>
			<th class="w10">类型</th>
			<th class="w10">金额</th>
			<th class="w10">去向说明</th>
			<th class="w10">结算时间</th>
		</tr>
	</thead>
	<?php if (is_array($output['return_list']) && !empty($output['return_list'])) { ?>
	<tbody>
		<?php foreach ($output['return_list'] as $key => $val) { ?>
		<tr class="bd-line" >
			<td><?php echo $val['log_type'] ?></td>
			<td><?php echo $val['log_fee'] ?></td>
			<td><?php echo $val['log_desc'] ?></td>
			<td><?php echo date('Y-m-d H:i:s',$val['log_add_time']) ?></td>
		</tr>
		<?php } ?>
		<?php } else { ?>
		<tr>
			<td colspan="20" class="norecord"><div class="warning-option"><i class="icon-warning-sign">&nbsp;</i><span><?php echo $lang['no_record'];?></span></div></td>
		</tr>
		<?php } ?>
	</tbody>
	<tfoot>
		<?php if (is_array($output['return_list']) && !empty($output['return_list'])) { ?>
		<tr>
			<td colspan="20"><div class="pagination"><?php echo $output['show_page']; ?></div></td>
		</tr>
		<?php } ?>
	</tfoot>
</table>