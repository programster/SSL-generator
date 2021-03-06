<?php

# Must include autoloader before settings so we can load our driver.
require_once(__DIR__ . '/vendor/autoload.php');
new iRAP\Autoloader\Autoloader([__DIR__]);

# Include the settings, which specified which DNS driver we are using (digital ocean, route53, etc).
require_once(__DIR__ . '/Settings.php');


if (!isset($argv[1]))
{
    print "What is the full domain do you want a certificate for?" . PHP_EOL;
    $FQDN = readline();
}
else
{
    $FQDN = $argv[1];
}

$FQDN = strtolower($FQDN); // Caps would likely just mess everything up and not needed.


function getDnsTxtValueForDomain(string $domain) : string
{
    $cmd = ACMEPHP_COMMAND . " authorize --solver dns {$domain}";
    $output = shell_exec($cmd);
    $lines = explode(PHP_EOL, $output);

    if (strpos($lines[0], 'Could not open input file') !== false)
    {
        die("You have not correctly configured your ACMEPHP_COMMAND setting. Please check the path." . PHP_EOL);
    }

    foreach ($lines as $line)
    {
        if (strpos($line, 'TXT value') !== false)
        {
            $txtValueLine = trim($line);
            $txtValue = str_replace("TXT value:", "", $txtValueLine);
            $txtValue = trim($txtValue);
        }
    }

    return $txtValue;
}


$txtValue = getDnsTxtValueForDomain($FQDN);
$recordHostname = "_acme-challenge." . $FQDN;

/* @var $driver AcmeDnsDriverInterface */
$driver = Settings::getDnsDriver();
$driver->addTxtRecord($recordHostname, $txtValue);

print "Waiting for DNS propagation. This may take a while depending on your DNS provider..." . PHP_EOL;
$hostCheckCommand = "/usr/bin/host -t TXT " . $recordHostname;

while (true)
{
    $output = shell_exec($hostCheckCommand);

    if (strpos($output, $txtValue) !== FALSE)
    {
        print "found record! " . $output . PHP_EOL;
        break;
    }

    sleep(1);
}

// get acmephp to check
print "Requesting letsencrypt run the check..." . PHP_EOL;
$checkCommand = ACMEPHP_COMMAND . " check -s dns {$FQDN}";

while (true)
{
    $output = shell_exec($checkCommand);

    if (strpos($output, "The authorization check was successful!") !== FALSE)
    {
        print "found record! " . $output . PHP_EOL;
        break;
    }

    sleep(3);
}

# finally, make the request for the certificates.
$requestCommand = ACMEPHP_COMMAND . " request {$FQDN}";
$output = shell_exec($requestCommand);

// the user may or may not have been asked a series of questions for that cert, depending on whether
// it is their first time or not. This actually still works.

print $output . PHP_EOL;


// Copy the certificates to wherever this script is being called from:
mkdir($FQDN);
mkdir("{$FQDN}/nginx");
mkdir("{$FQDN}/apache");

$certsPath = getenv('HOME') . '/.acmephp/master/certs/' . $FQDN;

$chainfile = $certsPath . '/public/chain.pem';
$nginxCombinedFile = $certsPath . '/public/fullchain.pem';
$siteCert = $certsPath . '/public/cert.pem';
$privateKey = $certsPath . '/private/key.private.pem';

copy($chainfile, "{$FQDN}/apache/ca_bundle.crt");
copy($siteCert, "{$FQDN}/apache/{$FQDN}.crt");
copy($privateKey, "{$FQDN}/apache/{$FQDN}.private.pem");

copy($nginxCombinedFile, "{$FQDN}/nginx/{$FQDN}.crt");
copy($privateKey, "{$FQDN}/nginx/{$FQDN}.private.pem");

























