<?php //netteCache[01]000379a:2:{s:4:"time";s:21:"0.13211700 1412633088";s:9:"callbacks";a:2:{i:0;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:9:"checkFile";}i:1;s:57:"D:\wamp\www\volbyonline\_\app\templates\Volby\multi.latte";i:2;i:1412633085;}i:1;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:10:"checkConst";}i:1;s:25:"Nette\Framework::REVISION";i:2;s:30:"b7f6732 released on 2013-01-01";}}}?><?php

// source file: D:\wamp\www\volbyonline\_\app\templates\Volby\multi.latte

?><?php
// prolog Nette\Latte\Macros\CoreMacros
list($_l, $_g) = Nette\Latte\Macros\CoreMacros::initRuntime($template, 'kn24leix4r')
;
// prolog Nette\Latte\Macros\UIMacros
//
// block content
//
if (!function_exists($_l->blocks['content'][] = '_lbd59fb1bebe_content')) { function _lbd59fb1bebe_content($_l, $_args) { extract($_args)
?>	<h2>Výpis sledovaných volebních klání</h2>

	<ul>
<?php $iterations = 0; foreach ($multiCasti as $key => $casti): ?>
		<li><a href="<?php echo htmlSpecialChars($_control->link("default", array($key))) ?>
"><?php echo Nette\Templating\Helpers::escapeHtml($casti, ENT_NOQUOTES) ?></a></li>
<?php $iterations++; endforeach ?>
	</ul>


	<div class="alert alert-info">
	  <strong>INFO</strong> Modře podbarvený řádek jsou data, která byla publikována na twitter účtu 
	  <a href="http://twitter.com/volbyonline">@volbyonline</a>
	</div>

<?php
}}

//
// block head
//
if (!function_exists($_l->blocks['head'][] = '_lba9887f72c2_head')) { function _lba9887f72c2_head($_l, $_args) { extract($_args)
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
?>

<?php if ($_l->extends) { ob_end_clean(); return Nette\Latte\Macros\CoreMacros::includeTemplate($_l->extends, get_defined_vars(), $template)->render(); }
call_user_func(reset($_l->blocks['content']), $_l, get_defined_vars())  ?>

<?php call_user_func(reset($_l->blocks['head']), $_l, get_defined_vars()) ; 