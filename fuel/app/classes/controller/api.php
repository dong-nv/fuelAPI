<?php
/**
 * API共通コントローラ
 *
 * 各APIで最低限必要な処理を纏めています。
 * 他のAPIはこのコントローラを継承して作成します。
 *
 * @access	public
 */

// パフォーマンス計測用プロファイラ
\Autoloader::add_class('Profiler',APPPATH.'classes/profiler.php');

// グローバル変数クラス
\Autoloader::add_class('GlobalVariable',APPPATH.'modules/game/GlobalVariable.php');

// 暗号化
\Autoloader::add_class('SimpleCipher',APPPATH.'modules/SimpleCipher.php');

// データ出力
\Autoloader::add_class('Output',APPPATH.'modules/output.php');

// コモン
\Autoloader::add_class('Common',APPPATH.'classes/base/common.php');

// 定数
\Autoloader::add_class('Constant',APPPATH.'modules/game/Const.php');

// ユーザ
\Autoloader::add_class('User',APPPATH.'modules/game/User.php');

// ログインボーナス
\Autoloader::add_class('LoginBonus',APPPATH.'modules/game/LoginBonus.php');

//// ゲーム用クラス


class Controller_Api extends Controller_Rest{

	/**
	 * @protected array サポートする出力形式に対するContent-Type
	 */
	protected $_supported_formats = array(
		'html' => 'text/html',
		'json' => 'application/json',			// json
		'mpk' => 'application/octet-stream',	// MessagePack
	);

	/**
	 * @var string 出力データ形式
	 */
	var $format					= 'mpk';

	/**
	 * @var string エラーメッセージ
	 */
	var $error_msg				= "";

	// API出力関連
	/**
	 * @var string APIステータス文字列<br>
	 * OK:正常 NG:エラー MAINTENANCE:メンテナンス DATAVER:データバージョンアップが必要 APPVER:アプリバージョンアップが必要 CLOSE:終了
	 */
	var $api_status			= "OK";
	/**
	 * @var int APIステータスコード<br>
	 * 0:OK 1:NG 2:認証エラー 3:アカウントエラー　6:データバージョンアップが必要 7:バージョンアップが必要 8:不明なAPI 9:メンテナンス 10:終了(URLなし) 11:終了(URLあり)
	 */
	var $api_status_code		= 0;
	/**
	 * @var int アプリ遷移制御コード<br>
	 * 1:次画面 2:前画面 3:タイトル画面 4:ホーム画面
	 */
	var $api_transition_code	= 1;
	/**
	 * @var string API出力メッセージ
	 */
	var $api_message			= "";
	/**
	 * @var string APIアクセス時間
	 */
	var $api_access_time		= "";
	/**
	 * @var array API出力データ
	 */
	var $api_result			= array();

	/**
	 * @var int time()の値(UNIXTIME)
	 */
	var $time					= "";
	/**
	 * @var string Y-m-d形式の値
	 */
	var $ymd					= "";
	/**
	 * @var string Y-m-d H:i:s形式の値
	 */
	var $ymdhis					= "";

	/**
	 * @var string ISO-8601形式の年
	 */
	var $week_year				= 0;
	/**
	 * @var string ISO-8601形式の週番号
	 */
	var $week_no				= 0;
	/**
	 * @var string ISO-8601形式の曜日番号 1:月曜 7:日曜
	 */
	var $day_no					= 0;

	/**
	 * @var array 出力用インスタンス
	 */
	var $output = array();

	/**
	 * @var array 暗号化モジュールインスタンス
	 */
	var $encryption = array();

	/**
	 * @var array コモンインスタンス
	 */
	var $common = array();

	/**
	 * @var array ユーザ基底インスタンス
	 */
	var $base_user = array();


	/**
	 * @var array POSTされたデータ配列 <br>Arr::get() で取得して下さい。
	 */
	var $in_post = array();
	/**
	 * @var array GETされたデータ配列 <br>Arr::get() で取得して下さい。
	 */
	var $in_get = array();
	/**
	 * @var array GET内のparamデータ配列 <br>Arr::get() で取得して下さい。
	 */
	var $in_param = array();

	/**
	 * @var string GETされたtra_id変数
	 */
	var $tran_id = "";

	/**
	 * @var boolean before()でエラーが発生したかフラグ
	 */
	var $before_error		= false;

	/**
	 * @var boolean ユーザ認証が必要か判別フラグ<br>(config/api.php で除外設定をする)
	 */
	var $user_auth			= true;

	// ユーザ情報
	/**
	 * @var int MID
	 */
	var $MID				= 0;  // 必ず0に戻すこと
	/**
	 * @var string ucode
	 */
	var $ucode				= "";
	/**
	 * @var string token
	 */
	var $token				= "";
	/**
	 * @var string ユーザ名
	 */
	var $NickName			= "";
	/**
	 * @var array ユーザデータ
	 */
	var $User				= array();

	/**
	 * @var string アプリアクセス時間
	 */
	var $send_time			= "";

