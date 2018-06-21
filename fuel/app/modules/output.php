<?php

class Output extends \Controller_Rest {

	var $api_status			= "NG";
	var $api_status_code		= 1;
	var $api_transition_code	= 1;
	var $api_message			= "";
	var $access_time			= "";
	var $api_result			= array();

	var $data_version_asset;	// Asset
	var $data_version_data;	// data

	var $Rest;

	function __construct(&$rest){
		// 初期化

		$this->Rest = $rest;

		$this->api_status				= "NG";
		$this->api_status_code			= 1;
		$this->api_transition_code	= 1;
		$this->api_message				= "";
		$this->access_time				= "";
		$this->api_result				= array();

		$this->data_version_asset		= 1;
		$this->data_version_data		= 1;

	}

	function API($api_status="NG",$api_status_code=1,$api_transition_code=1,$api_message="",$access_time="",$version_asset=1,$version_data=1,$api_result=array()){
		//// 出力処理

		$this->api_status				= $api_status;
		$this->api_status_code			= $api_status_code;
		$this->api_transition_code	= $api_transition_code;
		$this->api_message				= $api_message;
		$this->access_time				= $access_time;
		$this->api_result				= $api_result;

		$this->data_version_asset		= $version_asset;
		$this->data_version_data		= $version_data;

		$this->responseAPI();
	}

	function responseAPI(){
		//// 実際の出力処理

		$api_array = array();
		if(Config::get("common.response.api_status",true)){
			$api_array = $api_array + array('api_status'			=> $this->api_status);
		}
		if(Config::get("common.response.api_status_code",true)){
			$api_array = $api_array + array('api_status_code'		=> $this->api_status_code);
		}
		if(Config::get("common.response.api_transistion_code",true)){
			$api_array = $api_array + array('api_transistion_code'	=> $this->api_transition_code);
		}
		if(Config::get("common.response.api_message",true)){
			$api_array = $api_array + array('api_message'			=> $this->api_message);
		}
		if(Config::get("common.response.access_time",true)){
			if(strlen($this->access_time) != 0){
				$api_array = $api_array + array('access_time'		=> $this->access_time);
			}else{
				$api_array = $api_array + array('access_time'		=> date('Y-m-d H:i:s', time()));
			}
		}

		// データバージョン関連
		if(Config::get("common.response.ver_asset",true)){
			$api_array = $api_array + array('ver_asset'		=> (int)$this->data_version_asset);
		}
		if(Config::get("common.response.ver_data",true)){
			$api_array = $api_array + array('ver_data'		=> (int)$this->data_version_data);
		}

		// 出力データがある場合はresultも追加
		if(count($this->api_result) != 0){
			if(Config::get("common.response.result_class",true)){
				$api_array = $api_array + array('result'		=> $this->api_result); // もし階層を下げたいならコチラを有効に
			}else{
				$api_array = $api_array + $this->api_result;
			}
		}

		// ここからは$formatに従い、適切な形式で出力される
		return $this->Rest->response($api_array);
	}
	
}

?>
