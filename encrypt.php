<?php

use app\openssl\OpenSsl;
use app\openssl\OpenSslKey;

//$key = new OpenSslKey();
//$key->generate();
//var_dump($key->exportPublic());
//var_dump($key->exportPrivate());
//exit;

//  require_once __DIR__ . '/OpenSsl.php';
var_dump(function_exists('openssl_public_encrypt'));
putenv('OPENSSL_CONF='.($opensslConf = __DIR__.'\\ssl\\openssl.cnf'));
//putenv("OPENSSL_CONF=C:\Program Files (x86)\Ampps\php\extras\openssl.cnf");
var_dump(file_exists(getenv('OPENSSL_CONF')),getenv('OPENSSL_CONF'));

//var_dump(PHP_VERSION);
  $data = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,';
  $sha1orig = sha1($data);
$dn = array(
  "countryName" => "BG",
  "stateOrProvinceName" => "Sofia Grad",
  "localityName" => "Sofia",
  "organizationName" => "Nicksoft Solutions",
  "organizationalUnitName" => "Password storage",
  "commonName" => "passstore",
  "emailAddress" => "info@nicksoft.com"
);
  $openSslSettings = [
//    "config" => $opensslConf,
    "digest_alg" => "sha512",
    'private_key_type'=>OPENSSL_KEYTYPE_RSA,
    'private_key_bits'=>4096,
  ];

$key = new OpenSslKey();
$key->generate();
$key->setPassword('pass1234');

$ssl = new OpenSsl($openSslSettings);
$encrypted = $ssl->publicEncryptBase64($data, $key);
var_dump($encrypted);
$decrypted = $ssl->privateDecryptBase64($encrypted, $key, 'pass1234');
$sha1decrypted = sha1($decrypted);
echo ($sha1decrypted == $sha1orig)?"Checksums match":"Decrypted is corrupted!";
echo '<br/>';
var_dump('decrypted: '.$decrypted);

$encrypted = $ssl->privateEncryptBase64($data, $key, 'pass1234');
assert($encrypted != $decrypted);
$decrypted = $ssl->publicDecryptBase64($encrypted, $key);
$sha1decrypted = sha1($decrypted);
echo 'test 2 - '.($sha1decrypted == $sha1orig)?"Checksums match":"Decrypted is corrupted!";
echo '<br/>';
var_dump('test 2 decrypted: '.$decrypted);


exit;


$encrypt_method = "AES-256-CBC";
$secret_key = 'This is my secret key';
$secret_iv = 'This is my secret iv';
$key = hash('sha256', $secret_key);
$iv = substr(hash('sha256', $secret_iv), 0, 16);

$privateKey = <<<EOD
-----BEGIN PRIVATE KEY-----
MIICeQIBADANBgkqhkiG9w0BAQEFAASCAmMwggJfAgEAAoGBAK92ohKTxz/njXNX
[..]
WCHS8ImF4xhmXSTTdQ==
-----END PRIVATE KEY-----
EOD;

function encrypt($plainData, $privatePEMKey){
  $encrypted = '';
  $plainData = str_split($plainData, $this->ENCRYPT_BLOCK_SIZE);
}

openssl_public_encrypt($data, $encrypted, $key, OPENSSL_PKCS1_PADDING );
var_dump(base64_encode(

));

?>
