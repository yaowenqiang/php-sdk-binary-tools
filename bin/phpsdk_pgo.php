<?php

include dirname(__FILE__) . "/../lib/php/libsdk/autoload.php";

use SDK\Config;
use SDK\Exception;
use SDK\Build\PGO\Controller;

$sopt = "itudh";
$lopt = array("init", "train", "up", "down", "help");

$cmd = NULL;
/* TODO For now we simply check the current php build, this could be extended to take arbitrary binaries. */
$deps_root = NULL;
$php_root = NULL;

try {
	$opt = getopt($sopt, $lopt);
	foreach ($opt as $name => $val) {
		switch ($name) {
		case "i":
		case "init":
			$cmd = "init";
			break;
		case "t":
		case "train":
			$cmd = "train";
			break;
		case "u":
		case "up":
			$cmd = "up";
			break;
		case "d":
		case "down":
			$cmd = "down";
			break;
		case "h": case "help":
			usage(0);
			break;

		}
	}

	if (NULL === $cmd) {
		usage();
	}

	$deps_root = Config::getDepsLocalPath();

	if (!file_exists("Makefile")) {
		throw new Exception("Makefile not found. Arbitrary php snapshots are not supported yet, switch to the php source dir.");
	}
	/* XXX might be improved. */
	if (preg_match(",BUILD_DIR=(.+),", file_get_contents("Makefile"), $m)) {
		$php_root = trim($m[1]);
	}
	if (!$php_root || !file_exists($php_root)) {
		throw new Exception("Invalid php root dir encountered '$php_root'.");
	}
	//var_dump($cmd, $deps_root, $php_root);

	$controller = new Controller($cmd, $php_root, $deps_root);
	$controller->handle();

	/*$env = getenv();
	$env["PATH"] = $deps_root . DIRECTORY_SEPARATOR . "bin;" . $env["PATH"];

	$php = $php_root . DIRECTORY_SEPARATOR . "php.exe";
	$php = $php_root . DIRECTORY_SEPARATOR . "php.exe";*/

} catch (Throwable $e) {
	throw $e;
	exit(3);
}


function usage(int $code = -1)
{
	echo "PHP SDK PGO training tool.", PHP_EOL;
	echo "Usage: ", PHP_EOL, PHP_EOL;
	echo "Commands:", PHP_EOL;
	echo "  -i --init  Initialize training environment.", PHP_EOL;
	echo "  -t --train Run training. This involves startup, training and shutdown.", PHP_EOL;
	/*echo "  -u --up    Startup training environment.", PHP_EOL;
	echo "  -d --down  Shutdown training environment.", PHP_EOL;*/

	/*echo "  -p --php-root  PHP binary to train.", PHP_EOL;*/

	$code = -1 == $code ? 0 : $code;
	exit($code);
}

function msg(string $s, int $code = 0) {
	echo $s, PHP_EOL;
	exit($code);
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */