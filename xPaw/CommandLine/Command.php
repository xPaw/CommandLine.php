<?php
declare(strict_types=1);

namespace xPaw\CommandLine;

// TODO: One command so that scripts can be implemented without requering first argv to be a command
#[\Attribute]
class Command
{
	public \ReflectionClass $ReflectedClass;

	/** @var array<Option> */
	public array $Options = [];

	public function __construct(
        public string $Name,
        public string $Description = '',
    ) {
		//
	}
}
