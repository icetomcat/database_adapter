<?php

class VersionCompareTest extends PHPUnit_Framework_TestCase
{

	public function testVersion()
	{
		$this->assertEquals(version_compare("5.5.0", "5.7.0" , "<"), 1);
	}

}
