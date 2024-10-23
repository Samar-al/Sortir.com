<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

class SendEmailService
{
    
    public function __construct(
        private MailerInterface $mailer, 
        private string $adminMail
    ) {}

    public function send(
       
        string $to, 
        string $subject, 
        string $template,
        array $context,
    ): void
    {
        $email = (new TemplatedEmail())
            ->from($this->adminMail)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);
        
        $this->mailer->send($email); 
    }
}