<div id="save_search">
	<form submitAjax="1" onBeforeSubmit="" onsubmit="" method="POST" action="<?php echo $form_url ;?>"
		name="save_search" enctype="multipart/form-data" id="save_search_form">
		<div class="general_form_row add_margin_top_10">
			<div class="general_form_label" style="font-size: 12px;">
				Search label
			</div>
			<div class="general_form_column extended_input">
				<input type="text" name="search_label" value="<?php echo $search_label ;?>" />
			</div>
		</div>
		<div class="general_form_row add_margin_top_10" style="text-align: center;">
			<input type="submit" value="Save" />
		</div>
	</form>

	<script>
		$('form[name=save_search]').submit(function(event){
			event.preventDefault();

			var url = $('form[name=save_search]').attr('action');
			var form_id = $('form[name=save_search]').attr('id');
			var target = 'save_search';
			setTimeout(" AjaxRequest('"+url+"', '.body.', '"+form_id+"', '"+target+"', '', '', 'setCoverScreen(false);  '); ", 350);

			return false;
		});
	</script>
</div>