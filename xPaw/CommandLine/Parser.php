<?php
declare(strict_types=1);

namespace xPaw\CommandLine;

use Exception;

// php cli.php wtf -t --test --test=wtf -t=wtf --test="wtf wtf"  "0"  -t="wtf wtf" -t "wtf" -w --wtf -www -t= --test="" --null --notype="wtf"

class Parser
{
	/** @var array<Command> */
	public array $Commands = [];

	public function __construct( ?array $Classes = null )
	{
		$Classes ??= get_declared_classes();

		if( empty( $Classes ) )
		{
			throw new \Exception( 'There should be at least one class provided.' );
		}

		foreach( $Classes as $DefinedClass )
		{
			$ReflectedClass = new \ReflectionClass( $DefinedClass );
			$Attributes = $ReflectedClass->getAttributes( Command::class );

			if( empty( $Attributes ) )
			{
				continue;
			}

			$CommandAttribute = $Attributes[ 0 ]; // TODO: Throw if more than 1?

			/** @var Command */
			$Command = $CommandAttribute->newInstance();
			$Command->ReflectedClass = $ReflectedClass;

			foreach( $ReflectedClass->getProperties() as $Property )
			{
				$PropertyAttributes = $Property->getAttributes( Option::class );

				if( empty( $PropertyAttributes ) )
				{
					continue;
				}

				$OptionAttribute = $PropertyAttributes[ 0 ]; // TODO: Throw if more than 1?

				/** @var Option */
				$Option = $OptionAttribute->newInstance();
				$Option->ReflectedProperty = $Property;

				$Type = $Property->getType();

				if( $Type === null )
				{
					throw new \Exception( "Property \${$Property->getName()} must have a type." );
				}

				if( $Type instanceof \ReflectionUnionType ) // TODO
				{
					throw new \Exception( 'Union typed properties are not supported.' );
				}

				$Option->Nullable = $Type->allowsNull();
				$Option->Type = $Type->getName();

				$Command->Options[] = $Option;
			}

			$this->Commands[ $Command->Name ] = $Command;
		}
	}

	public function Handle( array $Arguments ) : BaseCommand
	{
		// The first argument $argv[0] is always the name that was used to run the script.
		array_shift( $Arguments );

		// If there is only one command registered, just don't take a sub command
		// and go on parsing options directly
		if( count( $this->Commands ) > 1 )
		{
			if( empty( $Arguments ) )
			{
				$this->PrintHelp();
				exit;
			}

			$ProvidedCommandName = array_shift( $Arguments );

			if( !isset( $this->Commands[ $ProvidedCommandName ] ) )
			{
				if( $ProvidedCommandName === 'help' )
				{
					$this->PrintHelp();
					exit;
				}

				\fwrite( STDERR, "Unknown command: '{$ProvidedCommandName}'" . PHP_EOL );
				exit( 1 );
			}

			$Command = $this->Commands[ $ProvidedCommandName ];
		}
		else
		{
			if( !empty( $Arguments ) && $Arguments[ 0 ] === 'help' )
			{
				$this->PrintHelp();
				exit;
			}

			$Command = \reset( $this->Commands );
		}

		$CommandInstance = $Command->ReflectedClass->newInstance();

		foreach( $Arguments as $Argument )
		{
			// The argument `--` terminates all options; any following arguments
			// are treated as non-option arguments, even if they begin with a hyphen.
			if( $Argument === '--' )
			{
				break;
			}

			if( $Argument[ 0 ] !== '-' )
			{
				\fwrite( STDERR, "Argument '{$Argument}' is not an option." . PHP_EOL );
				exit( 1 );
			}

			$IsShortOption = ( $Argument[ 1 ] ?? '' ) !== '-';
			$ValuePosition = strpos( $Argument, '=' );
			$ProvidedName = null;
			$ProvidedValue = null;

			if( $ValuePosition !== false )
			{
				$ProvidedName = substr( $Argument, $IsShortOption ? 1 : 2, $ValuePosition - ( $IsShortOption ? 1 : 2 ) );
				$ProvidedValue = substr( $Argument, $ValuePosition + 1 );
			}
			else
			{
				$ProvidedName = substr( $Argument, $IsShortOption ? 1 : 2 );
			}

			$FoundOption = null;

			foreach( $Command->Options as $Option )
			{
				$OptionName = $IsShortOption ? $Option->ShortName : $Option->LongName;

				if( $OptionName === $ProvidedName )
				{
					$FoundOption = $Option;
					break;
				}
			}

			if( $FoundOption === null )
			{
				$FormattedName = ( $IsShortOption ? '-' : '--' ) . $ProvidedName;
				\fwrite( STDERR, "Option '{$FormattedName}' is unknown." . PHP_EOL );
				exit( 1 );
			}

			$TypedValue = null;

			if( $ProvidedValue === null )
			{
				if( $FoundOption->Type === 'bool' )
				{
					$TypedValue = true;
				}
				else if( !$FoundOption->Nullable )
				{
					\fwrite( STDERR, "Option '{$FoundOption->ToTemplateString()}' requires a value." . PHP_EOL );
					exit( 1 );
				}
			}
			else
			{
				switch( $FoundOption->Type )
				{
					case 'string':
						$TypedValue = $ProvidedValue;
						break;

					case 'int':
						// TODO: Test that the value is actually numeric
						$TypedValue = intval( $ProvidedValue );
						break;

					case 'float':
						// TODO: Test that the value is actually numeric
						$TypedValue = floatval( $ProvidedValue );
						break;

					case 'bool':
						$TypedValue =
							$ProvidedValue === 'YES' ||
							$ProvidedValue === 'yes' ||
							$ProvidedValue === 'TRUE' ||
							$ProvidedValue === 'true';
						break;

					case 'array': // TODO: Support all iteratables?
						if( !$FoundOption->ReflectedProperty->isInitialized( $CommandInstance ) )
						{
							$FoundOption->ReflectedProperty->setValue( $CommandInstance, [] );
						}

						// TODO: Is there a better way of doing this?
						$arr = $FoundOption->ReflectedProperty->getValue( $CommandInstance );
						$arr[] = $ProvidedValue;
						$FoundOption->ReflectedProperty->setValue( $CommandInstance, $arr );

						continue 2;

					default:
						throw new \Exception( "Type '{$FoundOption->Type}' is unhandled." );
				}
			}

			// TODO: Error if option provided multiple times and option is not an array

			$FoundOption->ReflectedProperty->setValue( $CommandInstance, $TypedValue );
		}

		/*
		foreach( $Command->Options as $Option )
		{
			if( !$Option->Nullable && !$Option->ReflectedProperty->isInitialized( $CommandInstance ) )
			{
				\fwrite( STDERR, "Option '{$Option->ToTemplateString()}' is required." . PHP_EOL );
				exit( 1 );
			}
		}
		*/

		$CommandInstance->execute();

		return $CommandInstance;
	}

	public function PrintHelp() : void
	{
		echo "Commands:" . PHP_EOL;

		foreach( $this->Commands as $Command )
		{
			printf( " %-40s %s" . PHP_EOL, $Command->Name, $Command->Description );

			foreach( $Command->Options as $Option )
			{
				printf( "  %-20s %-20s %s" . PHP_EOL, ( $Option->Nullable ? '?' : '' ) . $Option->Type, $Option->ToTemplateString(), $Option->Description );
			}

			echo PHP_EOL;
		}
	}
}