	// アプリのバージョン
	/**
	 * @var string アプリバージョン文字列
	 */
	var $app_version;
	/**
	 * @var string プラットフォーム文字列<br>GooglePlay AppStore DMM_App DMM_App18 DMM_WebGL DMM_WebGL18
	 */
	var $app_platform;
	/**
	 * @var int プラットフォーム判別ID<br>1:GooglePlay 2:AppStore 3:DMM_App 4:DMM_App18 5:DMM_WebGL 6:DMM_WebGL18
	 */
	var $app_platform_id;
	/**
	 * @var int アプリバージョン(Major)
	 */
	var $app_version_major;
	/**
	 * @var int アプリバージョン(Minor)
	 */
	var $app_version_minor;
	/**
	 * @var int アプリバージョン(Build)
	 */
	var $app_version_build;

	// アプリが所持しているデータファイルバージョン
	/**
	 * @var int アプリ内アセットマスターバージョン
	 */
	var $app_data_version_asset;
	/**
	 * @var int アプリ内データマスターバージョン
	 */
	var $app_data_version_data;

	// データファイルバージョン
	/**
	 * @var int サーバ内アセットマスターバージョン
	 */
	var $data_version_asset;
	/**
	 * @var int サーバ内データマスターバージョン
	 */
	var $data_version_data;

	// パフォーマンス計測
	/**
	 * @var int パフォーマンス計測用
	 */
	var $time_start	= 0;
	/**
	 * @var int パフォーマンス計測用
	 */
	var $time_end		= 0;
	/**
	 * @var boolen ユーザデータ出力設定
	 */
	var $output_user_data	= FALSE;
	/**
	 * @var boolen ユーザキャラクター一覧出力設定
	 */
	var $output_user_character_list	= FALSE;
	/**
	 * @var boolen ユーザアイテム一覧出力設定
	 */
	var $output_user_item_list	= FALSE;
	/**
	 * @var boolen ログインボーナスフラグ出力設定
	 */
	var $output_is_loginbonus	= FALSE;
	
	/**
	 * @var boolen 常設テロップ一覧出力設定
	 */
	var $output_master_telop_list	= FALSE;
	/**
	 * @var boolen 緊急テロップ一覧出力設定
	 */
	var $output_telop_list	= TRUE;
	/**
	 * @var string APIのURI
	 */
	var $uri					= "";
	/**
	 * @var boolean ユーザ認証除外フラグ
	 */
	var $exclusion_user_auth	= FALSE;
	/**
	 * @var boolean アプリバージョンチェック除外フラグ
	 */
	var $exclusion_app_ver		= FALSE;
	/**
	 * @var boolean アセットバージョンチェック除外フラグ
	 */
	var $exclusion_asset_ver	= FALSE;
	/**
	 * @var boolean データバージョンチェック除外フラグ
	 */
	var $exclusion_data_ver		= FALSE;
	/**
	 * @var boolean デバッグログ除外フラグ
	 */
	var $exclusion_debug_log		= FALSE;



	/**
	 * @const string ユーザデータ出力設定 変数
	 */
	const OUTPUT_USER_DATA_PARAM	= 'status';
	/**
	 * @const string ユーザデータ出力設定 判定値
	 */
	const OUTPUT_USER_DATA_VALUE = 'on';
	/**
	 * @const string ユーザデータ出力設定 出力配列名
	 */
	const OUTPUT_USER_DATA_ARRAY	= 'user_data';
	/**
	 * @const string ユーザキャラクター一覧出力設定 出力配列名
	 */
	const OUTPUT_USER_CHARACTER_LIST_ARRAY	= 'character_list';
	/**
	 * @const string ユーザアイテム一覧出力設定 出力配列名
	 */
	const OUTPUT_USER_ITEM_LIST_ARRAY	= 'item_list';
	/**
	 * @const string  ログインボーナスフラグ出力名
	 */
	const OUTPUT_IS_LOGINBONUS	= 'is_loginbonus';
	/**
	 * @const string テロップ一覧出力設定 出力配列名
	 */
	const OUTPUT_MASTER_TELOP_LIST_ARRAY	= 'master_telop_list';	
	/**
	 * @const string テロップ一覧出力設定 出力配列名
	 */
	const OUTPUT_TELOP_LIST_ARRAY	= 'telop_list';	
	/**
	 * @const string 出力データ形式(既定値)
	 */
	const DEFAULT_FORMAT = 'mpk';
	
	/**
	 * @const string GET変数名 GET変数の暗号化用param
	 */
	const GET_ENCRYPTION_PARAM		= "param";
	/**
	 * @const string GET変数名 GET変数のtran_id
	 */
	const GET_TRAN_ID				= "tid";

	/**
	 * @const string ヘッダ名 アプリバージョン
	 */
	const HEADER_APP_VERSION		= "X-APP-VERSION";
	/**
	 * @const string ヘッダ名 アプリプラットフォーム
	 */
	const HEADER_APP_PLATFORM		= "X-APP-PLATFORM";
	/**
	 * @const string ヘッダ名 出力フォーマット
	 */
	const HEADER_APP_FORMAT		= "X-APP-FORMAT";
	/**
	 * @const string ヘッダ名 アセットバージョン
	 */
	const HEADER_APP_ASSET_VER	= "X-APP-AVER";
	/**
	 * @const string ヘッダ名 データバージョン
	 */
	const HEADER_APP_DATA_VER		= "X-APP-DVER";
	/**
	 * @const string ヘッダ名 ユーザ識別子
	 */
	const HEADER_APP_UCODE		= "X-APP-UCODE";
	/**
	 * @const string ヘッダ名 ユーザトークン
	 */
	const HEADER_APP_TOKEN		= "X-APP-TOKEN";

