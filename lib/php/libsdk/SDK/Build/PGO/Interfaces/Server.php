<?php

namespace SDK\Build\PGO\Interfaces;

interface Server
{
	public function init();
	public function up();
	public function down();
}

