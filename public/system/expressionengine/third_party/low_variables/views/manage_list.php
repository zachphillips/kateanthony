<?php if (empty($variables)): ?>

	<p class="alert"><?=lang('no_variables_found')?></p>

<?php else: ?>

<form action="<?=$base_url?>&amp;method=save_list" method="post">
	<div>
		<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>" />
	</div>
	<table cellpadding="0" cellspacing="0" class="mainTable">
		<colgroup>
			<col style="width:3%" />
			<col style="width:20%" />
			<col style="width:20%" />
			<col style="width:20%" />
			<col style="width:7%" />
			<col style="width:7%" />
			<col style="width:6%" />
			<col style="width:6%" />
			<col style="width:1%" />
		</colgroup>
		<thead>
			<tr>
				<th scope="col">#</th>
				<th scope="col"><?=lang('variable_name')?></th>
				<th scope="col"><?=lang('variable_label')?></th>
				<th scope="col"><?=lang('variable_group')?></th>
				<th scope="col"><?=lang('variable_type')?></th>
				<th scope="col"><?=lang('is_hidden_th')?></th>
				<th scope="col"><?=lang('early_parsing')?></th>
				<th scope="col"><?=lang('clone')?></th>
				<th scope="col"><input type="checkbox" id="low-toggle-all" /></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($variables AS $i => $row): ?>
				<?php $class = ($i % 2) ? 'odd' : 'even'?>
				<tr class="<?=$class?>">
					<td><?=$row['variable_id']?></td>
					<td><a href="<?=$base_url?>&amp;method=manage&amp;id=<?=$row['variable_id']?>" class="low-var-name"><?=$row['variable_name']?></td>
					<td><?=$row['variable_label']?>&nbsp;</td>
					<td><a href="<?=$base_url?>&amp;method=groups&amp;id=<?=$row['group_id']?>&amp;from=manage"><?=htmlspecialchars($variable_groups[$row['group_id']])?></a></td>
					<td><?=isset($types[$row['variable_type']])?$types[$row['variable_type']]->info['name']:lang('unknown_type')?></td>
					<td><?=lang($row['is_hidden'])?></td>
					<td><?=($settings['register_globals']=='y'?lang($row['early_parsing']):'--')?></td>
					<td><a href="<?=$base_url?>&amp;method=manage&amp;id=new&amp;clone=<?=$row['variable_id']?>" class="clone" title="<?=lang('clone')?> <?=$row['variable_name']?>"><?=lang('clone')?></a></td>
					<td><input type="checkbox" id="var_<?=$row['variable_id']?>" name="toggle[]" value="<?=$row['variable_id']?>" /></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div class="box" style="overflow:hidden">

		<div style="float:right">
			<label for="select_action"><?=lang('with_selected')?></label>
			<select name="action" id="select_action">
				<option value=""></option>
				<option value="delete"><?=lang('delete')?></option>
				<optgroup label="<?=lang('show-hide')?>">
					<option value="show"><?=lang('show')?></option>
					<option value="hide"><?=lang('hide')?></option>
				</optgroup>
				<?php if($settings['register_globals'] == 'y'): ?>
					<optgroup label="<?=lang('early_parsing')?>">
						<option value="enable_early_parsing"><?=lang('enable_early_parsing')?></option>
						<option value="disable_early_parsing"><?=lang('disable_early_parsing')?></option>
					</optgroup>
				<?php endif; ?>
				<optgroup label="<?=lang('change_group_to')?>">
					<?php foreach($variable_groups AS $vg_id => $vg_label): ?>
						<option value="<?=$vg_id?>"><?=$vg_label?></option>
					<?php endforeach; ?>
				</optgroup>
				<optgroup label="<?=lang('change_type_to')?>">
					<?php foreach($types AS $type => $obj): ?>
						<option value="<?=$type?>"><?=$obj->info['name']?></option>
					<?php endforeach; ?>
				</optgroup>
			</select>
			<button type="submit" class="submit"><?=lang('submit')?></button>
		</div>

		<!--a href="<?=$base_url?>&amp;method=sort_order" style="float:left;padding:3px"><?=lang('change_sort_order')?></a-->

	</div>
</form>
<?php endif; ?>