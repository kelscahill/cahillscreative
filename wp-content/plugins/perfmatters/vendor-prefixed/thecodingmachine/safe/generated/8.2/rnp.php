<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\RnpException;
/**
 * @param \RnpFFI $ffi
 * @param string $input
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_decrypt(\Perfmatters\Vendor\RnpFFI $ffi, string $input) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_decrypt($ffi, $input);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param string $input
 * @param int $flags
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_dump_packets_to_json(string $input, int $flags) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_dump_packets_to_json($input, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param string $input
 * @param int $flags
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_dump_packets(string $input, int $flags) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_dump_packets($input, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param string $pub_format
 * @param string $sec_format
 * @return \RnpFFI
 * @throws RnpException
 *
 * @internal
 */
function rnp_ffi_create(string $pub_format, string $sec_format) : \Perfmatters\Vendor\RnpFFI
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_ffi_create($pub_format, $sec_format);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param callable $password_callback
 * @throws RnpException
 *
 * @internal
 */
function rnp_ffi_set_pass_provider(\Perfmatters\Vendor\RnpFFI $ffi, callable $password_callback) : void
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_ffi_set_pass_provider($ffi, $password_callback);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
}
/**
 * @param \RnpFFI $ffi
 * @param string $input
 * @param int $flags
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_import_keys(\Perfmatters\Vendor\RnpFFI $ffi, string $input, int $flags) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_import_keys($ffi, $input, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $input
 * @param int $flags
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_import_signatures(\Perfmatters\Vendor\RnpFFI $ffi, string $input, int $flags) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_import_signatures($ffi, $input, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $key_fp
 * @param string $subkey_fp
 * @param string $uid
 * @param int $flags
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_key_export_autocrypt(\Perfmatters\Vendor\RnpFFI $ffi, string $key_fp, string $subkey_fp, string $uid, int $flags) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_key_export_autocrypt($ffi, $key_fp, $subkey_fp, $uid, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $key_fp
 * @param int $flags
 * @param array $options
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_key_export_revocation(\Perfmatters\Vendor\RnpFFI $ffi, string $key_fp, int $flags, ?array $options = null) : string
{
    \error_clear_last();
    if ($options !== null) {
        $safeResult = \Perfmatters\Vendor\rnp_key_export_revocation($ffi, $key_fp, $flags, $options);
    } else {
        $safeResult = \Perfmatters\Vendor\rnp_key_export_revocation($ffi, $key_fp, $flags);
    }
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $key_fp
 * @param int $flags
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_key_export(\Perfmatters\Vendor\RnpFFI $ffi, string $key_fp, int $flags) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_key_export($ffi, $key_fp, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $key_fp
 * @return array
 * @throws RnpException
 *
 * @internal
 */
function rnp_key_get_info(\Perfmatters\Vendor\RnpFFI $ffi, string $key_fp) : array
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_key_get_info($ffi, $key_fp);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $key_fp
 * @param int $flags
 * @throws RnpException
 *
 * @internal
 */
function rnp_key_remove(\Perfmatters\Vendor\RnpFFI $ffi, string $key_fp, int $flags) : void
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_key_remove($ffi, $key_fp, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
}
/**
 * @param \RnpFFI $ffi
 * @param string $key_fp
 * @param int $flags
 * @param array $options
 * @throws RnpException
 *
 * @internal
 */
function rnp_key_revoke(\Perfmatters\Vendor\RnpFFI $ffi, string $key_fp, int $flags, ?array $options = null) : void
{
    \error_clear_last();
    if ($options !== null) {
        $safeResult = \Perfmatters\Vendor\rnp_key_revoke($ffi, $key_fp, $flags, $options);
    } else {
        $safeResult = \Perfmatters\Vendor\rnp_key_revoke($ffi, $key_fp, $flags);
    }
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
}
/**
 * @param \RnpFFI $ffi
 * @param string $identifier_type
 * @return array
 * @throws RnpException
 *
 * @internal
 */
function rnp_list_keys(\Perfmatters\Vendor\RnpFFI $ffi, string $identifier_type) : array
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_list_keys($ffi, $identifier_type);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $format
 * @param string $input_path
 * @param int $flags
 * @throws RnpException
 *
 * @internal
 */
function rnp_load_keys_from_path(\Perfmatters\Vendor\RnpFFI $ffi, string $format, string $input_path, int $flags) : void
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_load_keys_from_path($ffi, $format, $input_path, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
}
/**
 * @param \RnpFFI $ffi
 * @param string $format
 * @param string $input
 * @param int $flags
 * @throws RnpException
 *
 * @internal
 */
