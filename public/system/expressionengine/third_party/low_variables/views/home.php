<?php if ($skipped): ?>
	<div class="low-alertbox">
		<p><?=lang('low_variables_saved_except')?></p>
		<ul><?php foreach($skipped AS $row): ?>
			<li><?=($row['var_label']?$row['var_label']:$row['var_name'])?></li>
		<?php endforeach; ?></ul>
	</div>
<?php endif; ?>

<form method="post" action="<?=$base_url.AMP?>method=save" enctype="multipart/form-data" id="low-variables-form" style="overflow:hidden">
	<div>
		<input type="hidden" name="all_ids" value="<?=$all_ids?>" />
		<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>" />
	</div>

<?php if ($show_groups): ?>

	<div id="low-grouplist"<?php if ($settings['is_manager']): ?> class="low-manager ee2"<?php endif; ?>>
		<table class="mainTable" cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th>
						<?=lang('groups')?>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr class="odd">
					<td>
						<ul id="low-sortable-groups">
							<?php foreach ($group_list AS $group_id => $row): ?>
								<?php if ($group_id == 0) continue; ?>
								<li>
									<?php if ($settings['is_manager']): ?>
										<a href="<?=$base_url?>&amp;method=group_delete_confirmation&amp;id=<?=$group_id?>"
											class="low-delete" title="<?=lang('delete_group').' '.htmlspecialchars($row['group_label'])?>"><?=lang('delete_group')?></a>
										<a href="<?=$base_url?>&amp;method=groups&amp;id=<?=$group_id?>&amp;from=home"
											class="low-edit" title="<?=lang('edit_group').' '.htmlspecialchars($row['group_label'])?>"><?=lang('edit_group')?></a>
										<span class="low-handle"></span>
									<?php endif; ?>
									<?php if ($row['count'] == 0): ?>
										<span class="low-grouplink" id="group_id_<?=$group_id?>"><?=htmlspecialchars($row['group_label'])?></span>
									<?php else: ?>
										<a href="#group-<?=$group_id?>" class="low-grouplink" id="group_id_<?=$group_id?>"><?=htmlspecialchars($row['group_label'])?> (<?=$row['count']?>)</a>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
						<ul>
							<?php if (isset($group_list['0'])): ?>
								<li>
									<?php if ($settings['is_manager']): ?>
										<a href="<?=$base_url?>&amp;method=groups&amp;id=0&amp;from=home"
											class="low-edit" title="<?=lang('edit_group').' '.htmlspecialchars($group_list['0']['group_label'])?>"><?=lang('edit_group')?></a>
									<?php endif; ?>
									<a href="#group-0" class="low-grouplink"><?=$group_list['0']['group_label']?> (<?=$group_list['0']['count']?>)</a>
								</li>
							<?php endif; ?>
							<?php if (count($group_list) > 1): ?>
								<li><a href="#all" class="low-grouplink"><?=lang('show_all')?></a></li>
							<?php endif; ?>
						</ul>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div id="low-varlist">

<?php endif; ?>

		<?php foreach($variables AS $group_id => $rows): ?>
			<table class="mainTable low-vargroup" cellspacing="0" cellpadding="0" id="group-<?=$group_id?>">
				<colgroup>
					<col class="label" />
					<col class="input" />
				</colgroup>
				<thead>
					<?php if($show_groups): ?>
					<tr>
						<th scope="col" colspan="2">
							<?=htmlspecialchars($groups[$group_id]['group_label'])?>
						</th>
					</tr>
					<?php else: ?>
					<tr>
						<th scope="col"><?=lang('variable_name')?></th>
						<th scope="col" ><?=lang('variable_data')?></th>
					</tr>
					<?php endif; ?>
				<tbody>
				<?php if($groups[$group_id]['group_notes']): ?>
					<tr>
						<td class="low-group-notes" colspan="2"><?=$groups[$group_id]['group_notes']?></td>
					</tr>
				<?php endif; ?>
				<?php foreach($rows AS $i => $row): ?>
					<tr class="<?=(($i%2)?'even':'odd')?>">
						<td style="vertical-align:top">
							<strong class="low-label">
								<?php if($settings['is_manager']): ?>
									<a href="<?=$base_url.AMP?>method=manage&amp;id=<?=$row['var_id']?>" title="<?=lang('manage_this_variable')?>">
								<?php endif; ?>
										<?=$row['var_name']?>
								<?php if($settings['is_manager']): ?>
									</a>
								<?php endif; ?>
							</strong>
							<?php if(isset($row['error_msg']) && !empty($row['error_msg'])): ?>
								<div class="low-var-alert"><?=(is_array($row['error_msg']) ? implode('<br />', $row['error_msg']) : lang($row['error_msg']))?></div>
							<?php endif; ?>
							<?php if($row['var_notes']): ?>
								<div class="low-var-notes"><?=$row['var_notes']?></div>
							<?php endif; ?>
						</td>
						<td>
							<?=$row['var_input']?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>

		<button type="submit" class="submit"><?=lang('low_variables_save')?></button>

	<?php if($show_groups): ?>
	</div>
	<?php endif; ?>
</form>