	/**
	 * @const string APIステータス 正常
	 */
	const API_STATUS_OK			= "OK";
	/**
	 * @const string APIステータス システム側でのエラー
	 */
	const API_STATUS_ERROR		= "ERROR";
	/**
	 * @const string APIステータス コントローラ側でのエラー
	 */
	const API_STATUS_NG			= "NG";
	/**
	 * @const string APIステータス メンテナンス
	 */
	const API_STATUS_MAINTENANCE	= "MAINTENANCE";
	/**
	 * @const string APIステータス データファイルのバージョンアップが必要
	 */
	const API_STATUS_DATA_VER		= "DATAVER";
	/**
	 * @const string APIステータス アプリのバージョンアップが必要
	 */
	const API_STATUS_APP_VER		= "APPVER";
	/**
	 * @const string APIステータス アプリの終了
	 */
	const API_STATUS_CLOSE		= "CLOSE";

	/**
	 * @const int APIステータスコード 正常
	 */
	const API_STATUS_CODE_OK				= 0;
	/**
	 * @const int APIステータスコード エラー
	 */
	const API_STATUS_CODE_ERROR				= 1;
	/**
	 * @const int APIステータスコード 認証エラー
	 */
	const API_STATUS_CODE_AUTH_ERROR		= 2;
	/**
	 * @const int APIステータスコード アカウントエラー(垢BAN等)
	 */
	const API_STATUS_CODE_BAN				= 3;
	/**
	 * @const int APIステータスコード データファイルのバージョンアップが必要
	 */
	const API_STATUS_CODE_DATAVER			= 6;
	/**
	 * @const int APIステータスコード アプリのバージョンアップが必要
	 */
	const API_STATUS_CODE_APPVER			= 7;
	/**
	 * @const int APIステータスコード 未定義API
	 */
	const API_STATUS_CODE_UNDEFINED			= 8;
	/**
	 * @const int APIステータスコード メンテナンス
	 */
	const API_STATUS_CODE_MAINTENANCE		= 9;
	/**
	 * @const int APIステータスコード バリデーションエラー
	 */
	const API_STATUS_CODE_VALIDATION_ERROR	= 10;
	/**
	 * @const int APIステータスコード リロード
	 */
	const API_STATUS_CODE_RELOAD	= 11;

	/**
	 * @const int 遷移コード 次画面へ
	 */
	const API_TRANSITION_NEXT				= 1;
	/**
	 * @const int 遷移コード 前画面へ
	 */
	const API_TRANSITION_BACK				= 2;
	/**
	 * @const int 遷移コード タイトル画面へ
	 */
	const API_TRANSITION_TITLE			= 3;
	/**
	 * @const int 遷移コード ホーム画面へ
	 */
	const API_TRANSITION_HOME				= 4;

	/**
	 * @const int プラットフォームコード
	 */
	const PLATFORM_GOOGLE_PLAY	= 1;
	const PLATFORM_APP_STORE		= 2;
	const PLATFORM_DMM_APP		= 3;
	const PLATFORM_DMM_APP18		= 4;
	const PLATFORM_DMM_WEBGL		= 5;
	const PLATFORM_DMM_WEBGL18	= 6;

	/**
	 * @const string プラットフォーム識別子
	 */
	const PLATFORM_TEXT_GOOGLE_PLAY	= "GooglePlay";
	const PLATFORM_TEXT_APP_STORE		= "AppStore";
	const PLATFORM_TEXT_DMM_APP		= "DMM_App";
	const PLATFORM_TEXT_DMM_APP18		= "DMM_App18";
	const PLATFORM_TEXT_DMM_WEBGL		= "DMM_WebGL";
	const PLATFORM_TEXT_DMM_WEBGL18	= "DMM_WebGL18";

	/**
	 * @const array プラットフォーム関連付け
	 */
	const PLATFORM_RELATION = array(
		self::PLATFORM_TEXT_GOOGLE_PLAY	=> self::PLATFORM_GOOGLE_PLAY,
		self::PLATFORM_TEXT_APP_STORE		=> self::PLATFORM_APP_STORE,
		self::PLATFORM_TEXT_DMM_APP		=> self::PLATFORM_DMM_APP,
		self::PLATFORM_TEXT_DMM_APP18		=> self::PLATFORM_DMM_APP18,
		self::PLATFORM_TEXT_DMM_WEBGL		=> self::PLATFORM_DMM_WEBGL,
		self::PLATFORM_TEXT_DMM_WEBGL18	=> self::PLATFORM_DMM_WEBGL18,
	);
	
	/**
	 * @const string APIコントローラ名 ログインボーナス
	 */
	const API_CONTROLLER_NAME_LOGINBONUS	= 'Controller_Api_Common_Loginbonus';

