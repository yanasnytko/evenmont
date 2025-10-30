<?php

if (!extension_loaded('openssl')) {
  fwrite(STDERR, "❌ L'extension OpenSSL n'est pas chargée par ce PHP CLI.\n");
  exit(1);
}

$projectDir = __DIR__;
$confPath   = $projectDir . DIRECTORY_SEPARATOR . 'openssl.cnf';
if (!is_file($confPath)) {
  fwrite(STDERR, "❌ Fichier openssl.cnf introuvable à: $confPath\nCrée-le puis relance.\n");
  exit(1);
}

putenv("OPENSSL_CONF=$confPath");

$dir = $projectDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'jwt';
if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
  fwrite(STDERR, "❌ Impossible de créer le dossier: $dir\n");
  exit(1);
}

$passphrase = 'evenmont_passphrase';

$config = [
  "private_key_type" => OPENSSL_KEYTYPE_RSA,
  "private_key_bits" => 4096,
];

$priv = openssl_pkey_new($config);
if ($priv === false) {
  fwrite(STDERR, "❌ openssl_pkey_new a échoué.\n");
  while ($msg = openssl_error_string()) { fwrite(STDERR, "OpenSSL: $msg\n"); }
  exit(1);
}

if (!openssl_pkey_export($priv, $privatePem, $passphrase)) {
  fwrite(STDERR, "❌ openssl_pkey_export a échoué.\n");
  while ($msg = openssl_error_string()) { fwrite(STDERR, "OpenSSL: $msg\n"); }
  exit(1);
}

$details = openssl_pkey_get_details($priv);
if ($details === false || empty($details['key'])) {
  fwrite(STDERR, "❌ openssl_pkey_get_details a échoué.\n");
  while ($msg = openssl_error_string()) { fwrite(STDERR, "OpenSSL: $msg\n"); }
  exit(1);
}

// Écrit les PEM
file_put_contents($dir . DIRECTORY_SEPARATOR . 'private.pem', $privatePem);
file_put_contents($dir . DIRECTORY_SEPARATOR . 'public.pem',  $details['key']);

echo "Clés générées dans config/jwt\n";
echo "   - private.pem (protégée par passphrase)\n";
echo "   - public.pem\n";
echo "Passphrase: $passphrase (mets-la dans .env -> JWT_PASSPHRASE)\n";
