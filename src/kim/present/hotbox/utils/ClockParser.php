<?php

/*
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0.0
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\hotbox\utils;

class ClockParser{
	/**
	 * RegExp pattern for parse clock string
	 * ex) "10:00", "23:59:59"
	 */
	public const PATTERN = "/^(\d+)(?:[:-](\d+))?(?:[:-](\d+))?$/";

	/**
	 * Constants for ClockParser::parse()
	 */
	public const HOUR = 0;
	public const MINUTE = 1;
	public const SECOND = 2;

	private const DEFAULT_RESULT = [
		self::HOUR => 0,
		self::MINUTE => 0,
		self::SECOND => 0
	];

	/**
	 * Constants for ClockParser::parseToTimestamp()
	 */
	public const SECOND_MULTIPLE = 1;
	public const MINUTE_MULTIPLE = self::SECOND_MULTIPLE * 60;
	public const HOUR_MULTIPLE = self::MINUTE_MULTIPLE * 60;


	/**
	 * Parse string to array of time parts
	 *
	 * @param string $str
	 *
	 * @return int[]|null
	 */
	public static function parse(string $str) : ?array{
		if(!preg_match(self::PATTERN, $str, $match)){
			return null;
		}
		$result = self::DEFAULT_RESULT;
		if(($count = count($match)) === 2){
			$result[self::SECOND] = (int) $match[1];
		}elseif($count === 3){
			$result[self::MINUTE] = (int) $match[1];
			$result[self::SECOND] = (int) $match[2];
		}elseif($count === 4){
			$result[self::HOUR] = (int) $match[1];
			$result[self::MINUTE] = (int) $match[2];
			$result[self::SECOND] = (int) $match[3];
		}
		return $result;
	}

	/**
	 * Convert array of time parts to timestamp
	 *
	 * @param int[] $timeArr
	 *
	 * @return int
	 */
	public static function toTimestamp(array $timeArr) : int{
		return $timeArr[self::SECOND] * self::SECOND_MULTIPLE
			+ $timeArr[self::MINUTE] * self::MINUTE_MULTIPLE
			+ $timeArr[self::HOUR] * self::HOUR_MULTIPLE;
	}
}