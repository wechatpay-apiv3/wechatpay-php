<?php declare(strict_types=1);

namespace WeChatPay\Exception;

const DEP_XML_PROTOCOL_IS_REACHABLE_EOL = 'New features are all in `APIv3`, there\'s no reason to continue use this kind client since v2.0.';

const ERR_INIT_MCHID_IS_MANDATORY = 'The merchant\' ID aka `mchid` is required, usually numerical.';
const ERR_INIT_SERIAL_IS_MANDATORY = 'The serial number of the merchant\'s certificate aka `serial` is required, usually hexadecial.';
const ERR_INIT_PRIVATEKEY_IS_MANDATORY = 'The merchant\'s private key aka `privateKey` is required, usual as pem format.';
const ERR_INIT_CERTS_IS_MANDATORY = 'The platform certificate(s) aka `certs` is required, paired as of `[$serial => $certificate]`.';
const ERR_INIT_CERTS_EXCLUDE_MCHSERIAL = 'The `certs(%1$s)` contains the merchant\'s certificate serial number(%2$s) which is not allowed here.';

const EV2_REQ_XML_NOTMATCHED_MCHID = 'The xml\'s structure[mch_id(%1$s)] doesn\'t matched the init one mchid(%2$s).';

const EV3_RES_HEADERS_INCOMPLETE = 'The response\'s Headers incomplete, must have(`%1$s`, `%2$s`, `%3$s` and `%4$s`).';
const EV3_RES_HEADER_TIMESTAMP_OFFSET = 'It\'s allowed time offset in ± %1$s seconds, the response was on %2$s, your\'s localtime on %3$s.';
const EV3_RES_HEADER_PLATFORM_SERIAL = 'Cannot found the serial(`%1$s`)\'s configuration, which\'s from the response(header:%2$s), your\'s %3$s.';
const EV3_RES_HEADER_SIGNATURE_DIGEST = 'Verify the response\'s data with: timestamp=%1$s, nonce=%2$s, signature=%3$s, cert=[%4$s => ...] failed.';

interface WeChatPayException
{
    const DEP_XML_PROTOCOL_IS_REACHABLE_EOL = DEP_XML_PROTOCOL_IS_REACHABLE_EOL;
    const EV3_RES_HEADERS_INCOMPLETE = EV3_RES_HEADERS_INCOMPLETE;
    const EV3_RES_HEADER_TIMESTAMP_OFFSET = EV3_RES_HEADER_TIMESTAMP_OFFSET;
    const EV3_RES_HEADER_PLATFORM_SERIAL = EV3_RES_HEADER_PLATFORM_SERIAL;
    const EV3_RES_HEADER_SIGNATURE_DIGEST = EV3_RES_HEADER_SIGNATURE_DIGEST;
}
