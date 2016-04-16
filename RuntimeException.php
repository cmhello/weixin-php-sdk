<?php

namespace WeiXin;

class  RuntimeException extends \Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}