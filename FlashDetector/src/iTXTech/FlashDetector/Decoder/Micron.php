<?php

/*
 * iTXTech FlashDetector
 *
 * Copyright (C) 2018 iTX Technologies
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace iTXTech\FlashDetector\Decoder;

use iTXTech\FlashDetector\FlashInfo;
use iTXTech\FlashDetector\Property\Classification;
use iTXTech\FlashDetector\Property\FlashInterface;
use iTXTech\SimpleFramework\Util\StringUtil;

class Micron extends Decoder{
	public static function getName() : string{
		return "Micron";
	}

	public static function check(string $partNumber) : bool{
		if(StringUtil::startsWith($partNumber, "MT")){
			return true;
		}
		return false;
	}

	public static function decode(string $partNumber) : FlashInfo{
		$flashInfo = (new FlashInfo($partNumber))->setManufacturer(self::getName());
		$partNumber = substr($partNumber, 2);//remove MT
		$flashInfo
			->setType(self::getOrDefault(self::shiftChars($partNumber, 3), [
				"29F" => "NAND Flash",
				"29E" => "Enterprise NAND Flash"
			]))
			->setDensity(self::matchFromStart($partNumber, [
				"1G" => "1Gb",
				"2G" => "2Gb",
				"4G" => "4Gb",
				"8G" => "8Gb",
				"16G" => "16Gb",
				"32G" => "32Gb",
				"64G" => "64Gb",
				"128G" => "128Gb",
				"256G" => "256Gb",
				"384G" => "384Gb",
				"512G" => "512Gb",
				"1T" => "1024Gb",
				"1T2" => "1152Gb",
				"1HT" => "1536Gb",
				"2T" => "2048Gb",
				"3T" => "3072Gb",
				"4T" => "4096Gb",
				"6T" => "6144Gb",
			]))
			->setDeviceWidth(self::getOrDefault(self::shiftChars($partNumber, 2), [
				"01" => "1 bit",
				"08" => "8 bits",
				"16" => "16 bits"
			]))
			->setCellLevel(self::getOrDefault(self::shiftChars($partNumber, 1), [
				"A" => "SLC",
				"C" => "MLC-2",
				"E" => "MLC-3 (TLC)"
				//TODO: QLC
			]));

		$classification = self::getOrDefault(self::shiftChars($partNumber, 1), [
			"A" => [1, 0, 0, 1],
			"B" => [1, 1, 1, 1],
			"D" => [2, 1, 1, 1],
			"E" => [2, 2, 2, 2],
			"F" => [2, 2, 2, 1],
			"G" => [3, 3, 3, 3],
			"J" => [4, 2, 2, 1],
			"K" => [4, 2, 2, 2],
			"L" => [4, 4, 4, 4],
			"M" => [4, 4, 4, 2],
			"Q" => [8, 4, 4, 4],
			"R" => [8, 2, 2, 2],
			"T" => [16, 8, 4, 2],
			"U" => [8, 4, 4, 2],
			"V" => [16, 8, 4, 4]
		], [0, 0, 0, 0]);

		$flashInfo->setClassification(new Classification(
			$classification[1], $classification[3], $classification[2], $classification[0]))
			->setVoltage(self::getOrDefault(self::shiftChars($partNumber, 1), [
				"A" => "Vcc: 3.3V (2.70–3.60V), VccQ: 3.3V (2.70–3.60V)",
				"B" => "1.8V (1.70–1.95V)",
				"C" => "Vcc: 3.3V (2.70–3.60V), VccQ: 1.8V (1.70–1.95V)",
				"E" => "Vcc: 3.3V (2.70–3.60V), VccQ: 3.3V (2.70–3.60V)",
				"F" => "Vcc: 3.3V (2.50–3.60V), VccQ: 1.2V (1.14–1.26V)",
				"G" => "Vcc: 3.3V (2.60–3.60V) , VccQ: 1.8V (1.70–1.95V)",
				"H" => "Vcc: 3.3V (2.50–3.60V), VccQ: 1.2V (1.14–1.26) or 1.8V (1.70–1.95V)",
				"J" => "Vcc: 3.3V (2.50–3.60V), VcQ: 1.8V (1.70–1.95V)",
				"K" => "Vcc: 3.3V (2.60–3.60V), VccQ: 3.3V (2.60–3.60V)",
				"L" => "Vcc: 3.3V (2.60–3.60V), VccQ: 3.3V (2.60–3.60V)",
			]))
			->setGeneration(self::getOrDefault(self::shiftChars($partNumber, 1), [
				"A" => 1,
				"B" => 2,
				"C" => 3,
				"D" => 4
			]))
			->setInterface(self::getOrDefault(self::shiftChars($partNumber, 1), [
				"A" => (new FlashInterface(false))->setAsync(true),
				"B" => (new FlashInterface(false))->setAsync(true)->setSync(true),
				"C" => (new FlashInterface(false))->setSync(true),
				"D" => (new FlashInterface(false))->setSpi(true)
			], new FlashInterface(false)));
		//ignoring package

		return $flashInfo;
	}
}
