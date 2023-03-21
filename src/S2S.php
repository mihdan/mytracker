<?php
/**
 * Class S2S.
 *
 * @package mytravker
 * @link https://github.com/tracker-my-com/s2s-api/blob/master/src/Example.php
 */

namespace VK\MyTracker;

use WP_User;

/**
 * Обёртка над API S2S.
 */
class S2S {
	/**
	 * WP_OSA instance.
	 *
	 * @var WPOSA $wposa
	 */
	public $wposa;

	const API_BASE = 'https://tracker-s2s.my.com/v1/%s/?idApp=%d';

	/**
	 * Идентификатор приложения.
	 *
	 * @var int $app_id
	 */
	private int $app_id;

	/**
	 * Ключ (токен) приложения.
	 *
	 * @var string $api_key
	 */
	private string $api_key;

	/**
	 * Constructor.
	 *
	 * @param WPOSA $wposa WPOSA instance.
	 */
	public function __construct( WPOSA $wposa ) {
		$this->wposa   = $wposa;
		$this->app_id  = (int) $this->wposa->get_option( 'app_id', 'api', 0 );
		$this->api_key = $this->wposa->get_option( 'api_key', 'api', '' );
	}

	/**
	 * Получает идентификатор приложения.
	 *
	 * @return int
	 */
	public function get_app_id(): int {
		return $this->app_id;
	}

	/**
	 * Получает токен приложения.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}

	/**
	 * Инициализация хуков.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		add_action( 'wp_login', [ $this, 'on_login' ], 10, 2 );
		add_action( 'user_register', [ $this, 'on_registration' ] );
	}

	/**
	 * Отправить событие об авторизации при входе на сайт.
	 *
	 * @param string  $login Логин.
	 * @param WP_User $user  Объект пользователя.
	 *
	 * @return void
	 */
	public function on_login( string $login, WP_User $user ): void {
		$this->send_login_event(
			[
				'customUserId' => $user->ID,
			]
		);
	}

	/**
	 * Отправить событие о регистрации при добавлении
	 * нового пользователя на сайт.
	 *
	 * @param int $user_id Идентификатор пользователя.
	 *
	 * @return void
	 */
	public function on_registration( int $user_id ): void {
		$this->send_registration_event(
			[
				'customUserId' => $user_id,
			]
		);
	}

	/**
	 * Отправка события о регистрации.
	 *
	 * @param array $data Данные по пользователю.
	 *
	 * @return bool
	 */
	public function send_registration_event( array $data ): bool {
		return $this->request( 'registration', $data );
	}

	/**
	 * Отправка события об авторизации.
	 *
	 * @param array $data Данные по пользователю.
	 *
	 * @return bool
	 */
	public function send_login_event( array $data ): bool {
		return $this->request( 'login', $data );
	}

	/**
	 * Отправка запроса в API.
	 *
	 * @param string $method Название метода.
	 * @param array  $data   Данные по пользователю.
	 *
	 * @return true
	 */
	private function request( string $method, array $data ): bool {
		$defaults = [
			'eventTimestamp' => time(),
		];

		$data = wp_parse_args( $data, $defaults );

		$args = [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => $this->get_api_key(),
			],
			'body'    => wp_json_encode( $data ),
		];

		$response = wp_remote_post(
			sprintf( self::API_BASE, $method, $this->get_app_id() ),
			$args
		);

		$status = wp_remote_retrieve_response_code( $response );

		if ( $status === 200 ) {
			return true;
		} else {
			return false;
		}
	}
}