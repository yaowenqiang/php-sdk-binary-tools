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
	protected $scenario = "default";

	public function __construct(int $mode = MODE_RUN)
	{
		$this->mode = $mode;


		$base = getenv("PHP_SDK_ROOT_PATH");

		if (self::MODE_INIT == $mode) {
			foreach (array("nginx", "mariadb", "php") as $i) {
				$fn = $this->getTplDir() . DIRECTORY_SEPARATOR . $i . DIRECTORY_SEPARATOR . "phpsdk_pgo.json";
				if (file_exists($fn)) {
					$s = file_get_contents($fn);
					$this->setSectionItem($i, json_decode($s, true));
				}
			}
		} else if (self::MODE_RUN == $mode) {
			$fn = $this->getSectionsFilename();
			if (!file_exists($fn)) {
				throw new Exception("Required config doesn't exist under '$fn'.");
			}
			$s = file_get_contents($fn);
			$this->sections = json_decode($s, true);
		} else {
			throw new Exception("Unknown config mode '$mode'.");
		}
	}

	public function getToolsDir() : string
	{
		$base = $this->getWorkDir();

		return $base . DIRECTORY_SEPARATOR . "tools";
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

	public function getCaseWorkDir(string $name = NULL) : string
	{
		$ret = $this->getWorkDir() . DIRECTORY_SEPARATOR . "htdocs";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function getCasesTplDir(string $name = NULL) : string
	{
		$ret = getenv("PHP_SDK_ROOT_PATH") . DIRECTORY_SEPARATOR . "pgo" . DIRECTORY_SEPARATOR . "cases";

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
			$it = &$it[$k];
			if (++$i >= count($args)) break;
			$k = $args[$i];
		}

		$it = $val;
	}

	public function getSectionsFilename()
	{
		return $this->getWorkDir() . DIRECTORY_SEPARATOR . "phpsdk_pgo.json";
	}

	public function dump(string $fn = NULL) : void
	{
		$fn = $fn ? $fn : $this->getSectionsFilename();

		$s = json_encode($this->sections, JSON_PRETTY_PRINT);

		$ret = file_put_contents($fn, $s);
		if (false === $ret || strlen($s) !== $ret) {
			throw new Exception("Errors with writing to '$fn'.");
		}
	}

	public function setScenario(string $scenario) : void
	{
		if (!in_array($scenario, array("default", "cache"), true)) {
			throw new Exception("Unknown scenario '$scenario'.");
		}
		$this->scenario = $scenario;
	}

	public function getScenario() : string
	{
		return $this->scenario;
	}
}
