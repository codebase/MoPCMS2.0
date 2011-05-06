<li id="item_<?=$data['id'];?>" class="listItem">
	<div class="container_12">	
	<?=mopui::Text( 'username', "rows-1 validation-nonEmpty grid_3 alpha", "p", $data['username'], 'Username' );?>
	<?=mopui::Text( 'email', "rows-1 validation-email grid_4", "p", $data['email'], 'Email' );?>
	<?=mopui::Text( 'password', "rows-1 validation-nonEmpty type-password grid_3 omega", "p", $data['password'], 'Reset and Mail Password' );?>
	</div>
	<div class="itemControls">
		<a href="#" title="delete this list item" class="icon delete"><span>delete</span></a>
		<a href="#" title="submit" class="button submit hidden">submit</a>
		<a href="#" title="cancel" class="button cancel hidden">cancel</a>
	</div>
	<div class="clear">
		<?=mopui::radioGroup( 'role', '', $managedRoles, $data['role'], 'User Role');?>
	</div>
</li>
