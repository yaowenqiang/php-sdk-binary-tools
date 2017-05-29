<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Config as PGOConfig;


interface TrainingCase
{
	public function __construct(PGOConfig $conf);

	/* Initialize the case, run only once on a new checkout. */
	public function init();

	/* Run training. */
	//public function run();

}