function rnp_load_keys(\Perfmatters\Vendor\RnpFFI $ffi, string $format, string $input, int $flags) : void
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_load_keys($ffi, $format, $input, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
}
/**
 * @param \RnpFFI $ffi
 * @param string $identifier_type
 * @param string $identifier
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_locate_key(\Perfmatters\Vendor\RnpFFI $ffi, string $identifier_type, string $identifier) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_locate_key($ffi, $identifier_type, $identifier);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $message
 * @param array $recipient_keys_fp
 * @param array $options
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_op_encrypt(\Perfmatters\Vendor\RnpFFI $ffi, string $message, array $recipient_keys_fp, ?array $options = null) : string
{
    \error_clear_last();
    if ($options !== null) {
        $safeResult = \Perfmatters\Vendor\rnp_op_encrypt($ffi, $message, $recipient_keys_fp, $options);
    } else {
        $safeResult = \Perfmatters\Vendor\rnp_op_encrypt($ffi, $message, $recipient_keys_fp);
    }
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $userid
 * @param string $key_alg
 * @param string $sub_alg
 * @param array $options
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_op_generate_key(\Perfmatters\Vendor\RnpFFI $ffi, string $userid, string $key_alg, ?string $sub_alg = null, ?array $options = null) : string
{
    \error_clear_last();
    if ($options !== null) {
        $safeResult = \Perfmatters\Vendor\rnp_op_generate_key($ffi, $userid, $key_alg, $sub_alg, $options);
    } elseif ($sub_alg !== null) {
        $safeResult = \Perfmatters\Vendor\rnp_op_generate_key($ffi, $userid, $key_alg, $sub_alg);
    } else {
        $safeResult = \Perfmatters\Vendor\rnp_op_generate_key($ffi, $userid, $key_alg);
    }
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $data
 * @param array $keys_fp
 * @param array $options
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_op_sign_cleartext(\Perfmatters\Vendor\RnpFFI $ffi, string $data, array $keys_fp, ?array $options = null) : string
{
    \error_clear_last();
    if ($options !== null) {
        $safeResult = \Perfmatters\Vendor\rnp_op_sign_cleartext($ffi, $data, $keys_fp, $options);
    } else {
        $safeResult = \Perfmatters\Vendor\rnp_op_sign_cleartext($ffi, $data, $keys_fp);
    }
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $data
 * @param array $keys_fp
 * @param array $options
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_op_sign_detached(\Perfmatters\Vendor\RnpFFI $ffi, string $data, array $keys_fp, ?array $options = null) : string
{
    \error_clear_last();
    if ($options !== null) {
        $safeResult = \Perfmatters\Vendor\rnp_op_sign_detached($ffi, $data, $keys_fp, $options);
    } else {
        $safeResult = \Perfmatters\Vendor\rnp_op_sign_detached($ffi, $data, $keys_fp);
    }
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $data
 * @param array $keys_fp
 * @param array $options
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_op_sign(\Perfmatters\Vendor\RnpFFI $ffi, string $data, array $keys_fp, ?array $options = null) : string
{
    \error_clear_last();
    if ($options !== null) {
        $safeResult = \Perfmatters\Vendor\rnp_op_sign($ffi, $data, $keys_fp, $options);
    } else {
        $safeResult = \Perfmatters\Vendor\rnp_op_sign($ffi, $data, $keys_fp);
    }
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $data
 * @param string $signature
 * @return array
 * @throws RnpException
 *
 * @internal
 */
function rnp_op_verify_detached(\Perfmatters\Vendor\RnpFFI $ffi, string $data, string $signature) : array
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_op_verify_detached($ffi, $data, $signature);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $data
 * @return array
 * @throws RnpException
 *
 * @internal
 */
function rnp_op_verify(\Perfmatters\Vendor\RnpFFI $ffi, string $data) : array
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_op_verify($ffi, $data);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param \RnpFFI $ffi
 * @param string $format
 * @param string $output_path
 * @param int $flags
 * @throws RnpException
 *
 * @internal
 */
function rnp_save_keys_to_path(\Perfmatters\Vendor\RnpFFI $ffi, string $format, string $output_path, int $flags) : void
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_save_keys_to_path($ffi, $format, $output_path, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
}
/**
 * @param \RnpFFI $ffi
 * @param string $format
 * @param string $output
 * @param int $flags
 * @throws RnpException
 *
 * @internal
 */
function rnp_save_keys(\Perfmatters\Vendor\RnpFFI $ffi, string $format, string &$output, int $flags) : void
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_save_keys($ffi, $format, $output, $flags);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
}
/**
 * @param string $type
 * @return string
 * @throws RnpException
 *
 * @internal
 */
function rnp_supported_features(string $type) : string
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rnp_supported_features($type);
    if ($safeResult === \false) {
        throw RnpException::createFromPhpError();
    }
    return $safeResult;
}
