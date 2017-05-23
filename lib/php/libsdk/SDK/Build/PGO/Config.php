<?php

namespace SDK\Build\PGO;

use SDK\{Config as SDKConfig, Exception};

class Config
{
	const MODE_INIT = 0;
	const MODE_RUN = 1;

	protected $mode;
	protected $last_port = 8083;
	protected $sections = array();

	public function __construct(int $mode = MODE_RUN)
	{
		$this->mode = $mode;


		$base = getenv("PHP_SDK_ROOT_PATH");

		if (self::MODE_INIT == $mode) {

			$this->sections["nginx"] = parse_ini_file(implode("\\", array($base, "pgo", "tpl", "nginx", "phpsdk_pgo.ini")), true, INI_SCANNER_TYPED);
			$this->sections["mariadb"] = parse_ini_file(implode("\\", array($base, "pgo", "tpl", "mariadb", "phpsdk_pgo.ini")), true, INI_SCANNER_TYPED);
			$this->sections["php"] = parse_ini_file(implode("\\", array($base, "pgo", "tpl", "php", "phpsdk_pgo.ini")), true, INI_SCANNER_TYPED);
		} else if (self::MODE_INIT == $mode) {
			$this->sections = parse_ini_file(implode("\\", array($base, "pgo", "work", "phpsdk_pgo.ini")), true, INI_SCANNER_TYPED);
		} else {
			throw new Exception("Unknown config mode '$mode'.");
		}
	}

	public function getWorkDir() : string
	{
		$base = getenv("PHP_SDK_ROOT_PATH");

		return $base . DIRECTORY_SEPARATOR . "pgo" . DIRECTORY_SEPARATOR . "work";
	}

	public function getSrvDir(string $name = NULL) : string
	{
		$ret = $this->getWorkDir() . DIRECTORY_SEPARATOR . "server";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function getHtdocs(string $name = NULL) : string
	{
		$ret = $this->getWorkDir() . DIRECTORY_SEPARATOR . "htdocs";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function getTplDir(string $name = NULL) : string
	{
		$ret = getenv("PHP_SDK_ROOT_PATH") . DIRECTORY_SEPARATOR . "pgo" . DIRECTORY_SEPARATOR . "tpl";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}


	public function sectionItemExists(...$args) : bool
	{
		$i = 0;
		$k = $args[$i];
		$it = $this->sections;

		while (array_key_exists($k, $it)) {
			$it = $it[$k];

			if (++$i >= count($args)) break;

			$k = $args[$i];
		}

		return $i == count($args);
	}

	public function getSectionItem(...$args)
	{
		$i = 0;
		$k = $args[$i];
		$it = $this->sections;

		while (array_key_exists($k, $it)) {
			$it = $it[$k];

			if (++$i >= count($args)) break;

			$k = $args[$i];
		}

		if ($i != count($args)) {
			return NULL;
		}

		return $it;
	}

	public function setSectionItem(...$args) : void
	{
		$val = array_pop($args);

		$i = 0;
		$k = $args[$i];
		$it = &$this->sections;

		while (true) {
			if (!array_key_exists($k, $it)) {
				$it = array();
			}
			$it = &$it[$k];

			if (++$i >= count($args)) break;

			$k = $args[$i];
		}

		$it = $val;
	}

	public function dump()
	{

	}
}
