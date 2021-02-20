<?php
declare(strict_types=1);

namespace xPaw\CommandLine;

#[\Attribute]
class Option
{
	public ?string $ShortName = null;
	public ?string $LongName = null;
	public string $Type;
	public bool $Nullable;
	public \ReflectionProperty $ReflectedProperty;

	/**
	 * @param string $Name        Option name. Example: `-v|--version`
	 * @param string $Description Option description.
	 */
	public function __construct(
		public string $Name,
        public string $Description = '',
	)
	{
		$Names = explode( '|', $Name );

		foreach( $Names as $OneName )
		{
			if( \str_starts_with( $OneName, '--' ) )
			{
				$this->LongName = \substr( $OneName, 2 );
			}
			else if( \str_starts_with( $OneName, '-' ) )
			{
				if( \strlen( $OneName ) !== 2 )
				{
					throw new \InvalidArgumentException( "Short option name must be exactly one character, given: '{$OneName}'" );
				}

				$this->ShortName = $OneName[ 1 ];
			}
			else
			{
				throw new \InvalidArgumentException( "Invalid argument template: '{$OneName}'" );
			}
		}
	}

	public function ToTemplateString() : string
	{
		$String = '';

		if( $this->ShortName !== null )
		{
			$String = "-$this->ShortName";
		}

		if( $this->LongName !== null )
		{
			if( $this->ShortName !== null )
			{
				$String .= '|';
			}

			$String .= "--$this->LongName";
		}

		return $String;
	}
}
