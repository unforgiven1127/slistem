<div class="general_form_row_np h1 add_margin_top_10">
	Saved searches
</div>

<?php if (!empty($saved_searches_list)) { ?>
	<div class="general_form_row_np gray_gradient_background add_margin_top_10 static_width_485 border_bottom"
		style="font-weight: bold;">
		<div class="saved_searches_column border_right static_width_300">
			Label
		</div>
		<div class="saved_searches_column border_right static_width_85">
			Date
		</div>
		<div class="saved_searches_column">
			Action
		</div>
	</div>

	<?php
	$row_number_rank = 1;
	foreach ($saved_searches_list as $value):

		if ($row_number_rank % 2 === 0)
			$even = ' even_gray';
		else
			$even = '';

		$log_link = preg_replace('/\&/', '&replay_search='.$value['activity_id'].'&', $value['link'], 1);
	?>

	<div class="general_form_row_np static_width_485 <?php echo $even; ?>">
		<div class="saved_searches_column border_right static_width_300">
			<a href="<?php echo $log_link; ?>"><?php echo $value['label']; ?></a>
		</div>
		<div class="saved_searches_column border_right static_width_85">
			<?php echo $value['date']; ?>
		</div>
		<?php
		$edit_url = $page_obj->getAjaxUrl($component_id, CONST_ACTION_SAVEEDIT, CONST_TYPE_SAVED_SEARCHES,
			$value['id'], array('action' => 'edit'));
		$edit_action = 'ajaxLayer(\''.$edit_url.'\', 370, 150);';

		$delete_url = $page_obj->getAjaxUrl($component_id, CONST_ACTION_SAVEEDIT, CONST_TYPE_SAVED_SEARCHES,
			$value['id'], array('action' => 'delete'));
		$delete_action = 'if(window.confirm(\'Delete this search?\')){ AjaxRequest(\''.$delete_url.'\'); }';
		?>
		<div class="saved_searches_column picture_link_hover">
			<a onclick="<?php echo $edit_action; ?>" href="javascript:;">
				<img src="<?php echo $edit_picture; ?>" title="Edit" />
			</a>
			&nbsp;
			<a onclick="<?php echo $delete_action; ?>" href="javascript:;">
				<img src="<?php echo $delete_picture; ?>" title="Delete" />
			</a>
		</div>
	</div>
	<?php
		$row_number_rank += 1;
	endforeach
	?>
<?php } else { ?>
	<div class="general_form_row add_margin_top_10" style="font-size: 14px;">
		No saved searches found
	</div>
<?php } ?>