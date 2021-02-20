<?php
declare(strict_types=1);

namespace xPaw\CommandLine;

abstract class BaseCommand
{
	public abstract function execute() : void;
}
