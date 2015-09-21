<?php
/**
 * unique_users model class
 *
 * @author vincent <vincent@747.cn>
 * @final 2013-3-25
 */
class UniqueUsersModel extends TZ_Db_Table
{
	//construct
	public function __construct()
	{
		parent::__construct(Yaf_Registry::get('user_db'), 'user_db.unique_users');
	}
}
