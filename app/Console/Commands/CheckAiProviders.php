<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class CheckAiProviders extends Command
{
    protected $signature = 'ai:check-providers';

    protected $description = 'Safely test configured AI provider credentials and report response metadata.';

    public function handle(): int
    {
        set_time_limit(90);

        $rows = [];
        $allSuccessful = true;

        foreach ($this->providers() as $name => $provider) {
            $key = config($provider['key']);
            $model = config($provider['model']);

            if (! is_string($key) || trim($key) === '') {
                $rows[] = [$name, (string) $model, 'not configured', '—', '—', '—', '—'];
                $allSuccessful = false;
                continue;
            }

            $startedAt = microtime(true);

            try {
                $response = $provider['send']($key, $model);
                $status = (string) $response->status();
                $usage = $this->usage($response->json(), $provider['usage']);
                $rateLimits = $this->rateLimitHeaders($response->headers());
                $diagnostic = $response->successful() ? '—' : $this->safeErrorCode($response->json());
                $allSuccessful = $allSuccessful && $response->successful();
            } catch (ConnectionException $exception) {
                $status = 'connection error';
                $usage = '—';
                $rateLimits = '—';
                $diagnostic = $exception::class;
                $allSuccessful = false;
            } catch (Throwable $exception) {
                $status = 'request error';
                $usage = '—';
                $rateLimits = '—';
                $diagnostic = $exception::class;
                $allSuccessful = false;
            }

            $rows[] = [
                $name,
                (string) $model,
                $status,
                number_format((microtime(true) - $startedAt) * 1000).' ms',
                $usage,
                $rateLimits,
                $diagnostic,
            ];
        }

        $this->table(['Provider', 'Configured model', 'HTTP status', 'Latency', 'Usage', 'Rate-limit headers', 'Safe error code'], $rows);
        $this->line('Requests used the synthetic prompt "Reply with exactly OK." No keys, response text, or error bodies are displayed.');
        $this->line('A successful request confirms credential/model access only; account quotas and billing limits require the provider dashboard.');

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }

    private function providers(): array
    {
        return [
            'OpenAI' => [
                'key' => 'services.openai.key',
                'model' => 'services.openai.model',
                'usage' => ['prompt_tokens', 'completion_tokens', 'total_tokens'],
                'send' => fn (string $key, string $model) => Http::connectTimeout(5)
                    ->timeout(12)
                    ->acceptJson()
                    ->withToken($key)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [['role' => 'user', 'content' => 'Reply with exactly OK.']],
                        'max_completion_tokens' => 8,
                        'temperature' => 0,
                    ]),
            ],
            'Gemini' => [
                'key' => 'services.gemini.key',
                'model' => 'services.gemini.model',
                'usage' => ['promptTokenCount', 'candidatesTokenCount', 'totalTokenCount'],
                'send' => fn (string $key, string $model) => Http::connectTimeout(5)
                    ->timeout(12)
                    ->acceptJson()
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent?key='.urlencode($key),
                        [
                            'contents' => [[
                                'parts' => [['text' => 'Reply with exactly OK.']],
                            ]],
                            'generationConfig' => [
                                'maxOutputTokens' => 8,
                                'temperature' => 0,
                            ],
                        ]
                    ),
            ],
            'DeepSeek' => [
                'key' => 'services.deepseek.key',
                'model' => 'services.deepseek.model',
                'usage' => ['prompt_tokens', 'completion_tokens', 'total_tokens'],
                'send' => fn (string $key, string $model) => Http::connectTimeout(5)
                    ->timeout(12)
                    ->acceptJson()
                    ->withToken($key)
                    ->post('https://api.deepseek.com/chat/completions', [
                        'model' => $model,
                        'messages' => [['role' => 'user', 'content' => 'Reply with exactly OK.']],
                        'max_tokens' => 8,
                        'temperature' => 0,
                    ]),
            ],
        ];
    }

    private function usage(mixed $body, array $fields): string
    {
        if (! is_array($body)) {
            return 'unavailable';
        }

        $usage = $body['usage'] ?? $body['usageMetadata'] ?? [];
        if (! is_array($usage)) {
            return 'unavailable';
        }

        $reported = array_intersect_key($usage, array_flip($fields));

        return $reported === [] ? 'unavailable' : json_encode($reported, JSON_THROW_ON_ERROR);
    }

    private function rateLimitHeaders(array $headers): string
    {
        $reported = [];
        foreach ($headers as $name => $values) {
            $normalized = strtolower($name);
            if (str_starts_with($normalized, 'x-ratelimit-')
                || str_starts_with($normalized, 'x-goog-quota-')
                || $normalized === 'retry-after') {
                $reported[$name] = $values[0] ?? '';
            }
        }

        return $reported === [] ? 'not provided' : json_encode($reported, JSON_THROW_ON_ERROR);
    }

    private function safeErrorCode(mixed $body): string
    {
        if (! is_array($body)) {
            return 'not provided';
        }

        $error = $body['error'] ?? [];
        if (! is_array($error)) {
            return 'not provided';
        }

        $code = $error['code'] ?? $error['status'] ?? $error['type'] ?? null;
        if (! is_string($code) && ! is_int($code)) {
            return 'not provided';
        }

        return substr(preg_replace('/[^A-Za-z0-9._-]/', '', (string) $code), 0, 80) ?: 'not provided';
    }
}
