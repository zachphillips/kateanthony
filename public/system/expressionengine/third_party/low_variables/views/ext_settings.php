<form method="post" action="<?=BASE?>&amp;D=cp&amp;C=addons_extensions&amp;M=save_extension_settings">
	<div>
		<input type="hidden" name="file" value="<?=strtolower($name)?>" />
		<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>" />
	</div>
	<table cellpadding="0" cellspacing="0" style="width:100%" class="mainTable">
		<colgroup>
			<col style="width:50%" />
			<col style="width:50%" />
		</colgroup>
		<thead>
			<tr>
				<th scope="col"><?=lang('preference')?></th>
				<th scope="col"><?=lang('setting')?></th>
			</tr>
		</thead>
		<tbody>
			<tr class="odd">
				<td>
					<span class="alert">*</span>
					<label for="license_key"><?=lang('license_key')?></label>
					<p><?=lang('license_key_help')?></p>
				</td>
				<td>
					<input type="text" name="license_key" id="license_key" style="width:90%" value="<?=htmlspecialchars($license_key)?>" />
				</td>
			</tr>
			<tr class="even">
				<td style="vertical-align:top">
					<strong class="label"><?=lang('can_manage')?></strong>
					<p><?=lang('can_manage_help')?></p>
				</td>
				<td>
					<?php foreach ($member_groups AS $group_id => $group_name): ?>
						<label style="display:block;cursor:pointer">
							<input type="checkbox" name="can_manage[]" value="<?=$group_id?>" <?php if (in_array($group_id, $can_manage)): ?>checked="checked" <?php endif; ?>/>
							<?=htmlspecialchars($group_name)?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr class="odd">
				<td>
					<strong class="label"><?=lang('register_globals')?></strong>
					<p><?=lang('register_globals_help')?></p>
				</td>
				<td>
					<label style="cursor:pointer"><input type="radio" name="register_globals" value="y"<?php if ($register_globals == 'y'): ?> checked="checked"<?php endif; ?> /> <?=lang('yes')?></label>
					<label style="cursor:pointer;margin-left:10px"><input type="radio" name="register_globals" value="n"<?php if ($register_globals == 'n'): ?> checked="checked"<?php endif; ?> /> <?=lang('no')?></label>
				</td>
			</tr>
			<tr class="even">
				<td>
					<strong class="label"><?=lang('register_member_data')?></strong>
					<p><?=lang('register_member_data_help')?></p>
				</td>
				<td>
					<label style="cursor:pointer"><input type="radio" name="register_member_data" value="y"<?php if ($register_member_data == 'y'): ?> checked="checked"<?php endif; ?> /> <?=lang('yes')?></label>
					<label style="cursor:pointer;margin-left:10px"><input type="radio" name="register_member_data" value="n"<?php if ($register_member_data == 'n'): ?> checked="checked"<?php endif; ?> /> <?=lang('no')?></label>
				</td>
			</tr>
			<tr class="odd">
				<td style="vertical-align:top">
					<strong class="label"><?=lang('variable_types')?></strong>
					<p><?=lang('variable_types_help')?></p>
				</td>
				<td>
					<?php foreach($variable_types AS $type => $info): ?>
						<label style="display:block;cursor:pointer"><input type="checkbox" name="enabled_types[]" value="<?=$type?>" <?php if(in_array($type, $enabled_types)): ?>checked="checked" <?php endif; ?>
							<?php if($info['is_default']): ?> disabled="disabled"<?php endif; ?> />
						<?=$info['name']?> &ndash; <small><?=$info['version']?></small></label>
					<?php endforeach; ?>
				</td>
			</tr>
		</tbody>
	</table>
	<input type="submit" class="submit" value="<?=lang('submit')?>" />
</form>