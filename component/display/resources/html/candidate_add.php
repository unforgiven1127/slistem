<form name="addcandidate" enctype="multipart/form-data" submitAjax="1"
	action="<?php echo $form_url; ?>" class="candiAddForm" ajaxTarget="candi_duplicate"
	method="POST" id="addcandidateId" onBeforeSubmit="" onsubmit="">
	<input type="hidden" name="userfk" value="<?php echo $user_id; ?>" />
	<input id="dup_checked" type="hidden" name="check_duplicate" value="0" />

	<div class="formFieldTitle">
		Add/edit contact details
	</div>
	<?php if ($display_all_tabs) { ?>
	<div class="general_form_row add_margin_top_10">
		<ul class="candidate_form_tabs">
			<li onclick="toggle_tabs(this, 'candi_data');" class="selected">
				<div>Candidate data</div>
			</li>
			<li onclick="toggle_tabs(this, 'candi_contact');">
				<div>Contact details</div>
			</li>
			<li onclick="toggle_tabs(this, 'candi_note');">
				<div>Notes</div>
			</li>
			<li onclick="toggle_tabs(this, 'candi_resume');">
				<div>Resume</div>
			</li>
			<li onclick="toggle_tabs(this, 'candi_duplicate');" class="hidden tab_duplicate">
				<div>Duplicates</div>
			</li>
		</ul>
	</div>
	<?php } ?>
	<div id="candi_container">
		<div id="candi_data" class="add_margin_top_10">
			<div class="general_form_row">
				Candidate details
			</div>
			<div class="gray_section extended_select extended_input">
				<div class="general_form_row">
					<div class="general_form_label">gender</div>
					<div class="general_form_column">
						<select id="sex_id" name="sex" onchange="toggleGenderPic(this);">
							<option value="2">female</option>
							<option value="1" <?php echo (($user_sex == 1)? 'selected':''); ?>>male</option>
						</select>
					</div>
					<div class="general_form_column">
						<span class="woman" href="javascript:;" onclick="toggleGenderPic(false, 1);"
							style="<?php echo (($user_sex != 1)? '':'display: none'); ?>">
							<img src="/common/pictures/slistem/woman_16.png"/>
						</span>
						<span class="man" href="javascript:;" onclick="toggleGenderPic(false, 2);"
							style="<?php echo (($user_sex == 1)? '':'display: none'); ?>">
							<img src="/common/pictures/slistem/man_16.png"/>
						</span>
					</div>
				</div>
				<div class="general_form_row">
					<div class="general_form_label">lastname</div>
					<div class="general_form_column">
						<input <?php echo $readonly_name; ?> type="text" name="lastname" value="<?php echo $lastname; ?>" />
					</div>
					<div class="general_form_label add_margin_left_30">firstname</div>
					<div class="general_form_column">
						<input <?php echo $readonly_name; ?> type="text" name="firstname" value="<?php echo $firstname; ?>" />
					</div>
					<div class="general_form_label add_margin_left_30">
						<a href="javascript:;" onclick="change_date_field('birth_date');">birth</a> /
						<a href="javascript:;" onclick="change_date_field('estimated_age');">age</a>
					</div>
					<div class="general_form_column">
						<input id="birth_date" type="text" name="birth_date" value="<?php echo $birth_date; ?>" />
						<input id="estimated_age" style="display: none;" type="text" name="age" value="<?php echo $estimated_age; ?>" />
					</div>
				</div>
				<div class="general_form_row">
					<div class="general_form_label">language</div>
					<div class="general_form_column">
						<select name="language">
						<?php echo $language; ?>
						</select>
					</div>
					<div class="general_form_label add_margin_left_30">nationality</div>
					<div class="general_form_column">
						<select name="nationality">
						<?php echo $nationality; ?>
						</select>
					</div>
					<div class="general_form_label add_margin_left_30">location</div>
					<div class="general_form_column">
						<select name="location" >
						<?php echo $location; ?>
						</select>
					</div>
				</div>
			</div>
			<div class="general_form_row">
				Occupation
			</div>
			<div class="gray_section">
				<div class="general_form_row extended_input">
					<div class="general_form_label">company</div>
					<div class="general_form_column" style="width: 183px;">
						<input id="company" type="text" name="companypk" value="<?php echo $company; ?>" />
					</div>
					<div class="general_form_column add_margin_left_30" style="width: 278px;">
						<a href="javascript:;"
						onclick="var oConf = goPopup.getConfig(); oConf.height = 600;
						oConf.width = 900;goPopup.setLayerFromAjax(oConf, '<?php echo $add_company_url ?>');">
							+ add a new company
						</a>
					</div>
					<div class="general_form_label add_margin_left_30">title</div>
					<div class="general_form_column">
						<input type="text" name="title" value="<?php echo $title; ?>" />
					</div>
				</div>
				<div class="general_form_row extended_input">
					<div class="general_form_label">occupation</div>
					<div class="general_form_column" style="width: 183px;">
					<?php echo $occupation_tree; ?>
					</div>
					<div class="general_form_label add_margin_left_30">industry</div>
					<div class="general_form_column" style="width: 184px;">
					<?php echo $industry_tree; ?>
					</div>
					<div class="general_form_label add_margin_left_30">department</div>
					<div class="general_form_column">
						<input type="text" name="department" value="<?php echo $department; ?>" />
					</div>
				</div>
				<div class="general_form_row">
					<div class="general_form_label">salary</div>
					<div class="general_form_column">
						<input class="salary_field" type="text" name="salary" value="<?php echo $candidate_salary; ?>" />
						<select id="salary_unit" class="salary_manipulation" name="salary_unit">
							<option value=""></option>
							<option value="K" <?php if ($money_unit == 'K') echo 'selected'; ?>>K</option>
							<option value="M" <?php if ($money_unit == 'M') echo 'selected'; ?>>M</option>
						</select>
						<select id="salary_currency" class="salary_manipulation" name="salary_currency">
						<?php foreach ($currency_list as $currency => $rate) { ?>
							<?php if ($currency == 'jpy') { ?>
							<option value="<?php echo $currency; ?>" selected
								title="<?php echo 'Rate: 1'.$currency.' = '.(1/$rate).'&yen'; ?>">
							<?php } else { ?>
							<option value="<?php echo $currency; ?>" title="<?php echo 'Rate: 1'.$currency.' = '.(1/$rate).'&yen'; ?>">
							<?php } ?>
								<?php echo $currency; ?>
							</option>
						<?php } ?>
						</select>
					</div>
					<div class="general_form_label add_margin_left_30">bonus</div>
					<div class="general_form_column">
						<input class="salary_field" type="text" name="bonus" value="<?php echo $candidate_salary_bonus; ?>" />
						<input id="bonus_unit" class="salary_manipulation_small read_only_field" type="text" name="bonus_unit"
						value="" readonly />
						<input id="bonus_currency" class="salary_manipulation_small read_only_field" type="text" name="bonus_currency"
						value="" readonly />
					</div>
				</div>
				<div class="general_form_row">
					<div class="general_form_label">target sal. from</div>
					<div class="general_form_column">
						<input class="salary_field" type="text" name="target_low" value="<?php echo $target_low; ?>" />
						<input id="target_low_unit" class="salary_manipulation_small read_only_field" type="text" name="target_low_unit"
							value="" readonly />
						<input id="target_low_currency" class="salary_manipulation_small read_only_field" type="text" name="target_low_currency"
							value="" readonly />
					</div>
					<div class="general_form_label add_margin_left_30">to</div>
					<div class="general_form_column">
						<input class="salary_field" type="text" name="target_high" value="<?php echo $target_high; ?>" />
						<input id="target_high_unit" class="salary_manipulation_small read_only_field" type="text" name="target_high_unit"
							value="" readonly />
						<input id="target_high_currency" class="salary_manipulation_small read_only_field" type="text" name="target_high_currency"
							value="" readonly />
					</div>
				</div>
			</div>
			<div class="general_form_row">
				Profile
			</div>
			<div class="gray_section">
				<div class="general_form_row  extended_select">
					<div class="general_form_label">grade</div>
					<div class="general_form_column">
						<select name="grade" >
						<?php echo $grade; ?>
						</select>
					</div>
					<div class="general_form_label add_margin_left_30">status</div>
					<div class="general_form_column">
						<select name="status" onchange="manageFormStatus(this, <?php echo $candidate_id; ?>);">
						<?php echo $status_options; ?>
						</select>
					</div>
					<div class="general_form_label add_margin_left_30">MBA/CPA</div>
					<div class="general_form_column">
						<select name="diploma">
							<option value="">none</option>
							<?php echo $diploma_options; ?>
						</select>
					</div>
				</div>
				<div class="general_form_row">
					<div class="general_form_label">keyword</div>
					<div class="general_form_column extended_input">
						<input type="text" name="keyword" value="<?php echo $keyword; ?>" />
					</div>
					<div class="general_form_label add_margin_left_30">Is client</div>
					<div class="general_form_column">
						<input id="is_client" class="css-checkbox" type="checkbox" name="client"
							<?php if (!empty($is_client)) echo 'checked'; ?> />
						<label for="is_client" class="css-label">&nbsp;</label>
					</div>
				</div>
				<div class="general_form_row add_margin_top_10">
					<div class="spinner_holder skill_field">
						<div class="spinner_label">
							AG
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_ag" value="<?php echo $skill_ag; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							AP
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_ap" value="<?php echo $skill_ap; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							AM
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_am" value="<?php echo $skill_am; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							MP
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_mp" value="<?php echo $skill_mp; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							IN
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_in" value="<?php echo $skill_in; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							EX
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_ex" value="<?php echo $skill_ex; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							FX
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_fx" value="<?php echo $skill_fx; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							CH
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_ch" value="<?php echo $skill_ch; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							ED
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_ed" value="<?php echo $skill_ed; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							PL
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_pl" value="<?php echo $skill_pl; ?>" />
					</div>
					<div class="spinner_holder skill_field add_margin_left_20">
						<div class="spinner_label">
							E
						</div>
						<input class="<?php echo $spinner_class; ?>" type="text" name="skill_e" value="<?php echo $skill_e; ?>" />
					</div>
				</div>
			</div>
			<div class="general_form_row">
				<div style="margin-top: 5px; cursor: pointer;" class="bold italic"
				onclick="$('#additional_candidate_info').fadeToggle(function(){ $(this).closest('.ui-dialog-content').scrollTop(5000); });">
					Additional data ?
				</div>
			</div>
			<div id="additional_candidate_info" class="gray_section hidden">
				<div class="general_form_row">
					Multiple industries ? Speak different languages? Fully and accuratly describing the candidates is a key for Sl[i]stem.
					<br>
					It will improve the search functions and increase the candidate profile quality. Use this section to add alternative /
					secondary information about the candidate.
				</div>
				<div class="general_form_row extended_select extended_input">
					<div class="general_form_label">alt. occupation</div>
					<div class="general_form_column">
						<input id="alt_occupation" type="text" name="alt_occupationpk" value="<?php echo $alt_occupationpk; ?>" />
					</div>
					<div class="general_form_label add_margin_left_30">alt. industry</div>
					<div class="general_form_column">
						<input id="alt_industry" type="text" name="alt_industrypk" value="<?php echo $alt_industrypk; ?>" />
					</div>
					<div class="general_form_label add_margin_left_30">language</div>
					<div class="general_form_column">
						<select id="alt_language" name="alt_language[]" multiple>
						<?php echo $alt_language; ?>
						</select>
					</div>
				</div>
				<?php if ($candidate_sys_status > 0 && $is_admin) { ?>
				<div class="general_form_row" style="color: #077AC1; font-weight: bold;">
					DBA
				</div>
				<div class="general_form_row">
					<div class="general_form_label">Deleted ?</div>
					<div class="general_form_column extended_select">
						<select name="_sys_status">
							<option value="<?php echo $candidate_sys_status; ?>">Keep deleted</option>
							<option value="0">Restore candidate</option>
						</select>
					</div>
					<div class="general_form_label add_margin_left_30">Merged with</div>
					<div class="general_form_column extended_input">
						<input type="text" name="_sys_redirect" value="<?php echo $candidate_sys_redirect; ?>" />
					</div>
				</div>
				<?php } ?>
			</div>
		</div>

		<?php if ($display_all_tabs) { ?>
		<div id="candi_contact" class="add_margin_top_10 hidden">
		<?php echo $contact_details_form; ?>
		</div>

		<div id="candi_note" class="add_margin_top_10 hidden">
			<div class="gray_section">
				<div class="general_form_row">
					<span style="font-size: 10px; color: blue;">
						* If the candidate has been "assessed", the character note is required.<br/>
						* In the other case, one of those fields is required.
					</span>
				</div>
				<div class="general_form_row add_margin_top_10">
					<div class="general_form_label">character note</div>
					<div class="general_form_column">
						<textarea id="character_note" name="character_note"></textarea>
					</div>
				</div>
				<div class="general_form_row">
					<div class="general_form_label">note</div>
					<div class="general_form_column">
						<textarea id="note" name="note"></textarea>
					</div>
				</div>
			</div>
		</div>

		<div id="candi_resume" class="add_margin_top_10 hidden">
			<div class="gray_section">
				<div class="general_form_row">
					<div class="general_form_label">doc title</div>
					<div class="general_form_column">
						<input type="text" name="doc_title" value="" />
					</div>
				</div>
				<div class="general_form_row add_margin_top_10">
					<div class="general_form_label">doc content</div>
					<div class="general_form_column">
						<textarea id="doc_description" name="doc_description"></textarea>
					</div>
				</div>
				<div class="general_form_row add_margin_top_10">
					<div class="general_form_label">upload doc</div>
					<div class="general_form_column">
						<input type="file" maxfilesize="<?php echo CONST_SS_MAX_DOCUMENT_SIZE; ?>" name="document" />
						<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo CONST_SS_MAX_DOCUMENT_SIZE; ?>" />
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
		<div id="candi_duplicate" class="add_margin_top_10 hidden">
		</div>
	</div>

	<div class="general_form_row add_margin_top_10" style="text-align: center;">
		<input type="submit" value="Save candidate" />
	</div>
</form>

<script>
	var company_token = '';
	var alt_occupation_token = '';
	var alt_industry_token = '';

	<?php if (!empty($company_token)) { ?>
	company_token = <?php echo $company_token; ?>
	<?php } ?>

	<?php if (!empty($alt_occupation_token)) { ?>
	alt_occupation_token = <?php echo $alt_occupation_token; ?>
	<?php } ?>

	<?php if (!empty($alt_industry_token)) { ?>
	alt_industry_token = <?php echo $alt_industry_token; ?>
	<?php } ?>

	$(function()
	{
		$('#birth_date').datepicker({
			defaultDate: '<?php echo $default_date; ?>',
			yearRange: '<?php echo $year_range; ?>',
			showButtonPanel: true,
			changeYear: true,
			numberOfMonths: 2,
			showOn: 'both',
			buttonImage: '<?php echo $calendar_icon; ?>',
			buttonImageOnly: true,
			dateFormat: 'yy-mm-dd'
		});

		$('#company').tokenInput('<?php echo $company_token_url; ?>',
		{
			noResultsText: "no results found",
			tokenLimit: 1,
			prePopulate: company_token
		});

		$('#alt_occupation').tokenInput('<?php echo $alt_occupation_token_url; ?>',
		{
			noResultsText: "no results found",
			tokenLimit: 5,
			prePopulate: alt_occupation_token
		});

		$('#alt_industry').tokenInput('<?php echo $alt_industry_token_url; ?>',
		{
			noResultsText: "no results found",
			tokenLimit: 5,
			prePopulate: alt_industry_token
		});

		$('.gray_section .skill_field input').spinner(
		{
			min:-1, max: 10,
			spin: function(event, ui)
			{
				if(ui.value > 9)
				{
					$(this).spinner("value", 0); return false;
				}
				else if (ui.value < 0)
				{
					$(this).spinner("value", 9); return false;
				}
			}
		});

		$('.gray_section .skill_field input').focus(function()
		{
			if($(this).hasClass('empty_spinner'))
			{
				$(this).val(5).removeClass('empty_spinner').unbind('focus');
			}
		});

		$('#alt_language').bsmSelect(
		{
			animate: true,
			highlight: true,
			showEffect: function(jQueryel)
			{
				var sText = jQueryel.text();
				sText = sText.substr(0, sText.length-1).trim();

				var oOriginal = $('#alt_language option:contains('+sText+')');
				if(oOriginal)
					jQueryel.addClass(oOriginal.attr('class'));

				jQueryel.fadeIn();
			},
			hideEffect: function(jQueryel){ jQueryel.fadeOut(function(){ $(this).remove(); }); },
			removeLabel: '<strong>X</strong>'
		}).change();

		$('.gray_section').find('textarea').each(function()
		{
		  initMce($(this).attr('name'), '', 700);
		});

		linkCurrencyFields('salary_unit', 'bonus', 'salary');
		linkCurrencyFields('salary_unit', 'target_low', 'salary');
		linkCurrencyFields('salary_unit', 'target_high', 'salary');

		$('.salary_field').focusout(function() {
			var formated_value = format_currency($(this).val());

			$(this).val(formated_value);
		});
	});

	function toggle_tabs(menu_dom, tab_id)
	{
		var menu_obj = $(menu_dom);
		var menu_siblings = menu_obj.siblings();

		var tab_obj = $('#candi_container #'+tab_id);
		var tab_siblings = tab_obj.siblings();

		menu_siblings.removeClass('selected');
		menu_obj.addClass('selected');

		tab_siblings.hide();
		tab_obj.show();
	}

	function change_date_field(date_field_id)
	{
		var date_field_obj = $('.general_form_column #'+date_field_id);
		var date_field_siblings = date_field_obj.siblings();

		date_field_siblings.hide();
		if (date_field_id == 'birth_date')
			$('.general_form_column .ui-datepicker-trigger').show();
		date_field_obj.show();
	}

	$('form[name=addcandidate]').submit(function(event){
		event.preventDefault();

		var sURL = $('form[name=addcandidate]').attr('action');
		var sFormId = $('form[name=addcandidate]').attr('id');
		var sAjaxTarget = 'candi_duplicate';
		setTimeout(" AjaxRequest('"+sURL+"', '.body.', '"+sFormId+"', '"+sAjaxTarget+"', '', '', 'setCoverScreen(false);  '); ", 350);

		return false;
	});
</script>