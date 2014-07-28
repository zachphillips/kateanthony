<?php if ($errors): ?>
	<div class="low-alertbox">
		<ul><?php foreach($errors AS $msg): ?>
			<li><?=$msg?></li>
		<?php endforeach; ?></ul>
	</div>
<?php endif; ?>

<form method="post" action="<?=$base_url?>&amp;method=save_group" id="low-variable-form">
	<div>
		<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>" />
		<input type="hidden" name="group_id" value="<?=$group_id?>" />
		<input type="hidden" name="from" value="<?=$from?>" />
	</div>
	<table cellpadding="0" cellspacing="0" class="mainTable">
		<colgroup>
			<col class="key" />
			<col class="val" />
		</colgroup>
		<thead>
			<tr>
				<th colspan="2"><?=lang('edit_group')?> (#<?=$group_id?>)</th>
			</tr>
		</thead>
		<tbody>
			<tr class="odd">
				<td>
					<label class="low-label" for="group_label"><span class="alert">*</span> <?=lang('group_label')?></label>
					<!-- <div class="low-var-notes"><?=lang('group_label_help')?></div> -->
				</td>
				<td>
					<?php if ($group_id === '0'): ?>
						<?=htmlspecialchars($group_label)?>
					<?php else: ?>
						<input type="text" name="group_label" id="low_group_label" class="medium" value="<?=htmlspecialchars($group_label)?>" />
						<?php if ($group_id == 'new'): ?><script type="text/javascript"> document.getElementById('low_group_label').focus(); </script><?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ($group_id !== '0'): ?>
				<tr class="even">
					<td style="vertical-align:top">
						<label class="low-label" for="group_notes"><?=lang('group_notes')?></label>
						<div class="low-var-notes"><?=lang('group_notes_help')?></div>
					</td>
					<td>
						<textarea name="group_notes" id="group_notes" rows="4" cols="40"><?=htmlspecialchars($group_notes)?></textarea>
					</td>
				</tr>
			<?php endif; ?>
			<?php if ($group_id != 'new' && count($variables)): ?>
				<tr class="odd">
					<td style="vertical-align:top">
						<span class="low-label"><?=lang('variable_order')?></span>
					</td>
					<td style="padding:0">
						<ul id="low-variables-list">
							<?php foreach($variables AS $i => $row): ?>
								<li>
									<input type="hidden" name="vars[]" value="<?=$row['variable_id']?>" />
									<?=(strlen($row['variable_label'])?$row['variable_label']:$row['variable_name'])?>
								</li>
							<?php endforeach; ?>
						</ul>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<button type="submit" class="submit"><?=lang('low_variables_save')?></button>

</form>