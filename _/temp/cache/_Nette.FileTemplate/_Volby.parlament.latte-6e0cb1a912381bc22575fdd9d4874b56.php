<?php //netteCache[01]000383a:2:{s:4:"time";s:21:"0.49230200 1382666005";s:9:"callbacks";a:2:{i:0;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:9:"checkFile";}i:1;s:61:"D:\wamp\www\volbyonline\_\app\templates\Volby\parlament.latte";i:2;i:1382666004;}i:1;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:10:"checkConst";}i:1;s:25:"Nette\Framework::REVISION";i:2;s:30:"b7f6732 released on 2013-01-01";}}}?><?php

// source file: D:\wamp\www\volbyonline\_\app\templates\Volby\parlament.latte

?><?php
// prolog Nette\Latte\Macros\CoreMacros
list($_l, $_g) = Nette\Latte\Macros\CoreMacros::initRuntime($template, '6q9s62mmez')
;
// prolog Nette\Latte\Macros\UIMacros
//
// block content
//
if (!function_exists($_l->blocks['content'][] = '_lb293135d6ee_content')) { function _lb293135d6ee_content($_l, $_args) { extract($_args)
?>	<table class='table table-condensed table-xhover table-bordered table-striped'>
		<tr>
			<th>datum</th>
			<th>čas</th>
			<th>zpracováno</h>
			<th>účast</th>

<?php $iterations = 0; foreach ($strany as $stranaId => $stranaNazev): ?>
			<th><?php echo Nette\Templating\Helpers::escapeHtml($stranaNazev, ENT_NOQUOTES) ?></th>
<?php $iterations++; endforeach ?>

		</tr>
<?php $iterations = 0; foreach ($data as $row): ?>		<tr <?php if ($row->tweet==1): ?>
 title='tweetnuto'<?php endif ;if ($_l->tmp = array_filter(array($row->tweet==1 ? 'info':null))) echo ' class="' . htmlSpecialChars(implode(" ", array_unique($_l->tmp))) . '"' ?>>
			<td><?php echo Nette\Templating\Helpers::escapeHtml($template->date($row->datumcas, '%d.%m.%Y'), ENT_NOQUOTES) ?></td>
			<td><?php echo Nette\Templating\Helpers::escapeHtml($template->date($row->datumcas, '%H:%M.%S'), ENT_NOQUOTES) ?></td>
			<td><?php echo Nette\Templating\Helpers::escapeHtml($row->zpracovano, ENT_NOQUOTES) ?>%</td>
			<td><?php echo Nette\Templating\Helpers::escapeHtml($row->ucast, ENT_NOQUOTES) ?>%</td>
			
<?php $sClass = '' ;$columnName = '' ;$procento = 0 ;$iterations = 0; foreach ($strany as $stranaId => $stranaNazev): $columnName = 'strana'.$stranaId ;$procento = $row->$columnName ;$sClass = '' ?>
				<?php if ($procento >= 5): $sClass = 'success' ;endif ?>
				<td class='<?php echo htmlSpecialChars($sClass, ENT_QUOTES) ?>'><?php echo Nette\Templating\Helpers::escapeHtml($row->$columnName, ENT_NOQUOTES) ?>%</td>
<?php $iterations++; endforeach ?>

		</tr>
<?php $iterations++; endforeach ?>
	</table>


	<div class="alert alert-info">
	  <strong>INFO</strong> Modře podbarvený řádek jsou data, která byla publikována na twitter účtu 
	  <a href="http://twitter.com/volbyonline">@volbyonline</a>
	</div>

<?php
}}

//
// block head
//
if (!function_exists($_l->blocks['head'][] = '_lbb3a4b6369f_head')) { function _lbb3a4b6369f_head($_l, $_args) { extract($_args)
?><style>
.table tr td, .table tr th  {
	text-align: center;
}

.table tbody tr td.success {
	background-color: #dff0d8;
}
.table tbody tr td.error {
	background-color: #f2dede;
}
</style>
<?php
}}

//
// end of blocks
//

// template extending and snippets support

$_l->extends = empty($template->_extended) && isset($_control) && $_control instanceof Nette\Application\UI\Presenter ? $_control->findLayoutTemplateFile() : NULL; $template->_extended = $_extended = TRUE;


if ($_l->extends) {
	ob_start();

} elseif (!empty($_control->snippetMode)) {
	return Nette\Latte\Macros\UIMacros::renderSnippets($_control, $_l, get_defined_vars());
}

//
// main template
//
if ($_l->extends) { ob_end_clean(); return Nette\Latte\Macros\CoreMacros::includeTemplate($_l->extends, get_defined_vars(), $template)->render(); }
call_user_func(reset($_l->blocks['content']), $_l, get_defined_vars())  ?>

<?php call_user_func(reset($_l->blocks['head']), $_l, get_defined_vars()) ; 