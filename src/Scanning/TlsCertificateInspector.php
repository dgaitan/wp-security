<?php

declare( strict_types=1 );

namespace WPSecurity\Scanning;

/**
 * Performs a raw TLS handshake to inspect a host's certificate.
 *
 * wp_remote_get()/cURL never expose the peer certificate to PHP userland
 * regardless of the 'sslverify' option, so certificate expiry cannot be
 * determined via the shared loopback request. This class opens its own
 * low-level TLS stream to capture and parse the peer certificate.
 *
 * This is the one collaborator in the scanning layer that performs real
 * socket I/O outside of wp_remote_get() and is therefore not exercised by
 * the recordable-HTTP-response test stub — see
 * tests/Integration/Scanning/TlsCertificateInspectorTest.php.
 */
class TlsCertificateInspector {

	/**
	 * @return array{valid_from: int, valid_to: int, days_until_expiry: int, subject_cn: string, issuer_cn: string, self_signed: bool}|null
	 */
	public function inspect( string $host, int $port = 443, int $timeoutSeconds = 10 ): ?array {
		if ( '' === $host ) {
			return null;
		}

		$streamContext = stream_context_create(
			[
				'ssl' => [
					'capture_peer_cert' => true,
					'verify_peer'       => false,
					'verify_peer_name'  => false,
				],
			]
		);

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- stream_socket_client() emits a PHP-level warning on connection failure/timeout/refused; failure is already handled below via the `false === $socket` check, not exceptions.
		$socket = @stream_socket_client(
			sprintf( 'ssl://%s:%d', $host, $port ),
			$errno,
			$errstr,
			$timeoutSeconds,
			STREAM_CLIENT_CONNECT,
			$streamContext
		);

		if ( false === $socket ) {
			return null;
		}

		$params = stream_context_get_params( $socket );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closes a raw TLS socket resource, not a filesystem handle; WP_Filesystem has no equivalent for stream sockets.
		fclose( $socket );

		$cert = $params['options']['ssl']['peer_certificate'] ?? null;

		if ( null === $cert ) {
			return null;
		}

		$parsed = openssl_x509_parse( $cert );

		if ( false === $parsed ) {
			return null;
		}

		$validFrom = (int) ( $parsed['validFrom_time_t'] ?? 0 );
		$validTo   = (int) ( $parsed['validTo_time_t'] ?? 0 );
		$subjectCn = (string) ( $parsed['subject']['CN'] ?? '' );
		$issuerCn  = (string) ( $parsed['issuer']['CN'] ?? '' );

		return [
			'valid_from'        => $validFrom,
			'valid_to'          => $validTo,
			'days_until_expiry' => (int) floor( ( $validTo - time() ) / DAY_IN_SECONDS ),
			'subject_cn'        => $subjectCn,
			'issuer_cn'         => $issuerCn,
			'self_signed'       => '' !== $subjectCn && $subjectCn === $issuerCn,
		];
	}
}
