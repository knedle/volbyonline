<?php

/**
 * Nette Framework (version 2.0.8 released on 2013-01-01, http://nette.org)
 *
 * Copyright (c) 2004, 2013 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */



/**
 * Check and reset PHP configuration.
 */
error_reporting(E_ALL | E_STRICT);
@set_magic_quotes_runtime(FALSE); // @ - deprecated since PHP 5.3.0
iconv_set_encoding('internal_encoding', 'UTF-8');
extension_loaded('mbstring') && mb_internal_encoding('UTF-8');
umask(0);
@header('X-Powered-By: Nette Framework'); // @ - headers may be sent
@header('Content-Type: text/html; charset=utf-8'); // @ - headers may be sent



/**
 * Load and configure Nette Framework.
 */
define('NETTE', TRUE);
define('NETTE_DIR', __DIR__);
define('NETTE_VERSION_ID', 20008); // v2.0.8
define('NETTE_PACKAGE', '5.3');



require_once __DIR__ . '/common/exceptions.php';
require_once __DIR__ . '/common/Object.php';
require_once __DIR__ . '/Utils/LimitedScope.php';
require_once __DIR__ . '/Loaders/AutoLoader.php';
require_once __DIR__ . '/Loaders/NetteLoader.php';


Nette\Loaders\NetteLoader::getInstance()->register();

require_once __DIR__ . '/Diagnostics/Helpers.php';
require_once __DIR__ . '/Diagnostics/shortcuts.php';
require_once __DIR__ . '/Utils/Html.php';
Nette\Diagnostics\Debugger::_init();

Nette\Utils\SafeStream::register();



/**
 * Nette\Callback factory.
 * @param  mixed   class, object, callable
 * @param  string  method
 * @return Nette\Callback
 */
function callback($callback, $m = NULL)
{
	return new Nette\Callback($callback, $m);
}
