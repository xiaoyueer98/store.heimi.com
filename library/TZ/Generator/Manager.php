<?php
/**
 * Thrift id maker
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2013-3-25
 */
class TZ_Generator_Manager
{
	/**
     * sms_send
     *
     * @param string $phone
     * @param string $message
     * @return boolean
     */
	static public function create()
	{
		if (!extension_loaded('thrift_protocol'))
			throw new Exception('Thrift_protocol extension not found.');
		
		$generatorConfig = Yaf_Registry::get('config')->generator;
		if (empty($generatorConfig))
			return false;

		try {
			$socket = new Thrift\Transport\TSocket($generatorConfig->host, $generatorConfig->port);
			$transport = new Thrift\Transport\TFramedTransport($socket);
			$protocol = new Thrift\Protocol\TBinaryProtocol($transport);
			Yaf_Loader::import(__DIR__.'/gen-php/generator_service.php');
			$client = new generator_serviceClient($protocol);
			$transport->open();
			$uid = $client->gen_id();
			$transport->close();
			return $uid;
		} catch (Thrift\Exception\TException $tx) {
			throw new Exception($tx->getMessage());	
		}

	}
}
