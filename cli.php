<?php

use xPaw\CommandLine\Command;
use xPaw\CommandLine\BaseCommand;
use xPaw\CommandLine\Option;
use xPaw\CommandLine\Parser;

#[Command("wtf", "This does a thing!!")]
class WtfCommand extends BaseCommand
{
	#[Option("-t|--thing", "This specifies a thing")]
	public int $SomeCoolArgument = 5;

	#[Option("--float", "This specifies a float")]
	public float $SomeFloatCoolArgument = 5.0;

	#[Option("--bool")]
	public ?bool $AnotherArgument;

	#[Option("--null", "This option is nullable")]
	public ?string $NullableOption;

	#[Option("--str", "String option")]
	public string $StringOption;

	public string $NotOption;

	#[Option("--array", "This option is an array")]
	public ?array $ArrayOption;

	public function execute() : void
	{
		//echo "Executed: ";
		//var_dump($this);
		//var_dump('int is ' . $this->SomeCoolArgument);
	}
}

/*
#[Command("testcommand", "Test command, does testy things")]
class TestCommand
{
	#[Option("-v|--verbose", "This specifies a thing")]
	public int $SomeCoolArgument = 5;

	public function __construct()
	{
		var_dump("command is a go!");
	}
}
*/

$Parser = new Parser( [
	WtfCommand::class
] );
$t = $Parser->Handle( $_SERVER[ 'argv' ] );
var_dump($t);
//var_dump($t->AnotherArgument);