	/**
	 *  コントローラで1番最初に呼ばれる
	 *
	 *  全てのAPIの事前処理
	 *
	 * @access	public
	 * @param	なし
	 * @return	なし
	*/
	public function before(){

		//// 計測開始
		$this->time_start = microtime(true);

		//// extendsしてるなら必須
		parent::before();

		//// 初期化
		$this->time		= GlobalVariable::getTime();
		$this->ymd		= GlobalVariable::getYmd();
		$this->ymdhis	= GlobalVariable::getYmdHis();

		$this->common		= new Common($this->time);
		$this->week_year	= $this->common->getWeekYear();
		$this->week_no		= $this->common->getWeekNo();
		$this->day_no		= $this->common->getDayNo();

		$this->uri		= Uri::string();

		$this->api_access_time	= GlobalVariable::getYmdHis();
		$this->output			= new Output($this);

		$this->encryption		= new SimpleCipher();
		$BaseUser				= new Base_User(); // ユーザ基底クラス
		$BaseAccessLog			= new Base_AccessLog(); // アクセスログ基底クラス

		$this->base_user		= $BaseUser;

		//// メンテナンス状態なら、このタイミングでメンテ情報を返す
		if(Config::get('maintenance.maintenance.flag')){

			$this->api_status				= self::API_STATUS_MAINTENANCE;
			$this->api_status_code			= self::API_STATUS_CODE_MAINTENANCE;
			$this->api_transition_code	= self::API_TRANSITION_TITLE;
			$this->api_message				= Config::get('maintenance.maintenance.message');
			$this->before_error			= true;

			return;
		}

		//// 除外設定読み込み
		$this->exclusion_user_auth	= Config::get('api.exclusion.user_auth.'.$this->uri,false);
		$this->exclusion_app_ver		= Config::get('api.exclusion.app_ver.'.$this->uri,false);
		$this->exclusion_asset_ver	= Config::get('api.exclusion.asset_ver.'.$this->uri,false);
		$this->exclusion_data_ver		= Config::get('api.exclusion.data_ver.'.$this->uri,false);

		$this->exclusion_debug_log	= Config::get('api.exclusion.debug_log.'.$this->uri,false);

		//// アクセスしたAPIのユーザ認証必要状態を設定
		// 明示的にtrue設定されてない/falseに設定されてる場合は認証必須とする
		if(!$this->exclusion_user_auth){
			// ユーザ認証が必要
			$this->user_auth = true;
		}else{
			// ユーザ認証は不要
			$this->user_auth = false;
		}

		//// これまでの間にエラーが有った場合は下記の処理は飛ばす
		if(!$this->before_error){
			// エラーが無かったので処理を続ける

			//// 出力フォーマットの設定
			if(Config::get('common.cipher.header.flag')){
				// 暗号化されてる
				$tmp = Input::headers(self::HEADER_APP_FORMAT, '');
				$app_format = $this->encryption->decode($tmp, Config::get('common.cipher.header.key'));
				if(strlen($app_format) == 0){
					$app_format = self::DEFAULT_FORMAT;
				}
			}else{
				$app_format = Input::headers(self::HEADER_APP_FORMAT, self::DEFAULT_FORMAT);
			}
			// 出力フォーマットのチェック
			if(!Config::get('common.format.'.$app_format,false)){
				// 定義されてない/falseとなってたので既定値で
				$app_format = self::DEFAULT_FORMAT;
			}
			$this->format = $app_format;

			//// プラットフォーム
			if(Config::get('common.cipher.header.flag')){
				// 暗号化されてる
				$tmp = Input::headers(self::HEADER_APP_PLATFORM, ''); //
				$app_platform = $this->encryption->decode($tmp, Config::get('common.cipher.header.key'));
				$this->app_platform = $app_platform;

			}else{
				// 暗号化してない
				$app_platform = Input::headers(self::HEADER_APP_PLATFORM, ''); //
				$this->app_platform = $app_platform;

			}
			// 定義チェック
			if(Arr::get(self::PLATFORM_RELATION,$app_platform,0) == 0){
				$app_platform = "";
			}

			// 複合化失敗 or 未定義
			if(strlen($app_platform) == 0){
				$this->api_status				= self::API_STATUS_ERROR;
				$this->api_status_code			= self::API_STATUS_CODE_ERROR;
				$this->api_transition_code	= self::API_TRANSITION_TITLE;
				$this->api_message				= __('Common.ERROR_Platform');
				$this->before_error			= true;
				return;
			}

			// platformからplatform_idを設定
			$this->app_platform_id = self::PLATFORM_RELATION[$app_platform];

			//// トランザクションID
			$this->tran_id = Input::get(self::GET_TRAN_ID, "");

			//// アプリバージョン
			$app_version = Input::headers(self::HEADER_APP_VERSION, ''); //
			if($app_version == "" ){
				// HTTPヘッダには無かったので、POSTをチェック
				$app_version = Input::post(self::HEADER_APP_VERSION, '0.0.0'); //
			}
			if(strlen($app_version) == 1){
				$app_version = "0.0.0";
			}
			$this->app_version = $app_version;
			// アプリバージョンを分離
			$tmp = array();
			$tmp = explode(".",$app_version);
			// メジャー
			$this->app_version_major = $tmp[0];
			// マイナー
			$this->app_version_minor = $tmp[1];
			// ビルド
			$this->app_version_build = $tmp[2];

			//// アプリ内データバージョン
			$this->app_data_version_asset = Input::headers(self::HEADER_APP_ASSET_VER, '0');	// Asset
			$this->app_data_version_data = Input::headers(self::HEADER_APP_DATA_VER, '0');	// データ

			//// サーバ内データバージョン
			$this->data_version_asset	= Config::get('asset.master_version',0);
			$this->data_version_data	= Config::get('data.master_version',0);

			//// アプリバージョンチェック
			// 明示的にtrue設定されてない/falseに設定されてる場合はチェック必須とする
			if($this->exclusion_app_ver === false){
				// 例外設定されてなかったのでチェック
				if($this->chkAppVersion($this->app_platform,$this->app_version_major,$this->app_version_minor,$this->app_version_build) == false){
					$this->api_status				= self::API_STATUS_APP_VER;
					$this->api_status_code			= self::API_STATUS_CODE_APPVER;
					$this->api_transition_code	= self::API_TRANSITION_TITLE;
					$this->api_message				= __("Common.ERROR_AppVer");
					$this->before_error			= true;
					return;
				}
			}

			//// アセットマスターバージョンをチェック
			// 明示的にtrue設定されてない/falseに設定されてる場合はチェック必須とする
			if($this->exclusion_asset_ver === false){
				// 例外設定されてなかったのでチェック
				if($this->data_version_asset != $this->app_data_version_asset){
					// サーバで管理しているマスターバージョンと異なる
					$this->api_status				= self::API_STATUS_DATA_VER;
					$this->api_status_code			= self::API_STATUS_CODE_DATAVER;
					$this->api_transition_code	= self::API_TRANSITION_TITLE;
					$this->api_message				= __("Common.ERROR_AssetVer")."(1)";
					$this->before_error			= true;
					return;
				}
			}

			//// アセットマスターバージョンをチェック
			// 明示的にtrue設定されてない/falseに設定されてる場合はチェック必須とする
			if($this->exclusion_data_ver === false){
				// 例外設定されてなかったのでチェック
				if($this->data_version_data != $this->app_data_version_data){
					// サーバで管理しているマスターバージョンと異なる
					$this->api_status				= self::API_STATUS_DATA_VER;
					$this->api_status_code			= self::API_STATUS_CODE_DATAVER;
					$this->api_transition_code	= self::API_TRANSITION_TITLE;
					$this->api_message				= __("Common.ERROR_AssetVer")."(2)";
					$this->before_error			= true;
					return;
				}
			}


			//// ユーザ認証が必須の場合の処理
			if($this->user_auth){
				//// ヘッダからtokenを取得
				$code1 = Input::headers(self::HEADER_APP_UCODE, ''); // ucode
				$code2 = Input::headers(self::HEADER_APP_TOKEN, ''); // token

				//// 最終的なチェック
				if($code1 == "" || $code2 == ""){
					$this->api_status				= self::API_STATUS_ERROR;
					$this->api_status_code			= self::API_STATUS_CODE_AUTH_ERROR; //
					$this->api_transition_code	= self::API_TRANSITION_TITLE;
					$this->api_message				= __('Common.ERROR_Auth')."(1)";
					$this->before_error			= true;
					return;
				}

				//// デコード
				if(Config::get('common.cipher.header.flag')){
					// 暗号化されてる
					$decode			= $this->encryption->decode($code2, Config::get('common.cipher.header.key'));
					$tmp_array		= explode(",", $decode );
					$send_time		= $tmp_array[0];
					$token			= $tmp_array[1];
				}else{
					// 暗号化してない
					$tmp_array		= explode(",", $code2 );
					$send_time		= $tmp_array[0];
					$token			= $tmp_array[1];
				}
				$ucode				= $code1;
				$this->ucode		= $ucode;
				$this->token		= $token;
				$this->send_time	= $send_time;

				//// デコードチェック
				if($ucode == "" || $token == ""){
					$this->api_status				= self::API_STATUS_ERROR;
					$this->api_status_code			= self::API_STATUS_CODE_AUTH_ERROR;
					$this->api_transition_code	= self::API_TRANSITION_TITLE;
					$this->api_message				= __('Common.ERROR_Auth')."(2)";
					$this->before_error			= true;
					return;
				}

				//// DBからユーザ検索
				Model_Crud::setSharding($ucode);
				$result = $BaseUser->_getUserMaster($ucode,$token,false);

				if(!$result){
					$this->api_status				= self::API_STATUS_ERROR;
					$this->api_status_code			= self::API_STATUS_CODE_AUTH_ERROR;
					$this->api_transition_code	= self::API_TRANSITION_TITLE;
					$this->api_message				= __('Common.ERROR_Auth')."(3)";
					$this->before_error			= true;
					return;
				}else{
					// 取得できたので、ユーザ情報をローカル変数にセット
					$this->User			= $result;
					$this->MID			= Arr::get($result,"mid",0);
					$this->ucode		= Arr::get($result,"ucode",0);
					$this->NickName	= Arr::get($result,"nickname","");
					$this->token		= $token;

					// ステータスチェック
					//　0:有効 1:無効 2:制限 9:垢BAN
					if($result["status"] == $BaseUser::USER_STATUS_INVALID){
						// 無効
						$this->api_status				= self::API_STATUS_ERROR;
						$this->api_status_code			= self::API_STATUS_CODE_BAN;
						$this->api_transition_code	= self::API_TRANSITION_TITLE;
						$this->api_message				= __("Common.ERROR_AccountInvalid",array("id"=>$ucode));
						$this->before_error			= true;
						return;
					}else if($result["status"] == $BaseUser::USER_STATUS_BAN){
						// 正常なBAN
						$this->api_status				= self::API_STATUS_ERROR;
						$this->api_status_code			= self::API_STATUS_CODE_BAN;
						$this->api_transition_code	= self::API_TRANSITION_TITLE;
						$this->api_message				= __("Common.ERROR_AccountBan1",array("id"=>$ucode));
						$this->before_error			= true;
						return;
					}elseif($result["status"] == $BaseUser::USER_STATUS_RESTRICTION){
						// 一時的に制限を掛けている
						$this->api_status				= self::API_STATUS_ERROR;
						$this->api_status_code			= self::API_STATUS_CODE_BAN;
						$this->api_transition_code	= self::API_TRANSITION_TITLE;
						$this->api_message				= __("Common.ERROR_AccountBan2",array("id"=>$ucode));
						$this->before_error			= true;
						return;
					}
				}

				//// 連続ログイン時間取得
				// 今日のデータを取得
				$today_date = $this->ymd;
				$access_log = $BaseAccessLog->getAccessLog($this->MID,$today_date);
				if($access_log){
					// 指定した日付のが存在したので、なにも無し
				}else{
					// 指定した日付のが存在し無かった

					// 前日を取得
					$latest_date = date("Y-m-d",strtotime("-1 day",$this->time));
					$latest_access_log = $BaseAccessLog->getAccessLog($this->MID,$latest_date);

					if($latest_access_log){
						// 前日が入ってたので、その日付＋１日目で新規作成
						$days = $latest_access_log["days"];
						$days++;
						$BaseAccessLog->updateAccessLog($this->MID, $today_date, $days, true);
					}else{
						// 前日が無かった、１日目として新規作成
						$days = 1;
						$BaseAccessLog->updateAccessLog($this->MID, $today_date, $days, true);
					}
				}

				//// 最終アクセス時間更新
				$User = new User();
				$User->updateLastAccessTime($this->MID, $this->ymdhis);

				//// 自動回復
				$User->autoRecover($this->MID, $this->time);

				//// ユーザデータ
				$this->User = $BaseUser->getUserMaster($this->MID,true);
				
				//// ログインボーナス
				if ($this->request->controller != self::API_CONTROLLER_NAME_LOGINBONUS){
					$LoginBonus = new LoginBonus();
					$this->output_is_loginbonus = $LoginBonus->isLoginBonus($this->MID);
				}
			}

			//// GETされたデータの取得
			$this->in_get = Input::get();

			//// GET内のparamの取得
			$param = Input::get(self::GET_ENCRYPTION_PARAM,null);
			if($param !== null){
				// paramが定義されていた
				if(Config::get('common.cipher.param.flag')){
					// param は暗号化されている
					$param_str = $this->encryption->decode($param, Config::get('common.cipher.post.key'));
				}else{
					$param_str = $param;
				}
				// paramの中を分解
				$param_key_list = array();
				if(0 < strlen($param_str)){
					$param_list = explode(',', $param_str);
					foreach ( $param_list as $key => $param_value ) {
						if(0 < strlen($param_value)){
							$tmp_list = explode(':', $param_value);
							$param_key_list[Arr::get($tmp_list,0,0)] = Arr::get($tmp_list,1,0);

						}
					}
				}
				$this->in_param = $param_key_list;

				// 最新のユーザデータが欲しい場合に、paramで指定した変数が一致した場合にafter()で出力する
				if(Arr::get($this->in_param,self::OUTPUT_USER_DATA_PARAM,"") === self::OUTPUT_USER_DATA_VALUE){
					$this->output_user_data = TRUE;
				}

			}

			//// POSTされたデータの取得
			if(Config::get('common.cipher.post.flag')){
				// POSTデータが暗号化されている

				$tmp = file_get_contents('php://input');

				// デコード
				$tmp = $this->encryption->decode_binary($tmp, Config::get('common.cipher.post.key'));

				if($this->format == "json"){
					// JSON
					$this->in_post = json_decode(trim($tmp),TRUE);
				}else if($this->format == "mpk"){
					// MessagePack
					$this->in_post = msgpack_unpack($tmp);
				}

			}else{
				// POSTデータは暗号化されてない
				if($this->format == "json"){
					// JSON
					$this->in_post = Input::json();
				}else if($this->format == "mpk"){
					// MessagePack
					$this->in_post = Input::mpk();
				}else{
					// その他
					$this->in_post = Input::post();
				}
			}

		}
	}

