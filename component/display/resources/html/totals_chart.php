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
		<th colspan="14"><?php echo ucfirst($key); ?> totals - <?php echo date('M Y', strtotime($start_date)); ?></th>
	</tr>
	<tr>
		<th class="name_column">Name</th>
		<th>Set</th>
		<th>Met</th>
		<th>Resumes sent</th>
		<th>CCM1 set</th>
		<th>CCM1 done</th>
		<th>CCM2 set</th>
		<th>CCM2 done</th>
		<th>MCCM set</th>
		<th>MCCM done</th>
		<th>New candidates<br>in play</th>
		<th>New positions<br>in play</th>
		<th>Offer</th>
		<th>Placement</th>
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
		<td class="name_column"><?php echo $value['name']; ?></td>
		<td>
			<div class="stat_holder">
			<?php echo $value['set']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['set_meeting_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['met']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['met_meeting_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['resumes_sent']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['resumes_sent_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['ccm1']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['ccm1_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['ccm1_done']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['ccm1_info'] as $stat_info): if (empty($stat_info['ccm_done_candidate'])) continue; ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['ccm_done_candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['ccm_done_candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['ccm2']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['ccm2_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['ccm2_done']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['ccm2_info'] as $stat_info): if (empty($stat_info['ccm_done_candidate'])) continue; ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['ccm_done_candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['ccm_done_candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['mccm']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['mccm_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['mccm_done']; ?>
			</div>
			<div class="stat_candi_info">
			<?php
				foreach ($value['mccm_info'] as $stat_info) {
					if (empty($stat_info['ccm_done_candidate'])) continue;
					foreach ($stat_info['ccm_done_candidate'] as $candidate) {
			?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$candidate); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $candidate; ?></a>
				</div>
			<?php
					}
				}
			?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['new_candidates']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['new_candidate_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['new_positions']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['new_position_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-005', CONST_ACTION_VIEW, CONST_POSITION_TYPE_JD, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_position('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['offers_sent']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['offer_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
		<td>
			<div class="stat_holder">
			<?php echo $value['placed']; ?>
			</div>
			<div class="stat_candi_info">
			<?php foreach ($value['placed_info'] as $stat_info): ?>
				<div>
				<?php $url = $page_obj->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$stat_info['candidate']); ?>
					<a href="javascript: view_candi('<?php echo $url; ?>')"><?php echo $stat_info['candidate']; ?></a>
				</div>
			<?php endforeach ?>
			</div>
		</td>
	</tr>

	<?php $row_number_rank += 1; ?>
	<?php endforeach ?>
	<tr class="totals_table_footer"><td colspan="14">&nbsp;</td></tr>
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

		$('.stat_holder').click(function() {
			var sibling_obj_size = $($(this).siblings().get(0)).children().length;

			if (sibling_obj_size > 0)
				$(this).siblings().toggle();
		});
	});
</script>