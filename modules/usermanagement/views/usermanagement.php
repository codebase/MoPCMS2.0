<div id="<?=$instance;?>" class="module <?=$class;?> classPath-mop_modules_List sortable-true">
<?if(isset($label) && $label):?>
		<label><?=$label;?></label>
	<?endif;?>
	<ul class="listing">
		<?=$items;?>
	</ul>
 <div class="controls">
    <a href="#" class="addItem button grid_2">Add a New User</a>
  </div>
</div>