    /**
     *
	 * before()の次に呼ばれる処理
     *
     * @param string $method
     * @param string $params
    */
	public function router($method, $params){

		//// extendsしてるなら必須
		parent::router($method, $params);

		//// これまでの間(before)にエラーが有った場合は下記の処理は飛ばす
		if($this->before_error){
			// エラーが有ったのでrouterは処理しない
			return;
		}

		//// ユーザ認証が必須の場合の処理
		if($this->user_auth){
		}
	}

	/**
	 *
	 * action_の後に呼ばれる処理
	 *
	 * 全てのAPIの事後処理
	 *
	 * @access	public
	 * @param	$response
	 * @return	$response
	 */
	public function after($response) {

		//// extendsしてるなら必須
		$response = parent::after($response);

		//// 未定義のAPIへのアクセス (405エラー)
		if($response->status == 405){
			// 本来はbeforeで処理出来れば良いのだが、
			$this->api_status				= self::API_STATUS_ERROR;
			$this->api_status_code			= self::API_STATUS_CODE_UNDEFINED;
			$this->api_transition_code	= self::API_TRANSITION_TITLE;
			$this->api_message				= __('Common.ERROR_NotAPI');
			$this->before_error			= FALSE;
		}

		// ユーザデータを出力
		if($this->output_user_data){
			$tmp = $this->getMyUserData(TRUE);
			if($tmp !== FALSE){
				$this->api_result[self::OUTPUT_USER_DATA_ARRAY] = $tmp;
			}
		}

		if($this->output_user_character_list){
			$tmp = $this->getMyUserCharacterList();
			if($tmp !== FALSE){
				$this->api_result[self::OUTPUT_USER_CHARACTER_LIST_ARRAY] = $tmp;
			}
		}

		if($this->output_user_item_list){
			$tmp = $this->getMyUserItemList();
			if($tmp !== FALSE){
				$this->api_result[self::OUTPUT_USER_ITEM_LIST_ARRAY] = $tmp;
			}
		}
		
		if($this->output_is_loginbonus){
			$tmp = $this->output_is_loginbonus;
			
			$this->api_result[self::OUTPUT_IS_LOGINBONUS] = $tmp;
		}
		
		if($this->output_master_telop_list){
			$tmp = $this->getMasterTelopList();
			if($tmp !== FALSE){
				$this->api_result[self::OUTPUT_MASTER_TELOP_LIST_ARRAY] = $tmp;
			}
		}
		
		if($this->output_telop_list){
			$tmp = $this->getTelopList();
			if($tmp !== FALSE){
				$this->api_result[self::OUTPUT_TELOP_LIST_ARRAY] = $tmp;
			}
		}
		
		//// 出力
		$this->output->API($this->api_status,$this->api_status_code,$this->api_transition_code,$this->api_message,$this->api_access_time,$this->data_version_asset,$this->data_version_data,$this->api_result);

		//// Status_code == 1　の場合にメッセージ等をログ出力
		if(Config::get('debug.api_error_message_log_flag')){

			if($this->api_status_code == self::API_STATUS_CODE_ERROR || $this->api_status_code == self::API_STATUS_CODE_AUTH_ERROR || Fuel::$env == "development" || Fuel::$env == "test"){
				$this->time_end = microtime(true);
				Log::debug("--- start Api ---------------------------------------");
				$status = "Time:".($this->time_end - $this->time_start)." sec Memory:".memory_get_usage(true)." Byte";
				$status2 = "Platform:".$this->app_platform." AppVer:".$this->app_version." AppCsvVer:".$this->app_data_version_asset." AppDataVer:".$this->app_data_version_data." CsvVer:".$this->data_version_asset." DataVer:".$this->data_version_data;
				$status3 = "Auth:".($this->user_auth ? "TRUE" : "FALSE" )." Format:".$this->format." Header:".(Config::get('common.cipher.header.flag') ? "TRUE" : "FALSE" )." Param:".(Config::get('common.cipher.param.flag') ? "TRUE" : "FALSE" )." Post:".(Config::get('common.cipher.post.flag') ? "TRUE" : "FALSE" )." Result:".(Config::get('common.cipher.result.flag') ? "TRUE" : "FALSE" );
				$status4 = "Exclusion > UserAuth:".($this->exclusion_user_auth ? "TRUE" : "FALSE" )." AppVer:".($this->exclusion_app_ver ? "TRUE" : "FALSE")." AssetVer:".($this->exclusion_asset_ver ? "TRUE" : "FALSE" )." DataVer:".($this->exclusion_data_ver ? "TRUE" : "FALSE" );

				Log::debug("IP:".filter_input(INPUT_SERVER, 'REMOTE_ADDR',FILTER_SANITIZE_STRIPPED)." SRC:".Uri::string()." MSG:".$this->api_message."\n".$status."\n".$status2."\n".$status3."\n".$status4."\n"."ucode:[".$this->ucode."] token:[".$this->token."] send_time:[".$this->send_time."] MID:[".$this->MID."] \n"."HEADER:".var_export(Input::headers(),true)."\n"."GET:".var_export($this->in_get,true)."\n"."PARAM:".var_export($this->in_param,true)."\n"."INPUT:".var_export($this->in_post,true)."");

				//// ログ出力除外チェック
				// 明示的にtrue設定されてない/falseに設定されてる場合はチェック必須とする
				if($this->exclusion_debug_log === false){
					Log::debug("\n"."RESULT:".  var_export($this->api_result,true));
				}

				// Profiler使用の場合はログにSQLを出力する
				Log::debug("--- database ---\n");
				Profiler::db_log();

				Log::debug("--- end Api ----------------------------------------\n");

			}
		}

		//// 全コミット
		if(count(Database_Connection::$started_instances) > 0){
			Database_Connection::allCommit();
		}

		return $response;
	}

