<form action="" method="post">
	<div class="general_form_row" style="font-size: 16px;">
		<div class="general_form_column">Start date: </div>
		<div class="general_form_column">
			<input id="start_date" style="width: 90px" type="text" name="start_date"
				value="<?php echo $start_date_original; ?>" />
		</div>
		<div class="general_form_column add_margin_left_20">End date: </div>
		<div class="general_form_column">
			<input id="end_date" style="width: 90px" type="text" name="end_date"
				value="<?php echo $end_date_original; ?>" />
		</div>
		<div class="general_form_column add_margin_left_10">
			<input type="submit" name="submit_totals" value="Get totals" />
		</div>
	</div>
</form>

<?php foreach ($stats_data as $key => $stat): ?>
<table class="totals_table">
	<tr>
		<th colspan="7"><?php echo ucfirst($key); ?> totals - <?php echo date('M Y', strtotime($start_date)); ?></th>
	</tr>
	<tr>
		<th>Name</th>
		<th>Set</th>
		<th>Met</th>
		<th>Resumes sent</th>
		<th>CCM1</th>
		<th>CCM2</th>
		<th>MCCM</th>
	</tr>

	<?php $row_number_rank = 1; ?>

	<?php foreach ($stat as $key => $value): ?>
	<?php
	if ($row_number_rank % 2 === 0)
		$even = ' even_row';
	else
		$even = '';
	?>

	<tr class="hover_row<?php echo $even; ?>">
		<td><?php echo $value['name']; ?></td>
		<td><?php echo $value['set']; ?></td>
		<td><?php echo $value['met']; ?></td>
		<td><?php echo $value['resumes_sent']; ?></td>
		<td><?php echo $value['ccm1']; ?></td>
		<td><?php echo $value['ccm2']; ?></td>
		<td><?php echo $value['mccm']; ?></td>
	</tr>

	<?php $row_number_rank += 1; ?>
	<?php endforeach ?>
	<tr class="totals_table_footer"><td colspan="7">&nbsp;</td></tr>
</table>

<div class="general_form_row" style="height: 20px;"></div>
<?php endforeach ?>

<script>
	$(function() {
		$("#start_date, #end_date").datepicker({
			showButtonPanel: true,
			changeYear: true,
			dateFormat: 'yy-mm-dd'
		});
	});
</script>