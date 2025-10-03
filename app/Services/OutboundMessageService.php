<?php
namespace App\Services;

/**
 * Lightweight outbound messaging facade writing to the local log.
 */
class OutboundMessageService
{
    private string $logFile;

    public function __construct(?string $logFile = null)
    {
        $this->logFile = $logFile ?? dirname(__DIR__, 2) . '/logs/outbound.log';
    }

    public function sendEmail(string $to, string $subject, string $body): bool
    {
        $valid = filter_var($to, FILTER_VALIDATE_EMAIL);
        $this->log([
            'channel' => 'email',
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'status' => $valid ? 'queued' : 'skipped',
            'reason' => $valid ? null : 'invalid_email',
        ]);
        return (bool) $valid;
    }

    public function sendSms(string $to, string $message): bool
    {
        $sanitised = trim($to);
        $valid = $sanitised !== '';
        $this->log([
            'channel' => 'sms',
            'to' => $sanitised,
            'message' => $message,
            'status' => $valid ? 'queued' : 'skipped',
            'reason' => $valid ? null : 'missing_number',
        ]);
        return $valid;
    }

    private function log(array $payload): void
    {
        $record = '[' . date('c') . '] ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($this->logFile, $record, FILE_APPEND | LOCK_EX);
    }
}