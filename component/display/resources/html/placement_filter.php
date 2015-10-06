<form name="placementFilterForm" enctype="multipart/form-data" submitajax="0" action="<?php echo $form_url; ?>"
	method="POST" id="placementFilterFormId">
	<div class="general_form_row">
		<div class="general_form_label">Consultant</div>
		<div class="general_form_column">
			<input id="consultant" type="text" name="loginpk" value="<?php echo $consultant; ?>" />
		</div>
		<div class="general_form_label add_margin_left_30">Position</div>
		<div class="general_form_column">
			<input id="position" type="text" name="cp_jd_key" value="<?php echo $position; ?>" />
		</div>
		<div class="general_form_label add_margin_left_30">Candidate</div>
		<div class="general_form_column">
			<input id="candidate" type="text" name="candidate" value="<?php echo $candidate; ?>" />
		</div>
	</div>
	<div class="general_form_row">
		<div class="general_form_label">Date</div>
		<div class="general_form_column">
			<input id="date_start" type="text" name="date_start" value="<?php echo $start_end_date; ?>" />
		</div>
		<div class="general_form_column add_margin_left_20">
			<select name="date_filter">
			<?php foreach ($date_filter_array as $key => $value): ?>
				<option value="<?php echo $key; ?>" <?php if ($key == $date_filter) echo 'selected'; ?>>
				<?php echo $value; ?>
				</option>
			<?php endforeach ?>
			</select>
		</div>
		<div class="general_form_column float_right">
			<input type="submit" value="Filter">
		</div>
	</div>
</form>

<script>
	var consultant_token = '';
	var position_token = '';
	var candidate_token = '';

	<?php if (!empty($consultant_token)) { ?>
	consultant_token = <?php echo $consultant_token; ?>
	<?php } ?>

	<?php if (!empty($position_token)) { ?>
	position_token = <?php echo $position_token; ?>
	<?php } ?>

	<?php if (!empty($candidate_token)) { ?>
	candidate_token = <?php echo $candidate_token; ?>
	<?php } ?>

	$(function()
	{
		$('#date_start').daterangepicker({
			datepickerOptions:
			{
				changeYear: true,
				numberOfMonths: 2,
			},
			dateFormat: 'yy-mm-dd'
		});

		$('#consultant').tokenInput('<?php echo $user_token_url; ?>',
		{
			noResultsText: "no results found",
			tokenLimit: 1,
			prePopulate: consultant_token
		});

		$('#position').tokenInput('<?php echo $position_token_url; ?>',
		{
			noResultsText: "no results found",
			tokenLimit: 1,
			prePopulate: position_token
		});

		$('#candidate').tokenInput('<?php echo $candidate_token_url; ?>',
		{
			noResultsText: "no results found",
			tokenLimit: 1,
			prePopulate: candidate_token
		});
	});
</script>