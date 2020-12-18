<?php
$pri_color = PRJ_PRIMARY_COLOUR;
$sec_color = PRJ_SECONDARY_COLOUR;
$logo = WHOST . PRJ_EMAIL_ICON;
$whost = WHOST;

$alert_eml = <<<EML
<html lang="en-US">
<body style="background-color: #ecf0f1 !important; max-width: 640px; padding: 20px; padding-top:0">
<header style="border-top: solid 10px {$sec_color}; padding: 12px; margin-bottom: 8px; border-bottom: solid 2px {$pri_color}; text-align: right;">
<a href="{$whost}/app/dashboard/wallet-history"> <img style="height:62px" src="{$logo}" alt="Logo" /></a>
</header>
<section style="padding:12px">
<p>Dear %name%, <br> <br> A [%type%] transaction has occured on your wallet.</p>
<h3>Transaction detail</h3>
<table>
<tr style="border-bottom: solid 1px {$sec_color};"> <th style="padding:8px; text-align:right">Currency</th> <td style="padding:8px">%currency%</td> </tr>
<tr style="border-bottom: solid 1px {$sec_color};"> <th style="padding:8px; text-align:right">Amount</th> <td style="padding:8px">%amount%</td> </tr>
<tr style="border-bottom: solid 1px {$sec_color};"> <th style="padding:8px; text-align:right">New balance</th> <td style="padding:8px">%new_balance%</td> </tr>
<tr style="border-bottom: solid 1px {$sec_color};"> <th style="padding:8px; text-align:right">Date</th> <td style="padding:8px">%date%</td> </tr>
</table>
<p> More info about this transaction is available on your account dashboard - wallet history.</p>
<p>Best regards.</p>
</section>
</body>
</html>
EML;
