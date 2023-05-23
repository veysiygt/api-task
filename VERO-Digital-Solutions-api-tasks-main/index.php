<?php
require_once 'Autoloader.php';
Autoloader::register();
new Api();

class Api
{
	private static $db;

	public static function getDb()
	{
		return self::$db;
	}

	public function __construct()
	{
		self::$db = (new Database())->init();

		$uri = strtolower(trim((string)$_SERVER['PATH_INFO'], '/'));
		$httpVerb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

		$wildcards = [
			':any' => '[^/]+',
			':num' => '[0-9]+',
		];
		$routes = [
			'get constructionStages' => [
				'class' => 'ConstructionStages',
				'method' => 'getAll',
			],
			'get constructionStages/(:num)' => [
				'class' => 'ConstructionStages',
				'method' => 'getSingle',
			],
			'post constructionStages' => [
				'class' => 'ConstructionStages',
				'method' => 'post',
				'bodyType' => 'ConstructionStagesCreate'
			],
		];

		$response = [
			'error' => 'No such route',
		];

		if ($uri) {

			foreach ($routes as $pattern => $target) {
				$pattern = str_replace(array_keys($wildcards), array_values($wildcards), $pattern);
				if (preg_match('#^'.$pattern.'$#i', "{$httpVerb} {$uri}", $matches)) {
					$params = [];
					array_shift($matches);
					if ($httpVerb === 'post') {
						$data = json_decode(file_get_contents('php://input'));
						$params = [new $target['bodyType']($data)];
					}
					$params = array_merge($params, $matches);
					$response = call_user_func_array([new $target['class'], $target['method']], $params);
					break;
				}
			}

			echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		}
	}
}