	/**
	 * エラー用の出力設定<br>
	 * ステータス等は明示的に指定して下さい
	 *
	 * @param string $api_message API出力メッセージ
	 * @param integer $api_status APIステータス文字列
	 * @param integer $api_status_code APIステータスコード
	 * @param integer $api_transition_code アプリ遷移制御コード
	 * @param array $api_result API出力データ
	 * @param boolean $before_message true:before()でのエラーだった false:before()以外でのエラーだった
	 * @return なし
	 */
	function setErrorResult($api_message = "",$api_status = self::API_STATUS_NG,$api_status_code = self::API_STATUS_CODE_ERROR,$api_transition_code = self::API_TRANSITION_TITLE,$api_result = array(),$before_error = false) {

		$this->api_message				= $api_message;
		$this->api_status				= $api_status;
		$this->api_status_code			= $api_status_code;
		$this->api_transition_code	= $api_transition_code;

		$this->api_result				= $api_result;
		$this->before_error			= $before_error;
	}

	/**
	 * 正常時用の出力設定<br>
	 * ステータス等は暗黙的に設定されます
	 *
	 * @param array $api_result API出力データ
	 * @param array $api_message API出力メッセージ
	 * @return なし
	 */
	function setResult($api_result = array(),$api_message = "") {

		$this->api_result				= $api_result;
		$this->api_message				= $api_message;

		// 正常用なのでステータスは正常とし、次の画面へ遷移とさせる
		$this->api_status				= self::API_STATUS_OK;
		$this->api_status_code			= self::API_STATUS_CODE_OK;
		$this->api_transition_code	= self::API_TRANSITION_NEXT;

		// 正常用なのでbefore_errorはfalseとする
		$this->before_error			= FALSE;
	}

