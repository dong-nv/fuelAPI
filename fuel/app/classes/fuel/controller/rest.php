<?php


class Controller_Rest extends \Fuel\Core\Controller_Rest {

	/**
	 * Response
	 *
	 * Takes pure data and optionally a status code, then creates the response
	 *
	 * @param   mixed
	 * @param   int
	 * @return  object  Response instance
	 */
	protected function response($data = array(), $http_status = null)
	{
		// set the correct response header
		if (method_exists('Format', 'to_'.$this->format))
		{
			$this->response->set_header('Content-Type', $this->_supported_formats[$this->format]);
		}

		// no data returned?
		if ((is_array($data) and empty($data)) or ($data == ''))
		{
			// override the http status with the NO CONTENT status
			$http_status = $this->no_data_status;
		}

		// make sure we have a valid return status
		$http_status or $http_status = $this->http_status;

		// If the format method exists, call and return the output in that format
		if (method_exists('Format', 'to_'.$this->format))
		{
			// Handle XML output
			if ($this->format === 'xml')
			{
				// Detect basenode
				$xml_basenode = $this->xml_basenode;
				$xml_basenode or $xml_basenode = \Config::get('rest.xml_basenode', 'xml');

				// 結果が暗号化対象か？
				if(Config::get('common.cipher.result.flag')){
					// 暗号化する
					$enc = $this->encryption->encode_binary(\Format::forge($data)->{'to_'.$this->format}(null, null, $xml_basenode),Config::get('common.cipher.result.key'));
					$this->response->body($enc);
				}else{
					// 暗号化しない
					// Set the XML response
					$this->response->body(\Format::forge($data)->{'to_'.$this->format}(null, null, $xml_basenode));
				}
			}
			else
			{
				// 結果が暗号化対象か？
				if(Config::get('common.cipher.result.flag')){
					// 暗号化する
					$enc = $this->encryption->encode_binary(\Format::forge($data)->{'to_'.$this->format}(),Config::get('common.cipher.result.key'));
					$this->response->body($enc);
				}else{
					// 暗号化しない
					// Set the formatted response
					$this->response->body(\Format::forge($data)->{'to_'.$this->format}());
				}

			}
		}

		// Format not supported, but the output is an array or an object that can not be cast to string
		elseif (is_array($data) or (is_object($data) and ! method_exists($data, '__toString')))
		{
			if (\Fuel::$env == \Fuel::PRODUCTION)
			{
				// not acceptable in production
				if ($http_status == 200)
				{	$http_status = 406;
				}
				$this->response->body('The requested REST method returned an array or object, which is not compatible with the output format "'.$this->format.'"');
			}
			else
			{
				// convert it to json so we can at least read it while we're developing
				$this->response->body('The requested REST method returned an array or object:<br /><br />'.\Format::forge($data)->to_json(null, true));
			}
		}

		// Format not supported, output directly
		else
		{
			$this->response->body($data);
		}

		// Set the reponse http status
		$http_status and $this->response->status = $http_status;

		return $this->response;
	}

}
