<?php //netteCache[01]000383a:2:{s:4:"time";s:21:"0.69998300 1359147192";s:9:"callbacks";a:2:{i:0;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:9:"checkFile";}i:1;s:61:"D:\wamp\www\twittervolbyonline\app\templates\Volby\cron.latte";i:2;i:1359147190;}i:1;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:10:"checkConst";}i:1;s:25:"Nette\Framework::REVISION";i:2;s:30:"b7f6732 released on 2013-01-01";}}}?><?php

// source file: D:\wamp\www\twittervolbyonline\app\templates\Volby\cron.latte

?><?php
// prolog Nette\Latte\Macros\CoreMacros
list($_l, $_g) = Nette\Latte\Macros\CoreMacros::initRuntime($template, '2gq9pw08oq')
;
// prolog Nette\Latte\Macros\UIMacros

// snippets support
if (!empty($_control->snippetMode)) {
	return Nette\Latte\Macros\UIMacros::renderSnippets($_control, $_l, get_defined_vars());
}

//
// main template
//
?>
<h1>cron</h1>
<p><?php echo Nette\Templating\Helpers::escapeHtml($status, ENT_NOQUOTES) ?></p>