<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Config as PGOConfig;


interface TrainingCase
{
	public function __construct(PGOConfig $conf, ?Server $srv_http, ?Server\DB $srv_db, ?PHP $php);

	public function getName() : string;

	/* Initialize the case, run only once on a new checkout. */
	public function init() : void;

	/* Run training. */
	public function run() : void;

	public function getType() : string;
}

