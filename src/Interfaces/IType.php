<?php

namespace Database\Interfaces;

interface IType
{

	function getName();

	function getType();

	function getLength();

	function getIndex();

	function isNull();

	function getCollate();

	function getDefault();

	function isAutoIncrement();

	function getAttribute();
}
