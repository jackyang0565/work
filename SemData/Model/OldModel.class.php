<?php
namespace SemData\Model;
use Common\Model\TbsModel;
class OldModel extends TbsModel {
	
	public function __construct()
	{
		$this->db(1,"DB_CONFIG_OLD");
	}
}