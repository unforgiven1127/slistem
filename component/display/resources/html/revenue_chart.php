<table class="revenue_table">
	<tr>
		<th colspan="7"><?php echo ucfirst($location); ?> - Individual Revenue Leaders <?php echo $year; ?></th>
	</tr>
	<tr>
		<th>Rank</th>
		<th>Name</th>
		<th></th>
		<th>Signed</th>
		<th>Paid</th>
		<th>Team</th>
		<th>Placed</th>
	</tr>

	<?php
		foreach ($revenue_data as $key => $value):

			if ($key == 'former' && empty($value['signed']))
				continue;

			if ($row_number_rank % 2 === 0)
				$even = ' even_row';
			else
				$even = '';

			if (empty($value['nationality']))
				$flag_pic = 'world_32.png';
			else
				$flag_pic = $value['nationality'].'_32.png';
	?>

	<tr class="hover_row<?php echo $even; ?>">
		<td class="text_right"><?php echo $row_number_rank; ?></td>
		<td class="text_center"><?php echo $value['name']; ?></td>
		<td class="text_center"><?php echo $display_object->getPicture('/common/pictures/flags/'.$flag_pic); ?></td>
		<td class="text_right">&yen;<?php echo number_format($value['signed'], $decimals, '.', ','); ?></td>
		<td class="text_right">&yen;<?php echo number_format($value['paid'], $decimals, '.', ','); ?></td>
		<td class="text_center"><?php echo $value['team']; ?></td>
		<td class="text_right"><?php echo $value['placed']; ?></td>
	</tr>

	<?php
		$row_number_rank += 1;

		$total_paid += $value['paid'];
		$total_signed += $value['signed'];
		$total_placed += $value['placed'];

		endforeach;
	?>

	<tr class="revenue_table_footer">
		<td class="text_center" colspan="3">Total</td>
		<td class="text_right">&yen;<?php echo number_format($total_signed, $decimals, '.', ','); ?></td>
		<td class="text_right">&yen;<?php echo number_format($total_paid, $decimals, '.', ','); ?></td>
		<td></td>
		<td class="text_right"><?php echo $total_placed; ?></td>
	</tr>
</table>

<script>
	var url = '<?php echo $url; ?>';
	var swap_time = <?php echo $swap_time; ?>;

	$('.scrollingContainer').css('overflow', 'auto');
	setTimeout(function() {
		window.location.replace(url);
	}, (swap_time));
</script>