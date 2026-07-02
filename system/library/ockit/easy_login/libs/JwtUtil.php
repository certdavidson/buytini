<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs;

use OcKit\EasyLogin\Exceptions\ProviderException;

/**
 * Minimal JWT helpers used by Apple (ES256 signing) and any provider
 * that needs to decode an id_token payload without library dependencies.
 */
class JwtUtil
{
    /**
     * Sign a JWT with ES256 (ECDSA P-256, SHA-256).
     *
     * @param array  $header     ['alg'=>'ES256','kid'=>'...'] (typ added automatically)
     * @param array  $payload
     * @param string $privateKey PEM-encoded EC private key (.p8 file contents)
     */
    public static function signES256(array $header, array $payload, string $privateKey): string
    {
        $header['typ'] = 'JWT';
        $header['alg'] = 'ES256';

        $segments = [
            self::base64UrlEncode(json_encode($header,  JSON_UNESCAPED_SLASHES)),
            self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signingInput = implode('.', $segments);

        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new ProviderException('Cannot load EC private key: ' . openssl_error_string());
        }
        $derSig = '';
        if (!openssl_sign($signingInput, $derSig, $key, OPENSSL_ALGO_SHA256)) {
            throw new ProviderException('ES256 signing failed: ' . openssl_error_string());
        }

        // openssl_sign returns DER; ES256 JWT requires raw R||S (64 bytes)
        $rawSig = self::derToRawSig($derSig, 32);

        $segments[] = self::base64UrlEncode($rawSig);
        return implode('.', $segments);
    }

    /**
     * Decode JWT payload WITHOUT signature verification.
     * Use only when the JWT was retrieved over a trusted HTTPS channel from the issuer.
     */
    public static function decodePayloadUnsafe(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new ProviderException('Malformed JWT');
        }
        $payload = json_decode((string)self::base64UrlDecode($parts[1]), true);
        if (!is_array($payload)) {
            throw new ProviderException('Invalid JWT payload');
        }
        return $payload;
    }

    /**
     * Verify RS256 signature against a JWKS keyset (array of JWK entries with n/e/kid).
     * Returns decoded payload on success; throws ProviderException on any failure.
     */
    public static function verifyRS256(string $jwt, array $jwks): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new ProviderException('Malformed JWT');
        }
        [$h64, $p64, $s64] = $parts;

        $header = json_decode((string)self::base64UrlDecode($h64), true);
        if (!is_array($header) || ($header['alg'] ?? '') !== 'RS256') {
            throw new ProviderException('Unsupported JWT alg');
        }
        $kid = (string)($header['kid'] ?? '');
        if ($kid === '') {
            throw new ProviderException('JWT missing kid');
        }

        $jwk = null;
        foreach ($jwks as $k) {
            if (isset($k['kid']) && $k['kid'] === $kid) { $jwk = $k; break; }
        }
        if ($jwk === null || !isset($jwk['n'], $jwk['e'])) {
            throw new ProviderException('JWKS key not found for kid ' . $kid);
        }

        $pem = self::jwkRsaToPem((string)$jwk['n'], (string)$jwk['e']);
        $key = openssl_pkey_get_public($pem);
        if ($key === false) {
            throw new ProviderException('Cannot load RSA public key');
        }

        $sig    = self::base64UrlDecode($s64);
        $signed = $h64 . '.' . $p64;
        $ok     = openssl_verify($signed, $sig, $key, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            throw new ProviderException('JWT signature verification failed');
        }

        $payload = json_decode((string)self::base64UrlDecode($p64), true);
        if (!is_array($payload)) {
            throw new ProviderException('Invalid JWT payload');
        }
        return $payload;
    }

    /**
     * Build a PEM-encoded RSA public key from JWK base64url(n) + base64url(e).
     */
    private static function jwkRsaToPem(string $nB64, string $eB64): string
    {
        $n = self::base64UrlDecode($nB64);
        $e = self::base64UrlDecode($eB64);

        $encInt = function (string $bytes): string {
            // Strip leading zeros, then re-add one if high bit set (DER positive-integer rule)
            $bytes = ltrim($bytes, "\x00");
            if ($bytes === '' || (ord($bytes[0]) & 0x80)) {
                $bytes = "\x00" . $bytes;
            }
            return "\x02" . self::derLen(strlen($bytes)) . $bytes;
        };

        $rsaKey = $encInt($n) . $encInt($e);
        $rsaSeq = "\x30" . self::derLen(strlen($rsaKey)) . $rsaKey;

        // SubjectPublicKeyInfo: AlgorithmIdentifier (rsaEncryption) + BIT STRING
        $algoId  = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $bitStr  = "\x03" . self::derLen(strlen($rsaSeq) + 1) . "\x00" . $rsaSeq;
        $spki    = "\x30" . self::derLen(strlen($algoId) + strlen($bitStr)) . $algoId . $bitStr;

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function derLen(int $len): string
    {
        if ($len < 0x80) return chr($len);
        $bytes = '';
        while ($len > 0) { $bytes = chr($len & 0xff) . $bytes; $len >>= 8; }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string)base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Convert ECDSA DER signature (as returned by openssl_sign) to raw R||S bytes for JWT.
     * P-256 uses 32-byte R and 32-byte S.
     */
    private static function derToRawSig(string $der, int $partLen): string
    {
        // Strict DER parse: 0x30 len 0x02 lenR R 0x02 lenS S
        $offset = 0;
        if (($der[$offset++] ?? '') !== "\x30") {
            throw new ProviderException('Invalid DER signature (no SEQUENCE)');
        }
        $seqLen = ord($der[$offset++]);
        if ($seqLen & 0x80) {
            $n = $seqLen & 0x7F;
            $seqLen = 0;
            for ($i = 0; $i < $n; $i++) {
                $seqLen = ($seqLen << 8) | ord($der[$offset++]);
            }
        }

        $readInt = function () use (&$der, &$offset) {
            if (($der[$offset++] ?? '') !== "\x02") {
                throw new ProviderException('Invalid DER signature (no INTEGER)');
            }
            $len = ord($der[$offset++]);
            $val = substr($der, $offset, $len);
            $offset += $len;
            // Strip leading zero used to indicate positive sign
            return ltrim($val, "\x00");
        };

        $r = $readInt();
        $s = $readInt();

        $r = str_pad($r, $partLen, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, $partLen, "\x00", STR_PAD_LEFT);
        return $r . $s;
    }
}
