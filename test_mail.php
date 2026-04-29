<?php
require 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

$transport = Transport::fromDsn('smtp://a217ae1b8f251e:a3c243e8cad407@sandbox.smtp.mailtrap.io:2525');
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('teyssirhouma@gmail.com')
    ->to('iyedrhouma@gmail.com')
    ->subject('Test EcoMarine')
    ->text('Test envoi email');

try {
    $mailer->send($email);
    echo "Email envoyé avec succès !";
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage();
}