<?php
namespace App\Bundle\ForgotPasswordBundle\Entity;

class PasswordResetToken
{
    private string $email;
    private string $token;
    private \DateTimeInterface $expiresAt;

    public function __construct(string $email)
    {
        $this->email     = $email;
        $this->token     = bin2hex(random_bytes(32));
        $this->expiresAt = new \DateTime('+1 hour');
    }

    public function getEmail(): string { return $this->email; }
    public function getToken(): string { return $this->token; }
    public function getExpiresAt(): \DateTimeInterface { return $this->expiresAt; }
    public function isExpired(): bool { return new \DateTime() > $this->expiresAt; }
}