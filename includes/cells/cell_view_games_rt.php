<div id="challengewrapper">
</div>
<div id="challenge">
	<?=GetStringFromStringTable("IDS_CREATE_GAME_BTN_CG", $config)?>
	<a href="javascript:cancelChallenge();"><img src="modules/RealTimeInterface/images/close.png" width="10" style="border:none; position:absolute; right:3px; top:3px;" /></a>
	<div style="clear:both"><div style="float:left"><?=GetStringFromStringTable("IDS_CREATE_GAME_TABLE_TXT_2", $config)?> </div> <div style="float:right"><select id="mypiececolor" class="post"> <option value="w"><?=GetStringFromStringTable("IDS_CREATE_GAME_SELECT_COLOR_1", $config)?></option><option value="b"><?=GetStringFromStringTable("IDS_CREATE_GAME_SELECT_COLOR_2", $config)?></option></select></div></div>
	<div style="clear:both"><div style="float:left"><?=GetStringFromStringTable("IDS_CREATE_GAME_TXT_8", $config)?> </div> <div style="float:right"><select id="ratingtype" class="post"><option value="grated"><?=GetStringFromStringTable("IDS_CREATE_GAME_OPT_7", $config)?></option><option value="gunrated"><?=GetStringFromStringTable("IDS_CREATE_GAME_OPT_8", $config)?></option></select></div></div>
	<div style="clear:both"><div style="float:left"><?=GetStringFromStringTable("IDS_CREATE_GAME_TXT_3", $config)?> </div> <div style="float:right"><select id="gametime" class="post"><option value="RT-Blitz"><?=GetStringFromStringTable("IDS_CREATE_GAME_OPT_1", $config)?></option><option value="RT-Short"><?=GetStringFromStringTable("IDS_CREATE_GAME_OPT_2", $config)?></option><option value="RT-Normal"><?=GetStringFromStringTable("IDS_CREATE_GAME_OPT_3", $config)?></option><option value="RT-Slow"><?=GetStringFromStringTable("IDS_CREATE_GAME_OPT_4", $config)?></option></select></div></div>
	<div style="clear:both"><a href="javascript:challenge();"><?=GetStringFromStringTable("IDS_CREATE_GAME_BTN_CG", $config)?></a></div>
</div>
<div id="wrapper">
	<div id="playerswrapper" class="forumline" style="overflow:auto">
		<div class="tableheadercolor sitemenuheader">
		<?=GetStringFromStringTable("IDS_INDEX_TXT_4", $config)?>
		</div>
		<div id="players" style="overflow:auto">
		</div>
	</div>
	<div id="challengeswrapper" class="forumline" style="overflow:auto">
		<div class="tableheadercolor sitemenuheader"> <?=GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_4", $config)?>
		</div>
		<div id="challenges" style="overflow:auto">
		</div>
	</div>
	<div id="opengameswrapper" class="forumline" style="overflow:auto">
		<div class="tableheadercolor sitemenuheader"> <?=GetStringFromStringTable("IDS_CR3DCQUERY_TXT_42", $config)?> <a href="javascript:promptChallenge('0');" style="margin-left:150px;" ><?=GetStringFromStringTable("IDS_CREATE_GAME_BTN_CG", $config)?></a>
		</div>
		<div id="opengames" style="overflow:auto">
		</div>
	</div>
</div>
