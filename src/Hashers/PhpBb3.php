<?php

namespace MigrateToFlarum\OldPasswords\Hashers;

use Illuminate\Support\Arr;

class PhpBb3 extends AbstractHasher {
	function check(string $password, array $oldPassword): bool {
		return self::phpbb_check_hash($password, Arr::get($oldPassword, 'password'));
	}

	static function phpbb_check_hash($password, $hash) {
		$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		if (strlen($hash) == 34) return (self::_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
		return (md5($password) === $hash) ? true : false;
	}

	static function _hash_crypt_private($password, $setting, &$itoa64) {
		$output = '*';
		if (substr($setting, 0, 3) != '$H$' && substr($setting, 0, 3) != '$P$') return $output;

		$count_log2 = strpos($itoa64, $setting[3]);

		if ($count_log2 < 7 || $count_log2 > 30) return $output;

		$count = 1 << $count_log2;
		$salt = substr($setting, 4, 8);

		if (strlen($salt) != 8) return $output;

		if (PHP_VERSION >= 5) {
			$hash = md5($salt . $password, true);
			do {
				$hash = md5($hash . $password, true);
			} while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			} while (--$count);
		}
		$output = substr($setting, 0, 12);
		$output .= self::_hash_encode64($hash, 16, $itoa64);
		return $output;
	}

	static function _hash_encode64($input, $count, &$itoa64) {
		$output = '';
		$i = 0;
		do {
			$value = ord($input[$i++]);
			$output .= $itoa64[$value & 0x3f];

			if ($i < $count) $value |= ord($input[$i]) << 8;
			$output .= $itoa64[($value >> 6) & 0x3f];

			if ($i++ >= $count) break;

			if ($i < $count) $value |= ord($input[$i]) << 16;

			$output .= $itoa64[($value >> 12) & 0x3f];

			if ($i++ >= $count) break;

			$output .= $itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);
		return $output;
	}
}