	/**
	 * プラットフォーム識別子から、サーバが許可しているバージョンかをチェック
	 *
	 * @param string $app_platform プラットフォーム識別子
	 * @param int $app_version_major アプリのメジャーバージョン値
	 * @param int $app_version_minor アプリのマイナーバージョン値
	 * @param int $app_version_build アプリのビルド値
	 * @return boolean
	 */
	public function chkAppVersion($app_platform,$app_version_major,$app_version_minor,$app_version_build){

		$version = Config::get('version.platform.'.$app_platform,false);

		if($version === false){
			// 対象プラットフォームのバージョンが定義されてなかった
			return false;
		}

		if($version[0] < $app_version_major){
			// 規定バージョンに達してないのでエラー
			return true;
		}

		if($version[1] < $app_version_minor){
			// 規定バージョンに達してないのでエラー
			return true;
		}

		if($version[2] < $app_version_build){
			// 規定バージョンに達してないのでエラー
			return true;
		}
		return false;
	}

	/**
	 * $this->User の中から必要なデータだけ返す
	 *
	 * @return mixed
	 */
	public function getMyUserData($flag=FALSE){

		$result = FALSE;

		if($flag){
			$result = $this->base_user->_getUserMaster($this->ucode,$this->token,TRUE);

			if(!$result){
				return FALSE;
			}else{
				// 取得できたので、ユーザ情報をローカル変数にセット
				$this->User	= $result;
			}
		}

		if($this->user_auth == TRUE){
			$User = new User();
			$result = $User->formatUserMasterForClient($this->User);
		}

		return $result;
	}
	
