<div class="pageTitle grid_12">	
	<?if($allowTitleEdit):?>
	<div class="ui-Text grid_8 rows-1 field-title">
		<h2 class="ipe h2"><?=$title;?></h2>
	</div>
	<?else:?>
	<div class="ineditable grid_8 rows-1 field-title">
		<h2 class="ipe h2"><?=$title;?></h2>
	</div>	
	<?endif?>
	
	<?if(Kohana::config('cms.enableSlugEditing') && $allowTitleEdit ):?>
		<div class="ui-Text grid_4 field-slug">
			<label>Edit slug</label>
			<p class="ipe p hidden"><?=$slug;?></p>
		</div>
 	<?endif;?>

	<div class="clear"></div>

</div>
