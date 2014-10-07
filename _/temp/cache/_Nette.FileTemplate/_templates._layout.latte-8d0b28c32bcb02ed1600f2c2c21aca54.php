<?php //netteCache[01]000375a:2:{s:4:"time";s:21:"0.64656600 1412631854";s:9:"callbacks";a:2:{i:0;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:9:"checkFile";}i:1;s:53:"D:\wamp\www\volbyonline\_\app\templates\@layout.latte";i:2;i:1412631852;}i:1;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:10:"checkConst";}i:1;s:25:"Nette\Framework::REVISION";i:2;s:30:"b7f6732 released on 2013-01-01";}}}?><?php

// source file: D:\wamp\www\volbyonline\_\app\templates\@layout.latte

?><?php
// prolog Nette\Latte\Macros\CoreMacros
list($_l, $_g) = Nette\Latte\Macros\CoreMacros::initRuntime($template, 'a79y6gh40j')
;
// prolog Nette\Latte\Macros\UIMacros
//
// block title
//
if (!function_exists($_l->blocks['title'][] = '_lbc14220c2f0_title')) { function _lbc14220c2f0_title($_l, $_args) { extract($_args)
?>Volby 2 twitter<?php
}}

//
// block head
//
if (!function_exists($_l->blocks['head'][] = '_lb83833ffde1_head')) { function _lb83833ffde1_head($_l, $_args) { extract($_args)
;
}}

//
// block scripts
//
if (!function_exists($_l->blocks['scripts'][] = '_lb84a18f9f84_scripts')) { function _lb84a18f9f84_scripts($_l, $_args) { extract($_args)
?>	<script src="<?php echo htmlSpecialChars($basePath) ?>/js/jquery.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/2.2.2/js/bootstrap.min.js"></script>	
	<script src="<?php echo htmlSpecialChars($basePath) ?>/js/netteForms.js"></script>
	<script src="<?php echo htmlSpecialChars($basePath) ?>/js/main.js"></script>

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
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="description" content="" />
<?php if (isset($robots)): ?>	<meta name="robots" content="<?php echo htmlSpecialChars($robots) ?>" />
<?php endif ?>

	<title><?php if ($_l->extends) { ob_end_clean(); return Nette\Latte\Macros\CoreMacros::includeTemplate($_l->extends, get_defined_vars(), $template)->render(); }
ob_start(); call_user_func(reset($_l->blocks['title']), $_l, get_defined_vars()); echo $template->upper($template->striptags(ob_get_clean()))  ?></title>

	<link rel="stylesheet" media="screen,projection,tv" href="<?php echo htmlSpecialChars($basePath) ?>/css/screen.css" />
	<link rel="stylesheet" media="print" href="<?php echo htmlSpecialChars($basePath) ?>/css/print.css" />
	<link rel="shortcut icon" href="<?php echo htmlSpecialChars($basePath) ?>/favicon.ico" />
	<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.2/css/bootstrap-combined.min.css" rel="stylesheet" />	
	<?php call_user_func(reset($_l->blocks['head']), $_l, get_defined_vars())  ?>

</head>

<body>
	<script> document.body.className+=' js' </script>

	<div class="navbar">
	  <div class="navbar-inner">
	    <a class="brand" href="#">@volbyonline</a>
	    <ul class="nav">
	      <li class="active"><a href="#">Home</a></li>
	      <li><a xclass="dropdown-toggle" xdata-toggle="dropdown" href="<?php echo htmlSpecialChars($_control->link("default", array('senatAkomunal2014'))) ?>
">2014 - Senátní a komunální volby</a>
	      </li>	      
	      <li><a href="<?php echo htmlSpecialChars($_control->link("default", array('poslaneckaSnemovna2013'))) ?>
">2013 - Poslanecká sněmovna</a></li>
	      <li><a href="<?php echo htmlSpecialChars($_control->link("default", array('prezident2013'))) ?>
">2013 - Prezidentské volby - 2 kolo</a></li>            
	    </ul>
	  </div>
	</div>	

	<p> </p>

	<div class='container'>

		<div class='hero-unit'>
			<h2>Průběžné výsledky voleb</h2>
			<h1><?php echo Nette\Templating\Helpers::escapeHtml($title, ENT_NOQUOTES) ?></h1>
			<h3><?php echo Nette\Templating\Helpers::escapeHtml($subtitle, ENT_NOQUOTES) ?></h3>
		</div>

<?php $iterations = 0; foreach ($flashes as $flash): ?>		<div class="flash <?php echo htmlSpecialChars($flash->type) ?>
"><?php echo Nette\Templating\Helpers::escapeHtml($flash->message, ENT_NOQUOTES) ?></div>
<?php $iterations++; endforeach ?>

<?php Nette\Latte\Macros\UIMacros::callBlock($_l, 'content', $template->getParameters()) ?>

		<ul class="nav nav-pills">
			<li><a href="http://volby.cz/opendata/opendata.htm">Zdroj dat: XML volby.cz</a></li>
		</ul>	

	</div>




<?php call_user_func(reset($_l->blocks['scripts']), $_l, get_defined_vars())  ?>
</body>
</html>
