<?php
// mock_payment_gateway.php - local demo payment service. It validates card input and
// generates a fake payment reference without storing sensitive card details.

class MockPaymentGateway
{
    private const PAYMENT_METHODS = ['Visa', 'MasterCard', 'Debit Card'];

    public function getSupportedMethods(): array
    {
        return self::PAYMENT_METHODS;
    }

    public function validatePaymentInput(
        string $paymentMethod,
        string $cardholderName,
        string $cardNumber,
        string $expiryMonth,
        string $expiryYear,
        string $cvv
    ): array {
        $errors = [];

        $cleanCardholder = trim($cardholderName);
        if ($cleanCardholder === '') {
            $errors[] = 'Please enter the cardholder name.';
        }

        if (!in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            $errors[] = 'Please select a valid payment method.';
        }

        $cleanCard = preg_replace('/\D+/', '', $cardNumber);
        if (!preg_match('/^[0-9]{13,19}$/', $cleanCard)) {
            $errors[] = 'Please enter a valid card number.';
        }

        if (!preg_match('/^(0[1-9]|1[0-2])$/', $expiryMonth)) {
            $errors[] = 'Please enter a valid expiry month.';
        }

        if (!preg_match('/^[0-9]{2}$/', $expiryYear)) {
            $errors[] = 'Please enter a valid expiry year.';
        }

        if (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
            $errors[] = 'Please enter a valid CVV.';
        }

        if (empty($errors)) {
            $expiryYearFull = (int)(date('Y') / 100) * 100 + (int)$expiryYear;
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('n');
            $expiryMonthInt = (int)$expiryMonth;

            if ($expiryYearFull < $currentYear || ($expiryYearFull === $currentYear && $expiryMonthInt < $currentMonth)) {
                $errors[] = 'The card expiry date has passed.';
            }
        }

        return $errors;
    }

    public function processPayment(float $amount, string $paymentMethod, string $cardNumber): array
    {
        $cleanCard = preg_replace('/\D+/', '', $cardNumber);
        if ($cleanCard === '') {
            throw new InvalidArgumentException('A valid card number is required for payment.');
        }

        $maskedCard = '•••• ' . substr($cleanCard, -4);
        $reference = sprintf(
            'MOCK-%s-%s-%s',
            strtoupper(str_replace(' ', '', $paymentMethod)),
            substr($cleanCard, -4),
            strtoupper(bin2hex(random_bytes(4)))
        );

        return [
            'approved' => true,
            'amount' => round($amount, 2),
            'payment_method' => $paymentMethod,
            'masked_card' => $maskedCard,
            'reference' => $reference,
        ];
    }
}
