<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckAiProvidersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_diagnostic_only_reports_safe_request_metadata(): void
    {
        config([
            'services.openai.key' => 'openai-test-secret',
            'services.gemini.key' => 'gemini-test-secret',
            'services.deepseek.key' => 'deepseek-test-secret',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'usage' => ['prompt_tokens' => 6, 'completion_tokens' => 2, 'total_tokens' => 8],
            ], 200, ['x-ratelimit-remaining-requests' => '17']),
            'generativelanguage.googleapis.com/*' => Http::response([
                'usageMetadata' => ['promptTokenCount' => 6, 'candidatesTokenCount' => 2, 'totalTokenCount' => 8],
            ], 200),
            'api.deepseek.com/*' => Http::response([
                'usage' => ['prompt_tokens' => 6, 'completion_tokens' => 2, 'total_tokens' => 8],
            ], 200),
        ]);

        $this->assertSame(0, Artisan::call('ai:check-providers'));

        $output = Artisan::output();
        $this->assertStringContainsString('OpenAI', $output);
        $this->assertStringContainsString('Gemini', $output);
        $this->assertStringContainsString('DeepSeek', $output);
        $this->assertStringContainsString('200', $output);
        $this->assertStringContainsString('prompt_tokens', $output);
        $this->assertStringContainsString('x-ratelimit-remaining-requests', $output);
        $this->assertStringNotContainsString('openai-test-secret', $output);
        $this->assertStringNotContainsString('gemini-test-secret', $output);
        $this->assertStringNotContainsString('deepseek-test-secret', $output);

        Http::assertSentCount(3);
        Http::assertSent(fn (ClientRequest $request): bool =>
            str_contains($request->body(), 'Reply with exactly OK.')
            && ($request->data()['max_completion_tokens'] ?? null) === 8
        );
    }

    public function test_provider_diagnostic_shows_safe_error_code_without_response_message(): void
    {
        config([
            'services.openai.key' => 'openai-test-secret',
            'services.gemini.key' => null,
            'services.deepseek.key' => null,
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => [
                    'code' => 'insufficient_quota',
                    'message' => 'Do not expose provider response text.',
                ],
            ], 429),
        ]);

        $this->assertSame(1, Artisan::call('ai:check-providers'));

        $output = Artisan::output();
        $this->assertStringContainsString('insufficient_quota', $output);
        $this->assertStringNotContainsString('Do not expose provider response text.', $output);
        $this->assertStringNotContainsString('openai-test-secret', $output);
    }

    public function test_provider_diagnostic_fails_if_a_key_is_not_configured(): void
    {
        config([
            'services.openai.key' => null,
            'services.gemini.key' => null,
            'services.deepseek.key' => null,
        ]);

        Http::fake();

        $this->assertSame(1, Artisan::call('ai:check-providers'));
        $this->assertStringContainsString('not configured', Artisan::output());
        Http::assertNothingSent();
    }
}
