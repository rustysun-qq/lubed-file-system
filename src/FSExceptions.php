<?php
namespace Lubed\FileSystem;

use Lubed\Exceptions\RuntimeException;

final class FSExceptions
{
	const FILE_NOT_FOUND=101501;
	const INVALID_ARGUMENT=101502;
	const INVALID_RESOURCE=101503;

	public static function fileNotFound(string $msg,array $options=[]):RuntimeException
	{
		throw new RuntimeException(self::FILE_NOT_FOUND,$msg,$options);
	}

	public static function invalidArgument(string $msg,array $options=[]):RuntimeException
	{
		throw new RuntimeException(self::INVALID_ARGUMENT,$msg,$options);
	}

	public static function invalidResource(string $msg,array $options=[]):RuntimeException
	{
		throw new RuntimeException(self::INVALID_RESOURCE,$msg,$options);
	}
}
