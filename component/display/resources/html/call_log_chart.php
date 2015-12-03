<table class="revenue_table">
	<tr>
		<th class="text_center" colspan="7"><?php echo $title; ?></th>
	</tr>
	<tr>
		<th class="text_left">Calling party</th>
		<th class="text_left" colspan="2">Name</th>
		<th class="text_left" >Attempts</th>
		<th class="text_left" >Calls</th>
	</tr>

	<?php
		foreach ($call_log_data as $key => $value):

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
		<td class="text_center"><?php echo $value['calling_party']; ?></td>
		<td class="text_center"><?php echo $value['name']; ?></td>
		<td class="text_center"><?php echo $display_object->getPicture('/common/pictures/flags/'.$flag_pic); ?></td>
		<td class="text_center"><?php echo $value['attempts']; ?></td>
		<td class="text_center"><?php echo $value['calls']; ?></td>
	</tr>

	<?php
		$row_number_rank += 1;

		endforeach;
	?>

	<tr class="revenue_table_footer">
		<td colspan="5">&nbsp;</td>
	</tr>
</table>

<script>
	$('.scrollingContainer').css('overflow', 'auto');
</script>