	/**
	 * 最新のユーザキャラクター一覧を返す
	 * 
	 * @return array
	 */
	private function getMyUserCharacterList(){
		\Autoloader::add_class('Character',APPPATH.'modules/game/Character.php');
		$Character = new Character();
		return $Character->getUserCharactetAllForClient($this->MID);
	}
	
	/**
	 * 最新のユーザアイテム一覧を返す
	 * 
	 * @return array
	 */
	private function getMyUserItemList(){
		\Autoloader::add_class('Item',APPPATH.'modules/game/Item.php');
		$Item = new Item();
		return $Item->getSyncFormatUserItem($this->MID);
	}
	
	/**
	 * 最新の常設テロップ一覧を返す(マスター)
	 * 
	 * @return array
	 */
	private function getMasterTelopList(){
		\Autoloader::add_class('MasterTelop',APPPATH.'modules/master/Telop.php');
		$MasterTelop = new MasterTelop();
		return $MasterTelop->getMasterAllToFormat();
	}
	
	/**
	 * 最新の緊急テロップ一覧を返す(DB)
	 * 
	 * @return array
	 */
	private function getTelopList(){
		\Autoloader::add_class('Telop',APPPATH.'modules/game/Telop.php');
		$Telop = new Telop();
		return $Telop->getTelopList();
	}
	
}

